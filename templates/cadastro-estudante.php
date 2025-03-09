<?php
/*
Template Name: Cadastro de Estudante
*/

get_header();

// Buscar todas as turmas cadastradas no CPT 'turma'
$turmas = get_posts([
    'post_type'      => 'turma',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC'
]);


?>

<div class="container">
    <h2>Cadastro de Estudante</h2>


<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['turma']) && !empty($_FILES['foto']['name'])) {
    $nome_estudante = sanitize_text_field($_POST['nome']);
    $turma_id = intval($_POST['turma']); // ID da turma selecionada
    error_log("✅ Iniciando processamento do aluno.");

    // Verifica se a turma existe no CPT 'turma'
    $turma_post = get_post($turma_id);
    if (!$turma_post || $turma_post->post_type !== 'turma') {
        echo "<p style='color: red;'>Erro: Turma inválida.</p>";
    } else {
        // Criar o estudante no CPT 'estudante'
        $estudante_id = wp_insert_post([
            'post_title'  => $nome_estudante,
            'post_status' => 'publish',
            'post_type'   => 'estudante',
        ]);

        if (!is_wp_error($estudante_id)) {
            // Processar Upload da Imagem
            require_once ABSPATH . 'wp-admin/includes/file.php';
            error_log("✅ Iniciando processamento do upload da imagem.");
        
            // Verifica se o arquivo foi enviado corretamente
            if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'])) {
                error_log("✅ Arquivo de imagem recebido: " . print_r($_FILES['foto'], true));
        
                // Verifica o tamanho do arquivo (5MB = 5 * 1024 * 1024 bytes)
                $tamanho_maximo = 5 * 1024 * 1024; // 5MB
                if ($_FILES['foto']['size'] > $tamanho_maximo) {
                    error_log("⚠️ O arquivo excede o tamanho máximo permitido de 5MB.");
                    echo "<p style='color: red;'>⚠️ O arquivo excede o tamanho máximo permitido de 5MB.</p>";
                    return; // Interrompe o processamento
                }
        
                // Tenta fazer o upload da imagem
                $upload = wp_handle_upload($_FILES['foto'], ['test_form' => false]);
        
                // Verifica se o upload foi bem-sucedido
                if (!isset($upload['error']) && isset($upload['file'])) {
                    error_log("✅ Upload da imagem bem-sucedido. Caminho do arquivo: " . $upload['file']);
        
                    $file_path = $upload['file'];
                    $file_name = basename($file_path);
        
                    // Prepara os dados do anexo
                    $attachment = [
                        'post_mime_type' => $upload['type'],
                        'post_title'     => sanitize_file_name($file_name),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];
        
                    // Insere o anexo no banco de dados
                    $attachment_id = wp_insert_attachment($attachment, $file_path, $estudante_id);
                    if (!is_wp_error($attachment_id)) {
                        error_log("✅ Anexo criado com sucesso. ID do anexo: " . $attachment_id);
        
                        // Gera os metadados do anexo
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                        if (!empty($attachment_data)) {
                            error_log("✅ Metadados do anexo gerados com sucesso.");
        
                            // Atualiza os metadados do anexo
                            wp_update_attachment_metadata($attachment_id, $attachment_data);
                            error_log("✅ Metadados do anexo atualizados.");
        
                            // Define a imagem destacada para o estudante
                            set_post_thumbnail($estudante_id, $attachment_id);
                            error_log("✅ Imagem destacada definida para o estudante ID $estudante_id.");
                        } else {
                            error_log("⚠️ Erro ao gerar metadados do anexo.");
                        }
                    } else {
                        error_log("⚠️ Erro ao criar anexo: " . $attachment_id->get_error_message());
                    }
                } else {
                    error_log("⚠️ Erro no upload da imagem: " . $upload['error']);
                    echo "<p style='color: red;'>⚠️ Erro no upload da imagem: " . $upload['error'] . "</p>";
                }
            } else {
                error_log("⚠️ Nenhum arquivo de imagem foi enviado.");
                echo "<p style='color: red;'>⚠️ Nenhum arquivo de imagem foi enviado.</p>";
            }
        
            // Salvar relacionamento com a turma usando Pods
            pods('estudante', $estudante_id)->save(['turma' => $turma_id]);
            error_log("✅ Relacionamento com a turma salvo com sucesso.");
        
            echo "<p style='color: green;'>✅ Estudante cadastrado com sucesso!</p>";
        } else {
            error_log("⚠️ Erro ao cadastrar estudante: " . $estudante_id->get_error_message());
            echo "<p style='color: red;'>⚠️ Erro ao cadastrar estudante.</p>";
        }
    }
}
?>
    <form method="POST" enctype="multipart/form-data">
        <label for="nome">Nome do Estudante:</label>
        <input type="text" name="nome" id="nome" required>

        <fieldset>
            <legend>Turma:</legend>
            <?php if ($turmas) : ?>
                <div class="radio-group">
                    <?php foreach ($turmas as $turma) : ?>
                        <label>
                            <input type="radio" name="turma" value="<?php echo esc_attr($turma->ID); ?>" required>
                            <?php echo esc_html($turma->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>Nenhuma turma cadastrada.</p>
            <?php endif; ?>
        </fieldset>

        <label for="foto">Foto do Estudante:</label>
        <input type="file" name="foto" id="foto" accept="image/*" required>

        <button type="submit">Cadastrar Estudante</button>
    </form>
</div>

<?php get_footer(); ?>
