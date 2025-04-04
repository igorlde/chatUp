<?php
session_start();
include("connector_database/connector.php");

// Habilitar relatório de erros detalhado (desativar em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validação e sanitização
        $titulo = trim($_POST["titulo"] ?? '');
        $tema = !empty($_POST["tema"]) ? trim($_POST["tema"]) : null;
        $descricao = !empty($_POST["descricao"]) ? trim($_POST["descricao"]) : null;
        $conteudo = trim($_POST["conteudo"] ?? '');
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        // Validação básica
        $erros = [];
        if (empty($titulo)) $erros[] = "Título é obrigatório";
        if (empty($conteudo)) $erros[] = "Conteúdo não pode estar vazio";
        if (!$usuario_id) $erros[] = "Usuário não autenticado";

        if (!empty($erros)) {
            $_SESSION['erros_post'] = $erros;
            header("Location: post.php");
            exit;
        }

        $conn->begin_transaction();

        // Inserir post principal
        $stmt = $conn->prepare("INSERT INTO posts 
            (usuario_id, titulo, tema, descricao, conteudo) 
            VALUES (?, ?, ?, ?, ?)");
        
        if (!$stmt) throw new Exception("Erro na preparação do post: " . $conn->error);
        
        $stmt->bind_param("issss", $usuario_id, $titulo, $tema, $descricao, $conteudo);
        if (!$stmt->execute()) throw new Exception("Erro ao salvar post: " . $stmt->error);
        
        $novo_post_id = $conn->insert_id;

        // Processar tags
        if (!empty($_POST['tags'])) {
            $tags = array_unique(array_filter(array_map('trim', 
                explode(',', $_POST['tags'])
            )));

            if (!empty($tags)) {
                $tagStmt = $conn->prepare("INSERT IGNORE INTO tags (nome_tag) VALUES (?)");
                $getTagStmt = $conn->prepare("SELECT id FROM tags WHERE nome_tag = ?");
                $relStmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");

                if (!$tagStmt || !$getTagStmt || !$relStmt) {
                    throw new Exception("Erro na preparação das tags: " . $conn->error);
                }

                foreach ($tags as $tagName) {
                    // Inserir tag
                    $tagStmt->bind_param("s", $tagName);
                    if (!$tagStmt->execute()) {
                        throw new Exception("Erro ao inserir tag: " . $tagStmt->error);
                    }

                    // Obter ID da tag
                    $getTagStmt->bind_param("s", $tagName);
                    $getTagStmt->execute();
                    $tagId = $getTagStmt->get_result()->fetch_row()[0];

                    // Relacionar com post
                    $relStmt->bind_param("ii", $novo_post_id, $tagId);
                    if (!$relStmt->execute()) {
                        throw new Exception("Erro ao vincular tag: " . $relStmt->error);
                    }
                }
            }
        }

        $conn->commit();
        $_SESSION['sucesso_post'] = "Post criado com sucesso!";
        header("Location: main.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("ERRO: " . $e->getMessage());
        $_SESSION['erros_post'] = ["Erro: " . $e->getMessage()];
        header("Location: post.php");
        exit;
    } finally {
        // Fechar todas as conexões
        if (isset($stmt)) $stmt->close();
        if (isset($tagStmt)) $tagStmt->close();
        if (isset($getTagStmt)) $getTagStmt->close();
        if (isset($relStmt)) $relStmt->close();
        if ($conn) $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
</head>
<body>
    <div class="post-creator">
        <!-- Exibir mensagens de erro/sucesso -->
        <?php if (!empty($_SESSION['erros_post'])): ?>
            <div class="alert-error">
                <?php foreach ($_SESSION['erros_post'] as $erro): ?>
                    <p><?= htmlspecialchars($erro) ?></p>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['erros_post']); ?>
        <?php endif; ?>
        
        <!--deixa como esta pelo amor de deus.-->
        <form method="POST">
           
            <div class="post-creator">
        <h1>Criar Novo Post</h1>
        <form action="post.php" method="POST">
            <!-- Título -->
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" required maxlength="100" placeholder="Insira um título impactante">
                <div class="form-note">Máximo 100 caracteres</div>
            </div>

            <!-- Tema/Categoria -->
            <div class="form-group">
                <label for="tema">Categoria Principal:</label>
                <select id="tema" name="tema">
                    <option value="">Selecione uma categoria</option>
                    <option value="Tecnologia">Tecnologia</option>
                    <option value="Educação">Educação</option>
                    <option value="Lifestyle">Lifestyle</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>

            <!-- Descrição -->
            <div class="form-group">
                <label for="descricao">Descrição Resumida:</label>
                <textarea id="descricao" name="descricao" maxlength="255" 
                    placeholder="Uma breve descrição para preview do post (opcional)"></textarea>
                <div class="form-note">Máximo 255 caracteres</div>
            </div>

            <!-- Conteúdo -->
            <div class="form-group">
                <label for="conteudo">Conteúdo Completo:</label>
                <textarea id="conteudo" name="conteudo" required 
                    placeholder="Desenvolva seu conteúdo aqui..."></textarea>
            </div>
            <!-- Ajuste no campo de tags -->
            <div class="form-group">
                <label>Tags:</label>
                <div class="tags-input" id="tagsContainer">
                    <input type="hidden" name="tags" id="hiddenTags">
                    <div id="tagsList"></div>
                    <input type="text" id="tagsInput" 
                           placeholder="Digite tags separadas por vírgula">
                </div>
                <div class="form-note">Exemplo: programação, web-development</div>
            </div>

            <button type="submit" class="submit-btn">Publicar Post</button>
        </form>
    </div>

    <script>
        // Script melhorado para tags
        const tagsInput = document.getElementById('tagsInput');
        const hiddenTags = document.getElementById('hiddenTags');
        const tagsList = document.getElementById('tagsList');

        function updateHiddenTags() {
            const tags = Array.from(tagsList.children)
                .map(tag => tag.textContent.replace('×', '').trim());
            hiddenTags.value = tags.join(',');
        }

        function createTagElement(tagName) {
            const tag = document.createElement('div');
            tag.className = 'tag-item';
            tag.innerHTML = `
                ${tagName}
                <span class="remove-tag" onclick="this.parentElement.remove(); updateHiddenTags()">×</span>
            `;
            return tag;
        }

        tagsInput.addEventListener('keydown', (e) => {
            if (['Enter', ',', ';'].includes(e.key)) {
                e.preventDefault();
                const tag = tagsInput.value.trim().replace(/[,;]$/, '');
                if (tag) {
                    tagsList.appendChild(createTagElement(tag));
                    tagsInput.value = '';
                    updateHiddenTags();
                }
            }
        });
    </script>
</body>
</html>