<?php
session_start();
header('Content-Type: application/json'); // Adiciona cabeçalho JSON

include("../connector_database/connector.php");

try {
    // Verifica autenticação e parâmetros
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Usuário não autenticado");
    }
    
    if (!isset($_GET['user']) || !ctype_digit($_GET['user'])) {
        throw new Exception("Parâmetro inválido");
    }

    $my_id = $_SESSION['usuario_id'];
    $other_id = (int)$_GET['user'];

    // Corrigindo a consulta usando PDO corretamente
    $stmt = $pdo->prepare("SELECT 
            mensagem AS msg, 
            remetente_id, 
            data_envio 
        FROM mensagens 
        WHERE 
            (remetente_id = :my_id AND destinatario_id = :other_id) OR 
            (remetente_id = :other_id AND destinatario_id = :my_id) 
        ORDER BY data_envio ASC");

    // Executa com parâmetros nomeados
    $stmt->execute([
        ':my_id' => $my_id,
        ':other_id' => $other_id
    ]);

    // Corrigindo o fetch para PDO
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construindo resposta estruturada
    $response = [
        'success' => true,
        'html' => ''
    ];

    foreach ($messages as $msg) {
        $class = $msg['remetente_id'] == $my_id ? 'sent' : 'received';
        $response['html'] .= "<div class='chat-message $class'>" 
                            . htmlspecialchars($msg['msg']) 
                            . "</div>";
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>