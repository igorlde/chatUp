<?php
session_start();
header('Content-Type: application/json');
include '../connector_database/connector.php';

/**
 * Valida se o usuário está autenticado
 */
function validar_autenticacao(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Acesso não autorizado");
    }
}

/**
 * Obtém e valida o ID do destinatário da conversa
 */
function obter_id_destinatario(): int
{
    $other_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

    if (!$other_id || $other_id < 1) {
        throw new Exception("ID de conversa inválido");
    }

    return $other_id;
}

/**
 * Busca as mensagens no banco de dados
 * @param mysqli $conn,
 * @param int $rementente_id,
 * @param int $destinatario_id.
 */
function buscar_mensagens(mysqli $conn, int $remetente_id, int $destinatario_id): mysqli_result
{
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

    $stmt->bind_param("iiii", $remetente_id, $destinatario_id, $destinatario_id, $remetente_id);
    $stmt->execute();

    return $stmt->get_result();
}

/**
 * Gera o HTML das mensagens
 * @param mysqli_result $result,
 * @param int $my_id.
 */
function gerar_html_mensagens(mysqli_result $result, int $my_id): string
{
    $html = '';
    while ($row = $result->fetch_assoc()) {
        $class = ($row['remetente_id'] == $my_id) ? 'sent' : 'destinatario';
        $html .= '
        <div class="chat-message ' . $class . '">
            <div class="msg-text">' . htmlspecialchars($row['mensagem']) . '</div>
            <div class="msg-time">' . $row['data_envio'] . '</div>
        </div>';
    }
    return $html;
}

/**
 * Envia a resposta em formato JSON
 * @param bool $success,
 * @param string $html,
 * @param string $error.
 */
function enviar_resposta_json(bool $success, string $html = '', string $error = ''): void
{
    $response = ['success' => $success];

    if ($success) {
        $response['html'] = $html;
    } else {
        $response['error'] = $error;
    }

    echo json_encode($response);
}

/**
 * Fecha recursos do banco de dados
 * @param mysqli_stmt $stmt,
 * @param mysqli $conn.
 */
function fechar_recursos(?mysqli_stmt $stmt = null, ?mysqli $conn = null): void
{
    if ($stmt) $stmt->close();
    if ($conn) $conn->close();
}

// Execução principal
try {
    validar_autenticacao();

    $my_id = $_SESSION['usuario_id'];
    $other_id = obter_id_destinatario();

    $result = buscar_mensagens($conn, $my_id, $other_id);
    $html = gerar_html_mensagens($result, $my_id);

    enviar_resposta_json(true, $html);
} catch (Exception $e) {
    http_response_code(500);
    enviar_resposta_json(false, '', $e->getMessage());
} finally {
    fechar_recursos($stmt ?? null, $conn ?? null);
}
