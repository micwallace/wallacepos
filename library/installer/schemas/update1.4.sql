ALTER TABLE `sales`
  CHANGE COLUMN `balance` `balance` decimal(10,2) NOT NULL DEFAULT 0,
  CHANGE COLUMN `duedt` `duedt` bigint(20) NOT NULL DEFAULT 0,
  CHANGE COLUMN `rounding` `rounding` decimal(10,2) NOT NULL DEFAULT 0;

ALTER TABLE `sale_items`
  CHANGE COLUMN `saleitemid` `saleitemid` varchar(12) NOT NULL;

ALTER TABLE `customers`
  CHANGE COLUMN `notes` `notes` varchar(2048) NOT NULL DEFAULT '';

ALTER TABLE `devices`
  CHANGE COLUMN `dt` `dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0;

ALTER TABLE `locations`
  CHANGE COLUMN `dt` `dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0;
