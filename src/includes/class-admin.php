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

	/**
	 * Inicializa os hooks da área de administração.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'load-post-new.php', [ $this, 'maybe_add_save_hook' ] );
		add_filter( 'use_block_editor_for_post', [ $this, 'force_classic_editor' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_code_meta_boxes' ] );
		add_action( 'save_post_page', [ $this, 'save_code_meta_boxes' ], 10, 2 );
	}

	/**
	 * Adiciona os meta boxes para os campos de código se for uma página de código.
	 */
	public function add_code_meta_boxes() {
		global $post;
		if ( ! $post || $post->post_type !== 'page' || ! get_post_meta( $post->ID, '_wcpc_is_code_page', true ) ) {
			return;
		}

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
	 * Salva os dados dos meta boxes.
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

		$meta_keys = [ self::META_KEY_HTML, self::META_KEY_CSS, self::META_KEY_JS ];

		foreach ( $meta_keys as $meta_key ) {
			if ( isset( $_POST[ $meta_key ] ) ) {
				update_post_meta( $post_id, $meta_key, wp_unslash( $_POST[ $meta_key ] ) );
			}
		}
	}


	/**
	 * Força o uso do Editor Clássico se a página tiver a nossa flag.
	 *
	 * @param bool    $use_block_editor Se o editor de blocos deve ser usado.
	 * @param WP_Post $post             O objeto do post.
	 * @return bool
	 */
	public function force_classic_editor( $use_block_editor, $post ) {
		if ( $post->post_type === 'page' && get_post_meta( $post->ID, '_wcpc_is_code_page', true ) ) {
			return false;
		}
		return $use_block_editor;
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
}