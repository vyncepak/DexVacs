-- ============================================================
-- VaxCare Database Backup & Schema Definition
-- Exported on: 2026-11-07 16:45:40
-- Database: vaxcare
-- ============================================================

CREATE DATABASE IF NOT EXISTS `vaxcare` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vaxcare`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table structure for table `users`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('Parent','Hospital','Admin') NOT NULL DEFAULT 'Parent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `created_at`) VALUES ('1', 'admin@vaxcare.com', 'admin123', 'Admin User', '0300-1234567', 'Admin', '2026-11-07 15:56:56');
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `created_at`) VALUES ('2', 'Elly@gmail.com', 'parent123', 'elly', '0311-9876543', 'Parent', '2026-11-07 15:56:56');
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `created_at`) VALUES ('3', 'ali@demo.com', 'parent123', 'Ali', '0322-5551234', 'Parent', '2026-11-07 15:56:56');
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `created_at`) VALUES ('4', 'info@abbasi.pk', 'hosp123', 'Abbasi Shaheed Hospital Admin', '021-99260313', 'Hospital', '2026-11-07 15:56:56');
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `created_at`) VALUES ('5', 'info@patel.pk', 'hosp123', 'Patel Hospital Admin', '021-34968641', 'Hospital', '2026-11-07 15:56:56');

-- ------------------------------------------------------------
-- Table structure for table `hospitals`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `hospitals`;
CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `hospitals`
INSERT INTO `hospitals` (`id`, `name`, `location`, `phone`, `status`, `created_at`) VALUES ('1', 'Abbasi Shaheed Hospital', 'Paposh Nagar, Karachi', '021-99260313', 'Active', '2026-11-07 15:56:56');
INSERT INTO `hospitals` (`id`, `name`, `location`, `phone`, `status`, `created_at`) VALUES ('2', 'Patel Hospital', 'Gulshan-e-Iqbal, Karachi', '021-34968641', 'Active', '2026-11-07 15:56:56');
INSERT INTO `hospitals` (`id`, `name`, `location`, `phone`, `status`, `created_at`) VALUES ('3', 'Civil Hospital Karachi', 'Mission Road, Karachi', '021-99215740', 'Active', '2026-11-07 15:56:56');
INSERT INTO `hospitals` (`id`, `name`, `location`, `phone`, `status`, `created_at`) VALUES ('4', 'Liaquat National Hospital', 'Stadium Road, Karachi', '021-34412000', 'Active', '2026-11-07 15:56:56');
INSERT INTO `hospitals` (`id`, `name`, `location`, `phone`, `status`, `created_at`) VALUES ('5', 'Aga Khan University Hospital', 'Stadium Road, Karachi', '021-34930051', 'Active', '2026-11-07 15:56:56');

-- ------------------------------------------------------------
-- Table structure for table `vaccines`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `vaccines`;
CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `vaccines`
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('1', 'BCG', 'Tuberculosis Vaccine', '800.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('2', 'Hepatitis B', 'Hepatitis B Infection Prevention', '1500.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('3', 'Polio (OPV)', 'Oral Polio Vaccine', '300.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('4', 'Polio (IPV)', 'Inactivated Polio Vaccine', '2500.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('5', 'Pentavalent (DPT-HepB-Hib)', '5-in-1 Combination Vaccine', '3500.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('6', 'Pneumococcal (PCV13)', 'Pneumococcal Conjugate Vaccine', '9000.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('7', 'Rotavirus', 'Rotavirus Diarrhea Prevention', '5000.00', 'Available', '2026-11-07 15:56:56');
INSERT INTO `vaccines` (`id`, `name`, `description`, `price`, `status`, `created_at`) VALUES ('8', 'Measles', 'Measles, Mumps & Rubella', '1200.00', 'Unavailable', '2026-11-07 15:56:56');

-- ------------------------------------------------------------
-- Table structure for table `hospital_vaccines`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `hospital_vaccines`;
CREATE TABLE `hospital_vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hosp_vax` (`hospital_id`,`vaccine_id`),
  KEY `vaccine_id` (`vaccine_id`),
  CONSTRAINT `hospital_vaccines_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hospital_vaccines_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `hospital_vaccines`
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('1', '1', '1', '10', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('2', '1', '2', '15', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('3', '1', '3', '20', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('4', '2', '5', '60', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('5', '2', '7', '45', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('6', '2', '8', '90', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('7', '3', '4', '150', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('8', '3', '6', '40', '2026-11-07 19:24:03');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('17', '4', '1', '95', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('18', '4', '2', '110', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('19', '4', '5', '75', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('20', '5', '2', '140', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('21', '5', '6', '120', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('22', '5', '7', '80', '2026-11-07 19:33:51');
INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `stock_quantity`, `created_at`) VALUES ('23', '5', '8', '115', '2026-11-07 19:33:51');

-- ------------------------------------------------------------
-- Table structure for table `children`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `children`;
CREATE TABLE `children` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Male',
  `status` enum('Not vaccinated','Vaccinated') NOT NULL DEFAULT 'Not vaccinated',
  `certificate_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `vaccine_id` (`vaccine_id`),
  KEY `hospital_id` (`hospital_id`),
  CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `children_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `children_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `children`
INSERT INTO `children` (`id`, `name`, `parent_id`, `vaccine_id`, `hospital_id`, `dob`, `gender`, `status`, `certificate_sent`, `created_at`) VALUES ('1', 'Bhoot', '2', '4', '1', '2023-05-12', 'Male', 'Vaccinated', '1', '2026-11-07 15:56:56');
INSERT INTO `children` (`id`, `name`, `parent_id`, `vaccine_id`, `hospital_id`, `dob`, `gender`, `status`, `certificate_sent`, `created_at`) VALUES ('2', 'Huzaifa', '2', '2', '2', '2022-11-04', 'Male', 'Vaccinated', '1', '2026-11-07 15:56:56');
INSERT INTO `children` (`id`, `name`, `parent_id`, `vaccine_id`, `hospital_id`, `dob`, `gender`, `status`, `certificate_sent`, `created_at`) VALUES ('3', 'Khan', '3', '8', '3', '2023-08-20', 'Male', 'Not vaccinated', '0', '2026-11-07 15:56:56');
INSERT INTO `children` (`id`, `name`, `parent_id`, `vaccine_id`, `hospital_id`, `dob`, `gender`, `status`, `certificate_sent`, `created_at`) VALUES ('4', 'nnnnn', '3', '2', '5', NULL, 'Male', 'Vaccinated', '1', '2026-11-07 16:37:36');
INSERT INTO `children` (`id`, `name`, `parent_id`, `vaccine_id`, `hospital_id`, `dob`, `gender`, `status`, `certificate_sent`, `created_at`) VALUES ('5', 'haha', '2', '1', '2', NULL, 'Male', 'Not vaccinated', '0', '2026-11-07 19:30:04');

-- ------------------------------------------------------------
-- Table structure for table `bookings`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `vaccine_id` (`vaccine_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `bookings`
INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `status`, `created_at`) VALUES ('1', '1', '1', '4', '2026-11-15', 'Completed', '2026-11-07 15:56:56');
INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `status`, `created_at`) VALUES ('2', '2', '2', '2', '2026-10-10', 'Completed', '2026-11-07 15:56:56');
INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `status`, `created_at`) VALUES ('3', '3', '3', '8', '2026-11-20', 'Approved', '2026-11-07 15:56:56');
INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `status`, `created_at`) VALUES ('4', '4', '5', '2', '2026-11-07', 'Completed', '2026-11-07 16:37:36');
INSERT INTO `bookings` (`id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `status`, `created_at`) VALUES ('5', '5', '2', '1', '2026-11-03', 'Approved', '2026-11-07 19:30:04');

SET FOREIGN_KEY_CHECKS = 1;
