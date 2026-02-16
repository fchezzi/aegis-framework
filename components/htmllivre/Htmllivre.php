<?php
/**
 * HTML Livre Component Renderer
 *
 * Permite inserir código HTML diretamente sem configurações.
 *
 * @package AEGIS Framework
 * @version 1.0.0
 */

class Htmllivre {

    /**
     * Renderiza o componente
     *
     * @param array $data Dados do formulário
     * @return string HTML do componente
     */
    public static function render($data) {
        // Pega o código HTML (sem sanitização - confia no admin)
        $html = $data['html'] ?? '';

        // Classe CSS adicional (opcional)
        $wrapperClass = htmlspecialchars($data['wrapper_class'] ?? '', ENT_QUOTES, 'UTF-8');

        // Se não tem código, retorna vazio
        if (empty($html)) {
            return '<div class="html-livre-empty">Nenhum código HTML inserido</div>';
        }

        // Se tem classe wrapper, envolve em div
        if (!empty($wrapperClass)) {
            return '<div class="html-livre-wrapper ' . $wrapperClass . '">' . $html . '</div>';
        }

        // Retorna HTML puro (sem wrapper)
        return $html;
    }
}
