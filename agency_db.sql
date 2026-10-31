-- ====================================================================
-- Database Schema & Sample Data for GA LKG MW (Manulife Medan)
-- Sistem Informasi Manajemen Presensi & Monitoring Aktivitas Harian Agen
-- Penulis: Erdaniwati (Institut Teknologi dan Bisnis Indonesia / ITBI)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `agency_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agency_db`;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(100) NOT NULL DEFAULT 'GA LKG MW',
  `site_description` varchar(255) NOT NULL DEFAULT 'Manulife Medan',
  `logo_url` varchar(255) DEFAULT '',
  `favicon_url` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping default data for table `settings`
INSERT INTO `settings` (`id`, `site_name`, `site_description`, `logo_url`, `favicon_url`) VALUES
(1, 'GA LKG MW', 'Manulife Medan', '', '');

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_lisensi` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Agen') NOT NULL DEFAULT 'Agen',
  `status` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`),
  UNIQUE KEY `idx_users_no_lisensi` (`no_lisensi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping seed data for table `users`
-- Default password:
-- Admin: admin123
-- Agen: agen123
INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `no_lisensi`, `password`, `role`, `status`, `profile_photo`, `created_at`) VALUES
(1, 'Staf Administrasi (DWY / TABITA)', 'admin@manulife.co.id', 'ADMIN001', '$2y$10$b4N24weD0VtjP7rEHxdQ2.Kjyh8XRgIOTpxeLq06S9/DGN8bAVBi.', 'Admin', 'Aktif', NULL, CURRENT_TIMESTAMP),
(2, 'Minaria Flora', 'minaria@manulife.co.id', 'AAJI-0012345', '$2y$10$hvVEb8HDIcmvqqTVGHowQOymSX.Om2e35sxz46lJZCKCtIOHqVbD6', 'Agen', 'Aktif', NULL, CURRENT_TIMESTAMP),
(3, 'Anatasya Falty', 'anatasya@manulife.co.id', 'AAJI-0012346', '$2y$10$hvVEb8HDIcmvqqTVGHowQOymSX.Om2e35sxz46lJZCKCtIOHqVbD6', 'Agen', 'Aktif', NULL, CURRENT_TIMESTAMP);

-- --------------------------------------------------------
-- Table structure for table `presensi`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `presensi`;
CREATE TABLE `presensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal_waktu` datetime NOT NULL,
  `status_kehadiran` varchar(50) NOT NULL,
  `foto_bukti` longtext DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_presensi_user` (`user_id`),
  CONSTRAINT `fk_presensi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_tracking`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_tracking`;
CREATE TABLE `activity_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kategori_aktivitas` varchar(50) NOT NULL,
  `nama_klien` varchar(150) NOT NULL,
  `deskripsi_kegiatan` text NOT NULL,
  `hasil_aktivitas` varchar(255) NOT NULL,
  `poin` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_activity_user` (`user_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `monitoring_rekrut`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `monitoring_rekrut`;
CREATE TABLE `monitoring_rekrut` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kandidat` varchar(150) NOT NULL,
  `level` varchar(50) NOT NULL,
  `coded_date` date NOT NULL,
  `nr` varchar(50) DEFAULT NULL,
  `nra` varchar(50) DEFAULT NULL,
  `perekrut` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
