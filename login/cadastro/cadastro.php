<?php
session_start();
include("../../connector_database/connector.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome_cadastrado"] ?? null;
    $nome_usuario = $_POST["nome_usuario"] ?? null;
    $email = $_POST["email_cadastro"] ?? null;
    $senha = $_POST["senha_cadastro"] ?? null;
    $confirma_senha = $_POST["senha_confirma"] ?? null;
    $data_nacimento = $_POST["data_nacimento"] ?? null;

    if (!$nome || !$nome_usuario || !$email || !$senha || !$confirma_senha || !$data_nacimento) {
        die("ERRO: preencha todos os campos por favor");
    }

    if ($senha !== $confirma_senha) {
        die("As senhas não são iguais");
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verificar se a conexão está aberta
    if ($conn->connect_error) {
        die("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }

    // Preparar a consulta para inserir dados no banco
    $sql = $conn->prepare("INSERT INTO users (nome, nome_usuario, email, senha, data_nacimento) VALUES (?, ?, ?, ?, ?)");
    $sql->bind_param("sssss", $nome, $nome_usuario, $email, $senha_hash, $data_nacimento);

    if ($sql->execute()) {
        header('Location: ../login.php'); 
        exit; // Certificar-se de que o script pare após o redirecionamento
    } else {
        echo "Erro ao cadastrar: " . $conn->error;
    }

    // Fechar a conexão após a execução do código
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
</head>

<body>
    <form action="cadastro.php" method="post">
        <div>
            <label for="nome">Nome</label>
            <input type="text" name="nome_cadastrado" id="nome_cadastrado">
        </div>
        <div>
            <label for="nome_usuario">Nome de usuário</label>
            <input type="text" name="nome_usuario" id="nome_usuario">
        </div>
        <div>
            <label for="email_cadastro">Email</label>
            <input type="email" name="email_cadastro" id="email_cadastro">
        </div>
        <div>
            <label for="senha_cadastro">Senha</label>
            <input type="password" name="senha_cadastro" id="senha_cadastro">
        </div>
        <div>
            <label for="senha_confirma">Confirma senha</label>
            <input type="password" name="senha_confirma" id="senha_confirma">
        </div>
        <div>
            <label for="data_nacimento">Data de Nascimento</label>
            <input type="date" name="data_nacimento" id="data_nacimento">
        </div>
        <div>
            <input type="submit" value="Cadastrar">
        </div>
    </form>
</body>

</html>