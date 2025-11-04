-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 09:31 AM
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
-- Database: `nipa_db`
--

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `product_id`, `quantity`, `location`, `status`, `manufacture_date`, `expiry_date`, `created_at`, `updated_at`) VALUES
(8, 11, 100, 'Kinali City', 'Active', '2016-02-20', '2038-06-20', '2025-10-28 01:50:11', '2025-10-29 12:35:23'),
(9, 12, 200, 'Buyun City', 'Active', '2030-10-20', '3032-12-20', '2025-10-28 02:06:08', '2025-10-28 02:08:52');

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `imageUrl`, `created_at`, `updated_at`) VALUES
(11, 'lambanog', 'alcohol drink', 'Lambanóg is a traditional Filipino distilled palm liquor. It is an alcoholic liquor made from the distillation of naturally fermented sap (tubâ) from palm trees such as sugar palm, coconut, or nipa.', 'uploads/690020921029a_OIP.webp', '2025-10-28 01:46:58', '2025-10-29 02:53:05'),
(12, 'Suka ng Nipa', 'vinegar', 'Nipa palm vinegar, also known as sukang sasâ or sukang nipa, is a traditional Filipino vinegar made from the sap of the nipa palm (Nypa fruticans).', 'uploads/690024bfd66d2_OIP (1).webp', '2025-10-28 02:04:47', '2025-10-28 02:04:47'),
(13, 'Nipa Roofing', 'Roofing', 'Nipa roofing refers to a traditional or synthetic roofing technique that uses the leaves of the nipa palm (Nypa fruticans) to create lightweight, eco-friendly, and breathable roof coverings, common in tropical regions like the Philippines and Southeast Asia. Nipa roofs are valued for their natural insulation, cost-effectiveness, and local availability, but typically require regular maintenance and have a shorter lifespan than metal or concrete alternatives unless using newer synthetic solutions.', 'uploads/69020b1672093_nipa roofing.jpg', '2025-10-29 12:39:50', '2025-10-29 12:40:18'),
(14, 'Tagapulot', 'Honey nipa', 'The term \"tagapulot nipa\" refers to a sweet syrup made from the sap of the nipa palm, which is commonly found in the Philippines, especially in regions where nipa palm trees grow abundantly. This syrup is locally produced and valued in traditional dishes and snacks, particularly in Ilocano cuisine, where it is also called \"issi\" in some areas. The tagapulot is cooked down to achieve a thick, rich sweetness similar to molasses and is used as a sweetener or as a component in native delicacies.', 'uploads/69020c9400fcf_Screenshot 2025-10-29 204442.png', '2025-10-29 12:46:12', '2025-10-29 12:46:12');

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `batch_id`, `product_id`, `quantity_sold`, `remarks`, `transaction_date`, `created_at`, `updated_at`) VALUES
(8, 8, 11, 100, 'Sold to songgo', '2025-10-28 01:55:13', '2025-10-28 01:55:13', '2025-10-28 01:55:13'),
(9, 9, 12, 200, 'Sold to songgoloid', '2025-10-28 02:06:55', '2025-10-28 02:06:55', '2025-10-28 02:06:55');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `created_at`, `updated_at`) VALUES
(1, 'admin@nipa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '2025-10-11 02:01:45', '2025-10-11 02:01:45'),
(3, 'apagarigan5@gmail.com', '$2y$10$KO5BnLwfYB7X./0itYbfrOwjggelI2r.j8XdsJ0RMFVP4FGtiR.Za', NULL, '2025-10-11 04:41:11', '2025-10-11 04:41:11'),
(5, 'dan@gmail.com', '$2a$11$nlzWFF4M85SDwHLmCWtJHOhpPyx2Wl5P2ixYqPeJW8FmX543w0g06', NULL, '2025-10-25 05:17:26', '2025-10-25 05:17:26'),
(6, 'pau@gmail.com', '$2a$11$OsVKiD5VNWp4Mi.d6tnsaeEKa56W/eGLQ27vRMmaQzivkeGZYQrRW', NULL, '2025-10-27 23:25:19', '2025-10-27 23:25:19');

-- --------------------------------------------------------

--
-- Structure for view `inventory_summary`
--
DROP TABLE IF EXISTS `inventory_summary`;

DROP VIEW IF EXISTS `inventory_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_summary`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, `p`.`category` AS `category`, coalesce(sum(`b`.`quantity`),0) AS `total_batch_quantity`, coalesce(sum(`t`.`quantity_sold`),0) AS `total_sold`, coalesce(sum(`b`.`quantity`),0) - coalesce(sum(`t`.`quantity_sold`),0) AS `available_stock` FROM ((`products` `p` left join `batches` `b` on(`p`.`id` = `b`.`product_id`)) left join `transactions` `t` on(`p`.`id` = `t`.`product_id`)) GROUP BY `p`.`id`, `p`.`name`, `p`.`category` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
