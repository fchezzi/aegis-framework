<?php
/**
 * Scheduler
 * Agendador de tarefas para execução periódica
 *
 * Similar ao cron, mas gerenciado pelo framework.
 * Executar via cron a cada minuto:
 * * * * * * cd /path/to/aegis && php aegis schedule:run >> /dev/null 2>&1
 *
 * @example
 * // Em app/Console/schedule.php ou bootstrap
 * Scheduler::command('cache:clear')->daily();
 * Scheduler::call(function() { ... })->hourly();
 * Scheduler::job(CleanupJob::class)->dailyAt('03:00');
 * Scheduler::exec('rm -rf /tmp/cache/*')->weekly();
 */

class Scheduler {

    /**
     * Tarefas agendadas
     */
    private static $tasks = [];

    /**
     * Timezone para agendamento
     */
    private static $timezone = 'America/Sao_Paulo';

    /**
     * Output das execuções
     */
    private static $output = [];

    /**
     * Se está em modo de teste
     */
    private static $testing = false;

    // ===================
    // TASK REGISTRATION
    // ===================

    /**
     * Agendar comando Artisan/CLI
     */
    public static function command($command) {
        $task = new ScheduledTask('command', $command);
        self::$tasks[] = $task;
        return $task;
    }

    /**
     * Agendar callback
     */
    public static function call(callable $callback, array $parameters = []) {
        $task = new ScheduledTask('callback', $callback, $parameters);
        self::$tasks[] = $task;
        return $task;
    }

    /**
     * Agendar job
     */
    public static function job($job, array $data = [], $queue = null) {
        $task = new ScheduledTask('job', $job, ['data' => $data, 'queue' => $queue]);
        self::$tasks[] = $task;
        return $task;
    }

    /**
     * Agendar comando shell
     */
    public static function exec($command) {
        $task = new ScheduledTask('exec', $command);
        self::$tasks[] = $task;
        return $task;
    }

    // ===================
    // EXECUTION
    // ===================

