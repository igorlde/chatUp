<?php 
$localhost = ""; 
$usuario = "root";
$senha = "";
$banco_de_dados_usado = "banco_chatUp";

// Estabelecendo a conexão
$conn = mysqli_connect($localhost, $usuario, $senha, $banco_de_dados_usado);

// Verificando se a conexão foi bem-sucedida
if (!$conn) {
    die("Erro ao tentar se conectar: " . mysqli_connect_error());
}
?>
