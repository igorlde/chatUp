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

$query = "SELECT 
            p.*, 
            u.nome as autor,
            GROUP_CONCAT(DISTINCT t.nome_tag) as tags,
            p.imagem_capa,
            GROUP_CONCAT(DISTINCT pi.caminho_arquivo) as imagens_adicionais
          FROM posts p
          INNER JOIN users u ON p.usuario_id = u.id
          LEFT JOIN post_tags pt ON p.id = pt.post_id
          LEFT JOIN tags t ON pt.tag_id = t.id
          LEFT JOIN post_imagens pi ON p.id = pi.post_id
          GROUP BY p.id
          ORDER BY p.data_publicacao DESC";

$posts = [];
$comentariosPorPost = [];

if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        // Processar campos
        $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
        $row['imagens_adicionais'] = $row['imagens_adicionais'] ? explode(',', $row['imagens_adicionais']) : [];
        $posts[] = $row;

        // Buscar comentários
        $stmtComentarios = $conn->prepare("
            SELECT c.*, u.nome as autor 
            FROM comentarios c
            INNER JOIN users u ON c.usuario_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.data_comentario DESC
        ");
        $stmtComentarios->bind_param("i", $row['id']);
        $stmtComentarios->execute();
        $comentarios = $stmtComentarios->get_result()->fetch_all(MYSQLI_ASSOC);
        $comentariosPorPost[$row['id']] = $comentarios;
    }
    $result->free();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web_principal_chatUp</title>
    <link rel="stylesheet" href="style/main.css">
</head>

<body>
    <header>
        <a href="post.php" class="create-post-btn">＋ Criar Novo Post</a>

        <!--metodo para buscar usuarios-->
        <form method="POST" action="busca.php">
            <input type="text" name="User_name" placeholder="Buscar usuários...">
            <button type="submit">Buscar</button>
        </form>
        <form action="editar-perfil.php" method="post">
            <button type="submit">editar perfil </button>
        </form>

        </form>
    </header>
    <main>

        <!--criação de perfil-->



        <!-- codigo valiosos-->
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

                    <?php if (!empty($post['imagem_capa'])): ?>
                        <img src="<?= htmlspecialchars($post['imagem_capa']) ?>"
                            class="post-capa"
                            alt="Capa do post">
                    <?php endif; ?>

                    <?php if (!empty($post['descricao'])): ?>
                        <p class="post-description"><?= htmlspecialchars($post['descricao']) ?></p>
                    <?php endif; ?>

                    <div class="post-content"><?= nl2br(htmlspecialchars($post['conteudo'])) ?></div>

                    <?php if (!empty($post['imagens_adicionais'])): ?>
                        <div class="galeria-post">
                            <?php foreach ($post['imagens_adicionais'] as $imagem): ?>
                                <img src="<?= htmlspecialchars($imagem) ?>"
                                    class="galeria-imagem"
                                    alt="Imagem do post">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['tags'])): ?>
                        <div class="post-tags">
                            <?php foreach ($post['tags'] as $tag): ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['sucesso'])): ?>
                        <div class="mensagem-sucesso" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                            <?= $_SESSION['sucesso'] ?>
                        </div>
                        <?php unset($_SESSION['sucesso']); ?>
                    <?php endif; ?>

                    <div class="comentarios">
                        <h3>Comentários (<?= count($comentariosPorPost[$post['id']] ?? []) ?>)</h3>

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

                        <!--botao de exclusao -->
                        <?php if ($post['usuario_id'] == $_SESSION['usuario_id']): ?>
                            <form method="GET" action="excluir-post.php" onsubmit="return confirm('Tem certeza que deseja excluir este post?');">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit">Excluir</button>
                            </form>
                            <?php endif; ?>//

                            <form class="form-comentarios" method="POST" action="comentario.php">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <textarea
                                    name="comentario"
                                    placeholder="Escreva seu comentário..."
                                    required></textarea>
                                <button type="submit" class="btn-comentar">Publicar Comentário</button>
                            </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-posts">
                <p>Nenhum post encontrado. Seja o primeiro a compartilhar algo!</p>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>