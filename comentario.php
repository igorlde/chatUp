<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("connector_database/connector.php");

//Verificando se o usuario esta logado pois tudo depende de seu id.
if(!isset($_SESSION["usuario_id"])){
    header("Location: login.php");
    exit;
}
if(!$conn || $conn->connect_error){
    die("Status da conexão: ".($conn->connect_error ?? "objeto invalido de conexão"));
}
//metodo para adicionar nossos dados do comentario dentro do MYSQL DB.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_id = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);
    $comentario = trim($_POST["comentario"] ?? ''); // Corrigido nome do campo

    if ($post_id && !empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO comentarios (post_id, usuario_id, texto) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $_SESSION["usuario_id"], $comentario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: main.php");
    exit;
}
$conn->close();
?>