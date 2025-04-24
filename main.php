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
//consulta de avatar
$stmtUser = $conn->prepare("SELECT nome, avatar FROM users WHERE id = ?");
$stmtUser->bind_param("i", $_SESSION["usuario_id"]);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$avatar = !empty($userData['avatar']) ? $userData['avatar'] : 'default-avatar.jpg';
$stmtUser->close();

//consulta da logica de aparecer nosso post curtidas e etc
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
$comentariosPorPost = [];

if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        // Converter strings para arrays
        $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
        $row['imagens_adicionais'] = $row['imagens_adicionais'] ? explode(',', $row['imagens_adicionais']) : [];
        $row['curtidas'] = (int)$row['curtidas'];
        $row['descurtidas'] = (int)$row['descurtidas'];

        $posts[] = $row;
        // Buscar comentários
        $stmtComentarios = $conn->prepare("
             SELECT 
        c.id,
        c.texto,
        c.data_comentario,
        u.id as usuario_id,
        u.nome as autor,
        u.avatar as autor_avatar
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
    <?php include 'sidebar.php'; ?>
    <!-- codigo valiosos-->
    <!--<pre><//?=// print_r($posts, true) ?></pre>-->
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
                    <form action="funtions/likes-functions.php" method="post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" class="btn-curtir">
                            👍 <?= $post['curtidas'] ?? 0 ?>
                        </button>
                    </form>
                    <!--logica de deslike-->
                    <form action="funtions/deslike-functions.php" method="post">
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
                                                <form action="excluir-comentarios.php" method="get" class="form-exclusao">
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
                            <form method="POST" action="comentario.php" class="form-comentario">
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

            <script>
                // Rolagem automática para o post após exclusão
                document.addEventListener('DOMContentLoaded', function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const postId = urlParams.get('post_id');

                    if (postId) {
                        const postElement = document.getElementById(`post-${postId}`);
                        if (postElement) {
                            postElement.scrollIntoView({
                                behavior: 'smooth'
                            });

                            // Destacar o post
                            postElement.style.transition = 'all 0.5s';
                            postElement.style.boxShadow = '0 0 15px rgba(52, 152, 219, 0.5)';

                            setTimeout(() => {
                                postElement.style.boxShadow = 'none';
                            }, 2000);
                        }
                    }
                });
            </script>
            <!--ao comentar sera rolado ate onde comentou-->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const urlParams = new URLSearchParams(window.location.search);

                    // Scroll para comentários se houver parâmetro
                    if (urlParams.has('comentario')) {
                        const postId = urlParams.get('post_id');
                        const comentarioStatus = document.getElementById('comentario-status');

                        if (postId) {
                            const targetSection = document.getElementById(`comentarios-post-${postId}`);
                            if (targetSection) {
                                setTimeout(() => {
                                    targetSection.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                }, 500);
                            }
                        }

                        // Remove a notificação após 5 segundos
                        if (comentarioStatus) {
                            setTimeout(() => {
                                comentarioStatus.style.transform = 'translateX(150%)';
                                setTimeout(() => comentarioStatus.remove(), 500);
                            }, 5000);
                        }
                    }
                });
            </script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Seleciona todos os botões que controlam os comentários
                    const btnToggleList = document.querySelectorAll('.btn-toggle');

                    btnToggleList.forEach(function(btnToggle) {
                        btnToggle.addEventListener('click', function() {
                            // Obtém o ID do post a partir do atributo data-post
                            const postId = btnToggle.getAttribute('data-post');
                            // Seleciona o container de comentários correspondente
                            const comentariosContainer = document.getElementById('comentarios-container-' + postId);

                            // Alterna a exibição do container dos comentários
                            if (comentariosContainer.style.display === "none" || comentariosContainer.style.display === "") {
                                comentariosContainer.style.display = "block";
                                btnToggle.textContent = "Ocultar comentários";
                            } else {
                                comentariosContainer.style.display = "none";
                                btnToggle.textContent = "Mostrar comentários";
                            }
                        });
                    });
                });
            </script>
            </div>
</body>

</html>