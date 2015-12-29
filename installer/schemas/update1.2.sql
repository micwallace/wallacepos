-- Version 1.2 DB update
ALTER DATABASE CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `auth` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `config` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `customers` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `customer_contacts` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `devices` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `device_map` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `locations` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `sales` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `sale_history` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `sale_items` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `sale_payments` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `sale_voids` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `stock_history` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `stock_levels` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `stored_items` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `stored_suppliers` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `tax_items` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `tax_rules` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
--
-- Table structure for table `tax_rules`
--
CREATE TABLE IF NOT EXISTS `tax_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` varchar(2048) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;
--
-- Dumping data for table `tax_rules`
--
INSERT IGNORE INTO `tax_rules` (`id`, `data`) VALUES
(1, '{"name":"No Tax","inclusive":true,"mode":"single","base":[],"locations":{},"id":"1"}'),
(2, '{"name":"GST","inclusive":true,"mode":"single","base":[1],"locations":{},"id":"1"}');

-- Alter device_map table
ALTER TABLE `device_map` ADD `ip` varchar(66) NOT NULL;
ALTER TABLE `device_map` ADD `useragent` varchar(256) NOT NULL;
ALTER TABLE `device_map` ADD `dt` DATETIME NOT NULL;
ALTER TABLE `device_map` CHANGE `uuid` `uuid` VARCHAR(64) NOT NULL;

-- Alter devices table
ALTER TABLE `devices` ADD `data` varchar(2048) NOT NULL AFTER `locationid`;

-- Alter stored_items table
ALTER TABLE `stored_items` ADD `data` varchar(2048) NOT NULL AFTER `id`;

-- Alter tax items table
ALTER TABLE `tax_items` ADD `type` varchar(12) NOT NULL AFTER `name`;
ALTER TABLE `tax_items` DROP COLUMN `calcfunc`;
ALTER TABLE `tax_items` CHANGE `divisor` `multiplier` varchar(8) not null;
DELETE FROM `tax_items` WHERE `id`=1 AND `name`='No Tax';
UPDATE `tax_items` SET `id`=1, `multiplier`='0.1', `type`='standard' WHERE `id`=2 AND `name`='GST';

ALTER TABLE `sale_items` CHANGE `tax` `tax` VARCHAR( 2048 ) NOT NULL;