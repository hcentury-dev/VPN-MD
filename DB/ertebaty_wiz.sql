-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 05, 2026 at 05:11 PM
-- Server version: 10.6.24-MariaDB-cll-lve-log
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ertebaty_wiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` mediumtext NOT NULL,
  `password` mediumtext NOT NULL,
  `email` varchar(1000) DEFAULT NULL,
  `image` varchar(1000) DEFAULT NULL,
  `owner` mediumtext NOT NULL,
  `author` varchar(1000) DEFAULT NULL,
  `is_admin` varchar(11) NOT NULL DEFAULT 'false',
  `isManager` varchar(11) NOT NULL DEFAULT 'deactive',
  `status` mediumtext NOT NULL,
  `login` varchar(1000) DEFAULT NULL,
  `date` varchar(1000) DEFAULT NULL,
  `x_add_admin` int(11) NOT NULL DEFAULT 0,
  `x_edit_admin` int(11) NOT NULL DEFAULT 0,
  `x_status_admin` int(11) NOT NULL DEFAULT 0,
  `x_delete_admin` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `image`, `owner`, `author`, `is_admin`, `isManager`, `status`, `login`, `date`, `x_add_admin`, `x_edit_admin`, `x_status_admin`, `x_delete_admin`) VALUES
(1, 'wizwiz', '$2y$10$ATUoI.XwdFflwh0zNfgmGOyvb..FOkz1V6Dg572JRLdN2X9231jUi', 't', 'profile.jpg', 'wiz', 'wizwiz', 'true', 'active', 'active', '2026-04-04 19:35:50', '2024-02-10 00:20:00', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `name` varchar(1000) DEFAULT NULL,
  `buy` varchar(1000) NOT NULL DEFAULT '#',
  `support` varchar(1000) NOT NULL DEFAULT '#',
  `renew` varchar(1000) NOT NULL DEFAULT '#',
  `amozesh` varchar(10000) NOT NULL DEFAULT '#',
  `app` varchar(1000) DEFAULT NULL,
  `from_id` varchar(1000) NOT NULL DEFAULT '0',
  `logo` varchar(1000) NOT NULL DEFAULT '0',
  `agent` varchar(1000) NOT NULL DEFAULT 'ADMIN'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `name`, `buy`, `support`, `renew`, `amozesh`, `app`, `from_id`, `logo`, `agent`) VALUES
(1, 'ADMIN', '#', '#', '#', '#', 'VPN', '97177520731', 'logo_5538.jpg', 'ADMIN');

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `version` mediumtext NOT NULL,
  `link` mediumtext NOT NULL,
  `versionWin` varchar(11) NOT NULL DEFAULT '1.0',
  `linkWin` varchar(1000) NOT NULL DEFAULT '#',
  `appstatus` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`id`, `version`, `link`, `versionWin`, `linkWin`, `appstatus`) VALUES
(1, '1.0', 'https://google.com', '1.0', '#', 'false');

-- --------------------------------------------------------

--
-- Table structure for table `configs`
--

CREATE TABLE `configs` (
  `id` int(11) NOT NULL,
  `name_fa` blob DEFAULT NULL,
  `name_en` blob NOT NULL,
  `title_fa` varchar(1000) NOT NULL DEFAULT '#',
  `title_en` varchar(1000) NOT NULL DEFAULT '#',
  `size` varchar(11) NOT NULL DEFAULT '0',
  `src` mediumtext NOT NULL,
  `locations_fa` varchar(1000) NOT NULL DEFAULT '#',
  `locations_en` varchar(1000) NOT NULL DEFAULT '#',
  `link` blob NOT NULL,
  `permium` varchar(11) NOT NULL DEFAULT 'Pro',
  `fragment` varchar(11) NOT NULL DEFAULT 'false',
  `json` varchar(11) NOT NULL DEFAULT 'false',
  `agent` varchar(1000) NOT NULL DEFAULT 'ADMIN',
  `oprator` varchar(11) NOT NULL DEFAULT 'ALL',
  `status` varchar(11) NOT NULL DEFAULT 'true',
  `date` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `configs`
--

INSERT INTO `configs` (`id`, `name_fa`, `name_en`, `title_fa`, `title_en`, `size`, `src`, `locations_fa`, `locations_en`, `link`, `permium`, `fragment`, `json`, `agent`, `oprator`, `status`, `date`) VALUES
(8, 0xd8b3d8b1d988d8b120d8aad8b1daa9db8cd987, 0x54c3bc726b697965, 'رایگان', 'Free', '930', 'tr.png', 'ترکیه', 'Turkey', 0x766d6573733a2f2f65776f6749434a32496a6f67496a49694c416f6749434a776379493649434a58615870336158707a614739774969774b494341695957526b496a6f67496e4e6f6233417561474679593268706147467a644335755a5851694c416f6749434a7762334a30496a6f674d5451314e7a417343694167496d6c6b496a6f67496a55794e444d334e4749344c575533595755744e4759304f5330354f4441784c5442684d575a694e6a646a4e5446684d43497343694167496e4e6a6553493649434a68645852764969774b49434169626d5630496a6f67496e526a6343497343694167496e52736379493649434a756232356c4969774b4943416964486c775a53493649434a6f644852774969774b4943416963474630614349364943497649677039, 'Free', 'true', 'false', 'ADMIN', 'ALL', 'true', '2026-02-20 20:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `username` varchar(1000) NOT NULL,
  `deviceId` varchar(1000) NOT NULL,
  `model` varchar(1000) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'active',
  `token` varchar(1000) NOT NULL DEFAULT '0',
  `size` varchar(1000) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `id` int(11) NOT NULL,
  `username` varchar(1000) NOT NULL,
  `password` varchar(1000) NOT NULL,
  `isservers` varchar(11) NOT NULL DEFAULT '0',
  `editServers` varchar(11) NOT NULL DEFAULT '0',
  `delServers` varchar(11) NOT NULL DEFAULT '0',
  `creServers` varchar(11) NOT NULL DEFAULT '0',
  `staServers` varchar(11) NOT NULL DEFAULT '0',
  `servers` varchar(11) NOT NULL DEFAULT '0',
  `isusers` varchar(11) NOT NULL DEFAULT '0',
  `editUsers` varchar(11) NOT NULL DEFAULT '0',
  `delUsers` varchar(11) NOT NULL DEFAULT '0',
  `creUsers` varchar(11) NOT NULL DEFAULT '0',
  `staUsers` varchar(11) NOT NULL DEFAULT '0',
  `inventory` varchar(11) NOT NULL DEFAULT '0',
  `type` varchar(11) NOT NULL DEFAULT '1',
  `size` varchar(11) NOT NULL DEFAULT '1024',
  `total` varchar(11) NOT NULL DEFAULT '1000',
  `day` varchar(11) NOT NULL DEFAULT '365',
  `multi` varchar(11) NOT NULL DEFAULT '1',
  `device` varchar(11) NOT NULL DEFAULT '1',
  `login` varchar(11) NOT NULL DEFAULT '0',
  `devices` varchar(10000) NOT NULL DEFAULT '|',
  `owner` varchar(1000) NOT NULL,
  `image` varchar(1000) NOT NULL,
  `status` varchar(11) NOT NULL DEFAULT 'active',
  `dlogin` varchar(1000) NOT NULL,
  `edate` varchar(1000) NOT NULL,
  `date` varchar(1000) NOT NULL,
  `expire` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `massage`
--

CREATE TABLE `massage` (
  `id` int(11) NOT NULL,
  `title` varchar(1000) NOT NULL,
  `text` varchar(1000) NOT NULL,
  `date` varchar(1000) NOT NULL,
  `titleB` varchar(255) DEFAULT NULL,
  `textB` varchar(255) DEFAULT NULL,
  `dateB` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `massage`
--

INSERT INTO `massage` (`id`, `title`, `text`, `date`, `titleB`, `textB`, `dateB`) VALUES
(1, 'ااشسسب', 'بلبل', '2026-02-24 10:17:29', 'قفقفeeeeeeeصص', 'ققققققققققیب', '2026-02-24 10:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title_fa` varchar(1000) DEFAULT NULL,
  `title_en` varchar(1000) NOT NULL,
  `text_fa` varchar(1000) DEFAULT NULL,
  `text_en` varchar(1000) NOT NULL,
  `main` varchar(11) NOT NULL DEFAULT '0',
  `status` varchar(11) NOT NULL DEFAULT 'active',
  `date` varchar(1000) DEFAULT NULL,
  `users` varchar(10000) NOT NULL DEFAULT '|',
  `agent` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title_fa`, `title_en`, `text_fa`, `text_en`, `main`, `status`, `date`, `users`, `agent`) VALUES
(8, 'خبر مهم', 'Important Announcement', '⚠️با توجه به افزایش قیمت ۴۵ درصدی از سمت دیتاسنترها مجبور به افزایش قیمت شدیم و اینکه دیگه اشتراک با تایم نامحدود قابل ارائه در شرایط حال حاضر نیست!', '⚠️ Due to a 45% price increase from the data centers, we have been forced to raise our prices. Additionally, unlimited-time subscriptions are no longer available under the current conditions.', '1', 'active', '2026-02-23', '||user864||user746||user786||anmkkx||user14||i5ul3i||mfejln||nuxwcx||s6eyev||g8fl56||h0mrn3||hiy3mb||user226||qwcelz||notblz||nio2f8||kaoml8||v5fwa8||gf61pl||3y08g1||1zs77a||user704||oydu2i||mi1n3p||user692||dov2sj||yy1di9||zuaeou||fcrrim||524n9q||5rdd2e||pc8n1y||lupe27||user232||072cr8||zux5li||bnl00c||ppt32k||6wl9iu||44v280||l24qm3||bfhrq1||eokplk||fbibzp|', 'betty,ADMIN,sn,rz,bt2');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `title` varchar(30) DEFAULT NULL,
  `text` varchar(370) DEFAULT NULL,
  `link` varchar(1000) DEFAULT NULL,
  `status` varchar(11) DEFAULT NULL,
  `users` varchar(10000) DEFAULT NULL,
  `date` varchar(11) DEFAULT NULL,
  `agent` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `title`, `text`, `link`, `status`, `users`, `date`, `agent`) VALUES
(1, 'سرور های مختلف', 'کاربران گرامی، بخش لیست سرور ها میتونین به بقیه سرور ها متصل بشین و فقط وابسته به یک سرور نیست.', '#', 'active', '', '', 'ADMIN');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `size` varchar(1000) NOT NULL DEFAULT '0',
  `day` varchar(1000) NOT NULL DEFAULT '0',
  `multi` varchar(1000) NOT NULL DEFAULT '0',
  `price` varchar(1000) NOT NULL DEFAULT '0',
  `status` varchar(1000) NOT NULL DEFAULT 'active',
  `date` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `size`, `day`, `multi`, `price`, `status`, `date`) VALUES
(3, '30', '30', '1', '5000', 'active', '2026-02-23 22:32:26'),
(4, '60', '60', '1', '10000', 'active', '2026-02-23 22:32:41'),
(5, '90', '90', '3', '15000', 'deactive', '2026-02-23 22:33:00');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `title` varchar(1000) NOT NULL DEFAULT '0',
  `text` varchar(1000) NOT NULL DEFAULT '0',
  `status` varchar(1000) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `title`, `text`, `status`) VALUES
(2, 'از کجا بفهمیم حجم تمام شده؟', 'سمت چپ یه گزینه هست بالا روش کلیک بکنید سمت راست پنجره باز میشه و میتونید', 'active'),
(3, 'ایا تست داره؟', 'بله میتونید به عنوان مهمان وارد بشید و ۱ گیگ حجم ۱ روز به صورت تستی استفاده کنید البته اگر دسترسی از طرف مدیریت روشن باشد', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `app_name` varchar(255) NOT NULL,
  `app_logo` varchar(255) NOT NULL,
  `telegram_token` varchar(255) NOT NULL DEFAULT '0',
  `telegram_id` varchar(255) NOT NULL DEFAULT '0',
  `app_developed_by` varchar(255) NOT NULL,
  `app_guest_volume` varchar(11) NOT NULL DEFAULT '10',
  `api_guest_day` varchar(11) NOT NULL DEFAULT '30',
  `api_guest_opt` varchar(255) NOT NULL DEFAULT 'ALL',
  `api_guest_status` int(11) NOT NULL DEFAULT 1,
  `api_guest_per` varchar(20) NOT NULL DEFAULT 'Free',
  `backup_interval` varchar(11) DEFAULT '24',
  `last_backup_time` varchar(20) DEFAULT '3600',
  `manager_gb` varchar(11) NOT NULL DEFAULT '0',
  `manager_day` varchar(11) NOT NULL DEFAULT '0',
  `manager_multi` varchar(11) NOT NULL DEFAULT '0',
  `manager_total` varchar(11) NOT NULL DEFAULT '0',
  `manager_server` varchar(11) NOT NULL DEFAULT '0',
  `user_ad_size_video` int(11) NOT NULL DEFAULT 2024,
  `user_ad_size_image` int(11) NOT NULL DEFAULT 1024,
  `user_ad_seen_video` int(11) NOT NULL DEFAULT 10,
  `user_ad_seen_image` int(11) NOT NULL DEFAULT 30,
  `gust_ad_size_video` int(11) NOT NULL DEFAULT 2024,
  `gust_ad_size_image` int(11) NOT NULL DEFAULT 1024,
  `gust_ad_seen_video` int(11) NOT NULL DEFAULT 10,
  `gust_ad_seen_image` int(11) NOT NULL DEFAULT 30,
  `user_ad_ok_video` int(11) NOT NULL DEFAULT 20,
  `user_ad_ok_image` int(11) NOT NULL DEFAULT 30,
  `gust_ad_ok_video` int(11) NOT NULL DEFAULT 30,
  `gust_ad_ok_image` int(11) NOT NULL DEFAULT 40,
  `link_get` varchar(1000) DEFAULT NULL,
  `panel_link` varchar(1000) NOT NULL,
  `admin_link` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `app_logo`, `telegram_token`, `telegram_id`, `app_developed_by`, `app_guest_volume`, `api_guest_day`, `api_guest_opt`, `api_guest_status`, `api_guest_per`, `backup_interval`, `last_backup_time`, `manager_gb`, `manager_day`, `manager_multi`, `manager_total`, `manager_server`, `user_ad_size_video`, `user_ad_size_image`, `user_ad_seen_video`, `user_ad_seen_image`, `gust_ad_size_video`, `gust_ad_size_image`, `gust_ad_seen_video`, `gust_ad_seen_image`, `user_ad_ok_video`, `user_ad_ok_image`, `gust_ad_ok_video`, `gust_ad_ok_image`, `link_get`, `panel_link`, `admin_link`) VALUES
(1, 'app vpn', 'wizwiz.png', '44577:AAERneGd79uHQGwPQDkLvT', '6337842149', 'MR', '1', '30', 'ALL', 1, 'Free', '1', '2025-09-21 13:00:10', '1000', '1500', '2000', '2500', '5000', 1, 1, 1, 5, 1, 1, 1, 2, 500, 200, 300, 200, '', 'https://manger.jasemhooti2.ir', 'https://sub.domain.com');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` int(11) NOT NULL,
  `title_buy` varchar(1000) NOT NULL DEFAULT '0',
  `name_buy` varchar(1000) NOT NULL DEFAULT '0',
  `title_channel` varchar(1000) NOT NULL DEFAULT '0',
  `name_channel` varchar(1000) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `support`
