<?php 
session_start();
include "connector_database/connector.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$erros = [];
//definindo o diretorio
$diretorioUploads = __DIR__ . '/uploads/avatars/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação do nome
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    if (empty($nome)) {
        $erros[] = "O campo nome é obrigatório";
    }
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);

    if (empty($erros)) {
        try {
            // Inicia a transação
            $conn->begin_transaction();

            // Atualização do nome e bio
            $stmt = $conn->prepare("UPDATE users SET nome = ?, bio = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nome, $bio, $usuario_id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar perfil: " . $stmt->error);
            }
            $stmt->close();

            // Processar upload de avatar, se houver arquivo
            if (!empty($_FILES['avatar']['name'])) {
                // Verifica se o diretório existe, senão cria
                if (!file_exists($diretorioUploads)) {
                    mkdir($diretorioUploads, 0755, true);
                }
                
                $extensao = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid('avatar_') . '.' . $extensao;
                $caminhoCompleto = $diretorioUploads . $nomeArquivo;
                
                // Validações de tamanho e tipo
                $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
                $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
                
                if ($_FILES['avatar']['size'] > $tamanhoMaximo) {
                    throw new Exception("Arquivo muito grande (máx. 2MB)");
                } 
                if (!in_array($_FILES['avatar']['type'], $tiposPermitidos)) {
                    throw new Exception("Formato inválido (use JPG, PNG ou WEBP)");
                }
                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $caminhoCompleto)) {
                    throw new Exception("Erro ao fazer upload");
                }
                
                // Atualiza o avatar no banco
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->bind_param("si", $nomeArquivo, $usuario_id);
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar avatar: " . $stmt->error);
                }
                $stmt->close();
            }

            // Se tudo ocorreu bem, commita a transação
            $conn->commit();
            $_SESSION['sucesso'] = "Perfil atualizado!";
            header("Location: main.php?id=" . $usuario_id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <style>
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        .erros p {
            color: red;
        }
    </style>
</head>
<body>
    <header>
        <a href="main.php">Voltar</a>
    </header>
    <h1>Editar Perfil</h1>
    
    <?php if (!empty($erros)): ?>
        <div class="erros">
            <?php foreach ($erros as $erro): ?>
                <p><?= htmlspecialchars($erro) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Foto do Perfil:</label><br>
            <img src="uploads/avatars/<?= htmlspecialchars($usuario['avatar'] ?? '') ?>" 
                 class="avatar-preview"
                 alt="Preview do avatar">
            <input type="file" name="avatar" accept="image/*">
        </div>

        <div class="form-group">
            <label>Nome:</label>
            <input type="text" name="nome" 
                   value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" 
                   required>
        </div>

        <div class="form-group">
            <label>Bio:</label>
            <textarea name="bio" rows="4"><?= htmlspecialchars($usuario['bio'] ?? '') ?></textarea>
        </div>

        <button type="submit">Salvar Alterações</button>
    </form>
</body>
</html>
