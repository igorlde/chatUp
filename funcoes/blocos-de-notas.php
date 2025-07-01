<?php 
include("../connector_database/connector.php");

/**
 * Função inserção de informações dentro do banco de dados 
 * 
 * @param int $usuario_id,
 * @param sql $conn,
 * @param string $titulo,
 * @param string $conteudo.
 */
function inseridoNotas($usuario_id, $conn, $titulo, $conteudo){
    $sql = $conn->prepare("INSERT INTO bloco_de_notas (titulo, conteudo, usuario_id) VALUES (?,?, ?)");
    $sql->bind_param("ssi", $titulo, $conteudo, $usuario_id);
    if($sql->execute()){
        return $sql->affected_rows;
    }
    else{
        throw new Exception("Erro ao tentar fazer inserção: ".$conn->error);
    }
    
}
/**
 * Função de atualizar Escritas dentro de seus blocos.
 * 
 * @param int $idBloco,
 * @param int $usuario_id,
 * @param sql $conn,
 * @param string $novoConteudo,
 * @param string $titulo.
 */
function atualizarEscritas($idBloco, $usuario_id, $conn, $novoConteudo, $titulo){
        $sql = $conn->prepare("UPDATE bloco_de_notas SET conteudo = ?, titulo = ? WHERE id = ? AND usuario_id = ?");
        $sql->bind_param("ssii", $novoConteudo, $titulo, $idBloco, $usuario_id);
        if($sql->execute()){
            return $sql->affected_rows;
        }else{
            throw new Exception("Erro ao tentar passar conexão: ".$conn->error);
        }  
     }

/**
 * Função de adicionar escrita no primeiro bloco de notas.
 * @param int $idBloco,
 * @param int $usuario_id,
 * @param sql $conn.
 */
function blocosEscritas($idBloco, $usuario_id, $conn){
    $sql = $conn->prepare("SELECT * FROM bloco_de_notas WHERE id = ? AND usuario_id = ?");
    $sql->bind_param("ii", $idBloco, $usuario_id);
    
    if($sql->execute()){
        return $sql->get_result();
    }
    else{
        throw new Exception("Erro ao tentar recuperar o bloco: ".$conn->error);
    }
}

/**
 * Função de excluir seus dados do bloco de notas.
 * 
 * @param int $idBloco,
 * @param int $usuario_id,
 * @param sql $conn.
 */
function excluirBloco($idbloco, $usuario_id, $conn){
    $sql = $conn->prepare("DELETE FROM bloco_de_notas WHERE id = ? AND usuario_id = ?");
    $sql->bind_param("ii", $idbloco, $usuario_id);
    if($sql->execute()){
        return $sql->affected_rows;
    }
    else{
        throw new Exception("Erro ao tentar deletar seu bloco de notas: ".$conn->error);
    }
}
?>