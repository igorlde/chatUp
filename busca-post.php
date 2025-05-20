<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");
include(__DIR__ . '/funcoes/busca-p.php');
include("sidebar/newsidebar.php");

validar_autenticacao();

// Processar busca e obter posts
$posts = processar_busca($conn);

// Fechar conexão apenas no final
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/busca.css">
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style/newsidebar.css">
</head>

<body>
    <main>
        <header>
            <nav>
                <?php include("sidebar/side-post.php") ?>
            </nav>
        </header>
    <main>
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
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum post encontrado com o termo pesquisado.</p>
        <?php endif; ?>
    </main>

</body>

</html>