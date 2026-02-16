<?php
/**
 * AEGIS Framework - Image Link Component
 *
 * Card simples com imagem e link
 *
 * @package AEGIS
 * @version 1.0.0
 */

class Imagelink {
    /**
     * Renderizar componente ImageLink
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'image_url' => '',
            'link_url' => '#',
            'alt_text' => 'Imagem',
            'target' => '_self',
            'object_fit' => 'cover'
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Validação
        if (empty($data['image_url'])) {
            return '<div class="imagelink-error">⚠️ Nenhuma imagem configurada</div>';
        }

        // Sanitizar
        $imageUrl = htmlspecialchars($data['image_url'], ENT_QUOTES, 'UTF-8');
        $linkUrl = htmlspecialchars($data['link_url'], ENT_QUOTES, 'UTF-8');
        $altText = htmlspecialchars($data['alt_text'], ENT_QUOTES, 'UTF-8');
        $target = htmlspecialchars($data['target'], ENT_QUOTES, 'UTF-8');
        $objectFit = htmlspecialchars($data['object_fit'], ENT_QUOTES, 'UTF-8');

        // HTML
        $objectFitClass = 'object-fit-' . $objectFit;
        $html = '<div class="imagelink-wrapper">';
        $html .= '<a href="' . $linkUrl . '" target="' . $target . '" class="imagelink-link">';
        $html .= '<img src="' . $imageUrl . '" alt="' . $altText . '" class="imagelink-image ' . $objectFitClass . '">';
        $html .= '</a>';
        $html .= '</div>';

        return $html;
    }
}
