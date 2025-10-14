<?php
namespace WCPC\Admin;

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe para funcionalidades de administração.
 */
class Admin {

    private const META_KEY_HTML = 'wcpc_html_completo';
	private const META_KEY_CSS  = 'wcpc_css_adicional';
	private const META_KEY_JS   = 'wcpc_js_adicional';
	private const META_KEY_COMPILED = 'wcpc_html_montado';

	/**
	 * Busca e compila o header ou footer selecionado para uma página.
	 *
	 * @param int    $post_id ID da página.
	 * @param string $type    Tipo: 'header' ou 'footer'.
	 * @return string HTML compilado do header/footer ou string vazia.
	 */
	private function get_compiled_header_footer( $post_id, $type ) {
		// Verifica se o tipo está habilitado
		$enable_key = "_wcpc_enable_{$type}";
		$selected_key = "_wcpc_selected_{$type}";
		
		$is_enabled = get_post_meta( $post_id, $enable_key, true );
		$selected_id = get_post_meta( $post_id, $selected_key, true );

		// Se não estiver habilitado ou não tiver selecionado, retorna vazio
		if ( $is_enabled !== '1' || empty( $selected_id ) ) {
			return '';
		}

		// Busca o header/footer compilado (corrigindo a meta_key)
		$compiled_content = get_post_meta( $selected_id, '_wcpc_hf_compiled', true );

		return $compiled_content ?: '';
	}

	/**
	 * Renderiza o conteúdo do meta box Header-Footer.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_header_footer_meta_box( $post ) {
		wp_nonce_field( 'wcpc_save_header_footer_data', 'wcpc_header_footer_nonce' );

		// Busca os valores salvos
		$enable_header = get_post_meta( $post->ID, '_wcpc_enable_header', true );
		$selected_header = get_post_meta( $post->ID, '_wcpc_selected_header', true );
		$enable_footer = get_post_meta( $post->ID, '_wcpc_enable_footer', true );
		$selected_footer = get_post_meta( $post->ID, '_wcpc_selected_footer', true );

		// Busca todos os headers e footers disponíveis
		$headers = get_posts( [
			'post_type' => 'wcpc_header_footer',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query' => [
				[
					'key' => '_wcpc_hf_type',
					'value' => 'header',
					'compare' => '='
				]
			]
		] );

		$footers = get_posts( [
			'post_type' => 'wcpc_header_footer',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query' => [
				[
					'key' => '_wcpc_hf_type',
					'value' => 'footer',
					'compare' => '='
				]
			]
		] );

		?>
		<div style="margin-bottom: 15px;">
			<label>
				<input type="checkbox" name="_wcpc_enable_header" value="1" <?php checked( $enable_header, '1' ); ?> />
				<?php _e( 'Ativar Header', 'wp-code-page-creator' ); ?>
			</label>
			<div style="margin-top: 5px;">
				<select name="_wcpc_selected_header" style="width: 100%;">
					<option value=""><?php _e( 'Selecione um Header', 'wp-code-page-creator' ); ?></option>
					<?php foreach ( $headers as $header ) : ?>
						<option value="<?php echo esc_attr( $header->ID ); ?>" <?php selected( $selected_header, $header->ID ); ?>>
							<?php echo esc_html( $header->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div>
			<label>
				<input type="checkbox" name="_wcpc_enable_footer" value="1" <?php checked( $enable_footer, '1' ); ?> />
				<?php _e( 'Ativar Footer', 'wp-code-page-creator' ); ?>
			</label>
			<div style="margin-top: 5px;">
				<select name="_wcpc_selected_footer" style="width: 100%;">
					<option value=""><?php _e( 'Selecione um Footer', 'wp-code-page-creator' ); ?></option>
					<?php foreach ( $footers as $footer ) : ?>
						<option value="<?php echo esc_attr( $footer->ID ); ?>" <?php selected( $selected_footer, $footer->ID ); ?>>
							<?php echo esc_html( $footer->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
	}

	/**
	 * Inicializa os hooks da área de administração.
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'setup_code_page_hooks' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'load-post-new.php', [ $this, 'maybe_add_save_hook' ] );
		add_action( 'save_post_page', [ $this, 'save_code_meta_boxes' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'add_html_post_state' ], 10, 2 );
	}

	/**
	 * Verifica se estamos em uma página de código e adiciona os hooks necessários.
	 */
	public function setup_code_page_hooks() {
		global $pagenow;

		$is_new_code_page = ( $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'page' && isset( $_GET['wcpc_new_code_page'] ) );

		$post_id = $_GET['post'] ?? 0;
		$is_existing_code_page = ( $pagenow === 'post.php' && $post_id && get_post_meta( $post_id, '_wcpc_is_code_page', true ) );

		if ( $is_new_code_page || $is_existing_code_page ) {
			add_filter( 'use_block_editor_for_post', '__return_false', 100 );
			add_action( 'add_meta_boxes', [ $this, 'add_code_meta_boxes' ] );
		
			// Remove o editor de conteúdo principal e a imagem destacada.
			remove_post_type_support( 'page', 'editor' );
			remove_meta_box( 'postimagediv', 'page', 'side' );
		}
	}

