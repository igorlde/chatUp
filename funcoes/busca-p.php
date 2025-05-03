<?php
require_once __DIR__ . '/../connector_database/connector.php';

/**
 * Função validar dados html do usuario_id para o banco.
 * 
 */
function validar_autenticacao(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}
/**
 * Função de puxa nosso dados dentro do banco.
 * @param sql $conn,
 * @param string $termo_busca.
 */
function sql_busca_posts($conn, string $termo_busca): array
{
    $posts = [];
    try {
        $sql = "SELECT p.*, u.nome AS autor, GROUP_CONCAT(DISTINCT t.nome_tag) AS tags, GROUP_CONCAT(DISTINCT pi.caminho_arquivo) AS imagens_adicionais FROM posts p INNER JOIN users u ON p.usuario_id = u.id LEFT JOIN post_tags pt ON p.id = pt.post_id LEFT JOIN tags t ON pt.tag_id = t.id LEFT JOIN post_imagens pi ON p.id = pi.post_id WHERE p.titulo LIKE ? GROUP BY p.id ORDER BY p.data_publicacao DESC LIMIT 10"; // Sua query original
        $stmt = $conn->prepare($sql);
        $searchTerm = "%" . $termo_busca . "%"; 
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
            $row['imagens_adicionais'] = $row['imagens_adicionais'] ? explode(',', $row['imagens_adicionais']) : [];
            $posts[] = $row;
        }
    } catch (Exception $e) {
        error_log("Erro na busca: " . $e->getMessage());
    }
    return $posts;
}

/**
 * Função receber dados do metodo post na busca do titulo.
 * @param sql $conn.
 */
function processar_busca($conn): array
{
    $posts = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Title_post'])) {
        $termo = trim($_POST['Title_post']);
        $posts = sql_busca_posts($conn, $termo);
    }
    return $posts;
}
