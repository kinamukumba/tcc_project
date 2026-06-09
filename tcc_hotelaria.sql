-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 09-Jun-2026 às 19:18
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `tcc_hotelaria`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `administrador`
--

CREATE TABLE `administrador` (
  `id_adminitrador` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `nivel_acesso` varchar(30) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `administrador`
--

INSERT INTO `administrador` (`id_adminitrador`, `id_reserva`, `nome`, `email`, `senha`, `nivel_acesso`, `id_usuario`) VALUES
(1, NULL, 'Administrador Geral', 'admin@sana.com', '0192023a7bbd73250516f069df18b500', 'Geral', 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliação`
--

CREATE TABLE `avaliação` (
  `id_avaliação` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `nota` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `data_avaliação` datetime DEFAULT NULL,
  `discrição` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `nome` varchar(20) DEFAULT NULL,
  `sobrenome` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `bi` varchar(20) DEFAULT NULL,
  `telemovel` varchar(15) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `nome`, `sobrenome`, `email`, `bi`, `telemovel`, `senha`, `id_usuario`) VALUES
(1, 'Kina Mukumba', '', 'kinamukumba@gmail.com', '', '926775029', '698d51a19d8a121ce581499d7b701668', 1),
(2, 'Jaime Kiando', NULL, 'jaime@gmail.com', 'S/N', '926775029', '698d51a19d8a121ce581499d7b701668', 4);

-- --------------------------------------------------------

--
-- Estrutura da tabela `gestor`
--

CREATE TABLE `gestor` (
  `id_gestor` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `nivel_acesso` varchar(50) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `gestor`
--

INSERT INTO `gestor` (`id_gestor`, `nome`, `telefone`, `email`, `senha`, `nivel_acesso`, `id_usuario`) VALUES
(1, 'Castro', '926775029', 'castro@sana.com', 'ad41c615c085306af0fd483904f89973', 'geral', 6);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id_mensagem` int(11) NOT NULL,
  `remetente_id` int(11) DEFAULT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `data_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `notificacao`
--

CREATE TABLE `notificacao` (
  `id_notificacao` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `notificacao`
--

INSERT INTO `notificacao` (`id_notificacao`, `id_cliente`, `mensagem`, `lida`, `data_criacao`) VALUES
(1, 1, 'As datas da sua reserva #2 foram alteradas com sucesso para: 29/05/2026 a 30/05/2026', 1, '2026-05-29 16:03:25'),
(2, 1, 'A sua reserva  foi APROVADA. Aguardamos a sua chegada!', 0, '2026-06-09 18:02:21'),
(3, 1, 'Check-in realizado com sucesso para . Bem-vindo(a)!', 1, '2026-06-09 18:02:23'),
(4, 1, 'As datas da sua reserva #1 foram alteradas com sucesso para: 12/09/2026 a 12/10/2026', 0, '2026-06-09 18:03:18'),
(5, 1, 'A sua reserva  foi APROVADA.', 1, '2026-06-09 18:04:39'),
(6, 1, 'A sua reserva  foi APROVADA. Aguardamos a sua chegada!', 0, '2026-06-09 18:08:30'),
(7, 1, 'Check-in realizado com sucesso para . Bem-vindo(a)!', 0, '2026-06-09 18:08:38'),
(8, 1, 'A sua reserva  foi cancelada.', 0, '2026-06-09 18:10:09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagamento`
--

CREATE TABLE `pagamento` (
  `id_pagamento` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `método_pagamento` enum('cartao','transferencia','dinheiro','outro') DEFAULT NULL,
  `status_pagamento` enum('pendente','pago','cancelado') DEFAULT 'pendente',
  `data_pagamento` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `recepcionista`
--

CREATE TABLE `recepcionista` (
  `id_recepcionista` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `recepcionista`
--

INSERT INTO `recepcionista` (`id_recepcionista`, `nome`, `telefone`, `email`, `senha`, `id_usuario`) VALUES
(1, 'Carla Recepcionista', '912345678', 'recepcao@sana.com', '591e1af5dec075239fcd6b2aa7dbb6cf', 2),
(2, 'Garcia', '926775029', 'garcia@sana.com', '192b290578bfb190691d9dc9229d4b20', 5);

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_serviço` int(11) DEFAULT NULL,
  `n_pessoa` int(11) DEFAULT NULL,
  `data_reserva` datetime DEFAULT NULL,
  `data_checkin` date DEFAULT NULL,
  `data_checkout` date DEFAULT NULL,
  `status_reserva` enum('pendente','aprovada','rejeitada','concluida') DEFAULT 'pendente',
  `codigo_reserva` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `reserva`
--

INSERT INTO `reserva` (`id_reserva`, `id_cliente`, `id_serviço`, `n_pessoa`, `data_reserva`, `data_checkin`, `data_checkout`, `status_reserva`, `codigo_reserva`) VALUES
(1, 1, 6, 1, '2026-05-29 15:57:46', '2026-09-12', '2026-10-12', '', NULL),
(2, 1, 4, 2, '2026-05-29 15:58:36', '2026-05-29', '2026-05-30', '', NULL),
(3, 1, 15, 1, '2026-06-09 18:07:39', '2026-07-12', '2026-08-12', '', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva_serviço`
--

CREATE TABLE `reserva_serviço` (
  `id_reserva_serviço` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `id_serviço` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preço_unitario` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `serviço`
--

CREATE TABLE `serviço` (
  `id_serviço` int(11) NOT NULL,
  `tipos_servicos` varchar(50) DEFAULT NULL,
  `descrição` varchar(100) DEFAULT NULL,
  `preço` decimal(10,2) DEFAULT NULL,
  `status` enum('desocupado','ocupado') DEFAULT 'desocupado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `serviço`
--

INSERT INTO `serviço` (`id_serviço`, `tipos_servicos`, `descrição`, `preço`, `status`) VALUES
(1, 'Pequeno-almoço VIP', 'Pequeno-almoço premium servido no quarto ou restaurante executivo.', 15000.00, 'ocupado'),
(2, 'Lavandaria', 'Serviço completo de lavagem, secagem e passagem de roupas.', 8000.00, 'ocupado'),
(3, 'Spa Premium', 'Massagem relaxante, sauna e tratamentos corporais exclusivos.', 45000.00, 'ocupado'),
(4, 'Jantar Romântico', 'Jantar privado com decoração especial e menu gourmet.', 25000.00, 'ocupado'),
(5, 'Transfer Aeroporto Luxo', 'Transporte executivo entre o hotel e o aeroporto.', 120000.00, 'ocupado'),
(6, 'Serviço de Quarto', 'Entrega de refeições e bebidas diretamente ao quarto.', 5000.00, 'ocupado'),
(7, 'Tour Privado', 'Passeio turístico privado com guia e transporte exclusivo.', 95000.00, 'ocupado'),
(8, 'Bebidas Premium', 'Seleção de bebidas alcoólicas e não alcoólicas premium.', 10000.00, 'ocupado'),
(9, 'Personal Trainer', 'Sessão individual de treino físico no ginásio do hotel.', 30000.00, 'ocupado'),
(10, 'Upgrade de Suite', 'Upgrade para uma suite de luxo com benefícios adicionais.', 200000.00, 'ocupado'),
(11, 'Aluguer de Sala de Conferência', 'Espaço executivo equipado para reuniões e eventos corporativos.', 180000.00, 'ocupado'),
(12, 'Babysitting', 'Serviço profissional de cuidado infantil para hóspedes.', 22000.00, 'ocupado'),
(13, 'Wi-Fi Premium', 'Internet de alta velocidade com acesso ilimitado.', 7000.00, 'ocupado'),
(14, 'Decoração para Lua de Mel', 'Decoração romântica especial para casais em lua de mel.', 35000.00, 'ocupado'),
(15, 'Piscina VIP', 'Acesso exclusivo à área premium da piscina.', 18000.00, 'desocupado');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `senha` varchar(32) DEFAULT NULL,
  `tipo_usuario` enum('utente','admin','gerente','recepcionista') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `email`, `telefone`, `senha`, `tipo_usuario`) VALUES
(1, 'Kina Mukumba', 'kinamukumba@gmail.com', '926775029', '698d51a19d8a121ce581499d7b701668', 'utente'),
(2, 'Carla Recepcionista', 'recepcao@sana.com', '912345678', '591e1af5dec075239fcd6b2aa7dbb6cf', 'recepcionista'),
(3, 'Administrador Geral', 'admin@sana.com', '900000000', '0192023a7bbd73250516f069df18b500', 'admin'),
(4, 'Jaime Kiando', 'jaime@gmail.com', '926775029', '698d51a19d8a121ce581499d7b701668', 'utente'),
(5, 'Garcia', 'garcia@sana.com', '926775029', '192b290578bfb190691d9dc9229d4b20', 'recepcionista'),
(6, 'Castro', 'castro@sana.com', '926775029', 'ad41c615c085306af0fd483904f89973', 'gerente');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_adminitrador`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `avaliação`
--
ALTER TABLE `avaliação`
  ADD PRIMARY KEY (`id_avaliação`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_reserva` (`id_reserva`);

--
-- Índices para tabela `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `gestor`
--
ALTER TABLE `gestor`
  ADD PRIMARY KEY (`id_gestor`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id_mensagem`),
  ADD KEY `remetente_id` (`remetente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`);

--
-- Índices para tabela `notificacao`
--
ALTER TABLE `notificacao`
  ADD PRIMARY KEY (`id_notificacao`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Índices para tabela `pagamento`
--
ALTER TABLE `pagamento`
  ADD PRIMARY KEY (`id_pagamento`),
  ADD KEY `id_reserva` (`id_reserva`);

--
-- Índices para tabela `recepcionista`
--
ALTER TABLE `recepcionista`
  ADD PRIMARY KEY (`id_recepcionista`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `codigo_reserva` (`codigo_reserva`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_serviço` (`id_serviço`);

--
-- Índices para tabela `reserva_serviço`
--
ALTER TABLE `reserva_serviço`
  ADD PRIMARY KEY (`id_reserva_serviço`),
  ADD KEY `id_reserva` (`id_reserva`),
  ADD KEY `id_serviço` (`id_serviço`);

--
-- Índices para tabela `serviço`
--
ALTER TABLE `serviço`
  ADD PRIMARY KEY (`id_serviço`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_adminitrador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `avaliação`
--
ALTER TABLE `avaliação`
  MODIFY `id_avaliação` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `gestor`
--
ALTER TABLE `gestor`
  MODIFY `id_gestor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id_mensagem` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacao`
--
ALTER TABLE `notificacao`
  MODIFY `id_notificacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pagamento`
--
ALTER TABLE `pagamento`
  MODIFY `id_pagamento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `recepcionista`
--
ALTER TABLE `recepcionista`
  MODIFY `id_recepcionista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `reserva_serviço`
--
ALTER TABLE `reserva_serviço`
  MODIFY `id_reserva_serviço` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `serviço`
--
ALTER TABLE `serviço`
  MODIFY `id_serviço` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `avaliação`
--
ALTER TABLE `avaliação`
  ADD CONSTRAINT `avaliação_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliação_ibfk_2` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `gestor`
--
ALTER TABLE `gestor`
  ADD CONSTRAINT `gestor_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`remetente_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `notificacao`
--
ALTER TABLE `notificacao`
  ADD CONSTRAINT `notificacao_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pagamento`
--
ALTER TABLE `pagamento`
  ADD CONSTRAINT `pagamento_ibfk_1` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `recepcionista`
--
ALTER TABLE `recepcionista`
  ADD CONSTRAINT `recepcionista_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserva_ibfk_2` FOREIGN KEY (`id_serviço`) REFERENCES `serviço` (`id_serviço`);

--
-- Limitadores para a tabela `reserva_serviço`
--
ALTER TABLE `reserva_serviço`
  ADD CONSTRAINT `reserva_serviço_ibfk_1` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserva_serviço_ibfk_2` FOREIGN KEY (`id_serviço`) REFERENCES `serviço` (`id_serviço`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
