<?php
session_start();
require_once __DIR__ . '/../connector_database/connector.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_login']) && isset($_POST['senha_login'])) {
    $email = $_POST['email_login'] ?? null;
    $senha = $_POST['senha_login'] ?? null;

    if (!$email || !$senha) {
        $_SESSION['erro'] = "Preencha todos os campos";
        header("Location: login.php");
        exit;
    }

    try {
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

        $sql->close();
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
    <title>Login | ChatUp</title>
    <!--esse daqui e de quando inicia-->
    <link rel="stylesheet" href="style/login.css">
    <!-- não estranha tive que fazer essa duplicação pois não estava funcionado quando clico em sair, o css-->
    <link rel="stylesheet" href="../style/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container-login">
        <div class="sidebar">
            <h1 class="logo-text">ChatUp</h1>
            <div class="frases">
                <span class="frase ativa">Publique fotos e vídeos</span>
                <span class="frase">Troque mensagens com seus amigos</span>
                <span class="frase">Divirta-se no ChatUp</span>
            </div>
        </div>

        <div class="form-area">
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="erro"><?= $_SESSION['erro'] ?></div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <form action="login/login.php" method="post">
                <div>
                    <label for="email_login">Email</label>
                    <input type="email" name="email_login" id="email_login" required>
                </div>
                <div>
                    <label for="senha_login">Senha</label>
                    <input type="password" name="senha_login" id="senha_login" required>
                </div>
                <div>
                    <input type="submit" value="Entrar">
                </div>
            </form>

            <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>

    <script>
        const frases = document.querySelectorAll(".frase");
        let index = 0;

        setInterval(() => {
            frases.forEach(f => f.classList.remove("ativa"));
            index = (index + 1) % frases.length;
            frases[index].classList.add("ativa");
        }, 3000);
    </script>
</body>

</html>