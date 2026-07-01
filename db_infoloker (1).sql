-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 02:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_infoloker`
--

-- --------------------------------------------------------

--
-- Table structure for table `lamaran`
--

CREATE TABLE `lamaran` (
  `id_lamaran` int(11) NOT NULL,
  `id_loker` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tgl_melamar` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_lamaran` enum('Pending','Diterima','Ditolak') DEFAULT 'Pending',
  `file_lampiran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lamaran`
--

INSERT INTO `lamaran` (`id_lamaran`, `id_loker`, `id_user`, `tgl_melamar`, `status_lamaran`, `file_lampiran`) VALUES
(4, 7, 5, '2026-06-21 13:56:00', 'Diterima', NULL),
(5, 2, 5, '2026-06-21 14:57:20', 'Pending', NULL),
(7, 5, 5, '2026-06-21 16:05:27', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_validasi`
--

CREATE TABLE `log_validasi` (
  `id_log` int(11) NOT NULL,
  `id_lamaran` int(11) NOT NULL,
  `status_lama` varchar(20) DEFAULT NULL,
  `status_baru` varchar(20) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `diubah_oleh` int(11) NOT NULL,
  `tgl_perubahan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_validasi`
--

INSERT INTO `log_validasi` (`id_log`, `id_lamaran`, `status_lama`, `status_baru`, `catatan`, `diubah_oleh`, `tgl_perubahan`) VALUES
(3, 4, 'Pending', 'Diterima', 'SELAMAT ANDA DINYATAKAN LANJUT KE TAHAP INTERVIEW', 4, '2026-06-21 14:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `lowongan`
--

CREATE TABLE `lowongan` (
  `id_loker` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nama_perusahaan` varchar(100) NOT NULL,
  `posisi` varchar(100) NOT NULL,
  `gaji` varchar(50) NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `tipe` enum('Full-time','Part-time','Remote') NOT NULL,
  `minimal_pendidikan` varchar(100) DEFAULT 'Semua Jenjang',
  `deskripsi` text NOT NULL,
  `berkas_wajib` varchar(255) DEFAULT 'CV',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lowongan`
--

INSERT INTO `lowongan` (`id_loker`, `id_user`, `nama_perusahaan`, `posisi`, `gaji`, `lokasi`, `tipe`, `minimal_pendidikan`, `deskripsi`, `berkas_wajib`, `status`, `created_at`) VALUES
(1, 6, 'Google', 'Web Developer', '18.000.000', 'jakarta pusat', 'Part-time', 'Sarjana (S1)', 'Mengembangkan dan memelihara aplikasi web berskala...', 'CV, Portofolio', 'aktif', '2026-06-21 14:43:55'),
(2, 7, 'Gojek', 'Data Analyst', '12.000.000', 'Jakarta Selatan', 'Full-time', 'Sarjana (S1)', 'Mengolah data mentah menjadi wawasan bisnis yang strategis dan membuat dashboard laporan realtime.', 'CV, Portofolio', 'aktif', '2026-06-21 14:48:08'),
(3, 8, 'Tokopedia', 'UI Designer', '15.000.000', 'Surabaya', 'Full-time', 'Sarjana (S1)', 'Mengelola dan mengembangkan sebuah Web/Apk', 'CV, Portofolio', 'aktif', '2026-06-21 15:15:30'),
(4, 9, 'Bukalapak', 'UX Designer', '10.000.000', 'Semarang', 'Remote', 'Sarjana (S1)', 'Mengelola infrastruktur cloud server dan menyusun pipeline CI/CD untuk otomatisasi deployment.', 'CV,, Portofolio', 'aktif', '2026-06-21 15:00:22'),
(5, 10, 'Shopee', 'DevOps Engineer', '20.000.000', 'Remote', 'Remote', 'Sarjana (S1)', 'Mengelola infrastruktur cloud server dan menyusun pipeline CI/CD untuk otomatisasi deployment', 'CV,  Portofolio', 'aktif', '2026-06-21 15:10:06'),
(6, 2, 'PT MENCARI CINTA SEJATI', 'DIREKTUR', '10.000.000 - 15.000.000', 'JAKARTA PUSAT', 'Full-time', 'Semua Jenjang', 'MENJADI DIREKTUR MEMBANTU JALANNYA SEBUAH PERUSAHAAN', 'CV', 'aktif', '2026-06-08 15:17:16'),
(7, 4, 'Ruang Guru', 'Guru Les Bahasa Jerman', '7.000.000 - 10.000.000', 'bandung', 'Full-time', 'Sarjana (S1)', 'Dibutuhkan segera guru les bahasa jerman ', 'CV, Portofolio', 'aktif', '2026-06-21 13:42:47'),
(15, 12, 'PT Bank Central Asia Tbk', 'Relationship Officer (RO)', '9.000.000', 'Jakarta pusat', 'Full-time', 'Sarjana (S1)', 'Membangun hubungan baik dengan nasabah baru maupun lama, menganalisis kelayakan kredit, serta menawarkan produk pendanaan dan pinjaman perbankan secara profesional.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:29:37'),
(16, 13, 'Net TV Media', 'Video Editor', '7.500.000', 'Jakarta selatan', 'Full-time', 'Diploma (D3)', 'Melakukan proses editing video, grading warna, serta menambahkan efek visual dan audio untuk kebutuhan konten program televisi dan media sosial perusahaan.', 'CV, Portofolio', 'aktif', '2026-06-21 15:32:01'),
(17, 4, 'Ruang Guru', 'Content Writer Edukasi', '6.000.000', 'Remote', 'Remote', 'Sarjana (S1)', 'Menyusun artikel pendidikan, naskah materi belajar yang interaktif, dan konten edukatif yang mudah dipahami oleh siswa sekolah dasar hingga menengah.', 'CV,Portofolio', 'aktif', '2026-06-21 15:34:37'),
(18, 14, 'Kopi Kenangan', 'Barista & Cashier', '4.500.000', 'parepare', 'Part-time', 'SMA/SMK Sederajat', 'Meracik dan menyajikan minuman sesuai standar perusahaan, melayani transaksi pembayaran konsumen dengan ramah, serta menjaga kebersihan area outlet.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:37:44'),
(19, 15, 'PT Ace Hardware Indonesia', 'Store Supervisor', '8.000.000', 'surabaya', 'Full-time', 'Diploma (D3)', 'Mengawasi operasional harian toko, memimpin tim pramuniaga, memantau ketersediaan stok barang, serta memastikan target penjualan toko tercapai.', 'CV, Portofolio', 'aktif', '2026-06-21 15:39:43'),
(20, 16, 'J&#38;T Express', 'Staff Administrasi Gudang', '5.200.000', 'Tanggerang', 'Full-time', 'SMA/SMK Sederajat', 'Mencatat arus masuk dan keluar barang, melakukan rekonsiliasi data pengiriman harian, serta membuat laporan inventaris gudang secara berkala menggunakan Excel.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:42:03'),
(21, 7, 'Gojek', 'Customer Service Officer', '5.500.000', 'yogyakarta', 'Full-time', 'Diploma (D3)', 'Menangani keluhan dan pertanyaan dari mitra driver maupun pelanggan melalui telepon, chat, dan email dengan memberikan solusi yang cepat dan tepat.', 'CV, Portofolio', 'aktif', '2026-06-21 15:44:06'),
(22, 17, 'Unilever Indonesia', 'Digital Marketing Specialist', '12.000.000', 'Jakarta selatan', 'Full-time', 'Sarjana (S1)', 'Merencanakan dan mengeksekusi kampanye iklan digital di Meta Ads dan Google Ads, mengoptimalkan SEO web, serta menganalisis tren pasar digital.', 'CV, Portofolio', 'aktif', '2026-06-21 15:46:29'),
(23, 18, 'Decorus Interior Design', 'Interior Designer', '9.500.000', 'Bali', 'Full-time', 'Sarjana (S1)', 'Membuat konsep tata ruang, pemodelan 3D, serta memilih material dan furnitur yang sesuai dengan permintaan dan anggaran klien.', 'CV, Portofolio', 'aktif', '2026-06-21 15:48:24'),
(24, 19, 'Hotel Aston', 'Front Office Staff', '5.000.000', 'semarang', 'Full-time', 'Diploma (D3)', 'Menyambut tamu hotel, melayani proses check-in dan check-out, serta memberikan informasi mengenai fasilitas hotel dan destinasi wisata sekitar.', 'CV, Portofolio', 'aktif', '2026-06-21 15:49:58'),
(25, 20, 'PT Astra International Tbk', 'HR Recruitment Staff', '8.500.000', 'jakarta utara', 'Full-time', 'Sarjana (S1)', 'Mengelola proses rekrutmen karyawan mulai dari penyaringan CV, pelaksanaan psikotes, wawancara kerja, hingga proses onboarding karyawan baru.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:51:55'),
(26, 21, 'Kimia Farma', 'Apoteker', '7.000.000', 'Makassar', 'Full-time', 'Diploma (D3)', 'Melayani peracikan obat berdasarkan resep dokter, memberikan edukasi mengenai cara penggunaan obat kepada pasien, serta mengontrol penyimpanan stok obat.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:53:46'),
(27, 22, 'Toyota Astra Motor', 'Mekanik Mobil', '6.000.000', 'Surabaya', 'Full-time', 'SMA/SMK Sederajat', 'Melakukan servis berkala, perbaikan mesin, penyetelan sistem kelistrikan, serta troubleshooting masalah teknis pada kendaraan roda empat milik pelanggan.', 'CV, Portofolio', 'aktif', '2026-06-21 15:55:54'),
(28, 22, 'Toyota Astra Motor', 'Mekanik Motor', '5.000.000', 'Surabaya', 'Full-time', 'SMA/SMK Sederajat', 'Melakukan servis berkala, perbaikan mesin, penyetelan sistem kelistrikan, serta troubleshooting masalah teknis pada kendaraan roda dua milik pelanggan.', 'CV, Portofolio', 'aktif', '2026-06-21 15:56:40'),
(29, 8, 'Tokopedia', 'Public Relations (PR) Staff', '11.000.000', 'Jakarta Selatan', 'Full-time', 'Sarjana (S1)', 'Menyusun siaran pers (press release), menjalin hubungan baik dengan media, serta mengelola komunikasi krisis untuk menjaga reputasi positif perusahaan.', 'CV,  Portofolio', 'aktif', '2026-06-21 15:58:08'),
(30, 23, 'SweetEscape Indonesia', 'Freelance Photographer', '4.000.000', 'Denpasar', 'Part-time', 'SMA/SMK Sederajat', 'Mengambil foto portrait, momen liburan, atau event klien dengan teknik pencahayaan dan komposisi estetis, serta melakukan proses penyuntingan dasar foto.', 'CV, Portofolio', 'aktif', '2026-06-21 16:00:14');

-- --------------------------------------------------------

--
-- Table structure for table `lowongan_tersimpan`
--

CREATE TABLE `lowongan_tersimpan` (
  `id_simpan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_loker` int(11) NOT NULL,
  `tgl_disimpan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lowongan_tersimpan`
--

INSERT INTO `lowongan_tersimpan` (`id_simpan`, `id_user`, `id_loker`, `tgl_disimpan`) VALUES
(1, 3, 6, '2026-06-08 15:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `profil_pelamar`
--

CREATE TABLE `profil_pelamar` (
  `id_profil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) DEFAULT NULL,
  `keahlian` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_pelamar`
--

INSERT INTO `profil_pelamar` (`id_profil`, `id_user`, `no_telp`, `pendidikan_terakhir`, `keahlian`, `cv_file`, `updated_at`) VALUES
(1, 3, '0888888', 'S1 NUCLEER ENGINEERING', 'ATOM', 'cv_3_1780930690.pdf', '2026-06-08 14:58:10'),
(2, 5, '088', 'S1 BAHASA JERMAN', 'Fasih Berbahasa Jerman', 'cv_5_1782050154.pdf', '2026-06-21 13:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `profil_perusahaan`
--

CREATE TABLE `profil_perusahaan` (
  `id_profil_corp` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_perusahaan` varchar(150) NOT NULL,
  `email_perusahaan` varchar(100) NOT NULL,
  `sektor_industri` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` text DEFAULT NULL,
  `deskripsi_perusahaan` text DEFAULT NULL,
  `alamat_kantor` text DEFAULT NULL,
  `website_resmi` varchar(100) DEFAULT NULL,
  `status_verifikasi` enum('Belum Verifikasi','Terverifikasi') DEFAULT 'Belum Verifikasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_perusahaan`
--

INSERT INTO `profil_perusahaan` (`id_profil_corp`, `id_user`, `nama_perusahaan`, `email_perusahaan`, `sektor_industri`, `alamat_perusahaan`, `deskripsi_perusahaan`, `alamat_kantor`, `website_resmi`, `status_verifikasi`) VALUES
(1, 2, 'PT MENCARI CINTA SEJATI', 'ptmencaricintasejati@gmail.com', 'E-commerce,teknologi', NULL, 'BERSAMA PT MENCARI CINTA SEJATI JADI LEBIH BAIK', NULL, NULL, 'Terverifikasi'),
(2, 4, 'Ruang Guru', 'ruangguru@gmail.com', 'Teknologi,Pendidikan', 'Jl. Sumatera No.31, Merdeka, Kec. Sumur Bandung, Kota Bandung, Jawa Barat 40117, Indonesia. Telepon: 0815-8536-3714', 'bersama ruang guru menuju indonesia emas 2045', NULL, NULL, 'Terverifikasi'),
(3, 8, 'Tokopedia', 'tokopedia@gmail.com', 'E-commerce,teknologi', 'Millenium Centennial Center, Lantai 28, Jalan Jenderal Sudirman Kav. 25, Karet, Setiabudi, Jakarta Selatan, DKI Jakarta, 12920', 'apa yang anda butuhkan semuanya tersedia di kami', NULL, NULL, 'Belum Verifikasi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('pelamar','hrd') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'HRD Global', 'hrd@perusahaan.com', '$2y$10$fQ7mscA/KIsjA8HhH4b27ee4M/EorS2280u1kFfP7e8G5g43YhVeq', 'hrd', '2026-06-08 13:51:46'),
(2, 'PT MENCARI CINTA SEJATI', 'ptmencaricintasejati@gmail.com', '$2y$10$CUQB.W6DXrcu4VQYW3SZYOVqLI15H1ogTo7b3pUlcVgGnEjnjoMl.', 'hrd', '2026-06-08 13:59:52'),
(3, 'shawn', 'shawn@gmail.com', '$2y$10$dXrwcstCenQfGzw0U3dvkuNd8sa/qZEseKSwguZJjycjXssGGSlzO', 'pelamar', '2026-06-08 14:02:08'),
(4, 'Ruang Guru', 'ruangguru@gmail.com', '$2y$10$cik3tsHZkktrVQrWXMhLZOvc7v0lHg79ARSoKkmsKPjFfhuYX3jHu', 'hrd', '2026-06-21 13:14:18'),
(5, 'hawk', 'hawk@gmail.com', '$2y$10$gZwRBw0NGtp4VY1IxFp0XeiO/.6tY5xM8bOCc/Ed.ApFb4C3/0/GW', 'pelamar', '2026-06-21 13:44:04'),
(6, 'Google', 'google@gmail.com', '$2y$10$jyrbkcMXrFuKzECeQD.KTOy6O01eitlJ6ApzN0Vvpzohm0hW8vQyy', 'hrd', '2026-06-21 14:41:52'),
(7, 'Gojek', 'gojek@gmail.com', '$2y$10$yNBqnkGrquwQ20lDgnAeyOAnk39jkYnmVXDQBLLuR7JD4W6g1M5WG', 'hrd', '2026-06-21 14:46:18'),
(8, 'Tokopedia', 'tokopedia@gmail.com', '$2y$10$I7jI8mAAgFaoGLzahDAZPONtdiVQnVWcOve.ysYT1HrVWKiUHBfJ.', 'hrd', '2026-06-21 14:51:05'),
(9, 'Bukalapak', 'bukalapak@gmail.com', '$2y$10$I4m4FQxLBLyIvcpjv2wNk.xikNd6qBCnH9MYghjRXnxWGPSZDEfua', 'hrd', '2026-06-21 14:58:43'),
(10, 'Shopee', 'shopee@gmail.com', '$2y$10$BsHQ0px88SJ14ZrqQLjL2udzyemI81w9k0QOxq273xDRmKih3XZae', 'hrd', '2026-06-21 15:07:36'),
(12, 'PT Bank Central Asia Tbk', 'ptbankcentralasia@gmail.com', '$2y$10$QNmAdzpzKA4wm0P7TXf3p.HwAYOR2tJGWmeAKzxVOhWOZXn9sM0F6', 'hrd', '2026-06-21 15:28:11'),
(13, 'Net TV Media', 'nettvmedia@gmail.com', '$2y$10$dNWnaUK2JsyNc/PWj2Q7k.W/18uUyNMIEI1gjanWWzRYkl05tUFEu', 'hrd', '2026-06-21 15:30:20'),
(14, 'Kopi Kenangan', 'kopikenangan@gmail.com', '$2y$10$JlL18sJjBI0BArfrFaiCyOljo5r/Dv1PcQ3tvlIYud/ckml.gUMoW', 'hrd', '2026-06-21 15:36:34'),
(15, 'PT Ace Hardware Indonesia', 'ptacehardwareidn@gmail.com', '$2y$10$Q3ExIJasV/dpECmFwlGyFujaO7spx7/72hZzrI91kcNBs5.JDK83.', 'hrd', '2026-06-21 15:38:39'),
(16, 'J&#38;T Express', 'j&t@gmail.com', '$2y$10$EgJlJMgZcmdy5MnRqPAvhuizHGUz9Qs9bZgLItP4k8Jh6/zsuibvq', 'hrd', '2026-06-21 15:40:32'),
(17, 'Unilever Indonesia', 'unileverindonesia@gmail.com', '$2y$10$dPWeuf2J/VlP1nFufqj2Uu0.FiwunFYcPnrqHjD/hwckDz.ariVOW', 'hrd', '2026-06-21 15:45:11'),
(18, 'Decorus Interior Design', 'DCI@gmail.com', '$2y$10$gHh97j.4oi8G/K.1HBjWteAxYrlERs03e.HiLjYLbBxyuiN2wT05.', 'hrd', '2026-06-21 15:47:07'),
(19, 'Hotel Aston', 'hotelaston@gmail.com', '$2y$10$e.I.hjnYNVafpyIV1w14y.jdoFK9T74.pKpwRWSOOu5hyCQL6ZYZK', 'hrd', '2026-06-21 15:48:59'),
(20, 'PT Astra International Tbk', 'ptastraint@gmail.com', '$2y$10$IxyS0W2nCPDpSMgj4A4H7ePIXCSjr2SpIeNgeSUyjakHl1qYtrVP.', 'hrd', '2026-06-21 15:50:43'),
(21, 'Kimia Farma', 'kimiafarma@gmail.com', '$2y$10$5e7LdSkVUK0yfs3YDV6rNum23/s.HpfzLpZUAFCbZ/rMZoS8RyKMa', 'hrd', '2026-06-21 15:52:33'),
(22, 'Toyota Astra Motor', 'toyotaastramotor@gmail.com', '$2y$10$ivQxd0fGAif3m/aNjT8ib.LHxQOMoM7/7g1e2GF9ieiviEPJVZHXu', 'hrd', '2026-06-21 15:54:20'),
(23, 'SweetEscape Indonesia', 'sweetescapeidn@gmail.com', '$2y$10$GTIFv7mgpe7w8cEOar10yOoBKvVg40HaHLT5YRPg74IFhYYeeZb9C', 'hrd', '2026-06-21 15:59:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lamaran`
--
ALTER TABLE `lamaran`
  ADD PRIMARY KEY (`id_lamaran`),
  ADD KEY `id_loker` (`id_loker`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `log_validasi`
--
ALTER TABLE `log_validasi`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_lamaran` (`id_lamaran`),
  ADD KEY `diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `lowongan`
--
ALTER TABLE `lowongan`
  ADD PRIMARY KEY (`id_loker`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `lowongan_tersimpan`
--
ALTER TABLE `lowongan_tersimpan`
  ADD PRIMARY KEY (`id_simpan`),
  ADD UNIQUE KEY `unik_simpan` (`id_user`,`id_loker`),
  ADD KEY `id_loker` (`id_loker`);

--
-- Indexes for table `profil_pelamar`
--
ALTER TABLE `profil_pelamar`
  ADD PRIMARY KEY (`id_profil`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `profil_perusahaan`
--
ALTER TABLE `profil_perusahaan`
  ADD PRIMARY KEY (`id_profil_corp`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lamaran`
--
ALTER TABLE `lamaran`
  MODIFY `id_lamaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `log_validasi`
--
ALTER TABLE `log_validasi`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lowongan`
--
ALTER TABLE `lowongan`
  MODIFY `id_loker` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `lowongan_tersimpan`
--
ALTER TABLE `lowongan_tersimpan`
  MODIFY `id_simpan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `profil_pelamar`
--
ALTER TABLE `profil_pelamar`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `profil_perusahaan`
--
ALTER TABLE `profil_perusahaan`
  MODIFY `id_profil_corp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lamaran`
--
ALTER TABLE `lamaran`
  ADD CONSTRAINT `lamaran_ibfk_1` FOREIGN KEY (`id_loker`) REFERENCES `lowongan` (`id_loker`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lamaran_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_validasi`
--
ALTER TABLE `log_validasi`
  ADD CONSTRAINT `log_validasi_ibfk_1` FOREIGN KEY (`id_lamaran`) REFERENCES `lamaran` (`id_lamaran`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `log_validasi_ibfk_2` FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lowongan`
--
ALTER TABLE `lowongan`
  ADD CONSTRAINT `lowongan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `lowongan_tersimpan`
--
ALTER TABLE `lowongan_tersimpan`
  ADD CONSTRAINT `lowongan_tersimpan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `lowongan_tersimpan_ibfk_2` FOREIGN KEY (`id_loker`) REFERENCES `lowongan` (`id_loker`) ON DELETE CASCADE;

--
-- Constraints for table `profil_pelamar`
--
ALTER TABLE `profil_pelamar`
  ADD CONSTRAINT `profil_pelamar_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `profil_perusahaan`
--
ALTER TABLE `profil_perusahaan`
  ADD CONSTRAINT `profil_perusahaan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
