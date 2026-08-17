-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 10:18 AM
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
-- Database: `smoke_n_grill`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:2;', 1781717559),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1781717559;', 1781717559),
('smoke-n-grill-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:3;', 1786952602),
('smoke-n-grill-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1786952602;', 1786952602);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Grilled Favorites', 'grilled-favorites', NULL, 0, 1, '2026-06-17 09:30:50', '2026-06-17 09:30:50'),
(2, 'Burgers', 'burgers', NULL, 1, 1, '2026-06-17 09:30:50', '2026-06-17 09:30:50'),
(3, 'Seafood', 'seafood', NULL, 2, 1, '2026-06-17 09:30:50', '2026-06-17 09:30:50'),
(4, 'Drinks', 'drinks', NULL, 3, 1, '2026-06-17 09:30:50', '2026-06-17 09:30:50'),
(5, 'Sizzling', 'sizzling', NULL, 4, 1, '2026-08-16 23:48:36', '2026-08-16 23:48:36'),
(6, 'Sides', 'sides', NULL, 5, 1, '2026-08-16 23:59:09', '2026-08-16 23:59:09'),
(7, 'Beverages', 'beverages', NULL, 6, 1, '2026-08-17 00:02:38', '2026-08-17 00:02:38'),
(8, 'Beers', 'beers', NULL, 7, 1, '2026-08-17 00:06:42', '2026-08-17 00:06:42');

-- --------------------------------------------------------

--
-- Table structure for table `dining_tables`
--

CREATE TABLE `dining_tables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `table_group_id` bigint(20) UNSIGNED NOT NULL,
  `table_number` varchar(255) NOT NULL,
  `chair_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `sit_code` varchar(255) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `map_x` decimal(5,2) DEFAULT NULL,
  `map_y` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dining_tables`
--

INSERT INTO `dining_tables` (`id`, `table_group_id`, `table_number`, `chair_count`, `sit_code`, `sort_order`, `map_x`, `map_y`, `created_at`, `updated_at`) VALUES
(2, 3, 'T1', 4, 'ground-t1', 0, 13.25, 14.03, '2026-08-16 23:20:03', '2026-08-16 23:20:25'),
(3, 3, 'T2', 4, 'ground-t2', 0, 46.46, 13.59, '2026-08-16 23:20:18', '2026-08-16 23:20:28');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_codes`
--

CREATE TABLE `email_verification_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verification_codes`
--

