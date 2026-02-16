<?php
/**
 * FrontendBannerController
 * Busca banners ativos para exibição frontend (read-only)
 */

class FrontendBannerController extends BaseController {

    /**
     * Buscar banners ativos para exibição
     * @return array Banners ativos ordenados
     */
    public function getActiveBanners() {
        try {
            // Buscar apenas banners ATIVOS, ordenados por 'order'
            $banners = $this->db()->query(
                "SELECT id, title, subtitle, image, button_text, button_url, `order`
                 FROM banners
                 WHERE ativo = 1
                 ORDER BY `order` ASC, created_at DESC"
            );

            return $banners ?? [];

        } catch (Exception $e) {
            error_log('FrontendBannerController::getActiveBanners() ERROR: ' . $e->getMessage());
            return [];
        }
    }
}
