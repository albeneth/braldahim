-- phpMyAdmin SQL Dump
-- version 2.10.2
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- G�n�r� le : Ven 14 Mars 2008 � 23:57
-- Version du serveur: 5.0.41
-- Version de PHP: 5.2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Base de donn�es: `braldahim`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `type_emplacement`
-- 

CREATE TABLE `type_emplacement` (
  `id_type_emplacement` int(11) NOT NULL auto_increment,
  `nom_systeme_type_emplacement` varchar(20) NOT NULL,
  `nom_type_emplacement` varchar(20) NOT NULL,
  `ordre_emplacement` int(11) NOT NULL,
  PRIMARY KEY  (`id_type_emplacement`),
  KEY `nom_systeme_type_emplacement` (`nom_systeme_type_emplacement`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
