<?php
/**
 * Template Name: Turmas e Estudantes
 * Description: Template para exibir turmas e estudantes agrupados.
 */

get_header(); // Inclui o cabeçalho do site
?>

<div class="turmas-container">
    <?php
    // Query para buscar todas as turmas
    $args = array(
        'post_type'      => 'turma',  // Nome do CPT
        'posts_per_page' => -1,       // Pega todos os itens
        'orderby'        => 'title',  // Ordena pelo título (nome da turma)
        'order'          => 'ASC'     // Ordem alfabética crescente
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $turma_id = get_the_ID();
            $turma_title = get_the_title();
            ?>
            <div class="turma">
                <h2><?php echo esc_html($turma_title); ?></h2>
                <div class="estudantes-lista">
                    <?php
                    // Busca os estudantes associados à turma atual usando Pods
                    $params = array(
                        'where' => 'turma.id IN (' . $turma_id . ')',
                        'limit' => -1  // Retorna todos os estudantes
                    );
                    $pods = pods('estudante', $params);

                    if ($pods->total() > 0) {
                        while ($pods->fetch()) {
                            $estudante_id = $pods->display('post_id');
                            $estudante_nome = $pods->display('post_title');
                            $estudante_thumbnail = $pods->display('post_thumbnail');
                            ?>
                            <div class="estudante">
                                <?php if ($estudante_thumbnail) : ?>
                                    <div class="estudante-thumbnail">
                                        <?php echo $estudante_thumbnail; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="estudante-nome">
                                    <?php echo esc_html($estudante_nome); ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p>Nenhum estudante encontrado nesta turma.</p>';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p>Nenhuma turma encontrada.</p>';
    }

    // Reseta os dados do WP_Query
    wp_reset_postdata();
    ?>
</div>

<?php
get_footer(); // Inclui o rodapé do site
?>