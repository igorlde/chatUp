<?php
    $localhost = "127.0.0.1";
    $usuario = "root";
    $senha = "G!9fLx@82_Tz%kR";
    $banco = "banco_chatUp";
    $porta = 3306;

    // Habilitar erros
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli($localhost, $usuario, $senha, $banco, $porta);
        $conn->set_charset("utf8mb4");

        // Verificação extra de conexão
        if ($conn->connect_errno) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("ERRO DE CONEXÃO: " . $e->getMessage());
    }

