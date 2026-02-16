<?php
/**
 * ImprovementsController - Gerenciamento de Melhorias Futuras
 *
 * @version 1.0.0
 */

class ImprovementsController extends BaseController {

	public function index() {
		Auth::require();

		// Lista de melhorias será construída com o usuário
		$improvements = [];

		$data = [
			'improvements' => $improvements
		];

		return $this->render('improvements', $data);
	}
}
