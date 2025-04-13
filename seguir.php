<?php
session_start();
require __DIR__ . '/connector_database/connector.php';

// Verifica autenticação
if (!isset($_SESSION["usuario_id"])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acesso não autorizado");
}

// Verifica método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Método inválido");
}

// Valida dados
$seguidor_id = $_SESSION["usuario_id"];
$seguido_id = filter_input(INPUT_POST, 'seguido_id', FILTER_VALIDATE_INT);
$acao = $_POST["acao"] ?? '';

// Validação rigorosa
if (!$seguido_id || $seguido_id < 1 || $seguidor_id == $seguido_id) {
    header("HTTP/1.1 400 Bad Request");
    exit("Dados inválidos");
}

try {
    // Verifica se usuário alvo existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $seguido_id);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        throw new Exception("Usuário alvo não existe");
    }

    // Executa a ação
    if ($acao === 'Seguir') {//lembrese não mude o value do html que esta fazendo esta função
        // Verifica se já segue
        $check = $conn->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $check->bind_param("ii", $seguidor_id, $seguido_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Você já segue este usuário");
        }

        $stmt = $conn->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
    } elseif ($acao === 'Deixar de Seguir') {
        $stmt = $conn->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    } else {
        header("HTTP/1.1 400 Bad Request");
        exit("Ação inválida");
    }

    $stmt->bind_param("ii", $seguidor_id, $seguido_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro na operação: " . $stmt->error);
    }

    // Verifica se houve alterações (especialmente importante para DELETE)
    if ($acao === 'Deixar de Seguir' && $stmt->affected_rows === 0) {
        throw new Exception("Você não estava seguindo este usuário");
    }

    // Redirecionamento seguro
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php'; // Fallback seguro
    $separador = (parse_url($referer, PHP_URL_QUERY) === null) ? '?' : '&';
    header("Location: $referer{$separador}status=success");
    exit;

} catch (Exception $e) {
    // Log detalhado
    error_log("ERRO seguir.php: " . $e->getMessage() . " - Usuário: {$_SESSION['usuario_id']}");
    
    // Redirecionamento com mensagem de erro codificada
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $separador = (parse_url($referer, PHP_URL_QUERY) === null) ? '?' : '&';
    header("Location: $referer{$separador}status=error&msg=" . urlencode($e->getMessage()));
    exit;
}