UPDATE `customers`
  SET `dt`='1970-01-01 00:00:00' WHERE CAST(dt AS CHAR(20)) = '0000-00-00 00:00:00';

ALTER TABLE `customers`
  CHANGE COLUMN `postcode` `postcode` varchar(12) NOT NULL DEFAULT '';

ALTER TABLE `sale_items` ADD `cost` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `tax`;

ALTER TABLE `sales` ADD `cost` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `rounding`;

