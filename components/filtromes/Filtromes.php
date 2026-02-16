<?php
/**
 * AEGIS Framework - Filtro de Mês e Ano
 *
 * Componente de filtro com dropdowns separados para mês e ano
 *
 * @package AEGIS
 * @version 1.0.0
 */

class Filtromes {
    /**
     * Renderizar componente de Filtro Mês/Ano
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'filter_group' => 'default',
            'show_month' => 'yes',
            'show_year' => 'yes',
            'default_month' => '',
            'default_year' => '',
            'start_year' => '2020',
            'end_year' => ''
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Sanitizar
        $filterGroup = htmlspecialchars($data['filter_group'], ENT_QUOTES, 'UTF-8');
        $showMonth = $data['show_month'] === 'yes';
        $showYear = $data['show_year'] === 'yes';

        // Anos
        $startYear = (int)($data['start_year'] ?: 2020);
        $endYear = (int)($data['end_year'] ?: (date('Y') + 2));

        // Valores padrão - Mês atual -1
        // Se estamos em dezembro/2025, mostra novembro/2025
        // Se estamos em janeiro/2026, mostra dezembro/2025
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        if ($currentMonth === 1) {
            // Janeiro: volta para dezembro do ano anterior
            $defaultMonth = '12';
            $defaultYear = $currentYear - 1;
        } else {
            // Outros meses: mês anterior do mesmo ano
            $defaultMonth = str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
            $defaultYear = $currentYear;
        }

        // Meses
        $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro'
        ];

        // HTML
        $html = '<div class="filter-mesano" data-filter-group="' . $filterGroup . '">';
        $html .= '<div class="filter-mesano__container">';

        // Dropdown de Mês
        if ($showMonth) {
            $html .= '<div class="filter-mesano__field">';
            $html .= '<label for="filter-month-' . $filterGroup . '">Mês</label>';
            $html .= '<select id="filter-month-' . $filterGroup . '" class="filter-mesano__select" data-filter-type="month">';

            foreach ($meses as $num => $nome) {
                $selected = ($num === $defaultMonth) ? ' selected' : '';
                $html .= '<option value="' . $num . '"' . $selected . '>' . $nome . '</option>';
            }

            $html .= '</select>';
            $html .= '</div>';
        }

        // Dropdown de Ano
        if ($showYear) {
            $html .= '<div class="filter-mesano__field">';
            $html .= '<label for="filter-year-' . $filterGroup . '">Ano</label>';
            $html .= '<select id="filter-year-' . $filterGroup . '" class="filter-mesano__select" data-filter-type="year">';

            for ($year = $endYear; $year >= $startYear; $year--) {
                $selected = ($year == $defaultYear) ? ' selected' : '';
                $html .= '<option value="' . $year . '"' . $selected . '>' . $year . '</option>';
            }

            $html .= '</select>';
            $html .= '</div>';
        }

        $html .= '</div>'; // .filter-mesano__container
        $html .= '</div>'; // .filter-mesano

        // Incluir JavaScript (apenas uma vez)
        static $scriptAdded = false;
        if (!$scriptAdded) {
            $html .= '<script src="' . url('/assets/js/filter-mesano.js') . '"></script>';
            $scriptAdded = true;
        }

        return $html;
    }
}
