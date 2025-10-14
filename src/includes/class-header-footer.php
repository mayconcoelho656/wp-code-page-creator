<?php
namespace WCPC\Admin;

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe responsável pelo gerenciamento do Custom Post Type Header/Footer.
 */
class HeaderFooter {

	// Constantes para as meta keys
	const META_HTML = '_wcpc_hf_html';
	const META_CSS = '_wcpc_hf_css';
	const META_JS = '_wcpc_hf_js';
	const META_TYPE = '_wcpc_hf_type';
	const META_COMPILED = '_wcpc_hf_compiled';
	const META_FLAG = '_wcpc_is_header_footer';

	/**
	 * Inicializa os hooks necessários.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Registra o Custom Post Type para Header/Footer.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => 'Header/Footer',
			'singular_name'         => 'Header/Footer',
			'menu_name'             => 'Header/Footer',
			'name_admin_bar'        => 'Header/Footer',
			'archives'              => 'Arquivos de Header/Footer',
			'attributes'            => 'Atributos do Header/Footer',
			'parent_item_colon'     => 'Header/Footer Pai:',
			'all_items'             => 'Todos os Headers/Footers',
			'add_new_item'          => 'Adicionar Novo Header/Footer',
			'add_new'               => 'Adicionar Novo',
			'new_item'              => 'Novo Header/Footer',
			'edit_item'             => 'Editar Header/Footer',
			'update_item'           => 'Atualizar Header/Footer',
			'view_item'             => 'Ver Header/Footer',
			'view_items'            => 'Ver Headers/Footers',
			'search_items'          => 'Buscar Headers/Footers',
			'not_found'             => 'Não encontrado',
			'not_found_in_trash'    => 'Não encontrado na lixeira',
			'featured_image'        => 'Imagem destacada',
			'set_featured_image'    => 'Definir imagem destacada',
			'remove_featured_image' => 'Remover imagem destacada',
			'use_featured_image'    => 'Usar como imagem destacada',
			'insert_into_item'      => 'Inserir no Header/Footer',
			'uploaded_to_this_item' => 'Enviado para este Header/Footer',
			'items_list'            => 'Lista de Headers/Footers',
			'items_list_navigation' => 'Navegação da lista de Headers/Footers',
			'filter_items_list'     => 'Filtrar lista de Headers/Footers',
		);

		$args = array(
			'label'                 => 'Header/Footer',
			'description'           => 'Headers e Footers para páginas WCPC',
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => false, // Será adicionado manualmente
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-editor-code',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
		);

		register_post_type( 'wcpc_header_footer', $args );
	}

	/**
	 * Adiciona os metaboxes para o CPT Header/Footer.
	 */
	public function add_meta_boxes() {
		// Verifica se estamos na tela correta
		$screen = get_current_screen();
		if ( $screen->post_type !== 'wcpc_header_footer' ) {
			return;
		}

		// Adiciona metabox para Tipo (Header ou Footer)
		add_meta_box(
			'wcpc_hf_type',
			'Tipo',
			array( $this, 'render_type_meta_box' ),
			'wcpc_header_footer',
			'side',
			'high'
		);

		// Adiciona metabox para HTML
		add_meta_box(
			'wcpc_hf_html',
			'Código HTML',
			array( $this, 'render_html_meta_box' ),
			'wcpc_header_footer',
			'normal',
			'high'
		);

		// Adiciona metabox para CSS
		add_meta_box(
			'wcpc_hf_css',
			'Código CSS',
			array( $this, 'render_css_meta_box' ),
			'wcpc_header_footer',
			'normal',
			'high'
		);

		// Adiciona metabox para JS
		add_meta_box(
			'wcpc_hf_js',
			'Código JavaScript',
			array( $this, 'render_js_meta_box' ),
			'wcpc_header_footer',
			'normal',
			'high'
		);
	}

	/**
	 * Renderiza o metabox de Tipo (Header ou Footer).
	 */
	public function render_type_meta_box( $post ) {
		wp_nonce_field( 'wcpc_hf_meta_box', 'wcpc_hf_meta_box_nonce' );
		
		$type = get_post_meta( $post->ID, self::META_TYPE, true );
		?>
		<p>
			<label>
				<input type="radio" name="wcpc_hf_type" value="header" <?php checked( $type, 'header' ); ?> />
				Header
			</label>
		</p>
		<p>
			<label>
				<input type="radio" name="wcpc_hf_type" value="footer" <?php checked( $type, 'footer' ); ?> />
				Footer
			</label>
		</p>
		<?php
	}

