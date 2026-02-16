<?php
/**
 * SitemapController - Gerenciamento do sitemap.xml
 *
 * @version 1.0.0
 */

class SitemapController extends BaseController {

    public function index() {
        Auth::require();

        $sitemapPath = ROOT_PATH . 'public/sitemap.xml';

        $data = [
            'exists' => file_exists($sitemapPath),
            'content' => file_exists($sitemapPath) ? file_get_contents($sitemapPath) : '',
            'path' => $sitemapPath,
            'url' => url('/sitemap.xml'),
            'last_modified' => file_exists($sitemapPath) ? date('d/m/Y H:i:s', filemtime($sitemapPath)) : null
        ];

        return $this->render('sitemap', $data);
    }

    public function generate() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        try {
            $sitemap = $this->buildSitemap();
            $sitemapPath = ROOT_PATH . 'public/sitemap.xml';

            file_put_contents($sitemapPath, $sitemap);
            $_SESSION['success'] = 'Sitemap.xml gerado com sucesso!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao gerar sitemap.xml: ' . $e->getMessage();
        }

        header('Location: ' . url('/admin/sitemap'));
        exit;
    }

    private function buildSitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $urls = [];

        // 1. Homepage
        $urls[] = [
            'loc' => url('/'),
            'priority' => '1.0',
            'changefreq' => 'daily'
        ];

        // 2. Páginas públicas do site (somente custom, não core do framework)
        $pages = DB::query("
            SELECT slug, updated_at
            FROM pages
            WHERE ativo = 1
            AND scope = 'frontend'
            AND type = 'custom'
            AND is_public = 1
            ORDER BY slug
        ");
        foreach ($pages as $page) {
            $urls[] = [
                'loc' => url('/' . $page['slug']),
                'lastmod' => date('Y-m-d', strtotime($page['updated_at'])),
                'priority' => '0.8',
                'changefreq' => 'weekly'
            ];
        }

        // 3. Módulo Notícias (se instalado)
        if (ModuleManager::isInstalled('noticias')) {
            $tableExists = count(DB::query("SHOW TABLES LIKE 'noticias'")) > 0;
            if ($tableExists) {
                $noticias = DB::query("SELECT slug, updated_at FROM noticias WHERE ativo = 1 ORDER BY created_at DESC");
                foreach ($noticias as $noticia) {
                    $urls[] = [
                        'loc' => url('/noticias/' . $noticia['slug']),
                        'lastmod' => date('Y-m-d', strtotime($noticia['updated_at'])),
                        'priority' => '0.7',
                        'changefreq' => 'monthly'
                    ];
                }
            }
        }

        // Adicionar URLs ao XML
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            if (isset($url['lastmod'])) {
                $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
