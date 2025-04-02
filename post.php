<?php
session_start();
include("connector_database/connector.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Valores padrão seguros
    $titulo = trim($_POST["titulo"] ?? '');
    $tema = trim($_POST["tema"] ?? null);
    $descricao = trim($_POST["descricao"] ?? null);
    $conteudo = trim($_POST["conteudo"] ?? '');
    $usuario_id = $_SESSION['usuario_id'] ?? null; // Supondo que o usuário está logado

    // Validação básica
    $erros = [];
    
    if (empty($titulo)) {
        $erros[] = "O título é obrigatório";
    }
    
    if (empty($conteudo)) {
        $erros[] = "O conteúdo não pode estar vazio";
    }
    
    if (!$usuario_id) {
        $erros[] = "Usuário não autenticado";
    }

    if (!empty($erros)) {
        $_SESSION['erros_post'] = $erros;
        header("Location: post.php");
        exit;
    }

    try {
        // Usar transação para segurança
        $conn->begin_transaction();

        // Preparar statement
        $stmt = $conn->prepare("INSERT INTO posts 
            (usuario_id, titulo, tema, descricao, conteudo) 
            VALUES (?, ?, ?, ?, ?)");
        
        // Verificar erro na preparação
        if (!$stmt) {
            throw new Exception("Erro na preparação da query: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param(
            "issss",  // Tipos: i (integer), s (string)
            $usuario_id,
            $titulo,
            $tema,
            $descricao,
            $conteudo
        );

        // Executar
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução: " . $stmt->error);
        }

        // Obter ID do novo post
        $novo_post_id = $conn->insert_id;

        // Aqui viria o código para processar as tags e imagem...

        $conn->commit();
        
        $_SESSION['sucesso_post'] = "Post criado com sucesso!";
        header("Location: main.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao criar post: " . $e->getMessage());
        $_SESSION['erros_post'] = ["Erro ao criar o post. Tente novamente."];
        header("Location: post.php");
        exit;
    } finally {
        $stmt->close();
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
        .post-creator {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .tags-input {
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .tag-item {
            display: inline-block;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin: 0.25rem;
            font-size: 0.9rem;
        }

        .submit-btn {
            background: #28a745;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #218838;
        }

        .form-note {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="post-creator">
        <h1>Criar Novo Post</h1>
        <form action="/posts" method="POST" enctype="multipart/form-data">
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

            <!-- Tags -->
            <div class="form-group">
                <label>Tags:</label>
                <div class="tags-input">
                    <div class="tag-item">Tecnologia <span class="remove-tag">×</span></div>
                    <input type="text" id="tags" name="tags" 
                        placeholder="Digite tags separadas por vírgula" 
                        style="border: none; background: transparent; width: auto;">
                </div>
                <div class="form-note">Exemplo: programação, web-development, frontend</div>
            </div>

            <!-- Upload de Imagem -->
            <div class="form-group">
                <label for="imagem">Imagem Destaque:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">
                <div class="form-note">Formatos suportados: JPG, PNG, GIF (máx. 5MB)</div>
            </div>

            <button type="submit" class="submit-btn">Publicar Post</button>
        </form>
    </div>

    <script>
        // Script básico para gerenciamento de tags
        const tagsInput = document.querySelector('#tags');
        const tagsContainer = document.querySelector('.tags-input');
        
        tagsInput.addEventListener('keydown', (e) => {
            if (e.key === ',' || e.key === 'Enter') {
                e.preventDefault();
                const tag = tagsInput.value.trim().replace(/,/g, '');
                if (tag) {
                    const tagElement = document.createElement('div');
                    tagElement.className = 'tag-item';
                    tagElement.innerHTML = `
                        ${tag}
                        <span class="remove-tag" onclick="this.parentElement.remove()">×</span>
                    `;
                    tagsContainer.insertBefore(tagElement, tagsInput);
                    tagsInput.value = '';
                }
            }
        });
    </script>
</body>
</html>