<?php
session_start();
include("../connector_database/connector.php"); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_login']) && isset($_POST['senha_login'])) {
    $email = $_POST['email_login'] ?? null;
    $senha = $_POST['senha_login'] ?? null;

    if (!$email || !$senha) {
        $_SESSION['erro'] = "Preencha todos os campos";
        header("Location: login.php");
        exit;
    }

    try {
        // Verifica se a conexão está ativa
        if (!$conn || $conn->connect_error) {
            throw new Exception("Erro na conexão com o banco de dados");
        }

        $sql = $conn->prepare("SELECT id, nome_usuario, senha FROM users WHERE email = ?");
        $sql->bind_param("s", $email);
        
        if (!$sql->execute()) {
            throw new Exception("Erro na execução da consulta");
        }

        $result = $sql->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (password_verify($senha, $row["senha"])) {
                $_SESSION["usuario_id"] = $row["id"];
                $_SESSION["nome_usuario"] = $row["nome_usuario"];
                header("Location: ../main.php");
                exit;
            } else {
                $_SESSION['erro'] = "Credenciais inválidas";
            }
        } else {
            $_SESSION['erro'] = "Credenciais inválidas";
        }

        $sql->close(); //fecha a conexão

    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro no servidor: " . $e->getMessage();
    } finally {
        header("Location: login.php");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style/style.css">
</head>

<body>
    <?php if (isset($_SESSION['erro'])): ?>
        <div class="erro"><?= $_SESSION['erro'] ?></div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div>
            <label for="email_login">Email</label>
            <input type="email" name="email_login" id="email_login" required>
        </div>
        <div>
            <label for="senha_login">Senha</label>
            <input type="password" name="senha_login" id="senha_login" required>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
    </form>

    <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</body>

</html>