CREATE DATABASE banco_chatUp;

USE banco_chatUp;

-- Remova todas as tabelas na ordem correta
DROP TABLE IF EXISTS bloco_de_notas;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS curtidas;
DROP TABLE IF EXISTS descurtidas;
DROP table if exists livros;
DROP TABLE IF EXISTS post_tags;
DROP TABLE IF EXISTS post_imagens;
DROP TABLE IF EXISTS mensagens;
DROP TABLE IF EXISTS seguidores;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS users;

-- Tabela de usuários (ALTERADA para BIGINT)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,  -- Alterado para BIGINT
    nome VARCHAR(100) NOT NULL,
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'default-avatar.jpg',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    nome_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    role ENUM ('admin', 'user') NOT NULL default 'user',
    INDEX idx_nome_usuario (nome_usuario),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de posts (ATUALIZADA com BIGINT)
CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,  -- Alterado para BIGINT
    usuario_id BIGINT UNSIGNED NOT NULL,            -- Tipo compatível com users.id
    titulo VARCHAR(100) NOT NULL,
    conteudo TEXT NOT NULL,
    descricao VARCHAR(255),
    tema VARCHAR(50),
    imagem_capa VARCHAR(255),
    video VARCHAR(255),
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_titulo (titulo),
    INDEX idx_data_publicacao (data_publicacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demais tabelas com tipos corrigidos:
CREATE TABLE post_imagens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_tag VARCHAR(30) NOT NULL UNIQUE,
    INDEX idx_nome_tag (nome_tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_tags (
    post_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- tabela comentarios --
CREATE TABLE comentarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    data_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_data_comentario (data_comentario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- tabela curtidas --
CREATE TABLE curtidas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    data_curtida DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curtida (usuario_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- tabela de deslike--
create table descurtidas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    data_descurtida DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_descurtida (usuario_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela seguidores corrigida
CREATE TABLE seguidores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seguidor_id BIGINT UNSIGNED NOT NULL,  -- Tipo igual ao users.id
    seguido_id BIGINT UNSIGNED NOT NULL,    -- Tipo igual ao users.id
    data_seguiu DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seguidor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seguido_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY relacao_unica (seguidor_id, seguido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- tabela de mensagem --
CREATE TABLE mensagens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    remetente_id BIGINT UNSIGNED NOT NULL,
    destinatario_id BIGINT UNSIGNED NOT NULL,
    mensagem TEXT(100) NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

 -- tabela do bloco de notas --
CREATE TABLE bloco_de_notas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;