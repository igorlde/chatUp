<?php
session_start();
include("connector_database/connector.php");

//verficação a mais oara ver o id 
if (!isset($_GET['id'])){
    // Se nenhum ID foi passado, redirecione para o próprio perfil do usuário
    if (isset($_SESSION['usuario_id'])) {
        header("Location: perfil.php?id=" . $_SESSION['usuario_id']);
    } else {
        header("Location: login.php");
    }
    exit;
}

$perfil_id = (int)$_GET['id'];

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $perfil_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Perfil não encontrado");
}

//criando a associção do usuario.
$usuario = $result->fetch_assoc();

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
    <!--Erro resolvido simplemente era o value que estava com o nome errado ai não passava informação para o seguir.php. principalmente na variavel $acao kkk-->
    <?php if ($perfilUser['id'] != $currentUserId): ?>
        <form method="POST" action="seguir.php">
            <input type="hidden" name="seguido_id" value="<?= $perfilUser['id'] ?>">
            <?php if ($perfilUser['seguindo']): ?>
                <button type="submit" name="acao" value="Deixar de Seguir" class="btn-unfollow">
                    Deixar de Seguir
                </button>
            <?php else: ?>
                <button type="submit" name="acao" value="Seguir" class="btn-follow">
                    Seguir
                </button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
    <form action="seguidores/pagina_seguidores.php" method="get" class="botao-container">
        <button type="submit" class="botao-primario">
            👥 Seguidores
        </button>
    </form>

    <form action="seguidores/pagina_seguido.php" method="get" class="botao-container">
        <button type="submit" class="botao-secundario">
            🔍 Seguindo
        </button>
    </form>
</div>
</body>

</html>