-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 05:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `feedloop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `admin_id`, `ip_address`, `user_agent`, `timestamp`) VALUES
(92, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-10-28 05:42:15'),
(93, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:42:15'),
(94, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-10-28 05:44:33'),
(95, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 05:44:33'),
(96, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-10-28 06:51:53'),
(97, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 06:51:53'),
(98, 13, 'form_created', 'Created custom form: KALIKASAN (ID: 2)', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 06:56:02'),
(99, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-10-28 07:13:47'),
(100, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 07:13:47'),
(101, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-11-01 06:44:51'),
(102, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 06:44:51'),
(103, 13, 'form_deleted', '{\"form_id\":2,\"form_title\":\"KALIKASAN\",\"deleted_questions\":2,\"deleted_responses\":0,\"deleted_answers\":0}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 13:11:10'),
(104, 13, 'google_login', 'User logged in via Google OAuth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:07:15'),
(109, 14, 'user_registration', 'User registered with email verification: donlyone7tm@gmail.com', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:26:20'),
(110, 14, 'google_login', 'User logged in via Google OAuth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:32:48'),
(111, 14, 'google_login', 'User logged in via Google OAuth', NULL, '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '2025-11-05 06:13:30'),
(112, 14, 'google_login', 'User logged in via Google OAuth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-05 08:22:30'),
(113, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-11-08 07:43:37'),
(114, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-08 07:43:37'),
(119, 15, 'user_registration', 'User registered with email verification: donlyone7tmbrazil@gmail.com', NULL, '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '2025-11-08 13:25:03'),
(120, 14, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-11-10 15:32:25'),
(121, 14, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-11-10 15:32:34'),
(122, 13, 'login', 'User logged in successfully', NULL, '127.0.0.1', NULL, '2025-11-10 15:33:38'),
(123, 13, 'admin_login', 'Admin login successful - Position: Admin', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-10 15:33:38'),
(124, 13, 'form_created', 'Created custom form: KASIKAS - BBB PROGRAM (ID: 3)', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-10 15:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `full_name`, `position`) VALUES
(6, 13, 'Fredrex Salac', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `custom_forms`
--

CREATE TABLE `custom_forms` (
  `form_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `visibility` enum('public','private','department','event') DEFAULT 'public',
  `target_audience` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `form_code` varchar(20) NOT NULL,
  `qr_code_path` varchar(500) DEFAULT NULL,
  `shareable_link` varchar(500) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `allow_anonymous` tinyint(1) DEFAULT 1,
  `require_login` tinyint(1) DEFAULT 0,
  `max_responses` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `response_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_forms`
--

