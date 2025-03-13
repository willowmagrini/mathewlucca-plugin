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
        'post_type'      => 'turma',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
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
                    $params = array(
                        'where' => 'turma.id IN (' . $turma_id . ')',
                        'limit' => -1
                    );
                    $pods = pods('estudante', $params);

                    if ($pods->total() > 0) {
                        while ($pods->fetch()) {
                            $estudante_id = $pods->display('ID');
                            $estudante_nome = $pods->display('post_title');
                            $estudante_thumbnail = $pods->display('post_thumbnail');
                            ?>
                            <div id="estudante-<?php echo $estudante_id; ?>" class="estudante">
                                <?php if ($estudante_thumbnail) : ?>
                                    <div class="estudante-thumbnail">
                                        <?php echo $estudante_thumbnail; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="estudante-nome">
                                    <?php echo esc_html($estudante_nome); ?>
                                </div>

                                <?php if (is_user_logged_in() && (current_user_can('administrator') || current_user_can('editor'))) : ?>
                                    <button onclick="upload_foto(<?php echo $estudante_id; ?>)">Atualizar Imagem</button>
                                <?php endif; ?>
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

    wp_reset_postdata();
    ?>
</div>

<script>
function upload_foto(estudante_id) {
    // Criar input file invisível
    let input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = function (event) {
        let file = event.target.files[0];
        if (!file) return;
        console.log(estudante_id)
        // Exibir loader na div do estudante
        let estudanteDiv = document.getElementById('estudante-' + estudante_id);
        estudanteDiv.innerHTML += '<p id="loader-' + estudante_id + '">Atualizando imagem...</p>';

        // Preparar dados para envio
        let formData = new FormData();
        formData.append('action', 'upload_foto_estudante');
        formData.append('estudante_id', estudante_id);
        formData.append('imagem', file);
        formData.append('_ajax_nonce', '<?php echo wp_create_nonce('upload_foto_estudante'); ?>');

        // Fazer upload via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Buscar HTML atualizado do estudante
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=buscar_estudante_atualizado&estudante_id=' + estudante_id)
                .then(response => response.text())
                .then(html => {
                    estudanteDiv.outerHTML = html; // Substitui a div do estudante inteira
                });
            } else {
                alert('Erro ao atualizar imagem: ' + data.data);
                document.getElementById('loader-' + estudante_id).remove();
            }
        })
        .catch(err => {
            alert('Erro inesperado: ' + err);
            document.getElementById('loader-' + estudante_id).remove();
        });
    };

    // Clicar no input
    input.click();
}
</script>

<?php
get_footer(); // Inclui o rodapé do site
?>
