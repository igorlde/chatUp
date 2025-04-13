<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");

// Verifica conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Verifica login
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
$posts = [];
//verificar envio do busca post
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["Title_post"])) {
    try {
        $searchTerm = trim($_POST['Title_post']);
        $executeBind = "%$searchTerm%";//criando a variavel para receber o parametro para o bind_param.

        $sql = "SELECT 
        p.*,
        u.nome AS autor,
        GROUP_CONCAT(DISTINCT t.nome_tag) AS tags,
        GROUP_CONCAT(DISTINCT pi.caminho_arquivo) AS imagens_adicionais
    FROM posts p
    INNER JOIN users u ON p.usuario_id = u.id
    LEFT JOIN post_tags pt ON p.id = pt.post_id
    LEFT JOIN tags t ON pt.tag_id = t.id
    LEFT JOIN post_imagens pi ON p.id = pi.post_id
    WHERE p.titulo LIKE ?
    GROUP BY p.id
    ORDER BY p.data_publicacao DESC
    LIMIT 10";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("erro ao tentar executar comando sql " . $conn->error);
        }
        $stmt->bind_param("s", $executeBind);//bind_param em execução
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
            $row['imagens_adicionais'] = $row['imagens_adicionais'] ? explode(',', $row['imagens_adicionais']) : [];
            $posts[] = $row;
        }
    } catch (Exception $e) {
        $e->getMessage();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/main.css">
</head>

<body>
    <main>
    <header>
    <nav>
    <ul>
        <a href="main.php">volta ao pagina principal</a>
    </ul>
    </nav>
    </header>
    </main>
    
    <!-- Código de posts -->
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
</body>

</html>