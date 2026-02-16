<?php
/**
 * UptimeRobotController - Dashboard e sincronização
 *
 * @version 1.0.0
 */

class UptimeRobotController extends BaseController {

    public function index() {
        Auth::require();

        $stats = UptimeRobot::getStats();
        $summary = UptimeRobot::getSummary();

        $data = [
            'stats' => $stats,
            'summary' => $summary,
            'has_data' => !empty($stats)
        ];

        return $this->render('uptime-robot', $data);
    }

    public function sync() {
        Auth::require();

        try {
            $uptime = new UptimeRobot();
            $result = $uptime->sync();

            $_SESSION['success'] = sprintf(
                'Sincronizado: %d monitores, %d tempos de resposta, %d logs',
                $result['monitors'],
                $result['response_times'],
                $result['logs']
            );
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao sincronizar: ' . $e->getMessage();
        }

        header('Location: ' . url('/admin/uptime-robot'));
        exit;
    }
}
