-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Sep 2025 pada 11.16
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
-- Database: `wisata_umkm_production`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` float DEFAULT 0,
  `facilities` text DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `description`, `location`, `price`, `category`, `image`, `rating`, `facilities`, `activities`, `created_at`, `is_featured`) VALUES
(2, 'Pantai Panjang', 'Jl. Pariwisata Pantai Panjang, Lempuing, Kec. Ratu Agung, Kota Bengkulu, Bengkulu', 'Kota Bengkulu', 100000.00, 'Pantai', 'uploads/destinations/68cbde8ab2ce4_1758191242.jpg', 4, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-18 06:59:47', 0),
(3, 'Benteng Marlborough', 'Jl. Benteng, Kebun Keling, Kec. Tlk. Segara, Kota Bengkulu, Bengkulu 38116', 'Kota Bengkulu', 50000.00, 'Sejarah', 'uploads/destinations/68cbf97824210_1758198136.jpg', 4.4, 'Parkir Luas\nToilet Bersih\nArea Makan\nSpot Foto', 'Berkemah\nHiking\nBerenang\nFotografi', '2025-09-18 12:22:16', 0),
(4, 'Danau Dendam Tak Sudah', 'Danau Dam Peninggalan Belanda', 'Kota Bengkulu', 20000.00, 'Danau', 'uploads/destinations/68cc0f008720b_1758203648.jpg', 3.9, 'Parkir Luas\nToilet Bersih\nArea Makan\nSpot Foto', 'Berkemah\nHiking\nBerenang\nFotografi', '2025-09-18 13:54:08', 0),
(5, 'Bukit Kaba', 'Wisata Alam Bukit Kaba Rejang Lebong', 'Sambirejo, Kec. Selupu Rejang, Kabupaten Rejang Lebong, Bengkulu', 50000.00, 'Gunung', 'uploads/destinations/68cfe4f8857c2_1758455032.jpg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 11:43:52', 0),
(6, 'Kompleks Makam Inggris', 'Makam Peninggalan Penjajahan Inggris', 'Jl. Veteran Jitra, Ps. Jitra, Kec. Tlk. Segara, Kota Bengkulu, Bengkulu 38119', 10000.00, 'Sejarah', 'uploads/destinations/68cfe5dcdfd23_1758455260.jpeg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 11:47:40', 0),
(7, 'Masjid Jamik Kota Bengkulu', 'Masjid Jamik Masa Pemerintahan Ir. Soekarno', 'Jl. Letjend Suprapto, Tengah Padang, Kec. Ratu Samban, Kota Bengkulu, Bengkulu 38222', 20000.00, 'Religi', 'uploads/destinations/68cfe678ee1bb_1758455416.jpg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 11:50:17', 0),
(9, 'Festival Tabot Bengkulu', 'Salah satu budaya ciri khas Provinsi Bengkulu', 'Jl. Indracaya, Ps. Jitra, Kec. Tlk. Segara, Kota Bengkulu, Bengkulu', 20000.00, 'Budaya', 'uploads/destinations/68cfe9825d090_1758456194.jpg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 12:03:14', 0),
(10, 'Pantai Padang Betuah', 'Wisata Pantai Padang Betuah Bengkulu Tengah', 'Padang Betuah, Kec. Pd. Klp., Kabupaten Bengkulu Tengah, Bengkulu', 15000.00, 'Pantai', 'uploads/destinations/68cfea1bc5d79_1758456347.jpg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 12:05:47', 0),
(11, 'Gunung Bungkuk', 'Salah satu jalur hiking di Bengkulu', 'Rajak Besi, Kec. Merigi Sakti, Kabupaten Bengkulu Tengah, Bengkulu', 100000.00, 'Gunung', 'uploads/destinations/68cfeaa470bcf_1758456484.jpg', 0, 'Parkir Luas\r\nToilet Bersih\r\nArea Makan\r\nSpot Foto', 'Berkemah\r\nHiking\r\nBerenang\r\nFotografi', '2025-09-21 12:08:04', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `umkm_id` int(11) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `destination_id`, `umkm_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 3, 2, 1, 4, 'Terbaik!!!', 'active', '2025-09-18 08:51:22'),
(2, 3, NULL, 2, 4, 'Baksonya full daging, mantap!!!', 'active', '2025-09-21 04:35:00'),
(3, 3, NULL, 3, 5, 'Jumlah sate nya banyak gak seperti sate-sate pada umumnya. Sate madura paling rekomended', 'active', '2025-09-21 04:36:14'),
(4, 3, 3, NULL, 4, 'Suasana dan nilai sejarah nya sangat terasa', 'active', '2025-09-21 10:19:58'),
(5, 3, 4, NULL, 4, 'Cocok untuk santai-santai', 'active', '2025-09-21 10:20:34'),
(6, 3, NULL, 4, 5, 'Sangat enak dan crispy, daging cumi nya juga gak alot', 'active', '2025-09-21 10:32:42'),
(7, 5, NULL, 2, 5, 'Bakso favorit klo ke Bengkulu', 'active', '2025-09-21 11:32:12'),
(8, 5, 4, NULL, 4, 'Suasana masih sangat alami, bikin tenang', 'active', '2025-09-21 11:34:55'),
(9, 5, NULL, 5, 5, 'Nasi goreng nya enak, perpaduan bumbu dan ada smoky\" nya...', 'active', '2025-09-21 12:20:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `visit_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `destination_id`, `code`, `visit_date`, `quantity`, `total_price`, `status`, `created_at`, `used_at`) VALUES
(15, 3, 3, 'BKL-BEN-20250920234931', '2025-09-21', 5, 250000.00, 'paid', '2025-09-20 16:49:31', '2025-09-21 22:58:43'),
(16, 5, 4, 'BKL-DAN-20250921183236', '2025-09-22', 5, 100000.00, 'paid', '2025-09-21 11:32:36', NULL),
(17, 5, 2, 'BKL-PAN-20250921183652', '2025-09-22', 2, 200000.00, 'paid', '2025-09-21 11:36:52', '2025-09-21 21:10:39'),
(18, 5, 10, 'BKL-PAN-20250921192108', '2025-09-28', 5, 75000.00, 'paid', '2025-09-21 12:21:08', '2025-09-21 21:13:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `ticket_id`, `payment_method`, `status`, `amount`, `bukti_pembayaran`, `created_at`) VALUES
(23, 3, 15, 'transfer', 'success', 250000.00, '1758386982_APPLE.jpg', '2025-09-20 16:49:42'),
(24, 5, 16, 'transfer', 'success', 100000.00, '1758454374_Screenshot 2025-09-21 160638.png', '2025-09-21 11:32:54'),
(25, 5, 17, 'transfer', 'success', 200000.00, '1758454626_Screenshot 2025-09-19 181528.png', '2025-09-21 11:37:06'),
(26, 5, 18, 'transfer', 'success', 75000.00, '1758457282_Screenshot 2025-09-04 120956.png', '2025-09-21 12:21:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `umkms`
--

CREATE TABLE `umkms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` float DEFAULT 0,
  `operational_hours` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `umkms`
--

INSERT INTO `umkms` (`id`, `name`, `description`, `category`, `image`, `rating`, `operational_hours`, `contact_phone`, `contact_email`, `created_at`, `is_featured`) VALUES
(1, 'Mie Ayam Pangsit Buffet Tris', 'Mie Ayam Pangsit Terlaris Di Provinsi Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cbdde482190_1758191076.jpg', 4.5, 'Senin - Jumat: 08:00 - 17:00\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-18 08:00:00', 0),
(2, 'Bakso Manunggal', 'Bakso Urat Terenak Di Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cbf9b5cc23a_1758198197.jpg', 5, 'Senin - Jumat: 08:00 - 17:00\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@baksomanunggal.com', '2025-09-18 12:23:17', 0),
(3, 'Sate Madura', 'Sate Madura Terenak Di Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cc0f2790246_1758203687.jpg', 5, 'Senin - Jumat: 08:00 - 17:00\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@satemadura.com', '2025-09-18 13:54:47', 0),
(4, 'Cumi Crispy', 'Cumi Crispy Paling Laris Se-Kota Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cc1012001bd_1758203922.jpg', 4.2, 'Senin - Jumat: 08:00 - 22:00\r\nSabtu - Minggu: 07:00 - 18:00', '6282269302321', 'cumi_crispy_bkl@gmail.com', '2025-09-18 13:58:42', 0),
(5, 'Nasi Goreng', 'Nasi goreng paling laris di Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cfeb3220b3a_1758456626.jpg', 0, 'Senin - Jumat: 08:00 - 17:00\r\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-21 12:10:26', 0),
(6, 'Gulai Curry', 'Curry terenak di Provinsi Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cfebbe091a3_1758456766.jpg', 0, 'Senin - Jumat: 08:00 - 17:00\r\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-21 12:12:46', 0),
(7, 'Soto Lamongan', 'Soto asli dari lamongan yang buka di Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cfebd87331e_1758456792.jpg', 0, 'Senin - Jumat: 08:00 - 17:00\r\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-21 12:13:12', 0),
(8, 'Pecel Ayam', 'Pecel ayam terlaris di Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cfec375038b_1758456887.jpg', 0, 'Senin - Jumat: 08:00 - 17:00\r\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-21 12:14:47', 0),
(9, 'Dimsum Yummy', 'Dimsum terlaris di Provinsi Bengkulu', 'Makanan & Minuman', 'uploads/umkms/68cfed25a3f7a_1758457125.jpg', 0, 'Senin - Jumat: 08:00 - 17:00\r\nSabtu - Minggu: 09:00 - 15:00', '+62 812 3456 7890', 'info@mieayampangsitbuffettris.com', '2025-09-21 12:18:45', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `reset_token`, `reset_expires`, `points`, `avatar`, `phone`, `address`, `created_at`) VALUES
(2, 'Administrator', 'admin@gmail.com', '$2y$10$hTR0L4z8a573FtKrgviXFOZZaB/oABwcnvR3yZtyU84c2UQLdRoeO', 'admin', NULL, NULL, 0, NULL, NULL, NULL, '2025-09-17 14:20:51'),
(3, 'User 1', 'imansyahdicky007@gmail.com', '$2y$10$F51jm2qdQ9aUnTc8S8lpD.XLYWHd5RVznbP.1pOs9FSX6X5v0ew3a', 'user', NULL, NULL, 190, 'uploads/avatars/user_3_1758518669.jpg', '', '', '2025-09-18 04:15:25'),
(5, 'User Test', 'user2@gmail.com', '$2y$10$0qYQbGi47FwPd3CGN/Wnn.k4Cyo9P01uSxo5BelE2u3D52FFZwbmC', 'user', NULL, NULL, 160, 'uploads/avatars/user_5_1758474302.jpg', '081145371234', 'Jl. Pariwisata, Pantai Panjang', '2025-09-21 11:30:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','purchase','review','wishlist') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('destination','umkm') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destinations_name` (`name`),
  ADD KEY `idx_destinations_category` (`category`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `umkm_id` (`umkm_id`),
  ADD KEY `reviews_ibfk_2` (`destination_id`),
  ADD KEY `idx_reviews_created_at` (`created_at`),
  ADD KEY `idx_reviews_status` (`status`),
  ADD KEY `idx_reviews_user_id` (`user_id`),
  ADD KEY `idx_reviews_destination_id` (`destination_id`),
  ADD KEY `idx_reviews_umkm_id` (`umkm_id`);

--
-- Indeks untuk tabel `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tickets_ibfk_2` (`destination_id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indeks untuk tabel `umkms`
--
ALTER TABLE `umkms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_umkms_name` (`name`),
  ADD KEY `idx_umkms_category` (`category`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_name` (`name`);

--
-- Indeks untuk tabel `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `umkms`
--
ALTER TABLE `umkms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`);

--
-- Ketidakleluasaan untuk tabel `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`);

--
-- Ketidakleluasaan untuk tabel `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
