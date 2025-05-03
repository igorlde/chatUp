<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");
include("funcoes/busca-user.php");
validar_usuario();
$searchResults = processar_busca($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resultados da Busca</title>
    <link rel="stylesheet" href="style/busca.css">
</head>
<body>
<a href="main.php">voltar</a>
    <main class="container">
        <h1>Resultados para "<?= htmlspecialchars($_POST['User_name'] ?? '') ?>"</h1>
        
        <?php if (!empty($searchResults)): ?>
            <div class="user-list">
                <?php foreach ($searchResults as $user): ?>
                    <div class="user-card">
                        <img src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default-avatar.jpg') ?>" 
                             alt="Avatar de <?= htmlspecialchars($user['nome']) ?>" 
                             class="profile-pic-small">
                        <div class="user-info">
                            <h3><?= htmlspecialchars($user['nome']) ?></h3>
                            <div class="action-buttons">
                                <a href="perfil.php?id=<?= $user['id'] ?>" class="btn-visualizar">Ver Perfil</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>Nenhum usuário encontrado com esse nome.</p>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>