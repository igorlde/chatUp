<?php
include("connector_database/connector.php");

// Verificando se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if (!$conn || $conn->connect_error) {
    die("Status da conexão: " . ($conn->connect_error ?? "objeto inválido de conexão"));
}

// Consulta para obter nome e avatar do usuário logado
$stmtUser = $conn->prepare("SELECT nome, avatar FROM users WHERE id = ?");
$stmtUser->bind_param("i", $_SESSION["usuario_id"]);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$avatar = !empty($userData['avatar']) ? $userData['avatar'] : 'default-avatar.jpg';
$stmtUser->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web_principal_chatUp</title>
    <link rel="stylesheet" href="style/sidebar.css">
</head>

<body>
    <div class="sidebar">
        <!-- Cabeçalho do sidebar -->
        <div class="sidebar-header">
        <h1 class="logo">ChatUp</h1>
            <a href="perfil.php"><img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Foto de Perfil" class="profile-pic"></a>
            <span><?= htmlspecialchars($userData['nome'] ?? 'Usuário') ?></span>
        </div>

        <!-- Menu do sidebar -->
        <ul class="sidebar-menu">
            <li><a href="post.php">＋ Criar Novo Post</a></li>

            <!-- Formulário de busca de usuários -->
            <li>
                <form method="POST" action="busca.php">
                    <input type="text" name="User_name" placeholder="Buscar usuários...">
                    <button type="submit">🔎</button>
                </form>
            </li>
            <li>
                <form method="POST" action="busca-post.php">
                    <input type="text" name="Title_post" placeholder="Buscar Post">
                    <button type="submit">🔎</button>
                </form>
            </li>

            <!-- Link para conversar -->
            <li>
                <form action="chat.php" method="get">
                    <button type="submit">💬 Bate-papo</button>
                </form>
            </li>
            <li>
                <?php include("funtions/sideBarMenu.php") ?>
            </li>
        </ul>
    </div>
</body>

</html>