<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web_principal_chatUp</title>
    <link rel="stylesheet" href="/chatup/style/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style/newsidebar.css">
</head>

<body>
    
    <?php include("sidebar/newsidebar.php"); ?>

    <div class="aba-post">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <article class="post-card" id="post-<?= $post['id'] ?>">
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

                    <!-- Seção de Vídeo (adicionar após a imagem de capa) -->
                    <?php if (!empty($post['video'])): ?>
                        <div class="video-container">
                            <video controls style="width: 100%; border-radius: 8px; margin: 15px 0;">
                                <source src="<?= htmlspecialchars($post['video']) ?>" type="video/mp4">
                                Seu navegador não suporta vídeos HTML5.
                            </video>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['descricao'])): ?>
                        <p class="post-description"><?= htmlspecialchars($post['descricao']) ?></p>
                    <?php endif; ?>

                    <!--conteudo do post-->
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

                    <!--logica das curtidas dentro do main.php -->
                    <form action="/chatup/funcoes/likes-functions.php" method="post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" class="btn-curtir">
                            👍 <?= $post['curtidas'] ?? 0 ?>
                        </button>
                    </form>
                    <!--logica de deslike-->
                    <form action="/chatup/funcoes/deslike-functions.php" method="post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" class="btn-dislike">👎 <?= $post['descurtidas'] ?? 0 ?></button>
                    </form>

                    <!--mostrar comentarios-->
                    <button type="button" class="btn-toggle" data-post="<?= $post['id'] ?>">Mostrar comentários</button>
                    <div id="comentarios-container-<?= $post['id'] ?>" class="comentarios-container" style="display:none;">
                        <h3>Comentários (<?= count($comentariosPorPost[$post['id']] ?? []) ?>)</h3>

                        <?php if (!empty($comentariosPorPost[$post['id']])): ?>
                            <?php foreach ($comentariosPorPost[$post['id']] as $comentario): ?>
                                <!--Div para estilizar os comentarios no mobile-->
                                <div class="comentario mobile-column">
                                    <img src="uploads/avatars/<?= htmlspecialchars($comentario['autor_avatar'] ?? 'default-avatar.jpg') ?>"
                                        alt="<?= htmlspecialchars($comentario['autor'] ?? 'Usuário') ?>"
                                        class="avatar-comentario"> <!-- Classe mantida -->
                                    <div>
                                        <div>
                                            <!--cabeçalho do comentario-->
                                            <div class="comentario-header">
                                                <span class="comentario-autor"><?= htmlspecialchars($comentario['autor']) ?></span>
                                                <span class="comentario-data"><?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?></span>
                                            </div>
                                            <!--Comentario-->
                                            <p class="comentario-texto"><?= nl2br(htmlspecialchars($comentario['texto'])) ?></p>

                                            <?php if ($comentario['usuario_id'] == $_SESSION['usuario_id'] || $post['usuario_id'] == $_SESSION['usuario_id']): ?>
                                                <!-- função html excluir comentarios-->
                                                <form action="/chatup/funcoes/excluir-comentarios.php" method="get" class="form-exclusao">
                                                    <input type="hidden" name="id" value="<?= $comentario['id'] ?>">
                                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                    <button type="submit" class="btn-excluir"
                                                        onclick="return confirm('Tem certeza? Esta ação é irreversível!')">
                                                        🗑️ Excluir
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                            <?php endif; ?>
                            <?php if ($post['usuario_id'] == $_SESSION['usuario_id']): ?>
                                <form method="GET" action="excluir-post.php" onsubmit="return confirm('Tem certeza que deseja excluir este post?');">
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn-excluir-post">Excluir Post</button>
                                </form>
                            <?php endif; ?>

                            <!-- Formulário original que será clonado para a área flutuante -->
                            <!-- Barra fixa para postar comentário -->
                            <!-- Barra fixa para comentar (única para toda a página) -->
                            <form method="POST" action="/chatup/funcoes/comentario.php" class="form-comentario">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <textarea name="texto" required></textarea>
                                <button type="submit">Comentar</button>
                            </form>
                                </div>
                </article>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="no-posts">
                <p>Nenhum post encontrado. Seja o primeiro a compartilhar algo!</p>
            <?php endif; ?>
            <!--rolagem a excluir post-->
            <script src="js/rolagemExcluirPost.js"></script>
            <script src="js/rolagemComentar.js"></script>
            <script src="js/mostrarComentario.js"></script>
            </div>
</body>

</html>