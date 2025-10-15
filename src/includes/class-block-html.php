<?php
namespace WCPC\Admin;

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe responsável pelo gerenciamento do Custom Post Type Block HTML.
 */
class BlockHtml {

	// Constantes para as meta keys
	const META_HTML = '_wcpc_block_html';
	const META_CSS = '_wcpc_block_css';
	const META_JS = '_wcpc_block_js';
	const META_COMPILED = '_wcpc_block_compiled';
	const META_FLAG = '_wcpc_is_block_html';
	const META_ACTIVE = '_wcpc_block_active';
	const META_MINIFY = '_wcpc_block_minify_enabled';

	/**
	 * Inicializa os hooks necessários.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_wcpc_block_html', array( $this, 'save_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'load-post-new.php', array( $this, 'maybe_add_save_hook' ) );
		add_shortcode( 'wcpc_block', array( $this, 'render_shortcode' ) );
		add_action( 'admin_init', array( $this, 'setup_block_html_hooks' ) );
	}

	/**
	 * Renderiza o metabox para configurações de minificação.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_minify_meta_box( $post ) {
		// Adiciona nonce para segurança
		wp_nonce_field( 'wcpc_save_block_meta_box_data', 'wcpc_block_meta_box_nonce' );
		
		// Obtém o valor atual (padrão é desativo)
		$minify_enabled = get_post_meta( $post->ID, self::META_MINIFY, true );
		$minify_enabled = ( $minify_enabled === '' ) ? '0' : $minify_enabled; // Padrão desativo para novos blocks
		
		echo '<p><label>';
		echo '<input type="checkbox" name="wcpc_block_minify" value="1" ' . checked( $minify_enabled, '1', false ) . ' />';
		echo ' <strong>Ativar Minificação</strong>';
		echo '</label></p>';
		echo '<p><small>Remove quebras de linha, espaços extras e comentários do HTML, CSS e JS compilados.</small></p>';
	}

	/**
	 * Registra o Custom Post Type para Block HTML.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => 'Block HTML',
			'singular_name'         => 'Block HTML',
			'menu_name'             => 'Block HTML',
			'name_admin_bar'        => 'Block HTML',
			'archives'              => 'Arquivos de Block HTML',
			'attributes'            => 'Atributos do Block HTML',
			'parent_item_colon'     => 'Block HTML Pai:',
			'all_items'             => 'Todos os Blocks HTML',
			'add_new_item'          => 'Adicionar Novo Block HTML',
			'add_new'               => 'Adicionar Novo',
			'new_item'              => 'Novo Block HTML',
			'edit_item'             => 'Editar Block HTML',
			'update_item'           => 'Atualizar Block HTML',
			'view_item'             => 'Ver Block HTML',
			'view_items'            => 'Ver Blocks HTML',
			'search_items'          => 'Buscar Blocks HTML',
			'not_found'             => 'Não encontrado',
			'not_found_in_trash'    => 'Não encontrado na lixeira',
			'featured_image'        => 'Imagem destacada',
			'set_featured_image'    => 'Definir imagem destacada',
			'remove_featured_image' => 'Remover imagem destacada',
			'use_featured_image'    => 'Usar como imagem destacada',
			'insert_into_item'      => 'Inserir no Block HTML',
			'uploaded_to_this_item' => 'Enviado para este Block HTML',
			'items_list'            => 'Lista de Blocks HTML',
			'items_list_navigation' => 'Navegação da lista de Blocks HTML',
			'filter_items_list'     => 'Filtrar lista de Blocks HTML',
		);

		$args = array(
			'label'                 => 'Block HTML',
			'description'           => 'Blocks HTML reutilizáveis para páginas WCPC',
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => false, // Será adicionado manualmente
			'menu_position'         => 26,
			'menu_icon'             => 'dashicons-editor-code',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
		);

		register_post_type( 'wcpc_block_html', $args );
	}

	/**
	 * Verifica se estamos em um block HTML e adiciona os hooks necessários.
	 */
	public function setup_block_html_hooks() {
		global $pagenow;

		$is_new_block = ( $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wcpc_block_html' && isset( $_GET['wcpc_new_block'] ) );

		$post_id = $_GET['post'] ?? 0;
		$is_existing_block = ( $pagenow === 'post.php' && $post_id && get_post_meta( $post_id, self::META_FLAG, true ) );

		if ( $is_new_block || $is_existing_block ) {
			add_filter( 'use_block_editor_for_post', '__return_false', 100 );
			
			// Remove o editor de conteúdo principal e a imagem destacada.
			remove_post_type_support( 'wcpc_block_html', 'editor' );
			remove_meta_box( 'postimagediv', 'wcpc_block_html', 'side' );
		}
	}

	/**
	 * Adiciona os metaboxes para o CPT Block HTML.
	 */
	public function add_meta_boxes() {
		// Verifica se estamos na tela correta
		$screen = get_current_screen();
		if ( $screen->post_type !== 'wcpc_block_html' ) {
			return;
		}

		// Adiciona metabox para HTML
		add_meta_box(
			'wcpc_block_html_meta_box',
			__( 'HTML do Block', 'wp-code-page-creator' ),
			array( $this, 'render_html_meta_box' ),
			'wcpc_block_html',
			'normal',
			'high'
		);

		// Adiciona metabox para CSS
		add_meta_box(
			'wcpc_block_css_meta_box',
			__( 'CSS do Block', 'wp-code-page-creator' ),
			array( $this, 'render_css_meta_box' ),
			'wcpc_block_html',
			'normal',
			'high'
		);

		// Adiciona metabox para JS
		add_meta_box(
			'wcpc_block_js_meta_box',
			__( 'JS do Block', 'wp-code-page-creator' ),
			array( $this, 'render_js_meta_box' ),
			'wcpc_block_html',
			'normal',
			'high'
		);

		// Adiciona metabox para Shortcode
		add_meta_box(
			'wcpc_block_shortcode_meta_box',
			__( 'Shortcode', 'wp-code-page-creator' ),
			array( $this, 'render_shortcode_meta_box' ),
			'wcpc_block_html',
			'side',
			'high'
		);

		// Adiciona metabox para Ativação/Desativação
		add_meta_box(
			'wcpc_block_active_meta_box',
			__( 'Status do Block', 'wp-code-page-creator' ),
			array( $this, 'render_active_meta_box' ),
			'wcpc_block_html',
			'side',
			'high'
		);

		// Adiciona metabox para Minificação
		add_meta_box(
			'wcpc_block_minify_meta_box',
			__( 'Minificação Completa', 'wp-code-page-creator' ),
			array( $this, 'render_minify_meta_box' ),
			'wcpc_block_html',
			'side',
			'default'
		);
	}

	/**
	 * Renderiza o metabox de HTML.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_html_meta_box( $post ) {
		wp_nonce_field( 'wcpc_save_block_meta_box_data', 'wcpc_block_meta_box_nonce' );

		$value = get_post_meta( $post->ID, self::META_HTML, true );

		echo '<textarea style="width:100%;min-height:200px;" name="' . esc_attr( self::META_HTML ) . '" placeholder="Digite o HTML do seu block aqui...">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Renderiza o metabox de CSS.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_css_meta_box( $post ) {
		$value = get_post_meta( $post->ID, self::META_CSS, true );

		echo '<textarea style="width:100%;min-height:200px;" name="' . esc_attr( self::META_CSS ) . '" placeholder="Digite o CSS do seu block aqui...">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Renderiza o metabox de JS.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_js_meta_box( $post ) {
		$value = get_post_meta( $post->ID, self::META_JS, true );

		echo '<textarea style="width:100%;min-height:200px;" name="' . esc_attr( self::META_JS ) . '" placeholder="Digite o JavaScript do seu block aqui...">' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Renderiza o metabox de Shortcode.
	 *
	 * @param WP_Post $post O objeto do post.
	 */
	public function render_shortcode_meta_box( $post ) {
		if ( $post->ID ) {
			$shortcode = '[wcpc_block id="' . $post->ID . '"]';
			echo '<p><strong>Use este shortcode para inserir o block:</strong></p>';
			echo '<input type="text" value="' . esc_attr( $shortcode ) . '" readonly style="width:100%;" onclick="this.select();" />';
			echo '<p><small>Clique no campo acima para selecionar e copiar o shortcode.</small></p>';
		} else {
			echo '<p>Salve o block primeiro para gerar o shortcode.</p>';
		}
	}

	/**
	 * Renderiza o metabox de ativação/desativação do block.
	 *
	 * @param WP_Post $post Objeto do post.
	 */
	public function render_active_meta_box( $post ) {
		// Adiciona nonce para segurança
		wp_nonce_field( 'wcpc_save_block_meta_box_data', 'wcpc_block_meta_box_nonce' );
		
		// Obtém o valor atual (padrão é ativo)
		$is_active = get_post_meta( $post->ID, self::META_ACTIVE, true );
		$is_active = ( $is_active === '' ) ? '1' : $is_active; // Padrão ativo para novos blocks
		
		echo '<p><label>';
		echo '<input type="checkbox" name="wcpc_block_active" value="1" ' . checked( $is_active, '1', false ) . ' />';
		echo ' <strong>Block Ativo</strong>';
		echo '</label></p>';
		echo '<p><small>Quando desativado, o shortcode não será renderizado nas páginas.</small></p>';
	}

	/**
	 * Adiciona o hook para salvar a flag apenas se o block for criado a partir do nosso menu.
	 */
	public function maybe_add_save_hook() {
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wcpc_block_html' && isset( $_GET['wcpc_new_block'] ) && $_GET['wcpc_new_block'] === 'true' ) {
			add_action( 'save_post_wcpc_block_html', array( $this, 'set_block_flag' ), 10, 2 );
		}
	}

