-- ============================================================
-- SCRIPT DE CRIAÇÃO E POVOAMENTO DO BANCO DE DADOS
-- Sistema de Gestão de Campeonatos e Torneios
-- Banco de Dados: campeonato_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS campeonato_db
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_unicode_ci;

USE campeonato_db;

-- Desabilita checagem de chaves estrangeiras temporariamente para limpezas/recriações
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS partida_equipamento;
DROP TABLE IF EXISTS partida_jogador;
DROP TABLE IF EXISTS partidas;
DROP TABLE IF EXISTS equipamentos;
DROP TABLE IF EXISTS jogadores;
DROP TABLE IF EXISTS times;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. TABELA DE USUÁRIOS (Autenticação / Juízes / Árbitros / Admins)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('ADMIN', 'ARBITRO') NOT NULL DEFAULT 'ARBITRO',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. TABELA DE TIMES
-- ------------------------------------------------------------
CREATE TABLE times (
    id_time INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tecnico VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. TABELA DE JOGADORES
-- ------------------------------------------------------------
CREATE TABLE jogadores (
    id_jogador INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    documento VARCHAR(20) UNIQUE,
    posicao_funcao VARCHAR(50),
    id_time INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jogadores_times FOREIGN KEY (id_time) REFERENCES times (id_time) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. TABELA DE EQUIPAMENTOS / MATERIAIS
-- ------------------------------------------------------------
CREATE TABLE equipamentos (
    id_equipamento INT AUTO_INCREMENT PRIMARY KEY,
    codigo_patrimonio VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 5,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. TABELA DE PARTIDAS / SÚMULAS
-- ------------------------------------------------------------
CREATE TABLE partidas (
    id_partida INT AUTO_INCREMENT PRIMARY KEY,
    fase ENUM('CLASSIFICATORIA', 'ELIMINATORIA') NOT NULL,
    data_prevista DATETIME NOT NULL,
    data_realizacao DATETIME NULL,
    status ENUM('AGENDADA', 'CONCLUIDA', 'CANCELADA') NOT NULL DEFAULT 'AGENDADA',
    id_time_casa INT NOT NULL,
    id_time_visitante INT NOT NULL,
    placar_casa INT DEFAULT 0,
    placar_visitante INT DEFAULT 0,
    id_arbitro INT NOT NULL,
    observacoes TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partidas_time_casa FOREIGN KEY (id_time_casa) REFERENCES times (id_time) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_partidas_time_visitante FOREIGN KEY (id_time_visitante) REFERENCES times (id_time) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_partidas_arbitro FOREIGN KEY (id_arbitro) REFERENCES usuarios (id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. TABELA DE DESEMPENHO INDIVIDUAL DOS JOGADORES NA PARTIDA
-- ------------------------------------------------------------
CREATE TABLE partida_jogador (
    id_partida INT NOT NULL,
    id_jogador INT NOT NULL,
    pontos_marcados INT DEFAULT 0,
    faltas INT DEFAULT 0,
    PRIMARY KEY (id_partida, id_jogador),
    CONSTRAINT fk_pj_partida FOREIGN KEY (id_partida) REFERENCES partidas (id_partida) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pj_jogador FOREIGN KEY (id_jogador) REFERENCES jogadores (id_jogador) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. TABELA DE USO DE EQUIPAMENTOS NA PARTIDA
-- ------------------------------------------------------------
CREATE TABLE partida_equipamento (
    id_partida INT NOT NULL,
    id_equipamento INT NOT NULL,
    quantidade_utilizada INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_partida, id_equipamento),
    CONSTRAINT fk_pe_partida FOREIGN KEY (id_partida) REFERENCES partidas (id_partida) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pe_equipamento FOREIGN KEY (id_equipamento) REFERENCES equipamentos (id_equipamento) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- POVOAMENTO DAS TABELAS (INSERÇÃO DE DADOS DE TESTE)
-- ============================================================

-- 1. Inserindo Usuários / Árbitros (Senhas simuladas)
INSERT INTO usuarios (nome, email, senha, perfil) VALUES
('Silva', 'arbitro@liga.com', '0044', 'ARBITRO'),
('Mariana', 'admin@liga.com', '6767', 'ADMIN'),
('Roberto', 'juiz@liga.com', '1234567', 'ARBITRO');

-- 2. Inserindo Times
INSERT INTO times (nome, tecnico) VALUES
('Real Matismo', 'Paixonete'),
('Bastard München', 'Arthur Fialho'),
('Karasuno', 'Ukai');

-- 3. Inserindo Jogadores
INSERT INTO jogadores (nome, documento, posicao_funcao, id_time) VALUES
('Arthur Fialho', '111.222.333-44', 'Atacante', 1),
('Nero', '555.666.777-88', 'Ponta Esquerda', 2),
('Cristiano Ronaldo', '999.888.777-66', 'Ponta Direita', 3);

-- 4. Inserindo Equipamentos (Com item em/abaixo do estoque mínimo para simular alertas)
INSERT INTO equipamentos (codigo_patrimonio, nome, quantidade_estoque, estoque_minimo) VALUES
('EQP-001', 'Bola Oficial de Futebol', 15, 5),
('EQP-002', 'Chuteira Nike Mercurial CR7', 3, 5), -- Alerta: Abaixo do estoque mínimo
('EQP-003', 'Kit Coletes Treino (10 un)', 8, 3);

-- 5. Inserindo Partidas (Agendada, Atrasada/Pendentes e Concluída)
INSERT INTO partidas (fase, data_prevista, data_realizacao, status, id_time_casa, id_time_visitante, placar_casa, placar_visitante, id_arbitro, observacoes) VALUES
-- Partida em atraso/pendente (data passada e não realizada)
('CLASSIFICATORIA', '2026-10-15 14:00:00', NULL, 'AGENDADA', 1, 2, 0, 0, 1, 'Jogo pendente de atualização de súmula'),
-- Partida concluída
('CLASSIFICATORIA', '2026-10-20 16:00:00', '2026-10-20 18:00:00', 'CONCLUIDA', 2, 3, 3, 1, 3, 'Partida realizada sem intercorrências'),
-- Partida agendada para o dia
('ELIMINATORIA', CURRENT_TIMESTAMP, NULL, 'AGENDADA', 3, 1, 0, 0, 1, 'Grande final regional');

-- 6. Inserindo Pontuações de Jogadores nas Partidas
INSERT INTO partida_jogador (id_partida, id_jogador, pontos_marcados, faltas) VALUES
(2, 2, 15, 1),
(2, 3, 8, 2),
(1, 1, 0, 0);

-- 7. Inserindo Registro de Uso de Equipamentos nas Partidas
INSERT INTO partida_equipamento (id_partida, id_equipamento, quantidade_utilizada) VALUES
(1, 1, 2),
(2, 2, 10),
(3, 3, 1);