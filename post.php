<?php
session_start();
include("connector_database/connector.php");
require __DIR__ . '/funcoes/Processo-post.php';
processarPostagem(
    $conn,
    __DIR__ . '/uploads/posts/',
    'uploads/posts/',
    'main.php',
    'post.php'
);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Criar Post</title>
    <link rel="stylesheet" href="style/post.css?v=2">
</head>

<body>
    <div class="layout">
        <div class="sidebar">
            <?php require __DIR__ . '/sidebar/newsidebar.php'; ?>
        </div>

        <div class="main-content">

            <div class="post-creator">
                <?php if (!empty($_SESSION['erros_post'])): ?>
                    <div class="alert-error">
                        <?php foreach ($_SESSION['erros_post'] as $erro): ?>
                            <p><?= htmlspecialchars($erro) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['erros_post']); ?>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <h1>Criar Novo Post</h1>
                    <div class="form-group">
                        <label>Video</label>
                        <input type="file" name="video" accept="video/mp4, video/webm">
                        <div class="form-note">Formatos: MP4/WebM (Máx. 200MB)</div>
                    </div>

                    <!-- Seção de upload de imagens -->
                    <div class="form-group">
                        <label>Imagem de Capa:</label>
                        <input type="file" name="imagem_capa" accept="image/*">
                        <div class="form-note">Formatos: JPG/PNG (Máx. 30MB)</div>
                    </div>


                    <div class="form-group">
                        <label>Imagens Adicionais:</label>
                        <input type="file" name="imagens_adicionais[]" multiple accept="image/*">
                        <div class="form-note">Máximo 5 imagens (30MB cada)</div>
                    </div>

                    <!-- Restante do formulário mantido -->
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="tema">Categoria:</label>
                        <select id="tema" name="tema">
                            <option value="">Selecione...</option>
                            <option value="Tecnologia">Tecnologia</option>
                            <option value="Educação">Educação</option>
                            <option value="Viagens">Viagens</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição Resumida:</label>
                        <textarea id="descricao" name="descricao" maxlength="255"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="conteudo">Conteúdo:</label>
                        <textarea id="conteudo" name="conteudo" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tags:</label>
                        <div class="tags-input">
                            <input type="text" id="tagsInput" placeholder="Ex: tecnologia, programação">
                            <input type="hidden" name="tags" id="hiddenTags">
                            <div id="tagsList"></div>
                            <small id="tagsError" class="error-message" style="color: red; display: none;">
                                Pelo menos uma tag é obrigatória
                            </small>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Publicar Post</button>
                </form>
            </div>
        </div>

        <script>
            const tagsInput = document.getElementById('tagsInput');
            const tagsList = document.getElementById('tagsList');
            const hiddenTags = document.getElementById('hiddenTags');

            function updateHiddenTags() {
                const tags = Array.from(tagsList.querySelectorAll('.tag'))
                    .map(tag => tag.textContent.replace('×', '').trim());

                hiddenTags.value = tags.join(',');
                document.getElementById('tagsError').style.display = tags.length ? 'none' : 'block';
            }

            tagsInput.addEventListener('keydown', function(e) {
                if (['Enter', ',', ';'].includes(e.key)) {
                    e.preventDefault();
                    const tag = this.value.trim().replace(/[,;]$/, '');
                    if (tag) {
                        const tagElement = document.createElement('div');
                        tagElement.className = 'tag';
                        tagElement.innerHTML = `
                    ${tag}
                    <span class="remove-tag" onclick="this.parentElement.remove(); updateHiddenTags()">&times;</span>
                `;
                        tagsList.appendChild(tagElement);
                        this.value = '';
                        updateHiddenTags();
                    }
                }
            });

            // Inicialização
            updateHiddenTags();
        </script>
</body>

</html>