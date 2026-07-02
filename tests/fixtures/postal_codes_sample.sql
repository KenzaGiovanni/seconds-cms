CREATE TABLE `db_province_data` (
  `province_name` VARCHAR(100) NOT NULL,
  `province_name_en` VARCHAR(100) NOT NULL,
  `province_code` INT(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_province_data` (`province_name`, `province_name_en`, `province_code`) VALUES
('TEST PROVINCE', 'TEST PROVINCE', 99);

CREATE TABLE `db_postal_code_data` (
    `id` BIGINT(11) NOT NULL AUTO_INCREMENT,
    `urban` VARCHAR(100) NOT NULL,
    `sub_district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `province_code` INT(2) NOT NULL,
    `postal_code` varchar(5) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_postal_code_data` (`urban`, `sub_district`, `city`, `province_code`, `postal_code`) VALUES
('Test Urban One', 'Test District', 'Test City', 99, '99001'),
('Test Urban Two', 'Test District', 'Test City', 99, '99002'),
('Ambiguous Urban A', 'Shared District', 'Test City', 99, '99101'),
('Ambiguous Urban B', 'Shared District', 'Other City', 99, '99201'),
('Nowhere Urban', 'Nonexistent District', 'Ghost City', 99, '99901'),
('Spaced Urban', 'LOOSE DISTRICT', 'Test City', 99, '99301'),
('Parens Urban', 'ALTNAME (OLDNAME)', 'Test City', 99, '99401');
