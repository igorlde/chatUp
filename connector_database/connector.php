<?php
   $host = "127.0.0.1";
$port = 3306;
$user = "root";
$pass = "654321$#@---";
$db = "banco_chatUp";

    // Habilitar erros
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
    
$conn = new mysqli($host, $user, $pass, $db, $port);
        $conn->set_charset("utf8mb4");

        // Verificação extra de conexão
        if ($conn->connect_errno) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("ERRO DE CONEXÃO: " . $e->getMessage());
    }


