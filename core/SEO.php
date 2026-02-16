<?php
/**
 * SEO - Gerenciador de Meta Tags e Structured Data
 *
 * Responsabilidades:
 * - Renderizar meta tags (title, description, robots, canonical)
 * - Renderizar Open Graph (Facebook/WhatsApp/LinkedIn)
 * - Renderizar Twitter Cards
 * - Renderizar JSON-LD structured data (Schema.org)
 * - Fallbacks inteligentes quando campos vazios
 *
 * @version 1.0.0
 */

class SEO {

    /**
     * Renderizar todas as meta tags SEO
     *
     * @param array $page Array com dados da página do banco
     * @return string HTML das meta tags
     */
    public static function render($page) {
        if (empty($page)) {
            return '';
        }

        $output = "\n<!-- SEO Meta Tags -->\n";

        // 1. Title Tag
        $output .= self::renderTitle($page);

        // 2. Meta Description
        $output .= self::renderDescription($page);

        // 3. Meta Robots
        $output .= self::renderRobots($page);

        // 4. Canonical URL
        $output .= self::renderCanonical($page);

        // 5. Open Graph (Facebook, WhatsApp, LinkedIn)
        $output .= self::renderOpenGraph($page);

        // 6. Twitter Cards
        $output .= self::renderTwitterCard($page);

        $output .= "<!-- /SEO Meta Tags -->\n\n";

        return $output;
    }

    /**
     * Renderizar JSON-LD Structured Data (Schema.org)
     *
     * @param array $page Array com dados da página do banco
     * @return string HTML com script JSON-LD
     */
    public static function renderJsonLD($page) {
        if (empty($page)) {
            return '';
        }

        $title = !empty($page['seo_title']) ? $page['seo_title'] : $page['title'];
        $description = !empty($page['seo_description']) ? $page['seo_description'] : ($page['description'] ?? '');
        $url = !empty($page['seo_canonical_url']) ? $page['seo_canonical_url'] : url('/' . $page['slug']);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $url
        ];

        // Adicionar imagem se existir
        if (!empty($page['seo_og_image'])) {
            $schema['image'] = url($page['seo_og_image']);
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $output = "\n<!-- JSON-LD Structured Data -->\n";
        $output .= "<script type=\"application/ld+json\">\n";
        $output .= $json . "\n";
        $output .= "</script>\n";
        $output .= "<!-- /JSON-LD Structured Data -->\n\n";

        return $output;
    }

    /**
     * Renderizar Title Tag
     */
    private static function renderTitle($page) {
        $title = !empty($page['seo_title']) ? $page['seo_title'] : $page['title'];
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'Site';

        return "<title>" . htmlspecialchars($title) . " - " . htmlspecialchars($siteName) . "</title>\n";
    }

    /**
     * Renderizar Meta Description
     */
    private static function renderDescription($page) {
        $description = !empty($page['seo_description']) ? $page['seo_description'] : ($page['description'] ?? '');

        if (empty($description)) {
            return '';
        }

        return '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    }

    /**
     * Renderizar Meta Robots
     */
    private static function renderRobots($page) {
        $robots = !empty($page['seo_robots']) ? $page['seo_robots'] : 'index,follow';

        return '<meta name="robots" content="' . htmlspecialchars($robots) . '">' . "\n";
    }

    /**
     * Renderizar Canonical URL
     */
    private static function renderCanonical($page) {
        $canonical = !empty($page['seo_canonical_url']) ? $page['seo_canonical_url'] : url('/' . $page['slug']);

        return '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
    }

    /**
     * Renderizar Open Graph Tags (Facebook, WhatsApp, LinkedIn)
     */
    private static function renderOpenGraph($page) {
        $output = "\n<!-- Open Graph -->\n";

        // OG Type
        $ogType = !empty($page['seo_og_type']) ? $page['seo_og_type'] : 'website';
        $output .= '<meta property="og:type" content="' . htmlspecialchars($ogType) . '">' . "\n";

        // OG URL
        $ogUrl = !empty($page['seo_canonical_url']) ? $page['seo_canonical_url'] : url('/' . $page['slug']);
        $output .= '<meta property="og:url" content="' . htmlspecialchars($ogUrl) . '">' . "\n";

        // OG Title
        $ogTitle = !empty($page['seo_og_title']) ? $page['seo_og_title'] : (!empty($page['seo_title']) ? $page['seo_title'] : $page['title']);
        $output .= '<meta property="og:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";

        // OG Description
        $ogDescription = !empty($page['seo_og_description']) ? $page['seo_og_description'] : (!empty($page['seo_description']) ? $page['seo_description'] : ($page['description'] ?? ''));
        if (!empty($ogDescription)) {
            $output .= '<meta property="og:description" content="' . htmlspecialchars($ogDescription) . '">' . "\n";
        }

        // OG Image
        if (!empty($page['seo_og_image'])) {
            $output .= '<meta property="og:image" content="' . htmlspecialchars(url($page['seo_og_image'])) . '">' . "\n";
        }

        // OG Site Name
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'Site';
        $output .= '<meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">' . "\n";

        // OG Locale
        $output .= '<meta property="og:locale" content="pt_BR">' . "\n";

        return $output;
    }

    /**
     * Renderizar Twitter Card Tags
     */
    private static function renderTwitterCard($page) {
        $output = "\n<!-- Twitter Card -->\n";

        // Twitter Card Type
        $cardType = !empty($page['seo_twitter_card']) ? $page['seo_twitter_card'] : 'summary';
        $output .= '<meta name="twitter:card" content="' . htmlspecialchars($cardType) . '">' . "\n";

        // Twitter Title
        $twitterTitle = !empty($page['seo_twitter_title']) ? $page['seo_twitter_title'] : (!empty($page['seo_title']) ? $page['seo_title'] : $page['title']);
        $output .= '<meta name="twitter:title" content="' . htmlspecialchars($twitterTitle) . '">' . "\n";

        // Twitter Description
        $twitterDescription = !empty($page['seo_twitter_description']) ? $page['seo_twitter_description'] : (!empty($page['seo_description']) ? $page['seo_description'] : ($page['description'] ?? ''));
        if (!empty($twitterDescription)) {
            $output .= '<meta name="twitter:description" content="' . htmlspecialchars($twitterDescription) . '">' . "\n";
        }

        // Twitter Image
        if (!empty($page['seo_og_image'])) {
            $output .= '<meta name="twitter:image" content="' . htmlspecialchars(url($page['seo_og_image'])) . '">' . "\n";
        }

        return $output;
    }
}
