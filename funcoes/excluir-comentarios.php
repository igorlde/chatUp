<?php
session_start();
require __DIR__ . '/../connector_database/connector.php';

function validar_autenticacao(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Valida os parâmetros da requisição
 * @param array $params
 */
function validar_parametros(array $params): array
{
    $filtros = [
        'id' => FILTER_VALIDATE_INT,
        'post_id' => FILTER_VALIDATE_INT
    ];
    $validados = filter_var_array($params, $filtros);
    if (in_array(false, $validados, true)) {
        header("Location: main.php?erro=parametros_invalidos");
        exit;
    }
    return [
        'comentario_id' => (int)$validados['id'],
        'post_id' => (int)$validados['post_id']
    ];
}
/**
 * Função deletar comentario no banco.
 * @param mysqli $conn,
 * @param int $comentario,
 * @param int $post_id,
 * @param int $usuario_id.
 */
function remover_comentario(mysqli $conn, int $comentario_id, int $post_id, int $usuario_id): void
{
    $sql = "DELETE FROM comentarios 
            WHERE id = ? 
            AND post_id = ? 
            AND (usuario_id = ? 
                OR EXISTS (
                    SELECT 1 
                    FROM posts 
                    WHERE id = ? 
                   AND usuario_id = ?
                ))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $comentario_id, $post_id, $usuario_id, $post_id, $usuario_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro na execução: " . $stmt->error);
    }
}
/**
 * Função gerar urls de redirecionar para pagina main
 * @param int $post_id,
 * @param bool $erro.
 */
function gerar_url_redirecionamento(int $post_id, bool $erro = false): string
{
    $base =  "../main.php?post_id=$post_id#post-$post_id";
    return $erro ? "$base&erro=exclusao" : "$base&ts=" . time();
}
/**
 * Fluxo principal junção das funções
 */
try {
    validar_autenticacao();

    $params = validar_parametros($_GET);
    $usuario_id = (int)$_SESSION['usuario_id'];

    remover_comentario(
        $conn,
        $params['comentario_id'],
        $params['post_id'],
        $usuario_id
    );

    header("Location: " . gerar_url_redirecionamento($params['post_id']));
} catch (RuntimeException $e) {
    error_log("ERRO EXCLUSÃO: " . $e->getMessage() . " - Usuário: $usuario_id");
    header("Location: " . gerar_url_redirecionamento($params['post_id'] ?? 0, true));
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
    exit;
}
