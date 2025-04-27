<?php
session_start();
include("../connector_database/connector.php");
include("../funtions/blocos-de-notas.php");
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$erro = '';
$sucesso = '';
$nota = [];

// Carregar nota para edição
if (isset($_GET['id'])) {
    try {
        $id_nota = (int)$_GET['id'];
        $resultado = blocosEscritas($id_nota, $usuario_id, $conn);
        $nota = $resultado->fetch_assoc();
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $titulo = htmlspecialchars($_POST['titulo']);
        $conteudo = htmlspecialchars($_POST['conteudo']);

        if (isset($_POST['id_nota'])) {
            $id_nota = (int)$_POST['id_nota'];
            atualizarEscritas($id_nota, $usuario_id, $conn, $conteudo, $titulo);
            $sucesso = 'Atualização feita com sucesso';

            // Recarregar os dados atualizados
            $resultado = blocosEscritas($id_nota, $usuario_id, $conn);
            $nota = $resultado->fetch_assoc();
        } else {
            inseridoNotas($usuario_id, $conn, $titulo, $conteudo);
            $sucesso = 'Inserção dentro do bloco de notas';
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloco de Notas</title>
    <link rel="stylesheet" href="../style/bloco.css">
</head>

<body>
    <nav>
        <a href="../main.php"><button type="submit" class="Butao-voltar">Volta</button></a>
    </nav>
    <div class="container">
        <h1>Meu Bloco de Notas</h1>

        <!-- Mensagens de status -->
        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="sucesso"><?= $sucesso ?></div>
        <?php endif; ?>

        <!-- Formulário de edição -->
        <div class="editor">
            <form method="POST">
                <?php if (isset($nota['id'])): ?>
                    <input type="hidden" name="id_nota" value="<?= $nota['id'] ?>">
                <?php endif; ?>

                <input type="text" name="titulo" placeholder="Título da nota"
                    value="<?= $nota['titulo'] ?? '' ?>" required>

                <textarea name="conteudo" rows="10"
                    placeholder="Digite seu texto aqui..." required><?= $nota['conteudo'] ?? '' ?></textarea>

                <button type="submit">Salvar Nota</button>
            </form>
        </div>

        <!-- Histórico de versões -->
        <h2>Histórico de Edições</h2>
        <div class="historico">
            <?php
            try {
                $historico = $conn->prepare("
                  SELECT id, titulo, left(conteudo, 300) AS conteudo, data_criacao 
                    FROM bloco_de_notas 
                    WHERE usuario_id = ?
                    ORDER BY data_criacao DESC;
                ");
                $historico->bind_param("i", $usuario_id);
                $historico->execute();
                $resultado = $historico->get_result();

                while ($registro = $resultado->fetch_assoc()):
            ?>
                    <div class="registro">
                        <h3>
                            <?= htmlspecialchars($registro['titulo']) ?>
                            <small>(<?= date('d/m/Y H:i', strtotime($registro['data_criacao'])) ?>)</small>
                        </h3>
                        <div class="conteudo">
                            <?= nl2br(htmlspecialchars($registro['conteudo'])) ?>
                        </div>
                        <div class="acoes">
                            <a href="?id=<?= $registro['id'] ?>">Editar</a>
                        </div>
                    </div>
            <?php
                endwhile;
            } catch (Exception $e) {
                echo "<div class='erro'>Erro ao carregar histórico: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>
</body>

</html>