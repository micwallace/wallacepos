ALTER DATABASE CHARACTER SET utf8 COLLATE utf8_unicode_ci;

ALTER TABLE `device_map` CHANGE `uuid` `uuid` varchar(64) NOT NULL;

ALTER TABLE `tax_rules` ADD `altname` varchar(66) NOT NULL;

INSERT IGNORE INTO `config` (`id`, `data`) VALUES (5, 'templates', '{"invoice":{"name":"Default Invoice","type":"invoice","filename":"invoice.mustache"},"invoice_mixed":{"name":"Mixed Language","type":"invoice","filename":"invoice_mixed.mustache"},"invoice_alt":{"name":"Alternate Language","type":"invoice","filename":"invoice_alt.mustache"},"receipt":{"name":"Default Receipt","type":"receipt","filename":"receipt.mustache"},"receipt_mixed":{"name":"Mixed Language","type":"receipt","filename":"receipt_mixed.mustache"},"receipt_alt":{"name":"Alternate Language","type":"receipt","filename":"receipt_alt.mustache"}}')