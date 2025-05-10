<?php
require __DIR__ . '/../connector_database/connector.php';
function processarPostagem(
    mysqli $conn,
    string $pastaUploadsFisica,
    string $pastaUploadsWeb,
    string $redirectSucesso = 'main.php',
    string $redirectErro = 'post.php',
    int $tamanhoMaximoImagem = 31457280, // 30MB
    int $tamanhoMaximoVideo = 62914560,   // 60MB
    int $maximoImagens = 5
): void {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") return;

    try {
        // Configurações iniciais
        $dados = [
            'video' => null,
            'imagem_capa' => null,
            'imagens_adicionais' => [],
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'titulo' => trim($_POST["titulo"] ?? ''),
            'tema' => $_POST["tema"] ?? null,
            'descricao' => $_POST["descricao"] ?? null,
            'conteudo' => trim($_POST["conteudo"] ?? ''),
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))
        ];

        // Validações básicas
        $erros = [];
        if (empty($dados['titulo'])) $erros[] = "Título é obrigatório";
        if (empty($dados['conteudo'])) $erros[] = "Conteúdo não pode estar vazio";
        foreach ($dados['tags'] as $tag) {
            if (mb_strlen($tag) > 30) $erros[] = "Tag '{$tag}' excede 30 caracteres";
        }

        // Gerenciamento de arquivos
        if (!file_exists($pastaUploadsFisica)) {
            mkdir($pastaUploadsFisica, 0755, true);
        }

        // Processar vídeo
        if (!empty($_FILES['video']['tmp_name'])) {
            $video = $_FILES['video'];
            $dados['video'] = processarVideo(
                $video,
                $pastaUploadsFisica,
                $pastaUploadsWeb,
                $tamanhoMaximoVideo
            );
        }

        // Processar capa
        if (!empty($_FILES['imagem_capa']['tmp_name'])) {
            $dados['imagem_capa'] = processarImagem(
                $_FILES['imagem_capa'],
                $pastaUploadsFisica,
                $pastaUploadsWeb,
                $tamanhoMaximoImagem,
                'capa_'
            );
        }

        // Processar imagens adicionais
        if (!empty($_FILES['imagens_adicionais']['tmp_name'][0])) {
            $dados['imagens_adicionais'] = processarImagensMultiplas(
                $_FILES['imagens_adicionais'],
                $pastaUploadsFisica,
                $pastaUploadsWeb,
                $tamanhoMaximoImagem,
                $maximoImagens
            );
        }

        // Validar erros acumulados
        if (!empty($erros)) {
            throw new Exception(implode("\n", $erros));
        }

        // Transação de banco de dados
        $conn->begin_transaction();

        // Inserir post principal
        $stmt = $conn->prepare("INSERT INTO posts (usuario_id, titulo, tema, descricao, conteudo, imagem_capa, video) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssss",
            $dados['usuario_id'],
            $dados['titulo'],
            $dados['tema'],
            $dados['descricao'],
            $dados['conteudo'],
            $dados['imagem_capa'],
            $dados['video']
        );
        $stmt->execute();
        $postId = $conn->insert_id;

        // Inserir imagens adicionais
        if (!empty($dados['imagens_adicionais'])) {
            $stmtImagens = $conn->prepare("INSERT INTO post_imagens (post_id, caminho_arquivo) VALUES (?, ?)");
            foreach ($dados['imagens_adicionais'] as $imagem) {
                $stmtImagens->bind_param("is", $postId, $imagem);
                $stmtImagens->execute();
            }
        }

        // Processar tags
        processarTags($conn, $postId, $dados['tags']);

        $conn->commit();
        $_SESSION['sucesso_post'] = "Post criado com sucesso!";
        header("Location: $redirectSucesso");
        exit;
    } catch (Exception $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }
        $_SESSION['erros_post'] = explode("\n", $e->getMessage());
        header("Location: $redirectErro");
        exit;
    }
}

// Funções auxiliares
function processarVideo(
    array $video,
    string $pastaFisica,
    string $pastaWeb,
    int $tamanhoMaximo
): string {
    if ($video['size'] > $tamanhoMaximo) {
        throw new Exception("Vídeo excede o tamanho máximo permitido");
    }

    $tiposPermitidos = ['video/mp4', 'video/webm', 'video/quicktime'];
    if (!in_array($video['type'], $tiposPermitidos)) {
        throw new Exception("Formato de vídeo não suportado");
    }

    $nomeSanitizado = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $video['name']);
    $nomeArquivo = uniqid('video_') . '_' . $nomeSanitizado;
    $caminhoCompleto = $pastaFisica . $nomeArquivo;

    if (!move_uploaded_file($video['tmp_name'], $caminhoCompleto)) {
        throw new Exception("Falha ao salvar vídeo no servidor");
    }

    return $pastaWeb . $nomeArquivo;
}

function processarImagem(
    array $arquivo,
    string $pastaFisica,
    string $pastaWeb,
    int $tamanhoMaximo,
    string $prefixo = 'post_'
): string {
    if ($arquivo['size'] > $tamanhoMaximo) {
        throw new Exception("Imagem excede o tamanho máximo permitido");
    }

    $tiposPermitidos = ['image/jpeg', 'image/png'];
    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        throw new Exception("Formato de imagem não suportado");
    }

    $nomeSanitizado = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $arquivo['name']);
    $nomeArquivo = uniqid($prefixo) . '_' . $nomeSanitizado;
    $caminhoCompleto = $pastaFisica . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        throw new Exception("Falha ao salvar imagem no servidor");
    }

    return $pastaWeb . $nomeArquivo;
}

function processarImagensMultiplas(
    array $arquivos,
    string $pastaFisica,
    string $pastaWeb,
    int $tamanhoMaximo,
    int $maximoImagens
): array {
    if (count($arquivos['tmp_name']) > $maximoImagens) {
        throw new Exception("Número máximo de imagens excedido");
    }

    $resultado = [];
    foreach ($arquivos['tmp_name'] as $key => $tmpName) {
        $arquivo = [
            'name' => $arquivos['name'][$key],
            'type' => $arquivos['type'][$key],
            'tmp_name' => $tmpName,
            'size' => $arquivos['size'][$key]
        ];
        $resultado[] = processarImagem($arquivo, $pastaFisica, $pastaWeb, $tamanhoMaximo);
    }
    return $resultado;
}

function processarTags(mysqli $conn, int $postId, array $tags): void
{
    $tags = array_unique($tags);
    $tagStmt = $conn->prepare("INSERT IGNORE INTO tags (nome_tag) VALUES (?)");
    $relStmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");

    foreach ($tags as $tag) {
        // Inserir tag se não existir
        $tagStmt->bind_param("s", $tag);
        $tagStmt->execute();

        // Obter ID da tag
        $resultado = $conn->query("SELECT id FROM tags WHERE nome_tag = '{$conn->real_escape_string($tag)}'");
        $tagId = $resultado->fetch_assoc()['id'];

        // Vincular ao post
        $relStmt->bind_param("ii", $postId, $tagId);
        $relStmt->execute();
    }
}
