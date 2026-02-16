<?php
/**
 * MockCache
 * Cache em memória para testes
 *
 * @example
 * $mockCache = new MockCache();
 * Cache::setDriver($mockCache);
 *
 * // Usar normalmente
 * Cache::set('key', 'value');
 */

class MockCache {

    /**
     * Dados em memória
     */
    protected $data = [];

    /**
     * Expirations
     */
    protected $expirations = [];

    /**
     * Operações logadas
     */
    protected $operations = [];

    /**
     * Get
     */
    public function get($key, $default = null) {
        $this->log('get', $key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    /**
     * Set
     */
    public function set($key, $value, $ttl = 3600) {
        $this->log('set', $key, $value);

        $this->data[$key] = $value;
        $this->expirations[$key] = time() + $ttl;

        return true;
    }

    /**
     * Has
     */
    public function has($key) {
        if (!isset($this->data[$key])) {
            return false;
        }

        // Check expiration
        if (isset($this->expirations[$key]) && $this->expirations[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete
     */
    public function delete($key) {
        $this->log('delete', $key);

        unset($this->data[$key], $this->expirations[$key]);
        return true;
    }

    /**
     * Clear all
     */
    public function clear() {
        $this->log('clear', null);

        $this->data = [];
        $this->expirations = [];
        return true;
    }

    /**
     * Remember (get or set)
     */
    public function remember($key, $ttl, callable $callback) {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * Increment
     */
    public function increment($key, $value = 1) {
        $current = $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    /**
     * Decrement
     */
    public function decrement($key, $value = 1) {
        return $this->increment($key, -$value);
    }

    // ===================
    // TEST HELPERS
    // ===================

    /**
     * Log operation
     */
    protected function log($operation, $key, $value = null) {
        $this->operations[] = [
            'operation' => $operation,
            'key' => $key,
            'value' => $value,
            'time' => microtime(true)
        ];
    }

    /**
     * Get operations log
     */
    public function getOperations() {
        return $this->operations;
    }

    /**
     * Check if operation was performed
     */
    public function wasPerformed($operation, $key = null) {
        foreach ($this->operations as $op) {
            if ($op['operation'] === $operation) {
                if ($key === null || $op['key'] === $key) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Count operations
     */
    public function countOperations($type = null) {
        if ($type === null) {
            return count($this->operations);
        }
        return count(array_filter($this->operations, fn($op) => $op['operation'] === $type));
    }

    /**
     * Reset mock
     */
    public function reset() {
        $this->data = [];
        $this->expirations = [];
        $this->operations = [];
    }

    /**
     * Get all data (for debugging)
     */
    public function getAllData() {
        return $this->data;
    }
}
