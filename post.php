<?php
session_start();
include("connector_database/connector.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de upload
$diretorioUploads = __DIR__ . '/uploads/posts/';
$tamanhoMaximo = 30 * 1024 * 1024; // 2MB
$tamanhoMaximoVideo = 60 * 1024 * 1024;
$MAXIMO_IMAGEM = 5;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verificar e criar diretório de uploads
        if (!file_exists($diretorioUploads)) {
            mkdir($diretorioUploads, 0755, true);
        }

        $videoPath = null;
        if (!empty($_FILES['video']['tmp_name'])) {
            $video = $_FILES['video'];
        
            if ($video['size'] > $tamanhoMaximoVideo) {
                throw new Exception("O vídeo excede o limite de 60MB");
            }
        
            if (!in_array($video['type'], ['video/mp4', 'video/webm'])) {
                throw new Exception("Formato inválido (MP4/WebM)");
            }
        
            $nomeUnicoVideo = uniqid('video_') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $video['name']); // Sanitize nome
            $caminhoCompletoVideo = $diretorioUploads . $nomeUnicoVideo;
        
            if (!move_uploaded_file($video['tmp_name'], $caminhoCompletoVideo)) {
                throw new Exception("Erro ao salvar vídeo. Verifique permissões.");
            }
        
            $videoPath = 'uploads/posts/' . $nomeUnicoVideo;
        }

        // Processar upload da capa
        $imagemCapaPath = $imagemCapaPath ?? null;
        $videoPath = $videoPath ?? null;
        if (!empty($_FILES['imagem_capa']['tmp_name'])) {
            $arquivo = $_FILES['imagem_capa'];

            // Validações
            if ($arquivo['size'] > $tamanhoMaximo) {
                throw new Exception("Imagem de capa excede 30MB");
            }
            if (!in_array($arquivo['type'], ['image/jpeg', 'image/png'])) {
                throw new Exception("Formato inválido para capa");
            }

            $nomeUnico = uniqid('capa_') . '_' . basename($arquivo['name']);
            $caminhoCompleto = $diretorioUploads . $nomeUnico;

            if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                $imagemCapaPath = 'uploads/posts/' . $nomeUnico;
            }
        }


        // Processar imagens adicionais
        $imagensAdicionais = [];
        if (!empty($_FILES['imagens_adicionais']['tmp_name'][0])) {
            $totalImagens = count($_FILES['imagens_adicionais']['tmp_name']);

            if ($totalImagens > $MAXIMO_IMAGEM) {
                throw new Exception("Máximo de {$MAXIMO_IMAGEM} imagens adicionais");
            }

            foreach ($_FILES['imagens_adicionais']['tmp_name'] as $key => $tmpName) {
                $arquivo = [
                    'name' => $_FILES['imagens_adicionais']['name'][$key],
                    'type' => $_FILES['imagens_adicionais']['type'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['imagens_adicionais']['size'][$key]
                ];

                if ($arquivo['size'] > $tamanhoMaximo) {
                    throw new Exception("Imagem {$arquivo['name']} excede 30MB");
                }
                if (!in_array($arquivo['type'], ['image/jpeg', 'image/png'])) {
                    throw new Exception("Formato inválido para {$arquivo['name']}");
                }

                $nomeUnico = uniqid('post_') . '_' . basename($arquivo['name']);
                $caminhoCompleto = $diretorioUploads . $nomeUnico;

                if (move_uploaded_file($tmpName, $caminhoCompleto)) {
                    $imagensAdicionais[] = 'uploads/posts/' . $nomeUnico;
                }
            }
        }

        // Validação dos dados do formulário
        $titulo = trim($_POST["titulo"] ?? '');
        $tema = $_POST["tema"] ?? null;
        $descricao = $_POST["descricao"] ?? null;
        $conteudo = trim($_POST["conteudo"] ?? '');
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        //partes onde arrays de erros são iniciados para fazermos a validação dos campos preenchidos
        $erros = [];
        if (empty(trim($titulo))) $erros[] = "Título é obrigatório";
        if (empty(trim($conteudo))) $erros[] = "Conteúdo não pode estar vazio";
        $tags = array_filter(array_map('trim', explode(',', $tagsInput)), function ($tag) {
            return !empty($tag);
        });

        foreach ($tags as $tag) {
            if (mb_strlen($tag) > 30) {
                $erros[] = "Tag '{$tag}' excede 30 caracteres";
            }
        }
        if (!empty($erros)) {
            $_SESSION['erros_post'] = $erros;
            header("Location: post.php");
            exit;
        }

        $conn->begin_transaction();

        // Inserir post principal
        $stmt = $conn->prepare("INSERT INTO posts 
            (usuario_id, titulo, tema, descricao, conteudo, imagem_capa, video) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $imagemCapaPath = !empty($imagemCapaPath) ? $imagemCapaPath : null;
        $videoPath = !empty($videoPath) ? $videoPath : null;

        $stmt->bind_param("issssss", $usuario_id, $titulo, $tema, $descricao, $conteudo, $imagemCapaPath, $videoPath);
        $stmt->execute();
        $novo_post_id = $conn->insert_id;

        // Inserir imagens adicionais
        if (!empty($imagensAdicionais)) {
            $stmtImagens = $conn->prepare("INSERT INTO post_imagens (post_id, caminho_arquivo) VALUES (?, ?)");
            foreach ($imagensAdicionais as $caminho) {
                $stmtImagens->bind_param("is", $novo_post_id, $caminho);
                $stmtImagens->execute();
            }
        }

        // Processar tags
        $tags = array_unique(array_map('trim', explode(',', $_POST['tags'])));
        $tagStmt = $conn->prepare("INSERT IGNORE INTO tags (nome_tag) VALUES (?)");
        $getTagStmt = $conn->prepare("SELECT id FROM tags WHERE nome_tag = ?");
        $relStmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $tagName) {
            $tagStmt->bind_param("s", $tagName);
            $tagStmt->execute();

            $getTagStmt->bind_param("s", $tagName);
            $getTagStmt->execute();
            $tagId = $getTagStmt->get_result()->fetch_row()[0];

            $relStmt->bind_param("ii", $novo_post_id, $tagId);
            $relStmt->execute();
        }

        $conn->commit();
        $_SESSION['sucesso_post'] = "Post criado com sucesso!";
        header("Location: main.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['erros_post'] = ["Erro: " . $e->getMessage()];
        header("Location: post.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Criar Post</title>
    <link rel="stylesheet" href="style/post.css?v=2">
</head>

<body>
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