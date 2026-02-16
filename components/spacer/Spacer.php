<?php
/**
 * AEGIS Framework - Spacer Component
 *
 * Componente para adicionar espaçamento entre blocos com linha divisória opcional
 *
 * @package AEGIS
 * @version 1.0.0
 */

class Spacer {
    /**
     * Renderizar componente Spacer
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'height' => 'medio',
            'custom_height' => 60,
            'show_divider' => 'no',
            'divider_style' => 'solid',
            'divider_width' => 'full',
            'divider_thickness' => 'thin',
            'divider_color' => 'default'
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Calcular altura
        $heightMap = [
            'pequeno' => 20,
            'medio' => 40,
            'grande' => 80,
            'extra-grande' => 120,
            'custom' => (int)$data['custom_height']
        ];

        $height = $heightMap[$data['height']] ?? 40;

        // Classes CSS
        $classes = ['aegis-spacer'];

        if ($data['show_divider'] === 'yes') {
            $classes[] = 'aegis-spacer--with-divider';
            $classes[] = 'aegis-spacer--divider-' . $data['divider_style'];
            $classes[] = 'aegis-spacer--divider-width-' . $data['divider_width'];
            $classes[] = 'aegis-spacer--divider-' . $data['divider_thickness'];
            $classes[] = 'aegis-spacer--divider-color-' . $data['divider_color'];
        }

        $classString = implode(' ', $classes);

        // HTML
        $html = '<div class="' . $classString . '" style="height: ' . $height . 'px;">';

        if ($data['show_divider'] === 'yes') {
            $html .= '<div class="aegis-spacer__divider"></div>';
        }

        $html .= '</div>';

        return $html;
    }
}
