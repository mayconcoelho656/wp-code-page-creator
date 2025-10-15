<?php
/**
 * Template Name: WCPC Final Render
 *
 * Este template é usado para renderizar o HTML compilado das páginas criadas
 * com o WP Code Page Creator.
 */

// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pega o ID do post atual.
$post_id = get_the_ID();

// Pega o HTML compilado do post meta.
// A constante META_KEY_COMPILED não está disponível aqui, então usamos a string diretamente.
$compiled_html = get_post_meta( $post_id, 'wcpc_html_montado', true );

// Aplica sistema de injeção próprio via filtros, substituindo os placeholders.
$wcpc_head    = apply_filters( 'wcpc_head_content', '' );
$wcpc_footer  = apply_filters( 'wcpc_footer_content', '' );

$compiled_html = str_replace( '{{wcpc_head}}', $wcpc_head, $compiled_html );
$compiled_html = str_replace( '{{wcpc_footer}}', $wcpc_footer, $compiled_html );

// Processa os shortcodes no HTML compilado
$compiled_html = do_shortcode( $compiled_html );

// Exibe o HTML final após injeções.
echo $compiled_html;
