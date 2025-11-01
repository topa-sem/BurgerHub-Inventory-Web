--
-- Database: `BurgerHub`
--

-- --------------------------------------------------------

--
-- Table structure for table `USER`
--
CREATE TABLE `USER` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('Admin','Manager') NOT NULL DEFAULT 'Manager',
  `branch` varchar(50) DEFAULT NULL,
  `account_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `USER` (`username`, `email`, `password_hash`, `user_type`, `branch`, `account_balance`)
VALUES
('Admin 1', 'admin@burgerhub.com', 'dd3ab9d20a9c3293a5646aa8d6d5a828', 'Admin', 'HQ', 5000.00);
-- --------------------------------------------------------

--
-- Table structure for table `SUPPLIER`
--
CREATE TABLE `SUPPLIER` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PRODUCT`
--
CREATE TABLE `PRODUCT` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `selling_price` decimal(6,2) NOT NULL,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `INGREDIENT`
--
CREATE TABLE `INGREDIENT` (
  `ingredient_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(10) NOT NULL,
  `current_stock_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`ingredient_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SALE`
--
CREATE TABLE `SALE` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sale_date` date NOT NULL DEFAULT curdate(),
  `total_revenue` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `SALE_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SALE_ITEM`
--
CREATE TABLE `SALE_ITEM` (
  `sale_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `sale_price` decimal(6,2) NOT NULL,
  PRIMARY KEY (`sale_item_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `SALE_ITEM_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `SALE` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `SALE_ITEM_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `PRODUCT` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `RECIPE`
--
CREATE TABLE `RECIPE` (
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(6,2) NOT NULL,
  PRIMARY KEY (`product_id`,`ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `RECIPE_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `PRODUCT` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `RECIPE_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ORDER`
--
CREATE TABLE `ORDER` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `ORDER_ibfk_supplier` (`supplier_id`),
  CONSTRAINT `ORDER_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `ORDER_ibfk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ORDER_ITEM`
--
CREATE TABLE `ORDER_ITEM` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quantity_ordered` decimal(10,2) NOT NULL,
  `unit_price` decimal(6,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `ingredient_id` (`ingredient_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `ORDER_ITEM_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `ORDER` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `ORDER_ITEM_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`),
  CONSTRAINT `ORDER_ITEM_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SUPPLIER_INGREDIENT`
--
CREATE TABLE `SUPPLIER_INGREDIENT` (
  `supplier_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`supplier_id`,`ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `si_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `SUPPLIER` (`supplier_id`) ON DELETE CASCADE,
  CONSTRAINT `si_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `INGREDIENT` (`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;