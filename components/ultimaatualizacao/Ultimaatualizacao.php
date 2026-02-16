<?php
/**
 * Componente: Última Atualização
 * Exibe a data/hora da última atualização de uma tabela
 */

class Ultimaatualizacao {
    public static function render(array $config): string {
        ob_start();
        require __DIR__ . '/render.php';
        return ob_get_clean();
    }
}
