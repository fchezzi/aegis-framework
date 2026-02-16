<?php
/**
 * AEGIS Framework - RD Station Helper
 * API de Conversões - Envio de Leads
 * Version: 1.0.0
 */

class RDStation {

    /**
     * Endpoint da API de Conversões
     */
    const API_ENDPOINT = 'https://api.rd.services/platform/conversions';

    /**
     * Envia lead para RD Station via API de Conversões
     *
     * @param string $email Email do lead
     * @param string $nome Nome completo do lead
     * @param string $whatsapp Telefone/WhatsApp do lead
     * @param string $tituloArtigo Título do artigo solicitado
     * @param string $slugArtigo Slug do artigo (para tag)
     * @param array $dadosAdicionais Dados extras opcionais
     * @return bool True se enviado com sucesso
     */
    public static function enviarLead($email, $nome, $whatsapp, $tituloArtigo, $slugArtigo, $dadosAdicionais = []) {

        // Verificar se integração está habilitada
        if (!defined('RDSTATION_ENABLED') || !RDSTATION_ENABLED) {
            error_log("RD Station: Integração desabilitada (RDSTATION_ENABLED = false)");
            return false;
        }

        // Validar API Key
        if (!defined('RDSTATION_API_KEY') || empty(RDSTATION_API_KEY)) {
            error_log("ERRO RD Station: API Key não configurada em _config.php");
            return false;
        }

        // Validações básicas
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("ERRO RD Station: Email inválido - {$email}");
            return false;
        }

        if (empty($nome) || empty($whatsapp)) {
            error_log("ERRO RD Station: Nome ou WhatsApp vazio");
            return false;
        }

        try {
            // Formatar telefone para padrão internacional (+55...)
            $telefoneFormatado = self::formatarTelefone($whatsapp);

            // Montar payload
            $payload = [
                'event_type' => 'CONVERSION',
                'event_family' => 'CDP',
                'payload' => [
                    'conversion_identifier' => 'artigo-solicitado',
                    'email' => $email,
                    'name' => $nome,
                    'mobile_phone' => $telefoneFormatado,
                    'tags' => [
                        'artigo-' . $slugArtigo,
                        'contato_instituto'
                    ],
                    'cf_titulo_artigo' => $tituloArtigo,
                    'cf_slug_artigo' => $slugArtigo,
                    'traffic_source' => 'website-artigos'
                ]
            ];

            // Adicionar dados extras (se fornecidos)
            if (!empty($dadosAdicionais)) {
                $payload['payload'] = array_merge($payload['payload'], $dadosAdicionais);
            }

            // Montar URL com API Key
            $url = self::API_ENDPOINT . '?api_key=' . RDSTATION_API_KEY;

            // Enviar requisição
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Verificar resultado
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("RD Station: Lead enviado com sucesso - Email: {$email}, Artigo: {$slugArtigo}");
                return true;
            } else {
                error_log("ERRO RD Station: HTTP {$httpCode} - Response: {$response} - cURL Error: {$curlError}");
                return false;
            }

        } catch (Exception $e) {
            error_log("ERRO RD Station: Exception - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formata telefone para padrão internacional (+5511999999999)
     *
     * @param string $telefone Telefone no formato brasileiro
     * @return string Telefone formatado
     */
    private static function formatarTelefone($telefone) {
        // Remove tudo exceto números
        $numeros = preg_replace('/[^0-9]/', '', $telefone);

        // Se não começar com 55, adiciona (código do Brasil)
        if (substr($numeros, 0, 2) !== '55') {
            $numeros = '55' . $numeros;
        }

        // Adiciona + no início
        return '+' . $numeros;
    }

    /**
     * Testa conexão com API do RD Station
     * Útil para validar se API Key está funcionando
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testarConexao() {
        if (!defined('RDSTATION_API_KEY') || empty(RDSTATION_API_KEY)) {
            return [
                'success' => false,
                'message' => 'API Key não configurada'
            ];
        }

        try {
            $url = self::API_ENDPOINT . '?api_key=' . RDSTATION_API_KEY;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 400) {
                // 400 é esperado (sem payload), mas significa que API Key está OK
                return [
                    'success' => true,
                    'message' => 'Conexão OK - API Key válida'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API Key inválida ou erro de conexão (HTTP ' . $httpCode . ')'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }
}
