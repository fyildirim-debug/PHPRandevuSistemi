-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 10 Şub 2025, 06:13:46
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `randevu_sistemi`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ayarlar`
--

CREATE TABLE `ayarlar` (
  `id` int(11) NOT NULL,
  `sistem_aktif` tinyint(1) DEFAULT 1,
  `haftalik_gorunum_limiti` int(11) DEFAULT 1,
  `program_bitis_tarihi` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `haftalik_randevu_limiti` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ayarlar`
--

INSERT INTO `ayarlar` (`id`, `sistem_aktif`, `haftalik_gorunum_limiti`, `program_bitis_tarihi`, `created_at`, `updated_at`, `haftalik_randevu_limiti`) VALUES


-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `haftalik_program`
--

CREATE TABLE `haftalik_program` (
  `id` int(11) NOT NULL,
  `gun` varchar(20) NOT NULL,
  `saat_baslangic` time NOT NULL,
  `saat_bitis` time NOT NULL,
  `max_randevu` int(11) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `tc_no` text DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefon` varchar(15) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `ad_soyad` varchar(100) NOT NULL,
  `rol` enum('admin','kullanici') DEFAULT 'kullanici',
  `durum` enum('beklemede','onayli','reddedildi') DEFAULT 'beklemede',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `son_giris` datetime DEFAULT NULL,
  `browser_hash` varchar(64) DEFAULT NULL COMMENT 'Tarayıcı parmak izi',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Son giriş IP adresi',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Aktif oturum ID',
  `last_activity` timestamp NULL DEFAULT NULL COMMENT 'Son aktivite zamanı'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `tc_no`, `email`, `telefon`, `sifre`, `ad_soyad`, `rol`, `durum`, `created_at`, `son_giris`, `browser_hash`, `ip_address`, `session_id`, `last_activity`) VALUES

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `program_sablon`
--

CREATE TABLE `program_sablon` (
  `id` int(11) NOT NULL,
  `gun` enum('pazartesi','sali','carsamba','persembe','cuma','cumartesi','pazar') DEFAULT NULL,
  `baslangic_saat` time NOT NULL,
  `bitis_saat` time NOT NULL,
  `kontenjan` int(11) DEFAULT 1,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `program_sablon`
--

INSERT INTO `program_sablon` (`id`, `gun`, `baslangic_saat`, `bitis_saat`, `kontenjan`, `aktif`, `created_at`) VALUES


-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `randevular`
--

CREATE TABLE `randevular` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `tarih` date NOT NULL,
  `saat` time NOT NULL,
  `durum` enum('beklemede','onaylandi','iptal') DEFAULT 'beklemede',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `randevu_saatleri`
--

CREATE TABLE `randevu_saatleri` (
  `id` int(11) NOT NULL,
  `sablon_id` int(11) DEFAULT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `tarih` date NOT NULL,
  `baslangic_saat` time NOT NULL,
  `bitis_saat` time NOT NULL,
  `kontenjan` int(11) NOT NULL DEFAULT 1,
  `kalan_kontenjan` int(11) NOT NULL DEFAULT 1,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `randevu_saatleri`
--

INSERT INTO `randevu_saatleri` (`id`, `sablon_id`, `kullanici_id`, `tarih`, `baslangic_saat`, `bitis_saat`, `kontenjan`, `kalan_kontenjan`, `aktif`, `created_at`) VALUES


--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ayarlar`
--
ALTER TABLE `ayarlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `haftalik_program`
--
ALTER TABLE `haftalik_program`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `tc_no_unique` (`tc_no`(255)),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_browser` (`browser_hash`);

--
-- Tablo için indeksler `program_sablon`
--
ALTER TABLE `program_sablon`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `randevular`
--
ALTER TABLE `randevular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `randevu_saatleri`
--
ALTER TABLE `randevu_saatleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sablon_id` (`sablon_id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ayarlar`
--
ALTER TABLE `ayarlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `haftalik_program`
--
ALTER TABLE `haftalik_program`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `program_sablon`
--
ALTER TABLE `program_sablon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Tablo için AUTO_INCREMENT değeri `randevular`
--
ALTER TABLE `randevular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `randevu_saatleri`
--
ALTER TABLE `randevu_saatleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `randevular`
--
ALTER TABLE `randevular`
  ADD CONSTRAINT `randevular_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`);

--
-- Tablo kısıtlamaları `randevu_saatleri`
--
ALTER TABLE `randevu_saatleri`
  ADD CONSTRAINT `randevu_saatleri_ibfk_1` FOREIGN KEY (`sablon_id`) REFERENCES `program_sablon` (`id`),
  ADD CONSTRAINT `randevu_saatleri_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
