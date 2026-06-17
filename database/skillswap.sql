-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 31, 2025 at 06:51 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skillswap`
CREATE DATABASE skillswap;
USE skillswap;
--

-- --------------------------------------------------------

--
-- Table structure for table `ms_boosters`
--

CREATE TABLE `ms_boosters` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `multiplier` decimal(3,1) DEFAULT NULL,
  `package_price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_boosters`
--

INSERT INTO `ms_boosters` (`package_id`, `package_name`, `image_url`, `multiplier`, `package_price`, `status`, `created_at`) VALUES
(4, 'Starter Boost', 'https://cdn-icons-png.flaticon.com/512/1077/1077035.png', 1.5, 15000.00, 'active', '2025-12-30 17:00:01'),
(5, 'Turbo Charge', 'https://cdn-icons-png.flaticon.com/512/1048/1048927.png', 2.0, 30000.00, 'active', '2025-12-30 17:00:01'),
(6, 'Rocket Speed', 'https://cdn-icons-png.flaticon.com/512/3050/3050525.png', 3.0, 50000.00, 'active', '2025-12-30 17:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `ms_coursecategory`
--

CREATE TABLE `ms_coursecategory` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `category_description` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_coursecategory`
--

INSERT INTO `ms_coursecategory` (`category_id`, `category_name`, `category_description`, `description`) VALUES
(1, 'Design Basics', 'Intro design', 'Learn basic design'),
(2, 'Programming Basics', 'Intro coding', 'Learn programming');

-- --------------------------------------------------------

--
-- Table structure for table `ms_courses`
--

