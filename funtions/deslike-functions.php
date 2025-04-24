<?php
session_start();
require_once __DIR__ . '/../connector_database/connector.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Você precisa estar logado para realizar esta ação";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação do post_id
        if (!isset($_POST['post_id']) || !ctype_digit($_POST['post_id'])) {
            throw new Exception("ID do post inválido");
        }

        $post_id = (int)$_POST['post_id'];
        $usuario_id = (int)$_SESSION['usuario_id'];

        // Verificar se já descurtiu
        $checkDeslike = $conn->prepare("SELECT id FROM descurtidas WHERE usuario_id = ? AND post_id = ?");
        $checkDeslike->bind_param("ii", $usuario_id, $post_id);
        $checkDeslike->execute();
        
        if ($checkDeslike->get_result()->num_rows > 0) {
            throw new Exception("Você já descurtiu este post");
        }

        // Verificar se já curtiu para remover like
        $checkCurtida = $conn->prepare("SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?");
        $checkCurtida->bind_param("ii", $usuario_id, $post_id);
        $checkCurtida->execute();
        
        if ($checkCurtida->get_result()->num_rows > 0) {
            $removeCurtida = $conn->prepare("DELETE FROM curtidas WHERE usuario_id = ? AND post_id = ?");
            $removeCurtida->bind_param("ii", $usuario_id, $post_id);
            
            if (!$removeCurtida->execute()) {
                throw new Exception("Erro ao remover curtida");
            }
        }

        // Inserir descurtida
        $inserirDescurtida = $conn->prepare("INSERT INTO descurtidas (usuario_id, post_id, data_descurtida) VALUES (?, ?, NOW())");
        $inserirDescurtida->bind_param("ii", $usuario_id, $post_id);
        
        if ($inserirDescurtida->execute()) {
            $_SESSION['success'] = "Descurtida registrada com sucesso!";
        } else {
            throw new Exception("Erro ao registrar descurtida");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: ../main.php");
    exit;
}
?>