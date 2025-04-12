<?php
session_start();
include("connector_database/connector.php");

// Verifica autenticação
if (!isset($_SESSION["usuario_id"])) {
    die("Acesso não autorizado");
}

// Verifica método POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Método inválido");
}
else{
// Valida dados
$seguidor_id = $_SESSION["usuario_id"];
$seguido_id = filter_input(INPUT_POST, 'seguido_id', FILTER_VALIDATE_INT);
$acao = $_POST["acao"] ?? '';

if (!$seguido_id || $seguidor_id === $seguido_id) {
    die("Dados inválidos");
}

try {
    // Executa a ação
    if ($acao === 'Seguir') {
        $stmt = $conn->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
    } elseif ($acao === 'Deixar de Seguir') {
        $stmt = $conn->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    } else {
        die("Ação inválida");
    }

    $stmt->bind_param("ii", $seguidor_id, $seguido_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro na operação: " . $stmt->error);
    }

    // Redireciona com sucesso
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=success");
    exit;

} catch (Exception $e) {
    // Log do erro e redirecionamento
    error_log("Erro seguir.php: " . $e->getMessage());
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=error");
    exit;
}
}