	/**
	 * Renderiza o metabox de HTML.
	 */
	public function render_html_meta_box( $post ) {
		$html = get_post_meta( $post->ID, self::META_HTML, true );
		?>
		<textarea name="wcpc_hf_html" rows="15" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $html ); ?></textarea>
		<p class="description">Adicione o código HTML do seu header ou footer aqui.</p>
		<?php
	}

	/**
	 * Renderiza o metabox de CSS.
	 */
	public function render_css_meta_box( $post ) {
		$css = get_post_meta( $post->ID, self::META_CSS, true );
		?>
		<textarea name="wcpc_hf_css" rows="15" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $css ); ?></textarea>
		<p class="description">Adicione o código CSS do seu header ou footer aqui (sem as tags &lt;style&gt;).</p>
		<?php
	}

	/**
	 * Renderiza o metabox de JavaScript.
	 */
	public function render_js_meta_box( $post ) {
		$js = get_post_meta( $post->ID, self::META_JS, true );
		?>
		<textarea name="wcpc_hf_js" rows="15" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $js ); ?></textarea>
		<p class="description">Adicione o código JavaScript do seu header ou footer aqui (sem as tags &lt;script&gt;).</p>
		<?php
	}

	/**
	 * Salva os dados dos metaboxes.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verifica se é o post type correto
		if ( get_post_type( $post_id ) !== 'wcpc_header_footer' ) {
			return;
		}

		// Verifica o nonce
		if ( ! isset( $_POST['wcpc_hf_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wcpc_hf_meta_box_nonce'], 'wcpc_hf_meta_box' ) ) {
			return;
		}

		// Verifica se não é um autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verifica permissões
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Define a flag que identifica este post como Header/Footer
		update_post_meta( $post_id, self::META_FLAG, '1' );

		// Salva o tipo (header ou footer)
		if ( isset( $_POST['wcpc_hf_type'] ) ) {
			$type = sanitize_text_field( $_POST['wcpc_hf_type'] );
			update_post_meta( $post_id, self::META_TYPE, $type );
		}

		// Salva o HTML
		if ( isset( $_POST['wcpc_hf_html'] ) ) {
			$html = wp_kses_post( $_POST['wcpc_hf_html'] );
			update_post_meta( $post_id, self::META_HTML, $html );
		}

		// Salva o CSS
		if ( isset( $_POST['wcpc_hf_css'] ) ) {
			$css = sanitize_textarea_field( $_POST['wcpc_hf_css'] );
			update_post_meta( $post_id, self::META_CSS, $css );
		}

		// Salva o JS
		if ( isset( $_POST['wcpc_hf_js'] ) ) {
			$js = sanitize_textarea_field( $_POST['wcpc_hf_js'] );
			update_post_meta( $post_id, self::META_JS, $js );
		}

		// Compila o código final
		$this->compile_header_footer( $post_id );
	}

	/**
	 * Compila o código HTML, CSS e JS em um único código final.
	 */
	private function compile_header_footer( $post_id ) {
		$html = get_post_meta( $post_id, self::META_HTML, true );
		$css = get_post_meta( $post_id, self::META_CSS, true );
		$js = get_post_meta( $post_id, self::META_JS, true );

		// Minifica CSS e JS
		$css_minified = $this->minify_css( $css );
		$js_minified = $this->minify_js( $js );

		// Monta o código compilado
		$compiled = '';
		
		// Adiciona CSS se existir
		if ( ! empty( $css_minified ) ) {
			$compiled .= '<style>' . $css_minified . '</style>';
		}

		// Adiciona HTML
		if ( ! empty( $html ) ) {
			$compiled .= '<section class="wcpc-header-footer">' . $html . '</section>';
		}

		// Adiciona JS se existir
		if ( ! empty( $js_minified ) ) {
			$compiled .= '<script>' . $js_minified . '</script>';
		}

		// Salva o código compilado
		update_post_meta( $post_id, self::META_COMPILED, $compiled );
	}

	/**
	 * Minifica código CSS removendo comentários, quebras de linha e espaços extras.
	 */
	private function minify_css( $css ) {
		if ( empty( $css ) ) {
			return '';
		}

		// Remove comentários CSS
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		
		// Remove quebras de linha e espaços extras
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );
		
		return trim( $css );
	}

	/**
	 * Minifica código JavaScript removendo comentários, quebras de linha e espaços extras.
	 */
	private function minify_js( $js ) {
		if ( empty( $js ) ) {
			return '';
		}

		// Remove comentários de linha única
		$js = preg_replace( '!//.*!', '', $js );
		
		// Remove comentários de múltiplas linhas
		$js = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js );
		
		// Remove quebras de linha e espaços extras
		$js = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $js );
		
		return trim( $js );
	}

	/**
	 * Adiciona o menu administrativo para gerenciar Headers/Footers.
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Header/Footer',
			'Header/Footer',
			'manage_options',
			'edit.php?post_type=wcpc_header_footer',
			'',
			'dashicons-editor-code',
			25
		);
	}
}