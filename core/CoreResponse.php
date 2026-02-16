<?php
/**
 * CoreResponse
 * Gestão de respostas HTTP (redirect, JSON, error, success)
 */

class CoreResponse {

    /**
     * Redirect para URL
     */
    public static function redirect($url) {
        // Se a URL for relativa, adicionar base path
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $basePath = dirname($scriptName);

            if ($basePath !== '/' && $basePath !== '\\') {
                $url = $basePath . $url;
            }
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Gerar URL completa com APP_URL
     */
    public static function url($path = '') {
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        return $baseUrl . $path;
    }

    /**
     * JSON response
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Error response
     */
    public static function error($statusCode, $message) {
        http_response_code($statusCode);

        if (CoreEnvironment::isProduction()) {
            // Página de erro genérica
            echo "<h1>Error {$statusCode}</h1>";
            echo "<p>An error occurred</p>";
        } else {
            // Mostrar erro detalhado
            echo "<h1>Error {$statusCode}</h1>";
            echo "<p>{$message}</p>";
            echo "<pre>Environment: " . CoreEnvironment::name() . "</pre>";
        }

        exit;
    }

    /**
     * Success message (salva em sessão)
     */
    public static function success($message, $redirectUrl = null) {
        $_SESSION['success_message'] = $message;

        if ($redirectUrl) {
            self::redirect($redirectUrl);
        }
    }

    /**
     * Renderizar Breadcrumb
     *
     * @param array $items Array de itens [[label, url], [label]]
     * @return string HTML do breadcrumb
     */
    public static function breadcrumb($items) {
        if (empty($items)) {
            return '';
        }

        $html = '<div class="l-breadcrumb">';
        $html .= '<div class="m-breadcrumb">';

        // Toggle button
        $html .= '<button class="m-breadcrumb__toggle" id="breadcrumbToggle">';
        $html .= '<i data-lucide="menu"></i>';
        $html .= '</button>';

        // Navigation
        $html .= '<nav class="m-breadcrumb__nav">';
        $html .= '<ol class="m-breadcrumb__list">';

        foreach ($items as $index => $item) {
            $label = $item[0] ?? '';
            $url = $item[1] ?? null;
            $isLast = ($index === count($items) - 1);

            $html .= '<li class="m-breadcrumb__item' . ($isLast ? ' m-breadcrumb__item--active' : '') . '">';

            if (!$isLast && !empty($url)) {
                $html .= '<a href="' . self::url($url) . '">' . htmlspecialchars($label) . '</a>';
            } else {
                $html .= htmlspecialchars($label);
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</nav>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
