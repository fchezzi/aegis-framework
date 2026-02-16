<?php
/**
 * AEGIS Framework - Hero Component
 *
 * Banner principal com título, subtítulo, imagem de fundo e CTA
 *
 * @package AEGIS
 * @version 1.0.0
 * @since 9.1.0
 */

class Hero {
    /**
     * Renderizar componente Hero
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'title' => 'Bem-vindo ao AEGIS',
            'subtitle' => 'Framework PHP modular e seguro',
            'background_image' => '',
            'background_color' => '#667eea',
            'text_color' => 'light',
            'height' => 'medium',
            'cta_text' => 'Saiba Mais',
            'cta_link' => '',
            'cta_style' => 'primary',
            'alignment' => 'center'
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Sanitizar dados
        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $subtitle = htmlspecialchars($data['subtitle'], ENT_QUOTES, 'UTF-8');
        $ctaText = htmlspecialchars($data['cta_text'], ENT_QUOTES, 'UTF-8');
        $ctaLink = htmlspecialchars($data['cta_link'], ENT_QUOTES, 'UTF-8');

        // Classes CSS
        $heightClass = 'hero-' . $data['height'];
        $textColorClass = 'text-' . $data['text_color'];
        $alignmentClass = 'align-' . $data['alignment'];
        $ctaStyleClass = 'btn-' . $data['cta_style'];

        // Renderizar HTML
        $html = <<<HTML
<div class="aegis-hero {$heightClass} {$textColorClass} {$alignmentClass}">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">{$title}</h1>
        <p class="hero-subtitle">{$subtitle}</p>
HTML;

        // Adicionar CTA se configurado
        if (!empty($ctaLink)) {
            $html .= <<<HTML
        <a href="{$ctaLink}" class="hero-cta {$ctaStyleClass}">{$ctaText}</a>
HTML;
        }

        $html .= <<<HTML
    </div>
</div>
HTML;

        return $html;
    }
}
