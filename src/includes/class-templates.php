<?php
namespace WCPC;

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerencia os templates de página do plugin.
 */
class Templates {

	/**
	 * O template do nosso plugin.
	 * @var array
	 */
	private $plugin_templates;

	/**
	 * Inicializa os hooks.
	 */
	public function init() {
		$this->plugin_templates = [
			'src/template/template-final.php' => 'WCPC Final Render',
		];

		add_filter( 'theme_page_templates', [ $this, 'register_plugin_templates' ] );
		add_filter( 'template_include', [ $this, 'view_plugin_template' ] );
	}

	/**
	 * Adiciona nossos templates à lista de templates de página do WordPress.
	 *
	 * @param array $templates Os templates de página existentes.
	 * @return array
	 */
	public function register_plugin_templates( $templates ) {
		return array_merge( $templates, $this->plugin_templates );
	}

	/**
	 * Verifica se a página atual está usando nosso template e, em caso afirmativo,
	 * carrega o arquivo de template do nosso plugin.
	 *
	 * @param string $template O caminho do template a ser incluído.
	 * @return string
	 */
	public function view_plugin_template( $template ) {
		global $post;

		if ( ! $post || $post->post_type !== 'page' ) {
			return $template;
		}

		$page_template = get_post_meta( $post->ID, '_wp_page_template', true );

		if ( isset( $this->plugin_templates[ $page_template ] ) ) {
			$template_path = WCPC_PATH . $page_template;
			if ( file_exists( $template_path ) ) {
				return $template_path;
			}
		}

		return $template;
	}
}
