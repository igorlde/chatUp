<?php
require __DIR__ . "/../connector_database/connector.php"; // Arquivo de conexão

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

try {
    $user_id = $_SESSION['usuario_id'];
    $sql = "SELECT nome, bio, avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Lateral</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style/newsidebar.css">
</head>

<body>
    <nav id="sidebar">
        <div id="sidebar-content">
            <div id="user">
                <a href="menus/editar-perfil.php"><img src="uploads/avatars/<?=htmlspecialchars($user['avatar'])?>" id="user-avatar" alt="Avatar"></a>
                <p id="user-infos">
                    <span class="item-description">
                        <?php echo htmlspecialchars($user['nome']); ?>
                    </span>
                    <span class="item-description">
                        <?php echo htmlspecialchars($user['bio']); ?>
                    </span>
                </p>
            </div>

            <!-- Restante do menu mantido igual -->
            <ul id="side-items">
                <li class="side-item active">
                    <a href="post.php">
                        <i class="fa-solid fa-plus"></i>
                        <span class="item-description">
                            Novo post
                        </span>
                    </a>
                </li>
                <li class="side-item">
                <a href="busca.php">
                <i class="fa-solid fa-users"></i>
                <span class="item-description">
                    Buscar Usuários
                </span>
                </a>
            </li>

            <!-- Botao buscar post -->
            <li class="side-item">
                <a href="busca-post.php">
                <i class="fa-solid fa-image"></i>
                <span class="item-description">
                    Buscar Post
                </span>
                </a>
            </li>

            <!-- Botao bate papo -->
            <li class="side-item">
                <a href="chat.php">
                <i class="fa-solid fa-comments"></i>
                <span class="item-description">
                    Bate-papo
                </span>
                </a>
            </li>

            <!-- Botao bloco de notas -->
            <li class="side-item">
                <a href="menus/blocoNotas.php">
                <i class="fa-solid fa-table-list"></i>
                <span class="item-description">
                    Bloco de notas
                </span>
                </a>
            </li>
            </ul>

            <button id="open-btn">
                <i id="open-btn-icon" class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <div id="logout">
            <button id="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <a href="index.php"><span class="item-description">
                        Sair
                    </span></a>
            </button>
        </div>
    </nav>

    <main>
    </main>

    <script src="js/script.js"></script>
</body>

</html>