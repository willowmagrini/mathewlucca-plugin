<?php
/**
 * Plugin Name: MathewLucca
 * Plugin URI:  https://seudominio.com/mathewlucca
 * Description: Este é um plugin para a escola  Mathew Lucca.
 * Version:     1.0.0
 * Author:      willow
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mathewlucca
 */

// Impede o acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once plugin_dir_path(__FILE__) . 'cli.php';
}


// Função de inicialização do plugin
function mathewlucca_init() {
    // Registrar o rewrite rule para /lista-por-turma
    add_rewrite_rule( '^lista-por-turma/?$', 'index.php?lista_por_turma=1', 'top' );

    // Adicionar o filtro para consultar a página personalizada
    add_filter( 'query_vars', 'mathewlucca_query_vars' );

    // Adicionar a função que renderiza a página
    add_action( 'template_redirect', 'mathewlucca_lista_por_turma_page' );
}

// Registrar a query var para verificar se estamos na página /lista-por-turma
function mathewlucca_query_vars( $query_vars ) {
    $query_vars[] = 'lista_por_turma';
    return $query_vars;
}

// Renderizar a página /lista-por-turma
function mathewlucca_lista_por_turma_page() {
    if ( get_query_var( 'lista_por_turma' ) ) {
        // Definir o template que será carregado
        include( plugin_dir_path( __FILE__ ) . 'templates/lista-por-turma.php' );
        exit;
    }
}
function carregar_css_turmas_estudantes() {
    // if (is_page('turmas-estudantes')) { // Substitua pelo slug ou ID da sua página
    // }
    wp_enqueue_style(
        'mathewlucca-plugin-css',
        plugin_dir_url(__FILE__) . 'assets/mathewlucca-plugin.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/mathewlucca-plugin.css')
    );
}
add_action('wp_enqueue_scripts', 'carregar_css_turmas_estudantes');
// Hook para inicializar o plugin
function restringir_acesso_apenas_para_editores() {
    // Verifica se o usuário está logado e se NÃO é um editor ou superior
    if (is_user_logged_in() && !current_user_can('edit_others_posts')) {
        // Redireciona usuários não autorizados para a página inicial
        wp_redirect(home_url());
        exit;
    }

    // Se o usuário não estiver logado, redireciona para a página de login
    if (!is_user_logged_in()) {
        auth_redirect();
    }
}
add_action('template_redirect', 'restringir_acesso_apenas_para_editores');


add_action( 'init', 'mathewlucca_init' );