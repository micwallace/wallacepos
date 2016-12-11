ALTER TABLE `sales`
  CHANGE COLUMN `balance` `balance` decimal(10,2) NOT NULL DEFAULT 0,
  CHANGE COLUMN `duedt` `duedt` bigint(20) NOT NULL DEFAULT 0,
  CHANGE COLUMN `rounding` `rounding` decimal(10,2) NOT NULL DEFAULT 0;

ALTER TABLE `sale_items`
  CHANGE COLUMN `saleitemid` `saleitemid` varchar(12) NOT NULL;

ALTER TABLE `customers`
  CHANGE COLUMN `notes` `notes` varchar(2048) NOT NULL DEFAULT '',
  CHANGE COLUMN `pass` `pass` varchar(512) NOT NULL DEFAULT '',
  CHANGE COLUMN `token` `token` varchar(256) NOT NULL DEFAULT '',
  CHANGE COLUMN `activated` `activated` int(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN `lastlogin` `lastlogin` datetime NULL DEFAULT NULL,
  CHANGE COLUMN `dt` `dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `devices`
  CHANGE COLUMN `dt` `dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0;

ALTER TABLE `locations`
  CHANGE COLUMN `dt` `dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0;

ALTER TABLE `auth`
  CHANGE COLUMN `name` `name` varchar(66) NOT NULL DEFAULT '',
  CHANGE COLUMN `token` `token` varchar(64) NOT NULL DEFAULT '',
  CHANGE COLUMN `disabled` `disabled` int(1) NOT NULL DEFAULT 0;
