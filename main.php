<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/connector_database/connector.php';
require __DIR__ . '/funcoes/principal-funcao.php'; // Corrigido caminho

try {
    // Obter conexão com o banco
    
    // Validar autenticação
    validate_user_authentication();
    
    // Dados do usuário
    $usuario_id = $_SESSION["usuario_id"];
    $userData = fetch_user_data($conn, $usuario_id);
    
    // Posts e comentários
    $posts = fetch_all_posts($conn);
    $comentariosPorPost = [];
    
    foreach ($posts as $post) {
        $comentariosPorPost[$post['id']] = fetch_comments_for_post($conn, $post['id']);
    }
    
    // Fechar conexão
    $conn->close();
    
    // Exibir template
    include __DIR__ . '/visualizar-html/main_template.php';

} catch (RuntimeException $e) {
    error_log("ERRO: " . $e->getMessage());
    include __DIR__ . '/visualizar-html/erro.php';
    exit;
}