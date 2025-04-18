<?php
session_start();
include("../connector_database/connector.php");
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido.";
    header("Location: chat.php");
    exit;
}
//aqui e onde fica todos os dados de usuario e o destinatario
$remetente_id = $_SESSION['usuario_id'];
$destinatario_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$mensagem = trim($_POST['msg'] ?? '');

//se for menor do que 1 tem algum erro pois são ids de usuarios
if ($destinatario_id < 1 || $remetente_id < 1) {
    $_SESSION['erro'] = "IDs inválidos.";
    header("Location: chat.php");
    exit;
}

//se a mensagem nãp tiver nehum digito esta vazia e não pode 
if (empty($mensagem)) {
    $_SESSION['erro'] = "A mensagem não pode estar vazia.";
    header("Location: chat.php?user=" . $destinatario_id);
    exit;
}

//aqui e onde valida se a pessoa esta seguido nos e nos seguido ela
try {
    // Verificar se o remetente segue o destinatário
    $stmt = $conn->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->bind_param("ii", $remetente_id, $destinatario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['erro'] = "Você precisa seguir o usuário para enviar mensagens.";
        header("Location: /chat.php?user=" . urlencode($destinatario_id));
        exit;
    }
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao verificar relação: " . $e->getMessage();
    header("Location: chat.php?user=" . $destinatario_id);
    exit;
}

//se la encima do nosso codigo ocorre tudo certo teremos a inserção no nosso banco 
try {
    $stmt = $conn->prepare("
        INSERT INTO mensagens 
        (remetente_id, destinatario_id, mensagem, data_envio) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $remetente_id, $destinatario_id, $mensagem);
    $stmt->execute();

    // Redirecionar de volta ao chat após sucesso
    header("Location: /projeto_ed_feito/chat.php?user=" . $destinatario_id);
    exit;
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao enviar mensagem: " . $e->getMessage();
    header("Location: chat.php?user=" . $destinatario_id);
    exit;
}
?>