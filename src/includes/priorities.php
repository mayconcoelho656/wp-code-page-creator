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
        'admin-bar'    => 5,  // Admin-bar tem prioridade alta
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

/**
 * Inicializa a admin-bar do WordPress nas páginas WCPC.
 * Adiciona automaticamente os estilos e scripts necessários quando o usuário está logado.
 */
function wcpc_init_admin_bar() {
    // Verifica se o usuário está logado e se a admin-bar está habilitada
    if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
        return;
    }

    // Adiciona os estilos da admin-bar no head
    wcpc_add_head( 'admin-bar', function() {
        ob_start();
        
        // Carrega os estilos necessários da admin-bar
        wp_enqueue_style( 'admin-bar' );
        wp_print_styles( 'admin-bar' );
        
        // Adiciona CSS personalizado para ajustar o body quando a admin-bar está presente (minificado)
        echo '<style type="text/css">html{margin-top:32px!important;}*html body{margin-top:32px!important;}@media screen and (max-width:782px){html{margin-top:46px!important;}*html body{margin-top:46px!important;}}</style>';
        
        $content = ob_get_clean();
        // Minifica o conteúdo removendo quebras de linha e espaços extras
        return preg_replace('/\s+/', ' ', trim($content));
    });

    // Adiciona a admin-bar e scripts no footer
    wcpc_add_footer( 'admin-bar', function() {
        ob_start();
        
        // Renderiza a admin-bar
        wp_admin_bar_render();
        
        // Carrega os scripts necessários da admin-bar
        wp_enqueue_script( 'admin-bar' );
        wp_print_scripts( 'admin-bar' );
        
        $content = ob_get_clean();
        // Minifica o conteúdo removendo quebras de linha e espaços extras
        return preg_replace('/\s+/', ' ', trim($content));
    });
}

// Inicializa a admin-bar quando o WordPress estiver carregado
add_action( 'wp_loaded', 'wcpc_init_admin_bar' );