	/**
	 * Adiciona os meta boxes para os campos de código.
	 */
	public function add_code_meta_boxes() {
		add_meta_box(
			'wcpc_html_meta_box',
			__( 'HTML Completo', 'wp-code-page-creator' ),
			[ $this, 'render_meta_box_callback' ],
			'page',
			'normal',
			'high',
			[ 'meta_key' => self::META_KEY_HTML ]
		);

		add_meta_box(
			'wcpc_css_meta_box',
			__( 'CSS Adicional', 'wp-code-page-creator' ),
			[ $this, 'render_meta_box_callback' ],
			'page',
			'normal',
			'high',
			[ 'meta_key' => self::META_KEY_CSS ]
		);

		add_meta_box(
			'wcpc_js_meta_box',
			__( 'JS Adicional', 'wp-code-page-creator' ),
			[ $this, 'render_meta_box_callback' ],
			'page',
			'normal',
			'high',
			[ 'meta_key' => self::META_KEY_JS ]
		);

		add_meta_box(
			'wcpc_header_footer_meta_box',
			__( 'Header-Footer', 'wp-code-page-creator' ),
			[ $this, 'render_header_footer_meta_box' ],
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Renderiza o conteúdo do meta box (um textarea).
	 *
	 * @param WP_Post $post O objeto do post.
	 * @param array   $args Argumentos passados pelo add_meta_box.
	 */
	public function render_meta_box_callback( $post, $args ) {
		wp_nonce_field( 'wcpc_save_meta_box_data', 'wcpc_meta_box_nonce' );

		$meta_key = $args['args']['meta_key'];
		$value    = get_post_meta( $post->ID, $meta_key, true );

		echo '<textarea style="width:100%;min-height:200px;" name="' . esc_attr( $meta_key ) . '">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Salva os dados dos meta boxes e compila o HTML final.
	 *
	 * @param int     $post_id ID do post.
	 * @param WP_Post $post    Objeto do post.
	 */
	public function save_code_meta_boxes( $post_id, $post ) {
        // Verifica se estamos numa página de código
        if ( ! get_post_meta( $post_id, '_wcpc_is_code_page', true ) ) {
            return;
        }

		if ( ! isset( $_POST['wcpc_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wcpc_meta_box_nonce'], 'wcpc_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		// Salva os dados do Header-Footer se o nonce estiver presente
		if ( isset( $_POST['wcpc_header_footer_nonce'] ) && wp_verify_nonce( $_POST['wcpc_header_footer_nonce'], 'wcpc_save_header_footer_data' ) ) {
			$enable_header = isset( $_POST['_wcpc_enable_header'] ) ? '1' : '0';
			$selected_header = sanitize_text_field( $_POST['_wcpc_selected_header'] ?? '' );
			$enable_footer = isset( $_POST['_wcpc_enable_footer'] ) ? '1' : '0';
			$selected_footer = sanitize_text_field( $_POST['_wcpc_selected_footer'] ?? '' );

			update_post_meta( $post_id, '_wcpc_enable_header', $enable_header );
			update_post_meta( $post_id, '_wcpc_selected_header', $selected_header );
			update_post_meta( $post_id, '_wcpc_enable_footer', $enable_footer );
			update_post_meta( $post_id, '_wcpc_selected_footer', $selected_footer );
		}

		// Salva os campos individuais
		$meta_keys = [ self::META_KEY_HTML, self::META_KEY_CSS, self::META_KEY_JS ];
		$post_data = [];
		foreach ( $meta_keys as $meta_key ) {
			if ( isset( $_POST[ $meta_key ] ) ) {
				$sanitized_value = wp_unslash( $_POST[ $meta_key ] );
				update_post_meta( $post_id, $meta_key, $sanitized_value );
				$post_data[ $meta_key ] = $sanitized_value;
			}
		}

		// Compila o HTML final
		$html = $post_data[self::META_KEY_HTML] ?? '';
		$css  = $post_data[self::META_KEY_CSS] ?? '';
		$js   = $post_data[self::META_KEY_JS] ?? '';

		// Adiciona {{wcpc_head}} e CSS no final do <head>
		$head_content = "\n{{wcpc_head}}\n";
		if ( ! empty( trim( $css ) ) ) {
			// Minifica o CSS removendo quebras de linha, espaços extras e comentários
			$minified_css = $this->minify_css( $css );
			$head_content .= "\n<style>{$minified_css}</style>\n\n";
		}
		$html = str_replace( '</head>', $head_content . '</head>', $html );

		// Busca e compila os headers/footers selecionados
		$compiled_header = $this->get_compiled_header_footer( $post_id, 'header' );
		$compiled_footer = $this->get_compiled_header_footer( $post_id, 'footer' );

		// Adiciona o header compilado no início do <body>
		$html = str_replace( '<body>', "<body>\n\n{$compiled_header}\n", $html );

		// Adiciona o footer compilado, JS e {{wcpc_footer}} no final do <body>
		$footer_content = "\n{$compiled_footer}\n";
		if ( ! empty( trim( $js ) ) ) {
			// Minifica o JS removendo quebras de linha, espaços extras e comentários
			$minified_js = $this->minify_js( $js );
			$footer_content .= "\n<script>{$minified_js}</script>\n";
		}
		$footer_content .= "\n{{wcpc_footer}}\n\n";
		$html = str_replace( '</body>', $footer_content . '</body>', $html );

		// Salva o HTML compilado
		update_post_meta( $post_id, self::META_KEY_COMPILED, $html );
	}

	/**
	 * Adiciona o hook para salvar a flag apenas se a página for criada a partir do nosso menu.
	 */
	public function maybe_add_save_hook() {
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'page' && isset( $_GET['wcpc_new_code_page'] ) && $_GET['wcpc_new_code_page'] === 'true' ) {
			add_action( 'save_post_page', [ $this, 'set_code_page_flag' ], 10, 2 );
		}
	}

	/**
	 * Define a flag _wcpc_is_code_page no post meta no primeiro salvamento.
	 *
	 * @param int     $post_id ID do post.
	 * @param WP_Post $post    Objeto do post.
	 */
	public function set_code_page_flag( $post_id, $post ) {
		// Se já tiver a flag, não faz nada.
		if ( get_post_meta( $post_id, '_wcpc_is_code_page', true ) ) {
			return;
		}

		// Verifica se não é um autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verifica permissões.
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		// Adiciona a flag.
		update_post_meta( $post_id, '_wcpc_is_code_page', true );

		// Define o template da página.
		update_post_meta( $post_id, '_wp_page_template', 'src/template/template-final.php' );
	}

	/**
	 * Adiciona o submenu ao menu "Páginas".
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=page',
			__( 'Criar Página HTML', 'wp-code-page-creator' ),
			__( 'Criar Página HTML', 'wp-code-page-creator' ),
			'edit_pages',
			'post-new.php?post_type=page&wcpc_new_code_page=true'
		);
	}

	/**
	 * Minifica o CSS removendo quebras de linha, espaços extras e comentários.
	 *
	 * @param string $css O código CSS a ser minificado.
	 * @return string O CSS minificado.
	 */
	private function minify_css( $css ) {
		// Remove comentários CSS (/* ... */)
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
		
		// Remove quebras de linha e espaços extras
		$css = preg_replace( '/\s+/', ' ', $css );
		
		// Remove espaços ao redor de caracteres especiais
		$css = preg_replace( '/\s*([{}:;,>+~])\s*/', '$1', $css );
		
		// Remove espaços no início e fim
		$css = trim( $css );
		
		return $css;
	}

	/**
	 * Minifica o JavaScript removendo quebras de linha, espaços extras e comentários.
	 *
	 * @param string $js O código JavaScript a ser minificado.
	 * @return string O JavaScript minificado.
	 */
	private function minify_js( $js ) {
		// Remove comentários de linha única (//)
		$js = preg_replace( '/\/\/.*$/m', '', $js );
		
		// Remove comentários de múltiplas linhas (/* ... */)
		$js = preg_replace( '/\/\*.*?\*\//s', '', $js );
		
		// Remove quebras de linha e espaços extras
		$js = preg_replace( '/\s+/', ' ', $js );
		
		// Remove espaços ao redor de operadores e pontuação
		$js = preg_replace( '/\s*([{}();,=+\-*\/&|!<>])\s*/', '$1', $js );
		
		// Remove espaços no início e fim
		$js = trim( $js );
		
		return $js;
	}

	/**
	 * Adiciona o post state "HTML" para páginas que são páginas de código.
	 *
	 * @param array   $post_states Array de estados do post.
	 * @param WP_Post $post        Objeto do post.
	 * @return array Array modificado de estados do post.
	 */
	public function add_html_post_state( $post_states, $post ) {
		// Verifica se é uma página e se tem a flag de página de código
		if ( $post->post_type === 'page' && get_post_meta( $post->ID, '_wcpc_is_code_page', true ) ) {
			$post_states['wcpc_html'] = __( 'HTML', 'wp-code-page-creator' );
		}

		return $post_states;
	}
}