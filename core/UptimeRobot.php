<?php
/**
 * UptimeRobot - Integração com API UptimeRobot
 *
 * @version 1.0.0
 */

class UptimeRobot {

    private $apiKey;

    public function __construct() {
        $this->apiKey = defined('UPTIME_ROBOT_API_KEY') ? UPTIME_ROBOT_API_KEY : '';

        if (empty($this->apiKey)) {
            throw new Exception('UPTIME_ROBOT_API_KEY não configurada');
        }
    }

    /**
     * Sincronizar todos os monitores e seus dados
     */
    public function sync() {
        $monitors = $this->getMonitors();

        if (!$monitors || !isset($monitors['monitors'])) {
            throw new Exception('Nenhum monitor retornado pela API');
        }

        $synced = 0;
        $responseTimes = 0;
        $logs = 0;

        foreach ($monitors['monitors'] as $monitor) {
            // 1. Salvar/atualizar monitor
            $this->saveMonitor($monitor);
            $synced++;

            // 2. Salvar tempos de resposta
            if (!empty($monitor['response_times'])) {
                foreach ($monitor['response_times'] as $rt) {
                    $this->saveResponseTime($monitor['id'], $rt);
                    $responseTimes++;
                }
            }

            // 3. Salvar logs (downtime)
            if (!empty($monitor['logs'])) {
                foreach ($monitor['logs'] as $log) {
                    $this->saveLog($monitor['id'], $log);
                    $logs++;
                }
            }
        }

        return [
            'monitors' => $synced,
            'response_times' => $responseTimes,
            'logs' => $logs
        ];
    }

    /**
     * Buscar monitores na API
     */
    private function getMonitors() {
        $url = 'https://api.uptimerobot.com/v2/getMonitors';

        $data = [
            'api_key' => $this->apiKey,
            'format' => 'json',
            'logs' => 1,
            'log_limit' => 100, // Últimos 100 eventos
            'response_times' => 1,
            'response_times_limit' => 50 // Últimos 50 checks
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Erro cURL: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }

        $json = json_decode($response, true);

        if (!$json || $json['stat'] !== 'ok') {
            throw new Exception('API retornou erro');
        }

        return $json;
    }

    /**
     * Salvar/atualizar monitor no banco
     */
    private function saveMonitor($monitor) {
        $existing = DB::query("SELECT id FROM uptime_monitors WHERE monitor_id = ?", [$monitor['id']]);

        $data = [
            'monitor_id' => $monitor['id'],
            'friendly_name' => $monitor['friendly_name'],
            'url' => $monitor['url'],
            'type' => $monitor['type'],
            'interval_seconds' => $monitor['interval'],
            'status' => $monitor['status'],
            'average_response_time' => $monitor['average_response_time'] ?? 0,
            'create_datetime' => $monitor['create_datetime']
        ];

        if (empty($existing)) {
            // Insert
            $data['id'] = Security::generateUUID();
            DB::insert('uptime_monitors', $data);
        } else {
            // Update
            unset($data['monitor_id']); // Não atualizar chave única
            DB::update('uptime_monitors', $data, ['monitor_id' => $monitor['id']]);
        }
    }

    /**
     * Salvar tempo de resposta
     */
    private function saveResponseTime($monitorId, $responseTime) {
        // Verificar se já existe
        $existing = DB::query(
            "SELECT id FROM uptime_response_times WHERE monitor_id = ? AND datetime = ?",
            [$monitorId, $responseTime['datetime']]
        );

        if (empty($existing)) {
            DB::insert('uptime_response_times', [
                'id' => Security::generateUUID(),
                'monitor_id' => $monitorId,
                'datetime' => $responseTime['datetime'],
                'value' => $responseTime['value']
            ]);
        }
    }

    /**
     * Salvar log (downtime/uptime)
     */
    private function saveLog($monitorId, $log) {
        // Verificar se já existe
        $existing = DB::query(
            "SELECT id FROM uptime_logs WHERE monitor_id = ? AND datetime = ?",
            [$monitorId, $log['datetime']]
        );

        if (empty($existing)) {
            DB::insert('uptime_logs', [
                'id' => Security::generateUUID(),
                'monitor_id' => $monitorId,
                'type' => $log['type'],
                'datetime' => $log['datetime'],
                'duration' => $log['duration'] ?? 0,
                'reason_code' => $log['reason']['code'] ?? 0,
                'reason_detail' => $log['reason']['detail'] ?? ''
            ]);
        }
    }

