<?php
session_start();
include("connector_database/connector.php");

// Verificação de autenticação melhorada
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erros_post'] = ["Acesso não autorizado. Faça login primeiro."];
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Valores com sanitização
        $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
        $tema = filter_input(INPUT_POST, 'tema', FILTER_SANITIZE_SPECIAL_CHARS);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
        $conteudo = filter_input(INPUT_POST, 'conteudo', FILTER_SANITIZE_SPECIAL_CHARS);
        $usuario_id = $_SESSION['usuario_id'];

        // Validação reforçada
        $erros = [];
        
        if (empty($titulo) || strlen($titulo) > 100) {
            $erros[] = "Título inválido (1-100 caracteres)";
        }
        
        if (empty($conteudo)) {
            $erros[] = "Conteúdo obrigatório";
        }

        if (!empty($erros)) {
            $_SESSION['erros_post'] = $erros;
            header("Location: post.php");
            exit;
        }

        // Transação segura
        $conn->begin_transaction();

        // Query com tratamento de imagem (mesmo que não seja enviada)
        $stmt = $conn->prepare("INSERT INTO posts 
            (usuario_id, titulo, tema, descricao, conteudo, imagem) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        // Valor padrão para imagem
        $imagem = null;

        // Processamento básico de imagem
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $diretorio_uploads = 'uploads/';
            if (!is_dir($diretorio_uploads)) {
                mkdir($diretorio_uploads, 0755, true);
            }
            
            $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '.' . $extensao;
            $caminho_completo = $diretorio_uploads . $nome_arquivo;
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
                $imagem = $caminho_completo;
            }
        }

        $stmt->bind_param(
            "isssss",
            $usuario_id,
            $titulo,
            $tema,
            $descricao,
            $conteudo,
            $imagem
        );

        if (!$stmt->execute()) {
            throw new Exception("Erro no banco de dados: " . $stmt->error);
        }

        $conn->commit();
        
        $_SESSION['sucesso_post'] = "Post publicado com sucesso!";
        header("Location: main.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("ERRO: " . $e->getMessage());
        $_SESSION['erros_post'] = ["Erro ao processar o post: " . $e->getMessage()];
        header("Location: post.php");
        exit;
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Post</title>
    <style>
        /* Estilos otimizados */
        .post-creator {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0,123,255,0.2);
        }

        .tag-system {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 6px;
        }

        .tag-item {
            background: #e3f2fd;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remove-tag {
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }

        .remove-tag:hover {
            color: #ff4444;
        }

        .submit-btn {
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="post-creator">
        <!-- Sistema de mensagens -->
        <?php if (!empty($_SESSION['erros_post'])): ?>
            <div class="error-message">
                <?php foreach ($_SESSION['erros_post'] as $erro): ?>
                    <p><?= htmlspecialchars($erro) ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['erros_post']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['sucesso_post'])): ?>
            <div class="success-message">
                <p><?= htmlspecialchars($_SESSION['sucesso_post']) ?></p>
                <?php unset($_SESSION['sucesso_post']); ?>
            </div>
        <?php endif; ?>

        <h1>Criar Novo Post</h1>
        <form action="post.php" method="POST" enctype="multipart/form-data">
            <!-- Campos do formulário -->
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" required 
                    value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="tema">Categoria:</label>
                <select id="tema" name="tema">
                    <option value="">Selecione...</option>
                    <option value="Tecnologia" <?= ($_POST['tema'] ?? '') === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia</option>
                    <option value="Educação" <?= ($_POST['tema'] ?? '') === 'Educação' ? 'selected' : '' ?>>Educação</option>
                    <option value="Lifestyle" <?= ($_POST['tema'] ?? '') === 'Lifestyle' ? 'selected' : '' ?>>Lifestyle</option>
                </select>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="conteudo">Conteúdo:</label>
                <textarea id="conteudo" name="conteudo" required><?= htmlspecialchars($_POST['conteudo'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Tags:</label>
                <div class="tag-system">
                    <input type="text" id="tag-input" placeholder="Adicione tags...">
                    <input type="hidden" name="tags" id="hidden-tags" 
                        value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="imagem">Imagem Destaque:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">
            </div>

            <button type="submit" class="submit-btn">Publicar Post</button>
        </form>
    </div>

    <script>
        // Sistema de tags aprimorado
        document.addEventListener('DOMContentLoaded', () => {
            const tagInput = document.getElementById('tag-input');
            const hiddenTags = document.getElementById('hidden-tags');
            const tagSystem = document.querySelector('.tag-system');

            function updateTags() {
                const tags = Array.from(document.querySelectorAll('.tag-item'))
                    .map(tag => tag.textContent.replace('×', '').trim());
                hiddenTags.value = tags.join(',');
            }

            tagInput.addEventListener('keydown', (e) => {
                if (['Enter', ','].includes(e.key)) {
                    e.preventDefault();
                    const tag = tagInput.value.trim().replace(/,/g, '');
                    if (tag) {
                        const tagElement = document.createElement('div');
                        tagElement.className = 'tag-item';
                        tagElement.innerHTML = `
                            ${tag}
                            <span class="remove-tag" onclick="this.parentElement.remove(); updateTags()">×</span>
                        `;
                        tagSystem.insertBefore(tagElement, tagInput);
                        tagInput.value = '';
                        updateTags();
                    }
                }
            });

            // Inicializar tags existentes
            if (hiddenTags.value) {
                hiddenTags.value.split(',').forEach(tag => {
                    if (tag.trim()) {
                        const tagElement = document.createElement('div');
                        tagElement.className = 'tag-item';
                        tagElement.innerHTML = `
                            ${tag.trim()}
                            <span class="remove-tag" onclick="this.parentElement.remove(); updateTags()">×</span>
                        `;
                        tagSystem.insertBefore(tagElement, tagInput);
                    }
                });
            }
        });
    </script>
</body>
</html>