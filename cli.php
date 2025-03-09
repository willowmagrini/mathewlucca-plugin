<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Impede acesso direto
}

// Garante que o WP-CLI está rodando
if ( defined( 'WP_CLI' ) && WP_CLI ) {

    // Função para criar 100 estudantes e atribuir a turmas
    function criar_estudantes_e_atribuir_turmas() {
        $total_estudantes = 100;
        $turmas = range(21, 45);

        for ($i = 1; $i <= $total_estudantes; $i++) {
            $estudante_id = wp_insert_post([
                'post_title'   => "Estudante $i",
                'post_status'  => 'publish',
                'post_type'    => 'estudante',
            ]);

            if (!is_wp_error($estudante_id)) {
                // Escolhe uma turma aleatória
                $turma_id = $turmas[array_rand($turmas)];

                // Salva a relação no Pods
                pods('estudante', $estudante_id)->save('turma', $turma_id);

                WP_CLI::success("✅ Estudante $i criado e atribuído à turma ID $turma_id.");
            } else {
                WP_CLI::warning("⚠️ Erro ao criar estudante $i.");
            }
        }

        WP_CLI::success('🎉 100 estudantes foram criados e atribuídos às turmas.');
    }
    function reatribuir_estudantes_para_turmas() {
        $turmas = range(21, 45); // IDs das turmas possíveis
    
        // Busca os 100 estudantes já criados
        $args = array(
            'post_type'      => 'estudante',
            'posts_per_page' => 100,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );
    
        $query = new WP_Query($args);
    
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $estudante_id = get_the_ID();
    
                // Escolhe uma turma aleatória
                $turma_id = $turmas[array_rand($turmas)];
    
                // Atualiza a relação no Pods
                pods('estudante', $estudante_id)->save('turma', $turma_id);
    
                WP_CLI::success("✅ Estudante ID $estudante_id foi atribuído à turma ID $turma_id.");
            }
    
            // Reseta os dados do WP_Query
            wp_reset_postdata();
    
            WP_CLI::success('🎉 Todos os 100 estudantes foram reatribuídos às turmas.');
        } else {
            WP_CLI::warning('⚠️ Nenhum estudante encontrado.');
        }
    }
    function definir_imagem_destacada_para_estudantes() {
        $imagem_id = 147; // ID da imagem genérica
    
        // Busca todos os estudantes
        $args = array(
            'post_type'      => 'estudante',
            'posts_per_page' => -1, // Pega todos os estudantes
            'fields'         => 'ids', // Retorna apenas os IDs para otimizar a consulta
        );
    
        $estudantes = get_posts($args);
    
        if (!empty($estudantes)) {
            foreach ($estudantes as $estudante_id) {
                // Define a imagem destacada
                set_post_thumbnail($estudante_id, $imagem_id);
                WP_CLI::success("✅ Imagem destacada definida para o estudante ID $estudante_id.");
            }
    
            WP_CLI::success('🎉 Imagem destacada atribuída a todos os estudantes.');
        } else {
            WP_CLI::warning('⚠️ Nenhum estudante encontrado.');
        }
    }
    
    
    // Registra o comando no WP-CLI
    WP_CLI::add_command('mathewlucca criar-estudantes', 'criar_estudantes_e_atribuir_turmas');
    WP_CLI::add_command('mathewlucca reatribuir-estudantes', 'reatribuir_estudantes_para_turmas');
    WP_CLI::add_command('mathewlucca imagem-estudantes', 'definir_imagem_destacada_para_estudantes');
}
