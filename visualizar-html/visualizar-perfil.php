<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="../style/editar-perfil.css">
</head>
<body>
    <header>
        <a href="../main.php">Voltar</a>
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
            <img src="/chatup/uploads/avatars/<?= htmlspecialchars($usuario['avatar'] ?? 'default.jpg') ?>" 
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