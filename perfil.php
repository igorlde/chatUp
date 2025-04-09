<?php
session_start();
include("connector_database/connector.php");

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION["usuario_id"];
$perfilUserId = $_GET['id'] ?? 0;

// Busca dados do perfil
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.nome,
        u.avatar,
        u.bio,
        EXISTS(
            SELECT 1 
            FROM seguidores 
            WHERE seguidor_id = ? 
            AND seguido_id = u.id
        ) AS seguindo
    FROM users u
    WHERE u.id = ?
");

if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

$stmt->bind_param("ii", $currentUserId, $perfilUserId);

if (!$stmt->execute()) {
    die("Erro na execução: " . $stmt->error);
}

$result = $stmt->get_result(); // Obter o resultado
$perfilUser = $result->fetch_assoc(); // Usar fetch_assoc() no resultado

$stmt->close();

// Verificar se o usuário foi encontrado
if (!$perfilUser) {
    die("Usuário não encontrado");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/perfil.css">
</head>
<a href="main.php">voltar</a>
<!-- Conteúdo do perfil -->
<div class="perfil-header">
        <img src="uploads/avatars/<?= $perfilUser['avatar'] ?>" class="profile-pic-large">
        <h1><?= htmlspecialchars($perfilUser['nome']) ?></h1>
        
        <?php if ($perfilUser['id'] != $currentUserId): ?>
            <form method="POST" action="seguir.php">
                <input type="hidden" name="seguido_id" value="<?= $perfilUser['id'] ?>">
                <?php if ($perfilUser['seguindo']): ?>
                    <button type="submit" name="acao" value="unfollow" class="btn-unfollow">
                        Deixar de Seguir
                    </button>
                <?php else: ?>
                    <button type="submit" name="acao" value="follow" class="btn-follow">
                        Seguir
                    </button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>