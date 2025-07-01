<?php
require_once __DIR__ . '/../connector_database/connector.php';

// Correção no nome da variável de sessão
if (!isset($_SESSION["usuario_id"])) { // Removi o "s" extra de "usuarios_id"
    header("Location: login.php");
    exit;
}

// Função para contar SEGUIDORES 
/**
 * @param int $user_id
 * @param $conn
 */
function getSeguidoresCount($user_id, $conn)
{
    // Correção na coluna (seguido_id em vez de seguidor_id)
    $sql = $conn->prepare("SELECT COUNT(*) AS total FROM seguidores WHERE seguido_id = ?");
    $sql->bind_param("i", $user_id);
    $sql->execute();
    $result = $sql->get_result();
    return $result->fetch_assoc()['total'];
}

// Função para listar SEGUIDORES 
/**
 *  @param int $user_id
 * @param $conn
 * 
 */
function getListSeguidores($user_id, $conn)
{
    $sql = $conn->prepare("
        SELECT u.id, u.nome_usuario, u.avatar 
        FROM seguidores s
        INNER JOIN users u ON s.seguidor_id = u.id
        WHERE s.seguido_id = ?
    ");
    $sql->bind_param("i", $user_id);
    $sql->execute();
    return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Função para contar SEGUINDO 
/**
 *  @param int $user_id
 * @param $conn
 * 
 * 
 */
function getSeguindoCount($user_id, $conn)
{
    // Correção na coluna (seguidor_id em vez de seguido_id)
    $sql = $conn->prepare("SELECT COUNT(*) AS total FROM seguidores WHERE seguidor_id = ?");
    $sql->bind_param("i", $user_id);
    $sql->execute();
    $result = $sql->get_result();
    return $result->fetch_assoc()['total'];
}

// Função para listar SEGUINDO 
/**
 *  @param int $user_id
 * @param $conn
 */
function getListSeguindo($user_id, $conn)
{
    $sql = $conn->prepare("
        SELECT u.id, u.nome_usuario, u.avatar 
        FROM seguidores s
        INNER JOIN users u ON s.seguido_id = u.id
        WHERE s.seguidor_id = ?
    ");
    $sql->bind_param("i", $user_id);
    $sql->execute();
    return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>