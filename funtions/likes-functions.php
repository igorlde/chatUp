<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../connector_database/connector.php';

// Verificação de autenticação
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Faça login para curtir posts";
    header("Location: ../login.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação do post_id
        if (!isset($_POST['post_id']) || !ctype_digit($_POST['post_id'])) {
            throw new Exception("ID do post inválido");
        }

        $post_id = (int)$_POST['post_id'];

        // Verificar se post existe
        $check_post = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $check_post->bind_param("i", $post_id);
        $check_post->execute();
        
        if ($check_post->get_result()->num_rows === 0) {
            throw new Exception("Post não encontrado");
        }

        // Verificar se já curtiu
        $check_sql = $conn->prepare("SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?");
        $check_sql->bind_param("ii", $usuario_id, $post_id);
        $check_sql->execute();

        if ($check_sql->get_result()->num_rows > 0) {
            throw new Exception("Você já curtiu este post");
        }

        // Inserir nova curtida
        $insert_sql = $conn->prepare("INSERT INTO curtidas (usuario_id, post_id, data_curtida) VALUES (?, ?, NOW())");
        $insert_sql->bind_param("ii", $usuario_id, $post_id);

        //aqui verifica se foi exeutado e pega quantas linhas de execução se for maior que zero deleta os dislikes do proprio usuario.
        if ($insert_sql->execute() || $insert_sql->get_result()->num_rows > 0) {
            $_SESSION['success'] = "Curtida registrada!";
            $deleteD = $conn->prepare("DELETE FROM descurtidas WHERE usuario_id = ? AND post_id = ?");
            $deleteD->bind_param("ii", $usuario_id, $post_id);
            $deleteD->execute();
        } else {
            throw new Exception("Erro ao curtir: " . $conn->error);
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../main.php");
    exit;
}

// Se não for POST, redirecionar
header("Location: ../main.php");
exit;
?>