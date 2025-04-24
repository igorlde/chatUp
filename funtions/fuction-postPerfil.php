<?php 
require_once __DIR__ . '/../connector_database/connector.php';

try {
    // Verifica se o ID do perfil foi passado via GET
    if(!isset($_GET['id'])) {
        if(isset($_SESSION['usuario_id'])) {
            header("Location: perfil.php");
            exit;
        } else {
            header("Location: login.php");
            exit;
        }
    }

    $perfil_id = (int) $_GET['id'];
    
    // Busca informações do usuário dono do perfil
    $sql = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $sql->bind_param("i", $perfil_id);
    $sql->execute();
    $result = $sql->get_result();
    
    if($result->num_rows === 0) {
        die("Perfil não encontrado");
    }
    
    $usuario_perfil = $result->fetch_assoc(); 
    
    // Busca os posts do usuário
    $sql_postUser = $conn->prepare("SELECT * FROM posts WHERE usuario_id = ?");
    $sql_postUser->bind_param("i", $perfil_id); // Usa o ID do perfil buscado
    $sql_postUser->execute();
    $posts = $sql_postUser->get_result();

} catch(Exception $e) {
    die("Erro: " . $e->getMessage()); // Melhor tratamento de erro
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/postsPerfil.css">
    <title>Perfil</title>
</head>
<body>
    <!-- Exibição dos posts -->
    <div class="posts">
        <?php while($post = $posts->fetch_assoc()): ?>
            <div class="post">
                <?php if(!empty($post['imagem_capa'])): ?>
                    <img src="<?= $post['imagem_capa'] ?>" alt="Imagem do post">
                <?php endif; ?>
                
                <?php if(!empty($post['video'])): ?>
                    <video controls>
                        <source src="<?= $post['video'] ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>