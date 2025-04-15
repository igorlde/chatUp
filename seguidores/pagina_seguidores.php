<?php
session_start();
require_once __DIR__ . '/seguidores.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['usuario_id'];
$total_seguidores = getSeguidoresCount($user_id, $conn);
$lista_seguidores = getListSeguidores($user_id, $conn); // Variável corrigida
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Seguidores</title>
    <link rel="stylesheet" href="../style/style_seguidores.css">
</head>

<body>
    <div class="container">
        <h1 class="titulo-principal">👥 Meus Seguidores (<?= $total_seguidores ?>)</h1>

        <a href="../perfil.php?id=<?= $user_id ?>" class="botao-voltar">← Voltar ao Perfil</a>
        <div class="lista-seguidores">
            <?php if (empty($lista_seguidores)): ?> <!-- Variável corrigida -->
                <p class="sem-resultados">Você ainda não tem seguidores.</p>
            <?php else: ?>
                <?php foreach ($lista_seguidores as $seguidor): ?> <!-- Variável corrigida -->
                    <div class="card-seguidor">
                        <img src="uploads/avatars/<?= htmlspecialchars($seguidor['avatar']) ?>"
                            alt="<?= htmlspecialchars($seguidor['nome_usuario'] ?? 'Usuário sem nome') ?>"
                            class="avatar">
                        <div class="info">
                            <h3><?= htmlspecialchars($seguidor['nome_usuario'] ?? 'Usuário sem nome') ?></h3>
                            <a href="../perfil.php?id=<?= $seguidor['id'] ?>" class="link-perfil">Ver Perfil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>