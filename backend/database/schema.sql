-- Online Voting System (Tomorrow Vote) — Clean Database Schema & Seed
-- Compatible with Railway MySQL 8.x, XAMPP, and Cloud MySQL

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `votes`;
DROP TABLE IF EXISTS `candidates`;
DROP TABLE IF EXISTS `positions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `election_settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'voter') NOT NULL DEFAULT 'voter',
  `full_name` VARCHAR(150) NOT NULL,
  `student_id` VARCHAR(50) DEFAULT NULL,
  `has_voted` TINYINT(1) NOT NULL DEFAULT 0,
  `phone` VARCHAR(50) DEFAULT '+63 912 345 6789',
  `date_of_birth` VARCHAR(50) DEFAULT '2003-10-12',
  `grade_level` VARCHAR(50) DEFAULT '3rd Year',
  `section` VARCHAR(50) DEFAULT 'BSIT 31008',
  `session_token` VARCHAR(255) DEFAULT NULL,
  `login_otp` VARCHAR(10) DEFAULT NULL,
  `login_otp_expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `positions`
-- --------------------------------------------------------
CREATE TABLE `positions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position_name` VARCHAR(100) NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `candidates`
-- --------------------------------------------------------
CREATE TABLE `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position_id` INT NOT NULL,
  `candidate_name` VARCHAR(150) NOT NULL,
  `year_level` VARCHAR(50) NOT NULL DEFAULT '1st Year',
  `platform` TEXT DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'default.svg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `votes`
-- --------------------------------------------------------
CREATE TABLE `votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `voter_id` INT NOT NULL,
  `position_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `encrypted_payload` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_voter_position` (`voter_id`, `position_id`),
  FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `election_settings`
-- --------------------------------------------------------
CREATE TABLE `election_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `positions`
-- --------------------------------------------------------
INSERT INTO `positions` (`id`, `position_name`, `display_order`) VALUES
(1, 'President', 1),
(2, 'Vice President', 2),
(3, 'Secretary', 3);

-- --------------------------------------------------------
-- Dumping data for table `candidates`
-- --------------------------------------------------------
INSERT INTO `candidates` (`id`, `position_id`, `candidate_name`, `year_level`, `platform`, `profile_image`) VALUES
(102, 1, 'Bagaipo', '2nd Year', 'Fostering campus unity', '1787038881_cbaca8b1-b34a-4fae-a1a1-d06090e3891c (2).jpg'),
(103, 2, 'Fryyyyyy', '1st Year', 'Empowering student voices through active dialogue.', '1787038842_e5a09349-8cfb-41df-a5f0-a156253dc18b (2).jpg'),
(104, 3, 'Ranola', '3rd Year', 'Dedicated service and efficient student programs.', '1787038963_d9570027-68fa-4cba-a501-835c62a47175 (2).jpg'),
(105, 2, 'Jaropohop, Arnel', '2nd Year', 'Streamlining academic support and student events.', '1787038861_89451d27-383d-4a88-83b9-633587981d26 (2).jpg'),
(106, 1, 'Fajardo', '4th Year', 'Action-driven leadership for all student organizations.', '1787040530_rex.jpg'),
(107, 1, 'Albos, Jesus Kyle F.', '2nd Year', 'Accurate record-keeping and clear communications.', '1787038985_image.png'),
(108, 2, 'Alvarez', '3rd Year', 'Digitalization of student services and requests.', '1787044088_lovee.jpg'),
(109, 3, 'Alejo', '1st Year', 'Fresh perspectives for student governance.', '1787040508_fa6ff0b0-3fe5-4123-a0cd-a9492846f928.jpg');

-- --------------------------------------------------------
-- Dumping data for table `election_settings`
-- --------------------------------------------------------
INSERT INTO `election_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'election_title', 'Student Council Election 2026'),
(2, 'voting_status', 'CLOSED'),
(3, 'start_date', '2026-08-16'),
(4, 'end_date', '2026-08-16'),
(33, 'start_time', '10:57 AM'),
(34, 'end_time', '11:00 AM'),
(44, 'election_schedules_list', '[{\"id\":\"1786848934198\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-16\",\"end_date\":\"2026-08-16\",\"date\":\"2026-08-16\",\"start_time\":\"10:57 AM\",\"end_time\":\"11:00 AM\",\"created_at\":\"2026-08-16 04:55:34\"},{\"id\":\"1786848875433\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"08:33 PM\",\"end_time\":\"08:39 PM\",\"created_at\":\"2026-08-16 04:54:35\"},{\"id\":\"1786847502120\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"08:33 PM\",\"end_time\":\"08:39 PM\",\"created_at\":\"2026-08-16 04:31:42\"},{\"id\":\"1786847491147\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"08:33 PM\",\"end_time\":\"08:39 PM\",\"created_at\":\"2026-08-16 04:31:31\"},{\"id\":\"1786624303397\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"08:33 PM\",\"end_time\":\"08:39 PM\",\"created_at\":\"2026-08-13 14:31:43\",\"status_override\":\"OPEN\"},{\"id\":\"1786615951644\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"06:15 PM\",\"end_time\":\"06:19 PM\",\"created_at\":\"2026-08-13 12:12:31\",\"status_override\":\"CLOSED\"},{\"id\":\"1786615288238\",\"title\":\"Student Council Election 2026\",\"start_date\":\"2026-08-13\",\"end_date\":\"2026-08-13\",\"date\":\"2026-08-13\",\"start_time\":\"6:02 PM\",\"end_time\":\"06:03 PM\",\"created_at\":\"2026-08-13 12:01:28\",\"status_override\":\"CLOSED\"}]'),
(217, 'manual_override', 'MANUAL_CLOSED');

