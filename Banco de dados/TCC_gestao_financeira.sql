CREATE DATABASE tcc26_gestao_financeira;
USE tcc26_gestao_financeira;

-- Tabela de Usuários
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB; -- Usado para ler,gravvar e gerenciar os dados), foca na segurança e integridade das informações

-- Tabela de Cursos
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    nivel ENUM('Basico', 'Intermediario', 'Avancado') DEFAULT 'Basico',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Módulos
CREATE TABLE modulos (
    id_modulo INT AUTO_INCREMENT PRIMARY KEY,
    fk_curso INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    ordem INT DEFAULT 1, -- Para organizar a sequência das aulas
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Matrículas
CREATE TABLE matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    fk_usuario INT NOT NULL,
    fk_curso INT NOT NULL,
    data_matricula DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_conclusao BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Tabela de Conversas (Contexto do Chat)
CREATE TABLE conversas (
    id_conversa INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('suporte', 'comunidade', 'privado') NOT NULL,
    fk_curso INT DEFAULT NULL, -- Preenchido se for chat de grupo de um curso
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_curso) REFERENCES cursos(id_curso) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Participantes 
CREATE TABLE participantes_conversa (
    fk_conversa INT NOT NULL,
    fk_usuario INT NOT NULL,
    data_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fk_conversa, fk_usuario),
    FOREIGN KEY (fk_conversa) REFERENCES conversas(id_conversa) ON DELETE CASCADE,
    FOREIGN KEY (fk_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Mensagens 
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