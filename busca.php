<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");

// Verifica conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Verifica login
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION["usuario_id"];
$searchResults = [];

// Verifica envio do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['User_name'])) {
    $searchTerm = '%' . trim($_POST['User_name']) . '%';

    // Query corrigida
    $sql = "SELECT 
                u.id, 
                u.nome, 
                u.avatar,
                EXISTS(
                    SELECT 1 
                    FROM seguidores 
                    WHERE seguidor_id = ? 
                    AND seguido_id = u.id
                ) AS seguindo
            FROM users u
            WHERE u.nome LIKE ? 
            OR u.nome_usuario LIKE ? 
            ORDER BY u.nome ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação: " . $conn->error);
    }

    $stmt->bind_param("iss", $currentUserId, $searchTerm, $searchTerm);

    if (!$stmt->execute()) {
        die("Erro na execução: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);

    // Fecha resultados
    $result->close();
    $stmt->close();
}

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
                                
                                <?php if ($user['id'] != $currentUserId): ?>
                                    <form method="POST" action="seguir.php" class="follow-form">
                                        <input type="hidden" name="seguido_id" value="<?= $user['id'] ?>">
                                        <?php if ($user['seguindo']): ?>
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