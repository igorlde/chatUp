<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/connector_database/connector.php';
require __DIR__ . '/funcoes/busca-user.php';

validar_usuario();

try {
    $searchResults = processar_busca($conn);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    $searchResults = [];
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Resultados da Busca</title>
    <link rel="stylesheet" href="style/busca.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style/newsidebar.css">
</head>

<body>
  <div class="layout">
    <?php include("sidebar/newsidebar.php"); ?>



      <main class="container">

      <div class="conteudo">
      <nav>
        <?php include("sidebar/side-busca.php") ?>
      </nav>
          <h1>Resultados para "<?= htmlspecialchars($_POST['User_name'] ?? '') ?>"</h1>

          <?php if (!empty($searchResults)): ?>
              <div class="user-list">
                  <?php foreach ($searchResults as $user): ?>
                      <div class="user-card">
                          <img src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default-avatar.jpg') ?>"
                              alt="Avatar"
                              class="profile-pic-small"
                              onerror="this.src='uploads/avatars/default-avatar.jpg'">
                          <div class="user-info">
                              <h3><?= htmlspecialchars($user['nome']) ?></h3>
                              <?php if ($user['seguindo']): ?>
                                  <span class="badge-seguindo">Seguindo</span>
                              <?php endif; ?>
                          </div>
                          <div class="action-buttons">
                              <a href="perfil.php?id=<?= $user['id'] ?>" class="btn-visualizar">Ver Perfil</a>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php else: ?>
              <div class="no-results">
                  <?php if (!empty($_GET['User_name'])): ?>
                      <p>Nenhum resultado para "<?= htmlspecialchars($_POST['User_name']) ?>"</p>
                  <?php else: ?>
                      <p>Digite um nome para buscar</p>
                  <?php endif; ?>
              </div>
          <?php endif; ?>
      </main>
    </div>
  </div>
</body>

</html>
