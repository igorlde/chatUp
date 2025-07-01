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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>
    <div class="sidebar">
        <?php require __DIR__ . '/sidebar/newsidebar.php'; ?>
    </div>

    <div class="layout">
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

                <?php if (!empty($_SESSION['post_success'])): ?>
                    <div class="alert-success">
                        <?= htmlspecialchars($_SESSION['post_success']) ?>
                    </div>
                    <?php unset($_SESSION['post_success']); ?>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <h1>Criar Novo Post</h1>

                    <div class="form-group">
                        <label>Video</label>
                        <div class="custom-file">
                            <input type="file" name="video" id="video-upload" accept="video/mp4, video/webm">
                            <label for="video-upload" class="file-label">Escolher Vídeo</label>
                        </div>
                        <video id="preview-video" controls style="display:none; width:100%; margin-top:10px;"></video>
                        <div class="form-note">Formatos: MP4/WebM (Máx. 200MB)</div>
                    </div>

                    <div class="form-group">
                        <label>Imagem de Capa:</label>
                        <div class="custom-file">
                            <input type="file" name="imagem_capa" id="input-capa" accept="image/*">
                            <label for="input-capa" class="file-label">Escolher Imagem</label>
                        </div>
                        <img id="preview-capa" style="max-width: 100%; margin-top: 10px; display: none;" />
                        <div class="form-note">Formatos: JPG/PNG (Máx. 30MB)</div>
                    </div>

                    <div class="form-group">
                        <label>Imagens Adicionais:</label>
                        <div class="custom-file">
                            <input type="file" name="imagens_adicionais[]" multiple id="imagens-adicionais" accept="image/*">
                            <label for="imagens-adicionais" class="file-label">Escolher Imagem</label>
                        </div>
                        <div class="form-note">Máximo 5 imagens (30MB cada)</div>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" required maxlength="100">
                        <small id="count-titulo">0/100</small>
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
                        <small id="count-descricao">0/255</small>
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

                tagsInput.addEventListener('keydown', function (e) {
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

                updateHiddenTags();

                // Preview da imagem de capa
                document.getElementById('input-capa').addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const preview = document.getElementById('preview-capa');
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Preview do vídeo
                document.getElementById('video-upload').addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('preview-video');
                    if (file) {
                        const url = URL.createObjectURL(file);
                        preview.src = url;
                        preview.style.display = 'block';
                    }
                });

                // Contadores de caracteres
                const descricao = document.getElementById('descricao');
                const contadorDescricao = document.getElementById('count-descricao');
                descricao.addEventListener('input', () => {
                    contadorDescricao.textContent = `${descricao.value.length}/255`;
                });

                const titulo = document.getElementById('titulo');
                const contadorTitulo = document.getElementById('count-titulo');
                titulo.addEventListener('input', () => {
                    contadorTitulo.textContent = `${titulo.value.length}/100`;
                });
            </script>
        </div>
    </div>
</body>
</html>
