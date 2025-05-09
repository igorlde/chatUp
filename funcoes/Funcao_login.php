<?php
require __DIR__ . '/../connector_database/connector.php';
/**
 * @param $email,
 * @param $senha.
 */
function validar_campos(string $email, string $senha): void
{
    if (empty($email) || empty($senha)) {
        $_SESSION['erro'] = 'Prencha todos os campos';
        header("Location: /projeto_ed_feito/login/login.php");
        exit;
    }
}
/**
 * @param mysqli $conn,
 * @param string $email.
 */
function validar_existencia_usuario(mysqli $conn, string $email): ?array
{
    $sql = $conn->prepare("SELECT id, nome_usuario, senha FROM users WHERE email = ?");
    $sql->bind_param("s", $email);
    if (!$sql->execute()) {
        throw new RuntimeException("Erro ao buscar usuário");
    }

    $resultado = $sql->get_result();
    return $resultado->fetch_assoc();
}
/**
 * @param string $senha,
 * @param ?array $usuario
 */
function validar_senha(string $senha, ?array $usuario): void
{
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        $_SESSION['erro'] = 'Credenciais inválidas';
        header("Location: /projeto_ed_feito/login/login.php");
        exit;
    }

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
    header("Location: /projeto_ed_feito/main.php");
    exit;
}
/**
 * 
 */
function processar_login(mysqli $conn)
{
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_login'], $_POST['senha_login'])) {
        try {
            $email = $_POST['email_login'];
            $senha = $_POST['senha_login'];
            
            validar_campos($email, $senha);
            $usuario = validar_existencia_usuario($conn, $email);
            validar_senha($senha, $usuario);
            
        } catch (RuntimeException $e) {
            $_SESSION['erro'] = "Erro: " . $e->getMessage();
            header("Location: /projeto_ed_feito/login/login.php");
            exit;
        }
    }
}
