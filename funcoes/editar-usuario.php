<?php
require_once __DIR__ . '/../connector_database/connector.php';

function validar_autenticacao(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        throw new RuntimeException('Acesso não autorizado');
    }
}
/**
 * Função validar dados de entrada html
 * @param array $dados.
 */
function validar_dados_perfil(array $dados): array
{
    $nome = trim(filter_var($dados['nome'] ?? '', FILTER_SANITIZE_STRING));
    $bio = filter_var($dados['bio'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($nome)) {
        throw new InvalidArgumentException('O campo nome é obrigatório');
    }

    return [
        'nome' => $nome,
        'bio' => $bio
    ];
}
/**
 * Função de mudar avatar via os uploads
 * @param int $usuario_id,
 * @param string $diretorio_uploads.
 */
function processar_avatar(int $usuario_id, string $diretorio_uploads): string
{
    if (empty($_FILES['avatar']['name'])) {
        return '';
    }

    $arquivo = $_FILES['avatar'];
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid('avatar_') . '.' . $extensao;
    $caminhoCompleto = $diretorio_uploads . $nomeArquivo;

    // Validações
    $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if ($arquivo['size'] > $tamanhoMaximo) {
        throw new RuntimeException('Arquivo muito grande (máx. 2MB)');
    }

    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        throw new RuntimeException('Formato inválido (use JPG, PNG ou WEBP)');
    }

    if (!is_dir($diretorio_uploads) && !mkdir($diretorio_uploads, 0755, true)) {
        throw new RuntimeException('Erro ao criar diretório de uploads');
    }

    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        throw new RuntimeException('Erro ao fazer upload do arquivo');
    }

    return $nomeArquivo;
}


/**
 * função processo final
 * @param mysqli $conn,
 * @param string $nome,
 * @param string $bio,
 * @param int $usuario_id.
 */
function atualizar_perfil(
    mysqli $conn,
    int $usuario_id,
    string $nome,
    string $bio,
    string $avatar = ''
): void {
    $conn->begin_transaction();

    try {
        // Atualizar nome e bio
        $stmt = $conn->prepare("UPDATE users SET nome = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $bio, $usuario_id);
        $stmt->execute();
        $stmt->close();
        if (!empty($avatar)) {
            $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $avatar, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