    /**
     * Executar tarefas pendentes
     */
    public static function run() {
        $now = new DateTime('now', new DateTimeZone(self::$timezone));
        $results = [];

        foreach (self::$tasks as $task) {
            if ($task->isDue($now)) {
                if ($task->filtersPass()) {
                    try {
                        $result = $task->run();
                        $results[] = [
                            'task' => $task->getDescription(),
                            'status' => 'success',
                            'output' => $result
                        ];

                        Logger::info("Scheduled task completed", [
                            'task' => $task->getDescription()
                        ]);
                    } catch (Exception $e) {
                        $results[] = [
                            'task' => $task->getDescription(),
                            'status' => 'failed',
                            'error' => $e->getMessage()
                        ];

                        Logger::error("Scheduled task failed", [
                            'task' => $task->getDescription(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        self::$output = $results;
        return $results;
    }

    /**
     * Listar tarefas agendadas
     */
    public static function list() {
        $list = [];

        foreach (self::$tasks as $task) {
            $list[] = [
                'description' => $task->getDescription(),
                'expression' => $task->getExpression(),
                'next_run' => $task->getNextRunDate()
            ];
        }

        return $list;
    }

    /**
     * Obter output da última execução
     */
    public static function getOutput() {
        return self::$output;
    }

    /**
     * Definir timezone
     */
    public static function timezone($tz) {
        self::$timezone = $tz;
    }

    /**
     * Limpar tarefas
     */
    public static function clear() {
        self::$tasks = [];
    }

    /**
     * Modo de teste
     */
    public static function fake() {
        self::$testing = true;
    }
}

/**
 * Tarefa agendada
 */
class ScheduledTask {

    private $type;
    private $task;
    private $parameters;
    private $expression = '* * * * *';
    private $description;
    private $withoutOverlapping = false;
    private $onOneServer = false;
    private $runInBackground = false;
    private $filters = [];
    private $rejects = [];
    private $beforeCallbacks = [];
    private $afterCallbacks = [];
    private $environments = [];

    public function __construct($type, $task, $parameters = []) {
        $this->type = $type;
        $this->task = $task;
        $this->parameters = $parameters;
        $this->description = is_string($task) ? $task : 'Closure';
    }

    // ===================
    // SCHEDULING
    // ===================

    /**
     * Definir expressão cron raw
     */
    public function cron($expression) {
        $this->expression = $expression;
        return $this;
    }

    /**
     * A cada minuto
     */
    public function everyMinute() {
        return $this->cron('* * * * *');
    }

    /**
     * A cada 5 minutos
     */
    public function everyFiveMinutes() {
        return $this->cron('*/5 * * * *');
    }

    /**
     * A cada 10 minutos
     */
    public function everyTenMinutes() {
        return $this->cron('*/10 * * * *');
    }

    /**
     * A cada 15 minutos
     */
    public function everyFifteenMinutes() {
        return $this->cron('*/15 * * * *');
    }

    /**
     * A cada 30 minutos
     */
    public function everyThirtyMinutes() {
        return $this->cron('*/30 * * * *');
    }

    /**
     * A cada hora
     */
    public function hourly() {
        return $this->cron('0 * * * *');
    }

    /**
     * A cada hora em minuto específico
     */
    public function hourlyAt($minute) {
        return $this->cron("{$minute} * * * *");
    }

    /**
     * Diariamente à meia-noite
     */
    public function daily() {
        return $this->cron('0 0 * * *');
    }

    /**
     * Diariamente em horário específico
     */
    public function dailyAt($time) {
        $parts = explode(':', $time);
        $hour = $parts[0];
        $minute = $parts[1] ?? '0';
        return $this->cron("{$minute} {$hour} * * *");
    }

    /**
     * Duas vezes por dia
     */
    public function twiceDaily($first = 1, $second = 13) {
        return $this->cron("0 {$first},{$second} * * *");
    }

    /**
     * Semanalmente (domingo à meia-noite)
     */
    public function weekly() {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Semanalmente em dia e hora específicos
     */
    public function weeklyOn($day, $time = '0:0') {
        $parts = explode(':', $time);
        $hour = $parts[0];
        $minute = $parts[1] ?? '0';
        return $this->cron("{$minute} {$hour} * * {$day}");
    }

    /**
     * Mensalmente
     */
    public function monthly() {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Mensalmente em dia e hora específicos
     */
    public function monthlyOn($day = 1, $time = '0:0') {
        $parts = explode(':', $time);
        $hour = $parts[0];
        $minute = $parts[1] ?? '0';
        return $this->cron("{$minute} {$hour} {$day} * *");
    }

    /**
     * Trimestralmente
     */
    public function quarterly() {
        return $this->cron('0 0 1 1,4,7,10 *');
    }

    /**
     * Anualmente
     */
    public function yearly() {
        return $this->cron('0 0 1 1 *');
    }

    /**
     * Dias de semana (seg-sex)
     */
    public function weekdays() {
        return $this->cron('0 0 * * 1-5');
    }

    /**
     * Finais de semana
     */
    public function weekends() {
        return $this->cron('0 0 * * 0,6');
    }

    /**
     * Segunda-feira
     */
    public function mondays() {
        return $this->days(1);
    }

    /**
     * Terça-feira
     */
    public function tuesdays() {
        return $this->days(2);
    }

    /**
     * Quarta-feira
     */
    public function wednesdays() {
        return $this->days(3);
    }

    /**
     * Quinta-feira
     */
    public function thursdays() {
        return $this->days(4);
    }

    /**
     * Sexta-feira
     */
    public function fridays() {
        return $this->days(5);
    }

    /**
     * Sábado
     */
    public function saturdays() {
        return $this->days(6);
    }

    /**
     * Domingo
     */
    public function sundays() {
        return $this->days(0);
    }

    /**
     * Em dias específicos
     */
    public function days($days) {
        $days = is_array($days) ? implode(',', $days) : $days;
        $parts = explode(' ', $this->expression);
        $parts[4] = $days;
        $this->expression = implode(' ', $parts);
        return $this;
    }

    /**
     * Entre horários
     */
    public function between($start, $end) {
        return $this->when(function() use ($start, $end) {
            $now = date('H:i');
            return $now >= $start && $now <= $end;
        });
    }

    // ===================
    // CONSTRAINTS
    // ===================

    /**
     * Executar apenas se condição for verdadeira
     */
    public function when(callable $callback) {
        $this->filters[] = $callback;
        return $this;
    }

    /**
     * Pular se condição for verdadeira
     */
    public function skip(callable $callback) {
        $this->rejects[] = $callback;
        return $this;
    }

    /**
     * Apenas em determinados ambientes
     */
    public function environments($environments) {
        $this->environments = is_array($environments) ? $environments : func_get_args();
        return $this;
    }

    /**
     * Evitar sobreposição
     */
    public function withoutOverlapping($minutes = 1440) {
        $this->withoutOverlapping = $minutes;
        return $this;
    }

    /**
     * Executar em apenas um servidor
     */
    public function onOneServer() {
        $this->onOneServer = true;
        return $this;
    }

    /**
     * Executar em background
     */
    public function runInBackground() {
        $this->runInBackground = true;
        return $this;
    }

    // ===================
    // HOOKS
    // ===================

    /**
     * Callback antes da execução
     */
    public function before(callable $callback) {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    /**
     * Callback após a execução
     */
    public function after(callable $callback) {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Executar callback em caso de sucesso
     */
    public function onSuccess(callable $callback) {
        return $this->after(function($output) use ($callback) {
            if ($output !== false) {
                $callback($output);
            }
        });
    }

    /**
     * Executar callback em caso de falha
     */
    public function onFailure(callable $callback) {
        return $this->after(function($output, $exception = null) use ($callback) {
            if ($exception !== null) {
                $callback($exception);
            }
        });
    }

    /**
     * Enviar output para URL
     */
    public function pingBefore($url) {
        return $this->before(function() use ($url) {
            @file_get_contents($url);
        });
    }

    /**
     * Enviar output para URL após
     */
    public function pingAfter($url) {
        return $this->after(function() use ($url) {
            @file_get_contents($url);
        });
    }

    /**
     * Enviar output para URL em caso de sucesso
     */
    public function pingOnSuccess($url) {
        return $this->onSuccess(function() use ($url) {
            @file_get_contents($url);
        });
    }

    /**
     * Enviar output para URL em caso de falha
     */
    public function pingOnFailure($url) {
        return $this->onFailure(function() use ($url) {
            @file_get_contents($url);
        });
    }

    // ===================
    // META
    // ===================

    /**
     * Definir descrição
     */
    public function description($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Alias para description
     */
    public function name($name) {
        return $this->description($name);
    }

    // ===================
    // EXECUTION
    // ===================

    /**
     * Verificar se a tarefa deve rodar agora
     */
    public function isDue(DateTime $now) {
        return $this->expressionPasses($now);
    }

    /**
     * Verificar se expressão cron passa
     */
    private function expressionPasses(DateTime $now) {
        $parts = explode(' ', $this->expression);

        if (count($parts) !== 5) {
            return false;
        }

        list($minute, $hour, $day, $month, $weekday) = $parts;

        return $this->matchesPart($minute, (int) $now->format('i'))
            && $this->matchesPart($hour, (int) $now->format('G'))
            && $this->matchesPart($day, (int) $now->format('j'))
            && $this->matchesPart($month, (int) $now->format('n'))
            && $this->matchesPart($weekday, (int) $now->format('w'));
    }

    /**
     * Verificar se parte da expressão passa
     */
    private function matchesPart($expression, $value) {
        // Wildcard
        if ($expression === '*') {
            return true;
        }

        // Lista (1,2,3)
        if (strpos($expression, ',') !== false) {
            return in_array($value, explode(',', $expression));
        }

        // Range (1-5)
        if (strpos($expression, '-') !== false) {
            list($start, $end) = explode('-', $expression);
            return $value >= (int) $start && $value <= (int) $end;
        }

        // Step (*/5)
        if (strpos($expression, '/') !== false) {
            list($range, $step) = explode('/', $expression);
            if ($range === '*') {
                return $value % (int) $step === 0;
            }
        }

        // Valor exato
        return (int) $expression === $value;
    }

    /**
     * Verificar se filtros passam
     */
    public function filtersPass() {
        // Verificar ambiente
        if (!empty($this->environments)) {
            $env = defined('ENVIRONMENT') ? ENVIRONMENT : 'production';
            if (!in_array($env, $this->environments)) {
                return false;
            }
        }

        // Verificar filtros
        foreach ($this->filters as $filter) {
            if (!$filter()) {
                return false;
            }
        }

        // Verificar rejeições
        foreach ($this->rejects as $reject) {
            if ($reject()) {
                return false;
            }
        }

        // Verificar overlapping
        if ($this->withoutOverlapping) {
            $lockFile = sys_get_temp_dir() . '/schedule-' . md5($this->description) . '.lock';

            if (file_exists($lockFile)) {
                $lockTime = filemtime($lockFile);
                if (time() - $lockTime < $this->withoutOverlapping * 60) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Executar tarefa
     */
    public function run() {
        // Criar lock se necessário
        if ($this->withoutOverlapping) {
            $lockFile = sys_get_temp_dir() . '/schedule-' . md5($this->description) . '.lock';
            touch($lockFile);
        }

        // Before callbacks
        foreach ($this->beforeCallbacks as $callback) {
            $callback();
        }

        $output = null;
        $exception = null;

        try {
            switch ($this->type) {
                case 'command':
                    $output = $this->runCommand();
                    break;
                case 'callback':
                    $output = $this->runCallback();
                    break;
                case 'job':
                    $output = $this->runJob();
                    break;
                case 'exec':
                    $output = $this->runExec();
                    break;
            }
        } catch (Exception $e) {
            $exception = $e;
        }

        // Remover lock
        if ($this->withoutOverlapping) {
            @unlink($lockFile);
        }

        // After callbacks
        foreach ($this->afterCallbacks as $callback) {
            $callback($output, $exception);
        }

        if ($exception) {
            throw $exception;
        }

        return $output;
    }

    private function runCommand() {
        $command = $this->task;
        $output = shell_exec("php aegis {$command} 2>&1");
        return $output;
    }

    private function runCallback() {
        return call_user_func_array($this->task, $this->parameters);
    }

    private function runJob() {
        $job = $this->task;
        $data = $this->parameters['data'] ?? [];
        $queue = $this->parameters['queue'];

        if ($queue) {
            Queue::on($queue)->push($job, $data);
        } else {
            Queue::push($job, $data);
        }

        return "Job {$job} queued";
    }

    private function runExec() {
        if ($this->runInBackground) {
            exec($this->task . ' > /dev/null 2>&1 &');
            return 'Running in background';
        }

        return shell_exec($this->task . ' 2>&1');
    }

    // ===================
    // GETTERS
    // ===================

    public function getDescription() {
        return $this->description;
    }

    public function getExpression() {
        return $this->expression;
    }

    public function getNextRunDate() {
        // Implementação simplificada
        $now = new DateTime();
        $parts = explode(' ', $this->expression);

        // Para expressões simples, calcular próxima execução
        // Isso é uma simplificação - uma implementação completa usaria biblioteca de cron
        return $now->format('Y-m-d H:i') . ' (approx)';
    }
}
