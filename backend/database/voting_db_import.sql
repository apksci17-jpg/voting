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
(4, '240199001', 'jhencel.abarracoso@student.bcp.edu.ph', '$2y$10$Qi6GeMDWejRCbwLeeXHbZO0bxE/3fz4t5.gYpXXTwitk93g/nAgDK', 'voter', 'Abarracoso, Jhencel T.', '240199001', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(5, '240199002', 'julius.agang@student.bcp.edu.ph', '$2y$10$Mq5s/NucinxHoXlCpOwvk.IELB7Y9jjfq2jVkc5DY1ZGO24ljOVyi', 'voter', 'Agang, Julius O.', '240199002', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(6, '240108957', 'jesuskylefedilo@gmail.com', '$2y$10$Wnog3e8U9q6Xl9iyfNgVNOE0nGO.dzMMnFkCAkt04igm9K/gDP1FG', 'voter', 'Albos, Jesus Kyle F.', '240108957', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(7, '240199003', 'erich.alejo@student.bcp.edu.ph', '$2y$10$PI52l.uaKeV8VgKnANzBU.Ww0KHxAXv48PIetNKTF2JSutA7raCxW', 'voter', 'Alejo, Erich Faye B.', '240199003', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(8, '240104785', 'moiseskeralavarez@gmail.com', '$2y$10$3U9NJ6B.Rb7EreYtO60IMegE8C3sVOrbrzT.Mr02vNoAUPfUT2C2i', 'voter', 'Alvarez, Moises Ker C.', '240104785', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(9, '240114524', 'jmantonioirl@gmail.com', '$2y$10$W.up/Bdfuq4sbTV9Fz2I3ehNaJPibigkwgCYAncLJRw8E4K0JcXMi', 'voter', 'Antonio, Jose Miguel P.', '240114524', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(10, '240104619', 'avingonag@gmail.com', '$2y$10$ptNLNWQLDNR.mlBpE92W0OW2NXDUJI2dK2gWglBw5fai3PlWfglv6', 'voter', 'Avingona, Gabriel Joshua', '240104619', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(11, '240199004', 'roniel.bagaipo@student.bcp.edu.ph', '$2y$10$fAA.UkZ7KJFyQVqacsfScuUI9Z4FlX8HhDeU4nl.kkFq01.QRWr7m', 'voter', 'Bagaipo, Roniel Dream', '240199004', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(12, '240111324', 'jastinroi@gmail.com', '$2y$10$GGJIpmONxSYdC8sqW1/NSeQLWBSLJtl0DdM8imxr4fnjHrtDyCutS', 'voter', 'Balaguer, Jastin Roi G.', '240111324', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(13, '240112382', 'preslerbaria100@gmail.com', '$2y$10$iZ/OzpRNXlLtRyuH8fs.w.xel6806Y/ILxgVQHpM.Bq3UW7P3vbq2', 'voter', 'Baria, Presler A.', '240112382', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(14, '240114322', 'bomitivojoanne@gmail.com', '$2y$10$KFtMcTcjYIEWBvnyVYQKT.UjTndwyGV6xEY7EirsXHLTzQUCf84oe', 'voter', 'Bomitivo, Joanne C.', '240114322', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(15, '240199005', 'jian.cabezas@student.bcp.edu.ph', '$2y$10$pcrmY2IOem6G1hH/q9N38exZC2ed9eds0SezxGd7NnMrm6okRS4.G', 'voter', 'Cabezas, Jian Carlo S.', '240199005', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(16, '240110711', 'Calusinjustin20@gmail.com', '$2y$10$f8Qd.r5NbeoiGpQbMyxy4.viHBO3rpM.yqQ2bkmzUQsztYx.9sD..', 'voter', 'Calusin, Justin', '240110711', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(17, '250100040', 'castulo.renzvan.edu@gmail.com', '$2y$10$gQ3w3RorPQ.TO0HQr0ujN.QrGTleWeCoUaAsbzlbavchlwJz/Dsz6', 'voter', 'Castulo, Renz Van S.', '250100040', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(18, '240114865', 'cotonmajohn16@gmail.com', '$2y$10$GuMJD4P3nScb7Kp4m48rE.ByyR2FzbZlrxoZFcMj94uJ4ymwASV0i', 'voter', 'Coton, Majohn J.', '240114865', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(19, '240116136', 'ronaldcruz070103@gmail.com', '$2y$10$vorS.E2oiyLlS3gxcpkyQecoAbazDuFzPleMr4G1Pg1r0rKnjtZRq', 'voter', 'Cruz, Ronald Jay', '240116136', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(20, '240199006', 'joross.diega@student.bcp.edu.ph', '$2y$10$ZNKCgxftOEkSWBHfNlQcauaWrVOOapmkFlWVqkCZj04U70YKJskFC', 'voter', 'Diega, Joross H.', '240199006', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(21, '240108641', 'kateelcarte10@gmail.com', '$2y$10$KIvuEwewCDaIktjIeMW3K.BfdcYC7U4jxJRl4CInOEs/pvp.pSPAG', 'voter', 'Elcarte, Hannah Kate M.', '240108641', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(22, '240112792', 'usherescarcha@gmail.com', '$2y$10$eWN40WAO.E75w7XpEM6bNORZwPSuXoNMVbeV8GbwZ2lAuV1q1zUqK', 'voter', 'Escarcha, Usher Miguel P.', '240112792', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(23, '240199007', 'jarex.fajardo@student.bcp.edu.ph', '$2y$10$/CZS2XwvZdkUkUkthG.RxezjRBKjFxoh5siVW/zS4T1iUcqgjT.5y', 'voter', 'Fajardo, Jarex P.', '240199007', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(24, '240110350', 'fernandezjeanashleyt@gmail.com', '$2y$10$fGPtX1fd.Vv17Qi2bhTYs.xcpYu9x1cGK4/RKG2O4MIaVdm3tzJKS', 'voter', 'Fernandez, Jean Ashley T.', '240110350', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(25, '240102182', 'angeloyuangigante@gmail.com', '$2y$10$rd75RsPi6yl1iQRywr2OS.7PaDcC3KHBak6LuyfQCU0VjUBe5BYKy', 'voter', 'Gigante, Angelo Yuan O.', '240102182', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(26, '240111136', 'diegonzaga123@gmail.com', '$2y$10$ECbJZjGaTNCgLY52NhupsuYb9DNjbM.3pV1cSClByg6n9tODWD982', 'voter', 'Gonzaga, Diego Rey D.', '240111136', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(27, '240115034', 'angelineguzman550@gmail.com', '$2y$10$uRzkObFuFaltLeTmshyrfeU4Eqjb9TBkhp9.0WF75nl39cDF9t0Py', 'voter', 'Guzman, Angeline O.', '240115034', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(28, '240104951', 'jalayahay.daisy.bermas1@gmail.com', '$2y$10$zGwdaBk9bHU0kYftCPM1Ue2vwAmv1nNpfiHLWNmgoYL9/OumbWTbS', 'voter', 'Jalayahay, Daisy B.', '240104951', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(29, '240112280', 'kimjafetkimpan13@gmail.com', '$2y$10$KJtdEEaLYG8FhOANWy3JeuYEuQPqb07UCfwDJJhG/n3fibnOozm8e', 'voter', 'Kimpan, Kim Jafet E.', '240112280', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(30, '240199008', 'angelo.lapada@student.bcp.edu.ph', '$2y$10$MUpjbCA9MEU1eSUPuOe1k.WAlNBjggXyvvtMb/1bpr2//aivOE0s6', 'voter', 'Lapada, Angelo R.', '240199008', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(31, '240100999', 'cristianlaroa9@gmail.com', '$2y$10$5eCwpy5Zk3kVcmYxnzkF3erMUCKw72U41bDom8eGUnV.RncQy81IS', 'voter', 'Laroa, John Cristian B.', '240100999', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(32, '240199009', 'ivan.lazo@student.bcp.edu.ph', '$2y$10$.B8c9fiQUjIVo2CzcC9W/.N9tAy3C8.xzA7S5xz9DmrTdKcQNKs2y', 'voter', 'Lazo, Ivan James G.', '240199009', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(33, '240199010', 'russel.murillo@student.bcp.edu.ph', '$2y$10$QwXrBgHYrmgvDkN.d2RfPOs5hx96losDxRhowE5jVG6jcuFaK6UCa', 'voter', 'Murillo, Russel Borge M.', '240199010', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(34, '240199011', 'kane.pandaraoan@student.bcp.edu.ph', '$2y$10$EasGUoD2j8dW43kYNoTUcuxs.oghnEfQpnHa5fhfKbtt2p3i4wR2a', 'voter', 'Pandaraoan, Kane M.', '240199011', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(35, '240199012', 'troy.paran@student.bcp.edu.ph', '$2y$10$dYOwMpe8niSvq0gnxxSpCeSWUFPMPHWoIexNJ4H2QMAEMKLVLFTfq', 'voter', 'Paran, Troy A.', '240199012', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(36, '240113524', 'jedricpatilano31@gmail.com', '$2y$10$KYOpYNag8D7J2t6TsYQdmey9Xje7IIWT5Ay/Cu3XLNctQ3hNTEHSW', 'voter', 'Patilano, Jhedric B.', '240113524', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(37, '240100581', 'keithpedroso9@gmail.com', '$2y$10$yY2Chqs09c8TAHHBIY4fZum3s.IY6x9KS203.sj3phJ5Y5fjeLMmu', 'voter', 'Pedroso, Keith Anton B.', '240100581', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(38, '240199013', 'jemmer.placenciajr@student.bcp.edu.ph', '$2y$10$IkeLYbBXGIRktqEqhrZvKeMEKdvdfdAj1Scf5jFA5wbcqk8LMJfSG', 'voter', 'Placencia Jr., Jemmer A.', '240199013', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(39, '240199014', 'john.pojas@student.bcp.edu.ph', '$2y$10$0WY5gZQDfdddHj0ChVJfDuzpPrbxRcDlp2t5qgJe20nWaCwyLtscO', 'voter', 'Pojas, John Ruzze A.', '240199014', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(40, '240114837', 'cassandramherranola@gmail.com', '$2y$10$fQx1t.RqQLRmkRjUy0vfUeQddj.1hUq6e145ko7ReWW9d8pv353DO', 'voter', 'Rañola, Cassandramher S.', '240114837', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(41, '240114417', 'jazel.venice@gmail.com', '$2y$10$/15ARdNhsPFnl9qpzQivzOIXMiTKwb4ZrahlOW.jyAE3Gjj9CF10q', 'voter', 'Rigor, Jazel Venice A.', '240114417', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(42, '240199015', 'noriel.rualizo@student.bcp.edu.ph', '$2y$10$vPs0ebicR7iJoUR6ZbvroO/C8YUDdYiabryWU2RoUtADwT7x06Elq', 'voter', 'Rualizo, Noriel D.', '240199015', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(43, '240110713', 'sampianojenny07@gmail.com', '$2y$10$AAcg6v5BEE7amvYleXJ1EeLjDMlmyqjNSbRZwktuvNQ/vukj.35T6', 'voter', 'Sampiano, Jenny A.', '240110713', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(44, '240109916', 'soriaogerald@gmail.com', '$2y$10$iiNuOZSKbh6Q4HPPcdrQe.Rg/mSI0BEdeJRykma5iscUuqnO4oIGq', 'voter', 'Soriao, Mark Gerald C.', '240109916', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(45, '240115320', 'taannalee82@gmail.com', '$2y$10$hdVNEX5yL1bPAGQ984St5OYl5LD0Npi7q9kyUh43U4TZ9ciabK/k2', 'voter', 'Sumagaysay, Annalee T.', '240115320', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(46, '240100841', 'franzclarencesy@gmail.com', '$2y$10$lxUA5PhM8.4m9sqy4.QlP.FtfEuyN1PCnRTvUtD935WHYgldWwYp2', 'voter', 'Sy, Franz Clarence E.', '240100841', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(47, '240199016', 'leo.tabayocyoc@student.bcp.edu.ph', '$2y$10$bEWG67DlljLEJ67z5LZRceqUWRSTAORcnBDy6eFwx482E6zeQ1.pS', 'voter', 'Tabayocyoc, Leo T.', '240199016', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(48, '240113401', 'cocandrew09@gmail.com', '$2y$10$KWQMVoJ/ThpVyoztKBJY1e1ECLXMI49Ni9TJs7XfdvgKHjOwzeAM6', 'voter', 'Tan, Andrew Jefferson L.', '240113401', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(49, '240116149', 'tarala.araidale.jaral@gmail.com', '$2y$10$W1L175w3ldqs.zzUcP69o.tnAqDyD.ttZ1wEDb1P80QNJtmenUvOq', 'voter', 'Tarala, Airadale', '240116149', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(50, '240109597', 'emmanuelroberttenorio@gmail.com', '$2y$10$o4C06bAAsxSvU0QfXqI8xOSheJNltUtb3cXy0TIWEaUEE2u7OeeZW', 'voter', 'Tenorio, Emmanuel Robert C.', '240109597', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(51, '240109409', 'jhewelletomelden028@gmail.com', '$2y$10$EKDbSnYmqB3ogLmqT29o/.75e2sZGGnm8HxiHbAG6XzXXu4jLj2ai', 'voter', 'Tomelden, Jhewelle C.', '240109409', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(52, '240111848', 'miraamirah2005@gmail.com', '$2y$10$On48eA.2un5cbUoyHQH.tO5Fkch8BadU3zv9P8vODqLyu8maBYDIi', 'voter', 'Tribo, Almira A.', '240111848', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(53, '240112797', 'dharendaves@gmail.com', '$2y$10$jL6bQYvJeWBXf7CPxShvWuTDuhL5ua4qTYC.joARRo2gAzAWtrHWq', 'voter', 'Tubeo, Dharen Daves M.', '240112797', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(54, '240104214', 'adrianvisto276@gmail.com', '$2y$10$1eao1yU9iNMpRNvKGYefD..jjyvDyApJvbcYQwucv3Ps2UTzxNHCu', 'voter', 'Visto, Adrian P.', '240104214', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(55, '240105182', 'obrajoshua9@gmail.com', '$2y$10$7SsIyCg.S.gcEFc6xZzrYu2SmbtqHxVtx9X7IuYmPAihLEhnT9j3e', 'voter', 'Obra, Joshua R.', '240105182', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008'),
(56, '240199017', 'jomel.navarro@student.bcp.edu.ph', '$2y$10$60cyFkFh.p.SuYttADSZDOuMAhgDzkcFtnR4580G.3ECML0WSegH6', 'voter', 'Navarro, Jomel L.', '240199017', 0, '+63 912 345 6789', '2003-10-12', '3rd Year', 'BSIT 31008');

