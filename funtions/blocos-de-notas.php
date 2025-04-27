<?php 
include("../connector_database/connector.php");
//ainda em pensamento para desenvolver
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
//função para salvar alterações
function atualizarEscritas($idBloco, $usuario_id, $conn, $novoConteudo, $titulo){
        $sql = $conn->prepare("UPDATE bloco_de_notas SET conteudo = ?, titulo = ? WHERE id = ? AND usuario_id = ?");
        $sql->bind_param("ssii", $novoConteudo, $titulo, $idBloco, $usuario_id);
        if($sql->execute()){
            return $sql->affected_rows;
        }else{
            throw new Exception("Erro ao tentar passar conexão: ".$conn->error);
        }  
     }

//criar função de aparecer toda a escrita dos seus blocos anteriores.
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
?>