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
    // Registrar rewrite rules para as páginas personalizadas
    add_rewrite_rule( '^lista-por-turma/?$', 'index.php?lista_por_turma=1', 'top' );
    add_rewrite_rule( '^cadastro-estudante/?$', 'index.php?cadastro_estudante=1', 'top' );
    add_rewrite_rule( '^cadastro-massa/?$', 'index.php?cadastro_massa=1', 'top' );
    add_rewrite_rule( '^processa-cadastro-massa/?$', 'index.php?processa_cadastro_massa=1', 'top' );
    // Adicionar os filtros para consultar as páginas personalizadas
    add_filter( 'query_vars', 'mathewlucca_query_vars' );

    // Adicionar as funções que renderizam as páginas
    add_action( 'template_redirect', 'mathewlucca_lista_por_turma_page' );
    add_action( 'template_redirect', 'mathewlucca_cadastro_estudante_page' );
    add_action( 'template_redirect', 'mathewlucca_cadastro_massa_page' );
}

// Registrar query vars para verificar se estamos nas páginas personalizadas
function mathewlucca_query_vars( $query_vars ) {
    $query_vars[] = 'lista_por_turma';
    $query_vars[] = 'cadastro_estudante';
    $query_vars[] = 'cadastro_massa';
    $query_vars[] = 'processa_cadastro_massa';
    return $query_vars;
}

// Renderizar a página /lista-por-turma
function mathewlucca_lista_por_turma_page() {
    if ( get_query_var( 'lista_por_turma' ) ) {
        include( plugin_dir_path( __FILE__ ) . 'templates/lista-por-turma.php' );
        exit;
    }
}

// Renderizar a página /cadastro-estudante
function mathewlucca_cadastro_estudante_page() {
    if ( get_query_var( 'cadastro_estudante' ) ) {
        include( plugin_dir_path( __FILE__ ) . 'templates/cadastro-estudante.php' );
        exit;
    }
}

// Renderizar a página /cadastro-massa
function mathewlucca_cadastro_massa_page() {
    if ( get_query_var( 'cadastro_massa' ) ) {
        include( plugin_dir_path( __FILE__ ) . 'templates/cadastro-massa.php' );
        exit;
    }
}
// Inicializar o plugin
add_action( 'init', 'mathewlucca_init' );

function mathewlucca_processa_cadastro_massa_page() {
    if ( get_query_var( 'processa_cadastro_massa' ) ) {
        // Busca o transiente
        $dados = get_transient('cadastro_massa_json');

        // Se não houver dados, finaliza o processo
        if (!$dados) {
            echo '<p>Processo expirado ou não iniciado.</p>';
            exit;
        }

        // Lista as turmas ainda presentes no transiente
        $turmas = array_keys($dados);

        // Se não houver mais turmas, encerra o processo
        if (empty($turmas)) {
            delete_transient('cadastro_massa_json'); // Limpa o transiente após o fim
            echo '<p>✅ Cadastro concluído com sucesso!</p>';
            exit;
        }

        // Pega a primeira turma disponível
        $nome_turma = $turmas[0];
        $estudantes = $dados[$nome_turma];

        // Chama a função que vai processar até 4 estudantes e fazer as remoções
        processar_uma_turma($nome_turma, $estudantes);

        // Mensagem de feedback simples
        echo "<p>Processando estudantes da turma <strong>$nome_turma</strong>...</p>";
        echo '<p>Redirecionando para o próximo grupo de estudantes...</p>';

        // Redireciona automaticamente para continuar o processo
        echo '<script>setTimeout(function(){ window.location.href = "?processa_cadastro_massa=1"; }, 2000);</script>';

        exit;
    }
}

add_action( 'template_redirect', 'mathewlucca_processa_cadastro_massa_page' );


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
// add_action('template_redirect', 'restringir_acesso_apenas_para_editores');

add_filter('intermediate_image_sizes_advanced', 'desativar_thumbnails');

function desativar_thumbnails($sizes) {
    return []; // Retorna um array vazio para evitar a geração de miniaturas
}
add_filter('big_image_size_threshold', '__return_false');
add_action( 'init', 'mathewlucca_init' );