INSERT INTO `custom_forms` (`form_id`, `title`, `description`, `created_by`, `visibility`, `target_audience`, `department`, `event_name`, `form_code`, `qr_code_path`, `shareable_link`, `is_active`, `allow_anonymous`, `require_login`, `max_responses`, `expires_at`, `response_count`, `created_at`, `updated_at`) VALUES
(3, 'KASIKAS - BBB PROGRAM', 'A Test Survey for the people who are Agreed to Build, Build, Build Program', 13, 'public', '', NULL, NULL, 'S4INYAHP', 'assets/img/qr_codes/S4INYAHP.png', 'https://localhost/feedloop/public/form/S4INYAHP', 1, 1, 0, NULL, '2025-11-11 23:41:00', 2, '2025-11-10 15:45:02', '2025-11-10 15:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `email`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:00:36'),
(2, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:01:13'),
(4, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:31:34'),
(5, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:32:19'),
(6, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:37:13'),
(7, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:38:16'),
(8, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:49:37'),
(9, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:50:04'),
(10, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 07:03:46'),
(11, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:16:41'),
(12, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:16:50'),
(13, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:18:15'),
(14, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '10.77.12.44', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-01 14:32:49'),
(15, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:34:14'),
(16, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:34:23'),
(17, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:37:32'),
(18, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-01 14:38:31'),
(19, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:11:33'),
(20, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:15:08'),
(21, 'fredrexsalac@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:21:30'),
(22, 'fredrexsalac@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:22:49'),
(23, 'donlyone7tm@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-03 09:24:37'),
(24, 'donlyone7tm@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-05 06:54:13'),
(25, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:52:05'),
(26, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:17'),
(27, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:21'),
(28, 'fredrexsalac@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:25'),
(33, 'donlyone7tm@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:06:34'),
(34, 'donlyone7tm@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:12:36'),
(35, 'donlyone7tm@gmail.com', 'reset_code_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:19:55'),
(36, 'donlyone7tmbrazil@gmail.com', 'registration_otp_sent', 'success', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '2025-11-08 13:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `email_oauth_config`
--

CREATE TABLE `email_oauth_config` (
  `id` int(11) NOT NULL,
  `oauth_token` text NOT NULL,
  `oauth_refresh_token` text NOT NULL,
  `oauth_expires_at` datetime NOT NULL,
  `oauth_email` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_smtp_config`
--

CREATE TABLE `email_smtp_config` (
  `id` int(11) NOT NULL,
  `smtp_username` varchar(255) NOT NULL COMMENT 'Gmail address',
  `smtp_password` varchar(255) NOT NULL COMMENT 'Gmail App Password',
  `is_active` tinyint(1) DEFAULT 1,
  `configured_by` int(11) DEFAULT NULL COMMENT 'Admin user_id who configured',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_smtp_config`
--

INSERT INTO `email_smtp_config` (`id`, `smtp_username`, `smtp_password`, `is_active`, `configured_by`, `created_at`, `updated_at`) VALUES
(1, 'fredrexsalac@gmail.com', 'abcdefghijklmnop', 1, 1, '2025-10-25 12:18:39', '2025-10-25 12:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_categories`
--

CREATE TABLE `feedback_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_categories`
--

INSERT INTO `feedback_categories` (`id`, `category_name`, `category_description`, `is_active`, `created_at`) VALUES
(1, 'Department Feedback', 'Feedback related to specific departments and their operations', 1, '2025-10-14 09:29:06'),
(2, 'Instructor Feedback', 'Feedback about instructors, teaching methods, and academic performance', 1, '2025-10-14 09:29:06'),
(3, 'Event Feedback', 'Feedback regarding events, activities, and programs', 1, '2025-10-14 09:29:06'),
(4, 'Dean/Office Feedback', 'Feedback directed to dean offices and administrative units', 1, '2025-10-14 09:29:06'),
(5, 'System Feedback', 'Feedback about the FeedLoop system itself and technical issues', 1, '2025-10-14 09:29:06'),
(6, 'Community-Based Issues', 'Feedback about community concerns and social issues', 1, '2025-10-14 09:29:06');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_limits`
--

CREATE TABLE `feedback_limits` (
  `id` int(11) NOT NULL,
  `daily_limit` int(11) NOT NULL DEFAULT 5,
  `weekly_limit` int(11) NOT NULL DEFAULT 20,
  `monthly_limit` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_responses`
--

CREATE TABLE `feedback_responses` (
  `response_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `action_taken` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_submissions`
--

CREATE TABLE `feedback_submissions` (
  `submission_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `subject_old` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `category` enum('general','suggestion','bug_report','complaint','other') DEFAULT 'general',
  `feedback_category` varchar(255) NOT NULL DEFAULT 'System Feedback',
  `status` enum('pending','under_review','resolved','rejected') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `frontend_user_id` int(11) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT 'student',
  `admin_response` text DEFAULT NULL,
  `admin_response_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_analytics`
--

CREATE TABLE `form_analytics` (
  `analytics_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `total_views` int(11) DEFAULT 0,
  `total_starts` int(11) DEFAULT 0,
  `total_completions` int(11) DEFAULT 0,
  `completion_rate` decimal(5,2) DEFAULT 0.00,
  `average_completion_time` int(11) DEFAULT NULL,
  `last_response_at` timestamp NULL DEFAULT NULL,
  `analytics_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analytics_data`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_analytics`
--

INSERT INTO `form_analytics` (`analytics_id`, `form_id`, `total_views`, `total_starts`, `total_completions`, `completion_rate`, `average_completion_time`, `last_response_at`, `analytics_data`, `updated_at`) VALUES
(3, 3, 2, 0, 2, 0.00, NULL, '2025-11-10 15:47:09', NULL, '2025-11-10 15:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `form_answers`
--

CREATE TABLE `form_answers` (
  `answer_id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_value` decimal(10,2) DEFAULT NULL,
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_options`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_answers`
--

INSERT INTO `form_answers` (`answer_id`, `response_id`, `question_id`, `answer_text`, `answer_value`, `selected_options`, `created_at`) VALUES
(1, 1, 8, 'Yes', NULL, '[\"Yes\"]', '2025-11-10 15:47:09'),
(2, 1, 9, 'Strongly Agree', NULL, '[\"Strongly Agree\"]', '2025-11-10 15:47:09'),
(3, 1, 10, 'cause this can improve the community by passing the bridge to the other barangays can be symphasized as the true collaboration', NULL, NULL, '2025-11-10 15:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `form_questions`
--

CREATE TABLE `form_questions` (
  `question_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','textarea','radio','checkbox','dropdown','rating_stars','rating_scale','slider','email','number','date','time') NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `question_order` int(11) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `placeholder_text` varchar(255) DEFAULT NULL,
  `help_text` text DEFAULT NULL,
  `min_value` int(11) DEFAULT NULL,
  `max_value` int(11) DEFAULT NULL,
  `step_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_questions`
--

INSERT INTO `form_questions` (`question_id`, `form_id`, `question_text`, `question_type`, `is_required`, `question_order`, `options`, `validation_rules`, `placeholder_text`, `help_text`, `min_value`, `max_value`, `step_value`, `created_at`, `updated_at`) VALUES
(8, 3, 'Have you heard about this Program?', 'radio', 1, 1, '{\"options\":[\"Yes\",\"No\"],\"allow_other\":false,\"other_placeholder\":\"Please specify...\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 15:45:02', '2025-11-10 15:45:02'),
(9, 3, 'Do you Agree about the program for more updates about building a Bridge to improve crossing the other barangays?', 'checkbox', 1, 2, '{\"options\":[\"Very Disagree\",\"Disagree\",\"Okay\",\"Agree\",\"Strongly Agree\"],\"allow_other\":false,\"other_placeholder\":\"Please specify...\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 15:45:02', '2025-11-10 15:45:02'),
(10, 3, 'If So....can you briefly comment why you would Agree?', 'textarea', 1, 3, NULL, '{\"max_length\":999}', NULL, NULL, NULL, NULL, NULL, '2025-11-10 15:45:02', '2025-11-10 15:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `form_responses`
--

CREATE TABLE `form_responses` (
  `response_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `respondent_name` varchar(255) DEFAULT NULL,
  `respondent_email` varchar(255) DEFAULT NULL,
  `respondent_type` enum('student','guest','staff','anonymous') DEFAULT 'anonymous',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `submission_source` enum('qr_code','direct_link','embedded') DEFAULT 'direct_link',
  `is_complete` tinyint(1) DEFAULT 1,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_responses`
--

INSERT INTO `form_responses` (`response_id`, `form_id`, `respondent_name`, `respondent_email`, `respondent_type`, `user_id`, `ip_address`, `user_agent`, `submission_source`, `is_complete`, `submitted_at`, `updated_at`, `started_at`) VALUES
(1, 3, 'Carmela', 'carmelamondelo@gmail.com', 'guest', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', 'direct_link', 1, '2025-11-10 15:47:09', '2025-11-10 15:47:09', NULL);

--
-- Triggers `form_responses`
--
DELIMITER $$
CREATE TRIGGER `update_form_response_count` AFTER INSERT ON `form_responses` FOR EACH ROW BEGIN
    UPDATE `custom_forms` 
    SET `response_count` = (
        SELECT COUNT(*) 
        FROM `form_responses` 
        WHERE `form_id` = NEW.form_id AND `is_complete` = 1
    )
    WHERE `form_id` = NEW.form_id;
    
    -- Update analytics
    UPDATE `form_analytics` 
    SET `total_completions` = (
        SELECT COUNT(*) 
        FROM `form_responses` 
        WHERE `form_id` = NEW.form_id AND `is_complete` = 1
    ),
    `last_response_at` = NEW.submitted_at
    WHERE `form_id` = NEW.form_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_form_response_count_delete` AFTER DELETE ON `form_responses` FOR EACH ROW BEGIN
    UPDATE `custom_forms` 
    SET `response_count` = (
        SELECT COUNT(*) 
        FROM `form_responses` 
        WHERE `form_id` = OLD.form_id AND `is_complete` = 1
    )
    WHERE `form_id` = OLD.form_id;
    
    -- Update analytics
    UPDATE `form_analytics` 
    SET `total_completions` = (
        SELECT COUNT(*) 
        FROM `form_responses` 
        WHERE `form_id` = OLD.form_id AND `is_complete` = 1
    )
    WHERE `form_id` = OLD.form_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `form_statistics`
-- (See below for the actual view)
--
CREATE TABLE `form_statistics` (
`form_id` int(11)
,`title` varchar(255)
,`created_by` int(11)
,`visibility` enum('public','private','department','event')
,`is_active` tinyint(1)
,`created_at` timestamp
,`total_responses` bigint(21)
,`responses_this_week` bigint(21)
,`responses_this_month` bigint(21)
,`avg_rating` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `google_oauth_clients`
--

CREATE TABLE `google_oauth_clients` (
  `id` int(11) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `client_secret` varchar(255) NOT NULL,
  `redirect_uri` varchar(500) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketing`
--

CREATE TABLE `marketing` (
  `marketing_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `posted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('feedback_update','announcement','system','warning') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_attempts`
--

CREATE TABLE `password_reset_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_type` enum('request','verify','reset') NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_attempts`
--

INSERT INTO `password_reset_attempts` (`id`, `email`, `ip_address`, `attempt_type`, `success`, `error_message`, `user_agent`, `created_at`) VALUES
(1, 'fredrexsalac@gmail.com', '::1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:38:16'),
(2, 'unknown', '::1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:39:18'),
(3, 'unknown', '::1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:45:50'),
(4, 'unknown', '::1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:45:51'),
(5, 'fredrexsalac@gmail.com', '::1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:49:37'),
(6, 'fredrexsalac@gmail.com', '::1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-25 11:50:04'),
(7, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 07:03:43'),
(8, 'donlyone7tm@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-05 06:54:09'),
(9, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:52:01'),
(10, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:09'),
(11, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:11'),
(12, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:13'),
(13, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:17'),
(14, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:21'),
(16, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 0, 'Too many reset attempts. Please try again later.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:25'),
(18, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 0, 'Too many reset attempts. Please try again later.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:25'),
(20, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 0, 'Too many reset attempts. Please try again later.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:25'),
(22, 'fredrexsalac@gmail.com', '127.0.0.1', 'request', 0, 'Too many reset attempts. Please try again later.', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:25'),
(23, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:33'),
(24, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:35'),
(25, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:36'),
(26, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:37'),
(27, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:37'),
(28, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:38'),
(29, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:38'),
(30, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:38'),
(31, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:40'),
(32, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:41'),
(33, 'donlyone7tm@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:06:19'),
(34, 'unknown', '127.0.0.1', 'verify', 0, 'Invalid or expired token', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:07:12'),
(35, 'donlyone7tm@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:12:32'),
(36, 'donlyone7tm@gmail.com', '127.0.0.1', 'verify', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:12:52'),
(37, 'donlyone7tm@gmail.com', '127.0.0.1', 'request', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:19:52'),
(38, 'donlyone7tm@gmail.com', '127.0.0.1', 'verify', 1, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `email`, `reset_token`, `reset_code`, `expires_at`, `is_used`, `attempts`, `max_attempts`, `ip_address`, `user_agent`, `created_at`, `used_at`) VALUES
(10, 13, 'fredrexsalac@gmail.com', '39ba0a1e613044a7771fee47dab4f2f0eb2359c305ef1f165e8ef9973bd4e1b5', '420489', '2025-10-28 08:18:43', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-10-28 07:03:43', NULL),
(11, 14, 'donlyone7tm@gmail.com', '494a98e4a0bccc55347f70cc34c8ab50cc3ce62f295f6d742c9f008f3266fdf6', '650203', '2025-11-05 08:09:09', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-05 06:54:09', NULL),
(12, 13, 'fredrexsalac@gmail.com', '33215db3d8677865bb07066388c9fce808278b6dbda686efcd1235fd056dabd2', '947023', '2025-11-06 14:07:01', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:52:01', NULL),
(13, 13, 'fredrexsalac@gmail.com', '7d4122271fda7a68b48b660cf8053b59c13d4bffecb613b6afe4fc01250d3f46', '551989', '2025-11-06 14:10:13', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:13', NULL),
(14, 13, 'fredrexsalac@gmail.com', 'b37587ba8b8c2d4b84c5c84b39f4ddedf667f6e56c045db26ef0db32d6d0c067', '801472', '2025-11-06 14:10:14', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:17', NULL),
(15, 13, 'fredrexsalac@gmail.com', '533332c36abdc7383261dcd9e3fd240476f7b8e3016f2e227c27960c880661a6', '139486', '2025-11-06 14:10:15', 0, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 12:55:21', NULL),
(20, 14, 'donlyone7tm@gmail.com', 'e79d48f817a57d85c031c069add03d5b62bf869274eba6faf8e75d24dd01f478', '196563', '2025-11-06 14:21:19', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:06:19', NULL),
(21, 14, 'donlyone7tm@gmail.com', '1ff723309c3b17122521d6c85a5f8229086d48e1eba95014818cb7f36d369e2a', 'fa0f97ab2b', '2025-11-06 21:22:51', 1, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:12:32', NULL),
(22, 14, 'donlyone7tm@gmail.com', 'fd7ea5bb3e1c30188fb636197fb2a2977343f2cc1cc854510087343fca50b932', 'f579f78be5', '2025-11-06 21:30:06', 0, 0, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-06 13:19:52', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `question_analytics`
-- (See below for the actual view)
--
CREATE TABLE `question_analytics` (
`question_id` int(11)
,`form_id` int(11)
,`question_text` text
,`question_type` enum('text','textarea','radio','checkbox','dropdown','rating_stars','rating_scale','slider','email','number','date','time')
,`total_answers` bigint(21)
,`avg_numeric_value` decimal(14,6)
,`min_numeric_value` decimal(10,2)
,`max_numeric_value` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `report_type` enum('feedback_summary','user_activity','system_analytics') NOT NULL,
  `title` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'admin_email', 'admin@feedloop.com', '2025-10-01 01:48:38'),
(2, 'session_timeout', '35', '2025-10-01 01:48:38'),
(3, 'require_strong_password', '1', '2025-10-01 01:49:11'),
(4, 'enable_activity_logging', '1', '2025-10-01 01:49:11'),
(5, 'max_login_attempts', '10', '2025-10-01 01:49:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` varchar(20) DEFAULT 'student',
  `user_type_other` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `status` enum('active','suspended','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `password_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_type`, `user_type_other`, `full_name`, `profile_pic`, `email_verified`, `role`, `status`, `created_at`, `last_activity`, `session_id`, `password_updated_at`) VALUES
(13, 'fredrexsalac', '$2y$10$erm4Xq3yv6AWpDn0rf0Gj.QxGjWKzlCPnrtF0KCHczny4eJ4yqe7e', 'fredrexsalac@gmail.com', 'student', NULL, NULL, NULL, 0, 'admin', 'active', '2025-10-28 05:41:53', '2025-11-11 04:00:05', 't2pdl2lfhv9ml2at7ma43c6p3u', NULL),
(14, 'donlyone7tm', '$2y$10$gHDCWQF/htKNojmiDPlqJeSUNqyK9.uuWR62YsyuoJZKO2pTL7wOy', 'donlyone7tm@gmail.com', 'student', NULL, 'Fredrex Salac', '../assets/img/profile/user_14_1762331311.jpg', 1, 'user', 'active', '2025-11-03 09:26:20', '2025-11-05 08:28:31', NULL, NULL),
(15, 'freddie', '$2y$10$XtOA7CAhXaB6HWFamfrjneAGpQPC040vU55uTM7mvG8au82v.e4h.', 'donlyone7tmbrazil@gmail.com', 'teacher', NULL, 'The Real Fred', NULL, 1, 'user', 'active', '2025-11-08 13:25:03', '2025-11-10 08:58:26', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_dismissed_announcements`
--

CREATE TABLE `user_dismissed_announcements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `dismissed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL,
  `theme` enum('light','dark','animated') DEFAULT 'light',
  `accent_color` varchar(20) DEFAULT NULL,
  `compact_mode` tinyint(1) DEFAULT 0,
  `language` varchar(8) DEFAULT 'en',
  `timezone` varchar(64) DEFAULT 'UTC',
  `notif_email` tinyint(1) DEFAULT 1,
  `notif_push` tinyint(1) DEFAULT 1,
  `notif_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notif_categories`)),
  `landing_section` varchar(40) DEFAULT 'announcements',
  `show_tutorials` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(72, 13, 'require_strong_password', '1', '2025-11-01 13:17:48', '2025-11-01 13:17:48'),
(73, 13, 'enable_activity_logging', '1', '2025-11-01 13:17:48', '2025-11-01 13:17:48'),
(74, 13, 'max_login_attempts', '10', '2025-11-01 13:17:48', '2025-11-01 13:17:48'),
(75, 13, 'session_timeout', '30', '2025-11-08 07:44:33', '2025-11-08 12:58:47'),
(76, 13, 'theme_mode', 'light', '2025-11-08 07:44:33', '2025-11-08 12:58:47');

-- --------------------------------------------------------

--
-- Structure for view `form_statistics`
--
DROP TABLE IF EXISTS `form_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `form_statistics`  AS SELECT `cf`.`form_id` AS `form_id`, `cf`.`title` AS `title`, `cf`.`created_by` AS `created_by`, `cf`.`visibility` AS `visibility`, `cf`.`is_active` AS `is_active`, `cf`.`created_at` AS `created_at`, count(distinct `fr`.`response_id`) AS `total_responses`, count(distinct case when `fr`.`submitted_at` >= current_timestamp() - interval 7 day then `fr`.`response_id` end) AS `responses_this_week`, count(distinct case when `fr`.`submitted_at` >= current_timestamp() - interval 30 day then `fr`.`response_id` end) AS `responses_this_month`, avg(case when `fq`.`question_type` = 'rating_stars' then `fa`.`answer_value` end) AS `avg_rating` FROM (((`custom_forms` `cf` left join `form_responses` `fr` on(`cf`.`form_id` = `fr`.`form_id`)) left join `form_questions` `fq` on(`cf`.`form_id` = `fq`.`form_id`)) left join `form_answers` `fa` on(`fq`.`question_id` = `fa`.`question_id` and `fr`.`response_id` = `fa`.`response_id`)) GROUP BY `cf`.`form_id`, `cf`.`title`, `cf`.`created_by`, `cf`.`visibility`, `cf`.`is_active`, `cf`.`created_at` ;

-- --------------------------------------------------------

--
-- Structure for view `question_analytics`
--
DROP TABLE IF EXISTS `question_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `question_analytics`  AS SELECT `fq`.`question_id` AS `question_id`, `fq`.`form_id` AS `form_id`, `fq`.`question_text` AS `question_text`, `fq`.`question_type` AS `question_type`, count(`fa`.`answer_id`) AS `total_answers`, avg(case when `fq`.`question_type` in ('rating_stars','rating_scale','slider') then `fa`.`answer_value` end) AS `avg_numeric_value`, min(case when `fq`.`question_type` in ('rating_stars','rating_scale','slider') then `fa`.`answer_value` end) AS `min_numeric_value`, max(case when `fq`.`question_type` in ('rating_stars','rating_scale','slider') then `fa`.`answer_value` end) AS `max_numeric_value` FROM (`form_questions` `fq` left join `form_answers` `fa` on(`fq`.`question_id` = `fa`.`question_id`)) GROUP BY `fq`.`question_id`, `fq`.`form_id`, `fq`.`question_text`, `fq`.`question_type` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_logs_admin_fk` (`admin_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `custom_forms`
--
ALTER TABLE `custom_forms`
  ADD PRIMARY KEY (`form_id`),
  ADD UNIQUE KEY `form_code` (`form_code`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_form_code` (`form_code`),
  ADD KEY `idx_visibility` (`visibility`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_forms_created_by_active` (`created_by`,`is_active`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_action` (`email`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_oauth_config`
--
ALTER TABLE `email_oauth_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_smtp_config`
--
ALTER TABLE `email_smtp_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback_categories`
--
ALTER TABLE `feedback_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `feedback_limits`
--
ALTER TABLE `feedback_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_limits_admin` (`admin_id`);

--
-- Indexes for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_feedback_status` (`status`),
  ADD KEY `idx_feedback_category` (`category`),
  ADD KEY `idx_feedback_date` (`created_at`),
  ADD KEY `idx_frontend_user` (`frontend_user_id`);

--
-- Indexes for table `form_analytics`
--
ALTER TABLE `form_analytics`
  ADD PRIMARY KEY (`analytics_id`),
  ADD UNIQUE KEY `unique_form_analytics` (`form_id`);

--
-- Indexes for table `form_answers`
--
ALTER TABLE `form_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `unique_response_question` (`response_id`,`question_id`),
  ADD KEY `idx_response_id` (`response_id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `idx_answers_question_value` (`question_id`,`answer_value`);

--
-- Indexes for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_question_order` (`question_order`),
  ADD KEY `idx_question_type` (`question_type`);

--
-- Indexes for table `form_responses`
--
ALTER TABLE `form_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_respondent_type` (`respondent_type`),
  ADD KEY `idx_responses_form_submitted` (`form_id`,`submitted_at`);

--
-- Indexes for table `google_oauth_clients`
--
ALTER TABLE `google_oauth_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marketing`
--
ALTER TABLE `marketing`
  ADD PRIMARY KEY (`marketing_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_used` (`used`);

--
-- Indexes for table `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_created` (`email`,`created_at`),
  ADD KEY `idx_ip_created` (`ip_address`,`created_at`),
  ADD KEY `idx_success_created` (`success`,`created_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_reset_code` (`reset_code`),
  ADD KEY `idx_email_expires` (`email`,`expires_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_used` (`expires_at`,`is_used`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_admin` (`admin_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `user_dismissed_announcements`
--
ALTER TABLE `user_dismissed_announcements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_form` (`user_id`,`form_id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `custom_forms`
--
ALTER TABLE `custom_forms`
  MODIFY `form_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `email_oauth_config`
--
ALTER TABLE `email_oauth_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_smtp_config`
--
ALTER TABLE `email_smtp_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback_categories`
--
ALTER TABLE `feedback_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `feedback_limits`
--
ALTER TABLE `feedback_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_analytics`
--
ALTER TABLE `form_analytics`
  MODIFY `analytics_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `form_questions`
--
ALTER TABLE `form_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `form_responses`
--
ALTER TABLE `form_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `google_oauth_clients`
--
ALTER TABLE `google_oauth_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketing`
--
ALTER TABLE `marketing`
  MODIFY `marketing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_attempts`
--
ALTER TABLE `password_reset_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_dismissed_announcements`
--
ALTER TABLE `user_dismissed_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_forms`
--
ALTER TABLE `custom_forms`
  ADD CONSTRAINT `fk_custom_forms_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_limits`
--
ALTER TABLE `feedback_limits`
  ADD CONSTRAINT `fk_feedback_limits_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  ADD CONSTRAINT `feedback_responses_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `feedback_submissions` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_responses_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`frontend_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `form_analytics`
--
ALTER TABLE `form_analytics`
  ADD CONSTRAINT `fk_form_analytics_form` FOREIGN KEY (`form_id`) REFERENCES `custom_forms` (`form_id`) ON DELETE CASCADE;

--
-- Constraints for table `form_answers`
--
ALTER TABLE `form_answers`
  ADD CONSTRAINT `fk_form_answers_question` FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_form_answers_response` FOREIGN KEY (`response_id`) REFERENCES `form_responses` (`response_id`) ON DELETE CASCADE;

--
-- Constraints for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD CONSTRAINT `fk_form_questions_form` FOREIGN KEY (`form_id`) REFERENCES `custom_forms` (`form_id`) ON DELETE CASCADE;

--
-- Constraints for table `form_responses`
--
ALTER TABLE `form_responses`
  ADD CONSTRAINT `fk_form_responses_form` FOREIGN KEY (`form_id`) REFERENCES `custom_forms` (`form_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_form_responses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `marketing`
--
ALTER TABLE `marketing`
  ADD CONSTRAINT `marketing_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user_new` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_dismissed_announcements`
--
ALTER TABLE `user_dismissed_announcements`
  ADD CONSTRAINT `fk_dismissed_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_dismissed_announcements_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `custom_forms` (`form_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `fk_user_notifications` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `custom_forms` (`form_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
