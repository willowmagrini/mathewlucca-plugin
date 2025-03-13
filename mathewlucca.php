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

    // Adicionar os filtros para consultar as páginas personalizadas
    add_filter( 'query_vars', 'mathewlucca_query_vars' );

    // Adicionar as funções que renderizam as páginas
    add_action( 'template_redirect', 'mathewlucca_lista_por_turma_page' );
    add_action( 'template_redirect', 'mathewlucca_cadastro_estudante_page' );
}

// Registrar query vars para verificar se estamos nas páginas personalizadas
function mathewlucca_query_vars( $query_vars ) {
    $query_vars[] = 'lista_por_turma';
    $query_vars[] = 'cadastro_estudante';
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

// Inicializar o plugin
add_action( 'init', 'mathewlucca_init' );

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

function converter_post_para_estudante_automaticamente($post_id, $post, $update) {
    // Verifica se é um novo post (não uma atualização) e se é do tipo 'post'
    if ($post->post_type === 'post') {
        // Verifica se o post não está no lixo
        if ($post->post_status == 'publish' ) {
            $post_title = $post->post_title;
            $categories = get_the_category($post_id);
            $term_list = wp_get_post_terms($post_id, 'category', array('fields' => 'names'));
            error_log("Categorias encontradas para o post ID $post_id: " . print_r($term_list, true));

            // Converte o post para o CPT estudante
            $estudante_id = wp_insert_post([
                'post_title'   => $post_title,
                'post_status'  => 'publish',
                'post_type'    => 'estudante',
            ]);

            if (!is_wp_error($estudante_id)) {
                // Se houver categorias, usa o nome da primeira categoria para buscar a turma
                if (!empty($categories)) {
                    $categoria_nome = $categories[0]->name;

                    // Busca a turma com o mesmo nome da categoria
                    $turma_query = new WP_Query([
                        'post_type'      => 'turma',
                        'posts_per_page' => 1,
                        'title'          => $categoria_nome,
                    ]);

                    if ($turma_query->have_posts()) {
                        $turma_query->the_post();
                        $turma_id = get_the_ID();

                        // Cria a relação entre o estudante e a turma usando Pods
                        pods('estudante', $estudante_id)->save('turma', $turma_id);

                        error_log("✅ Post ID $post_id convertido para estudante ID $estudante_id e atribuído à turma ID $turma_id.");
                    } else {
                        error_log("⚠️ Nenhuma turma encontrada com o nome da categoria: $categoria_nome.");
                    }

                    // Reseta a query da turma
                    wp_reset_postdata();
                } else {
                    error_log("⚠️ Post ID $post_id não tem categorias.");
                }

                // Copia a imagem destacada do post original para o estudante
                $imagem_destacada_id = get_post_thumbnail_id($post_id);
                if ($imagem_destacada_id) {
                    set_post_thumbnail($estudante_id, $imagem_destacada_id);
                    error_log("✅ Imagem destacada copiada para o estudante ID $estudante_id.");
                } else {
                    error_log("⚠️ Post ID $post_id não tem imagem destacada.");
                }

                // Define o post original como rascunho
                wp_update_post([
                    'ID'          => $post_id,
                    'post_status' => 'draft',
                ]);

                error_log("✅ Post ID $post_id definido como rascunho.");
            } else {
                error_log("⚠️ Erro ao converter post ID $post_id para estudante.");
            }
        }
    }
}
// add_action('publish_post', 'converter_post_para_estudante_automaticamente', 10, 3);
// add_action('post_updated', 'converter_post_para_estudante_automaticamente', 10, 3);
// Desativa a geração de thumbnails
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
