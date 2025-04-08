<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");

//Verificando se o usuario esta logado pois tudo depende de seu id.
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
if (!$conn || $conn->connect_error) {
    die("Status da conexão: " . ($conn->connect_error ?? "objeto invalido de conexão"));
}

//Metodo de acesso para aparecer nossos dados la no html
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
if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
        // Dentro do loop de posts
        $queryComentarios = "SELECT 
                                c.*, 
                                u.nome AS autor 
                            FROM comentarios c
                            INNER JOIN users u ON c.usuario_id = u.id
                            WHERE c.post_id = ?
                            ORDER BY c.data_comentario DESC";
        
        $stmtComentarios = $conn->prepare($queryComentarios);
        $stmtComentarios->bind_param("i", $row['id']); // ID do post atual
        $stmtComentarios->execute();
        $resultComentarios = $stmtComentarios->get_result();
        
        // Armazena comentários no array usando o ID do post como chave
        $comentariosPorPost[$row['id']] = $resultComentarios->fetch_all(MYSQLI_ASSOC);
        
        $stmtComentarios->close();
    }
    $result->free();
}

?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web_principal_chatUp</title>
    <link rel="stylesheet" href="style/main.css">
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
                <!--comentarios a partir daqui -->
                <div class="comentarios">
                    <h3>Comentários (<?= count($comentariosPorPost[$post['id']] ?? []) ?>)</h3>

                    <!-- Lista de Comentários -->
                    <?php if (!empty($comentariosPorPost[$post['id']])): ?>
                        <?php foreach ($comentariosPorPost[$post['id']] as $comentario): ?>
                            <div class="comentario">
                                <div class="comentario-header">
                                    <span class="comentario-autor">
                                        <?= htmlspecialchars($comentario['autor']) ?>
                                    </span>
                                    <span class="comentario-data">
                                        <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                    </span>
                                </div>
                                <p class="comentario-texto">
                                    <?= nl2br(htmlspecialchars($comentario['texto'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                    <?php endif; ?>

                    <!-- Formulário Corrigido -->
                    <form class="form-comentarios" method="POST" action="comentario.php">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <textarea
                            name="comentario" # Nome do campo corrigido
                            placeholder="Escreva seu comentário..."
                            required></textarea>
                        <button type="submit" class="btn-comentar">Publicar Comentário</button>
                    </form>
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