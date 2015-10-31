-- Version 1.0 DB update

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Add additional config values
--

INSERT INTO `config` (`id`, `name`, `data`) VALUES
(4, 'accounting', '{"xeroenabled":0,"xerotoken":"","xeroaccnmap":""}');
-- --------------------------------------------------------

--
-- Update Table structure for table `customers`
--

ALTER TABLE `customers` ADD `pass` varchar(512) NOT NULL AFTER `googleid`;
ALTER TABLE `customers` ADD `token` varchar(256) NOT NULL AFTER `pass`;
ALTER TABLE `customers` ADD `activated` int(1) NOT NULL AFTER `token`;
ALTER TABLE `customers` ADD `disabled` int(1) NOT NULL AFTER `activated`;
ALTER TABLE `customers` ADD `lastlogin` datetime NOT NULL AFTER `disabled`;

--
-- Update Table structure for table `customers`
--

ALTER TABLE `auth` ADD `token` varchar(64) NOT NULL AFTER `password`;

