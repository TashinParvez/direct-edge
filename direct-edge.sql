-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 10:03 PM
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
-- Database: `direct-edge`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `created_at`) VALUES
(1, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 18:48:04'),
(2, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 18:48:09'),
(3, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 18:49:01'),
(4, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 18:49:12'),
(5, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Expired. Notes: ', '2025-10-15 18:50:00'),
(6, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Expired. Notes: ', '2025-10-15 18:50:04'),
(7, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Terminated. Notes: ', '2025-10-15 18:50:13'),
(8, 71, 'Agreement Status Update', 'Updated agreement ID 4 to status: Expired. Notes: ', '2025-10-15 19:04:05'),
(9, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Pending. Notes: ', '2025-10-15 19:04:18'),
(10, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Pending. Notes: ', '2025-10-15 19:05:31'),
(11, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 19:08:29'),
(12, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Active. Notes: ', '2025-10-15 19:08:43'),
(13, 71, 'Agreement Status Update', 'Updated agreement ID 5 to status: Expired. Notes: ', '2025-10-15 19:09:52');

-- --------------------------------------------------------

--
-- Table structure for table `agent_assigned_cities`
--

CREATE TABLE `agent_assigned_cities` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agent_assigned_cities`
--

INSERT INTO `agent_assigned_cities` (`id`, `agent_id`, `city_id`, `assigned_at`) VALUES
(1, 5, 10, '2025-09-16 17:31:35'),
(2, 4, 2, '2025-09-23 17:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `agent_farmer_agreements`
--

CREATE TABLE `agent_farmer_agreements` (
  `agreement_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `agreement_reference` varchar(100) NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `payment_terms` text NOT NULL,
  `exclusive_rights` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `agreement_status` enum('Pending','Active','Expired','Terminated') NOT NULL DEFAULT 'Pending',
  `agent_signature_url` varchar(255) DEFAULT NULL,
  `farmer_signature_url` varchar(255) DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agent_farmer_agreements`
--

INSERT INTO `agent_farmer_agreements` (`agreement_id`, `agent_id`, `farmer_id`, `agreement_reference`, `commission_percentage`, `payment_terms`, `exclusive_rights`, `start_date`, `end_date`, `agreement_status`, `agent_signature_url`, `farmer_signature_url`, `terms_accepted_at`, `created_at`, `updated_at`) VALUES
(4, 70, 45, 'AGR-2025-0070-0045-1760551715', 9.97, 'Payment will be processed within 7 days of crop delivery. Commission will be deducted before final payment to farmer. Payment method: Bank transfer or Mobile Banking as per farmer\'s preference.', 1, '2025-10-15', '2026-10-17', 'Active', 'uploads/signatures/agent_70_1760551715.png', 'uploads/signatures/farmer_45_1760551715.png', '2025-10-15 18:08:35', '2025-10-15 18:08:35', '2025-10-15 19:24:13'),
(5, 70, 44, 'AGR-2025-0070-0044-1760552686', 10.00, 'Payment will be processed within 7 days of crop delivery. Commission will be deducted before final payment to farmer. Payment method: Bank transfer or Mobile Banking as per farmer\'s preference.', 1, '2025-10-15', '2026-10-21', 'Active', 'uploads/signatures/agent_70_1760552686.png', 'uploads/signatures/farmer_44_1760552686.png', '2025-10-15 18:24:46', '2025-10-15 18:24:46', '2025-10-15 19:25:38');

-- --------------------------------------------------------

--
-- Table structure for table `agent_info`
--

CREATE TABLE `agent_info` (
  `agent_info_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `nid_number` varchar(50) NOT NULL,
  `region` varchar(100) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `upazila` varchar(100) DEFAULT NULL,
  `coverage_area_km` int(11) DEFAULT 20,
  `experience_years` int(11) DEFAULT 0,
  `crops_expertise` text NOT NULL,
  `vehicle_types` varchar(200) DEFAULT NULL,
  `warehouse_capacity` varchar(200) DEFAULT NULL,
  `reference_name` varchar(150) DEFAULT NULL,
  `reference_phone` varchar(40) DEFAULT NULL,
  `statement` text DEFAULT NULL,
  `id_doc_url` varchar(255) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `trade_license_url` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agent_info`
--

INSERT INTO `agent_info` (`agent_info_id`, `agent_id`, `nid_number`, `region`, `district`, `upazila`, `coverage_area_km`, `experience_years`, `crops_expertise`, `vehicle_types`, `warehouse_capacity`, `reference_name`, `reference_phone`, `statement`, `id_doc_url`, `photo_url`, `trade_license_url`, `status`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 46, '1111111111111', 'Dah\\j', 'sdfghj', 'fg', 20, 2, 'xdfgbn', 'vb', 'cvf', '', '', 'dfg', NULL, NULL, NULL, 'Pending', NULL, NULL, '2025-10-07 20:52:00', '2025-10-15 17:09:00'),
(2, 49, '46546165465131564', 'Dhaka', 'Gazipur', 'Kapasia', 20, 10, 'Rice', 'Van', '500', 'Mahbub', '01744177620', 'Nothing to', NULL, NULL, NULL, 'Pending', NULL, NULL, '2025-10-08 07:54:21', '2025-10-08 07:54:21'),
(3, 58, '542353425234', 'Dhaka', 'Gazipur', 'Kapasia', 20, 0, 'xdfgbn', 'vb', 'cvf', 'Mahbub', '01744177620', '', 'uploads/agent_docs/id_1760291535_68ebeacfa3492.png', NULL, NULL, 'Pending', NULL, NULL, '2025-10-12 17:52:15', '2025-10-12 18:39:13'),
(4, 64, '542353425234', 'Dhaka', 'Gazipur', 'fg', 20, 0, 'xdfgbn', 'vb', '500', 'Mahbub', '01744177620', 'dfg', NULL, NULL, NULL, 'Approved', NULL, NULL, '2025-10-13 06:21:30', '2025-10-13 06:23:29'),
(5, 45, '2222234234234324', 'Dhaka', 'Gazipur', 'Kapasi', 20, 3, 'Rice', 'Pickup', '500', 'Md. Mahbubur Rahman', '', 'asdfas', NULL, NULL, NULL, 'Approved', NULL, NULL, '2025-10-15 17:15:54', '2025-10-15 17:26:53'),
(6, 45, '2222234234234324', 'Dhaka', 'Gazipur', 'Kapasi', 20, 2, 'Rice', 'Pickup', '500', 'Md. Mahbubur Rahman', '01744177620', 'asdfsa', NULL, NULL, NULL, 'Approved', NULL, NULL, '2025-10-15 17:16:47', '2025-10-15 17:26:59'),
(7, 70, '2222234234234324', 'Rajshahi', 'Pabna', 'Bera', 20, 4, 'Rice', 'Pickup', '500', 'Md. Mahbubur Rahman', '01744177620', 'asdfas', NULL, NULL, NULL, 'Approved', NULL, NULL, '2025-10-15 17:37:03', '2025-10-15 17:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `billing_info`
--

CREATE TABLE `billing_info` (
  `billing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `shop_owener_name` varchar(150) NOT NULL,
  `billing_address` text NOT NULL,
  `shipping_address` text NOT NULL,
  `special_nstructions` text DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_info`
--

INSERT INTO `billing_info` (`billing_id`, `user_id`, `order_id`, `shop_owener_name`, `billing_address`, `shipping_address`, `special_nstructions`, `tax_id`, `created_at`) VALUES
(1, 48, 17, 'Mahbub', 'dfghj, Dhaka, Bangladesh 1211', 'dfghj, Dhaka, Bangladesh 1211', 'no', '1234', '2025-10-08 09:02:14'),
(3, 48, 19, 'Mahbub', 'dfghj, Dhaka, Bangladesh 1211', 'dfghj, Dhaka, Bangladesh 1211', 'no', '1234', '2025-10-08 09:02:58');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `city_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`city_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Dhaka', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(2, 'Chattogram', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(3, 'Khulna', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(4, 'Rajshahi', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(5, 'Sylhet', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(6, 'Barishal', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(7, 'Rangpur', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(8, 'Mymensingh', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(9, 'Comilla', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(10, 'Jessore', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(11, 'Cox\'s Bazar', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(12, 'Noakhali', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(13, 'Pabna', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(14, 'Tangail', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(15, 'Gazipur', '2025-09-16 17:30:45', '2025-09-16 17:30:45'),
(16, 'New York', '2025-09-23 17:52:20', '2025-09-23 17:52:20'),
(17, 'Los Angeles', '2025-09-23 17:52:20', '2025-09-23 17:52:20'),
(18, 'Chicago', '2025-09-23 17:52:20', '2025-09-23 17:52:20'),
(19, 'Houston', '2025-09-23 17:52:20', '2025-09-23 17:52:20');

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales_history`
--

CREATE TABLE `daily_sales_history` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `avg_selling_price` decimal(10,2) DEFAULT 0.00,
  `stock_level_start` int(11) DEFAULT 0,
  `stock_level_end` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_sales_history`
--

INSERT INTO `daily_sales_history` (`id`, `date`, `shop_id`, `product_id`, `quantity_sold`, `total_revenue`, `avg_selling_price`, `stock_level_start`, `stock_level_end`, `created_at`) VALUES
(1, '2025-09-19', 2, 7, 2, 120.00, 60.00, 0, 0, '2025-10-14 16:51:05'),
(2, '2025-09-21', 2, 8, 5, 75.00, 15.00, 0, 0, '2025-10-14 16:51:05'),
(3, '2025-09-22', 2, 6, 3, 240.00, 80.00, 0, 0, '2025-10-14 16:51:05'),
(4, '2025-09-23', 2, 6, 3, 180.00, 60.00, 0, 0, '2025-10-14 16:51:05'),
(5, '2025-09-23', 6, 1, 1, 1200.00, 1200.00, 0, 0, '2025-10-14 16:51:05'),
(6, '2025-09-23', 6, 2, 2, 1600.00, 800.00, 0, 0, '2025-10-14 16:51:05'),
(7, '2025-09-23', 6, 3, 5, 100.00, 20.00, 0, 0, '2025-10-14 16:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `demand_forecasts`
--

CREATE TABLE `demand_forecasts` (
  `id` int(11) NOT NULL,
  `forecast_date` date NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `predicted_demand` int(11) NOT NULL,
  `confidence_score` decimal(5,4) DEFAULT 0.0000,
  `model_used` varchar(50) NOT NULL,
  `forecast_period_days` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `demand_forecasts`
--

INSERT INTO `demand_forecasts` (`id`, `forecast_date`, `shop_id`, `product_id`, `predicted_demand`, `confidence_score`, `model_used`, `forecast_period_days`, `created_at`, `updated_at`) VALUES
(1, '2025-10-14', 6, 1, 1, 0.7313, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(2, '2025-10-15', 6, 1, 1, 0.8941, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(3, '2025-10-16', 6, 1, 1, 0.8559, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(4, '2025-10-17', 6, 1, 1, 0.9151, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(5, '2025-10-18', 6, 1, 1, 0.8841, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(6, '2025-10-19', 6, 1, 1, 0.7620, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(7, '2025-10-20', 6, 1, 1, 0.7217, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(8, '2025-10-21', 6, 1, 1, 0.8040, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(9, '2025-10-22', 6, 1, 1, 0.7307, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(10, '2025-10-23', 6, 1, 1, 0.8468, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(11, '2025-10-24', 6, 1, 1, 0.7131, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(12, '2025-10-25', 6, 1, 1, 0.8775, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(13, '2025-10-26', 6, 1, 1, 0.7039, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(14, '2025-10-27', 6, 1, 1, 0.9275, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(15, '2025-10-28', 6, 1, 1, 0.7363, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(16, '2025-10-29', 6, 1, 1, 0.8432, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(17, '2025-10-30', 6, 1, 1, 0.8447, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(18, '2025-10-31', 6, 1, 1, 0.9093, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(19, '2025-11-01', 6, 1, 1, 0.8753, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(20, '2025-11-02', 6, 1, 1, 0.8977, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(21, '2025-11-03', 6, 1, 1, 0.8774, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(22, '2025-11-04', 6, 1, 1, 0.7918, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(23, '2025-11-05', 6, 1, 1, 0.8479, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(24, '2025-11-06', 6, 1, 1, 0.9343, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(25, '2025-11-07', 6, 1, 1, 0.8203, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(26, '2025-11-08', 6, 1, 1, 0.8763, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(27, '2025-11-09', 6, 1, 1, 0.7182, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(28, '2025-11-10', 6, 1, 1, 0.7080, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(29, '2025-11-11', 6, 1, 1, 0.9378, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05'),
(30, '2025-11-12', 6, 1, 1, 0.8968, 'ARIMA_BASELINE', 30, '2025-10-14 16:51:05', '2025-10-14 16:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

CREATE TABLE `farmers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `nid_number` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `present_address` text DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `farmer_type` enum('Small','Medium','Large') DEFAULT 'Small',
  `crops_cultivated` text DEFAULT NULL,
  `land_size` decimal(10,2) DEFAULT NULL,
  `land_ownership` enum('Own Land','Leased Land') DEFAULT 'Own Land',
  `fertilizer_usage` text DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `mobile_banking_account` varchar(100) DEFAULT NULL,
  `training_received` text DEFAULT NULL,
  `avg_selling_price` varchar(200) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `agent_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`id`, `full_name`, `dob`, `nid_number`, `contact_number`, `present_address`, `profile_picture`, `farmer_type`, `crops_cultivated`, `land_size`, `land_ownership`, `fertilizer_usage`, `bank_account`, `mobile_banking_account`, `training_received`, `avg_selling_price`, `additional_notes`, `created_at`, `agent_id`) VALUES
(1, 'tashin parvez', '2025-10-10', '15415454121', '01954449226', 'Dakshin azampur, Dakshin khan, Dhaka 1230', 'uploads/1759343288_68dd72b82b080.jpg', 'Medium', 'sdfsdfsdf', 12.00, 'Own Land', '0', '15415454121', '15415454121', '', '', '', '2025-10-01 18:28:08', 5),
(2, 'Arif Hossain', NULL, NULL, '01720020001', 'Dhaka', 'assets/farmer-image/user_male_001.jpg', 'Small', 'Rice, Vegetables', 1.50, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 3),
(4, 'Hasan Mahmud', NULL, NULL, '01720020003', 'Khulna', 'assets/farmer-image/user_male_002.jpg', 'Small', 'Potato, Onion', 0.90, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 5),
(5, 'Fatema Begum', NULL, NULL, '01720020004', 'Rajshahi', 'assets/farmer-image/user_female_003.jpg', 'Large', 'Mango, Vegetables', 3.20, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 10),
(6, 'Rakibul Islam', NULL, NULL, '01720020005', 'Sylhet', 'assets/farmer-image/user_male_004.jpg', 'Medium', 'Tea, Vegetables', 1.80, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 15),
(7, 'Riya Sultana', NULL, NULL, '01720020006', 'Barishal', 'assets/farmer-image/user_female_004.jpg', 'Small', 'Rice, Eggplant', 0.75, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 16),
(8, 'Sabbir Ahmed', NULL, NULL, '01720020007', 'Rangpur', 'assets/farmer-image/user_male_005.jpg', 'Medium', 'Wheat, Mustard', 1.60, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 17),
(9, 'Sumaiya Khatun', NULL, NULL, '01720020008', 'Mymensingh', 'assets/farmer-image/user_female_005.jpg', 'Small', 'Spinach, Tomato', 0.65, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 18),
(10, 'Nazmul Karim', NULL, NULL, '01720020009', 'Comilla', 'assets/farmer-image/user_male_006.jpg', 'Medium', 'Rice, Jute', 1.90, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 19),
(11, 'Jannat Ara', NULL, NULL, '01720020010', 'Gazipur', 'assets/farmer-image/user_female_006.jpg', 'Small', 'Chili, Garlic', 0.80, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 20),
(12, 'Tanvir Alam', NULL, NULL, '01720020011', 'Noakhali', 'assets/farmer-image/user_male_007.jpg', 'Large', 'Rice, Pumpkin', 3.00, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 21),
(13, 'Anika Rahman', NULL, NULL, '01720020012', 'Pabna', 'assets/farmer-image/user_female_008.jpg', 'Medium', 'Cucumber, Gourd', 1.40, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 22),
(14, 'Farhan Chowdhury', NULL, NULL, '01720020013', 'Tangail', 'assets/farmer-image/user_male_008.jpg', 'Small', 'Okra, Beans', 0.85, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 23),
(15, 'Moumita Das', NULL, NULL, '01720020014', 'Jessore', 'assets/farmer-image/user_female_009.jpg', 'Medium', 'Cauliflower, Cabbage', 1.70, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 24),
(16, 'Imran Hossain', NULL, NULL, '01720020015', 'Bogura', 'assets/farmer-image/user_male_009.jpg', 'Small', 'Rice, Potato', 1.10, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 25),
(17, 'Lamia Hasan', NULL, NULL, '01720020016', 'Narayanganj', 'assets/farmer-image/user_female_010.jpg', 'Medium', 'Tomato, Capsicum', 1.55, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 26),
(18, 'Mahir Rahman', NULL, NULL, '01720020017', 'Dinajpur', 'assets/farmer-image/user_male_010.jpg', 'Large', 'Rice, Wheat', 3.50, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 27),
(19, 'Arefin Khan', NULL, NULL, '01720020018', 'Feni', 'assets/farmer-image/user_male_011.jpg', 'Small', 'Pepper, Onion', 0.95, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 28),
(20, 'Nayeem Siddiqui', NULL, NULL, '01720020019', 'Kushtia', 'assets/farmer-image/user_male_012.jpg', 'Medium', 'Garlic, Onion', 1.45, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 29),
(21, 'Tawhid Alam', NULL, NULL, '01720020020', 'Madaripur', 'assets/farmer-image/user_male_013.jpg', 'Small', 'Eggplant, Spinach', 0.70, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 30),
(22, 'Shafayet Rafi', NULL, NULL, '01720020021', 'Manikganj', 'assets/farmer-image/user_male_014.jpg', 'Medium', 'Potato, Carrot', 1.30, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 31),
(23, 'Fahim Ahmed', NULL, NULL, '01720020022', 'Narsingdi', 'assets/farmer-image/user_male_015.jpg', 'Medium', 'Rice, Maize', 1.85, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 32),
(24, 'Sabrina Yasmin', NULL, NULL, '01720020023', 'Cumilla Sadar', 'assets/farmer-image/user_female_001.jpg', 'Small', 'Coriander, Mint', 0.55, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 33),
(25, 'Mariya Akter', NULL, NULL, '01720020024', 'Sirajganj', 'assets/farmer-image/user_female_003.jpg', 'Medium', 'Pumpkin, Bean', 1.20, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 34),
(26, 'Tasnim Noor', NULL, NULL, '01720020025', 'Gopalganj', 'assets/farmer-image/user_female_004.jpg', 'Small', 'Okra, Chili', 0.65, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 35),
(27, 'Farzana Islam', NULL, NULL, '01720020026', 'Pirojpur', 'assets/farmer-image/user_female_005.jpg', 'Large', 'Rice, Jute', 3.10, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 3),
(28, 'Noman Molla', '0000-00-00', '', '01720020027', 'Kishoreganj', 'assets/farmer-image/user_male_001.jpg', 'Small', 'Bitter gourd, Tomato', 0.60, 'Own Land', '0', '', '', '', '', '', '2025-10-05 17:20:18', 4),
(29, 'Adnan Shah', NULL, NULL, '01720020028', 'Sherpur', 'assets/farmer-image/user_male_002.jpg', 'Medium', 'Rice, Eggplant', 1.25, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 5),
(30, 'Saad Chowdhury', NULL, NULL, '01720020029', 'Habiganj', 'assets/farmer-image/user_male_004.jpg', 'Medium', 'Cucumber, Capsicum', 1.75, 'Leased Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 10),
(31, 'Nusrat Jahan', NULL, NULL, '01720020030', 'Jhenaidah', 'assets/farmer-image/user_female_006.jpg', 'Small', 'Spinach, Coriander', 0.72, 'Own Land', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-05 17:20:18', 15),
(32, 'Mahbub', '2025-10-15', '9998712631', '01744177620', 'Sayednagar, Vatara, Dhaka', '', 'Medium', 'Rice, Tomato, Patato', 10.00, 'Own Land', '0', '4342314123412', '0151177620', 'No', 'Rice-35/kg', 'He is a Extra Ordinary Man', '2025-10-12 15:46:12', 0),
(33, 'Noman Molla', '2025-10-09', '22222222', '2222222222', 'dfg', '', 'Small', 'Rice, Wheat, Corn', 0.11, 'Leased Land', '0', '4654646516431651', '4353245', 'No training', 'Rice - 35BDT/kg', 'Checking for under agent', '2025-10-13 07:42:52', 64),
(37, 'Kulsum Akter', '1995-05-25', '19950525456789', '01612345004', 'Kapasia, Gazipur', 'assets/farmer-image/user/female003.jpg', 'Small', 'Chili, Coriander, Spinach', 0.80, 'Own Land', 'Organic only', '456789012345', '01612345004', 'Local NGO training', 'Chili-120tk/kg', 'Interested in organic certification', '2025-10-13 09:35:30', 64),
(38, 'Shahjahan Mia', '1982-09-08', '19820908567890', '01512345005', 'Tongi, Gazipur', 'assets/farmer-image/user/male001.jpg', 'Medium', 'Potato, Onion, Garlic', 1.80, 'Leased Land', 'Conventional', '567890123456', '01512345005', 'No training', 'Potato-28tk/kg', 'Struggles with lease renewal', '2025-10-13 09:35:30', 64),
(39, 'Fatema Khatun', '1988-12-12', '19881212678901', '01412345006', 'Sreepur, Gazipur', 'assets/farmer-image/user/female009.jpg', 'Medium', 'Cauliflower, Cabbage, Eggplant', 2.10, 'Own Land', 'Balanced fertilizer use', '678901234567', '01412345006', 'District agriculture training', 'Cauliflower-25tk/pcs', 'Reliable and experienced', '2025-10-13 09:35:30', 64),
(40, 'Mofizul Rahman', '1992-02-18', '19920218789012', '01312345007', 'Kaliakair, Gazipur', 'assets/farmer-image/user/male002.jpg', 'Small', 'Cucumber, Bitter gourd, Pumpkin', 1.00, 'Own Land', 'Minimal use', '789012345678', '01312345007', 'Self-taught', 'Cucumber-30tk/kg', 'Young enthusiastic farmer', '2025-10-13 09:35:30', 64),
(41, 'Nasima Akter', '1986-06-30', '19860630890123', '01212345008', 'Kapasia, Gazipur', 'assets/farmer-image/user/female005.jpg', 'Large', 'Rice, Wheat, Lentils', 4.50, 'Own Land', 'Modern scientific methods', '890123456789', '01212345008', 'National level training', 'Rice-48tk/kg, Lentils-85tk/kg', 'Award-winning farmer 2024', '2025-10-13 09:35:30', 64),
(42, 'Habibur Rahman', '1980-04-05', '19800405901234', '01112345009', 'Tongi, Gazipur', 'assets/farmer-image/user/male006.jpg', 'Medium', 'Maize, Groundnut, Sesame', 2.30, 'Leased Land', 'Integrated pest management', '901234567890', '01112345009', 'Workshop on IPM', 'Maize-32tk/kg', 'Innovative farming techniques', '2025-10-13 09:35:30', 64),
(43, 'Rehana Parvin', '1993-08-22', '19930822012345', '01712345010', 'Sreepur, Gazipur', 'assets/farmer-image/user/female010.jpg', 'Small', 'Beans, Okra, Radish', 0.90, 'Own Land', 'Eco-friendly methods', '012345678901', '01712345010', 'Organic farming course', 'Beans-55tk/kg', 'Committed to sustainability', '2025-10-13 09:35:30', 64),
(44, 'Farmer 169', '2025-10-17', '2222234234234324', '2412412412', 'fasdfas', '', 'Large', 'Rice, Tomato, Patato', 11.00, 'Leased Land', '0', '4342314123412', '0151177620', 'No', 'Rice-35/kg', 'No need to insert', '2025-10-15 17:38:54', 70),
(45, 'Xunayed', '2025-10-04', '2222234234234324', '01744177620', 'Natore', '', 'Medium', 'Rice, Tomato, Patato', 12.00, 'Leased Land', '0', '4342314123412', '', 'No', 'Rice-35/kg', 'dasfasdf', '2025-10-15 17:47:52', 70),
(46, 'Follar farmer', '2025-10-06', '2222234234234324', '01744177620', 'asdfasdf', '', 'Small', 'Rice, Tomato, Patato', 123.00, 'Own Land', '0', '4342314123412', '', 'No', 'Rice-35/kg', 'fsadfsad', '2025-10-15 17:57:50', 70);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_payments`
--

CREATE TABLE `farmer_payments` (
  `payment_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_payment_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Partial','Paid') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_payments`
--

INSERT INTO `farmer_payments` (`payment_id`, `agent_id`, `farmer_id`, `total_amount`, `paid_amount`, `last_payment_date`, `due_date`, `last_payment_amount`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(7, 64, 37, 15000.00, 15000.00, '2025-09-18', NULL, 15000.00, 'Paid', 'Organic chili full payment', '2025-09-10 02:30:00', '2025-10-13 09:37:02'),
(8, 64, 37, 12000.00, 5000.00, '2025-10-02', '2025-10-25', 5000.00, 'Partial', 'Spinach order balance pending', '2025-09-22 04:00:00', '2025-10-13 09:37:02'),
(9, 64, 38, 32000.00, 32000.00, '2025-09-20', NULL, 32000.00, 'Paid', 'Potato bulk order settled', '2025-09-08 09:00:00', '2025-10-13 09:37:02'),
(10, 64, 38, 28000.00, 10000.00, '2025-10-10', '2025-11-05', 10000.00, 'Partial', 'Onion order advance paid', '2025-10-03 06:20:00', '2025-10-13 09:37:02'),
(11, 64, 39, 38000.00, 38000.00, '2025-09-22', NULL, 25000.00, 'Paid', 'Cauliflower payment cleared', '2025-09-12 05:30:00', '2025-10-13 09:37:02'),
(12, 64, 39, 22000.00, 8000.00, '2025-10-06', '2025-11-12', 8000.00, 'Partial', 'Cabbage order partial', '2025-09-26 08:00:00', '2025-10-13 09:37:02'),
(13, 64, 40, 16000.00, 16000.00, '2025-09-12', NULL, 16000.00, 'Paid', 'Cucumber supply paid', '2025-09-02 03:45:00', '2025-10-13 09:37:02'),
(14, 64, 40, 14000.00, 6000.00, '2025-10-04', '2025-10-28', 6000.00, 'Partial', 'Pumpkin order pending balance', '2025-09-24 10:30:00', '2025-10-13 09:37:02'),
(15, 64, 41, 82000.00, 82000.00, '2025-09-28', NULL, 60000.00, 'Paid', 'Premium rice full payment', '2025-09-15 04:15:00', '2025-10-13 09:37:02'),
(16, 64, 41, 45000.00, 25000.00, '2025-10-09', '2025-11-18', 25000.00, 'Partial', 'Wheat order advance', '2025-10-01 07:00:00', '2025-10-13 09:37:02'),
(17, 64, 42, 36000.00, 36000.00, '2025-09-16', NULL, 36000.00, 'Paid', 'Maize purchase settled', '2025-09-06 08:45:00', '2025-10-13 09:37:02'),
(18, 64, 42, 27000.00, 12000.00, '2025-10-07', '2025-11-08', 12000.00, 'Partial', 'Groundnut order partial', '2025-09-29 05:20:00', '2025-10-13 09:37:02'),
(19, 64, 43, 19000.00, 19000.00, '2025-09-14', NULL, 19000.00, 'Paid', 'Organic beans full payment', '2025-09-04 02:00:00', '2025-10-13 09:37:02'),
(20, 64, 43, 13000.00, 4000.00, '2025-10-03', '2025-10-30', 4000.00, 'Partial', 'Okra order balance due', '2025-09-25 09:30:00', '2025-10-13 09:37:02'),
(30, 64, 42, 20000.00, 15000.00, NULL, '2025-10-29', 0.00, 'Partial', '', '2025-10-13 13:15:07', '2025-10-13 13:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `model_performance`
--

CREATE TABLE `model_performance` (
  `id` int(11) NOT NULL,
  `model_name` varchar(50) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `mae` decimal(10,4) DEFAULT 0.0000,
  `mape` decimal(10,4) DEFAULT 0.0000,
  `rmse` decimal(10,4) DEFAULT 0.0000,
  `r_squared` decimal(10,4) DEFAULT 0.0000,
  `evaluation_date` date NOT NULL,
  `data_points_used` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Recipient user',
  `type` enum('low_stock','new_order','request_approved','system','offer','profile_update') NOT NULL COMMENT 'Notification type',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'Optional link to relevant page, e.g., /orders.php?id=123',
  `is_read` tinyint(1) DEFAULT 0 COMMENT '0 = unread, 1 = read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES
(2, 1, 'new_order', 'New order #ORD-201 from Alice Johnson for $89.99.', '/orders.php?id=201', 1, '2025-10-05 17:59:24'),
(4, 1, 'request_approved', 'Your custom order request has been approved.', '/requests.php?id=req_1', 1, '2025-10-05 17:59:24'),
(5, 1, 'system', 'Account security: Two-factor authentication enabled.', '/security.php', 1, '2025-10-05 17:59:24'),
(6, 2, 'profile_update', 'Email address updated in your profile.', '/profile.php?tab=email', 1, '2025-10-05 17:59:24'),
(7, 2, 'low_stock', 'Restock needed: Apparel Y down to 7 items.', '/inventory.php?product=apparel_y', 1, '2025-10-05 17:59:24'),
(8, 2, 'new_order', 'Order #ORD-202 placed successfully by Bob Smith.', '/orders.php?id=202', 0, '2025-10-05 17:59:24'),
(9, 2, 'offer', 'Flash deal: Buy one get one 50% off on shoes.', '/deals.php?category=shoes', 0, '2025-10-05 17:59:24'),
(10, 2, 'request_approved', 'Seller verification approved. Start selling now!', '/seller-dashboard.php', 0, '2025-10-05 17:59:24'),
(16, 4, 'request_approved', 'Product upload request processed and live.', '/products.php?id=prod_4', 0, '2025-10-05 17:59:24'),
(17, 4, 'system', 'Maintenance complete: All systems operational.', NULL, 0, '2025-10-05 17:59:24'),
(18, 4, 'profile_update', 'Shipping address added to your account.', '/settings.php?tab=shipping', 0, '2025-10-05 17:59:24'),
(19, 4, 'low_stock', 'Furniture W stock warning: 6 units remaining.', '/inventory.php?product=furniture_w', 0, '2025-10-05 17:59:24'),
(20, 4, 'new_order', 'New bulk order #ORD-204 received.', '/orders.php?id=204', 0, '2025-10-05 17:59:24'),
(21, 5, 'offer', 'Seasonal sale: 30% off home decor items.', '/shop.php?category=home', 0, '2025-10-05 17:59:24'),
(22, 5, 'request_approved', 'Refund request approved. Funds returning soon.', '/refunds.php?id=ref_5', 0, '2025-10-05 17:59:24'),
(23, 5, 'system', 'Update your app for the latest features.', '/download.php', 0, '2025-10-05 17:59:24'),
(24, 5, 'profile_update', 'Phone number verified and updated.', '/profile.php?tab=contact', 0, '2025-10-05 17:59:24'),
(25, 5, 'low_stock', 'Toys V low stock: Only 3 left in warehouse.', '/inventory.php?product=toys_v', 0, '2025-10-05 17:59:24'),
(26, 6, 'new_order', 'Order #ORD-205 confirmed with express shipping.', '/orders.php?id=205', 0, '2025-10-05 17:59:24'),
(27, 6, 'offer', 'Exclusive: Early access to new product launches.', '/new-arrivals.php', 0, '2025-10-05 17:59:24'),
(28, 6, 'request_approved', 'Support query resolved. Check response.', '/support.php?id=sup_6', 0, '2025-10-05 17:59:24'),
(29, 6, 'system', 'Data backup: Your information is safe.', NULL, 0, '2025-10-05 17:59:24'),
(30, 6, 'profile_update', 'Profile bio updated successfully.', '/profile.php', 0, '2025-10-05 17:59:24'),
(31, 7, 'low_stock', 'Electronics U inventory alert: 5 units left.', '/inventory.php?product=electronics_u', 0, '2025-10-05 17:59:24'),
(32, 7, 'new_order', 'New order #ORD-206 from a new customer.', '/orders.php?id=206', 0, '2025-10-05 17:59:24'),
(33, 7, 'offer', 'Coupon code: SAVE20 for 20% off next order.', '/cart.php?code=SAVE20', 0, '2025-10-05 17:59:24'),
(34, 7, 'request_approved', 'Listing edit approved and updated.', '/listings.php?id=list_7', 0, '2025-10-05 17:59:24'),
(35, 7, 'system', 'Security tip: Use strong passwords.', '/tips.php', 0, '2025-10-05 17:59:24'),
(36, 8, 'profile_update', 'Payment card details updated.', '/settings.php?tab=payment', 0, '2025-10-05 17:59:24'),
(37, 8, 'low_stock', 'Apparel T stock low: Replenish soon.', '/inventory.php?product=apparel_t', 0, '2025-10-05 17:59:24'),
(38, 8, 'new_order', 'Order #ORD-207 processed for $75.00.', '/orders.php?id=207', 0, '2025-10-05 17:59:24'),
(39, 8, 'offer', 'Bundle offer: Save on combined purchases.', '/bundles.php', 0, '2025-10-05 17:59:24'),
(40, 8, 'request_approved', 'Account upgrade request granted.', '/upgrade.php', 0, '2025-10-05 17:59:24'),
(41, 9, 'system', 'New feature: Real-time order tracking.', '/tracking.php', 0, '2025-10-05 17:59:24'),
(42, 9, 'profile_update', 'Newsletter preferences saved.', '/preferences.php', 0, '2025-10-05 17:59:24'),
(43, 9, 'low_stock', 'Book S inventory warning: 4 copies remaining.', '/inventory.php?product=book_s', 0, '2025-10-05 17:59:24'),
(44, 9, 'new_order', 'International order #ORD-208 received.', '/orders.php?id=208', 0, '2025-10-05 17:59:24'),
(45, 9, 'offer', 'Holiday special: Free gift with purchase.', '/holidays.php', 0, '2025-10-05 17:59:24'),
(46, 10, 'request_approved', 'Feedback submission approved for display.', '/feedback.php?id=feed_10', 0, '2025-10-05 17:59:24'),
(47, 10, 'system', 'Reminder: Review your recent orders.', '/orders.php', 0, '2025-10-05 17:59:24'),
(48, 10, 'profile_update', 'Avatar image changed successfully.', '/profile.php?tab=avatar', 0, '2025-10-05 17:59:24'),
(49, 10, 'low_stock', 'Furniture R stock alert: Low levels.', '/inventory.php?product=furniture_r', 0, '2025-10-05 17:59:24'),
(50, 10, 'new_order', 'Order #ORD-209 placed and pending payment.', '/orders.php?id=209', 0, '2025-10-05 17:59:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `shopowner_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('Pending','Approved','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid') NOT NULL DEFAULT 'Pending',
  `tran_id` varchar(255) DEFAULT NULL,
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `shopowner_id`, `total_amount`, `status`, `payment_status`, `tran_id`, `placed_at`, `updated_at`) VALUES
(1, 6, 50000.00, 'Pending', 'Pending', NULL, '2025-09-23 16:31:22', '2025-09-23 16:31:22'),
(2, 6, 2000.00, 'Approved', 'Pending', NULL, '2025-09-23 16:31:39', '2025-09-23 16:31:39'),
(3, 6, 223000.00, 'Shipped', 'Pending', NULL, '2025-09-23 16:32:05', '2025-10-08 08:26:11'),
(4, 6, 23000.00, 'Shipped', 'Pending', NULL, '2025-09-23 16:32:21', '2025-10-08 08:27:03'),
(5, 2, 2000.00, 'Pending', 'Pending', NULL, '2025-09-23 17:52:21', '2025-09-23 17:52:21'),
(6, 2, 100.00, 'Approved', 'Pending', NULL, '2025-09-23 17:52:21', '2025-09-23 17:52:21'),
(7, 2, 1200.00, 'Pending', 'Pending', NULL, '2025-09-20 04:00:00', '2025-09-20 04:00:00'),
(8, 2, 1600.00, 'Approved', 'Pending', NULL, '2025-09-21 05:00:00', '2025-09-21 06:00:00'),
(9, 2, 300.00, 'Shipped', 'Pending', NULL, '2025-09-22 03:30:00', '2025-09-22 04:30:00'),
(10, 2, 180.00, 'Delivered', 'Pending', NULL, '2025-09-19 08:00:00', '2025-09-20 09:00:00'),
(11, 5, 500.00, 'Cancelled', 'Pending', NULL, '2025-09-18 10:00:00', '2025-09-19 11:00:00'),
(12, 5, 75.00, 'Pending', 'Pending', NULL, '2025-09-23 02:00:00', '2025-09-23 02:00:00'),
(13, 6, 240.00, 'Approved', 'Pending', NULL, '2025-09-22 07:00:00', '2025-09-22 08:00:00'),
(14, 6, 120.00, 'Shipped', 'Pending', NULL, '2025-09-21 09:00:00', '2025-09-21 10:00:00'),
(15, 6, 45.00, 'Delivered', 'Pending', NULL, '2025-09-20 06:00:00', '2025-09-21 07:00:00'),
(16, 6, 10.00, 'Pending', 'Pending', NULL, '2025-09-23 03:00:00', '2025-09-23 03:00:00'),
(17, 48, 450.00, 'Pending', 'Pending', 'SSLCZ_TEST_68e62896a584d', '2025-10-08 09:02:14', '2025-10-08 09:02:14'),
(19, 48, 350.00, 'Pending', 'Paid', 'SSLCZ_TEST_68e628c2a145f', '2025-10-08 09:02:58', '2025-10-08 09:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 1200.00),
(2, 1, 2, 1, 800.00),
(3, 2, 3, 5, 20.00),
(4, 3, 1, 1, 1200.00),
(5, 4, 2, 2, 800.00),
(6, 5, 4, 2, 150.00),
(7, 6, 6, 3, 60.00),
(8, 7, 3, 10, 50.00),
(9, 8, 8, 5, 15.00),
(10, 9, 6, 3, 80.00),
(11, 10, 7, 2, 60.00),
(12, 11, 9, 9, 5.00),
(13, 12, 10, 10, 1.00),
(14, 17, 1, 1, 150.00),
(15, 17, 2, 2, 50.00),
(16, 19, 1, 1, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `img_url` varchar(300) NOT NULL DEFAULT 'assets\\products-image\\mango.jpg',
  `unit_space` decimal(6,3) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `notes` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `unit`, `created_at`, `updated_at`, `img_url`, `unit_space`, `special_instructions`, `notes`) VALUES
(1, 'Apple', 'Fruit', 150.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\apple.jpg', 0.005, NULL, ''),
(2, 'Banana', 'Fruit', 50.00, 'dozen', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\banana.jpg', 0.005, NULL, ''),
(3, 'Mango', 'Fruit', 200.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\mango.jpg', 0.007, NULL, ''),
(4, 'Orange', 'Fruit', 120.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\orrange.jpg', 0.013, NULL, ''),
(5, 'Pineapple', 'Fruit', 180.00, 'pcs', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Pineapple.jpg', 0.010, NULL, ''),
(6, 'Grapes', 'Fruit', 160.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Grapes.jpg', 0.019, NULL, ''),
(7, 'Strawberry', 'Fruit', 300.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Strawberries.jpg', 0.017, NULL, ''),
(8, 'Tomato', 'Vegetable', 80.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Tomato.jpg', 0.008, NULL, ''),
(9, 'Potato', 'Vegetable', 40.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Potatoes.jpg', 0.014, NULL, ''),
(10, 'Carrot', 'Vegetable', 90.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Carrot.jpg', 0.012, NULL, ''),
(11, 'Cucumber', 'Vegetable', 60.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Cucumber.jpg', 0.013, NULL, ''),
(12, 'Spinach', 'Vegetable', 50.00, 'bunch', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Spinach.jpg', 0.007, NULL, ''),
(13, 'Cauliflower', 'Vegetable', 70.00, 'pcs', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets/products-image/Cauliflower.jpg', 0.005, NULL, ''),
(14, 'Capsicum', 'Vegetable', 120.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\Capsicum.jpg', 0.016, NULL, ''),
(15, 'Onion', 'Vegetable', 60.00, 'kg', '2025-09-16 17:12:24', '2025-10-05 17:07:26', 'assets\\products-image\\onions.jpg', 0.015, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `sales_trends`
--

CREATE TABLE `sales_trends` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `trend_type` enum('daily','weekly','monthly','seasonal') NOT NULL,
  `trend_period` varchar(20) NOT NULL,
  `avg_demand` decimal(10,2) DEFAULT 0.00,
  `demand_variance` decimal(10,2) DEFAULT 0.00,
  `peak_demand_hour` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `self_service_orders`
--

CREATE TABLE `self_service_orders` (
  `order_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `user_name` varchar(200) NOT NULL,
  `order_code` varchar(200) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `products` text NOT NULL,
  `status` enum('In queue','Running','Done','Cancelled') NOT NULL DEFAULT 'In queue',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `self_service_orders`
--

INSERT INTO `self_service_orders` (`order_id`, `shop_id`, `user_name`, `order_code`, `total_amount`, `products`, `status`, `created_at`, `updated_at`) VALUES
(4, 6, 'tashin', 'Y37', 20.00, '[{\"product_id\":\"1\",\"quantity\":2,\"price\":10}]', '', '2025-10-04 13:51:11', '2025-10-04 13:51:11'),
(5, 6, 'Noman', 'T45', 30.00, '[{\"product_id\":\"1\",\"quantity\":3,\"price\":10}]', '', '2025-10-04 14:00:07', '2025-10-04 14:00:07'),
(6, 6, 'Noman', 'G66', 90.00, '[{\"product_id\":\"1\",\"quantity\":9,\"price\":10}]', '', '2025-10-04 14:00:43', '2025-10-04 14:00:43'),
(7, 6, 'das', 'B52', 40.00, '[{\"product_id\":\"4\",\"quantity\":4,\"price\":10}]', '', '2025-10-04 14:01:21', '2025-10-04 14:01:21'),
(8, 6, 'aranaya', 'U66', 50.00, '[{\"product_id\":\"4\",\"quantity\":5,\"price\":10}]', '', '2025-10-04 14:04:33', '2025-10-04 14:04:33'),
(9, 6, 'ushfiq', 'U48', 40.00, '[{\"product_id\":\"11\",\"quantity\":4,\"price\":10}]', '', '2025-10-04 14:12:01', '2025-10-04 14:12:01'),
(10, 6, 'ovi', 'F79', 40.00, '[{\"product_id\":\"4\",\"quantity\":4,\"price\":10}]', '', '2025-10-04 14:14:44', '2025-10-04 14:14:44'),
(11, 6, 'liz ', 'O16', 40.00, '[{\"product_id\":\"11\",\"quantity\":4,\"price\":10}]', '', '2025-10-04 14:15:22', '2025-10-04 14:15:22');

-- --------------------------------------------------------

--
-- Table structure for table `shop_owner_cart_items`
--

CREATE TABLE `shop_owner_cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_time` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_owner_cart_list`
--

CREATE TABLE `shop_owner_cart_list` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','ordered','abandoned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_owner_cart_list`
--

INSERT INTO `shop_owner_cart_list` (`cart_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'active', '2025-10-07 19:42:34', '2025-10-07 19:42:34'),
(2, 48, 'ordered', '2025-10-08 08:57:49', '2025-10-08 09:02:14'),
(3, 48, 'ordered', '2025-10-08 09:02:44', '2025-10-08 09:02:58');

-- --------------------------------------------------------

--
-- Table structure for table `shop_products`
--

CREATE TABLE `shop_products` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `bought_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_products`
--

INSERT INTO `shop_products` (`id`, `shop_id`, `product_id`, `quantity`, `selling_price`, `bought_price`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 500, 16.00, 12.00, '2025-10-01 10:25:30', '2025-10-01 10:25:30'),
(2, 2, 2, 500, 16.00, 12.00, '2025-10-01 10:25:41', '2025-10-01 10:25:41'),
(3, 2, 4, 100, 16.00, 12.00, '2025-10-01 10:26:09', '2025-10-01 10:26:09'),
(4, 6, 11, 2500, 10.00, 7.00, '2025-10-01 10:26:47', '2025-10-01 10:26:47'),
(5, 6, 1, 2500, 10.00, 7.00, '2025-10-01 10:26:56', '2025-10-01 10:26:56'),
(6, 6, 4, 2500, 10.00, 7.00, '2025-10-01 10:27:02', '2025-10-01 10:27:02');

-- --------------------------------------------------------

--
-- Table structure for table `stock_requests`
--

CREATE TABLE `stock_requests` (
  `request_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Done','Rejected','Working') DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_requests`
--

INSERT INTO `stock_requests` (`request_id`, `requester_id`, `product_id`, `quantity`, `note`, `status`, `requested_at`, `updated_at`) VALUES
(7, 6, 1, 600, 'Urgent', 'Pending', '2025-10-02 17:38:43', '2025-10-02 17:38:43'),
(8, 2, 1, 500, 'Replenish apples', 'Rejected', '2025-10-04 17:07:10', '2025-10-08 05:28:46'),
(9, 6, 2, 120, 'Weekly restock', 'Working', '2025-10-03 17:07:10', '2025-10-05 17:07:10'),
(10, 9, 3, 300, 'Promo demand', 'Pending', '2025-10-02 17:07:10', '2025-10-05 17:07:10'),
(11, 12, 4, 220, NULL, 'Pending', '2025-10-01 17:07:10', '2025-10-05 17:07:10'),
(12, 13, 5, 150, 'For weekend', 'Pending', '2025-09-30 17:07:10', '2025-10-05 17:07:10'),
(13, 14, 6, 90, 'Urgent', 'Working', '2025-10-05 16:07:10', '2025-10-05 17:07:10'),
(15, 4, 8, 400, 'Bulk order', 'Pending', '2025-09-27 17:07:10', '2025-10-05 17:07:10'),
(16, 5, 9, 1000, 'Seasonal', 'Pending', '2025-09-26 17:07:10', '2025-10-05 17:07:10'),
(17, 10, 10, 250, 'Trial lot', 'Rejected', '2025-09-25 17:07:10', '2025-10-05 17:07:10'),
(18, 2, 11, 200, NULL, 'Pending', '2025-09-24 17:07:10', '2025-10-05 17:07:10'),
(19, 6, 12, 75, 'Leafy greens', 'Done', '2025-09-23 17:07:10', '2025-10-05 17:07:10'),
(20, 9, 13, 180, 'New branch', 'Pending', '2025-09-22 17:07:10', '2025-10-05 17:07:10'),
(21, 12, 14, 140, NULL, 'Working', '2025-09-21 17:07:10', '2025-10-05 17:07:10'),
(22, 13, 15, 600, 'Price drop', 'Pending', '2025-09-20 17:07:10', '2025-10-05 17:07:10'),
(23, 14, 1, 450, 'Festival rush', 'Pending', '2025-09-19 17:07:10', '2025-10-05 17:07:10'),
(25, 4, 3, 350, 'High demand', 'Pending', '2025-09-17 17:07:10', '2025-10-05 17:07:10'),
(26, 5, 4, 260, NULL, 'Pending', '2025-09-16 17:07:10', '2025-10-05 17:07:10'),
(27, 10, 5, 130, 'Slow seller', 'Working', '2025-09-15 17:07:10', '2025-10-05 17:07:10'),
(28, 2, 6, 95, 'Top seller', 'Done', '2025-09-14 17:07:10', '2025-10-05 17:07:10'),
(29, 6, 7, 70, 'Trial pack', 'Pending', '2025-09-13 17:07:10', '2025-10-05 17:07:10'),
(30, 9, 8, 420, 'Price promo', 'Pending', '2025-09-12 17:07:10', '2025-10-05 17:07:10'),
(31, 12, 9, 800, 'Holiday prep', 'Pending', '2025-09-11 17:07:10', '2025-10-05 17:07:10'),
(32, 13, 10, 210, NULL, 'Working', '2025-09-10 17:07:10', '2025-10-05 17:07:10'),
(33, 14, 11, 160, NULL, 'Pending', '2025-09-09 17:07:10', '2025-10-05 17:07:10'),
(35, 4, 13, 190, 'New listing', 'Pending', '2025-09-07 17:07:10', '2025-10-05 17:07:10'),
(36, 5, 14, 150, 'Color mix', 'Done', '2025-09-06 17:07:10', '2025-10-05 17:07:10'),
(37, 10, 15, 700, 'Wholesale', 'Working', '2025-09-05 17:07:10', '2025-10-08 04:58:54'),
(38, 2, 1, 500, 'Replenish apples', 'Pending', '2025-10-04 17:07:54', '2025-10-05 17:07:54'),
(39, 6, 2, 120, 'Weekly restock', 'Working', '2025-10-03 17:07:54', '2025-10-05 17:07:54'),
(40, 9, 3, 300, 'Promo demand', 'Pending', '2025-10-02 17:07:54', '2025-10-05 17:07:54'),
(41, 12, 4, 220, NULL, 'Pending', '2025-10-01 17:07:54', '2025-10-05 17:07:54'),
(42, 13, 5, 150, 'For weekend', 'Pending', '2025-09-30 17:07:54', '2025-10-05 17:07:54'),
(43, 14, 6, 90, 'Urgent', 'Rejected', '2025-10-05 16:07:54', '2025-10-08 05:28:37'),
(45, 4, 8, 400, 'Bulk order', 'Pending', '2025-09-27 17:07:54', '2025-10-05 17:07:54'),
(46, 5, 9, 1000, 'Seasonal', 'Pending', '2025-09-26 17:07:54', '2025-10-05 17:07:54'),
(47, 10, 10, 250, 'Trial lot', 'Rejected', '2025-09-25 17:07:54', '2025-10-05 17:07:54'),
(48, 2, 11, 200, NULL, 'Pending', '2025-09-24 17:07:54', '2025-10-05 17:07:54'),
(49, 6, 12, 75, 'Leafy greens', 'Done', '2025-09-23 17:07:54', '2025-10-05 17:07:54'),
(50, 9, 13, 180, 'New branch', 'Pending', '2025-09-22 17:07:54', '2025-10-05 17:07:54'),
(51, 12, 14, 140, NULL, 'Working', '2025-09-21 17:07:54', '2025-10-05 17:07:54'),
(52, 13, 15, 600, 'Price drop', 'Pending', '2025-09-20 17:07:54', '2025-10-05 17:07:54'),
(53, 14, 1, 450, 'Festival rush', 'Pending', '2025-09-19 17:07:54', '2025-10-05 17:07:54'),
(55, 4, 3, 350, 'High demand', 'Pending', '2025-09-17 17:07:54', '2025-10-05 17:07:54'),
(56, 5, 4, 260, NULL, 'Pending', '2025-09-16 17:07:54', '2025-10-05 17:07:54'),
(57, 10, 5, 130, 'Slow seller', 'Working', '2025-09-15 17:07:54', '2025-10-05 17:07:54'),
(58, 2, 6, 95, 'Top seller', 'Done', '2025-09-14 17:07:54', '2025-10-05 17:07:54'),
(59, 6, 7, 70, 'Trial pack', 'Pending', '2025-09-13 17:07:54', '2025-10-05 17:07:54'),
(60, 9, 8, 420, 'Price promo', 'Pending', '2025-09-12 17:07:54', '2025-10-05 17:07:54'),
(61, 12, 9, 800, 'Holiday prep', 'Pending', '2025-09-11 17:07:54', '2025-10-05 17:07:54'),
(62, 13, 10, 210, NULL, 'Working', '2025-09-10 17:07:54', '2025-10-05 17:07:54'),
(63, 14, 11, 160, NULL, 'Pending', '2025-09-09 17:07:54', '2025-10-05 17:07:54'),
(65, 4, 13, 190, 'New listing', 'Pending', '2025-09-07 17:07:54', '2025-10-05 17:07:54'),
(66, 5, 14, 150, 'Color mix', 'Done', '2025-09-06 17:07:54', '2025-10-05 17:07:54'),
(67, 10, 15, 700, 'Wholesale', 'Pending', '2025-09-05 17:07:54', '2025-10-05 17:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Shop-Owner','Agent','User') DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `image_url`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Rafiq Ahmed', 'rafiq.admin@example.com', '01710000001', 'hashed_password', 'assets/user-image/boy.jpg', 'Admin', '2025-09-16 17:27:57', '2025-10-05 17:25:30'),
(2, 'Shwapno', 'shakila.owner@example.com', '01710000002', 'hashed_password', 'assets/user-image/shwapno.jpg', 'Shop-Owner', '2025-09-16 17:27:57', '2025-10-05 17:25:30'),
(4, 'Nabila Karim', 'nabila.agent@example.com', '01710000004', 'hashed_password', 'assets/user-image/user_female_010.jpg', 'Agent', '2025-09-16 17:27:57', '2025-10-05 17:09:03'),
(5, 'tashin', 'tashin.agent@gmail.com', '01710000005', '1234', 'assets/user-image/tashin.jpg', 'Agent', '2025-09-16 17:27:57', '2025-10-05 17:25:30'),
(6, 'Agora', 'safin@gmail.com', '01952223224', '1234', 'assets/user-image/agora.jpg', 'Shop-Owner', '2025-09-20 13:21:09', '2025-10-05 17:25:30'),
(7, 'tasruba', 'tasruba@gmail.uiu.com', '01952222222', '12345', 'assets/user-image/girl.jpg', 'User', '2025-09-20 13:21:09', '2025-10-05 17:25:30'),
(8, 'Admin User', 'admin@example.com', '1234567890', 'hashedpassword1', 'assets/user-image/tashin.jpg', 'Admin', '2025-09-23 17:52:20', '2025-10-05 17:25:30'),
(9, 'Amana Big Bazar', 'shop1@example.com', '0987654321', 'hashedpassword2', 'assets/user-image/Amana Big Bazar.png', 'Shop-Owner', '2025-09-23 17:52:20', '2025-10-05 17:25:30'),
(10, 'Agent User', 'agent@example.com', '1122334455', 'hashedpassword3', 'assets/user-image/user_male_006.jpg', 'Agent', '2025-09-23 17:52:20', '2025-10-05 17:09:03'),
(11, 'Regular User', 'user@example.com', '5566778899', 'hashedpassword4', 'assets/user-image/boy.jpg', 'User', '2025-09-23 17:52:20', '2025-10-05 17:25:30'),
(12, 'Meena Bazar', 'shop2@example.com', '2233445566', 'hashedpassword5', 'assets/user-image/Meena Bazar.png', 'Shop-Owner', '2025-09-23 17:52:49', '2025-10-05 17:25:30'),
(13, 'Unimart', 'shop3@example.com', '3344556677', 'hashedpassword6', 'assets/user-image/unimart.png', 'Shop-Owner', '2025-09-23 17:52:49', '2025-10-05 17:25:30'),
(14, 'Almas Super Shop', 'mparvez221437@bscse.uiu.ac.bd', '01952223224', '1345', 'assets/user-image/Almas Super Shop.jpeg', 'Shop-Owner', '2025-10-01 20:10:21', '2025-10-05 17:25:30'),
(15, 'Field Agent 01', 'agent001@example.com', '01720000001', 'changeme001', 'assets/user-image/user_male_013.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(16, 'Field Agent 02', 'agent002@example.com', '01720000002', 'changeme002', 'assets/user-image/user_male_004.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(18, 'Field Agent 04', 'agent004@example.com', '01720000004', 'changeme004', 'assets/user-image/user_male_001.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(19, 'Field Agent 05', 'agent005@example.com', '01720000005', 'changeme005', 'assets/user-image/user_male_004.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(20, 'Field Agent 06', 'agent006@example.com', '01720000006', 'changeme006', 'assets/user-image/user_male_015.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(21, 'Field Agent 07', 'agent007@example.com', '01720000007', 'changeme007', 'assets/user-image/user_male_009.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(22, 'Field Agent 08', 'agent008@example.com', '01720000008', 'changeme008', 'assets/user-image/user_male_006.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(23, 'Field Agent 09', 'agent009@example.com', '01720000009', 'changeme009', 'assets/user-image/user_male_001.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(24, 'Field Agent 10', 'agent010@example.com', '01720000010', 'changeme010', 'assets/user-image/user_male_001.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(25, 'Field Agent 11', 'agent011@example.com', '01720000011', 'changeme011', 'assets/user-image/user_male_006.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(26, 'Field Agent 12', 'agent012@example.com', '01720000012', 'changeme012', 'assets/user-image/user_male_005.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(27, 'Field Agent 13', 'agent013@example.com', '01720000013', 'changeme013', 'assets/user-image/user_male_007.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(28, 'Field Agent 14', 'agent014@example.com', '01720000014', 'changeme014', 'assets/user-image/user_male_007.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(29, 'Field Agent 15', 'agent015@example.com', '01720000015', 'changeme015', 'assets/user-image/user_male_004.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(30, 'Field Agent 16', 'agent016@example.com', '01720000016', 'changeme016', 'assets/user-image/user_male_011.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(31, 'Field Agent 17', 'agent017@example.com', '01720000017', 'changeme017', 'assets/user-image/user_male_004.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(32, 'Field Agent 18', 'agent018@example.com', '01720000018', 'changeme018', 'assets/user-image/user_male_005.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(33, 'Field Agent 19', 'agent019@example.com', '01720000019', 'changeme019', 'assets/user-image/user_male_002.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(34, 'Field Agent 20', 'agent020@example.com', '01720000020', 'changeme020', 'assets/user-image/user_male_013.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(35, 'Field Agent 21', 'agent021@example.com', '01720000021', 'changeme021', 'assets/user-image/user_male_009.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(36, 'Field Agent 22', 'agent022@example.com', '01720000022', 'changeme022', 'assets/user-image/user_male_006.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(37, 'Field Agent 23', 'agent023@example.com', '01720000023', 'changeme023', 'assets/user-image/user_male_001.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(38, 'Field Agent 24', 'agent024@example.com', '01720000024', 'changeme024', 'assets/user-image/user_male_006.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(39, 'Field Agent 25', 'agent025@example.com', '01720000025', 'changeme025', 'assets/user-image/user_male_001.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(40, 'Field Agent 26', 'agent026@example.com', '01720000026', 'changeme026', 'assets/user-image/user_male_009.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(41, 'Field Agent 27', 'agent027@example.com', '01720000027', 'changeme027', 'assets/user-image/user_male_013.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(42, 'Field Agent 28', 'agent028@example.com', '01720000028', 'changeme028', 'assets/user-image/user_male_014.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(43, 'Field Agent 29', 'agent029@example.com', '01720000029', 'changeme029', 'assets/user-image/user_male_007.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(44, 'Field Agent 30', 'agent030@example.com', '01720000030', 'changeme030', 'assets/user-image/user_male_010.jpg', 'Agent', '2025-10-05 17:08:38', '2025-10-05 17:09:03'),
(45, 'Mahbuburu Rahman', 'johnsmith@gmail.com', '01646855870', '$2y$10$qv4zlYdxBliV625BVUoq4Ozt04jNdUFmy7K2IWLLKMMEOyfE4idn.', NULL, 'Agent', '2025-10-07 20:52:00', '2025-10-07 20:52:00'),
(46, 'noman', 'mahbub@gmail.com', '01744177620', '$2y$10$qkNRkii7jFC30f8I8d2q7O4GrpqwdeQpYMRdYyP7bmT1Udf37tuDq', NULL, 'Admin', '2025-10-07 20:55:12', '2025-10-07 20:55:12'),
(48, 'Mahbub', 'admin@gmail.com', '00000000000', '$2y$10$2vNkxN15RZ36eWxmONai2ur22DacpDCHlLnFTVbKcFiT1aZZWnyre', NULL, 'Agent', '2025-10-08 07:51:07', '2025-10-08 08:02:21'),
(49, 'Noman Molla', 'molla@gmail.com', '11111111111', '$2y$10$melFTA4xhhqjMCqSbeRMY.5BRbDxpLs1PHMLeI3Tb2FEleJbAiMi.', NULL, 'Agent', '2025-10-08 07:54:21', '2025-10-08 07:54:21'),
(50, 'The OTC', 'otc@gmail.com', '33333333333', '$2y$10$kaqZvcmSouEhnjq9.cN5LeorxRU4xLhmyB2nRucCBJxMcr9SeMUBC', NULL, 'User', '2025-10-08 07:58:46', '2025-10-08 07:58:46'),
(51, 'Riana', 'riana@gmail.com', '00000000000', '$2y$10$huRJ/K6hdTq4uYL.Sutk9O2CS5WP5x3cjF8WIM0FEXjLAavPUFrSG', NULL, 'User', '2025-10-11 17:47:05', '2025-10-11 17:47:05'),
(53, 'Admin', 'adminnn@gmail.com', '1234', '$2y$10$eyHnT0TeEu0l9ttUapbrOOg.yBH7jMU1opDhoU2VJ5lDbxxIh9RPW', NULL, 'Admin', '2025-10-11 17:48:32', '2025-10-11 17:48:32'),
(54, 'Admin', 'amiadmin@gmail.com', '0000', '$2y$10$wbs47c7DF5Z//CcgsqHA1efccR./UE4qPSLe3RXuUtpMtkG4LlIgO', NULL, 'Admin', '2025-10-12 14:48:17', '2025-10-12 14:48:17'),
(55, 'Agent', 'amiagent@gmail.com', '1111', '$2y$10$oBRXYCgsSjItNDlQ.F.ke.7oWqpAVpC1PntDxiNWBZ6Zxi5nvLl3.', NULL, 'Agent', '2025-10-12 14:49:05', '2025-10-12 14:49:05'),
(56, 'Shop Owner', 'amiowner@gmail.com', '2222', '$2y$10$LyexNLpeu8Uh1PUcvMmmVOzG158hVUlw8Hxyov5FfMuWPLnZdfSWS', NULL, 'Shop-Owner', '2025-10-12 14:49:55', '2025-10-12 14:49:55'),
(57, 'User', 'amiuser@gmail.com', '3333', '$2y$10$OrygycutT0O3r6zR3O2IXO0gT.MUjGrfVnD6oR.bZ8yOB/lEoxeU6', NULL, 'User', '2025-10-12 14:50:28', '2025-10-12 14:50:28'),
(58, 'azizul', 'noman123@gmail.com', '123', '$2y$10$2MdLMFhZ64Zm948162TYAO79/vmq0KTRcdrUmVLvwUaaYeOe8nEJi', NULL, 'Agent', '2025-10-12 17:52:15', '2025-10-12 17:52:15'),
(63, 'noman', 'mahbub456@gmail.com', '12345', '$2y$10$oZF3gxQAGDxptBrfUfH90ucD3dmH9jUWTNY8VSUVzmnezc33CF8bW', NULL, 'User', '2025-10-12 18:56:04', '2025-10-12 18:56:04'),
(64, 'azizul', 'noman456@gmail.com', '789', '$2y$10$fxwa36GnhZEdDapUzuW.4eyqDPg0bURVzwKWrJIrZaGtDEkMYqsem', NULL, 'Agent', '2025-10-13 06:21:30', '2025-10-13 06:21:30'),
(70, 'Agent', 'agent@gmail.com', '987654321', '$2y$10$gNP3G3TBhQrnG99zOuTnAOf6M35C6ynRvzw6CKXevDKez.xgpMz7a', NULL, 'Agent', '2025-10-15 17:37:03', '2025-10-15 17:37:03'),
(71, 'Admin', 'adminnnnn@gmail.com', '999999999', '$2y$10$pewRRAmWWVOc9tTIC4hIueuKTwoNfibkjsbp1hHpehayLndEf3QKe', NULL, 'Admin', '2025-10-15 18:43:10', '2025-10-15 18:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `warehouse_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity_total` int(11) NOT NULL,
  `capacity_used` int(11) DEFAULT 0,
  `type` enum('Normal','Cold Storage','Hazardous') DEFAULT 'Normal',
  `status` enum('Active','Inactive','Under Maintenance') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`warehouse_id`, `name`, `location`, `capacity_total`, `capacity_used`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Dhaka Central Warehouse', 'Dhaka', 100000, 0, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(2, 'Chattogram Port Warehouse', 'Chattogram', 85000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(3, 'Sylhet Food Storage', 'Sylhet', 50000, 0, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(4, 'Khulna Industrial Warehouse', 'Khulna', 70000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(5, 'Rajshahi Textile Warehouse', 'Rajshahi', 60000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(6, 'Barishal Cold Storage', 'Barishal', 40000, 10000, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-23 17:14:02'),
(7, 'Mymensingh Agro Warehouse', 'Mymensingh', 55000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(8, 'Comilla General Warehouse', 'Comilla', 65000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(9, 'Rangpur Cold Storage', 'Rangpur', 45000, 1000, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-23 17:13:55'),
(10, 'Bogura Industrial Storage', 'Bogura', 50000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(11, 'Cox’s Bazar Food Warehouse', 'Cox\'s Bazar', 35000, 0, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(12, 'Noakhali Textile Warehouse', 'Noakhali', 40000, 0, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(13, 'Pabna Agro Storage', 'Pabna', 30000, 0, 'Normal', 'Inactive', '2025-09-16 15:10:12', '2025-09-23 15:55:04'),
(14, 'Tangail Cold Storage', 'Tangail', 55000, 0, 'Cold Storage', 'Active', '2025-09-16 15:10:12', '2025-09-16 15:10:12'),
(15, 'Jessore General Warehouse', 'Jessore', 60000, 200000, 'Normal', 'Active', '2025-09-16 15:10:12', '2025-09-23 17:13:46'),
(18, 'Main Warehouse', 'New York', 10000, 2000, 'Normal', 'Active', '2025-09-23 17:52:21', '2025-09-23 17:52:21'),
(19, 'Cold Storage', 'Los Angeles', 5000, 1000, 'Cold Storage', 'Active', '2025-09-23 17:52:21', '2025-09-23 17:52:21'),
(20, 'Warehouse Three', 'Chicago', 8000, 1500, 'Hazardous', 'Active', '2025-09-23 17:52:49', '2025-09-23 17:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_manager`
--

CREATE TABLE `warehouse_manager` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_products`
--

CREATE TABLE `warehouse_products` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_volume` decimal(10,2) DEFAULT 0.00,
  `offer_percentage` double DEFAULT NULL,
  `offer_start` date DEFAULT NULL,
  `offer_end` date DEFAULT NULL,
  `request_status` tinyint(1) DEFAULT 0,
  `inbound_stock_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `agent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_products`
--

INSERT INTO `warehouse_products` (`id`, `warehouse_id`, `product_id`, `quantity`, `unit_volume`, `offer_percentage`, `offer_start`, `offer_end`, `request_status`, `inbound_stock_date`, `expiry_date`, `last_updated`, `agent_id`) VALUES
(1, 15, 1, 1, 0.09, 15, '2025-10-09', '2025-10-24', 1, '2025-10-08', '2025-10-02', '2025-10-08 08:57:02', 5),
(3, 15, 2, 66, 8000.00, 0, NULL, NULL, 1, NULL, '2025-09-30', '2025-10-01 18:09:58', 5),
(4, 15, 6, 100, 8000.00, 0, NULL, NULL, 1, NULL, '2025-09-30', '2025-10-01 18:09:58', 5),
(5, 15, 7, 100, 8000.00, 0, NULL, NULL, 1, NULL, '2025-09-30', '2025-10-01 18:09:58', 5),
(9, 15, 1, 500, 8000.00, 0, NULL, NULL, 0, NULL, '2025-10-23', '2025-10-01 18:09:58', 5),
(10, 1, 1, 50, 1.50, 0, NULL, NULL, 1, NULL, '2026-01-01', '2025-10-01 18:09:58', 5),
(11, 1, 2, 100, 0.50, 0, NULL, NULL, 1, NULL, '2025-12-31', '2025-10-01 18:09:58', 5),
(12, 2, 3, 200, 0.20, 0, NULL, NULL, 0, NULL, '2025-12-31', '2025-10-01 18:09:58', 5),
(13, 3, 4, 30, 2.00, 0, NULL, NULL, 1, NULL, '2025-12-31', '2025-10-01 18:09:58', 5),
(14, 3, 5, 50, 0.80, 0, NULL, NULL, 0, NULL, '2025-12-31', '2025-10-01 18:09:58', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent_assigned_cities`
--
ALTER TABLE `agent_assigned_cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_id` (`agent_id`,`city_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `agent_farmer_agreements`
--
ALTER TABLE `agent_farmer_agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD UNIQUE KEY `agreement_reference` (`agreement_reference`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `agreement_status` (`agreement_status`);

--
-- Indexes for table `agent_info`
--
ALTER TABLE `agent_info`
  ADD PRIMARY KEY (`agent_info_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user_id` (`agent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_region` (`region`);

--
-- Indexes for table `billing_info`
--
ALTER TABLE `billing_info`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `fk_billing_user` (`user_id`),
  ADD KEY `fk_billing_order` (`order_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`city_id`);

--
-- Indexes for table `daily_sales_history`
--
ALTER TABLE `daily_sales_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date_shop_product` (`date`,`shop_id`,`product_id`),
  ADD KEY `idx_shop_product` (`shop_id`,`product_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `demand_forecasts`
--
ALTER TABLE `demand_forecasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forecast_date` (`forecast_date`),
  ADD KEY `idx_shop_product` (`shop_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `farmers`
--
ALTER TABLE `farmers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_type` (`farmer_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `farmer_payments`
--
ALTER TABLE `farmer_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `model_performance`
--
ALTER TABLE `model_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_model_shop_product` (`model_name`,`shop_id`,`product_id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `shopowner_id` (`shopowner_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `sales_trends`
--
ALTER TABLE `sales_trends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_product_trend` (`shop_id`,`product_id`,`trend_type`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `self_service_orders`
--
ALTER TABLE `self_service_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_selfservice_shop` (`shop_id`);

--
-- Indexes for table `shop_owner_cart_items`
--
ALTER TABLE `shop_owner_cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `fk_cart_item_cart` (`cart_id`),
  ADD KEY `fk_cart_item_product` (`product_id`);

--
-- Indexes for table `shop_owner_cart_list`
--
ALTER TABLE `shop_owner_cart_list`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_user` (`user_id`);

--
-- Indexes for table `shop_products`
--
ALTER TABLE `shop_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_id` (`shop_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_requests`
--
ALTER TABLE `stock_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `fk_stock_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- Indexes for table `warehouse_manager`
--
ALTER TABLE `warehouse_manager`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouse_id` (`warehouse_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `warehouse_products`
--
ALTER TABLE `warehouse_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_products_ibfk_1` (`warehouse_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `agent_assigned_cities`
--
ALTER TABLE `agent_assigned_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `agent_farmer_agreements`
--
ALTER TABLE `agent_farmer_agreements`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `agent_info`
--
ALTER TABLE `agent_info`
  MODIFY `agent_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `billing_info`
--
ALTER TABLE `billing_info`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `city_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `daily_sales_history`
--
ALTER TABLE `daily_sales_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `demand_forecasts`
--
ALTER TABLE `demand_forecasts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `farmers`
--
ALTER TABLE `farmers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `farmer_payments`
--
ALTER TABLE `farmer_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `model_performance`
--
ALTER TABLE `model_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `sales_trends`
--
ALTER TABLE `sales_trends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `self_service_orders`
--
ALTER TABLE `self_service_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shop_owner_cart_items`
--
ALTER TABLE `shop_owner_cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shop_owner_cart_list`
--
ALTER TABLE `shop_owner_cart_list`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shop_products`
--
ALTER TABLE `shop_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_requests`
--
ALTER TABLE `stock_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `warehouse_manager`
--
ALTER TABLE `warehouse_manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `warehouse_products`
--
ALTER TABLE `warehouse_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agent_assigned_cities`
--
ALTER TABLE `agent_assigned_cities`
  ADD CONSTRAINT `agent_assigned_cities_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_assigned_cities_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_farmer_agreements`
--
ALTER TABLE `agent_farmer_agreements`
  ADD CONSTRAINT `fk_agreement_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_agreement_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_info`
--
ALTER TABLE `agent_info`
  ADD CONSTRAINT `agent_info_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_info_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `billing_info`
--
ALTER TABLE `billing_info`
  ADD CONSTRAINT `fk_billing_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_billing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_sales_history`
--
ALTER TABLE `daily_sales_history`
  ADD CONSTRAINT `daily_sales_history_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_sales_history_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `demand_forecasts`
--
ALTER TABLE `demand_forecasts`
  ADD CONSTRAINT `demand_forecasts_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demand_forecasts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_payments`
--
ALTER TABLE `farmer_payments`
  ADD CONSTRAINT `farmer_payments_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent_info` (`agent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `farmer_payments_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `model_performance`
--
ALTER TABLE `model_performance`
  ADD CONSTRAINT `model_performance_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `model_performance_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`shopowner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_trends`
--
ALTER TABLE `sales_trends`
  ADD CONSTRAINT `sales_trends_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_trends_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `self_service_orders`
--
ALTER TABLE `self_service_orders`
  ADD CONSTRAINT `fk_selfservice_shop` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `shop_owner_cart_items`
--
ALTER TABLE `shop_owner_cart_items`
  ADD CONSTRAINT `fk_cart_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `shop_owner_cart_list` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_owner_cart_list`
--
ALTER TABLE `shop_owner_cart_list`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_products`
--
ALTER TABLE `shop_products`
  ADD CONSTRAINT `shop_products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_requests`
--
ALTER TABLE `stock_requests`
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_manager`
--
ALTER TABLE `warehouse_manager`
  ADD CONSTRAINT `warehouse_manager_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouse_manager_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse_products`
--
ALTER TABLE `warehouse_products`
  ADD CONSTRAINT `warehouse_products_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouse_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
