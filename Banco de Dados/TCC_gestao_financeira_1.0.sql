CREATE DATABASE tcc26_gestao_financeira;
USE tcc26_gestao_financeira;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    numero_telefone VARCHAR(20),
    data_nascimento DATE NOT NULL,
    genero ENUM('Masculino', 'Feminino', 'Outro', 'Prefiro nao dizer') DEFAULT 'Prefiro nao dizer',
    cidade VARCHAR(100),
    estado VARCHAR(100),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- TABELAS GRÁFICOS FINANCEIROS (Sem Transações)

-- Tabela para Gráficos (Distribuição de Orçamento Planejado)
CREATE TABLE orcamentos_categoria (
    id_orcamento INT AUTO_INCREMENT PRIMARY KEY,
    fk_usuario INT NOT NULL,
    categoria ENUM('Essencial', 'Investimentos', 'Lazer', 'Educacao', 'Outros') NOT NULL,
    valor_alocado DECIMAL(10,2) NOT NULL, -- Ex: 1500.00
    mes_ano DATE NOT NULL, -- Guarda o mês de referência (Ex: '2026-05-01')
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela para Gráficos de Linha/Barras (Evolução de Metas Planejado vs Realizado)
CREATE TABLE metas_financeiras (
    id_meta INT AUTO_INCREMENT PRIMARY KEY,
    fk_usuario INT NOT NULL,
    titulo_meta VARCHAR(100) NOT NULL, -- Ex: "Reserva de Emergência"
    valor_objetivo DECIMAL(10,2) NOT NULL, -- Quanto quer juntar (Ex: 5000.00)
    valor_atual DECIMAL(10,2) DEFAULT 0.00, -- Quanto já poupou (Ex: 1200.00)
    data_alvo DATE NOT NULL, -- Prazo final da meta
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;



-- ESTRUTURA DE CURSOS, MÓDULOS E AULAS

-- Tabela curso
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    nivel ENUM('Basico', 'Intermediario', 'Avancado') DEFAULT 'Basico',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela modulo
CREATE TABLE modulos (
    id_modulo INT AUTO_INCREMENT PRIMARY KEY,
    fk_curso INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    ordem INT DEFAULT 1,
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela aulas
CREATE TABLE aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    fk_modulo INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    conteudo_texto TEXT,
    url_video VARCHAR(255),
    ordem INT DEFAULT 1,
    FOREIGN KEY (fk_modulo) REFERENCES modulos(id_modulo) ON DELETE CASCADE
) ENGINE=InnoDB;

--  MATRÍCULAS E PROGRESSO (Gráficos de Engajamento)

-- Tabela matricula
CREATE TABLE matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    fk_usuario INT NOT NULL,
    fk_curso INT NOT NULL,
    data_matricula DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_conclusao BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela progresso da aula
CREATE TABLE progresso_aulas (
    fk_usuario INT NOT NULL,
    fk_aula INT NOT NULL,
    data_conclusao DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fk_usuario, fk_aula),
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (fk_aula) REFERENCES aulas(id_aula) ON DELETE CASCADE
) ENGINE=InnoDB;


-- SISTEMA DE MEDALHAS

-- Tabela medalhas
CREATE TABLE medalhas (
    id_medalha INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    icone_url VARCHAR(255),
    tipo_conquista ENUM('login_diario', 'meta_alcancada', 'modulo_concluido', 'curso_concluido') NOT NULL
) ENGINE=InnoDB;

-- Tabela medalhas conquistada por cada usuario
CREATE TABLE usuarios_medalhas (
    fk_usuario INT NOT NULL,
    fk_medalha INT NOT NULL,
    data_conquista DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fk_usuario, fk_medalha),
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (fk_medalha) REFERENCES medalhas(id_medalha) ON DELETE CASCADE
) ENGINE=InnoDB;

--  SISTEMA DE CHATS

-- Tabela conversa
CREATE TABLE conversas (
    id_conversa INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('suporte', 'comunidade', 'privado') NOT NULL,
    fk_curso INT DEFAULT NULL, 
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela participantes de uma conversa
CREATE TABLE participantes_conversa (
    fk_conversa INT NOT NULL,
    fk_usuario INT NOT NULL,
    data_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fk_conversa, fk_usuario),
    FOREIGN KEY (fk_conversa) REFERENCES conversas(id_conversa) ON DELETE CASCADE,
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de mensagens
CREATE TABLE mensagens (
    id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
    fk_conversa INT NOT NULL,
    fk_autor INT NOT NULL,
    conteudo TEXT NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (fk_conversa) REFERENCES conversas(id_conversa) ON DELETE CASCADE,
    FOREIGN KEY (fk_autor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;