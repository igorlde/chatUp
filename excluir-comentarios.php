<?php
session_start();
require __DIR__ . '/connector_database/connector.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Validação reforçada dos parâmetros
if (!isset($_GET['id'], $_GET['post_id']) || 
    !filter_var($_GET['id'], FILTER_VALIDATE_INT) || 
    !filter_var($_GET['post_id'], FILTER_VALIDATE_INT)) {
    header("Location: main.php?erro=parametros_invalidos");
    exit;
}

$comment_id = (int)$_GET['id'];
$post_id = (int)$_GET['post_id'];
$usuario_id = (int)$_SESSION['usuario_id'];

//tem como o dono exluir outros comentarios sem ser o do dele claro se tiver dentro de seu post.
try {
    // Query corrigida com verificação de propriedade
    $sql = "DELETE FROM comentarios 
            WHERE id = ? 
            AND post_id = ? 
            AND (usuario_id = ? 
                OR EXISTS (
                    SELECT 1 
                    FROM posts 
                    WHERE id = ? 
                   AND usuario_id = ?
                ))";
                 //
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $comment_id, $post_id, $usuario_id, $post_id, $usuario_id);// era so acrecentar mais um i ta bom..
    
    if (!$stmt->execute()) {
        throw new Exception("Erro na execução: " . $stmt->error);
    }

    // Redirecionamento com tratamento de cache
    header("Location: main.php?post_id=" . $post_id . "#post-" . $post_id . "&ts=" . time());

} catch (Exception $e) {
    error_log("ERRO EXCLUSÃO: " . $e->getMessage() . " - Usuário: $usuario_id");
    header("Location: main.php?erro=erro_exclusao&post_id=" . $post_id);
    exit;
}