<?php
session_start();
require __DIR__ . '/connector_database/connector.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Usuário não logado.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID do post inválido.");
}

$post_id = (int)$_GET['id'];
echo "ID recebido: $post_id<br>";

try {
    $conn->begin_transaction();

    // Buscar caminhos dos arquivos
    $stmt = $conn->prepare("
        SELECT p.imagem_capa, pi.caminho_arquivo 
        FROM posts p
        LEFT JOIN post_imagens pi ON p.id = pi.post_id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['imagem_capa'])) {
            $arquivos[] = __DIR__ . '/' . $row['imagem_capa'];
        }
        if (!empty($row['caminho_arquivo'])) {
            $arquivos[] = __DIR__ . '/' . $row['caminho_arquivo'];
        }
    }
   // echo "Arquivos encontrados: " . implode(", ", $arquivos) . "<br>";

  
    $stmt = $conn->prepare("DELETE FROM posts WHERE video = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

      // Excluir comentários
    $stmt = $conn->prepare("DELETE FROM comentarios WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    //echo "Comentários excluídos<br>";

    // Excluir tags
    $stmt = $conn->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
   // echo "Tags excluídas<br>";

    // Excluir imagens do post
    $stmt = $conn->prepare("DELETE FROM post_imagens WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    //echo "Imagens adicionais excluídas<br>";

    // Excluir o post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
       // echo "Post excluído<br>";
    } else {
        echo "NENHUM POST FOI EXCLUÍDO!<br>";
    }

    $conn->commit();

    // Excluir arquivos físicos
    foreach ($arquivos as $caminho) {
        if (file_exists($caminho)) {
            unlink($caminho);
           // echo "Arquivo deletado: $caminho<br>";
           header("Location: main.php");
           exit;
        } else {
            echo "Arquivo não encontrado: $caminho<br>";
        }
    }

} catch (Exception $e) {
    $conn->rollback();
    echo "Erro ao excluir: " . $e->getMessage();
}
?>