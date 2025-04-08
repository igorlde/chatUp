<?php
session_start();
require __DIR__ . '/connector_database/connector.php';

// Verificar autenticação e permissões
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar se o ID do post foi recebido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['erro'] = "Post inválido";
    header("Location: main.php");
    exit;
}

$post_id = (int)$_GET['id'];

try {
    $conn->begin_transaction();

    // 1. Buscar informações do post
    $stmt = $conn->prepare("
        SELECT p.imagem_capa, pi.caminho_arquivo 
        FROM posts p
        LEFT JOIN post_imagens pi ON p.id = pi.post_id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['imagem_capa'])) {
            $arquivos[] = __DIR__ . '/' . $row['imagem_capa'];
        }
        if (!empty($row['caminho_arquivo'])) {
            $arquivos[] = __DIR__ . '/' . $row['caminho_arquivo'];
        }
    }

    // 2. Excluir registros do banco de dados
    // Excluir comentários primeiro
    $stmt = $conn->prepare("DELETE FROM comentarios WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Excluir relacionamento de tags
    $stmt = $conn->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Excluir imagens do post
    $stmt = $conn->prepare("DELETE FROM post_imagens WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Finalmente excluir o post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    $conn->commit();

    // 3. Excluir arquivos físicos
    foreach ($arquivos as $caminho) {
        if (file_exists($caminho)) {
            unlink($caminho);
        }
    }

    $_SESSION['sucesso'] = "Post excluído com sucesso!";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['erro'] = "Erro ao excluir post: " . $e->getMessage();
}

header("Location: main.php");
exit;
?>