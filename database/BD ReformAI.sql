-- Tabela principal de usuários (Clientes e Prestadores)
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cpf CHAR(11) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cidade VARCHAR(50),
    foto_perfil VARCHAR(255) DEFAULT 'default.png'
);

-- Informações complementares do perfil profissional
CREATE TABLE prestadores_detalhes (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    bio TEXT,
    nicho VARCHAR(100),
    experiencia_anos INT,
    CONSTRAINT fk_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- Cadastro de serviços para exibição na Home
CREATE TABLE servicos (
    id SERIAL PRIMARY KEY,
    prestador_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    categoria_nome VARCHAR(50),
    valor_base NUMERIC(10,2),
    descricao_curta VARCHAR(255),
    CONSTRAINT fk_prestador
        FOREIGN KEY (prestador_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- Projetos e fotos do portfólio (Tela Novo Projeto)
CREATE TABLE portfolio_imagens (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo_projeto VARCHAR(100) NOT NULL,
    descricao_projeto TEXT,
    url_imagem VARCHAR(255) NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_portfolio
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- Sistema de notas e feedbacks (Estrelas)
CREATE TABLE avaliacoes (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL,
    prestador_id INT NOT NULL,
    nota INT CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES usuarios(id),
    CONSTRAINT fk_prestador_avaliacao
        FOREIGN KEY (prestador_id)
        REFERENCES usuarios(id)
);