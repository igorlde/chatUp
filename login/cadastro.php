<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../connector_database/connector.php");

if (!$conn || $conn->connect_error) {
    die("STATUS CONEXÃO: " . ($conn->connect_error ?? "Objeto de conexão inválido"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coletar dados brutos
    $rawData = [
        'nome' => $_POST["nome_cadastrado"] ?? '',
        'nome_usuario' => $_POST["nome_usuario"] ?? '',
        'email' => $_POST["email_cadastro"] ?? '',
        'senha' => $_POST["senha_cadastro"] ?? '',
        'senha_confirma' => $_POST["senha_confirma"] ?? '',
        'data_nascimento' => $_POST["data_nascimento"] ?? '',
    ];

    // Sanitização básica
    $dados = array_map(function ($item) use ($conn) {
        return $conn->real_escape_string(trim($item));
    }, $rawData);

    // Validações
    $erros = [];

    if (empty($dados['nome'])) {
        $erros[] = "Nome obrigatório";
    } elseif (strlen($dados['nome']) > 100) {
        $erros[] = "Nome máximo 100 caracteres";
    }

    if (empty($dados['nome_usuario'])) {
        $erros[] = "Nome de usuário obrigatório";
    }
    if (empty($dados['email'])) {
        $erros[] = "Email obrigatório";
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de email inválido";
    }

    if (strlen($dados['senha']) < 8) {
        $erros[] = "Senha deve ter no mínimo 8 caracteres";
    }

    if ($dados['senha'] !== $dados['senha_confirma']) {
        $erros[] = "As senhas não coincidem";
    }

    if (empty($dados['data_nascimento'])) {
        $erros[] = "Data de nascimento obrigatória";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_nascimento'])) {
        $erros[] = "Formato de data inválido (AAAA-MM-DD)";
    } else {
        $dataNascimento = DateTime::createFromFormat('Y-m-d', $dados['data_nascimento']);
        $dataAtual = new DateTime();
        if ($dataNascimento >= $dataAtual) {
            $erros[] = "Você não pode colocar essa data de nascimento";
        }
    }

    if (!empty($erros)) {
        $_SESSION['erros_cadastro'] = $erros;
        header("Location: /chatUp/login/cadastro.php");
        exit;
    }

    try {
        // Hash da senha
        $senha_hash = password_hash($dados['senha'], PASSWORD_BCRYPT);

        // Início da transação
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO users (nome, nome_usuario, email, senha, data_nascimento) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Preparação falhou: " . $conn->error);
        }

        $stmt->bind_param(
            "sssss",
            $dados['nome'],
            $dados['nome_usuario'],
            $dados['email'],
            $senha_hash,
            $dados['data_nascimento']
        );

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception($stmt->error, $stmt->errno);
        }

        $conn->commit();
        $_SESSION['sucesso'] = "Cadastro realizado com sucesso!";
        header("Location: login.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        if ($errorCode == 1062) {
            $campo = strpos($errorMessage, 'nome_usuario') !== false ? 'Nome de usuário' : 'Email';
            $_SESSION['erros_cadastro'][] = "$campo já está em uso.";
        } else {
            $_SESSION['erros_cadastro'][] = "Erro no banco de dados: $errorMessage";
        }
        header("Location: /chatUp/login/cadastro.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['erros_cadastro'][] = "Erro: " . $e->getMessage();
        header("Location: /chatUp/login/cadastro.php");
        exit;
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="/chatUp/style/cadastro.css"> <!--conectando a style -->
    <style>

    </style>
</head>

<body>
    <div class="logo-container">
        <h1>chatUp</h1>
    </div>

    <h1 class="h1-cadastro">Cadastro de Usuário</h1>

    <?php

    if (isset($_SESSION['erros_cadastro'])) {
        echo '<div class="error">';
        foreach ($_SESSION['erros_cadastro'] as $erro) {
            echo "<p>$erro</p>";
        }
        echo '</div>';
        unset($_SESSION['erros_cadastro']);
    }

    if (isset($_SESSION['sucesso'])) {
        echo '<div class="success">';
        echo "<p>{$_SESSION['sucesso']}</p>";
        echo '</div>';
        unset($_SESSION['sucesso']);
    }
    ?>

    <form method="POST" action="/chatUp/login/cadastro.php">
        <div class="form-group">
            <label for="nome_cadastrado">Nome Completo:</label>
            <input type="text" id="nome_cadastrado" name="nome_cadastrado" required>
        </div>

        <div class="form-group">
            <label for="nome_usuario">Nome de Usuário:</label>
            <input type="text" id="nome_usuario" name="nome_usuario" required>
        </div>

        <div class="form-group">
            <label for="email_cadastro">E-mail:</label>
            <input type="email" id="email_cadastro" name="email_cadastro" required>
        </div>

        <div class="form-group">
            <label for="senha_cadastro">Senha:</label>
            <input type="password" id="senha_cadastro" name="senha_cadastro" required>
        </div>

        <div class="form-group">
            <label for="senha_confirma">Confirme a Senha:</label>
            <input type="password" id="senha_confirma" name="senha_confirma" required>
        </div>

        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required>
        </div>

        <button type="submit" class="btn-cadastrar">Cadastrar</button>
    </form>
</body>

</html>