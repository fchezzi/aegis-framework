<?php
/**
 * UptimeTestController - Teste da API UptimeRobot
 *
 * @version 1.0.0
 */

class UptimeTestController extends BaseController {

    public function index() {
        Auth::require();

        $apiKey = defined('UPTIME_ROBOT_API_KEY') ? UPTIME_ROBOT_API_KEY : '';

        $data = [
            'has_key' => !empty($apiKey),
            'api_key' => $apiKey,
            'response' => null,
            'error' => null
        ];

        if (!empty($apiKey) && isset($_GET['test'])) {
            try {
                $url = 'https://api.uptimerobot.com/v2/getMonitors';

                $postData = [
                    'api_key' => $apiKey,
                    'format' => 'json',
                    'logs' => 1,
                    'response_times' => 1,
                    'response_times_limit' => 10
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
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

                if (!$json) {
                    throw new Exception('Resposta inválida (não é JSON)');
                }

                $data['response'] = $json;

            } catch (Exception $e) {
                $data['error'] = $e->getMessage();
            }
        }

        return $this->render('uptime-test', $data);
    }
}
