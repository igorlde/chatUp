<?php
session_start();
include("connector_database/connector.php");
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
$meu_id = $_SESSION['usuario_id'];
$selecionar_usuario_id = isset($_GET['user']) ? (int) $_GET['user'] : null;

// Correção 1: Buscar TODOS os usuários seguidos$seguidores_lista = [];
$stmt_seguidores = $conn->prepare("
SELECT u.id, u.nome_usuario, u.avatar 
FROM users u 
JOIN seguidores f ON u.id = f.seguido_id 
WHERE f.seguidor_id = ?
");
$stmt_seguidores->bind_param("i", $meu_id); // "i" = inteiro
$stmt_seguidores->execute();
$result_seguidores = $stmt_seguidores->get_result();
$seguidores_lista = $result_seguidores->fetch_all(MYSQLI_ASSOC);
$mensagems = [];

if ($selecionar_usuario_id) {
    // Query para mensagens em ambas as direções
    $stmt_mensagens = $conn->prepare("
        SELECT * 
        FROM mensagens 
        WHERE (remetente_id = ? AND destinatario_id = ?) 
        OR (remetente_id = ? AND destinatario_id = ?) 
        ORDER BY data_envio ASC
    ");
    // Vincular 4 parâmetros (dois pares de IDs)
    $stmt_mensagens->bind_param("iiii", $meu_id, $selecionar_usuario_id, $selecionar_usuario_id, $meu_id);
    $stmt_mensagens->execute();
    $result_mensagens = $stmt_mensagens->get_result();
    $mensagems = $result_mensagens->fetch_all(MYSQLI_ASSOC);
}

$stmt_usuario = $conn->prepare("SELECT nome_usuario, avatar FROM users WHERE id = ?");
$stmt_usuario->bind_param("i", $selecionar_usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$conversa_usuario = $result_usuario->fetch_assoc(); // Apenas 1 registro
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style/newsidebar.css">

    <?php include("sidebar/newsidebar.php"); ?>

</head>

<main>
<body>

    <div class="chat-container">
        <div class="chat-sidebar">
            <h3>Mensagens</h3>
            <a href="main.php">voltar</a>
            <ul class="user-list">
                <?php foreach ($seguidores_lista as $usuario): ?>
                    <li class="user-item">
                        <a href="perfil.php?id=<?= $usuario['id'] ?>" class="profile-link">
                            <img src="uploads/avatars/<?= $usuario['avatar'] ?? 'default.jpg' ?>" class="user-avatar">
                        </a>
                        <a href="chat.php?user=<?= $usuario['id'] ?>" class="chat-link">
                            <?= htmlspecialchars($usuario['nome_usuario']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="chat-main">
            <?php if ($selecionar_usuario_id && $conversa_usuario): ?>
                <div class="chat-header">
                    <a href="perfil.php?id-<?= $selecionar_usuario_id ?>">
                        <img src="uploads/avatars/<?= $conversa_usuario['avatar'] ?? '' ?>" class="chat-avatar">
                    </a>
                    <h4>@<?= htmlspecialchars($conversa_usuario['nome_usuario']) ?></h4>
                </div>
                <div class="chat-menssages">
                    <?php foreach ($mensagems as $msg): ?>
                        <div class="chat-message<?= $msg['remetente_id'] == $meu_id ? 'sent' : 'destinatario' ?>">
                            <?= htmlspecialchars($msg['mensagem']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form action="funcoes/send_menssage.php" method="post" class="chat-form">
                    <input type="hidden" name="receiver_id" value="<?= $selecionar_usuario_id ?>">
                    <input type="text" name="msg" placeholder="Digite sua mensagem" required>
                    <button type="submit">Enviar</button>
                </form>
            <?php else: ?>
                <p>Selecionar uma conversa no meu menu á esquerda.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Verifica se o parâmetro user existe
        let destinatario = <?php echo isset($_GET['user']) ? (int)$_GET['user'] : 'null'; ?>;

        if (destinatario) {
            function fetchMessages() {
                // Corrigido: caminho absoluto para fetch_messages.php
                fetch('/chatup/funcoes/fetch_messages.php?user=' + destinatario)
                    .then(res => res.json())
                    .then(data => {
                        const messagesContainer = document.querySelector('.chat-menssages');
                        messagesContainer.innerHTML = data.html; // Campo correto: 'html'
                        messagesContainer = messagesContainer.scrollHeight;
                    });
            }

            // Envia mensagem com AJAX
            document.querySelector('.chat-form').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.append('receiver_id', destinatario);

                // Corrigido: caminho e nome do arquivo
                fetch('/ChatUp/funcoes/send_menssage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.reset();
                            fetchMessages();
                        }
                    });
            });

            // Atualiza mensagens a cada 2 segundos
            setInterval(fetchMessages, 500);
            fetchMessages();
        }
    </script>
</body>
</main>

</html>