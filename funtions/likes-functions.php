<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
 * Função verficar post onde sera curtido.
 * @param sql $conn,
 * @param int $post_id.
 */
function verificar_post(mysqli $conn, int $post_id): void
{
    try {
        $check_post = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $check_post->bind_param("i", $post_id);
        $check_post->execute();
        if ($check_post->get_result()->num_rows === 0) {
            throw new Exception("Post não encontrado");
        }
    } catch (Exception $e) {
        $e->getMessage();
    }
}
/**
 * Função verficar se usuario ja curtiu o post
 * @param sql $conn,
 * @param int $usuario_id,
 * @param int $post_id
 */
function curtida_existe(mysqli $conn, int $usuario_id, int $post_id): bool
{
    $check = $conn->prepare("SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?");
    $check->bind_param("ii", $usuario_id, $post_id);
    $check->execute();
    return $check->get_result()->num_rows > 0;
}
/**
 * Função verficar se usuario ja deu deslike naquele post.
 * @param sql $conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function descurtida_existe(mysqli $conn, int $usuario_id, int $post_id): bool
{
    $check = $conn->prepare("SELECT id FROM descurtidas WHERE usuario_id = ? AND post_id = ?");
    $check->bind_param("ii", $usuario_id, $post_id);
    $check->execute();
    return $check->get_result()->num_rows > 0;
}
/**
 * Função remover deslike dentro do banco.
 * @param sql $conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function remover_descurtida(mysqli $conn, int $usuario_id, int $post_id): void
{
    $stmt = $conn->prepare("DELETE FROM descurtidas WHERE usuario_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $usuario_id, $post_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao remover descurtida: " . $stmt->error);
    }
}
/**
 * Função registrar curtidas no banco de dados.
 * @param sql $conn,
 * @param int $usuario_id,
 * @param int $post_id.
 */
function registrar_curtida(mysqli $conn, int $usuario_id, int $post_id): void
{
    $conn->begin_transaction();
    try {
        // Remover descurtida se existir
        if (descurtida_existe($conn, $usuario_id, $post_id)) {
            remover_descurtida($conn, $usuario_id, $post_id);
        }

        // Registrar nova curtida
        $stmt = $conn->prepare("INSERT INTO curtidas (usuario_id, post_id, data_curtida) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $usuario_id, $post_id);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao curtir: " . $stmt->error);
        }

        $conn->commit();
        $_SESSION['success'] = "Curtida registrada com sucesso!";
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
/**
 * Função processamneto final
 * @param sql $conn.
 */
function processar_curtida(mysqli $conn): void {
    validar_usuario();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id']) || !ctype_digit($_POST['post_id'])) {
        throw new Exception("Requisição inválida");
    }

    $usuario_id = (int)$_SESSION['usuario_id'];
    $post_id = (int)$_POST['post_id'];

    verificar_post($conn, $post_id);

    if (curtida_existe($conn, $usuario_id, $post_id)) {
        throw new Exception("Você já curtiu este post");
    }

    registrar_curtida($conn, $usuario_id, $post_id);
}

// Execução principal
try {
    processar_curtida($conn);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../main.php");
exit;
