-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 16, 2018 at 02:47 PM
-- Server version: 10.1.36-MariaDB
-- PHP Version: 7.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prwb_1819_PA02`
--
DROP DATABASE IF EXISTS `prwb_1819_PA02`;
CREATE DATABASE IF NOT EXISTS `prwb_1819_PA02` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `prwb_1819_PA02`;

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

DROP TABLE IF EXISTS `book`;
CREATE TABLE IF NOT EXISTS `book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isbn` char(13) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `editor` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn_UNIQUE` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Modification pour Iteration 2
--
ALTER TABLE book ADD COLUMN nbCopies INT(11) NOT NULL DEFAULT 1;

--
-- Contenu de la table `book`
--

INSERT INTO `book` (`id`, `isbn`, `title`, `author`, `editor`, `picture`, `nbCopies`) VALUES
(1,
 '9781119247791',
 'Java All-in-One For Dummies',
 'Doug Lowe',
 'For Dummies',
 'javafordummies.jpg',
 10),
(2,
 '9780007322596',
 'The Lord of the Rings: The Fellowship of the Ring, The Two Towers, The Return of the King',
 'J. R. R. Tolkien',
 'HarperCollins',
 null,
 10),
(3,
 '9780140444308',
 'Les Miserables',
 'Victor Hugo',
 'Penguin Classics',
 null,
 10),
(4,
 '9782253140870',
 'L''écume des jours',
 'Boris Vian',
 'Le Livre de Poche',
 null,
 10),
(5,
 '9782253088752',
 'Frankenstein',
 'Mary W. Shelley',
 'Le Livre de Poche',
 null,
 10),
(6,
 '9782070394869',
 'Kitchen',
 'Banana Yoshimoto',
 'Gallimard',
 null,
 10),
(7,
 '9782743608620',
 'Lézard',
 'Banana Yoshimoto',
 'Rivages',
 null,
 10),
(8,
 '9781449372637',
 'CSS Secrets',
 'Lea Verou',
 'O′Reilly',
 null,
 10),
(9,
 '9781491939703',
 'SVG Animations',
 'Sarah Drasner',
 'O''Reilly Media',
 null,
 10),
(10,
 '9780735611313',
 'Code',
 'Charles Petzold',
 'Microsoft Press',
 null,
 10),
(11,
 '9782080708915',
 'Le songe d''une nuit d''été - A Midsummer night''s dream, édition bilingue (français-anglais)',
 'William Shakespeare',
 'Garnier Flammarion / Théâtre bilingue',
 null,
 10);


-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

DROP TABLE IF EXISTS `rental`;
CREATE TABLE IF NOT EXISTS `rental` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `book` int(11) NOT NULL,
  `rentaldate` datetime DEFAULT NULL,
  `returndate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rentalitem_book1_idx` (`book`),
  KEY `fk_rentalitem_user1_idx` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `rental`
--

INSERT INTO `rental` (`id`, `user`, `book`, `rentaldate`, `returndate`) VALUES
(1,  1, 2, null, null),
(2,  1, 5, null, null),
(3,  1, 1, '2018-09-09', null),
(4,  1, 4, '2019-02-08', null),
(5,  1, 3, '2019-01-07', '2019-02-07'),
(6,  2, 2, null, null),
(7,  2, 5, null, null),
(8,  2, 1, '2018-09-09', null),
(9,  2, 4, '2019-02-08', null),
(10, 2, 3, '2019-01-07', '2019-02-07'),
(11,  3, 2, null, null),
(12,  3, 5, null, null),
(13,  3, 1, '2018-09-09', null),
(14,  3, 4, '2019-02-08', null),
(15,  3, 3, '2019-01-07', '2019-02-07'),
(16,  4, 2, null, null),
(17,  4, 5, null, null),
(18,  4, 1, '2018-09-09', null),
(19,  4, 4, '2019-02-08', null),
(20,  4, 3, '2019-01-07', '2019-02-07'),
(21,  4, 3, '2019-01-07', '2019-02-07');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(64) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `role` enum('admin','manager','member') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique` (`username`) USING BTREE,
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `fullname`, `email`, `birthdate`, `role`) VALUES
(1, 'admin', 'c6aa01bd261e501b1fea93c41fe46dc7', 'Administrateur', 'admin@test.com', NULL, 'admin'),
(2, 'ben', 'cc4902e2506fc6de54e53489314c615a', 'Benoît', 'ben@test.com', '1999-01-01', 'manager'),
(3, 'test', '6833f56bb07f8df131d6c97cc6587129', 'Testeur', 'test@test.com', NULL, 'member'),
(4, 'romain', 'ba79dc38975a1d33c4cadf29484872e9', 'Romain', 'romain@test.com', NULL, 'member'),
(5, 'julie', 'fb6ce5c514a534bdda3f87e93f7a88ed', 'Julie', 'julie@test.com', NULL, 'member'),
(6, 'jane', 'e71cbba05f0db3c3575a3bb3fd85fb15', 'Jane Shepard', 'jane@test.com', NULL, 'member');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `fk_rentalitem_book` FOREIGN KEY (`book`) REFERENCES `book` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_rentalitem_user1` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
