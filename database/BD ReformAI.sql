-- Tabela principal de usuários (Clientes e Prestadores)
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cidade VARCHAR(100),
    foto_perfil TEXT DEFAULT 'default.png'
);

-- Informações complementares do perfil profissional
CREATE TABLE prestadores_detalhes (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    bio TEXT,
    nicho VARCHAR(100),
    experiencia_anos INT,
    CONSTRAINT fk_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Cadastro de serviços
CREATE TABLE servicos (
    id SERIAL PRIMARY KEY,
    prestador_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    categoria_nome VARCHAR(50),
    valor_base NUMERIC(10,2),
    descricao_curta TEXT,
    CONSTRAINT fk_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Projetos e fotos do portfólio
CREATE TABLE portfolio_imagens (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo_projeto VARCHAR(100) NOT NULL,
    descricao_projeto TEXT,
    url_imagem VARCHAR(255) NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    servico_id INT,
    CONSTRAINT fk_usuario_portfolio FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_servico_portfolio FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL
);

-- Sistema de notas e feedbacks
CREATE TABLE avaliacoes (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL,
    prestador_id INT NOT NULL,
    nota INT CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    servico_id INT,
    avaliador_tipo VARCHAR(50) DEFAULT 'cliente',
    CONSTRAINT fk_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    CONSTRAINT fk_prestador_avaliacao FOREIGN KEY (prestador_id) REFERENCES usuarios(id),
    CONSTRAINT fk_servico_avaliacao FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

-- Chat de mensagens
CREATE TABLE mensagens_chat (
    id SERIAL PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP,
    deletado INT DEFAULT 0,
    url_imagem TEXT,
    lido_em TIMESTAMP,
    entregue_em TIMESTAMP
);

-- Contratação de serviços entre cliente e prestador
CREATE TABLE contratos (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL,
    prestador_id INT NOT NULL,
    servico_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente', 'aceito', 'concluido', 'cancelado')),
    data_pactuada DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finalizado_prestador_em TIMESTAMP,
    finalizado_cliente_em TIMESTAMP,
    avaliado BOOLEAN DEFAULT FALSE,
    favorito BOOLEAN NOT NULL DEFAULT FALSE,
    avaliado_prestador BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_contrato_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    CONSTRAINT fk_contrato_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios(id),
    CONSTRAINT fk_contrato_servico FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

-- Serviços favoritados pelos usuários
CREATE TABLE favoritos_servicos (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL,
    servico_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_favorito_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorito_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
);