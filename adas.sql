-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 09, 2025 at 06:24 PM
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
-- Database: `adas`
--

-- --------------------------------------------------------

--
-- Table structure for table `INGREDIENT`
--

CREATE TABLE `INGREDIENT` (
  `ingredient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `INGREDIENT`
--

INSERT INTO `INGREDIENT` (`ingredient_id`, `user_id`, `name`, `unit`) VALUES
(1, 1, 'Beef Patty', 'pcs'),
(2, 1, 'Chicken Patty', 'pcs'),
(3, 1, 'Burger Bun', 'pcs'),
(4, 1, 'Lettuce', 'kg'),
(5, 1, 'Tomato', 'kg'),
(6, 1, 'Cheddar Cheese', 'slice'),
(9, 1, 'Mayonnaise', 'L'),
(10, 1, 'Ketchup', 'L'),
(11, 1, 'French Fries', 'kg'),
(12, 1, 'Cola Syrup', 'L'),
(13, 1, 'Chicken Tender', 'pcs'),
(14, 1, 'Egg', 'pcs'),
(20, 1, 'mustard', 'L');

-- --------------------------------------------------------

--
-- Table structure for table `INGREDIENT_STOCK`
--

CREATE TABLE `INGREDIENT_STOCK` (
  `stock_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_stock_quantity` decimal(10,2) DEFAULT 0.00,
  `low_stock_threshold` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `INGREDIENT_STOCK`
--

INSERT INTO `INGREDIENT_STOCK` (`stock_id`, `ingredient_id`, `user_id`, `current_stock_quantity`, `low_stock_threshold`) VALUES
(1, 10, 1, 0.00, 3),
(8, 10, 2, 0.00, 1),
(18, 13, 1, 0.00, 10),
(19, 2, 2, 0.00, 10),
(28, 3, 1, 0.00, 10),
(29, 1, 1, 0.00, 10),
(31, 6, 1, 0.00, 10),
(32, 2, 1, 0.00, 10),
(34, 14, 1, 0.00, 10),
(35, 11, 1, 0.00, 10),
(37, 4, 1, 0.00, 10),
(50, 20, 1, 0.00, 12);

-- --------------------------------------------------------

--
-- Table structure for table `ORDER`
--

CREATE TABLE `ORDER` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ORDER`
--

INSERT INTO `ORDER` (`order_id`, `user_id`, `supplier_id`, `order_date`, `total_amount`, `status`) VALUES
(1, 1, NULL, '2025-10-31 08:35:35', 0.00, 'Cancelled'),
(2, 1, 4, '2025-11-01 00:12:24', 340.00, 'Completed'),
(3, 1, 7, '2025-11-01 00:13:17', 300.00, 'Completed'),
(4, 1, 8, '2025-11-02 17:36:12', 16.00, 'Completed'),
(5, 1, 5, '2025-11-02 17:47:25', 120.00, 'Completed'),
(6, 1, 5, '2025-11-02 17:47:51', 30.00, 'Completed'),
(7, 1, 6, '2025-11-02 17:54:12', 150.00, 'Completed'),
(8, 1, 7, '2025-11-02 18:00:35', 150.00, 'Completed'),
(9, 1, 6, '2025-11-02 18:01:24', 50.00, 'Completed'),
(10, 1, 6, '2025-11-02 18:15:01', 61.00, 'Completed'),
(11, 1, 8, '2025-11-02 18:16:24', 1464.00, 'Completed'),
(12, 1, 6, '2025-11-02 18:17:55', 50.00, 'Completed'),
(13, 1, 6, '2025-11-02 18:18:31', 50.00, 'Completed'),
(14, 1, 6, '2025-11-02 18:18:59', 0.00, 'Completed'),
(15, 1, 7, '2025-11-02 18:20:38', 750.00, 'Completed'),
(16, 1, 6, '2025-11-02 18:22:15', 50.00, 'Completed'),
(17, 1, 8, '2025-11-02 18:22:53', 130.00, 'Completed'),
(18, 1, 8, '2025-11-02 22:32:44', 15.00, 'Completed'),
(19, 1, 7, '2025-11-02 22:33:30', 3000000.00, 'Completed'),
(20, 1, 7, '2025-11-02 22:40:50', 75.00, 'Completed'),
(21, 1, 7, '2025-11-03 10:07:02', 150.00, 'Completed'),
(22, 1, 5, '2025-11-03 10:09:44', 10.00, 'Completed'),
(23, 1, 8, '2025-11-03 10:09:59', 5.00, 'Completed'),
(24, 1, 8, '2025-11-03 10:14:41', 5.00, 'Completed'),
(25, 1, 4, '2025-11-03 10:15:03', 70.00, 'Completed'),
(26, 1, 7, '2025-11-03 10:18:50', 150.00, 'Completed'),
(27, 1, 7, '2025-11-03 10:51:22', 0.00, 'Completed'),
(28, 2, 7, '2025-11-03 15:45:11', 15.00, 'Cancelled'),
(29, 2, 4, '2025-11-03 16:06:52', 10.00, 'Pending'),
(30, 2, 7, '2025-11-03 16:07:12', 15.00, 'Completed'),
(31, 1, 7, '2025-11-03 16:07:46', 15.00, 'Completed'),
(32, 1, 8, '2025-11-03 16:11:36', 0.00, 'Cancelled'),
(33, 2, 7, '2025-11-03 16:49:32', 0.00, 'Pending'),
(34, 2, 5, '2025-11-03 16:50:01', 2000.00, 'Completed'),
(35, 2, 4, '2025-11-03 16:55:29', 84.00, 'Completed'),
(36, 2, 7, '2025-11-03 16:55:51', 1500.00, 'Pending'),
(37, 1, 8, '2025-11-03 16:56:59', 50.00, 'Completed'),
(38, 2, 8, '2025-11-03 17:00:12', 50.00, 'Completed'),
(39, 2, 7, '2025-11-03 19:02:20', 15.00, 'Completed'),
(40, 1, 5, '2025-11-03 21:40:17', 100.00, 'Completed'),
(41, 1, 8, '2025-11-03 21:42:29', 1200.00, 'Completed'),
(42, 1, 6, '2025-11-09 22:46:12', 0.00, 'Cancelled'),
(43, 1, 4, '2025-11-09 22:46:24', 140.00, 'Completed'),
(44, 1, NULL, '2025-11-09 22:47:25', 0.00, 'Cancelled'),
(45, 1, 8, '2025-11-09 22:48:17', 1200.00, 'Completed'),
(46, 2, 8, '2025-11-09 22:58:08', 120.00, 'Completed'),
(47, 1, 7, '2025-11-09 23:04:01', 0.00, 'Cancelled'),
(48, 2, 4, '2025-11-09 23:04:30', 0.00, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `ORDER_ITEM`
--

CREATE TABLE `ORDER_ITEM` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quantity_ordered` decimal(10,2) NOT NULL,
  `unit_price` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ORDER_ITEM`
--

INSERT INTO `ORDER_ITEM` (`order_item_id`, `order_id`, `ingredient_id`, `supplier_id`, `quantity_ordered`, `unit_price`) VALUES
(1, 2, 10, 4, 20.00, 7.00),
(2, 2, 9, 4, 20.00, 10.00),
(3, 3, 12, 7, 20.00, 15.00),
(4, 4, 6, 8, 32.00, 0.50),
(5, 5, 1, 5, 30.00, 1.00),
(6, 5, 2, 5, 30.00, 1.00),
(7, 5, 13, 5, 30.00, 2.00),
(8, 6, 2, 5, 30.00, 1.00),
(9, 7, 1, 6, 300.00, 0.50),
(10, 8, 12, 7, 10.00, 15.00),
(11, 9, 1, 6, 100.00, 0.50),
(12, 10, 1, 6, 122.00, 0.50),
(13, 11, 11, 8, 122.00, 12.00),
(14, 12, 1, 6, 100.00, 0.50),
(15, 13, 1, 6, 100.00, 0.50),
(16, 15, 12, 7, 50.00, 15.00),
(17, 16, 3, 6, 100.00, 0.50),
(19, 17, 4, 8, 10.00, 8.00),
(20, 17, 5, 8, 10.00, 5.00),
(21, 18, 14, 8, 30.00, 0.50),
(22, 19, 12, 7, 200000.00, 15.00),
(23, 20, 12, 7, 5.00, 15.00),
(24, 21, 12, 7, 10.00, 15.00),
(25, 22, 2, 5, 10.00, 1.00),
(26, 23, 14, 8, 10.00, 0.50),
(27, 24, 14, 8, 10.00, 0.50),
(28, 25, 10, 4, 10.00, 7.00),
(29, 26, 12, 7, 10.00, 15.00),
(30, 28, 12, 7, 1.00, 15.00),
(31, 29, 9, 4, 1.00, 10.00),
(32, 30, 12, 7, 1.00, 15.00),
(33, 31, 12, 7, 1.00, 15.00),
(34, 34, 13, 5, 1000.00, 2.00),
(35, 35, 10, 4, 12.00, 7.00),
(36, 36, 12, 7, 100.00, 15.00),
(37, 37, 14, 8, 100.00, 0.50),
(38, 38, 14, 8, 100.00, 0.50),
(39, 39, 12, 7, 1.00, 15.00),
(40, 40, 1, 5, 100.00, 1.00),
(41, 41, 11, 8, 100.00, 12.00),
(42, 43, 10, 4, 20.00, 7.00),
(43, 45, 11, 8, 100.00, 12.00),
(44, 46, 11, 8, 10.00, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `PRODUCT`
--

CREATE TABLE `PRODUCT` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `selling_price` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `PRODUCT`
--

INSERT INTO `PRODUCT` (`product_id`, `name`, `selling_price`) VALUES
(1, 'Cheese Burger', 6.50),
(2, 'Crispy Chicken Burger', 8.00),
(3, 'Chicken Burger', 5.50),
(4, 'Beef Burger', 5.50),
(5, 'Benjo Burger', 3.00),
(6, 'French Fries', 5.00),
(7, 'Cola', 2.00),
(8, 'Double Crispy Chicken Burger', 12.00),
(10, 'sandwich', 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `RECIPE`
--

CREATE TABLE `RECIPE` (
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `RECIPE`
--

INSERT INTO `RECIPE` (`product_id`, `ingredient_id`, `quantity_required`) VALUES
(1, 1, 1.00),
(1, 3, 1.00),
(1, 4, 0.02),
(1, 5, 0.04),
(1, 6, 1.00),
(1, 9, 0.01),
(1, 10, 0.01),
(2, 3, 1.00),
(2, 4, 0.02),
(2, 5, 0.04),
(2, 6, 1.00),
(2, 9, 0.01),
(2, 10, 0.01),
(2, 13, 1.00),
(3, 2, 1.00),
(3, 3, 1.00),
(3, 4, 0.02),
(3, 5, 0.04),
(3, 9, 0.01),
(3, 10, 0.01),
(4, 1, 1.00),
(4, 3, 1.00),
(4, 4, 0.02),
(4, 5, 0.04),
(4, 9, 0.01),
(4, 10, 0.01),
(5, 3, 1.00),
(5, 4, 0.02),
(5, 5, 0.04),
(5, 9, 0.01),
(5, 10, 0.01),
(5, 14, 1.00),
(6, 11, 0.15),
(7, 12, 0.04),
(8, 3, 1.00),
(8, 4, 0.02),
(8, 5, 0.04),
(8, 6, 2.00),
(8, 9, 0.01),
(8, 10, 0.01),
(8, 13, 2.00),
(10, 13, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `SALE`
--

CREATE TABLE `SALE` (
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` date NOT NULL DEFAULT curdate(),
  `total_revenue` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `SALE`
--

INSERT INTO `SALE` (`sale_id`, `user_id`, `sale_date`, `total_revenue`) VALUES
(1, 1, '2025-11-02', 2.00),
(2, 1, '2025-11-02', 55.00),
(3, 1, '2025-11-02', 10000.00),
(4, 1, '2025-11-03', 5.50),
(5, 1, '2025-11-03', 5.50),
(6, 2, '2025-11-03', 3.00),
(7, 1, '2025-11-03', 5.50),
(8, 2, '2025-11-03', 2.00),
(9, 1, '2025-11-03', 100.00),
(10, 1, '2025-11-08', 5.00),
(11, 1, '2025-11-09', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `SALE_ITEM`
--

CREATE TABLE `SALE_ITEM` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `sale_price` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `SALE_ITEM`
--

INSERT INTO `SALE_ITEM` (`sale_item_id`, `sale_id`, `product_id`, `quantity_sold`, `sale_price`) VALUES
(1, 1, 7, 1, 2.00),
(2, 2, 3, 10, 5.50),
(3, 3, 7, 5000, 2.00),
(4, 4, 4, 1, 5.50),
(5, 5, 4, 1, 5.50),
(6, 6, 5, 1, 3.00),
(7, 7, 3, 1, 5.50),
(8, 8, 7, 1, 2.00),
(9, 9, 6, 20, 5.00),
(10, 10, 6, 1, 5.00),
(11, 11, 6, 1, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `SUPPLIER`
--

CREATE TABLE `SUPPLIER` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `SUPPLIER`
--

INSERT INTO `SUPPLIER` (`supplier_id`, `name`, `contact_email`, `phone`) VALUES
(4, 'Adabi Berhad', 'adabi.sdn@gmail.com', '011-37443422'),
(5, 'Ramly Processing Sdn. Bhd', 'ramly.co@gmail.com', '011-37446593'),
(6, 'Roti Boy Bakery', 'rotiboybakery@gmail.com', '013-34449053'),
(7, 'Coca-Cola Drinks and Co.', 'cocacola@gmail.com', '012-23446776'),
(8, 'Jaya Grocer Market', 'jayagrocer@gmail.com', '016-77444486');

-- --------------------------------------------------------

--
-- Table structure for table `SUPPLIER_INGREDIENT`
--

CREATE TABLE `SUPPLIER_INGREDIENT` (
  `supplier_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `SUPPLIER_INGREDIENT`
--

INSERT INTO `SUPPLIER_INGREDIENT` (`supplier_id`, `ingredient_id`, `price`) VALUES
(4, 9, 10.00),
(4, 10, 7.00),
(5, 1, 1.00),
(5, 2, 1.00),
(5, 13, 2.00),
(6, 3, 0.50),
(7, 12, 15.00),
(8, 4, 8.00),
(8, 5, 5.00),
(8, 6, 0.50),
(8, 11, 12.00),
(8, 14, 0.50);

-- --------------------------------------------------------

--
-- Table structure for table `USER`
--

CREATE TABLE `USER` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('Admin','Manager') NOT NULL DEFAULT 'Manager',
  `branch` varchar(50) DEFAULT NULL,
  `account_balance` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `USER`
--

INSERT INTO `USER` (`user_id`, `username`, `email`, `password_hash`, `user_type`, `branch`, `account_balance`) VALUES
(1, 'Admin 1', 'admin@burgerhub.com', 'e00cf25ad42683b3df678c61f42c6bda', 'Admin', 'HQ', 12328.50),
(2, 'Ahmad K', 'ahmadk@gmail.com', '202cb962ac59075b964b07152d234b70', 'Manager', 'Kajang', 718.00);

-- --------------------------------------------------------

--
-- Table structure for table `USER_INGREDIENT_STOCK`
--

CREATE TABLE `USER_INGREDIENT_STOCK` (
  `user_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `current_stock_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `USER_INGREDIENT_STOCK`
--

INSERT INTO `USER_INGREDIENT_STOCK` (`user_id`, `ingredient_id`, `current_stock_quantity`, `low_stock_threshold`) VALUES
(1, 1, 100.00, 10),
(1, 2, 0.00, 10),
(1, 3, 0.00, 10),
(1, 6, 0.00, 10),
(1, 9, 0.00, 10),
(1, 10, 20.00, 10),
(1, 11, 196.70, 11),
(1, 12, 0.00, 10),
(1, 13, 0.00, 10),
(2, 11, 10.00, 10),
(2, 12, 1.96, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `INGREDIENT`
--
ALTER TABLE `INGREDIENT`
  ADD PRIMARY KEY (`ingredient_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_ingredient_user` (`user_id`);

--
-- Indexes for table `INGREDIENT_STOCK`
--
ALTER TABLE `INGREDIENT_STOCK`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `unique_ingredient_user` (`ingredient_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ORDER`
--
ALTER TABLE `ORDER`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ORDER_ibfk_supplier` (`supplier_id`);

--
-- Indexes for table `ORDER_ITEM`
--
ALTER TABLE `ORDER_ITEM`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `PRODUCT`
--
ALTER TABLE `PRODUCT`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `RECIPE`
--
ALTER TABLE `RECIPE`
  ADD PRIMARY KEY (`product_id`,`ingredient_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `SALE`
--
ALTER TABLE `SALE`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `SALE_ITEM`
--
ALTER TABLE `SALE_ITEM`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `SUPPLIER`
--
ALTER TABLE `SUPPLIER`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `SUPPLIER_INGREDIENT`
--
ALTER TABLE `SUPPLIER_INGREDIENT`
  ADD PRIMARY KEY (`supplier_id`,`ingredient_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `USER`
--
ALTER TABLE `USER`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `USER_INGREDIENT_STOCK`
--
ALTER TABLE `USER_INGREDIENT_STOCK`
  ADD PRIMARY KEY (`user_id`,`ingredient_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `INGREDIENT`
--
ALTER TABLE `INGREDIENT`
  MODIFY `ingredient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `INGREDIENT_STOCK`
--
ALTER TABLE `INGREDIENT_STOCK`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `ORDER`
--
ALTER TABLE `ORDER`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `ORDER_ITEM`
--
ALTER TABLE `ORDER_ITEM`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `PRODUCT`
--
ALTER TABLE `PRODUCT`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `SALE`
--
ALTER TABLE `SALE`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `SALE_ITEM`
--
ALTER TABLE `SALE_ITEM`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `SUPPLIER`
--
ALTER TABLE `SUPPLIER`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `USER`
--
ALTER TABLE `USER`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `INGREDIENT`
--
ALTER TABLE `INGREDIENT`
  ADD CONSTRAINT `fk_ingredient_user` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `INGREDIENT_STOCK`
--
ALTER TABLE `INGREDIENT_STOCK`
  ADD CONSTRAINT `INGREDIENT_STOCK_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `INGREDIENT_STOCK_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ORDER`
--
ALTER TABLE `ORDER`
  ADD CONSTRAINT `ORDER_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ORDER_ibfk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `ORDER_ITEM`
--
ALTER TABLE `ORDER_ITEM`
  ADD CONSTRAINT `ORDER_ITEM_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `ORDER` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ORDER_ITEM_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`),
  ADD CONSTRAINT `ORDER_ITEM_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`);

--
-- Constraints for table `RECIPE`
--
ALTER TABLE `RECIPE`
  ADD CONSTRAINT `RECIPE_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `PRODUCT` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `RECIPE_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE;

--
-- Constraints for table `SALE`
--
ALTER TABLE `SALE`
  ADD CONSTRAINT `SALE_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `SALE_ITEM`
--
ALTER TABLE `SALE_ITEM`
  ADD CONSTRAINT `SALE_ITEM_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `SALE` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `SALE_ITEM_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `PRODUCT` (`product_id`);

--
-- Constraints for table `SUPPLIER_INGREDIENT`
--
ALTER TABLE `SUPPLIER_INGREDIENT`
  ADD CONSTRAINT `si_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `si_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE;

--
-- Constraints for table `USER_INGREDIENT_STOCK`
--
ALTER TABLE `USER_INGREDIENT_STOCK`
  ADD CONSTRAINT `user_ingredient_stock_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_ingredient_stock_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
