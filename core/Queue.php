<?php
/**
 * Queue
 * Sistema de filas para processamento assíncrono de jobs
 *
 * Suporta múltiplos drivers:
 * - database: Armazena jobs em tabela MySQL
 * - redis: Usa Redis como backend (requer extensão)
 * - sync: Executa imediatamente (para dev/debug)
 *
 * @example
 * // Dispatch de job
 * Queue::push(SendEmailJob::class, ['to' => 'user@email.com']);
 *
 * // Com delay
 * Queue::later(60, SendEmailJob::class, $data); // 60 segundos
 *
 * // Em fila específica
 * Queue::on('emails')->push(SendEmailJob::class, $data);
 *
 * // Processar filas (CLI)
 * php aegis queue:work
 * php aegis queue:work --queue=emails
 */

class Queue {

    /**
     * Driver atual
     */
    private static $driver = 'database';

    /**
     * Fila padrão
     */
    private static $defaultQueue = 'default';

    /**
     * Fila selecionada para próximo dispatch
     */
    private static $selectedQueue = null;

    /**
     * Configurações
     */
    private static $config = [
        'table' => 'jobs',
        'failed_table' => 'failed_jobs',
        'retry_after' => 90, // segundos
        'max_tries' => 3
    ];

    /**
     * Estatísticas
     */
    private static $stats = [
        'pushed' => 0,
        'processed' => 0,
        'failed' => 0
    ];

    // ===================
    // CONFIGURATION
    // ===================

