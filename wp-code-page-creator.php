<?php
/**
 * Plugin Name:       WP Code Page Creator
 * Plugin URI:        https://example.com/
 * Description:       Cria páginas a partir de código HTML, CSS e JS com pré-renderização no salvamento.
 * Version:           1.0.0
 * Author:            Seu Nome
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-code-page-creator
 * Domain Path:       /languages
 */

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constantes úteis
define( 'WCPC_VERSION', '1.0.0' );
define( 'WCPC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCPC_URL', plugin_dir_url( __FILE__ ) );

if ( class_exists( 'WCPC\Core' ) ) {
	return;
}

// Carrega a classe principal que gerencia o plugin.
require_once WCPC_PATH . 'src/includes/class-core.php';

/**
 * Inicia o plugin.
 */
function wcpc_run() {
	$plugin = new WCPC\Core();
	$plugin->init();
}
wcpc_run();
