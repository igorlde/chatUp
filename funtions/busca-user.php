<?php
require_once __DIR__ . '/../connector_database/connector.php';
function validar_usuario(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}
/**
 * Função de processa metodo post de pagina html.
 * @param sql $conn.
 */
function processar_busca($conn): array
{
    $usuarios = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['User_name'])) {
        $termo_busca = '%' . trim($_POST['User_name']) . '%';
        $usuarios = processar_sql($conn, $termo_busca);
    }
    return $usuarios;
}
/**
 * Função de processar sql de busca usuarios.
 * @param sql $conn,
 * @param string $termo_busca.
 */
function processar_sql($conn, $termo_busca): array
{
    try {
        $usuario_id_atual = (int)$_SESSION["usuario_id"];
        $sql = $conn->prepare("SELECT 
                    u.id, 
                    u.nome, 
                    u.avatar,
                    EXISTS(
                        SELECT 1 
                        FROM seguidores 
                        WHERE seguidor_id = ? 
                        AND seguido_id = u.id
                    ) AS seguindo
                FROM users u
                WHERE u.nome LIKE ? 
                OR u.nome_usuario LIKE ? 
                ORDER BY u.nome ASC");

        if (!$sql) {
            throw new Exception("Erro na preparação: " . $conn->error);
        }
        $sql->bind_param("iss", $usuario_id_atual, $termo_busca, $termo_busca);
        if (!$sql->execute()) {
            throw new Exception("Erro na execução: " . $sql->error);
        }
        $result = $sql->get_result();
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        $sql->close();

        return $usuarios;
    } catch (Exception $e) {
        error_log("Erro na busca: " . $e->getMessage());
        return [];
    }
}
