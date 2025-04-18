<?php
session_start();
header('Content-Type: application/json');

include '../connector_database/connector.php';

try {
    // Verificar autenticação
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Acesso não autorizado");
    }

    // Validar parâmetro
    $my_id = $_SESSION['usuario_id'];
    $other_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

    if (!$other_id || $other_id < 1) {
        throw new Exception("ID de conversa inválido");
    }

    // Buscar mensagens
    $stmt = $conn->prepare("
        SELECT 
            m.mensagem,
            m.remetente_id,
            DATE_FORMAT(m.data_envio, '%d/%m/%Y %H:%i') as data_envio,
            u.avatar
        FROM mensagens m
        JOIN users u ON m.remetente_id = u.id
        WHERE 
            (m.remetente_id = ? AND m.destinatario_id = ?)
            OR 
            (m.remetente_id = ? AND m.destinatario_id = ?)
        ORDER BY m.data_envio ASC
    ");

    $stmt->bind_param("iiii", $my_id, $other_id, $other_id, $my_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Gerar HTML
    $html = '';
    while ($row = $result->fetch_assoc()) {
        $class = ($row['remetente_id'] == $my_id) ? 'sent' : 'destinatario';
        $html .= '
        <div class="chat-message ' . $class . '">
            <div class="msg-text">' . htmlspecialchars($row['mensagem']) . '</div>
            <div class="msg-time">' . $row['data_envio'] . '</div>
        </div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Fechar conexão
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?>