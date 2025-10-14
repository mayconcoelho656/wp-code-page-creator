<?php
namespace WCPC;

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe principal que carrega e inicializa os componentes do plugin.
 */
class Core {

	/**
	 * Carrega os arquivos necessários e inicializa as classes.
	 */
	public function init() {
		// Carrega as classes.
		require_once WCPC_PATH . 'src/includes/class-admin.php';
		require_once WCPC_PATH . 'src/includes/class-templates.php';

		// Inicializa a classe de administração.
		$admin = new \WCPC\Admin\Admin();
		$admin->init();

		// Inicializa a classe de templates.
		$templates = new \WCPC\Templates();
		$templates->init();
	}
}