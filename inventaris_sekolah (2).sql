-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Bulan Mei 2025 pada 16.00
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'elektronik', 'jfjghjfhjgh'),
(2, 'mebel', 'drfhg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `qrcode_image` varchar(255) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `item_condition` varchar(255) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `barcode` varchar(255) DEFAULT NULL,
  `status` enum('bisadipinjam','tidakbisadipinjam') NOT NULL DEFAULT 'bisadipinjam',
  `total` int(11) NOT NULL,
  `repair_status` enum('tidak_diperbaiki','diperbaiki') NOT NULL DEFAULT 'tidak_diperbaiki'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `items`
--

INSERT INTO `items` (`id`, `qrcode_image`, `name`, `item_code`, `category_id`, `purchase_date`, `price`, `image`, `item_condition`, `room_id`, `quantity`, `barcode`, `status`, `total`, `repair_status`) VALUES
(78, NULL, 'kulkas', 'KUL-01-250413-X0N9L', 1, '2025-03-30', 1000.00, 'images.jpeg', 'baik', 3, 1, 'barcodes/qr_KUL-01-250413-X0N9L_78.png', 'bisadipinjam', 1, 'diperbaiki'),
(79, NULL, 'kulkas', 'KUL-01-250413-T6KAC', 1, '2025-03-30', 1000.00, 'images.jpeg', 'rusak ringan', 3, 2, 'barcodes/qr_KUL-01-250413-T6KAC_79.png', 'tidakbisadipinjam', 1, 'tidak_diperbaiki'),
(81, NULL, 'kulkas', 'KUL-01-250413-7HC3T', 1, '2025-04-12', 34555.00, 'images.jpeg', 'baik', 3, 1, 'barcodes/qr_KUL-01-250413-7HC3T_81.png', 'bisadipinjam', 1, 'diperbaiki'),
(82, NULL, 'kulkas', 'KUL-01-250413-6E8AC', 1, '2025-04-12', 34555.00, 'images.jpeg', 'rusak ringan', 3, 1, 'barcodes/qr_KUL-01-250413-6E8AC_82.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(103, NULL, 'kulkas', 'KUL-01-250512-IZ59A', 1, '2025-04-28', 1222.00, '9890.jpeg', 'rusak ringan', 3, 1, 'barcodes/qr_KUL-01-250512-IZ59A_103.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(104, NULL, 'kulkas', 'KUL-01-250512-DVLZ6', 1, '2025-04-28', 1222.00, '9890.jpeg', 'rusak ringan', 3, 1, 'barcodes/qr_KUL-01-250512-DVLZ6_104.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(105, NULL, 'drhds', 'DRH-01-250512-OFQSA', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 0, 'barcodes/qr_DRH-01-250512-OFQSA_105.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(106, NULL, 'drhds', 'DRH-01-250512-0AQUN', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-0AQUN_106.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(109, NULL, 'drhds', 'DRH-01-250512-LSZOM', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-LSZOM_109.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(110, NULL, 'drhds', 'DRH-01-250512-D2POE', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-D2POE_110.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(111, NULL, 'drhds', 'DRH-01-250512-TR5SU', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-TR5SU_111.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(112, NULL, 'drhds', 'DRH-01-250512-WDVZB', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-WDVZB_112.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(113, NULL, 'drhds', 'DRH-01-250512-KNM4D', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-KNM4D_113.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(114, NULL, 'drhds', 'DRH-01-250512-W9SAK', 1, '2025-05-15', 200.00, '9891.jpeg', 'baik', 2, 1, 'barcodes/qr_DRH-01-250512-W9SAK_114.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(115, NULL, 'fn', 'FN-01-250512-3NPZS', 1, '2025-05-08', 200.00, '9892.jpeg', 'rusak ringan', 3, 0, 'barcodes/qr_FN-01-250512-3NPZS_115.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(116, NULL, 'tgfjg', 'TGF-01-250512-A5FEY', 1, '2025-05-08', 10000.00, '9893.jpg', 'rusak berat', 2, 1, 'barcodes/qr_TGF-01-250512-A5FEY_116.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(117, NULL, 'tgfjg', 'TGF-01-250512-INZTK', 1, '2025-05-08', 10000.00, '9893.jpg', 'rusak berat', 2, 1, 'barcodes/qr_TGF-01-250512-INZTK_117.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(118, NULL, 'fxc', 'FXC-01-250512-68Q9I', 1, '2025-05-12', 6662.00, '9894.png', 'rusak ringan', 2, 1, 'barcodes/qr_FXC-01-250512-68Q9I_118.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(119, NULL, 'gffm', 'GFF-02-250512-TFRB6', 2, '2025-05-09', 444.00, '9895.png', 'baik', 3, 1, 'barcodes/qr_GFF-02-250512-TFRB6_119.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(120, NULL, 'kulkas', 'KUL-01-250522-0JU23', 1, '2025-05-22', 111.00, '9896.jpeg', 'rusak ringan', 2, 1, 'barcodes/qr_KUL-01-250522-0JU23_120.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(121, NULL, 'kulkas', 'KUL-01-250522-XE20B', 1, '2025-05-22', 111.00, '9897.jpeg', 'rusak ringan', 3, 1, 'barcodes/qr_KUL-01-250522-XE20B_121.png', 'bisadipinjam', 1, 'tidak_diperbaiki'),
(122, NULL, 'kulkas', 'KUL-01-250522-TRO1E', 1, '2025-05-22', 111.00, '9898.jpeg', 'rusak ringan', 3, 1, 'barcodes/qr_KUL-01-250522-TRO1E_122.png', 'bisadipinjam', 1, 'tidak_diperbaiki');

-- --------------------------------------------------------

--
-- Struktur dari tabel `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `timestamp`) VALUES
(1, 12, 'bulk_delete_item', 'items', 80, '{\"id\":\"80\",\"qrcode_image\":null,\"name\":\"kulkas\",\"item_code\":\"KUL-01-250413-6FHQ1\",\"category_id\":\"1\",\"purchase_date\":\"2025-04-12\",\"price\":\"34555.00\",\"image\":\"images.jpeg\",\"item_condition\":\"baik\",\"room_id\":\"2\",\"quantity\":\"1\",\"barcode\":\"barcodes\\/qr_KUL-01-250413-6FHQ1_80.png\",\"status\":\"bisadipinjam\",\"total\":\"1\",\"repair_status\":\"diperbaiki\"}', NULL, '2025-05-22 21:04:33'),
(2, 12, 'add_room', 'rooms', 9, NULL, '{\"id\":9,\"name\":\"fy\",\"location\":\"yhg\"}', '2025-05-22 21:12:50'),
(3, 14, 'approve_loan', 'peminjaman', 92, '{\"id\":92,\"user_id\":13,\"item_id\":103,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"}', '{\"id\":92,\"user_id\":13,\"item_id\":103,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":\"0000-00-00\",\"jumlah\":1,\"status\":\"dipinjam\"}', '2025-05-22 21:24:19'),
(4, 13, 'undo_approve_loan', 'peminjaman', 92, '{\"action\":\"approve_loan\",\"table\":\"peminjaman\",\"record_id\":92,\"old_data\":{\"id\":92,\"user_id\":13,\"item_id\":103,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"},\"new_data\":{\"id\":92,\"user_id\":13,\"item_id\":103,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":\"0000-00-00\",\"jumlah\":1,\"status\":\"dipinjam\"}}', '{\"undone_log_id\":3}', '2025-05-22 21:34:37'),
(5, 12, 'add_room', 'rooms', 10, NULL, '{\"id\":10,\"name\":\"fmc\",\"location\":\"lantai 1 depan\"}', '2025-05-24 20:52:47'),
(6, 12, 'start_item_repair', 'items', 78, '{\"id\":78,\"item_condition\":\"rusak ringan\",\"repair_status\":\"tidak_diperbaiki\",\"status\":\"bisadipinjam\"}', '{\"repair_status\":\"dalam_perbaikan\",\"status\":\"tidakbisadipinjam\"}', '2025-05-24 20:53:02'),
(7, 12, 'add_maintenance_record', 'maintenance', 11, NULL, '{\"id\":11,\"item_id\":78,\"maintenance_date\":\"2025-05-24\",\"description\":\"shs\",\"cost\":4444,\"technician\":\"xz\",\"status\":\"dalam_perbaikan\"}', '2025-05-24 20:53:02'),
(8, 12, 'complete_maintenance_record', 'maintenance', 11, '{\"id\":11,\"status\":\"dalam_perbaikan\"}', '{\"status\":\"selesai\"}', '2025-05-24 20:53:05'),
(9, 12, 'item_repair_completed', 'items', 78, '{\"id\":78,\"item_condition\":\"rusak ringan\",\"repair_status\":\"\",\"status\":\"tidakbisadipinjam\"}', '{\"repair_status\":\"diperbaiki\",\"item_condition\":\"baik\",\"status\":\"bisadipinjam\"}', '2025-05-24 20:53:05'),
(10, 12, 'delete_item', 'items', 108, '{\"id\":108,\"qrcode_image\":null,\"name\":\"drhds\",\"item_code\":\"DRH-01-250512-658HC\",\"category_id\":1,\"purchase_date\":\"2025-05-15\",\"price\":\"200.00\",\"image\":\"9891.jpeg\",\"item_condition\":\"baik\",\"room_id\":2,\"quantity\":1,\"barcode\":\"barcodes\\/qr_DRH-01-250512-658HC_108.png\",\"status\":\"bisadipinjam\",\"total\":1,\"repair_status\":\"tidak_diperbaiki\"}', NULL, '2025-05-24 20:53:30'),
(11, 13, 'update_item_status', 'items', 79, '{\"status\":\"bisadipinjam\"}', '{\"status\":\"tidakbisadipinjam\"}', '2025-05-24 20:55:14'),
(12, 13, 'approve_loan', 'peminjaman', 94, '{\"id\":94,\"user_id\":14,\"item_id\":105,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"}', '{\"id\":94,\"user_id\":14,\"item_id\":105,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":\"0000-00-00\",\"jumlah\":1,\"status\":\"dipinjam\"}', '2025-05-24 20:55:49'),
(13, 13, 'reject_loan', 'peminjaman', 95, '{\"id\":95,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"}', '{\"id\":95,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":\"2025-05-24\",\"jumlah\":1,\"status\":\"ditolak\"}', '2025-05-24 20:55:52'),
(14, 13, 'update_item_quantity_after_loan', 'items', 115, '{\"id\":115,\"quantity\":0}', '{\"id\":115,\"quantity\":1}', '2025-05-24 20:55:52'),
(15, 13, 'reject_loan', 'peminjaman', 93, '{\"id\":93,\"user_id\":13,\"item_id\":104,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"}', '{\"id\":93,\"user_id\":13,\"item_id\":104,\"tanggal_pinjam\":\"2025-05-19\",\"tanggal_kembali\":\"2025-05-24\",\"jumlah\":1,\"status\":\"ditolak\"}', '2025-05-24 20:55:56'),
(16, 13, 'update_item_quantity_after_loan', 'items', 104, '{\"id\":104,\"quantity\":0}', '{\"id\":104,\"quantity\":1}', '2025-05-24 20:55:56'),
(17, 13, 'approve_loan', 'peminjaman', 96, '{\"id\":96,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":null,\"jumlah\":1,\"status\":\"diajukan\"}', '{\"id\":96,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":\"0000-00-00\",\"jumlah\":1,\"status\":\"dipinjam\"}', '2025-05-24 20:56:18'),
(18, 13, 'confirm_return', 'peminjaman', 96, '{\"id\":96,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":\"2025-05-24\",\"jumlah\":1,\"status\":\"menunggu konfirmasi\"}', '{\"id\":96,\"user_id\":13,\"item_id\":115,\"tanggal_pinjam\":\"2025-05-24\",\"tanggal_kembali\":\"2025-05-24\",\"jumlah\":1,\"status\":\"dikembalikan\"}', '2025-05-24 20:56:30'),
(19, 13, 'update_item_quantity_after_loan', 'items', 115, '{\"id\":115,\"quantity\":0}', '{\"id\":115,\"quantity\":1}', '2025-05-24 20:56:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('dalam_perbaikan','selesai') DEFAULT 'dalam_perbaikan',
  `cost` decimal(10,2) DEFAULT NULL,
  `technician` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `maintenance`
--

INSERT INTO `maintenance` (`id`, `item_id`, `maintenance_date`, `description`, `status`, `cost`, `technician`) VALUES
(10, 81, '2025-05-15', 'tymkyuk', 'selesai', 76653.00, 'fd'),
(11, 78, '2025-05-24', 'shs', 'selesai', 4444.00, 'xz');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `status` enum('diajukan','menunggu konfirmasi','dipinjam','dikembalikan','ditolak') NOT NULL DEFAULT 'diajukan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `user_id`, `item_id`, `tanggal_pinjam`, `tanggal_kembali`, `jumlah`, `status`) VALUES
(61, 13, 79, '2025-04-16', '2025-04-16', 1, 'dikembalikan'),
(62, 13, 78, '2025-04-16', '2025-04-16', 1, 'dikembalikan'),
(64, 13, 78, '2025-04-16', '2025-04-16', 1, 'ditolak'),
(65, 12, 78, '2025-04-16', '2025-04-17', 1, 'ditolak'),
(66, 13, 79, '2025-04-18', '2025-04-18', 1, 'ditolak'),
(67, 13, 78, '2025-04-21', '0000-00-00', 1, 'dikembalikan'),
(68, 13, 79, '2025-04-21', '0000-00-00', 1, 'dikembalikan'),
(69, 13, 79, '2025-04-21', '2025-04-21', 1, 'ditolak'),
(70, 13, 81, '2025-04-21', '2025-04-21', 1, 'ditolak'),
(73, 13, 79, '2025-04-21', '0000-00-00', 1, 'dikembalikan'),
(74, 13, 81, '2025-04-21', '2025-04-21', 1, 'ditolak'),
(75, 13, 82, '2025-04-21', '2025-04-21', 1, 'ditolak'),
(92, 13, 103, '2025-05-19', '2025-05-22', 1, 'ditolak'),
(93, 13, 104, '2025-05-19', '2025-05-24', 1, 'ditolak'),
(94, 14, 105, '2025-05-24', '0000-00-00', 1, 'dipinjam'),
(95, 13, 115, '2025-05-24', '2025-05-24', 1, 'ditolak'),
(96, 13, 115, '2025-05-24', NULL, 1, 'dipinjam');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `location`) VALUES
(2, 'asm1', 'lantai1'),
(3, 'asm2', 'lantai1'),
(5, 'pc', 'lantai1'),
(6, 'kulkas', 'lantai1'),
(9, 'fy', 'yhg'),
(10, 'fmc', 'lantai 1 depan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'profile/default.jpg',
  `role` varchar(30) NOT NULL DEFAULT 'user',
  `theme_mode` enum('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `role`, `theme_mode`) VALUES
(3, 'Ucok_Baba', 'kempligimang@gmail.com', '$2y$10$oGcMTGUbUeMcIw.dlRJrBuZEgJA7J4Mtpq5BlKlBRt3FeV.BOpW8W', 'profile/default.jpg', 'user', 'light'),
(6, 'bagas', 'bagas88@gmail.com', '$2y$10$OXu0p/2jUnqb8sWOC2cizuUmQDe0Q/Gqqrff7NQKfjaiySEqVMfUu', 'profile/default.jpg', 'user', 'light'),
(7, 'agus', 'aguskotak@gmail.com', '$2y$10$OtTwbNsiL21584fgL.fy7.TV.oxpUbl6YOkv48u6l9FQafmDGqV3i', 'profile/default.jpg', 'user', 'light'),
(8, 'agus444', 'juga@gmail.com', '$2y$10$ExpSQ3UOKrnGDF1bZqZlo.M6HPrzOkyAvVWLJS7S9kdR38UgOm5F.', 'profile/default.jpg', 'user', 'light'),
(9, 'juta', 'jota555@gmail.com', '$2y$10$IqDegWejt2wj0VQuM6w9r.4HTAIRbmP3.hVOCd6ZKvPHggqO98ERq', 'profile/default.jpg', 'user', 'light'),
(10, 'mna', 'dava.abhirama21@smk.belajar.id', '$2y$10$IyMqbunUsFraIocCGnToe.35/Vmo93OxgJYvPPWsNW/YHRbfKP/0S', 'profile/default.jpg', 'user', 'light'),
(11, 'da', 'da.abhirama21@smk.belajar.id', '$2y$10$MKxe2fwt7l.5a7sQvtar0e80V8NK5VX75Q4mPoiZfBZ64Yc2wcgwG', 'profile/default.jpg', 'user', 'light'),
(12, 'das', 'dasiramap@gmail.com', '$2y$10$TH0y53CyHt.O/SXLwmjeG.Zk7gd2RzLLl6Nxqf3aThdIgLQm6B6ue', 'profile/default.jpg', 'admin', 'light'),
(13, 'dar', 'dava.dar@smk.belajar.id', '$2y$10$9.CTHAn1OLzmRo4bEBtE8.Y8Kmrabyl//MJgp5YWmnQUlbuYhd1XC', 'profile/default.jpg', 'user', 'light'),
(14, 'daz', 'daz.abhirama21@smk.belajar.id', '$2y$10$fq.CNF2WWlUx8l/gTs.v2uRE7IQILd/vSM2u911wXQzN2hr9VzOLK', 'profile/default.jpg', 'superadmin', 'light'),
(15, 'dark', 'cava.abhirama21@smk.belajar.id', '$2y$10$ayQ08OQQ0WvK2NT3r8TqVuKo98e6lAs.q1JeMJV8n.iwwwsF99TK6', 'profile/default.jpg', 'superadmin', 'light'),
(16, 'rar', 'rasr@gmail.com', '$2y$10$Qkwg8Fyja8NidBQ.FxrE0O4zwDob7vnl0Co5D0QGExKM9.EukJaZu', 'profile/default.jpg', 'admin', 'light');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indeks untuk tabel `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT untuk tabel `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT untuk tabel `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
