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

// Hook para inicializar o plugin
add_action( 'init', 'mathewlucca_init' );

Passo 3: Criar o Template para Listar os Estudantes

Crie uma pasta templates dentro da pasta do plugin e adicione o arquivo lista-por-turma.php. Este arquivo será o template responsável por exibir os Estudantes agrupados por Turma.
Criando a pasta e o arquivo de template

    Crie a pasta templates dentro da pasta do plugin: wp-content/plugins/mathewlucca/templates/.
    Dentro da pasta templates, crie o arquivo lista-por-turma.php.

templates/lista-por-turma.php

<?php
/**
 * Template para exibir a lista de Estudantes agrupados por Turma
 */

get_header();  // Inclui o cabeçalho do site

// Argumentos para obter os Estudantes (CPTs) agrupados por Turma
$args = array(
    'post_type' => 'estudante', // Seu CPT 'Estudante'
    'posts_per_page' => -1, // Exibe todos os Estudantes
    'tax_query' => array(
        array(
            'taxonomy' => 'turma',  // A taxonomia associada ao CPT 'Estudante'
            'field'    => 'slug',
            'terms'    => get_terms( array( 'taxonomy' => 'turma', 'fields' => 'slugs' ) ), // Todos os slugs das turmas
            'operator' => 'IN',
        ),
    ),
);

// Realiza a consulta para obter os Estudantes
$query = new WP_Query( $args );

// Exibe o título da página
echo '<h1>Lista de Estudantes por Turma</h1>';

// Verifica se há Estudantes para exibir
if ( $query->have_posts() ) {
    // Loop para exibir Estudantes
    $current_turma = '';
    while ( $query->have_posts() ) {
        $query->the_post();

        // Obtém a turma do Estudante
        $turmas = get_the_terms( get_the_ID(), 'turma' );

        if ( $turmas && ! is_wp_error( $turmas ) ) {
            // Mostra a turma se ainda não foi exibida
            foreach ( $turmas as $turma ) {
                if ( $current_turma !== $turma->name ) {
                    if ( $current_turma !== '' ) {
                        echo '</ul>'; // Fecha a lista anterior de Estudantes
                    }

                    $current_turma = $turma->name;
                    echo '<h2>Turma: ' . esc_html( $current_turma ) . '</h2>';
                    echo '<ul>'; // Abre a lista de Estudantes para essa turma
                }

                // Exibe o Estudante
                echo '<li>' . get_the_title() . '</li>';
            }
        }
    }

    echo '</ul>'; // Fecha a última lista de Estudantes
} else {
    echo '<p>Nenhum Estudante encontrado.</p>';
}

wp_reset_postdata();  // Restaura o post data

get_footer();  // Inclui o rodapé do site