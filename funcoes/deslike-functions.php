<?php
session_start();
require_once __DIR__ . '/../connector_database/connector.php';

function validar_usuario(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['error'] = 'Você precisa estar logado para realizar esta ação';
        header("Location: login.php");
        exit;
    }
}
/**
 * Função de veficar de deslike
 * @param mysqli conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function verificar_deslike_existente(mysqli $conn, int $usuario_id, int $post_id): void
{
    $stmt = $conn->prepare("SELECT id FROM descurtidas WHERE usuario_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $usuario_id, $post_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Você já descurtiu este post");
    }
}
/**
 * Função de remover curtidas existente.
 * @param mysqli conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function remover_curtida_existente(mysqli $conn, int $usuario_id, int $post_id): void
{
    $stmt = $conn->prepare("DELETE FROM curtidas WHERE usuario_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $usuario_id, $post_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao remover curtida: " . $stmt->error);
    }
}

/**
 * Função de da deslike
 * @param mysqli conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function registrar_descurtida(mysqli $conn, int $usuario_id, int $post_id): void
{
    $stmt = $conn->prepare("INSERT INTO descurtidas (usuario_id, post_id, data_descurtida) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $usuario_id, $post_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao registrar descurtida: " . $stmt->error);
    }
}
/**
 * Função processar seus deslikes.
 * @param mysqli $conn.
 */
function processar_descurtida(mysqli $conn): void
{
    validar_usuario();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id']) || !ctype_digit($_POST['post_id'])) {
        throw new Exception("Requisição inválida");
    }

    $usuario_id = (int)$_SESSION['usuario_id'];
    $post_id = (int)$_POST['post_id'];

    verificar_deslike_existente($conn, $usuario_id, $post_id);

    // Remove like se existir
    if (curtida_existe($conn, $usuario_id, $post_id)) {
        remover_curtida_existente($conn, $usuario_id, $post_id);
    }

    registrar_descurtida($conn, $usuario_id, $post_id);

    $_SESSION['success'] = "Descurtida registrada com sucesso!";
}

/**
 * Função auxiliar
 * 
 * @param mysqli conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
 
function curtida_existe(mysqli $conn, int $usuario_id, int $post_id): bool
{
    $stmt = $conn->prepare("SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $usuario_id, $post_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Execução principal
try {
    processar_descurtida($conn);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
} finally {
    header("Location: ../main.php");
    exit;
}
