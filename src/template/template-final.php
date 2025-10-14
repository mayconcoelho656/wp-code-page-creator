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

// Exibe o HTML.
// Não usamos a função the_content() ou qualquer outra função de template do WordPress.
// Apenas exibimos o HTML bruto que foi compilado.
echo $compiled_html;
