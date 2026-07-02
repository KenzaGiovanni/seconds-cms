CREATE TABLE `t_provinsi` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(32) NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_provinsi` (`id`, `nama`, `latitude`, `longitude`) VALUES
('99', 'Test Province', 0, 0);

CREATE TABLE `t_kota` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(32) NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_kota` (`id`, `nama`, `latitude`, `longitude`) VALUES
('9901', 'Test Regency A', 0, 0),
('9902', 'Test Regency B', 0, 0);

CREATE TABLE `t_kecamatan` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(32) NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_kecamatan` (`id`, `nama`, `latitude`, `longitude`) VALUES
('990101', 'Test District A1', 0, 0),
('990201', 'Test District B1', 0, 0);

CREATE TABLE `t_kelurahan` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(32) NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_kelurahan` (`id`, `nama`, `latitude`, `longitude`) VALUES
('9901012001', 'Test Village', 2.9310948032, 97.4845840426);
