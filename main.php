<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");

//Verificando se o usuario esta logado pois tudo depende de seu id.
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}
$query = "SELECT 
            p.*, 
            u.nome as autor,
            GROUP_CONCAT(t.nome_tag) as tags
          FROM posts p
          INNER JOIN users u ON p.usuario_id = u.id
          LEFT JOIN post_tags pt ON p.id = pt.post_id
          LEFT JOIN tags t ON pt.tag_id = t.id
          GROUP BY p.id
          ORDER BY p.data_publicacao DESC";

//criando um array de post.
$posts = [];
if($result = $conn->query($query)){
    while($row = $result->fetch_assoc()){
        $posts[] = $row;
    }
    $result->free();
}
$conn->close();
//a partir daqui começa os codigos para os comentarios
/*
if(!$conn || $conn->connect_error){
    die("Status da conexão: ".($conn->connect_error ?? "objeto invalido de conexão"));
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $comentarios = $_POST["comentarios"]??"empty";
}
*/
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web_principal_chatUp</title>
    <link rel="stylesheet" href="style/style.css">
</head>
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <div class="post-header">
                        <h2 class="post-title"><?= htmlspecialchars($post['titulo']) ?></h2>
                        <div class="post-meta">
                            <span class="post-author"><?= htmlspecialchars($post['autor']) ?></span>
                            <span> • <?= date('d/m/Y H:i', strtotime($post['data_publicacao'])) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($post['descricao']): ?>
                        <p class="post-description"><?= htmlspecialchars($post['descricao']) ?></p>
                    <?php endif; ?>

                    <div class="post-content"><?= nl2br(htmlspecialchars($post['conteudo'])) ?></div>

                    <?php if (!empty($post['tags'])): ?>
                        <div class="post-tags">
                            <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-posts">
                <p>Nenhum post encontrado. Seja o primeiro a compartilhar algo!</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="post.php" class="create-post-btn">Criar Novo Post</a>
</body>
</html>