--

INSERT INTO `support` (`id`, `title_buy`, `name_buy`, `title_channel`, `name_channel`) VALUES
(1, 'ارتباط با ما', 'همکاری،انتقاد،پبشنهاد', 'تلگرام', 'کانال اطلاع‌رسانی ');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` mediumtext NOT NULL,
  `password` mediumtext NOT NULL,
  `size` varchar(11) NOT NULL DEFAULT '0',
  `total` decimal(11,1) NOT NULL DEFAULT 0.0,
  `full` varchar(11) NOT NULL DEFAULT '-1',
  `subscription` varchar(100) DEFAULT NULL,
  `date_login` varchar(1000) NOT NULL DEFAULT '0000-00-00',
  `date_buy` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT '0000-00-00',
  `adate` varchar(255) DEFAULT '',
  `count_pic` int(11) NOT NULL DEFAULT 0,
  `count_vid` int(11) NOT NULL DEFAULT 0,
  `owner` mediumtext NOT NULL,
  `price` varchar(11) NOT NULL DEFAULT '0',
  `permium` varchar(255) NOT NULL DEFAULT 'Pro',
  `agent` varchar(1000) NOT NULL DEFAULT 'ADMIN',
  `multi` int(11) NOT NULL DEFAULT 1,
  `active` varchar(255) NOT NULL DEFAULT 'active',
  `status` varchar(11) NOT NULL DEFAULT '0',
  `oprator` varchar(11) DEFAULT '0',
  `login` varchar(255) NOT NULL DEFAULT 'false',
  `device` varchar(1000) NOT NULL DEFAULT '0',
  `deviceIMEI` varchar(1000) DEFAULT '0',
  `is_login` int(11) NOT NULL DEFAULT 0,
  `from_id` varchar(20) NOT NULL DEFAULT '0',
  `id_from` varchar(20) NOT NULL DEFAULT '0',
  `online` varchar(100) NOT NULL DEFAULT 'offline',
  `last_update` timestamp NULL DEFAULT NULL,
  `days` varchar(11) NOT NULL DEFAULT '1',
  `gb` varchar(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `configs`
--
ALTER TABLE `configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `massage`
--
ALTER TABLE `massage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `configs`
--
ALTER TABLE `configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `massage`
--
ALTER TABLE `massage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support`
--
ALTER TABLE `support`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=464;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
