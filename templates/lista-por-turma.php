<?php
/**
 * Template para exibir a lista de Estudantes agrupados por Turma
 */

get_header();  // Inclui o cabeçalho do site



$args = array(
    'post_type'      => 'turma',  // Nome do CPT
    'posts_per_page' => -1,       // Pega todos os itens
    'orderby'        => 'title',  // Ordena pelo título (nome da turma)
    'order'          => 'ASC'     // Ordem alfabética crescente
);

$query = new WP_Query( $args );

if ( $query->have_posts() ) {
    echo '<ul>';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<li>' . get_the_title() . '</li>';
        echo '<ul>';
        $params = array(
            'where' => 'turma.id IN ('.get_the_id().')',
            'limit'   => -1  // Return all rows

        );
        //search in articles pod
        $pods = pods( 'estudante', $params );
        //loop through results
        if ( 0 < $pods->total() ) {
            while ( $pods->fetch() ) {
                // print_r($pods);
                echo '<li>'.$pods->display( 'post_title' ).'</li>';
            }
        }
        echo "</ul>";
    }
    echo '</ul>';
} else {
    echo 'Nenhuma turma encontrada.';
}

// Reseta os dados do WP_Query
wp_reset_postdata();


get_footer();  // Inclui o rodapé do site