INSERT INTO `email_verification_codes` (`id`, `user_id`, `code`, `expires_at`, `verified_at`, `created_at`, `updated_at`) VALUES
(2, 5, '839929', '2026-08-16 23:18:08', NULL, '2026-08-16 23:08:08', '2026-08-16 23:08:08');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_level` int(10) UNSIGNED DEFAULT NULL,
  `reorder_level` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `image`, `is_available`, `stock_level`, `reorder_level`, `sort_order`, `created_at`, `updated_at`) VALUES
(15, 1, 'Grilled Balidbid', 'pork-bbq', NULL, 290.00, 'images/menu/96880911-895e-4d02-ad2a-8cb8beccf87c.webp', 1, NULL, NULL, 0, '2026-08-16 22:51:24', '2026-08-16 23:54:18'),
(25, 5, 'Sizzling Pork Sisig', 'sizzling-pork-sisig-d6bI', NULL, 260.00, 'images/menu/4f3e103a-8401-461b-8c21-4cc5746e0581.jpg', 1, NULL, NULL, 0, '2026-08-16 23:12:26', '2026-08-16 23:48:55'),
(26, 5, 'Sizzling Tuna Sisig', 'sizzling-tuna-sisig-Cl3c', NULL, 300.00, 'images/menu/b51f14fa-41a4-4853-b488-18f45f294791.jpg', 1, NULL, NULL, 0, '2026-08-16 23:50:35', '2026-08-16 23:52:22'),
(27, 5, 'Sizzling Tanguigue', 'sizzling-tanguigue-DALA', NULL, 300.00, 'images/menu/cb05f672-70c8-4451-89cc-ffc9caeb8ec3.jpg', 1, NULL, NULL, 0, '2026-08-16 23:50:49', '2026-08-16 23:52:40'),
(28, 5, 'Sizzling Salmon', 'sizzling-salmon-VVvw', NULL, 580.00, 'images/menu/bb6bc4e8-610b-429e-9636-ec6a417f3ad3.jpg', 1, NULL, NULL, 0, '2026-08-16 23:51:15', '2026-08-16 23:53:11'),
(29, 5, 'Sizzling Blue Marlin', 'sizzling-blue-marlin-DDRN', NULL, 300.00, 'images/menu/a73838d7-11e0-44e4-a28b-4489ac488a75.jpg', 1, NULL, NULL, 0, '2026-08-16 23:51:41', '2026-08-16 23:53:28'),
(30, 1, 'Grilled Tanguigue', 'grilled-tanguigue-8k1s', NULL, 290.00, 'images/menu/37084ec4-2da1-44b3-ada7-a6e8b947faf1.jpg', 1, NULL, NULL, 0, '2026-08-16 23:55:51', '2026-08-16 23:55:51'),
(31, 1, 'Grilled Blue Marlin', 'grilled-blue-marlin-ff23', NULL, 290.00, 'images/menu/d6c3bdec-2591-4f8f-a69a-2a0a51ee890d.jpg', 1, NULL, NULL, 0, '2026-08-16 23:56:24', '2026-08-16 23:56:24'),
(32, 1, 'Grilled Liempo', 'grilled-liempo-Dt4G', NULL, 270.00, 'images/menu/eafed148-ee2f-4406-a112-99e41b74ebca.jpg', 1, NULL, NULL, 0, '2026-08-16 23:56:49', '2026-08-16 23:56:49'),
(33, 1, 'Pork Barbeque (3 Sticks)', 'pork-barbeque-3-sticks-ldCp', NULL, 150.00, 'images/menu/e9d9f5e3-c700-4511-8e4b-d402e6771853.jpg', 1, NULL, NULL, 0, '2026-08-16 23:58:25', '2026-08-16 23:58:25'),
(34, 6, 'Toast', 'toast-6DFu', NULL, 8.00, 'images/menu/79c04097-0091-4185-b6ea-83d74b22453d.jpg', 1, NULL, NULL, 0, '2026-08-16 23:59:33', '2026-08-16 23:59:33'),
(35, 6, 'Plain Rice', 'plain-rice-me05', 'kanin rice', 25.00, 'images/menu/c35576df-5e96-4fcf-a181-41ad02da7d16.webp', 1, NULL, NULL, 0, '2026-08-17 00:00:00', '2026-08-17 00:00:53'),
(36, 6, 'Garlic Rice', 'garlic-rice-MWHo', 'kanin rice', 30.00, 'images/menu/9536fcbe-e4c7-4921-be3f-6848e906fe44.jpg', 1, NULL, NULL, 0, '2026-08-17 00:00:37', '2026-08-17 00:00:37'),
(37, 6, 'Java Rice', 'java-rice-aVqf', NULL, 35.00, 'images/menu/97ba5b1e-e17d-4901-9f56-786c183638d3.jpg', 1, NULL, NULL, 0, '2026-08-17 00:01:31', '2026-08-17 00:01:31'),
(38, 6, 'Atsara', 'atsara-QLWT', NULL, 40.00, 'images/menu/d72fd037-fb66-4e82-b58a-ff4f97fb75fc.jpg', 1, NULL, NULL, 0, '2026-08-17 00:01:51', '2026-08-17 00:01:51'),
(39, 7, 'Hot Tea', 'hot-tea-uAtb', NULL, 50.00, 'images/menu/5db6d515-22fa-4d02-9c59-f066ff6bae08.png', 1, NULL, NULL, 0, '2026-08-17 00:03:04', '2026-08-17 00:03:04'),
(40, 7, 'Instant Coffee', 'instant-coffee-A1Yi', NULL, 30.00, 'images/menu/b94c6f33-aced-48b8-ae19-5d6eeca79a47.jpg', 1, NULL, NULL, 0, '2026-08-17 00:03:44', '2026-08-17 00:06:17'),
(41, 7, 'Pineapple Juice', 'pineapple-juice-zROd', NULL, 70.00, 'images/menu/b8bc505d-e79b-4c83-acc5-e5f6bb3e6373.jpg', 1, NULL, NULL, 0, '2026-08-17 00:04:16', '2026-08-17 00:04:16'),
(42, 7, 'Mango Juice', 'mango-juice-EVwv', NULL, 70.00, 'images/menu/72219f77-bd19-4981-bd15-0989f017ff82.jpg', 1, NULL, NULL, 0, '2026-08-17 00:04:45', '2026-08-17 00:04:45'),
(43, 7, 'Brewed Coffee', 'brewed-coffee-7ysb', NULL, 60.00, 'images/menu/7b964641-4e26-402e-bd54-fba8b1f4e48c.jpg', 1, NULL, NULL, 0, '2026-08-17 00:05:59', '2026-08-17 00:05:59'),
(44, 8, 'Smirnoff', 'smirnoff-cO8e', NULL, 85.00, 'images/menu/55435b25-10b2-49a8-9330-01637fe9a450.jpg', 1, NULL, NULL, 0, '2026-08-17 00:07:10', '2026-08-17 00:07:10'),
(45, 8, 'San Mig Light', 'san-mig-light-6CZU', NULL, 85.00, 'images/menu/ea601adf-576d-4ffd-8289-49c5cbf8ff83.jpg', 1, NULL, NULL, 0, '2026-08-17 00:07:31', '2026-08-17 00:07:31'),
(46, 8, 'San Mig Pilsen', 'san-mig-pilsen-GdIK', NULL, 85.00, 'images/menu/4119cd41-ccf8-4ba9-b6b2-9a9ebf433140.jpg', 1, NULL, NULL, 0, '2026-08-17 00:07:56', '2026-08-17 00:07:56'),
(47, 8, 'San Mig Red Horse', 'san-mig-red-horse-7WF9', NULL, 85.00, 'images/menu/1db81f8a-8b71-427c-a39c-34372bd2a64e.jpeg', 1, NULL, NULL, 0, '2026-08-17 00:08:33', '2026-08-17 00:08:33'),
(48, 8, 'San Mig Apple', 'san-mig-apple-J4jY', NULL, 85.00, 'images/menu/a3432453-2c18-441b-b951-e45c431e905c.png', 1, NULL, NULL, 0, '2026-08-17 00:08:58', '2026-08-17 00:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_05_15_000001_add_personal_fields_to_users_table', 1),
(5, '2026_05_15_000002_create_sessions_table', 1),
(6, '2026_05_15_100000_create_modules_table', 1),
(7, '2026_05_15_120000_create_module_role_table', 1),
(8, '2026_05_15_130000_create_page_role_table', 1),
(9, '2026_05_16_000001_create_pages_table', 1),
(10, '2026_05_16_000001_ensure_sessions_table_exists', 1),
(11, '2026_05_16_120000_add_sidebar_expand_children_to_modules_table', 1),
(12, '2026_05_17_000001_create_roles_table', 1),
(13, '2026_05_17_000002_add_role_id_to_users_table', 1),
(14, '2026_05_17_000003_seed_default_module_and_page_roles_where_empty', 1),
(15, '2026_06_18_000001_create_restaurant_tables', 2),
(16, '2026_06_29_000001_ensure_categories_table_exists', 3),
(17, '2026_06_29_000002_seed_smoke_n_grill_module', 4),
(18, '2026_06_29_000003_fix_smoke_n_grill_page_icons', 5),
(19, '2026_06_29_000004_set_pos_page_icon', 6),
(20, '2026_06_29_000005_simplify_order_statuses_for_restaurant', 7),
(21, '2026_06_29_000006_create_dining_tables_schema', 8),
(22, '2026_06_29_000007_add_map_position_to_dining_tables', 9),
(23, '2026_06_29_000008_add_payment_proof_to_orders', 10),
(24, '2026_06_29_000009_add_payment_ocr_fields_to_orders', 11),
(25, '2026_07_02_000001_add_table_link_to_reservations', 12),
(26, '2026_07_02_000002_add_additional_chairs_to_reservations', 13),
(27, '2026_07_04_000001_add_deposit_fields_to_reservations_and_orders', 14),
(28, '2026_07_12_000001_add_stock_fields_to_menu_items', 15),
(29, '2026_08_14_000001_create_email_verification_codes_table', 16);

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `sequence` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sidebar_expand_children` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `name`, `alias`, `sequence`, `is_active`, `sidebar_expand_children`, `created_at`, `updated_at`) VALUES
(1, 'User Settings', 'user_settings', 1, 1, 1, '2026-06-17 09:22:16', '2026-06-17 09:22:25'),
(3, 'Smoke n\' Grill', 'smoke_n_grill', 0, 1, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `module_role`
--

CREATE TABLE `module_role` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `module_role`
--

INSERT INTO `module_role` (`id`, `module_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 3, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(2, 3, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(3, 3, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reservation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_number` varchar(30) NOT NULL,
  `delivery_address` text NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_amount_paid` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deposit_credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `reservation_id`, `customer_name`, `contact_number`, `delivery_address`, `payment_method`, `payment_proof`, `payment_reference`, `payment_amount_paid`, `subtotal`, `delivery_fee`, `deposit_credit`, `total`, `status`, `special_instructions`, `created_at`, `updated_at`) VALUES
(2, 'SG-20260629-D27690', 2, NULL, 'Walk-in Customer', 'N/A', 'Dine-in', 'cash', NULL, NULL, NULL, 1000.00, 0.00, 0.00, 1000.00, 'completed', NULL, '2026-06-28 20:43:09', '2026-06-28 20:43:55'),
(3, 'SG-20260629-169E83', NULL, NULL, 'user123 user123', '0123456789', 'Ground — Table 1', 'gcash', 'images/payment-proofs/c75a8a7c-329b-4833-9b55-0ef5c4b57db1.jpeg', NULL, NULL, 1000.00, 0.00, 0.00, 1000.00, 'pending', NULL, '2026-06-28 22:02:57', '2026-08-16 23:14:01'),
(4, 'SG-20260817-93A2E2', 5, 3, 'Joshua Moral', '09947976522', 'Ground — Table T1', 'cash', NULL, NULL, NULL, 747.00, 0.00, 500.00, 247.00, 'preparing', NULL, '2026-08-16 23:36:57', '2026-08-16 23:39:19');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `unit_price`, `quantity`, `line_total`, `special_instructions`, `created_at`, `updated_at`) VALUES
(2, 2, NULL, 'test1', 500.00, 2, 1000.00, NULL, '2026-06-28 20:43:09', '2026-06-28 20:43:09'),
(3, 3, NULL, 'test1', 500.00, 1, 500.00, NULL, '2026-06-28 22:02:57', '2026-06-28 22:02:57'),
(4, 3, NULL, 'test2', 500.00, 1, 500.00, NULL, '2026-06-28 22:02:57', '2026-06-28 22:02:57'),
(5, 4, 15, 'Pork BBQ', 199.00, 2, 398.00, NULL, '2026-08-16 23:36:57', '2026-08-16 23:36:57'),
(6, 4, NULL, 'Beef Steak', 349.00, 1, 349.00, NULL, '2026-08-16 23:36:57', '2026-08-16 23:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_alias` varchar(255) NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `sequence_number` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `page_name`, `page_alias`, `module_id`, `sequence_number`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Roles Management', 'roles_management', 1, 1, NULL, 1, '2026-06-17 09:22:21', '2026-06-17 09:22:21'),
(2, 'Users Lists', 'users_lists', 1, 2, NULL, 1, '2026-06-17 09:22:21', '2026-06-17 09:22:21'),
(3, 'Menu Items', 'menu_items', 3, 1, 'fa-solid fa-utensils', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(4, 'Orders', 'orders', 3, 2, 'fa-solid fa-receipt', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(5, 'Customers', 'customers', 3, 3, 'fa-solid fa-users', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(6, 'Reservations', 'reservations', 3, 4, 'fa-solid fa-calendar-check', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(7, 'Sales Reports', 'sales_reports', 3, 5, 'fa-solid fa-chart-line', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(8, 'Restaurant Info', 'restaurant_info', 3, 6, 'fa-solid fa-store', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(9, 'View Website', 'view_website', 3, 7, 'fa-solid fa-globe', 1, '2026-06-28 20:15:21', '2026-06-28 20:24:00'),
(10, 'POS', 'pos', 3, 10, 'fa-solid fa-cash-register', 1, '2026-06-28 20:22:32', '2026-06-28 20:26:01'),
(11, 'Tables Configurations', 'tables_configurations', 3, 11, 'fa-solid fa-gear', 1, '2026-06-28 20:45:18', '2026-06-28 20:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `page_role`
--

CREATE TABLE `page_role` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `page_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_role`
--

INSERT INTO `page_role` (`id`, `page_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 3, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(2, 3, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(3, 3, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(4, 4, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(5, 4, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(6, 4, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(7, 5, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(8, 5, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(9, 5, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(10, 6, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(11, 6, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(12, 6, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(13, 7, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(14, 7, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(15, 7, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(16, 8, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(17, 8, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(18, 8, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(19, 9, 2, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(20, 9, 1, '2026-06-28 20:15:21', '2026-06-28 20:15:21'),
(21, 9, 3, '2026-06-28 20:15:21', '2026-06-28 20:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dining_table_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_number` varchar(30) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `party_size` int(10) UNSIGNED NOT NULL,
  `additional_chairs` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `table_deposit_amount` decimal(10,2) NOT NULL DEFAULT 500.00,
  `preorder_deposit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deposit_total` decimal(10,2) NOT NULL DEFAULT 500.00,
  `deposit_payment_method` varchar(30) DEFAULT NULL,
  `deposit_payment_proof` varchar(255) DEFAULT NULL,
  `deposit_payment_reference` varchar(255) DEFAULT NULL,
  `deposit_payment_amount_paid` decimal(10,2) DEFAULT NULL,
  `deposit_status` varchar(30) NOT NULL DEFAULT 'paid',
  `deposit_paid_at` timestamp NULL DEFAULT NULL,
  `applied_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reserved_at` datetime NOT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 120,
  `notes` text DEFAULT NULL,
  `preorder_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preorder_items`)),
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `dining_table_id`, `customer_name`, `contact_number`, `email`, `party_size`, `additional_chairs`, `table_deposit_amount`, `preorder_deposit_amount`, `deposit_total`, `deposit_payment_method`, `deposit_payment_proof`, `deposit_payment_reference`, `deposit_payment_amount_paid`, `deposit_status`, `deposit_paid_at`, `applied_order_id`, `reserved_at`, `duration_minutes`, `notes`, `preorder_items`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'user123 user123', '09123456789', 'jameskarlborras01@gmail.com', 2, 0, 500.00, 0.00, 500.00, NULL, NULL, NULL, NULL, 'paid', NULL, NULL, '2026-07-02 21:54:00', 120, NULL, NULL, 'cancelled', '2026-07-02 05:55:09', '2026-07-24 21:05:22'),
(2, NULL, NULL, 'user123 user123', '09123456789', 'user@gmail.com', 8, 4, 500.00, 500.00, 1000.00, 'cash', NULL, NULL, NULL, 'pending_at_restaurant', NULL, NULL, '2026-07-12 14:30:00', 120, '2× test1 — ₱500.00 each (₱1,000.00)\r\n2× test2 — ₱500.00 each (₱1,000.00)\r\n\r\nEstimated total: ₱2,000.00', NULL, 'pending', '2026-07-11 21:35:07', '2026-07-11 21:35:07'),
(3, 5, 2, 'Joshua Moral', '09947976522', 'joshuajanmoral7@gmail.com', 1, 0, 500.00, 0.00, 500.00, 'gcash', 'images/payment-proofs/138c2034-726d-4be3-88d3-f4e787ebdee4.jpg', NULL, NULL, 'applied', '2026-08-16 23:28:14', 4, '2026-08-17 15:22:00', 120, NULL, NULL, 'pending', '2026-08-16 23:28:14', '2026-08-16 23:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_settings`
--

CREATE TABLE `restaurant_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'Smoke n'' Grill',
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 50.00,
  `opening_hours` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restaurant_settings`
--

INSERT INTO `restaurant_settings` (`id`, `name`, `description`, `address`, `phone`, `email`, `delivery_fee`, `opening_hours`, `created_at`, `updated_at`) VALUES
(1, 'Smoke n\' Grill', 'Enjoy premium grilled dishes and unforgettable dining experiences.', '123 Grill Street, Manila, Philippines', '+63 912 345 6789', 'hello@smokengrill.ph', 50.00, 'Mon–Sun: 2:00 PM – 12:00 AM', '2026-06-17 09:30:50', '2026-07-11 21:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `slug`, `name`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'Superadmin', '2026-06-17 09:18:19', '2026-06-17 09:18:19'),
(2, 'admin', 'Admin', '2026-06-17 09:18:19', '2026-06-17 09:18:19'),
(3, 'user', 'Users', '2026-06-17 09:18:19', '2026-06-17 09:18:19'),
(4, 'cashier', 'Cashier', '2026-06-28 20:35:57', '2026-06-28 20:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('4OyvQbYuOEtMTIL5sWKD0JC9Xdxb1UI8MweAhMo5', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidFVCd1lRYzE5YjFibGduT0lmY3NNNURiUVQ3THk1N2dBQVNhODV5YyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9tb2R1bGVzL3Ntb2tlX25fZ3JpbGwvbWVudV9pdGVtcyI7czo1OiJyb3V0ZSI7czozNToicGFnZS5zbW9rZV9uX2dyaWxsLm1lbnVfaXRlbXMuaW5kZXgiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1786954138),
('R1l7qYYpyMv9j8v8J4uw2QnFWcFN8u0P2P66106D', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWG1WNzJOSWdhdXZ0dFBzMDhPUDZHNmtNM1dEcmY1Q3VKeFJKZDB0TyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo3OiJsYW5kaW5nIjt9fQ==', 1786952497);

-- --------------------------------------------------------

--
-- Table structure for table `table_groups`
--

CREATE TABLE `table_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `map_image` varchar(255) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `table_groups`
--

INSERT INTO `table_groups` (`id`, `name`, `map_image`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Ground', 'images/table-maps/b8703e01-7b0f-42c1-8932-eafc25e73685.png', 0, '2026-08-16 23:19:17', '2026-08-16 23:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `table_occupancies`
--

CREATE TABLE `table_occupancies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dining_table_id` bigint(20) UNSIGNED NOT NULL,
  `occupied_chairs` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `table_occupancies`
--

INSERT INTO `table_occupancies` (`id`, `dining_table_id`, `occupied_chairs`, `status`, `started_at`, `ended_at`, `created_at`, `updated_at`) VALUES
(3, 2, 1, 'occupied', '2026-08-16 23:35:41', NULL, '2026-08-16 23:35:41', '2026-08-16 23:35:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone`, `date_of_birth`, `gender`, `name`, `username`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(2, 1, 'Admin', NULL, 'User', NULL, '09123456789', NULL, NULL, 'Admin User', 'admin123', 'admin@gmail.com', '2026-08-16 22:51:24', '$2y$12$q..bP/9AFNS.fmrl3i5NOuZkA17kh0ki/n7Ieac9Yxn8IBRGrsmdO', NULL, '2026-06-28 19:53:33', '2026-08-16 22:51:24'),
(5, 3, 'Joshua', NULL, 'Moral', NULL, '09947976522', NULL, NULL, 'Joshua Moral', 'josh', 'joshuajanmoral7@gmail.com', NULL, '$2y$12$SqPcbYfrHKuFG43fisyqB.DyAzvsc7N7GkLfiXpvkROzOG95qvQY6', NULL, '2026-08-16 23:08:08', '2026-08-16 23:08:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `dining_tables`
--
ALTER TABLE `dining_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dining_tables_table_group_id_table_number_unique` (`table_group_id`,`table_number`),
  ADD UNIQUE KEY `dining_tables_sit_code_unique` (`sit_code`);

--
-- Indexes for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_verification_codes_user_id_code_index` (`user_id`,`code`),
  ADD KEY `email_verification_codes_expires_at_index` (`expires_at`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_items_slug_unique` (`slug`),
  ADD KEY `menu_items_category_id_foreign` (`category_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modules_alias_unique` (`alias`);

--
-- Indexes for table `module_role`
--
ALTER TABLE `module_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_role_module_id_role_id_unique` (`module_id`,`role_id`),
  ADD KEY `module_role_role_id_index` (`role_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_user_id_foreign` (`user_id`),
  ADD KEY `orders_reservation_id_foreign` (`reservation_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_menu_item_id_foreign` (`menu_item_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pages_module_id_page_alias_unique` (`module_id`,`page_alias`);

--
-- Indexes for table `page_role`
--
ALTER TABLE `page_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_role_page_id_role_id_unique` (`page_id`,`role_id`),
  ADD KEY `page_role_page_id_index` (`page_id`),
  ADD KEY `page_role_role_id_index` (`role_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservations_user_id_foreign` (`user_id`),
  ADD KEY `reservations_dining_table_id_foreign` (`dining_table_id`),
  ADD KEY `reservations_applied_order_id_foreign` (`applied_order_id`);

--
-- Indexes for table `restaurant_settings`
--
ALTER TABLE `restaurant_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `table_groups`
--
ALTER TABLE `table_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `table_occupancies`
--
ALTER TABLE `table_occupancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_occupancies_dining_table_id_foreign` (`dining_table_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_role_id_index` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `dining_tables`
--
ALTER TABLE `dining_tables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `module_role`
--
ALTER TABLE `module_role`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `page_role`
--
ALTER TABLE `page_role`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restaurant_settings`
--
ALTER TABLE `restaurant_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `table_groups`
--
ALTER TABLE `table_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `table_occupancies`
--
ALTER TABLE `table_occupancies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dining_tables`
--
ALTER TABLE `dining_tables`
  ADD CONSTRAINT `dining_tables_table_group_id_foreign` FOREIGN KEY (`table_group_id`) REFERENCES `table_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD CONSTRAINT `email_verification_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `module_role`
--
ALTER TABLE `module_role`
  ADD CONSTRAINT `module_role_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_applied_order_id_foreign` FOREIGN KEY (`applied_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_dining_table_id_foreign` FOREIGN KEY (`dining_table_id`) REFERENCES `dining_tables` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `table_occupancies`
--
ALTER TABLE `table_occupancies`
  ADD CONSTRAINT `table_occupancies_dining_table_id_foreign` FOREIGN KEY (`dining_table_id`) REFERENCES `dining_tables` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