add_action('wp_ajax_upload_foto_estudante', function () {
    if (!current_user_can('administrator') && !current_user_can('editor')) {
        wp_send_json_error('Permissão negada.');
    }

    $estudante_id = intval($_POST['estudante_id']);
    if (empty($_FILES['imagem'])) {
        wp_send_json_error('Nenhuma imagem enviada.');
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = wp_handle_upload($_FILES['imagem'], array('test_form' => false));

    if (isset($upload['error'])) {
        wp_send_json_error($upload['error']);
    }

    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name($upload['file']),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $upload['file'], $estudante_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    set_post_thumbnail($estudante_id, $attach_id);
    wp_send_json_success('Imagem atualizada com sucesso.');
});

add_action('wp_ajax_buscar_estudante_atualizado', function () {
    $estudante_id = intval($_GET['estudante_id']);
    $post = get_post($estudante_id);

    if (!$post) {
        echo '<p>Estudante não encontrado.</p>';
        wp_die();
    }

    $nome = get_the_title($post);
    $thumb = get_the_post_thumbnail($post->ID, 'medium');

    ?>
    <div id="estudante-<?php echo $post->ID; ?>" class="estudante">
        <?php if ($thumb): ?>
            <div class="estudante-thumbnail"><?php echo $thumb; ?></div>
        <?php endif; ?>
        <div class="estudante-nome"><?php echo esc_html($nome); ?></div>
        <?php if (current_user_can('administrator') || current_user_can('editor')): ?>
            <button onclick="upload_foto(<?php echo $post->ID; ?>)">Atualizar Imagem</button>
        <?php endif; ?>
    </div>
    <?php
    wp_die(); // Encerra corretamente
});

add_filter('upload_mimes', function($mimes) {
    $mimes['json'] = 'application/json';
    return $mimes;
});

function processar_uma_turma() {
    $transient_data = get_transient('cadastro_massa_json');
    if (!$transient_data || empty($transient_data['dados'])) return [];

    $dados = $transient_data['dados'];
    $turmas = array_keys($dados);
    $nome_turma = $turmas[0];
    $estudantes = $dados[$nome_turma];
    $processados_nomes = [];
    $contador = 0;

    $turma = get_page_by_title($nome_turma, OBJECT, 'turma');
    if (!$turma) {
        $turma_id = wp_insert_post([
            'post_title'  => $nome_turma,
            'post_type'   => 'turma',
            'post_status' => 'publish'
        ]);
    } else {
        $turma_id = $turma->ID;
    }

    if (!$turma_id) return [];

    foreach ($estudantes as $key => $nome_estudante) {
        if ($contador >= 4) break;

        $estudante_id = wp_insert_post([
            'post_title'  => sanitize_text_field($nome_estudante),
            'post_type'   => 'estudante',
            'post_status' => 'publish'
        ]);

        if ($estudante_id && !is_wp_error($estudante_id)) {
            pods('estudante', $estudante_id)->save(['turma' => $turma_id]);
            $processados_nomes[] = $nome_estudante;
        }

        unset($dados[$nome_turma][$key]); // Remove estudante processado
        $contador++;
        $transient_data['processados']++; // Incrementa o contador global
    }

    if (empty($dados[$nome_turma])) unset($dados[$nome_turma]); // Remove turma vazia

    $transient_data['dados'] = $dados;
    set_transient('cadastro_massa_json', $transient_data, HOUR_IN_SECONDS); // Atualiza o transiente

    return $processados_nomes;
}


add_action('wp_ajax_processar_cadastro_massa', function () {
    $transient_data = get_transient('cadastro_massa_json');
    if (!$transient_data) {
        wp_send_json_error(['message' => 'Processo expirado ou não iniciado.']);
    }

    $estudantes_processados = processar_uma_turma();

    $finalizado = empty($transient_data['dados']);
    wp_send_json_success([
        'qtd_processados' => count($estudantes_processados),
        'processados_total' => $transient_data['processados'],
        'total' => $transient_data['total_alunos'],
        'estudantes' => $estudantes_processados,
        'finalizado' => $finalizado
    ]);
});
