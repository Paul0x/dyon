-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 01-Mar-2016 às 19:47
-- Versão do servidor: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `bdyon`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `evento_padrao` int(10) unsigned NOT NULL DEFAULT '0',
  `diretoria_padrao` int(11) DEFAULT NULL,
  `fluxo_padrao` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_admin_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `casa`
--

CREATE TABLE IF NOT EXISTS `casa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `nome` varchar(60) NOT NULL,
  `endereco` varchar(150) NOT NULL,
  `valor_pessoa` float NOT NULL,
  `numero_vagas` int(11) NOT NULL DEFAULT '0',
  `anexo_contrato` varchar(32) DEFAULT NULL,
  `mapa` varchar(32) NOT NULL,
  PRIMARY KEY (`id`,`id_evento`),
  KEY `fk_casa_evento1_idx` (`id_evento`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `categoria_compra`
--

CREATE TABLE IF NOT EXISTS `categoria_compra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_UNIQUE` (`nome`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comentario`
--

CREATE TABLE IF NOT EXISTS `comentario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node` int(45) DEFAULT NULL,
  `id_node` int(45) DEFAULT NULL,
  `texto` mediumtext,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comentario_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=135 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `compra`
--

CREATE TABLE IF NOT EXISTS `compra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` float NOT NULL,
  `tipo` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`,`id_evento`),
  KEY `fk_compra_evento1_idx` (`id_evento`),
  KEY `fk_compra_categoria_compra1_idx` (`id_categoria`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=58 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `diretoria`
--

CREATE TABLE IF NOT EXISTS `diretoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(45) DEFAULT NULL,
  `acesso_minimo` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_diretoria_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `diretoria_usuarios`
--

CREATE TABLE IF NOT EXISTS `diretoria_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_diretoria` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_diretoria`),
  KEY `fk_usuario_has_diretoria_diretoria1_idx` (`id_diretoria`),
  KEY `fk_usuario_has_diretoria_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `evento`
--

