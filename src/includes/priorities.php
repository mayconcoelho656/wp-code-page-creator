<?php
// Bloqueia acesso direto.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mapa fixo de prioridades por plugin (aplicado igualmente ao head e footer).
 * Ajuste as chaves conforme sua convenção.
 */
function wcpc_get_priority( $plugin_key ) {
    static $map = [
        'block-html'   => 10,
        'default' => 12,
        'maintenance' => 16,
        'button-contact' => 18,
        'video-pop-up' => 20,
        '404' => 30,
        // Adicione novos plugins aqui com a prioridade desejada
    ];

    return $map[ $plugin_key ] ?? 100;
}

/**
 * Registra conteúdo no head com prioridade fixa baseada no plugin_key.
 * $payload pode ser string HTML ou uma callback que retorna string.
 */
function wcpc_add_head( $plugin_key, $payload ) {
    $priority = wcpc_get_priority( $plugin_key );
    add_filter( 'wcpc_head_content', function( $content ) use ( $payload ) {
        $addition = is_callable( $payload ) ? (string) call_user_func( $payload ) : (string) $payload;
        return $content . $addition;
    }, $priority );
}

/**
 * Registra conteúdo no footer com prioridade fixa baseada no plugin_key.
 * $payload pode ser string HTML/JS ou uma callback que retorna string.
 */
function wcpc_add_footer( $plugin_key, $payload ) {
    $priority = wcpc_get_priority( $plugin_key );
    add_filter( 'wcpc_footer_content', function( $content ) use ( $payload ) {
        $addition = is_callable( $payload ) ? (string) call_user_func( $payload ) : (string) $payload;
        return $content . $addition;
    }, $priority );
}