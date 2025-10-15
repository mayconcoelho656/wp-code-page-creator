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
		require_once WCPC_PATH . 'src/includes/priorities.php';
		require_once WCPC_PATH . 'src/includes/class-admin.php';
		require_once WCPC_PATH . 'src/includes/class-templates.php';
		require_once WCPC_PATH . 'src/includes/class-header-footer.php';
		require_once WCPC_PATH . 'src/includes/class-block-html.php';

		// Inicializa a classe de administração.
		$admin = new \WCPC\Admin\Admin();
		$admin->init();

		// Inicializa a classe de templates.
		$templates = new \WCPC\Templates();
		$templates->init();

		// Inicializa a classe de Header/Footer.
		$header_footer = new \WCPC\Admin\HeaderFooter();
		$header_footer->init();

		// Inicializa a classe de Block HTML.
		$block_html = new \WCPC\Admin\BlockHtml();
		$block_html->init();
	}
}