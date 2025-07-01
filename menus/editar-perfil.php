<?php
session_start();
include "../connector_database/connector.php";
require __DIR__ . "/../funcoes/editar-usuario.php";
try {
    validar_autenticacao();
    $usuario_id = $_SESSION['usuario_id'];
    $uploadsBase = realpath(__DIR__ . '/../uploads');
if ($uploadsBase === false) {
    throw new RuntimeException('Diretório base de uploads não existe');
}
$diretorioUploads = $uploadsBase . '/avatars';


    $erros = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dadosValidados = validar_dados_perfil($_POST);
        $nomeArquivo = processar_avatar($usuario_id, $diretorioUploads);
        atualizar_perfil(
            $conn,
            $usuario_id,
            $dadosValidados['nome'],
            $dadosValidados['bio'],
            $nomeArquivo
        );

        $_SESSION['sucesso'] = 'Perfil atualizado com sucesso!';
        header("Location: /chatUp/main.php?id=$usuario_id");
        exit;
    }
} catch (InvalidArgumentException $e) {
    $erros[] = $e->getMessage();
} catch (RuntimeException $e) {
    $erros[] = $e->getMessage();
} catch (Exception $e) {
    error_log('ERRO PERFIL: ' . $e->getMessage());
    $erros[] = 'Erro interno ao atualizar perfil';
}
$stmt = $conn->prepare("SELECT nome, bio, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
include __DIR__ .'/../visualizar-html/visualizar-perfil.php';
?>
