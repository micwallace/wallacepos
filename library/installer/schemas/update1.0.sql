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
(3, 'invoice', '{"defaultduedt":"+2 weeks","payinst":"Please contact us for payment instructions","emailmsg":"<div align=\\"left\\">Dear %name%,<br><\\/div><br>Please find the attached invoice.<br><br>Kind regards,<br>Administration"}');
-- --------------------------------------------------------

--
-- Update Table structure for table `customers`
--

ALTER TABLE `customers` ADD `state` varchar(66) NOT NULL AFTER `postcode`;
ALTER TABLE `customers` ADD `notes` varchar(2048) NOT NULL AFTER `country`;
ALTER TABLE `customers` ADD `googleid` varchar(1024) NOT NULL AFTER `notes`;

--
-- Add Table structure for table `customer_contacts`
--

CREATE TABLE IF NOT EXISTS `customer_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `position` varchar(128) NOT NULL,
  `phone` varchar(66) NOT NULL,
  `mobile` varchar(66) NOT NULL,
  `email` varchar(128) NOT NULL,
  `receivesinv` int(1) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Add Table structure for table `sale_history`
--

CREATE TABLE IF NOT EXISTS `sale_history` (
  `id` int(11) NOT NULL,
  `saleid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `type` varchar(66) NOT NULL,
  `description` varchar(256) NOT NULL,
  `dt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Update Table structure for table `sales`
--

ALTER TABLE `sales` ADD `type` varchar(12) NOT NULL AFTER `ref`;
ALTER TABLE `sales` ADD `channel` varchar(12) NOT NULL AFTER `type`;
ALTER TABLE `sales` ADD `balance` decimal(10,2) NOT NULL AFTER `total`;
ALTER TABLE `sales` ADD `duedt` bigint(20) NOT NULL AFTER `processdt`;

--
-- Update Table structure for table `sale_payments`
--

ALTER TABLE `sale_payments` ADD `processdt` bigint(20) NOT NULL AFTER `amount`;