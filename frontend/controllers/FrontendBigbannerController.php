<?php
/**
 * FrontendBigbannerController
 * Controller frontend para BigBanner
 * Gerado automaticamente
 */

class FrontendBigbannerController {

    /**
     * Buscar registros ativos
     */
    public function getActive() {
        $db = DB::connect();

        $query = "SELECT id, iamge, title, subtitle, cta, cta_link, slug
                 FROM tbl_bigbanner
                 WHERE ativo = 1
                 ORDER BY `order` ASC
                 LIMIT 10";

        return $db->query($query);
    }
}
