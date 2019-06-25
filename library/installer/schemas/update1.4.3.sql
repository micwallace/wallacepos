ALTER TABLE `sale_items` ADD `tax_incl` int(1) NOT NULL DEFAULT 1 AFTER `tax`;
ALTER TABLE `sale_items` ADD `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `tax_incl`;
ALTER TABLE `sale_items` ADD `unit_original` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `cost`;



