-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Jan 2026 pada 14.38
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
-- Database: `lostfound`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `claimer_name` varchar(80) NOT NULL,
  `claimer_contact` varchar(120) NOT NULL,
  `proof_text` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `user_name` varchar(80) NOT NULL,
  `user_contact` varchar(120) NOT NULL,
  `location` varchar(120) NOT NULL,
  `date_reported` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_claimed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `items`
--

INSERT INTO `items` (`id`, `title`, `description`, `status`, `user_name`, `user_contact`, `location`, `date_reported`, `image_path`, `is_claimed`, `created_at`, `type`) VALUES
(0, 'Dompet Kulit Hitam', 'Dompet kulit hitam berisi kartu identitas dan uang', 'open', 'Ahmad Rizki', '081234567890', 'Perpustakaan', '2026-01-04', NULL, 0, '2026-01-05 12:52:38', 'lost'),
(0, 'HP Samsung Galaxy', 'HP Samsung warna hitam, kondisi masih baru', 'open', 'Budi Santoso', 'budi@email.com', 'Kantin', '2026-01-04', NULL, 0, '2026-01-05 12:52:38', 'found'),
(0, 'Kunci Motor Honda', 'Kunci motor Honda dengan gantungan karakter', 'open', 'Citra Dewi', '081234567891', 'Parkiran', '2026-01-03', NULL, 0, '2026-01-05 12:52:38', 'lost'),
(0, 'Tas Ransel Biru', 'Tas ransel warna biru merk Eiger', 'returned', 'Dian Pratama', 'dian@email.com', 'Lab Komputer', '2026-01-02', NULL, 1, '2026-01-05 12:52:38', 'found'),
(0, 'Buku Catatan Bergaris', 'Buku catatan bergaris ukuran A5', 'returned', 'Eko Wijaya', '081234567892', 'Kelas A1', '2026-01-01', NULL, 1, '2026-01-05 12:52:38', 'lost'),
(0, 'Jam Tangan Analog', 'Jam tangan analog merk Casio', 'returned', 'Fajar Nugroho', 'fajar@email.com', 'Lapangan', '2025-12-30', NULL, 1, '2026-01-05 12:52:38', 'found');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'admin', 'admin@lostfound.com', 'hashed_password', 'Administrator', '081234567890', '2026-01-01 00:00:00', NULL, 1),
(2, 'user1', 'user1@email.com', 'hashed_password', 'User Satu', '081234567891', '2026-01-02 00:00:00', NULL, 1),
(3, 'user2', 'user2@email.com', 'hashed_password', 'User Dua', '081234567892', '2026-01-03 00:00:00', NULL, 1);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
