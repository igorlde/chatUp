<?php
session_start();
include("connector_database/connector.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de upload
$diretorioUploads = __DIR__ . '/uploads/posts/';
$tamanhoMaximo = 2 * 1024 * 1024; // 2MB
$MAXIMO_IMAGEM = 5;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verificar e criar diretório de uploads
        if (!file_exists($diretorioUploads)) {
            mkdir($diretorioUploads, 0755, true);
        }

        // Processar upload da capa
        $imagemCapaPath = null;
        if (!empty($_FILES['imagem_capa']['tmp_name'])) {
            $arquivo = $_FILES['imagem_capa'];

            // Validações
            if ($arquivo['size'] > $tamanhoMaximo) {
                throw new Exception("Imagem de capa excede 2MB");
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
                    throw new Exception("Imagem {$arquivo['name']} excede 2MB");
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

        $erros = [];
        if (empty($titulo)) $erros[] = "Título é obrigatório";
        if (empty($conteudo)) $erros[] = "Conteúdo não pode estar vazio";
        if (empty($_POST['tags'])) $erros[] = "Tag obrigatória";
        if (!$usuario_id) $erros[] = "Usuário não autenticado";

        if (!empty($erros)) {
            $_SESSION['erros_post'] = $erros;
            header("Location: post.php");
            exit;
        }

        $conn->begin_transaction();

        // Inserir post principal
        $stmt = $conn->prepare("INSERT INTO posts 
            (usuario_id, titulo, tema, descricao, conteudo, imagem_capa) 
            VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssss", $usuario_id, $titulo, $tema, $descricao, $conteudo, $imagemCapaPath);
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
    <link rel="stylesheet" href="style/post.css">
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
         

            <!-- Seção de upload de imagens -->
            <div class="form-group">
                <label>Imagem de Capa:</label>
                <input type="file" name="imagem_capa" accept="image/*">
                <div class="form-note">Formatos: JPG/PNG (Máx. 2MB)</div>
            </div>

            <div class="form-group">
                <label>Imagens Adicionais:</label>
                <input type="file" name="imagens_adicionais[]" multiple accept="image/*">
                <div class="form-note">Máximo 5 imagens (2MB cada)</div>
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
                </div>
            </div>

            <button type="submit" class="btn-primary">Publicar Post</button>
        </form>
    </div>

    <script>
        // Script para gerenciar tags
        const tagsInput = document.getElementById('tagsInput');
        const hiddenTags = document.getElementById('hiddenTags');
        const tagsList = document.getElementById('tagsList');

        tagsInput.addEventListener('keydown', function(e) {
            if (['Enter', ',', ';'].includes(e.key)) {
                e.preventDefault();
                const tag = this.value.trim().replace(/[,;]$/, '');
                if (tag) {
                    const tagElement = document.createElement('div');
                    tagElement.className = 'tag';
                    tagElement.innerHTML = `
                        ${tag}
                        <span class="remove-tag" onclick="this.parentElement.remove()">&times;</span>
                    `;
                    tagsList.appendChild(tagElement);
                    this.value = '';
                    updateHiddenTags();
                }
            }
        });

        function updateHiddenTags() {
            const tags = Array.from(document.querySelectorAll('.tag'))
                .map(tag => tag.textContent.replace('×', '').trim());
            hiddenTags.value = tags.join(',');
        }
    </script>
</body>

</html>