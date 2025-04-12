<?php
session_start();
require __DIR__ . '/connector_database/connector.php';

// Verificação reforçada de sessão
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Validação dos dados recebidos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id'], $_POST['texto'])) {
    header("Location: main.php?comentario=erro&motivo=dados_invalidos");
    exit;
}

$post_id = (int)$_POST['post_id'];
$usuario_id = (int)$_SESSION['usuario_id'];
$texto = trim($_POST['texto']);

// Validação do conteúdo
if (empty($texto) || strlen($texto) > 500) {
    header("Location: main.php?post_id=" . $post_id . "&comentario=erro&motivo=texto_invalido");
    exit;
}

try {
    // Proteção contra XSS
    $texto_limpo = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    
    $stmt = $conn->prepare("INSERT INTO comentarios (post_id, usuario_id, texto) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $usuario_id, $texto_limpo);
    
    if ($stmt->execute()) {
        header("Location: main.php?post_id=" . $post_id . "#comentarios-post-" . $post_id . "&comentario=sucesso&ts=" . time());
    } else {
        error_log("Erro na execução: " . $stmt->error);
        header("Location: main.php?post_id=" . $post_id . "&comentario=erro&motivo=erro_banco");
    }
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Erro no comentário: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    header("Location: main.php?post_id=" . $post_id . "&comentario=erro&motivo=excecao");
    exit;
}