-- --------------------------------------------------------
-- Dumping data for table `users`
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `full_name`, `student_id`, `has_voted`, `phone`, `date_of_birth`, `grade_level`, `section`) VALUES
(1, 'admin', 'admin@tomorrowvote.com', '$2y$10$wBx.kT0DfIpigZJA4pXNB.ho3YqozHqOjCwn/4ZqS7d0dM8pv712S', 'admin', 'Admin', 'ADM-2026', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(4, '240104785', '240104785@student.bcp.edu.ph', '$2y$10$AiVCpZ2c5DyR4Lr0WxsRTOpa2.H/ZZqGUfT.DNu.UqVwoSoN.FyO6', 'voter', 'Alvarez, Moises Ker C', '240104785', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(5, '240114524', '240114524@student.bcp.edu.ph', '$2y$10$Pg8pL/V61mvw7GWDvRckI.gB53W0Q6AIAyWXl.DvUxXIzrkaSaasO', 'voter', 'Antonio, Jose Miguel P.', '240114524', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(6, '240104619', '240104619@student.bcp.edu.ph', '$2y$10$yXTWaOrDdm/lu4virSDLku6ZvsXBHy3NKbxhWaD2ZKlLoHxDlud4i', 'voter', 'Avingona, Gabriel Joshua', '240104619', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(7, '240111324', '240111324@student.bcp.edu.ph', '$2y$10$9VUbyjy3uHX0eEvZZ7Hbj.1zLZzrWH95.8qcAWQf80CBKx9mbY1au', 'voter', 'Balaguer, Jastin Roi G.', '240111324', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(8, '240112382', '240112382@student.bcp.edu.ph', '$2y$10$FCmrByQOFlzWO9KuTzyODeV2XgKIce5cDCzYnj2cDPVjLOQouyGLW', 'voter', 'Baria, Presler A.', '240112382', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(9, '240114322', '240114322@student.bcp.edu.ph', '$2y$10$N6OOs0qOtz3b7RAMcd5m1OecHvqOzjLnwBvNpni3KwLW1BA/lldSC', 'voter', 'Bomitivo, Joanne C.', '240114322', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(10, '240110711', '240110711@student.bcp.edu.ph', '$2y$10$5IIohzzAtpuo9LpxIYNf8OKZPQ9obJjkuijh4CmrkX77od69Z1DZe', 'voter', 'Calusin, Justin', '240110711', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(11, '250100040', '250100040@student.bcp.edu.ph', '$2y$10$Y0VfTIGorNoa3i.BI7pY/ey.fE2QpaN6oBJti3RNXSSP0/nGFND6q', 'voter', 'Castulo, Renz Van S.', '250100040', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(12, '240114865', '240114865@student.bcp.edu.ph', '$2y$10$IyBEJwY7SNgr65EdE7vOB.t2BPUCK6HVGRSMhoQj.ELXzAiZFvH6O', 'voter', 'Coton, Majohn J.', '240114865', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(13, '240116136', '240116136@student.bcp.edu.ph', '$2y$10$4MZh2fpplV/E287EbF9tzO7GyjGSfyg.bpbxJ5iPMn5RKSdvnx5B.', 'voter', 'Cruz, Ronald Jay', '240116136', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(14, '240108641', '240108641@student.bcp.edu.ph', '$2y$10$Cnap4rk0j73k07mflX44zeYLvQVYX1M3Pk4s0K.YlmTnx0Mzz7LNS', 'voter', 'Elcarte, Hannah Kate M.', '240108641', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(15, '240110350', '240110350@student.bcp.edu.ph', '$2y$10$OvQn4wOyjStPea4uBTedxOYAZZkY0LvwLCOwPL7LekuUjObpn21Ni', 'voter', 'Fernandez, Jean Ashley T.', '240110350', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(16, '240102182', '240102182@student.bcp.edu.ph', '$2y$10$tXoILsDLK/NwNEJq1wp1F.T0hAbEAWejUu7MeTiJej1qsUgg5X7yi', 'voter', 'Gigante, Angelo Yuan O.', '240102182', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(17, '240111136', '240111136@student.bcp.edu.ph', '$2y$10$smQKetbEEYO.jOETHg8e4us15aQOe9Iv.I7TODz95M7wk/buMST2.', 'voter', 'Gonzaga, Diego Rey D.', '240111136', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(18, '240115034', '240115034@student.bcp.edu.ph', '$2y$10$AyK9jOJcgrcF9OfRILm2qu5QRsEispgKkLnIvsW8ruvM6vBB9Oy1y', 'voter', 'Guzman, Angeline O.', '240115034', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(19, '240104951', '240104951@student.bcp.edu.ph', '$2y$10$AMQfT2pWWpvjRN1a0zWxhubXTgOzD4qdgP6kB0TF8wVvPuISGg7tm', 'voter', 'Jalayahay, Daisy B.', '240104951', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(20, '240112280', '240112280@student.bcp.edu.ph', '$2y$10$9SLjpojd5w5.ZD2WGqHH6u9oSURq89mzYdezITC7HgYItd6lwMNTu', 'voter', 'Kimpan, Kim Jafet E.', '240112280', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(21, '240100999', '240100999@student.bcp.edu.ph', '$2y$10$rIfVTdKqI6DvWNGJxUUAmOjPD7kwHJ2gwEW.4SFirNWDlM34ylGgC', 'voter', 'Laroa, John Cristian B', '240100999', 1, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(22, '240100581', '240100581@student.bcp.edu.ph', '$2y$10$HE0J30w30/SWAvB/kvotm.rPhLIw1FcKc7Mqo55IVMOczTMWoDCei', 'voter', 'Pedroso, Keith Anton B.', '240100581', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(23, '240114837', '240114837@student.bcp.edu.ph', '$2y$10$njP4ht9edcMmosgua4/oY.hGU39Lu31UVu6adwLP53C1YkqhI./9i', 'voter', 'Rañola, Cassandramher S.', '240114837', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(24, '240114417', '240114417@student.bcp.edu.ph', '$2y$10$lMmXUycJGoXa.5rWSRob1..2dpm6eaGnEqXpYpdfIJIqM/pSHmaMa', 'voter', 'Rigor, Jazel Venice A.', '240114417', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(25, '240110713', '240110713@student.bcp.edu.ph', '$2y$10$MOLh9a96Xfy/tIOwW/RBsuo7MBCTUR7.FMO3nwmQsGbw7M/sAqhCO', 'voter', 'Sampiano, Jenny A.', '240110713', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(26, '240109916', '240109916@student.bcp.edu.ph', '$2y$10$9f9LTDC2Exh3iH1Mcd3/luNoOZuPFP/bpel5vVF5Y8wbxLtWB59Dy', 'voter', 'Soriao, Mark Gerald C.', '240109916', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(27, '240115320', '240115320@student.bcp.edu.ph', '$2y$10$FiSJaBsjtLka/xq48RE.guoPcdmsoxNjXp71GnqjRBOE/j5RMAjeW', 'voter', 'Sumagaysay, Annalee T.', '240115320', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(28, '240100841', '240100841@student.bcp.edu.ph', '$2y$10$lqyhUHlMvwmJ2PMyQjJRfekyEmSLJ2loIjz38JDpt7BK9c2Yg61Ba', 'voter', 'Sy, Franz Clarence E.', '240100841', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(29, '240116149', '240116149@student.bcp.edu.ph', '$2y$10$47/0ofjeC5y5677uDH2R.evUwmyUDlLSCQtsKZ/kaq7.0S.mcDnOi', 'voter', 'Tarala, Airadale', '240116149', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(30, '240109597', '240109597@student.bcp.edu.ph', '$2y$10$8k9RpCoQtbv8Z2xoBx4sLerXvK9VyNQyNDwhtnHIFOs2FDlVce9DO', 'voter', 'Tenorio, Emmanuel Robert C.', '240109597', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(31, '240109409', '240109409@student.bcp.edu.ph', '$2y$10$vLHy/.NiJGpgqwkzNZCfMusqXdYbbOlzOCgcx5wPyvZqRiLzLOzK2', 'voter', 'Tomelden, Jhewelle C.', '240109409', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(32, '240111848', '240111848@student.bcp.edu.ph', '$2y$10$0sMGLDLesOibwhJzspbPoutsUTEG6DLlM/CjNLtWfvo7cm83pfKr2', 'voter', 'Tribo, Almira A.', '240111848', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(33, '240104214', '240104214@student.bcp.edu.ph', '$2y$10$0qzRmUaceKfkkKczLCjQA.4sm3wlBEyO0fheBXdxsEyphPBBKjgDO', 'voter', 'Visto, Adrian P.', '240104214', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(34, '240105182', '240105182@student.bcp.edu.ph', '$2y$10$pmTWGQz8vQKPJKjfrA7kOOS2tTzatcwnnvgOAY0XdCK7BAfX4QqTC', 'voter', 'Obra, Joshua R.', '240105182', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008');

