<?php
if (!current_user_can('manage_options')) {
    wp_die('Você não tem permissão para acessar esta página.');
}

// Carrega o transiente
$transient_exists = get_transient('cadastro_massa_json');
$total_alunos = $transient_exists['total_alunos'] ?? 0;
$processados = $transient_exists['processados'] ?? 0;
$progresso_inicial = $total_alunos > 0 ? ($processados / $total_alunos) * 100 : 0;
// Se o arquivo foi enviado, processa o upload e armazena no transiente
if (isset($_FILES['json_file'])) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $uploaded = media_handle_upload('json_file', 0);
    if (is_wp_error($uploaded)) {
        echo '<div class="notice notice-error"><p>Erro no upload: ' . $uploaded->get_error_message() . '</p></div>';
    } else {
        $file_url = wp_get_attachment_url($uploaded);
        $json_content = file_get_contents($file_url);
        $dados = json_decode($json_content, true);

        if ($dados) {
            $total = array_sum(array_map('count', $dados)); // Conta total de estudantes
            $transient_data = [
                'total_alunos' => $total,
                'processados'  => 0,
                'dados'        => $dados
            ];
            set_transient('cadastro_massa_json', $transient_data, HOUR_IN_SECONDS); // Guarda o novo formato
            echo '<div class="notice notice-success"><p>Arquivo carregado com sucesso!</p></div>';
            $transient_exists = $transient_data;
        }
    }
}
?>

<div class="wrap">
    <h1>Cadastro em Massa de Estudantes</h1>

    <?php if (!$transient_exists): ?>
        <!-- Formulário de upload -->
        <form method="post" enctype="multipart/form-data">
            <p><label for="json_file">Selecione o arquivo JSON com os dados:</label></p>
            <input type="file" name="json_file" id="json_file" accept=".json" required>
            <br><br>
            <button type="submit" class="button button-primary">Enviar Arquivo</button>
        </form>
    <?php else: ?>
        <!-- Opções após o upload ou se processo interrompido -->
        <p><strong>Um processo de importação está em andamento ou interrompido.</strong></p>
        <button id="iniciar-importacao" class="button button-primary">Iniciar/Retomar Importação via AJAX</button>
        <br><br>

        <!-- Mostrar o restante do JSON ainda não processado -->
        <!-- <h2>Dados Restantes para Importação</h2>
        <pre style="background: #f7f7f7; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow: auto;"><?php echo esc_html(json_encode($transient_exists, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre> -->

        <!-- Botão para cancelar e apagar o processo atual (opcional) -->
        <form method="post" style="margin-top: 20px;">
            <input type="hidden" name="cancelar_importacao" value="1">
            <button type="submit" class="button button-secondary" style="background: red; color: white;">Cancelar e Apagar Processo Atual</button>
        </form>
        <!-- Estilos CSS do loader -->
        <style>
        .spinner {
            margin: 0 auto;
            border: 8px solid #f3f3f3; /* Cor cinza claro */
            border-top: 8px solid #3498db; /* Cor azul */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>

        <!-- Barra de progresso -->
        <h3>Progresso da Importação</h3>
        <!-- Loader (ícone giratório) -->
        <div id="loader" style="display: none; text-align: center; margin: 20px 0;">
            <div class="spinner"></div>
            <p>Processando estudantes, aguarde...</p>
        </div>
        <div id="progresso" style="background: #e0e0e0; width: 100%; height: 30px; border-radius: 5px; overflow: hidden;">
            <div id="barra-progresso" style="background: green; width: <?php echo $progresso_inicial; ?>%; height: 100%; transition: width 0.5s;"></div>
        </div>
        <p id="status-progresso">Processados <?php echo $processados; ?> de <?php echo $total_alunos; ?> estudantes.</p>

        <!-- Lista de alunos processados -->
        <h3>Estudantes Processados neste momento</h3>
        <ul id="log-processados" style="max-height: 300px; overflow: auto; border: 1px solid #ccc; padding: 10px;"></ul>

    <?php endif; ?>
</div>

<?php
// Se o botão de cancelar foi pressionado
if (isset($_POST['cancelar_importacao']) && $_POST['cancelar_importacao'] == '1') {
    delete_transient('cadastro_massa_json');
    echo '<div class="notice notice-success"><p>Processo de importação cancelado e dados removidos.</p></div>';
    // Redireciona para evitar reprocessamento ao atualizar a página
    echo '<script>window.location.href = "' . home_url('/cadastro-massa') . '";</script>';
    exit;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnIniciar = document.getElementById('iniciar-importacao');
    const barraProgresso = document.getElementById('barra-progresso');
    const statusProgresso = document.getElementById('status-progresso');
    const logProcessados = document.getElementById('log-processados');
    const loader = document.getElementById('loader');

    // Importando os valores do PHP
    let totalEstudantes = <?php echo $total_alunos; ?>;
    let processados = <?php echo $processados; ?>;

    function iniciarImportacao() {
        loader.style.display = 'block'; // Mostrar o loader

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=processar_cadastro_massa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                data = result.data;
                processados = data.processados_total; // Atualiza o número real processado do transiente

                // Atualizar barra de progresso com base nos dados reais
                let progresso = Math.min((processados / totalEstudantes) * 100, 100);
                barraProgresso.style.width = progresso + '%';
                statusProgresso.innerText = `Processados ${processados} de ${totalEstudantes} estudantes.`;

                // Mostrar os nomes dos estudantes processados
                data.estudantes.forEach(function(nome) {
                    const li = document.createElement('li');
                    li.innerText = nome;
                    logProcessados.appendChild(li);
                });

                // Continua o processo se ainda houver dados
                if (!data.finalizado) {
                    setTimeout(iniciarImportacao, 1000);
                } else {
                    statusProgresso.innerText = '✅ Cadastro concluído com sucesso!';
                    loader.style.display = 'none'; // Esconde o loader
                }
            } else {
                statusProgresso.innerText = '❌ Erro: ' + data.message;
                loader.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            statusProgresso.innerText = '❌ Erro ao processar. Verifique o console.';
            loader.style.display = 'none';
        });
    }

    if (btnIniciar) {
        btnIniciar.addEventListener('click', function () {
            iniciarImportacao(); // Começa o processo ao clicar
        });
    }
});

</script>
