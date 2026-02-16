<?php
/**
 * Supabase Adapter
 * Implementação Supabase usando REST API
 * Baseado no Stark Framework
 */

class SupabaseAdapter implements DatabaseInterface {

    private $url;
    private $key;

    public function __construct($url, $key) {
        $this->url = rtrim($url, '/');
        $this->key = $key;
    }

    public function connect() {
        // Testar conexão
        try {
            $this->request('GET', '/rest/v1/');
            return true;
        } catch (Exception $e) {
            throw new Exception("Falha ao conectar no Supabase: " . $e->getMessage());
        }
    }

    public function disconnect() {
        // Não precisa desconectar (stateless)
    }

    public function select($table, $where = [], $options = []) {
        $endpoint = "/rest/v1/{$table}";
        $params = [];

        // WHERE
        foreach ($where as $field => $value) {
            // 🚀 PERFORMANCE: Suporte para WHERE IN (arrays)
            if (is_array($value)) {
                // WHERE IN: field=in.(value1,value2,value3)
                $params["{$field}"] = "in.(" . implode(',', array_map(function($v) {
                    return '"' . str_replace('"', '\"', $v) . '"';
                }, $value)) . ")";
            } else {
                // WHERE =: field=eq.value
                // Converter boolean para string corretamente
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $params["{$field}"] = "eq.{$value}";
            }
        }

        // ORDER
        if (isset($options['order'])) {
            // Converter formato MySQL "campo ASC/DESC" para Supabase "campo.asc/desc"
            $order = $options['order'];

            // Suporta ORDER BY múltiplo: "campo1 ASC, campo2 DESC"
            if (strpos($order, ',') !== false) {
                $fields = array_map('trim', explode(',', $order));
                $orderParts = [];

                foreach ($fields as $field) {
                    if (preg_match('/^([\w.]+)\s+(ASC|DESC)$/i', $field, $matches)) {
                        $fieldName = $matches[1];

                        // Se tem ponto (tabela.campo), remover a tabela
                        if (strpos($fieldName, '.') !== false) {
                            $parts = explode('.', $fieldName);
                            $fieldName = end($parts);
                        }

                        $direction = strtolower($matches[2]);
                        $orderParts[] = "{$fieldName}.{$direction}";
                    }
                }

                if (!empty($orderParts)) {
                    $params['order'] = implode(',', $orderParts);
                }
            }
            // ORDER BY único: "campo ASC"
            else if (preg_match('/^([\w.]+)\s+(ASC|DESC)$/i', $order, $matches)) {
                $field = $matches[1];

                // Se tem ponto (tabela.campo), remover a tabela
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $field = end($parts);
                }

                $direction = strtolower($matches[2]);
                $params['order'] = "{$field}.{$direction}";
            } else {
                // Se já estiver no formato correto ou sem direção, usar direto
                $params['order'] = $order;
            }
        }

        // LIMIT
        if (isset($options['limit'])) {
            $params['limit'] = $options['limit'];
        }

