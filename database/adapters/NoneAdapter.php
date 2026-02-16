<?php
/**
 * None Adapter
 * Mock adapter para sites sem banco de dados
 * Retorna arrays vazios
 */

class NoneAdapter implements DatabaseInterface {

    public function connect() {
        return true;
    }

    public function disconnect() {
        // Nada
    }

    public function select($table, $where = [], $options = []) {
        return [];
    }

    public function insert($table, $data) {
        return null;
    }

    public function update($table, $data, $where) {
        return true;
    }

    public function delete($table, $where) {
        return true;
    }

    public function query($sql, $params = []) {
        return [];
    }

    public function getLastId() {
        return null;
    }

    public function tableExists($table) {
        return false;
    }

    public function getColumns($table) {
        return [];
    }
}
