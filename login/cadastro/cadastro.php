<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../connector_database/connector.php");

// Debug: Verificar conexão
if (!$conn || $conn->connect_error) {
    die("STATUS CONEXÃO: " . ($conn->connect_error ?? "Objeto de conexão inválido"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Coletar dados brutos
        $rawData = [
            'nome' => $_POST['nome'] ?? '',
            'nome_usuario' => $_POST['nome_usuario'] ?? '',
            'email' => $_POST['email'] ?? '',
            'senha' => $_POST['senha'] ?? '',
            'data_nascimento' => $_POST['data_nascimento'] ?? ''
        ];

        // Sanitização
        $dados = array_map(function($item) use ($conn) {
            return $conn->real_escape_string(trim($item));
        }, $rawData);

        // Manter senha original para hash
        $dados['senha'] = $rawData['senha'];

        // Validações
        $erros = [];
        
        // Campo Nome
        if (empty($dados['nome'])) {
            $erros[] = "Nome obrigatório";
        } elseif (strlen($dados['nome']) > 100) {
            $erros[] = "Nome máximo 100 caracteres";
        }

        // Campo Nome de Usuário
        if (empty($dados['nome_usuario'])) {
            $erros[] = "Nome de usuário obrigatório";
        } elseif (!preg_match('/^[a-z0-9_]{4,20}$/', $dados['nome_usuario'])) {
            $erros[] = "Nome de usuário inválido (a-z, 0-9, _)";
        }

        // Campo Email
        if (empty($dados['email'])) {
            $erros[] = "Email obrigatório";
        } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Formato de email inválido";
        }

        // Campo Senha
        if (strlen($dados['senha']) < 8) {
            $erros[] = "Senha deve ter no mínimo 8 caracteres";
        }

        // Campo Data
        if (empty($dados['data_nascimento'])) {
            $erros[] = "Data de nascimento obrigatória";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_nascimento'])) {
            $erros[] = "Formato de data inválido (AAAA-MM-DD)";
        }

        if (!empty($erros)) {
            throw new Exception(implode("||", $erros));
        }

        // Hash da senha
        $senha_hash = password_hash($dados['senha'], PASSWORD_BCRYPT);

        // Transação
        $conn->begin_transaction();

        // Preparar statement
        $stmt = $conn->prepare("INSERT INTO users 
            (nome, nome_usuario, email, senha, data_nascimento) 
            VALUES (?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Preparação falhou: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("sssss", 
            $dados['nome'],
            $dados['nome_usuario'],
            $dados['email'],
            $senha_hash,
            $dados['data_nascimento']
        );

        // Executar
        if (!$stmt->execute()) {
            throw new Exception("Execução falhou: " . $stmt->error);
        }

        // Commit
        $conn->commit();

        // Redirecionamento com sucesso
        $_SESSION['sucesso'] = "Cadastro realizado!";
        header("Location: ../cadastro/login.php");
        exit;

    } catch (mysqli_sql_exception $e) {
        // Rollback e tratamento de erro
        $conn->rollback();
        
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        // Tratar erros específicos do MySQL
        if ($errorCode == 1062) {
            preg_match('/Duplicate entry \'(.+?)\'/', $errorMessage, $matches);
            $campo = strpos($errorMessage, 'nome_usuario') !== false ? 'Nome de usuário' : 'Email';
            $erros[] = "$campo '{$matches[1]}' já está em uso";
        } else {
            $erros[] = "Erro no banco de dados: " . $errorMessage;
        }

        $_SESSION['erros_cadastro'] = $erros;
        header("Location: cadastro.php");
        exit;

    } catch (Exception $e) {
        // Outros erros
        $_SESSION['erros_cadastro'] = explode("||", $e->getMessage());
        header("Location: cadastro.php");
        exit;

    } finally {
        // Fechar conexão apenas aqui
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <style>
        .erro { color: red; margin: 10px 0; }
        .sucesso { color: green; margin: 10px 0; }
        form { max-width: 500px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; }
        input { display: block; width: 95%; margin: 10px 0; padding: 8px; }
    </style>
</head>
<body>
    <?php if (!empty($_SESSION['erros_cadastro'])): ?>
        <div class="erro">
            <?php foreach ($_SESSION['erros_cadastro'] as $erro): ?>
                <p><?= htmlspecialchars($erro) ?></p>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['erros_cadastro']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['sucesso'])): ?>
        <div class="sucesso">
            <p><?= htmlspecialchars($_SESSION['sucesso']) ?></p>
        </div>
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="text" name="nome_usuario" placeholder="Nome de usuário" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="senha" placeholder="Senha (mínimo 8 caracteres)" required>
        <input type="date" name="data_nascimento" required>
        <button type="submit">Cadastrar</button>
    </form>
</body>
</html>