        // Query string
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->request('GET', $endpoint);
    }

    public function insert($table, $data) {
        $endpoint = "/rest/v1/{$table}";

        error_log("[SUPABASE::insert] Tabela: {$table}");
        error_log("[SUPABASE::insert] Dados enviados: " . json_encode($data));

        $response = $this->request('POST', $endpoint, $data, [
            'Prefer: return=representation'
        ]);

        error_log("[SUPABASE::insert] Resposta recebida: " . json_encode($response));

        // Verificar se houve erro
        if ($response === false) {
            error_log("[SUPABASE::insert] ❌ Response é false!");
            throw new Exception("Erro ao inserir em {$table} no Supabase");
        }

        // Tabelas com composite PK não retornam 'id'
        // Ex: page_permissions (group_id + page_id), member_groups (member_id + group_id)
        $compositePkTables = ['page_permissions', 'member_groups', 'member_page_permissions'];

        if (in_array($table, $compositePkTables)) {
            error_log("[SUPABASE::insert] ✅ Tabela com composite PK detectada: {$table}");
            // Para composite PK, só verificar se retornou algo
            if (empty($response)) {
                error_log("SUPABASE INSERT ERROR: Resposta vazia para {$table}. Response=" . json_encode($response));
                throw new Exception("Supabase não retornou dados após inserção em {$table}");
            }
            error_log("[SUPABASE::insert] ✅ Insert bem-sucedido (composite PK)");
            return true; // Sucesso
        }

        // Verificar se retornou dados com ID (tabelas normais)
        if (empty($response) || !isset($response[0]['id'])) {
            error_log("SUPABASE INSERT ERROR: Resposta vazia ou sem ID. Response=" . json_encode($response));
            throw new Exception("Supabase não retornou ID após inserção em {$table}. Response: " . json_encode($response));
        }

        error_log("[SUPABASE::insert] ✅ Insert bem-sucedido. ID retornado: " . $response[0]['id']);
        return $response[0]['id'];
    }

    public function update($table, $data, $where) {
        $endpoint = "/rest/v1/{$table}";
        $params = [];

        // WHERE
        foreach ($where as $field => $value) {
            $params["{$field}"] = "eq.{$value}";
        }

        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        $this->request('PATCH', $endpoint, $data);
        return true;
    }

    public function delete($table, $where) {
        $endpoint = "/rest/v1/{$table}";
        $params = [];

        // WHERE
        foreach ($where as $field => $value) {
            $params["{$field}"] = "eq.{$value}";
        }

        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        $this->request('DELETE', $endpoint);
        return true;
    }

    public function query($sql, $params = []) {
        error_log("=== SupabaseAdapter::query INICIADO ===");
        error_log("SQL length: " . strlen($sql) . " characters");
        error_log("SQL preview (first 300 chars): " . substr($sql, 0, 300));

        // Detectar se e SELECT ou DDL
        $sql_upper = strtoupper(trim($sql));

        // Se for SELECT, usar exec_query que retorna resultados
        if (strpos($sql_upper, 'SELECT') === 0) {
            error_log("Query type: SELECT - usando exec_query");

            // Substituir placeholders ? pelos valores reais
            if (!empty($params)) {
                foreach ($params as $param) {
                    // Escapar aspas simples e envolver em aspas
                    $escaped = str_replace("'", "''", $param);
                    $sql = preg_replace('/\?/', "'{$escaped}'", $sql, 1);
                }
                error_log("SQL após substituição de parâmetros: " . substr($sql, 0, 300));
            }

            try {
                $endpoint = "/rest/v1/rpc/exec_query";
                $result = $this->request('POST', $endpoint, ['query_text' => $sql]);

                // exec_query retorna JSONB, precisamos decodificar
                if (is_string($result)) {
                    $decoded = json_decode($result, true);
                    return $decoded ?? [];
                }

                return $result ?? [];
            } catch (Exception $e) {
                error_log("ERRO ao executar SELECT: " . $e->getMessage());
                throw new Exception("Erro ao executar SELECT no Supabase. Certifique-se que a RPC 'exec_query' existe. Erro: " . $e->getMessage());
            }
        }

        // Se for DDL (CREATE, ALTER, DROP, etc), usar exec_sql
        error_log("Query type: DDL - usando exec_sql");
        try {
            $endpoint = "/rest/v1/rpc/exec_sql";
            error_log("Chamando endpoint: {$endpoint}");
            error_log("Payload: " . json_encode(['query' => substr($sql, 0, 200)]));
            $result = $this->request('POST', $endpoint, ['query' => $sql]);
            error_log("Resultado recebido: " . json_encode($result));
            return $result ?? [];
        } catch (Exception $e) {
            error_log("ERRO ao executar DDL: " . $e->getMessage());
            throw new Exception("Erro ao executar SQL no Supabase. Certifique-se que a RPC 'exec_sql' existe. Erro: " . $e->getMessage());
        }
    }

    public function execute($sql, $params = []) {
        // Para CREATE TABLE, ALTER TABLE, etc - executar via query
        return $this->query($sql, $params);
    }

    public function getLastId() {
        // Supabase retorna o ID no insert
        return null;
    }

    public function tableExists($table) {
        try {
            // Usar limit=1 ao invés de limit=0 (melhor com cache do Supabase)
            $response = $this->request('GET', "/rest/v1/{$table}?limit=1");
            return true;
        } catch (Exception $e) {
            // Se HTTP 404 com mensagem de schema cache, retornar false
            // Qualquer outro erro, também retornar false (tabela não existe)
            return false;
        }
    }

    public function getColumns($table) {
        // Usar RPC function get_table_columns
        $endpoint = "/rest/v1/rpc/get_table_columns";
        return $this->request('POST', $endpoint, ['p_table_name' => $table]);
    }

    /**
     * Fazer requisição HTTP
     */
    private function request($method, $endpoint, $data = null, $extraHeaders = []) {
        $url = $this->url . $endpoint;

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ];

        $headers = array_merge($headers, $extraHeaders);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        // Erro: tentar decodificar mensagem de erro do Supabase
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['message'] ?? $errorData['hint'] ?? $response;

        // Logar erro apenas em development
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_log("SUPABASE ERROR: HTTP {$httpCode} | URL: {$url} | Response: " . $response);
        }

        // Se for erro de RLS (Row Level Security), dar mensagem específica
        if ($httpCode === 403 || strpos($response, 'policy') !== false) {
            throw new Exception("SUPABASE RLS BLOCKING: Verifique se RLS policies estão corretas. HTTP {$httpCode}: {$errorMsg}");
        }

        throw new Exception("Supabase API Error (HTTP {$httpCode}): {$errorMsg}");
    }
}