    /**
     * Obter estatísticas de uptime (últimos 30 dias)
     */
    public static function getStats($monitorId = null) {
        $where = $monitorId ? "WHERE monitor_id = {$monitorId}" : "";

        $monitors = DB::query("
            SELECT
                monitor_id,
                friendly_name,
                url,
                status,
                average_response_time,
                FROM_UNIXTIME(create_datetime) as created_at
            FROM uptime_monitors
            {$where}
            ORDER BY friendly_name
        ");

        $stats = [];

        foreach ($monitors as $monitor) {
            // Calcular uptime % (baseado em logs de downtime nos últimos 30 dias)
            $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

            $downtime = DB::query("
                SELECT SUM(duration) as total_downtime
                FROM uptime_logs
                WHERE monitor_id = ?
                AND type = 1
                AND datetime >= ?
            ", [$monitor['monitor_id'], $thirtyDaysAgo]);

            $totalDowntime = $downtime[0]['total_downtime'] ?? 0;
            $totalSeconds = 30 * 24 * 60 * 60; // 30 dias em segundos
            $uptimePercent = (($totalSeconds - $totalDowntime) / $totalSeconds) * 100;

            // Buscar últimos response times (para sparkline e min/max)
            $responseTimes = DB::query("
                SELECT datetime, value
                FROM uptime_response_times
                WHERE monitor_id = ?
                ORDER BY datetime DESC
                LIMIT 20
            ", [$monitor['monitor_id']]);

            $responseValues = array_column($responseTimes, 'value');
            $minResponse = !empty($responseValues) ? min($responseValues) : 0;
            $maxResponse = !empty($responseValues) ? max($responseValues) : 0;
            $lastCheck = !empty($responseTimes) ? $responseTimes[0] : null;

            // Buscar último incidente (downtime)
            $lastIncident = DB::query("
                SELECT datetime, duration, reason_detail
                FROM uptime_logs
                WHERE monitor_id = ?
                AND type = 1
                ORDER BY datetime DESC
                LIMIT 1
            ", [$monitor['monitor_id']]);

            $stats[] = [
                'monitor_id' => $monitor['monitor_id'],
                'name' => $monitor['friendly_name'],
                'url' => $monitor['url'],
                'status' => $monitor['status'],
                'status_text' => self::getStatusText($monitor['status']),
                'avg_response_time' => round($monitor['average_response_time']),
                'uptime_percent' => round($uptimePercent, 2),
                'created_at' => $monitor['created_at'],
                'min_response' => $minResponse,
                'max_response' => $maxResponse,
                'last_check' => $lastCheck,
                'last_incident' => !empty($lastIncident) ? $lastIncident[0] : null,
                'sparkline_data' => array_reverse($responseValues) // Ordem cronológica
            ];
        }

        return $stats;
    }

    /**
     * Obter resumo geral (cards do topo)
     */
    public static function getSummary() {
        $total = DB::query("SELECT COUNT(*) as total FROM uptime_monitors");
        $online = DB::query("SELECT COUNT(*) as total FROM uptime_monitors WHERE status = 2");

        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
        $incidents = DB::query("
            SELECT COUNT(DISTINCT monitor_id) as total
            FROM uptime_logs
            WHERE type = 1
            AND datetime >= ?
        ", [$thirtyDaysAgo]);

        // Calcular uptime médio de todos os monitores
        $monitors = DB::query("SELECT monitor_id FROM uptime_monitors");
        $avgUptime = 0;

        if (!empty($monitors)) {
            $uptimes = [];
            foreach ($monitors as $m) {
                $downtime = DB::query("
                    SELECT SUM(duration) as total_downtime
                    FROM uptime_logs
                    WHERE monitor_id = ?
                    AND type = 1
                    AND datetime >= ?
                ", [$m['monitor_id'], $thirtyDaysAgo]);

                $totalDowntime = $downtime[0]['total_downtime'] ?? 0;
                $totalSeconds = 30 * 24 * 60 * 60;
                $uptimes[] = (($totalSeconds - $totalDowntime) / $totalSeconds) * 100;
            }
            $avgUptime = count($uptimes) > 0 ? array_sum($uptimes) / count($uptimes) : 0;
        }

        return [
            'total_monitors' => $total[0]['total'] ?? 0,
            'online_now' => $online[0]['total'] ?? 0,
            'incidents_month' => $incidents[0]['total'] ?? 0,
            'avg_uptime' => round($avgUptime, 2)
        ];
    }

    /**
     * Traduzir status code
     */
    public static function getStatusText($status) {
        $statuses = [
            0 => 'Pausado',
            1 => 'Não verificado',
            2 => 'Online',
            8 => 'Parece offline',
            9 => 'Offline'
        ];

        return $statuses[$status] ?? 'Desconhecido';
    }
}
