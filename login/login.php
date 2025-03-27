<?php
session_start();
include("../connector_database/connector.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_login']) && isset($_POST['senha_login'])) {
    $email = $_POST['email_login'] ?? null;
    $senha = $_POST['senha_login'] ?? null;

    if (!$email || !$senha) {
        die("ERRO: Preencha todos os campos.");
    }

    $sql = $conn->prepare("SELECT * FROM logins WHERE email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($senha, $row["senha"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["nome_usuario"] = $row["nome_usuario"];
            header("Location: dashboard.php"); // Redirecionar para página de sucesso
            exit;
        } else {
            echo "Senha incorreta.";
        }
    } else {
        echo "Usuário não encontrado.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
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

    <!-- Formulário para redirecionar para cadastro.php -->
    <form action="cadastro/cadastro.php" method="get">
        <div>
            <input type="submit" value="Cadastrar">
        </div>
    </form>
</body>

</html>