	/**
	 * Define a flag _wcpc_is_block_html no post meta no primeiro salvamento.
	 *
	 * @param int     $post_id ID do post.
	 * @param WP_Post $post    Objeto do post.
	 */
	public function set_block_flag( $post_id, $post ) {
		// Se já tiver a flag, não faz nada.
		if ( get_post_meta( $post_id, self::META_FLAG, true ) ) {
			return;
		}

		// Verifica se não é um autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verifica permissões.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Adiciona a flag.
		update_post_meta( $post_id, self::META_FLAG, true );
	}

	/**
	 * Salva os dados dos metaboxes e compila o HTML final.
	 *
	 * @param int $post_id ID do post.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verifica se é o tipo de post correto
		if ( get_post_type( $post_id ) !== 'wcpc_block_html' ) {
			return;
		}

		// Define a flag automaticamente se não existir
		if ( ! get_post_meta( $post_id, self::META_FLAG, true ) ) {
			update_post_meta( $post_id, self::META_FLAG, true );
		}

		if ( ! isset( $_POST['wcpc_block_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wcpc_block_meta_box_nonce'], 'wcpc_save_block_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Salva os campos individuais
		$meta_keys = [ self::META_HTML, self::META_CSS, self::META_JS ];
		$post_data = [];
		
		foreach ( $meta_keys as $meta_key ) {
			if ( isset( $_POST[ $meta_key ] ) ) {
				$sanitized_value = wp_unslash( $_POST[ $meta_key ] );
				update_post_meta( $post_id, $meta_key, $sanitized_value );
				$post_data[ $meta_key ] = $sanitized_value;
			}
		}

		// Salva o status de ativação do block
		$is_active = isset( $_POST['wcpc_block_active'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META_ACTIVE, $is_active );

		// Salva o status de minificação
		$minify_enabled = isset( $_POST['wcpc_block_minify'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META_MINIFY, $minify_enabled );

		// Compila o HTML final no formato solicitado
		$html = $post_data[self::META_HTML] ?? '';
		$css  = $post_data[self::META_CSS] ?? '';
		$js   = $post_data[self::META_JS] ?? '';

		$compiled_content = '';

		// Verifica se a minificação está habilitada
		$minify_enabled = get_post_meta( $post_id, self::META_MINIFY, true );
		$minify_enabled = ( $minify_enabled === '' ) ? '0' : $minify_enabled; // Padrão desativo

		// Adiciona CSS (minificado ou não)
		if ( ! empty( trim( $css ) ) ) {
			if ( $minify_enabled === '1' ) {
				$processed_css = $this->minify_css( $css );
				$compiled_content .= "<style>{$processed_css}</style>";
			} else {
				$compiled_content .= "<style>\n{$css}\n</style>\n\n";
			}
		}

		// Adiciona HTML (minificado ou não)
		if ( ! empty( trim( $html ) ) ) {
			if ( $minify_enabled === '1' ) {
				$processed_html = $this->minify_html( $html );
				$compiled_content .= $processed_html;
			} else {
				$compiled_content .= "{$html}\n\n";
			}
		}

		// Adiciona JS (minificado ou não)
		if ( ! empty( trim( $js ) ) ) {
			if ( $minify_enabled === '1' ) {
				$processed_js = $this->minify_js( $js );
				$compiled_content .= "<script>{$processed_js}</script>";
			} else {
				$compiled_content .= "<script>\n{$js}\n</script>";
			}
		}

		// Salva o conteúdo compilado
		update_post_meta( $post_id, self::META_COMPILED, $compiled_content );
	}

	/**
	 * Renderiza o shortcode do block HTML.
	 *
	 * @param array $atts Atributos do shortcode.
	 * @return string HTML compilado do block.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'wcpc_block' );

		$block_id = intval( $atts['id'] );

		if ( ! $block_id ) {
			return '';
		}

		// Verifica se o post existe e é do tipo correto
		$post = get_post( $block_id );
		if ( ! $post || $post->post_type !== 'wcpc_block_html' || $post->post_status !== 'publish' ) {
			return '';
		}

		// Verifica se tem a flag de block HTML
		if ( ! get_post_meta( $block_id, self::META_FLAG, true ) ) {
			return '';
		}

		// Verifica se o block está ativo
		$is_active = get_post_meta( $block_id, self::META_ACTIVE, true );
		if ( $is_active === '0' ) {
			return ''; // Retorna vazio se o block estiver desativado
		}

		// Retorna o conteúdo compilado
		$compiled_content = get_post_meta( $block_id, self::META_COMPILED, true );
		
		return $compiled_content ?: '';
	}

	/**
	 * Adiciona o menu administrativo.
	 */
	public function add_admin_menu() {
		// Adiciona o menu principal
		add_menu_page(
			'Block HTML',
			'Block HTML',
			'edit_posts',
			'edit.php?post_type=wcpc_block_html',
			'',
			'dashicons-editor-code',
			26
		);

		// Adiciona o submenu "Adicionar Novo"
		add_submenu_page(
			'edit.php?post_type=wcpc_block_html',
			__( 'Adicionar Block HTML', 'wp-code-page-creator' ),
			__( 'Adicionar Novo', 'wp-code-page-creator' ),
			'edit_posts',
			'post-new.php?post_type=wcpc_block_html&wcpc_new_block=true'
		);
	}

	/**
	 * Minifica o HTML removendo quebras de linha, espaços extras e comentários.
	 *
	 * @param string $html O código HTML a ser minificado.
	 * @return string O HTML minificado.
	 */
	private function minify_html( $html ) {
		// Remove comentários HTML (<!-- ... -->)
		$html = preg_replace( '/<!--.*?-->/s', '', $html );
		
		// Remove quebras de linha e espaços extras entre tags
		$html = preg_replace( '/>\s+</', '><', $html );
		
		// Remove espaços extras no início e fim de cada linha
		$html = preg_replace( '/^\s+|\s+$/m', '', $html );
		
		// Remove quebras de linha vazias
		$html = preg_replace( '/\n\s*\n/', '', $html );
		
		// Remove espaços múltiplos dentro do conteúdo (mas preserva espaços únicos)
		$html = preg_replace( '/\s{2,}/', ' ', $html );
		
		// Remove quebras de linha restantes para deixar tudo em uma linha
		$html = str_replace( array( "\r\n", "\r", "\n" ), '', $html );
		
		// Remove espaços no início e fim
		$html = trim( $html );
		
		return $html;
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
}