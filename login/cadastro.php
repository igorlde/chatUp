<?php
 session_start();
 error_reporting(E_ALL);
 ini_set('display_errors', 1);
 
 include("../connector_database/connector.php");
 
 // Debug: Verificar conexão
 if (!$conn || $conn->connect_error) {
     die("STATUS CONEXÃO: " . ($conn->connect_error ?? "Objeto de conexão inválido"));
 }
 
 if ($_SERVER["REQUEST_METHOD"] == "POST") {
     $nome = $_POST["nome_cadastrado"] ?? null;
     $nome_usuario = $_POST["nome_usuario"] ?? null;
     $email = $_POST["email_cadastro"] ?? null;
     $senha = $_POST["senha_cadastro"] ?? null;
     $confirma_senha = $_POST["senha_confirma"] ?? null;
     $data_nacimento = $_POST["data_nascimento"] ?? null;
 
     if (!$nome || !$nome_usuario || !$email || !$senha || !$confirma_senha || !$data_nacimento) {
         die("ERRO: preencha todos os campos por favor");
     }
     try {
         // Coletar dados brutos
         $rawData = [
             'nome' => $_POST['nome'] ?? '',
             'nome_usuario' => $_POST['nome_usuario'] ?? '',
             'email' => $_POST['email'] ?? '',
             'senha' => $_POST['senha'] ?? '',
             'data_nascimento' => $_POST['data_nascimento'] ?? ''
         ];
 
     if ($senha !== $confirma_senha) {
         die("As senhas não são iguais");
     }
         // Sanitização
         $dados = array_map(function($item) use ($conn) {
             return $conn->real_escape_string(trim($item));
         }, $rawData);
 
     $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
         // Manter senha original para hash
         $dados['senha'] = $rawData['senha'];
 
     // Verificar se a conexão está aberta
     if ($conn->connect_error) {
         die("Falha na conexão com o banco de dados: " . $conn->connect_error);
     }
         // Validações
         $erros = [];
         
         // Campo Nome
         if (empty($dados['nome'])) {
             $erros[] = "Nome obrigatório";
         } elseif (strlen($dados['nome']) > 100) {
             $erros[] = "Nome máximo 100 caracteres";
         }
 
     // Preparar a consulta para inserir dados no banco
     $sql = $conn->prepare("INSERT INTO users (nome, nome_usuario, email, senha, data_nascimento) VALUES (?, ?, ?, ?, ?)");
     $sql->bind_param("sssss", $nome, $nome_usuario, $email, $senha_hash, $data_nacimento);
         // Campo Nome de Usuário
         if (empty($dados['nome_usuario'])) {
             $erros[] = "Nome de usuário obrigatório";
         } elseif (!preg_match('/^[a-z0-9_]{4,20}$/', $dados['nome_usuario'])) {
             $erros[] = "Nome de usuário inválido (a-z, 0-9, _)";
         }
 
     if ($sql->execute()) {
         header('Location: login.php'); 
         exit; // Certificar-se de que o script pare após o redirecionamento
     } else {
         echo "Erro ao cadastrar: " . $conn->error;
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
 
     // Fechar a conexão após a execução do código
     $conn->close();
         // Redirecionamento com sucesso
         $_SESSION['sucesso'] = "Cadastro realizado!";
         header("Location: login.php");
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="../style/style.css"> <!--conectando a style -->
    <style>
       
    </style>
</head>
<body>
    <h1 class = "h1-cadastro">Cadastro de Usuário</h1>

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

    <form method="POST" action="cadastro.php">
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

        <button type="submit">Cadastrar</button>
    </form>
</body>
</html>