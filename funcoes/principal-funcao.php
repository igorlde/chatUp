<?php
require_once __DIR__ . '/../connector_database/connector.php';


/**
 * Valida autenticação do usuário
 */
function validate_user_authentication(): void {
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: login.php");
        exit;
    }
}

function fetch_user_data(mysqli $conn, int $usuario_id): array {
    $stmt = $conn->prepare("SELECT nome, avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return [
        'nome' => $result['nome'] ?? '',
        'avatar' => $result['avatar'] ?? 'default-avatar.jpg'
    ];
}

/**
 * Busca todos os posts com relacionamentos
 */
function fetch_all_posts(mysqli $conn): array {
    $query = "SELECT 
            p.id,
            p.usuario_id,
            p.titulo,
            p.conteudo,
            p.data_publicacao,
            p.imagem_capa,
            p.video,
            u.nome as autor,

            /* Tags (Subquery)*/
            (SELECT GROUP_CONCAT(DISTINCT t.nome_tag) 
             FROM post_tags pt 
             INNER JOIN tags t ON pt.tag_id = t.id 
             WHERE pt.post_id = p.id) as tags,

            (SELECT GROUP_CONCAT(DISTINCT pi.caminho_arquivo) 
             FROM post_imagens pi 
             WHERE pi.post_id = p.id) as imagens_adicionais,
                        /*contado curtidas*/
            (SELECT COUNT(DISTINCT c.id) 
             FROM curtidas c 
             WHERE c.post_id = p.id) as curtidas,
            /*contado dislikes*/
            (SELECT COUNT(DISTINCT d.id) 
             FROM descurtidas d 
             WHERE d.post_id = p.id) as descurtidas
            /*Ordenado por curtidas e data de publicação*/
          FROM posts p
          INNER JOIN users u ON p.usuario_id = u.id
          ORDER BY curtidas DESC, p.data_publicacao DESC";
    
    $posts = [];
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = process_post_row($row);
        }
        $result->free();
    }
    return $posts;
}

/**
 * Processa uma linha de post para formato correto
 */
function process_post_row(array $row): array {
    return [
        'id' => (int)$row['id'],
        'usuario_id' => (int)$row['usuario_id'],
        'titulo' => $row['titulo'],
        'conteudo' => $row['conteudo'],
        'data_publicacao' => $row['data_publicacao'],
        'imagem_capa' => $row['imagem_capa'],
        'video' => $row['video'],
        'autor' => $row['autor'],
        'tags' => $row['tags'] ? explode(',', $row['tags']) : [],
        'imagens_adicionais' => $row['imagens_adicionais'] ? explode(',', $row['imagens_adicionais']) : [],
        'curtidas' => (int)$row['curtidas'],
        'descurtidas' => (int)$row['descurtidas']
    ];
}

/**
 * Busca comentários de um post
 */
function fetch_comments_for_post(mysqli $conn, int $post_id): array {
    $stmt = $conn->prepare("
        SELECT c.id, c.texto, c.data_comentario, 
               u.id as usuario_id, u.nome as autor, u.avatar as autor_avatar
        FROM comentarios c
        INNER JOIN users u ON c.usuario_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.data_comentario DESC
    ");
    
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $comments;
}