CREATE TABLE `ms_courses` (
  `course_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `credits_price` int(11) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_courses`
--

INSERT INTO `ms_courses` (`course_id`, `user_id`, `category_id`, `title`, `video`, `description`, `upload_date`, `credits_price`, `thumbnail`) VALUES
(1, 3, 1, 'UI Design for Beginners', 'ui_design.mp4', 'Learn UI Design', '2025-12-16 20:39:58', 100, 'ui_design.png'),
(2, 3, 2, 'Web Programming 101', 'web101.mp4', 'Intro to web dev', '2025-12-16 20:39:58', 150, 'web_programming.png'),
(3, 4, 1, 'adfasd', 'vid_1765953742_694250ce74aea.mp4', 'asf', '2025-12-17 13:42:22', 123, 'img_1765953742_694250ce74af3.jpg'),
(4, 4, 2, 'hsjssd', 'vid_1766048065_6943c1418c3b5.mp4', 'saxsax', '2025-12-18 15:54:25', 100, 'img_1766048065_6943c1418c762.png');

-- --------------------------------------------------------

--
-- Table structure for table `ms_skillcategory`
--

CREATE TABLE `ms_skillcategory` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_skillcategory`
--

INSERT INTO `ms_skillcategory` (`category_id`, `category_name`) VALUES
(1, 'Design'),
(2, 'Programming'),
(3, 'Language'),
(4, 'Web Development'),
(5, 'Mobile App Development'),
(6, 'Data Science & Analytics'),
(7, 'Cybersecurity'),
(8, 'Game Development'),
(9, 'Blockchain & Crypto'),
(10, 'Cloud Computing'),
(11, 'DevOps & Engineering'),
(12, 'AI & Machine Learning'),
(13, 'Hardware & IoT'),
(14, 'Graphic Design'),
(15, 'UI/UX Design'),
(16, 'Illustration & Drawing'),
(17, '3D Modeling & Animation'),
(18, 'Fashion Design'),
(19, 'Interior Design'),
(20, 'Product Design'),
(21, 'Photography'),
(22, 'Videography & Editing'),
(23, 'Calligraphy & Typography'),
(24, 'Digital Marketing'),
(25, 'SEO & SEM'),
(26, 'Social Media Management'),
(27, 'Content Strategy'),
(28, 'Project Management'),
(29, 'Business Strategy'),
(30, 'Entrepreneurship'),
(31, 'Accounting & Finance'),
(32, 'Human Resources (HR)'),
(33, 'Sales & Negotiation'),
(34, 'Public Speaking'),
(35, 'Creative Writing'),
(36, 'Copywriting'),
(37, 'Technical Writing'),
(38, 'Journalism'),
(39, 'Translation'),
(40, 'English Language'),
(41, 'Mandarin Language'),
(42, 'Japanese Language'),
(43, 'Korean Language'),
(44, 'European Languages'),
(45, 'Music Production'),
(46, 'Vocal Training'),
(47, 'Guitar & Bass'),
(48, 'Piano & Keyboards'),
(49, 'Drums & Percussion'),
(50, 'Audio Engineering'),
(51, 'Songwriting'),
(52, 'Cooking & Culinary Arts'),
(53, 'Baking & Pastry'),
(54, 'Gardening & Agriculture'),
(55, 'DIY & Crafts'),
(56, 'Knitting & Sewing'),
(57, 'Gaming & Esports'),
(58, 'Travel Planning'),
(59, 'Makeup & Beauty'),
(60, 'Pet Training'),
(61, 'Personal Training & Fitness'),
(62, 'Yoga & Pilates'),
(63, 'Nutrition & Diet'),
(64, 'Meditation & Mindfulness'),
(65, 'Mental Health Awareness'),
(66, 'Martial Arts'),
(67, 'Mathematics'),
(68, 'Physics & Chemistry'),
(69, 'Biology & Life Sciences'),
(70, 'History & Social Studies'),
(71, 'Economics'),
(72, 'Psychology'),
(73, 'Research Methodology'),
(74, 'Legal & Law'),
(75, 'Career Coaching');

-- --------------------------------------------------------

--
-- Table structure for table `ms_topuppackages`
--

CREATE TABLE `ms_topuppackages` (
  `package_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price_money` decimal(10,2) DEFAULT NULL,
  `credit_amount` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_topuppackages`
--

INSERT INTO `ms_topuppackages` (`package_id`, `name`, `price_money`, `credit_amount`, `created_at`) VALUES
(6, 'Starter Pack', 10000.00, 100, '2025-12-31 00:02:30'),
(7, 'Pro Pack', 45000.00, 500, '2025-12-31 00:02:30'),
(8, 'Ultimate Pack', 80000.00, 1000, '2025-12-31 00:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `ms_usercredits`
--

CREATE TABLE `ms_usercredits` (
  `credit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `balance` int(11) DEFAULT 0,
  `update_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_usercredits`
--

INSERT INTO `ms_usercredits` (`credit_id`, `user_id`, `balance`, `update_at`) VALUES
(1, 1, 1500, '2025-12-16 20:39:58'),
(2, 2, 300, '2025-12-16 20:39:58'),
(3, 3, 500, '2025-12-16 20:39:58'),
(4, 4, 227, '2025-12-18 15:58:53'),
(5, 5, 0, '2025-12-17 13:58:34'),
(6, 6, 3647, '2025-12-20 09:48:25'),
(7, 7, 410, '2025-12-20 09:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `ms_users`
--

CREATE TABLE `ms_users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','expert','admin') DEFAULT 'user',
  `joined_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_users`
--

INSERT INTO `ms_users` (`user_id`, `name`, `email`, `phone_number`, `password`, `profile_picture`, `bio`, `role`, `joined_date`) VALUES
(1, 'Admin SkillSwap', 'admin@skillswap.com', '0811111111', '$2y$10$rEcLbYGaqZjdDYggVzxj2OaNtHKyu1eD4mOAPA1fUw/as5nc13ZSi', NULL, 'System administrator', 'admin', '2025-12-16 20:39:58'),
(2, 'Alice User', 'alice@skillswap.com', '0822222222', '$2y$10$userhash', 'default.png', 'Learning new skills', 'user', '2025-12-16 20:39:58'),
(3, 'Bob Expert', 'bob@skillswap.com', '0833333333', '$2y$10$experthash', 'default.png', 'Professional designer', 'expert', '2025-12-16 20:39:58'),
(4, 'Vanessa Angelica Budiono', 'vanessa.budiono@binus.ac.id', '08123456789', '$2y$10$2zooc6fphgwX/aNTdwybuuxslAx0vEceIk8/eTmVGIk6xTfVuLlnO', '1765892711_VanessaAngelicaBudiono.jpg', 'Aa', 'expert', '2025-12-16 20:45:11'),
(5, 'Tralalelo Tralala', 'tralalelo.tralala@gmail.com', '0871273818239', '$2y$10$0nkwfvfF9Uzl1mlUTT4w2Om2YtNRYLk.KgXvhAHF/C0I/0cByWnnO', 'default.png', 'asfasdf', 'user', '2025-12-17 13:58:34'),
(6, 'Steven', 'steven@gmail.com', '0839823', '$2y$10$mqx4t191ElRhIQMVp78al.SVwu0RJZ1TozdZXqmmcrSqda1fqgcg2', '1766028668_Steven.JPG', 'Hello', 'user', '2025-12-18 10:31:08'),
(7, 'ucup', 'ucup@gmail.com', '08943423232', '$2y$10$vKNswpVooyfQ3VM9uMRDb.m2rFpyeEfKEq8839Cm9jQLoyOBBLJVC', '1766199165_ucup.jpg', 'Hai', 'user', '2025-12-20 09:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `ms_userskills`
--

CREATE TABLE `ms_userskills` (
  `skill_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `skill_name` varchar(100) DEFAULT NULL,
  `skill_type` enum('teach','learn') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_userskills`
--

INSERT INTO `ms_userskills` (`skill_id`, `user_id`, `category_id`, `skill_name`, `skill_type`, `created_at`) VALUES
(1, 3, 1, 'UI/UX Design', 'teach', '2025-12-16 20:39:58'),
(2, 2, 2, 'Basic Java', 'learn', '2025-12-16 20:39:58'),
(3, 3, 2, 'Web Development', 'teach', '2025-12-16 20:39:58'),
(10, 4, 21, 'Photography', 'teach', '2025-12-17 14:25:26'),
(11, 4, 47, 'Guitar', 'learn', '2025-12-17 14:25:26'),
(45, 3, 12, 'Machine Learning Algorithm', 'learn', '2025-12-29 23:12:42'),
(46, 7, 9, 'Basic Blockchain', 'teach', '2025-12-30 00:32:49'),
(47, 7, 10, 'Basic Cloud Computing', 'learn', '2025-12-30 00:33:10'),
(48, 5, 29, 'Digital Business Today', 'teach', '2025-12-30 00:33:26'),
(49, 5, 1, 'Digital Design', 'learn', '2025-12-30 00:33:41'),
(50, 2, 53, 'Salt Bread', 'teach', '2025-12-30 00:34:07'),
(51, 6, 52, 'Cooking', 'teach', '2025-12-30 00:34:24'),
(52, 6, 18, 'Fashion Design', 'learn', '2025-12-30 00:34:24'),
(53, 6, 21, 'Golden Ratio Photography', 'learn', '2025-12-30 00:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `ms_voucher`
--

CREATE TABLE `ms_voucher` (
  `voucher_id` int(11) NOT NULL,
  `voucher_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `partner_name` varchar(100) DEFAULT NULL,
  `credits_price` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ms_voucher`
--

INSERT INTO `ms_voucher` (`voucher_id`, `voucher_name`, `description`, `partner_name`, `credits_price`, `stock`, `image`, `created_at`, `end_date`, `is_active`) VALUES
(1, '10% Discount', 'Discount 10% ', 'Tokopedia', 100, 32, 'v10.png', '2025-12-16 20:39:58', '2026-01-01 00:00:00', 1),
(2, 'Free Shipping', 'Free Ongkir', 'Shopee', 150, 26, 'freeongkir.png', '2025-12-16 20:39:58', '2025-10-31 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tr_coursepurchase`
--

CREATE TABLE `tr_coursepurchase` (
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_coursepurchase`
--

INSERT INTO `tr_coursepurchase` (`purchase_id`, `user_id`, `course_id`, `created_at`) VALUES
(1, 2, 1, '2025-12-16 20:39:58'),
(2, 4, 2, '2025-12-17 01:40:38'),
(5, 4, 3, '2025-12-17 14:33:07'),
(10, 6, 1, '2025-12-19 21:01:03'),
(11, 7, 4, '2025-12-31 11:12:33'),
(12, 6, 3, '2025-12-31 11:14:17');

-- --------------------------------------------------------

--
-- Table structure for table `tr_credithistory`
--

CREATE TABLE `tr_credithistory` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `transaction_type` enum('topup','earning','spending') NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_credithistory`
--

INSERT INTO `tr_credithistory` (`history_id`, `user_id`, `amount`, `transaction_type`, `description`, `created_at`) VALUES
(1, 6, 500, 'topup', 'Top Up: Pro Pack', '2025-12-31 04:17:07'),
(2, 6, 1000, 'topup', 'Top Up: Ultimate Pack', '2025-12-31 04:23:26'),
(3, 6, 500, 'topup', 'Top Up: Pro Pack', '2025-12-31 04:23:36'),
(4, 6, 10, 'earning', 'Reward for completing agreement (Booster 2x Active)', '2025-12-31 05:08:35'),
(5, 7, 5, 'earning', 'Reward for completing agreement', '2025-12-31 05:08:35');

-- --------------------------------------------------------

--
-- Table structure for table `tr_credittopup`
--

CREATE TABLE `tr_credittopup` (
  `topup_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_credittopup`
--

INSERT INTO `tr_credittopup` (`topup_id`, `user_id`, `package_id`, `payment_method`, `confirmed_at`, `created_at`) VALUES
(20, 6, 7, 'Virtual Account', '2025-12-31 11:17:07', '2025-12-31 11:17:07'),
(21, 6, 8, 'Virtual Account', '2025-12-31 11:23:26', '2025-12-31 11:23:26'),
(22, 6, 7, 'Virtual Account', '2025-12-31 11:23:36', '2025-12-31 11:23:36');

-- --------------------------------------------------------

--
-- Table structure for table `tr_redeemvoucher`
--

CREATE TABLE `tr_redeemvoucher` (
  `redeem_id` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('available','used','expired') DEFAULT NULL,
  `redeem_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_redeemvoucher`
--

INSERT INTO `tr_redeemvoucher` (`redeem_id`, `voucher_id`, `user_id`, `status`, `redeem_date`) VALUES
(2, 2, 4, 'available', '2025-12-16 23:44:17'),
(3, 1, 4, 'expired', '2025-12-17 00:28:57'),
(4, 1, 4, 'available', '2025-12-17 07:58:47'),
(5, 1, 4, 'available', '2025-12-17 13:16:25'),
(6, 1, 4, 'available', '2025-12-17 14:26:34'),
(9, 1, 4, 'available', '2025-12-18 15:50:42'),
(10, 1, 4, 'available', '2025-12-18 15:50:49'),
(17, 1, 6, 'expired', '2025-12-20 09:13:08'),
(18, 1, 6, 'available', '2025-12-20 09:15:18'),
(19, 2, 6, 'used', '2025-12-20 09:15:21'),
(20, 1, 6, 'used', '2025-12-20 09:48:25');

-- --------------------------------------------------------

--
-- Table structure for table `tr_swapagreement`
--

CREATE TABLE `tr_swapagreement` (
  `agreement_id` int(11) NOT NULL,
  `user_a_id` int(11) DEFAULT NULL,
  `user_b_id` int(11) DEFAULT NULL,
  `status` enum('pending','scheduled','half_completed','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `text` text DEFAULT NULL,
  `skill_offered_id` int(11) DEFAULT NULL COMMENT 'ID Skill milik User A (yang diajarkan)',
  `skill_requested_name` varchar(255) DEFAULT NULL COMMENT 'Nama Skill milik User B (yang diminta User A)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_swapagreement`
--

INSERT INTO `tr_swapagreement` (`agreement_id`, `user_a_id`, `user_b_id`, `status`, `created_at`, `completed_at`, `text`, `skill_offered_id`, `skill_requested_name`) VALUES
(19, 6, 7, 'completed', '2025-12-30 18:22:22', NULL, 'Halo', 51, 'Basic Blockchain'),
(27, 6, 7, 'completed', '2025-12-30 20:29:53', NULL, 'HELLOOOOOO', 51, 'Basic Blockchain'),
(28, 6, 7, 'cancelled', '2025-12-31 11:24:02', NULL, 'HAKJFKSAKF', 51, 'Basic Blockchain'),
(29, 6, 7, 'cancelled', '2025-12-31 11:41:40', NULL, 'HAI GUYS', 51, 'Basic Blockchain'),
(30, 6, 7, 'completed', '2025-12-31 11:54:15', NULL, 'HALLOOO', 51, 'Basic Blockchain');

-- --------------------------------------------------------

--
-- Table structure for table `tr_swapsession`
--

CREATE TABLE `tr_swapsession` (
  `session_id` int(11) NOT NULL,
  `agreement_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL COMMENT 'User ID yang mengajar',
  `learner_id` int(11) NOT NULL COMMENT 'User ID yang belajar',
  `skill_id` int(11) NOT NULL COMMENT 'Skill yang diajarkan',
  `scheduled_datetime` datetime DEFAULT NULL,
  `status` enum('pending_schedule','proposed','scheduled','ongoing','completed','cancelled') DEFAULT 'pending_schedule',
  `meeting_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proposed_by` int(11) DEFAULT NULL COMMENT 'ID User yang mengajukan tanggal',
  `started_at` datetime DEFAULT NULL COMMENT 'Waktu sesi dimulai (klik start)',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_swapsession`
--

INSERT INTO `tr_swapsession` (`session_id`, `agreement_id`, `teacher_id`, `learner_id`, `skill_id`, `scheduled_datetime`, `status`, `meeting_link`, `created_at`, `proposed_by`, `started_at`, `completed_at`) VALUES
(9, 27, 6, 7, 51, '2025-12-30 20:35:00', 'completed', 'https://docs.google.com/document/d/1akJHQE9vk_uXqwHX9IyhNVVw5oiI936eZYTMsYKkLBU/edit?tab=t.0', '2025-12-30 13:29:59', 6, '2025-12-31 00:40:20', NULL),
(10, 27, 7, 6, 46, '2025-12-30 20:33:00', 'completed', 'https://docs.google.com/document/d/1akJHQE9vk_uXqwHX9IyhNVVw5oiI936eZYTMsYKkLBU/edit?tab=t.0', '2025-12-30 13:29:59', 7, '2025-12-30 20:31:34', NULL),
(11, 19, 6, 7, 51, '2025-12-31 01:30:00', 'completed', 'https://docs.google.com/document/d/1akJHQE9vk_uXqwHX9IyhNVVw5oiI936eZYTMsYKkLBU/edit?tab=t.0', '2025-12-30 18:28:33', 6, '2025-12-31 01:47:35', '2025-12-31 09:53:36'),
(12, 19, 7, 6, 46, '2025-12-31 01:30:00', 'completed', 'https://docs.google.com/document/d/1akJHQE9vk_uXqwHX9IyhNVVw5oiI936eZYTMsYKkLBU/edit?tab=t.0', '2025-12-30 18:28:33', 7, '2025-12-31 01:44:23', '2025-12-31 09:53:20'),
(13, 30, 6, 7, 51, '2025-12-31 11:56:00', 'completed', 'https://gemini.google.com/app/7504db219ccaadce?hl=id', '2025-12-31 04:54:27', 6, '2025-12-31 12:05:31', '2025-12-31 12:08:35'),
(14, 30, 7, 6, 46, '2025-12-31 11:56:00', 'completed', 'https://gemini.google.com/app/7504db219ccaadce?hl=id', '2025-12-31 04:54:27', 7, '2025-12-31 12:01:55', '2025-12-31 12:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `tr_userbooster`
--

CREATE TABLE `tr_userbooster` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booster_id` int(11) NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_userbooster`
--

INSERT INTO `tr_userbooster` (`id`, `user_id`, `booster_id`, `purchased_at`) VALUES
(2, 6, 5, '2025-12-30 17:20:44');

-- --------------------------------------------------------

--
-- Table structure for table `tr_userreviews`
--

CREATE TABLE `tr_userreviews` (
  `review_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewee_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `agreement_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_userreviews`
--

INSERT INTO `tr_userreviews` (`review_id`, `reviewer_id`, `reviewee_id`, `rating`, `review`, `created_at`, `agreement_id`) VALUES
(9, 6, 7, 3, 'Good', '2025-12-31 10:03:35', 27),
(10, 6, 7, 5, 'GOod', '2025-12-31 10:03:44', 19);

-- --------------------------------------------------------

--
-- Table structure for table `tr_userverification`
--

CREATE TABLE `tr_userverification` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `portfolio_link` varchar(255) DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_userverification`
--

INSERT INTO `tr_userverification` (`verification_id`, `user_id`, `cv_file`, `portfolio_link`, `explanation`, `status`, `submitted_at`, `reviewed_at`) VALUES
(1, 3, 'cv_bob.pdf', 'https://portfolio.bob.com', 'Saya ingin berbagi ilmu', 'rejected', '2025-12-16 20:39:58', '2025-12-19 21:11:29'),
(2, 4, 'cv_vanessa.pdf', 'https://porto', 'Pengalaman saya sudah 5 tahun', 'approved', '2025-12-17 12:48:46', '2025-12-17 13:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `tr_withdraw`
--

CREATE TABLE `tr_withdraw` (
  `withdraw_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `credit_amount` int(11) DEFAULT NULL,
  `money_received` int(11) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `bank_name` varchar(100) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr_withdraw`
--

INSERT INTO `tr_withdraw` (`withdraw_id`, `user_id`, `credit_amount`, `money_received`, `bank_account_number`, `status`, `received_at`, `created_at`, `bank_name`, `account_holder_name`) VALUES
(1, 3, 200, 20000, '1234567890', 'pending', NULL, '2025-12-16 20:39:58', NULL, NULL),
(2, 4, 10000, 10000, '1234567', 'completed', '2025-12-17 08:22:44', '2025-12-17 08:22:44', 'BCA', 'Pramodya'),
(3, 4, 10000, 10000, '008909089', 'completed', '2025-12-18 09:53:08', '2025-12-18 09:53:08', 'BCA', 'Putra');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ms_boosters`
--
ALTER TABLE `ms_boosters`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `ms_coursecategory`
--
ALTER TABLE `ms_coursecategory`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `ms_courses`
--
ALTER TABLE `ms_courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `ms_skillcategory`
--
ALTER TABLE `ms_skillcategory`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `ms_topuppackages`
--
ALTER TABLE `ms_topuppackages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `ms_usercredits`
--
ALTER TABLE `ms_usercredits`
  ADD PRIMARY KEY (`credit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ms_users`
--
ALTER TABLE `ms_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ms_userskills`
--
ALTER TABLE `ms_userskills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `skill_category` (`category_id`);

--
-- Indexes for table `ms_voucher`
--
ALTER TABLE `ms_voucher`
  ADD PRIMARY KEY (`voucher_id`);

--
-- Indexes for table `tr_coursepurchase`
--
ALTER TABLE `tr_coursepurchase`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `tr_credithistory`
--
ALTER TABLE `tr_credithistory`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tr_credittopup`
--
ALTER TABLE `tr_credittopup`
  ADD PRIMARY KEY (`topup_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `tr_redeemvoucher`
--
ALTER TABLE `tr_redeemvoucher`
  ADD PRIMARY KEY (`redeem_id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tr_swapagreement`
--
ALTER TABLE `tr_swapagreement`
  ADD PRIMARY KEY (`agreement_id`),
  ADD KEY `user_a_id` (`user_a_id`),
  ADD KEY `user_b_id` (`user_b_id`),
  ADD KEY `fk_agreement_skill_offered` (`skill_offered_id`);

--
-- Indexes for table `tr_swapsession`
--
ALTER TABLE `tr_swapsession`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `agreement_id` (`agreement_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `learner_id` (`learner_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `tr_userbooster`
--
ALTER TABLE `tr_userbooster`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`),
  ADD KEY `fk_booster_Id` (`booster_id`);

--
-- Indexes for table `tr_userreviews`
--
ALTER TABLE `tr_userreviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`),
  ADD KEY `tr_userreviews_ibfk_3` (`agreement_id`);

--
-- Indexes for table `tr_userverification`
--
ALTER TABLE `tr_userverification`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tr_withdraw`
--
ALTER TABLE `tr_withdraw`
  ADD PRIMARY KEY (`withdraw_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ms_boosters`
--
ALTER TABLE `ms_boosters`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ms_coursecategory`
--
ALTER TABLE `ms_coursecategory`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ms_courses`
--
ALTER TABLE `ms_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ms_skillcategory`
--
ALTER TABLE `ms_skillcategory`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `ms_topuppackages`
--
ALTER TABLE `ms_topuppackages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ms_usercredits`
--
ALTER TABLE `ms_usercredits`
  MODIFY `credit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ms_users`
--
ALTER TABLE `ms_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ms_userskills`
--
ALTER TABLE `ms_userskills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `ms_voucher`
--
ALTER TABLE `ms_voucher`
  MODIFY `voucher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tr_coursepurchase`
--
ALTER TABLE `tr_coursepurchase`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tr_credithistory`
--
ALTER TABLE `tr_credithistory`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tr_credittopup`
--
ALTER TABLE `tr_credittopup`
  MODIFY `topup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tr_redeemvoucher`
--
ALTER TABLE `tr_redeemvoucher`
  MODIFY `redeem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tr_swapagreement`
--
ALTER TABLE `tr_swapagreement`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tr_swapsession`
--
ALTER TABLE `tr_swapsession`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tr_userbooster`
--
ALTER TABLE `tr_userbooster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tr_userreviews`
--
ALTER TABLE `tr_userreviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tr_userverification`
--
ALTER TABLE `tr_userverification`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tr_withdraw`
--
ALTER TABLE `tr_withdraw`
  MODIFY `withdraw_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ms_courses`
--
ALTER TABLE `ms_courses`
  ADD CONSTRAINT `ms_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `ms_courses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `ms_coursecategory` (`category_id`);

--
-- Constraints for table `ms_usercredits`
--
ALTER TABLE `ms_usercredits`
  ADD CONSTRAINT `ms_usercredits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`);

--
-- Constraints for table `ms_userskills`
--
ALTER TABLE `ms_userskills`
  ADD CONSTRAINT `ms_userskills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `ms_userskills_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `ms_skillcategory` (`category_id`);

--
-- Constraints for table `tr_coursepurchase`
--
ALTER TABLE `tr_coursepurchase`
  ADD CONSTRAINT `tr_coursepurchase_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `tr_coursepurchase_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `ms_courses` (`course_id`);

--
-- Constraints for table `tr_credithistory`
--
ALTER TABLE `tr_credithistory`
  ADD CONSTRAINT `tr_credithistory_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_credittopup`
--
ALTER TABLE `tr_credittopup`
  ADD CONSTRAINT `tr_credittopup_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `tr_credittopup_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `ms_topuppackages` (`package_id`);

--
-- Constraints for table `tr_redeemvoucher`
--
ALTER TABLE `tr_redeemvoucher`
  ADD CONSTRAINT `tr_redeemvoucher_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `ms_voucher` (`voucher_id`),
  ADD CONSTRAINT `tr_redeemvoucher_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`);

--
-- Constraints for table `tr_swapagreement`
--
ALTER TABLE `tr_swapagreement`
  ADD CONSTRAINT `fk_agreement_skill_offered` FOREIGN KEY (`skill_offered_id`) REFERENCES `ms_userskills` (`skill_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tr_swapagreement_ibfk_1` FOREIGN KEY (`user_a_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `tr_swapagreement_ibfk_2` FOREIGN KEY (`user_b_id`) REFERENCES `ms_users` (`user_id`);

--
-- Constraints for table `tr_swapsession`
--
ALTER TABLE `tr_swapsession`
  ADD CONSTRAINT `tr_swapsession_ibfk_1` FOREIGN KEY (`agreement_id`) REFERENCES `tr_swapagreement` (`agreement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_swapsession_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `tr_swapsession_ibfk_3` FOREIGN KEY (`learner_id`) REFERENCES `ms_users` (`user_id`),
  ADD CONSTRAINT `tr_swapsession_ibfk_4` FOREIGN KEY (`skill_id`) REFERENCES `ms_userskills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_userbooster`
--
ALTER TABLE `tr_userbooster`
  ADD CONSTRAINT `fk_booster_Id` FOREIGN KEY (`booster_id`) REFERENCES `ms_boosters` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_userbooster_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_userreviews`
--
ALTER TABLE `tr_userreviews`
  ADD CONSTRAINT `tr_userreviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_userreviews_ibfk_2` FOREIGN KEY (`reviewee_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_userreviews_ibfk_3` FOREIGN KEY (`agreement_id`) REFERENCES `tr_swapagreement` (`agreement_id`);

--
-- Constraints for table `tr_userverification`
--
ALTER TABLE `tr_userverification`
  ADD CONSTRAINT `tr_userverification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tr_withdraw`
--
ALTER TABLE `tr_withdraw`
  ADD CONSTRAINT `tr_withdraw_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
