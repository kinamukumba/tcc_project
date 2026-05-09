CREATE DATABASE IF NOT EXISTS tcc_hotelaria;
USE tcc_hotelaria;

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(50),
    telefone VARCHAR(15),
    senha VARCHAR(32),
    tipo_usuario ENUM('utente', 'admin', 'gerente')
);

CREATE TABLE IF NOT EXISTS cliente (
    id_cliente INT(11) AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20),
    sobrenome VARCHAR(50),
    email VARCHAR(50),
    bi VARCHAR(20),
    telemovel VARCHAR(15),
    senha VARCHAR(32),
    id_usuario INT(11),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS administrador (
    id_adminitrador INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT(11),
    nome VARCHAR(50),
    email VARCHAR(50),
    senha VARCHAR(32),
    nivel_acesso VARCHAR(30),
    id_usuario INT(11),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gestor (
    id_gestor INT(11) AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    telefone VARCHAR(15),
    email VARCHAR(50),
    senha VARCHAR(32),
    nivel_acesso VARCHAR(50),
    id_usuario INT(11),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS serviço (
    id_serviço INT(11) AUTO_INCREMENT PRIMARY KEY,
    tipos_servicos VARCHAR(50),
    descrição VARCHAR(100),
    preço DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS reserva (
    id_reserva INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT(11),
    id_serviço INT(11),
    n_pessoa INT(11),
    data_reserva DATETIME,
    data_checkin DATE,
    data_checkout DATE,
    status_reserva ENUM('pendente', 'aprovada', 'rejeitada', 'concluida') DEFAULT 'pendente',
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_serviço) REFERENCES serviço(id_serviço)
);

CREATE TABLE IF NOT EXISTS reserva_serviço (
    id_reserva_serviço INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT(11),
    id_serviço INT(11),
    quantidade INT(11),
    preço_unitario DECIMAL(10,2),
    FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva) ON DELETE CASCADE,
    FOREIGN KEY (id_serviço) REFERENCES serviço(id_serviço)
);

CREATE TABLE IF NOT EXISTS avaliação (
    id_avaliação INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT(11),
    id_reserva INT(11),
    nota INT(11),
    comentario TEXT,
    data_avaliação DATETIME,
    discrição VARCHAR(20),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pagamento (
    id_pagamento INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT(11),
    valor DECIMAL(10,2),
    método_pagamento ENUM('cartao', 'transferencia', 'dinheiro', 'outro'),
    status_pagamento ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    data_pagamento DATETIME,
    FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mensagens (
    id_mensagem INT(11) AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT(11),
    destinatario_id INT(11),
    conteudo TEXT,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);
