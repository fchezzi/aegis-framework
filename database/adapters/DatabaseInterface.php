<?php
/**
 * Database Interface
 * Contrato que todos os adapters devem seguir
 */

interface DatabaseInterface {

    /**
     * Conectar ao banco de dados
     */
    public function connect();

    /**
     * Desconectar do banco de dados
     */
    public function disconnect();

    /**
     * SELECT - Buscar registros
     *
     * @param string $table Nome da tabela
     * @param array $where Condições WHERE ['campo' => 'valor']
     * @param array $options Opções (limit, order, etc)
     * @return array Resultados
     */
    public function select($table, $where = [], $options = []);

    /**
     * INSERT - Inserir registro
     *
     * @param string $table Nome da tabela
     * @param array $data Dados ['campo' => 'valor']
     * @return mixed ID do registro criado
     */
    public function insert($table, $data);

    /**
     * UPDATE - Atualizar registros
     *
     * @param string $table Nome da tabela
     * @param array $data Dados para atualizar
     * @param array $where Condições WHERE
     * @return bool Sucesso
     */
    public function update($table, $data, $where);

    /**
     * DELETE - Deletar registros
     *
     * @param string $table Nome da tabela
     * @param array $where Condições WHERE
     * @return bool Sucesso
     */
    public function delete($table, $where);

    /**
     * QUERY - Executar query customizada
     *
     * @param string $sql Query SQL
     * @param array $params Parâmetros (prepared statement)
     * @return mixed Resultado
     */
    public function query($sql, $params = []);

    /**
     * Pegar último ID inserido
     */
    public function getLastId();

    /**
     * Verificar se tabela existe
     */
    public function tableExists($table);

    /**
     * Pegar colunas de uma tabela
     */
    public function getColumns($table);
}