    /**
     * Configurar sistema de filas
     */
    public static function configure(array $config) {
        if (isset($config['driver'])) {
            self::$driver = $config['driver'];
        }
        if (isset($config['default_queue'])) {
            self::$defaultQueue = $config['default_queue'];
        }
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Selecionar fila para próximo dispatch
     */
    public static function on($queue) {
        self::$selectedQueue = $queue;
        return new static;
    }

    // ===================
    // DISPATCH
    // ===================

    /**
     * Adicionar job à fila
     *
     * @param string|object $job Classe do job ou instância
     * @param array $data Dados para o job
     * @return string|int ID do job
     */
    public static function push($job, array $data = []) {
        return self::dispatch($job, $data, 0);
    }

    /**
     * Adicionar job com delay
     *
     * @param int $delay Segundos de delay
     * @param string|object $job
     * @param array $data
     * @return string|int
     */
    public static function later($delay, $job, array $data = []) {
        return self::dispatch($job, $data, $delay);
    }

    /**
     * Dispatch interno
     */
    private static function dispatch($job, array $data, $delay) {
        $queue = self::$selectedQueue ?? self::$defaultQueue;
        self::$selectedQueue = null; // Reset

        // Se for sync, executar imediatamente
        if (self::$driver === 'sync') {
            return self::executeJob($job, $data);
        }

        $payload = [
            'job' => is_object($job) ? get_class($job) : $job,
            'data' => $data,
            'attempts' => 0,
            'max_tries' => self::$config['max_tries']
        ];

        $availableAt = time() + $delay;

        switch (self::$driver) {
            case 'database':
                $id = self::pushToDatabase($queue, $payload, $availableAt);
                break;
            case 'redis':
                $id = self::pushToRedis($queue, $payload, $availableAt);
                break;
            default:
                throw new RuntimeException("Queue driver not supported: " . self::$driver);
        }

        self::$stats['pushed']++;

        Logger::debug("Job queued", [
            'job' => $payload['job'],
            'queue' => $queue,
            'delay' => $delay,
            'id' => $id
        ]);

        return $id;
    }

    // ===================
    // DATABASE DRIVER
    // ===================

    /**
     * Adicionar job ao banco de dados
     */
    private static function pushToDatabase($queue, array $payload, $availableAt) {
        $table = self::$config['table'];

        DB::table($table)->insert([
            'queue' => $queue,
            'payload' => json_encode($payload),
            'attempts' => 0,
            'available_at' => date('Y-m-d H:i:s', $availableAt),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return DB::lastInsertId();
    }

    /**
     * Obter próximo job do banco
     */
    private static function popFromDatabase($queue) {
        $table = self::$config['table'];
        $retryAfter = self::$config['retry_after'];

        // Buscar job disponível
        $job = DB::table($table)
            ->where('queue', $queue)
            ->where('available_at', '<=', date('Y-m-d H:i:s'))
            ->where(function($q) use ($retryAfter) {
                $q->whereNull('reserved_at')
                  ->orWhere('reserved_at', '<=', date('Y-m-d H:i:s', time() - $retryAfter));
            })
            ->orderBy('id', 'ASC')
            ->first();

        if (!$job) {
            return null;
        }

        // Reservar job
        DB::table($table)
            ->where('id', $job['id'])
            ->update([
                'reserved_at' => date('Y-m-d H:i:s'),
                'attempts' => $job['attempts'] + 1
            ]);

        $job['attempts']++;
        $job['payload'] = json_decode($job['payload'], true);

        return $job;
    }

    /**
     * Remover job do banco
     */
    private static function deleteFromDatabase($id) {
        DB::table(self::$config['table'])->where('id', $id)->delete();
    }

    /**
     * Mover para failed_jobs
     */
    private static function failInDatabase($job, $exception) {
        $failedTable = self::$config['failed_table'];

        DB::table($failedTable)->insert([
            'queue' => $job['queue'],
            'payload' => json_encode($job['payload']),
            'exception' => $exception->getMessage() . "\n" . $exception->getTraceAsString(),
            'failed_at' => date('Y-m-d H:i:s')
        ]);

        self::deleteFromDatabase($job['id']);
    }

    // ===================
    // REDIS DRIVER
    // ===================

    /**
     * Adicionar job ao Redis
     */
    private static function pushToRedis($queue, array $payload, $availableAt) {
        $redis = self::getRedis();
        $id = uniqid('job_', true);

        $payload['id'] = $id;
        $payload['queue'] = $queue;

        if ($availableAt > time()) {
            // Job com delay - usar sorted set
            $redis->zAdd("queues:{$queue}:delayed", $availableAt, json_encode($payload));
        } else {
            // Job imediato
            $redis->rPush("queues:{$queue}", json_encode($payload));
        }

        return $id;
    }

    /**
     * Obter próximo job do Redis
     */
    private static function popFromRedis($queue) {
        $redis = self::getRedis();

        // Mover jobs delayed para a fila principal
        self::migrateDelayedJobs($queue);

        // Pop do início da fila
        $payload = $redis->lPop("queues:{$queue}");

        if (!$payload) {
            return null;
        }

        $job = json_decode($payload, true);
        $job['attempts'] = ($job['attempts'] ?? 0) + 1;

        return $job;
    }

    /**
     * Migrar jobs delayed que já estão disponíveis
     */
    private static function migrateDelayedJobs($queue) {
        $redis = self::getRedis();
        $now = time();

        // Buscar jobs com score <= now
        $jobs = $redis->zRangeByScore("queues:{$queue}:delayed", '-inf', $now);

        foreach ($jobs as $job) {
            $redis->zRem("queues:{$queue}:delayed", $job);
            $redis->rPush("queues:{$queue}", $job);
        }
    }

    /**
     * Obter conexão Redis
     */
    private static function getRedis() {
        static $redis = null;

        if ($redis === null) {
            if (!class_exists('Redis')) {
                throw new RuntimeException("Redis extension not installed");
            }

            $redis = new Redis();
            $host = self::$config['redis_host'] ?? '127.0.0.1';
            $port = self::$config['redis_port'] ?? 6379;
            $redis->connect($host, $port);

            if (isset(self::$config['redis_password'])) {
                $redis->auth(self::$config['redis_password']);
            }
        }

        return $redis;
    }

    // ===================
    // WORKER
    // ===================

    /**
     * Processar jobs da fila
     *
     * @param string $queue Nome da fila
     * @param array $options Opções do worker
     */
    public static function work($queue = null, array $options = []) {
        $queue = $queue ?? self::$defaultQueue;
        $sleep = $options['sleep'] ?? 3;
        $maxJobs = $options['max_jobs'] ?? 0;
        $timeout = $options['timeout'] ?? 60;
        $memory = $options['memory'] ?? 128;

        $processed = 0;

        while (true) {
            // Verificar memória
            if (memory_get_usage(true) / 1024 / 1024 >= $memory) {
                Logger::warning("Queue worker memory limit reached", ['memory' => $memory]);
                break;
            }

            // Obter próximo job
            $job = self::pop($queue);

            if ($job === null) {
                sleep($sleep);
                continue;
            }

            // Processar
            try {
                self::process($job, $timeout);
                $processed++;
                self::$stats['processed']++;
            } catch (Exception $e) {
                self::handleFailedJob($job, $e);
            }

            // Limite de jobs
            if ($maxJobs > 0 && $processed >= $maxJobs) {
                break;
            }
        }

        return $processed;
    }

    /**
     * Processar um único job
     */
    public static function workOnce($queue = null) {
        $queue = $queue ?? self::$defaultQueue;
        $job = self::pop($queue);

        if ($job === null) {
            return false;
        }

        try {
            self::process($job, 60);
            self::$stats['processed']++;
            return true;
        } catch (Exception $e) {
            self::handleFailedJob($job, $e);
            return false;
        }
    }

    /**
     * Obter próximo job
     */
    private static function pop($queue) {
        switch (self::$driver) {
            case 'database':
                return self::popFromDatabase($queue);
            case 'redis':
                return self::popFromRedis($queue);
            default:
                return null;
        }
    }

    /**
     * Processar job
     */
    private static function process($job, $timeout) {
        $payload = $job['payload'];
        $class = $payload['job'];
        $data = $payload['data'];

        Logger::debug("Processing job", [
            'job' => $class,
            'attempt' => $job['attempts']
        ]);

        // Executar com timeout
        $result = self::executeWithTimeout(function() use ($class, $data) {
            return self::executeJob($class, $data);
        }, $timeout);

        // Remover job processado
        if (isset($job['id'])) {
            switch (self::$driver) {
                case 'database':
                    self::deleteFromDatabase($job['id']);
                    break;
            }
        }

        Logger::info("Job processed", ['job' => $class]);

        return $result;
    }

    /**
     * Executar job
     */
    private static function executeJob($job, array $data) {
        if (is_string($job)) {
            if (!class_exists($job)) {
                throw new RuntimeException("Job class not found: {$job}");
            }
            $job = new $job();
        }

        if (!method_exists($job, 'handle')) {
            throw new RuntimeException("Job must have a handle() method");
        }

        return $job->handle($data);
    }

    /**
     * Executar com timeout
     */
    private static function executeWithTimeout(callable $callback, $timeout) {
        // Em PHP não há timeout nativo para funções
        // Usar pcntl_alarm se disponível
        if (function_exists('pcntl_alarm')) {
            pcntl_alarm($timeout);
        }

        try {
            return $callback();
        } finally {
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        }
    }

    /**
     * Lidar com job falhado
     */
    private static function handleFailedJob($job, Exception $e) {
        $payload = $job['payload'];
        $maxTries = $payload['max_tries'] ?? self::$config['max_tries'];

        Logger::error("Job failed", [
            'job' => $payload['job'],
            'attempt' => $job['attempts'],
            'error' => $e->getMessage()
        ]);

        self::$stats['failed']++;

        // Ainda tem tentativas?
        if ($job['attempts'] < $maxTries) {
            // Re-enfileirar com delay exponencial
            $delay = pow(2, $job['attempts']) * 10; // 20s, 40s, 80s...
            self::later($delay, $payload['job'], $payload['data']);
        } else {
            // Mover para failed_jobs
            switch (self::$driver) {
                case 'database':
                    self::failInDatabase($job, $e);
                    break;
            }

            // Chamar método failed() do job se existir
            $class = $payload['job'];
            if (class_exists($class) && method_exists($class, 'failed')) {
                try {
                    (new $class())->failed($payload['data'], $e);
                } catch (Exception $ex) {
                    // Ignorar erros no handler de falha
                }
            }
        }
    }

    // ===================
    // UTILITIES
    // ===================

    /**
     * Obter tamanho da fila
     */
    public static function size($queue = null) {
        $queue = $queue ?? self::$defaultQueue;

        switch (self::$driver) {
            case 'database':
                return DB::table(self::$config['table'])
                    ->where('queue', $queue)
                    ->count();
            case 'redis':
                return self::getRedis()->lLen("queues:{$queue}");
            default:
                return 0;
        }
    }

    /**
     * Limpar fila
     */
    public static function clear($queue = null) {
        $queue = $queue ?? self::$defaultQueue;

        switch (self::$driver) {
            case 'database':
                DB::table(self::$config['table'])
                    ->where('queue', $queue)
                    ->delete();
                break;
            case 'redis':
                self::getRedis()->del("queues:{$queue}");
                self::getRedis()->del("queues:{$queue}:delayed");
                break;
        }
    }

    /**
     * Listar jobs falhados
     */
    public static function failed($limit = 50) {
        return DB::table(self::$config['failed_table'])
            ->orderBy('failed_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Retry job falhado
     */
    public static function retry($id) {
        $failedTable = self::$config['failed_table'];

        $job = DB::table($failedTable)->where('id', $id)->first();

        if (!$job) {
            return false;
        }

        $payload = json_decode($job['payload'], true);

        self::on($job['queue'])->push($payload['job'], $payload['data']);

        DB::table($failedTable)->where('id', $id)->delete();

        return true;
    }

    /**
     * Retry todos os jobs falhados
     */
    public static function retryAll() {
        $jobs = self::failed(1000);
        $count = 0;

        foreach ($jobs as $job) {
            if (self::retry($job['id'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Limpar jobs falhados
     */
    public static function flushFailed() {
        return DB::table(self::$config['failed_table'])->delete();
    }

    /**
     * Obter estatísticas
     */
    public static function getStats() {
        return array_merge(self::$stats, [
            'pending' => self::size(),
            'failed_count' => DB::table(self::$config['failed_table'])->count()
        ]);
    }
}

/**
 * Classe base para Jobs
 */
abstract class Job {

    /**
     * Número máximo de tentativas
     */
    public $tries = 3;

    /**
     * Timeout em segundos
     */
    public $timeout = 60;

    /**
     * Fila para este job
     */
    public $queue = 'default';

    /**
     * Delay antes de processar
     */
    public $delay = 0;

    /**
     * Processar o job
     *
     * @param array $data Dados do job
     * @return mixed
     */
    abstract public function handle(array $data);

    /**
     * Handler de falha (opcional)
     *
     * @param array $data
     * @param Exception $e
     */
    public function failed(array $data, Exception $e) {
        // Override para lidar com falhas
    }

    /**
     * Dispatch estático
     */
    public static function dispatch(array $data = []) {
        $job = new static();
        $queue = $job->queue;
        $delay = $job->delay;

        if ($delay > 0) {
            return Queue::on($queue)->later($delay, static::class, $data);
        }

        return Queue::on($queue)->push(static::class, $data);
    }
}
