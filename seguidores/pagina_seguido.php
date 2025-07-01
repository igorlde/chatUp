<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/seguidores.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['usuario_id'];
$total_seguindo = getSeguindoCount($user_id, $conn);
$lista_seguindo = getListSeguindo($user_id, $conn);

// DEBUG (remova após teste)
/*
echo "<pre>";
var_dump($lista_seguindo);
echo "</pre>";
*/
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pessoas que Sigo</title>
    <link rel="stylesheet" href="/chatUp/style/style_seguidores.css">
</head>

<body>
    <div class="container">
        <h1 class="titulo-principal">🔍 Pessoas que Sigo (<?= $total_seguindo ?>)</h1>

        <a href="javascript:history.back()" class="botao-voltar">← Voltar ao Perfil</a>

        <div class="lista-seguidores">
            <?php if (empty($lista_seguindo)): ?>
                <p class="sem-resultados">Você não está seguindo ninguém ainda.</p>
            <?php else: ?>
                <?php foreach ($lista_seguindo as $perfil): ?>
                    <div class="card-seguidor">
                        <img src="/chatUp/uploads/avatars/<?= htmlspecialchars($perfil['avatar']) ?>"
                            alt="<?= htmlspecialchars($perfil['nome_usuario'] ?? 'Usuário') ?>"
                            class="avatar">

                        <div class="info">
                            <h3><?= htmlspecialchars($perfil['nome_usuario']) ?></h3>
                            <a href="../perfil.php?id=<?= $perfil['id'] ?>" class="link-perfil">Ver Perfil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>