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
    echo '<div class="turmas">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<h2>' . get_the_title() . '</h2>';
        echo '<div id="turma-'.get_the_id().'"class="cada-turma">';
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
                echo '<div class="cada-aluno" id="aluno-'.$pods->display( 'post_id' ).'">'.$pods->display( 'post_thumbnail' ).'<br>';
                echo ''.$pods->display( 'post_title' ).'</div>';
            }
        }
        echo "</div>";
    }
    echo '</div>';
} else {
    echo 'Nenhuma turma encontrada.';
}

// Reseta os dados do WP_Query
wp_reset_postdata();


get_footer();  // Inclui o rodapé do site