<?php
$localhost = "127.0.0.1:3306";
$usuario = "root";
$senha = "G!9fLx@82_Tz%7kR";
$banco = "banco_chatUp";

// Tentativa de conexão
try {
    $conn = new mysqli($localhost, $usuario, $senha, $banco, 3306);
    
    if ($conn->connect_error) {
        throw new Exception("Erro MySQL: " . $conn->connect_error);
    }
    
    echo "Conexão bem-sucedida!<br>";
    echo "Versão do MySQL: " . $conn->server_version;
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Falha crítica: " . $e->getMessage();
    error_log("Erro de conexão: " . $e->getMessage()); // Registra no log de erros
}
?>