CREATE TABLE IF NOT EXISTS `evento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(80) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `max_venda` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`,`id_usuario`),
  KEY `fk_evento_usuario_idx` (`id_usuario`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupo`
--

CREATE TABLE IF NOT EXISTS `grupo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_lider` int(11) NOT NULL,
  `nome` varchar(45) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_evento` int(11) NOT NULL,
  `codigo_acesso` varchar(32) NOT NULL,
  PRIMARY KEY (`id`,`id_lider`,`id_evento`),
  UNIQUE KEY `codigo_acesso_UNIQUE` (`codigo_acesso`),
  KEY `fk_grupo_usuario1_idx` (`id_lider`),
  KEY `fk_grupo_evento1_idx` (`id_evento`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=261 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_log` int(11) NOT NULL,
  `mensagem_log` text NOT NULL,
  PRIMARY KEY (`id`,`id_usuario`),
  KEY `fk_logs_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `lote`
--

CREATE TABLE IF NOT EXISTS `lote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(40) NOT NULL,
  `valor` float NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `max_venda` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `genero` enum('f','m') DEFAULT NULL,
  `pagseguro_hash` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`,`id_evento`,`id_usuario`),
  KEY `fk_lote_evento1_idx` (`id_evento`),
  KEY `fk_lote_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `metodo_pagamento`
--

CREATE TABLE IF NOT EXISTS `metodo_pagamento` (
  `id` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `nome` varchar(70) NOT NULL,
  `tipo` int(11) NOT NULL,
  `hash_link` varchar(100) DEFAULT NULL,
  `max_parcelas` int(11) NOT NULL,
  `desconto` float NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_metodo_pagamento_evento1_idx` (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pacote`
--

CREATE TABLE IF NOT EXISTS `pacote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `desconto` int(11) NOT NULL DEFAULT '0',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_pagamento` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `data_alteracao` timestamp NULL DEFAULT NULL,
  `id_quarto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`id_usuario`,`id_grupo`,`id_lote`),
  KEY `fk_pacote_usuario1_idx` (`id_usuario`),
  KEY `fk_pacote_lote1_idx` (`id_lote`),
  KEY `fk_pacote_grupo1_idx` (`id_grupo`),
  KEY `id_quarto` (`id_quarto`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=895 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `parcela_compra`
--

CREATE TABLE IF NOT EXISTS `parcela_compra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_comprovante` varchar(150) DEFAULT NULL,
  `valor` double NOT NULL,
  `status` int(45) NOT NULL,
  `tipo_comprovante` int(11) DEFAULT NULL,
  `data_vencimento` datetime DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`id_compra`),
  KEY `fk_parcela_compra_compra1_idx` (`id_compra`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=73 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `parcela_pacote`
--

CREATE TABLE IF NOT EXISTS `parcela_pacote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pacote` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_vencimento` datetime NOT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `valor` float NOT NULL,
  `status` int(11) NOT NULL,
  `id_comprovante` varchar(100) DEFAULT NULL,
  `tipo_comprovante` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`id_pacote`),
  KEY `fk_parcelas_pacote1_idx` (`id_pacote`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2597 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `quarto`
--

CREATE TABLE IF NOT EXISTS `quarto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_casa` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `numero_vagas` int(11) NOT NULL,
  `suite` enum('s','n') NOT NULL,
  PRIMARY KEY (`id`,`id_casa`),
  KEY `fk_quarto_casa1_idx` (`id_casa`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=50 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `tarefa`
--

CREATE TABLE IF NOT EXISTS `tarefa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_diretoria` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `descricao` longtext,
  `data_vencimento` datetime DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `prioridade` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_tarefa_diretoria1_idx` (`id_diretoria`),
  KEY `fk_tarefa_usuario1_idx` (`id_usuario`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(90) NOT NULL,
  `email` varchar(90) NOT NULL,
  `senha` varchar(32) NOT NULL,
  `rg` varchar(20) NOT NULL,
  `tipo` int(11) NOT NULL,
  `sexo` enum('m','f') NOT NULL,
  `cidade` varchar(45) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image` varchar(100) DEFAULT NULL,
  `codigo_recuperacao` varchar(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `cpf_UNIQUE` (`rg`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1288 ;

--
-- Constraints for dumped tables
--

--
-- Limitadores para a tabela `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `casa`
--
ALTER TABLE `casa`
  ADD CONSTRAINT `fk_casa_evento1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `comentario`
--
ALTER TABLE `comentario`
  ADD CONSTRAINT `fk_comentario_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_categoria_compra1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_compra` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_compra_evento1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `diretoria`
--
ALTER TABLE `diretoria`
  ADD CONSTRAINT `fk_diretoria_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `diretoria_usuarios`
--
ALTER TABLE `diretoria_usuarios`
  ADD CONSTRAINT `fk_usuario_has_diretoria_diretoria1` FOREIGN KEY (`id_diretoria`) REFERENCES `diretoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_has_diretoria_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `evento`
--
ALTER TABLE `evento`
  ADD CONSTRAINT `fk_evento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `grupo`
--
ALTER TABLE `grupo`
  ADD CONSTRAINT `fk_grupo_evento1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_grupo_usuario1` FOREIGN KEY (`id_lider`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `fk_logs_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `lote`
--
ALTER TABLE `lote`
  ADD CONSTRAINT `fk_lote_evento1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_lote_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `metodo_pagamento`
--
ALTER TABLE `metodo_pagamento`
  ADD CONSTRAINT `fk_metodo_pagamento_evento1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `pacote`
--
ALTER TABLE `pacote`
  ADD CONSTRAINT `fk_idquartoidx` FOREIGN KEY (`id_quarto`) REFERENCES `quarto` (`id`),
  ADD CONSTRAINT `fk_pacotequarto` FOREIGN KEY (`id_quarto`) REFERENCES `quarto` (`id`),
  ADD CONSTRAINT `fk_pacote_grupo1` FOREIGN KEY (`id_grupo`) REFERENCES `grupo` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_pacote_lote1` FOREIGN KEY (`id_lote`) REFERENCES `lote` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_pacote_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `parcela_compra`
--
ALTER TABLE `parcela_compra`
  ADD CONSTRAINT `fk_parcela_compra_compra1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `parcela_pacote`
--
ALTER TABLE `parcela_pacote`
  ADD CONSTRAINT `fk_parcelas_pacote1` FOREIGN KEY (`id_pacote`) REFERENCES `pacote` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `quarto`
--
ALTER TABLE `quarto`
  ADD CONSTRAINT `fk_quarto_casa1` FOREIGN KEY (`id_casa`) REFERENCES `casa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limitadores para a tabela `tarefa`
--
ALTER TABLE `tarefa`
  ADD CONSTRAINT `fk_tarefa_diretoria1` FOREIGN KEY (`id_diretoria`) REFERENCES `diretoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tarefa_usuario1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
