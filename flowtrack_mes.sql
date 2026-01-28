-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 03:56 PM
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
-- Database: `flowtrack_mes`
--

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `Batch_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_Type` varchar(100) DEFAULT NULL,
  `Start_Time` datetime DEFAULT NULL,
  `End_Time` datetime DEFAULT NULL,
  `Status` enum('Running','Completed','Failed') DEFAULT 'Running',
  `Quantity` int(11) DEFAULT 0,
  `Fabric` varchar(150) DEFAULT 'Standard Cotton',
  `Trims` varchar(150) DEFAULT 'Standard Thread',
  `Variant_Options` varchar(100) DEFAULT 'S-M-L Mixed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch`
--

INSERT INTO `batch` (`Batch_ID`, `Order_ID`, `Product_Type`, `Start_Time`, `End_Time`, `Status`, `Quantity`, `Fabric`, `Trims`, `Variant_Options`) VALUES
(1, 1001, 'Denim Jeans', '2023-10-25 08:00:00', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(2, 1002, 'Cotton Shirt', '2023-10-24 09:00:00', '2023-10-24 17:00:00', 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(3, 1003, 'Jacket', '2023-10-20 08:00:00', '2023-10-20 12:00:00', 'Failed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(4, 201, 'Premium Polo (Perfect)', '2026-01-13 19:16:45', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(5, 202, 'Standard Tee (Okay)', '2026-01-13 19:16:45', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(6, 203, 'Discount Hoodie (Bad)', '2026-01-13 19:16:45', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(7, 205, 'Socks', '2026-01-13 14:21:50', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(8, 1005, 'T-Shirt', '2026-01-13 14:47:02', NULL, 'Completed', 0, 'Standard Cotton', 'Standard Thread', 'S-M-L Mixed'),
(9, 7781, 'Baggy Jeans', '2026-01-13 15:49:36', NULL, 'Completed', 45, 'Cotton Fabric (2250 Meters)', 'Polyester Thread (675 Spools)', 'L/Black'),
(10, 71, 'Socks', '2026-01-13 16:47:25', NULL, 'Completed', 30, 'Cotton Fabric (300 Meters)', 'Polyester Thread (150 Spools)', 'L/Black'),
(11, 1342, 'Shirts', '2026-01-14 02:19:13', NULL, 'Completed', 45, 'Cotton Fabric (135 Meters)', 'Plastic Buttons (225 Pieces)', 'L/Black'),
(12, 1342, 'Shirts', '2026-01-14 02:20:37', NULL, 'Completed', 45, 'Cotton Fabric (135 Meters)', 'Plastic Buttons (225 Pieces)', 'L/Black'),
(14, 1100, 'Denim Jacket', '2026-01-16 11:12:45', NULL, 'Completed', 45, 'Cotton Fabric', 'Plastic Buttons', 'L/Black'),
(17, 6842, 'Socks', '2026-01-18 18:59:21', NULL, 'Completed', 10, 'Cotton Fabric', 'Polyester Thread', 'L/Black'),
(18, 1232, 'Socks', '2026-01-18 19:08:29', NULL, 'Completed', 10, 'Cotton Fabric', 'Plastic Buttons', 'L/Black'),
(19, 2066, 'Baggy Jeans', '2026-01-18 19:46:22', NULL, 'Completed', 45, 'Cotton Fabric', 'Plastic Buttons', 'L/Black'),
(22, 8834, 'Shirts', '2026-01-19 03:45:50', NULL, 'Completed', 10, 'Cotton Fabric', 'Polyester Thread', 'L/Black'),
(23, 1413, 'Baggy Jeans', '2026-01-19 09:00:56', NULL, 'Completed', 45, 'Cotton Fabric', 'Plastic Buttons', 'L/Black'),
(32, 1937, 'Socks', '2026-01-27 06:56:34', NULL, 'Completed', 15, 'Cotton Fabric', 'Polyester Thread', 'L/Black'),
(34, 1651, 'Socks', '2026-01-27 08:12:20', NULL, 'Completed', 14, 'Cotton Fabric', 'Polyester Thread', 'L/Black'),
(39, 5671, 'Baggy Jeans', '2026-01-28 09:25:59', NULL, 'Running', 45, 'Cotton Fabric', 'Plastic Buttons', 'L/Black'),
(40, 2364, 'Baggy Jeans', '2026-01-28 09:35:01', NULL, 'Running', 50, 'Cotton Fabric', 'Polyester Thread', 'L/Black');

-- --------------------------------------------------------

--
-- Table structure for table `defect_log`
--

CREATE TABLE `defect_log` (
  `Defect_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Stage_Name` varchar(50) DEFAULT NULL,
  `Defect_Type` varchar(100) DEFAULT NULL,
  `Found_By_Worker_ID` int(11) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Action_Taken` enum('Rework','Reject') DEFAULT NULL,
  `Log_Date` datetime DEFAULT current_timestamp(),
  `Status` enum('Open','Fixed','Scrapped') DEFAULT 'Open',
  `Assigned_To_Worker_ID` int(11) DEFAULT NULL,
  `Log_Time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `defect_log`
--

INSERT INTO `defect_log` (`Defect_ID`, `Batch_ID`, `Stage_Name`, `Defect_Type`, `Found_By_Worker_ID`, `Quantity`, `Action_Taken`, `Log_Date`, `Status`, `Assigned_To_Worker_ID`, `Log_Time`) VALUES
(1, 34, 'Finishing', 'Wrong Pattern', 53, 1, 'Rework', '2026-01-28 07:30:07', 'Fixed', 53, '2026-01-28 01:30:07'),
(7, 39, 'Cutting', 'Stain', 64, 1, 'Rework', '2026-01-28 09:27:36', 'Fixed', 64, '2026-01-28 03:27:36'),
(8, 39, 'Sewing', 'Wrong Pattern', 64, 2, 'Rework', '2026-01-28 09:31:36', 'Fixed', 64, '2026-01-28 03:31:36');

-- --------------------------------------------------------

--
-- Table structure for table `finished_goods`
--

CREATE TABLE `finished_goods` (
  `Item_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Grade` enum('A','B','C') DEFAULT NULL,
  `Completed_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finished_goods`
--

INSERT INTO `finished_goods` (`Item_ID`, `Batch_ID`, `Quantity`, `Grade`, `Completed_Date`) VALUES
(1, 2, NULL, 'A', '2023-10-24'),
(2, 1, NULL, 'A', '2026-01-13'),
(3, 7, NULL, 'C', '2026-01-13'),
(4, 8, NULL, 'B', '2026-01-13'),
(5, 4, NULL, 'A', '2026-01-13'),
(6, 5, NULL, 'B', '2026-01-13'),
(7, 6, NULL, 'C', '2026-01-13'),
(8, 9, NULL, 'B', '2026-01-13'),
(9, 10, NULL, 'B', '2026-01-13'),
(10, 11, NULL, 'B', '2026-01-14'),
(11, 12, NULL, 'B', '2026-01-14'),
(12, 14, NULL, 'B', '2026-01-16'),
(13, 17, NULL, 'C', '2026-01-19'),
(14, 18, NULL, 'C', '2026-01-19'),
(15, 19, 43, 'B', '2026-01-19'),
(16, 23, 43, 'B', '2026-01-19'),
(17, 22, 6, 'C', '2026-01-25'),
(18, 32, 15, 'A', '2026-01-27'),
(19, 34, 13, 'C', '2026-01-28');

-- --------------------------------------------------------

--
-- Table structure for table `machine`
--

CREATE TABLE `machine` (
  `Machine_ID` int(11) NOT NULL,
  `Machine_Type` varchar(100) DEFAULT NULL,
  `Status` enum('Active','Idle','Maintenance') DEFAULT 'Active',
  `Last_Maintenance_Date` date DEFAULT NULL,
  `Breakdown_Count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machine`
--

INSERT INTO `machine` (`Machine_ID`, `Machine_Type`, `Status`, `Last_Maintenance_Date`, `Breakdown_Count`) VALUES
(1, 'Industrial Cutter', 'Active', '2023-10-01', 0),
(2, 'Sewing Station A', 'Active', '2023-09-15', 2),
(3, 'Sewing Station B', 'Maintenance', '2023-10-05', 5),
(4, 'Packaging Unit', 'Idle', '2023-08-20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_log`
--

CREATE TABLE `maintenance_log` (
  `Log_ID` int(11) NOT NULL,
  `Machine_ID` int(11) DEFAULT NULL,
  `Issue_Description` text DEFAULT NULL,
  `Fix_Description` text DEFAULT NULL,
  `Date` datetime DEFAULT current_timestamp(),
  `Technician` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_log`
--

INSERT INTO `maintenance_log` (`Log_ID`, `Machine_ID`, `Issue_Description`, `Fix_Description`, `Date`, `Technician`) VALUES
(1, 3, 'Motor overheating', 'Replaced cooling fan', '2026-01-10 10:30:58', 'Technician A');

-- --------------------------------------------------------

--
-- Table structure for table `material`
--

CREATE TABLE `material` (
  `Material_ID` int(11) NOT NULL,
  `Material_Name` varchar(100) NOT NULL,
  `Unit` varchar(20) DEFAULT NULL,
  `Current_Stock` decimal(10,2) DEFAULT 0.00,
  `Category` enum('Fabric','Trim') DEFAULT 'Fabric'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material`
--

INSERT INTO `material` (`Material_ID`, `Material_Name`, `Unit`, `Current_Stock`, `Category`) VALUES
(1, 'Cotton Fabric', 'Meters', 2087.00, 'Fabric'),
(2, 'Polyester Thread', 'Spools', 540.40, 'Trim'),
(3, 'Plastic Buttons', 'Pieces', 6485.00, 'Trim'),
(4, 'Zipper (Metal)', 'Pieces', 1410.00, 'Trim');

-- --------------------------------------------------------

--
-- Table structure for table `material_usage`
--

CREATE TABLE `material_usage` (
  `Usage_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Material_ID` int(11) DEFAULT NULL,
  `Quantity_Used` decimal(10,2) DEFAULT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material_usage`
--

INSERT INTO `material_usage` (`Usage_ID`, `Batch_ID`, `Material_ID`, `Quantity_Used`, `Date`) VALUES
(1, 1, 1, 150.00, '2023-10-25'),
(2, 1, 2, 5.00, '2023-10-25'),
(3, 9, 1, 2250.00, '2026-01-13'),
(4, 9, 2, 675.00, '2026-01-13'),
(5, 10, 1, 300.00, '2026-01-13'),
(6, 10, 2, 150.00, '2026-01-13'),
(7, 11, 1, 135.00, '2026-01-14'),
(8, 11, 3, 225.00, '2026-01-14'),
(9, 12, 1, 135.00, '2026-01-14'),
(10, 12, 3, 225.00, '2026-01-14'),
(13, 14, 1, 90.00, '2026-01-16'),
(14, 14, 3, 225.00, '2026-01-16'),
(19, 17, 1, 10.00, '2026-01-18'),
(20, 17, 2, 10.00, '2026-01-18'),
(21, 18, 1, 10.00, '2026-01-18'),
(22, 18, 3, 50.00, '2026-01-18'),
(23, 19, 1, 45.00, '2026-01-18'),
(24, 19, 3, 225.00, '2026-01-18'),
(29, 22, 1, 20.00, '2026-01-19'),
(30, 22, 2, 10.00, '2026-01-19'),
(31, 23, 1, 45.00, '2026-01-19'),
(32, 23, 3, 225.00, '2026-01-19');

-- --------------------------------------------------------

--
-- Table structure for table `ncr`
--

CREATE TABLE `ncr` (
  `NCR_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Issue` text DEFAULT NULL,
  `Severity` enum('Low','Medium','High','Critical') DEFAULT NULL,
  `Reported_By` varchar(100) DEFAULT NULL,
  `Suggested_Action` text DEFAULT NULL,
  `Status` enum('Open','Resolved','Pending') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ncr`
--

INSERT INTO `ncr` (`NCR_ID`, `Batch_ID`, `Issue`, `Severity`, `Reported_By`, `Suggested_Action`, `Status`) VALUES
(1, 3, 'Fabric low quality, tearing at seams', 'Critical', 'Jamal Hossain', 'Return fabric to supplier', 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `production_stage`
--

CREATE TABLE `production_stage` (
  `Stage_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Stage_Name` varchar(100) DEFAULT NULL,
  `Target_Time` decimal(10,2) DEFAULT NULL,
  `Actual_Time` decimal(10,2) DEFAULT NULL,
  `Remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_stage`
--

INSERT INTO `production_stage` (`Stage_ID`, `Batch_ID`, `Stage_Name`, `Target_Time`, `Actual_Time`, `Remarks`) VALUES
(1, 1, 'Cutting', 60.00, 55.00, 'Completed ahead of schedule'),
(2, 1, 'Sewing', 120.00, 130.00, 'Slight delay due to thread break'),
(3, 9, 'Cutting', 60.00, 5.00, 'On Time'),
(4, 9, 'Sewing', 120.00, 5.00, 'On Time'),
(5, 9, 'Finishing', 45.00, 5.00, 'On Time'),
(6, 10, 'Cutting', 60.00, 1.00, 'On Time'),
(7, 10, 'Sewing', 120.00, 1.00, 'On Time'),
(8, 10, 'Finishing', 45.00, 1.00, 'On Time'),
(9, 11, 'Cutting', 60.00, 0.00, NULL),
(10, 11, 'Sewing', 120.00, 0.00, NULL),
(11, 11, 'Finishing', 45.00, 0.00, NULL),
(12, 12, 'Cutting', 60.00, 60.00, 'On Time'),
(13, 12, 'Sewing', 120.00, 120.00, 'On Time'),
(14, 12, 'Finishing', 45.00, 45.00, 'On Time'),
(18, 14, 'Cutting', 60.00, 60.00, 'On Time'),
(19, 14, 'Sewing', 120.00, 20.00, 'On Time'),
(20, 14, 'Finishing', 45.00, 10.00, 'On Time'),
(27, 17, 'Cutting', 60.00, 10.00, 'On Time'),
(28, 17, 'Sewing', 120.00, 15.00, 'On Time'),
(29, 17, 'Finishing', 45.00, 14.00, 'On Time'),
(30, 18, 'Cutting', 60.00, 150.00, 'DELAYED (+90m)'),
(31, 18, 'Sewing', 120.00, 125.00, 'DELAYED (+5m)'),
(32, 18, 'Finishing', 45.00, 147.00, 'DELAYED (+102m)'),
(33, 19, 'Cutting', 60.00, 125.00, 'DELAYED (+65m)'),
(34, 19, 'Sewing', 120.00, 528.00, 'DELAYED (+408m)'),
(35, 19, 'Finishing', 45.00, 457.00, 'DELAYED (+412m)'),
(42, 22, 'Cutting', 60.00, 20.00, NULL),
(43, 22, 'Sewing', 120.00, 169.00, 'DELAYED (+49m)'),
(44, 22, 'Finishing', 45.00, 59.00, 'DELAYED (+14m)'),
(45, 23, 'Cutting', 60.00, 60.00, 'On Time'),
(46, 23, 'Sewing', 120.00, 120.00, 'On Time'),
(47, 23, 'Finishing', 45.00, 120.00, 'DELAYED (+75m)'),
(72, 32, 'Cutting', 60.00, 100.00, NULL),
(73, 32, 'Sewing', 120.00, 200.00, NULL),
(74, 32, 'Finishing', 45.00, 69.00, NULL),
(78, 34, 'Cutting', 60.00, 155.00, NULL),
(79, 34, 'Sewing', 120.00, 55.00, NULL),
(80, 34, 'Finishing', 45.00, 45.00, NULL),
(93, 39, 'Cutting', 60.00, 60.00, NULL),
(94, 39, 'Sewing', 120.00, 150.00, NULL),
(95, 39, 'Finishing', 45.00, 45.00, NULL),
(96, 40, 'Cutting', 60.00, NULL, NULL),
(97, 40, 'Sewing', 120.00, NULL, NULL),
(98, 40, 'Finishing', 45.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `qc_check`
--

CREATE TABLE `qc_check` (
  `QC_ID` int(11) NOT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Stage_ID` int(11) DEFAULT NULL,
  `Passed` tinyint(1) DEFAULT NULL,
  `Defect_Count` int(11) DEFAULT 0,
  `Comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qc_check`
--

INSERT INTO `qc_check` (`QC_ID`, `Batch_ID`, `Stage_ID`, `Passed`, `Defect_Count`, `Comments`) VALUES
(1, 1, 1, 1, 0, 'Cutting precise, passed.'),
(2, 3, NULL, 0, 15, 'Fabric tearing issue.'),
(3, 4, 1, 1, 0, NULL),
(4, 5, 1, 1, 3, NULL),
(5, 6, 1, 0, 15, NULL),
(6, 7, 1, 0, 15, 'Routine Check'),
(7, 8, 1, 0, 10, 'Routine Check'),
(8, 9, 1, 0, 2, 'Routine Check'),
(9, 10, 1, 0, 10, 'Routine Inspection'),
(10, 11, 1, 0, 1, 'Routine Inspection'),
(11, 12, 1, 0, 1, 'Routine Inspection'),
(12, 14, 1, 0, 2, 'Routine Inspection'),
(13, 17, 1, 0, 1, 'Routine Inspection'),
(14, 18, 1, 0, 2, 'Routine Inspection'),
(15, 18, 1, 1, 0, 'Routine Inspection');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Manager','QC_Inspector') DEFAULT 'Manager',
  `Created_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `Username`, `Password`, `Role`, `Created_At`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'Manager', '2026-01-13 18:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `worker`
--

CREATE TABLE `worker` (
  `Worker_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Role` enum('Cutter','Sewer','Finisher','Supervisor') DEFAULT NULL,
  `Secondary_Role` varchar(50) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `Password` varchar(50) DEFAULT '12345',
  `Shift_Timing` varchar(50) DEFAULT '09:00 AM - 05:00 PM',
  `Availability` enum('Available','On Leave','Sick') DEFAULT 'Available',
  `Preferences` varchar(255) DEFAULT 'No specific preferences',
  `Efficiency_Rating` decimal(5,2) DEFAULT 100.00,
  `Phone` varchar(20) DEFAULT '+880 1700-000000',
  `Joining_Date` date DEFAULT curdate(),
  `Skills` text DEFAULT 'General',
  `NID_No` varchar(30) DEFAULT 'N/A',
  `Emergency_Contact` varchar(20) DEFAULT '',
  `Emergency_Contact_Name` varchar(100) DEFAULT NULL,
  `Blood_Group` varchar(5) DEFAULT NULL,
  `Home_Address` varchar(255) DEFAULT 'Dhaka',
  `Gross_Salary` decimal(10,2) DEFAULT 12500.00,
  `Join_Date` date DEFAULT '2024-01-01'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker`
--

INSERT INTO `worker` (`Worker_ID`, `Name`, `Role`, `Secondary_Role`, `Status`, `Password`, `Shift_Timing`, `Availability`, `Preferences`, `Efficiency_Rating`, `Phone`, `Joining_Date`, `Skills`, `NID_No`, `Emergency_Contact`, `Emergency_Contact_Name`, `Blood_Group`, `Home_Address`, `Gross_Salary`, `Join_Date`) VALUES
(51, 'Rahim Uddin', 'Supervisor', 'QC Inspector', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'Morning Shift Only', 99.50, '01711000001', '2019-01-10', 'Floor Management, Manpower Planning', '8293481290', '01811000001', 'Abdul Malek (Father)', 'O+', 'House 12, Road 5, Mirpur 10, Dhaka', 35000.00, '2019-01-10'),
(52, 'Abdul Karim', 'Sewer', 'Cutter', 'Active', '12345', '09:00 AM - 06:00 PM', 'Sick', 'No Heavy Lifting', 96.00, '01711000002', '2021-03-10', 'Stock Management, Ledger Maintenance', '1928374655', '01811000002', 'Salma Begum (Wife)', 'B+', 'Plot 45, Sector 7, Uttara, Dhaka', 20000.00, '2021-03-10'),
(53, 'Fatima Begum', 'Finisher', 'Sewer', 'Active', '12345', '09:00 AM - 05:00 PM', 'Available', 'Flexible Hours', 84.62, '01711000003', '2022-06-01', 'Defect Identification, Measurement', '5647382910', '01811000003', 'Kuddus Ali (Husband)', 'A+', 'Vill: Noyapara, Savar', 18500.00, '2022-06-01'),
(54, 'Nasreen Akter', 'Sewer', 'Finisher', 'Active', '22222', '09:00 AM - 05:00 PM', 'Sick', 'Prefer Overtime', 96.50, '01711000004', '2023-01-20', 'Final Audit, Fabric Inspection', '9988776655', '01811000004', 'Rahima Khatun (Mother)', 'O-', 'Block C, Bashundhara, Dhaka', 18000.00, '2023-01-20'),
(55, 'Sujon Mia', 'Cutter', 'Store Keeper', 'Active', '12345', '08:00 AM - 04:00 PM', 'On Leave', 'Morning Shift', 88.50, '01711000005', '2023-05-12', 'Fabric Spreading, Cutting Machine', '1122334455', '01811000005', 'Babul Mia (Brother)', 'B+', '12/A Lalbagh, Old Dhaka', 14500.00, '2023-05-12'),
(56, 'Rafiqul Islam', 'Cutter', 'Finisher', 'Active', '12345', '02:00 PM - 10:00 PM', 'On Leave', 'Any Shift', 70.59, '01711000006', '2022-11-05', 'Pattern Cutting, Marking', '5566778899', '01811000006', 'Jahura Bibi (Mother)', 'AB+', 'Gazipur Sadar, Gazipur', 15000.00, '2022-11-05'),
(57, 'Bilkis Banu', 'Cutter', 'Sewer', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'No Night Shift', 101.23, '01711000007', '2023-08-15', 'Scissor Cutting, Layering', '6677889900', '01811000007', 'Kashem Molla (Father)', 'A+', 'Mohammadpur, Dhaka', 14000.00, '2023-08-15'),
(58, 'Jamal Hossain', 'Cutter', 'Sewer', 'Active', '12345', '09:00 AM - 05:00 PM', 'Sick', 'Overtime Allowed', 93.40, '01711000008', '2021-09-01', 'Auto Cutter Operation', '3344556677', '01811000008', 'Nazma Begum (Wife)', 'O+', 'Badda Link Road, Dhaka', 15500.00, '2021-09-01'),
(59, 'Lovely Yesmin', 'Sewer', 'Finisher', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'Morning Shift Only', 26.97, '01711000009', '2022-02-14', 'Single Needle, Overlock', '2233445566', '01811000009', 'Solaiman Haque (Brother)', 'B-', 'Section 11, Mirpur, Dhaka', 13500.00, '2022-02-14'),
(60, 'Salma Khatun', 'Sewer', 'Cutter', 'Active', '12345', '09:00 AM - 05:00 PM', 'Available', 'Flexible', 87.90, '01711000010', '2023-10-10', 'Button Hole, Button Attach', '7788990011', '01811000010', 'Rafiq Ahmed (Husband)', 'O+', 'Tongi Bazaar, Gazipur', 13000.00, '2023-10-10'),
(61, 'Rina Parvin', 'Sewer', 'QC Inspector', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'Prefer Weekends Off', 92.30, '01711000011', '2021-06-20', 'Hemming, Stitching', '8899001122', '01811000011', 'Mokbul Hossain (Father)', 'A-', 'Banani Korail, Dhaka', 14000.00, '2021-06-20'),
(62, 'Shahin Alam', 'Sewer', 'Finisher', 'Active', '12345', '02:00 PM - 10:00 PM', 'Sick', 'Night Shift OK', 85.50, '01711000012', '2023-12-01', 'Bar Tack, Feed off the arm', '9900112233', '01811000012', 'Julekha Begum (Wife)', 'B+', 'Tejgaon Industrial Area', 13500.00, '2023-12-01'),
(63, 'Mitu Chowdhury', 'Sewer', 'Finisher', 'Active', '12345', '09:00 AM - 05:00 PM', 'Available', 'Overtime Allowed', 37.50, '01711000013', '2020-11-11', 'Collar Attach, Cuff Attach', '4455667788', '01811000013', 'Hasan Chowdhury (Brother)', 'AB-', 'Dhanmondi 32, Dhaka', 14500.00, '2020-11-11'),
(64, 'Kamal Hasan', 'Sewer', 'Cutter', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'Morning Shift', 75.69, '01711000014', '2022-04-05', 'Pocket Joint, Flap Make', '5566778800', '01811000014', 'Rokeya Begum (Mother)', 'O+', 'Malibagh Chowdhury Para', 13800.00, '2022-04-05'),
(65, 'Sumon Reza', 'Sewer', 'Store Keeper', 'Active', '12345', '09:00 AM - 05:00 PM', 'Available', 'No Overtime', 90.50, '01711000015', '2023-07-22', 'Waistband Join', '6677889911', '01811000015', 'Sajeda Khanom (Wife)', 'A+', 'Rampura TV Center', 13200.00, '2023-07-22'),
(66, 'Tania Sultana', 'Sewer', 'Finisher', 'Active', '12345', '09:00 AM - 05:00 PM', 'Available', 'Flexible', 88.00, '01711000016', '2023-09-30', 'Label Attach, Zipper Join', '7788990022', '01811000016', 'Azizul Islam (Uncle)', 'B+', 'Khilgaon Taltola, Dhaka', 13000.00, '2023-09-30'),
(67, 'Ayesha Siddika', 'Finisher', 'Sewer', 'Active', '12345', '08:00 AM - 04:00 PM', 'Available', 'Morning Shift Only', 100.00, '01711000017', '2021-01-10', 'Ironing, Folding', '8899001133', '01811000017', 'Kawsar Ahmed (Husband)', 'O+', 'Farmgate, Dhaka', 13500.00, '2021-01-10'),
(68, 'Monir Hossain', 'Finisher', 'QC Inspector', 'Active', '12345', '02:00 PM - 10:00 PM', 'Available', 'Any Shift', 91.20, '01711000018', '2022-08-18', 'Tagging, Packing', '9900112244', '01811000018', 'Morium Begum (Mother)', 'A+', 'Agargaon BNP Bazar', 13000.00, '2022-08-18'),
(69, 'Tumpa Barua', 'Finisher', 'Sewer', 'Active', '12345', '10:00 PM - 06:00 AM', 'Available', 'Prefer Night Shift', 50.00, '01711000019', '2020-05-25', 'Thread Sucking, Spot Removing', '1122334466', '01811000019', 'Suman Barua (Brother)', 'B+', 'Gabtoli, Dhaka', 14000.00, '2020-05-25'),
(70, 'Sohel Rana', 'Finisher', 'Cutter', 'Active', '12345', '09:00 AM - 05:00 PM', 'On Leave', 'Overtime OK', 86.40, '01711000020', '2023-11-15', 'Final Boxing, Carton Taping', '2233445577', '01811000020', 'Faruk Hossain (Father)', 'O+', 'Savar EPZ Area', 12500.00, '2023-11-15'),
(111, 'Chayon Banarjee', 'Cutter', 'Finisher', 'Active', '12345', '09:00 AM - 05:00 PM', 'Sick', 'No specific preferences', 100.00, '+880 1700124545', '2025-10-22', 'General', 'N/A', '01988758456', 'Chacha', 'B+', 'Dhaka', 32500.00, '2025-10-22');

-- --------------------------------------------------------

--
-- Table structure for table `worker_performance`
--

CREATE TABLE `worker_performance` (
  `Perf_ID` int(11) NOT NULL,
  `Worker_ID` int(11) DEFAULT NULL,
  `Batch_ID` int(11) DEFAULT NULL,
  `Stage_Name` varchar(50) DEFAULT NULL,
  `Actual_Time` int(11) DEFAULT NULL,
  `Target_Time` int(11) DEFAULT NULL,
  `Efficiency_Score` decimal(5,2) DEFAULT NULL,
  `Logged_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_performance`
--

INSERT INTO `worker_performance` (`Perf_ID`, `Worker_ID`, `Batch_ID`, `Stage_Name`, `Actual_Time`, `Target_Time`, `Efficiency_Score`, `Logged_Date`) VALUES
(5, 67, 34, 'Finishing', 45, 45, 100.00, '2026-01-28'),
(6, 57, 35, 'Cutting', 69, 60, 86.96, '2026-01-28'),
(7, 63, 35, 'Sewing', 320, 120, 37.50, '2026-01-28'),
(8, 69, 35, 'Finishing', 90, 45, 50.00, '2026-01-28'),
(9, 56, 36, 'Cutting', 85, 60, 70.59, '2026-01-28'),
(10, 59, 36, 'Sewing', 445, 120, 26.97, '2026-01-28'),
(11, 53, 36, 'Finishing', 65, 45, 69.23, '2026-01-28'),
(12, 57, 37, 'Cutting', 65, 60, 92.31, '2026-01-28'),
(13, 57, 37, 'Cutting', 65, 60, 92.31, '2026-01-28'),
(14, 64, 37, 'Sewing', 255, 120, 47.06, '2026-01-28'),
(15, 57, 38, 'Cutting', 45, 60, 133.33, '2026-01-28'),
(16, 64, 39, 'Cutting', 60, 60, 100.00, '2026-01-28'),
(17, 64, 39, 'Sewing', 150, 120, 80.00, '2026-01-28'),
(18, 53, 39, 'Finishing', 45, 45, 100.00, '2026-01-28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`Batch_ID`);

--
-- Indexes for table `defect_log`
--
ALTER TABLE `defect_log`
  ADD PRIMARY KEY (`Defect_ID`),
  ADD KEY `Batch_ID` (`Batch_ID`),
  ADD KEY `Found_By_Worker_ID` (`Found_By_Worker_ID`);

--
-- Indexes for table `finished_goods`
--
ALTER TABLE `finished_goods`
  ADD PRIMARY KEY (`Item_ID`),
  ADD UNIQUE KEY `Batch_ID` (`Batch_ID`);

--
-- Indexes for table `machine`
--
ALTER TABLE `machine`
  ADD PRIMARY KEY (`Machine_ID`);

--
-- Indexes for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  ADD PRIMARY KEY (`Log_ID`),
  ADD KEY `Machine_ID` (`Machine_ID`);

--
-- Indexes for table `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`Material_ID`);

--
-- Indexes for table `material_usage`
--
ALTER TABLE `material_usage`
  ADD PRIMARY KEY (`Usage_ID`),
  ADD KEY `Batch_ID` (`Batch_ID`),
  ADD KEY `Material_ID` (`Material_ID`);

--
-- Indexes for table `ncr`
--
ALTER TABLE `ncr`
  ADD PRIMARY KEY (`NCR_ID`),
  ADD KEY `Batch_ID` (`Batch_ID`);

--
-- Indexes for table `production_stage`
--
ALTER TABLE `production_stage`
  ADD PRIMARY KEY (`Stage_ID`),
  ADD KEY `Batch_ID` (`Batch_ID`);

--
-- Indexes for table `qc_check`
--
ALTER TABLE `qc_check`
  ADD PRIMARY KEY (`QC_ID`),
  ADD KEY `Batch_ID` (`Batch_ID`),
  ADD KEY `Stage_ID` (`Stage_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `worker`
--
ALTER TABLE `worker`
  ADD PRIMARY KEY (`Worker_ID`);

--
-- Indexes for table `worker_performance`
--
ALTER TABLE `worker_performance`
  ADD PRIMARY KEY (`Perf_ID`),
  ADD KEY `Worker_ID` (`Worker_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `Batch_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `defect_log`
--
ALTER TABLE `defect_log`
  MODIFY `Defect_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `finished_goods`
--
ALTER TABLE `finished_goods`
  MODIFY `Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `machine`
--
ALTER TABLE `machine`
  MODIFY `Machine_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  MODIFY `Log_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `material`
--
ALTER TABLE `material`
  MODIFY `Material_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `material_usage`
--
ALTER TABLE `material_usage`
  MODIFY `Usage_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `ncr`
--
ALTER TABLE `ncr`
  MODIFY `NCR_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `production_stage`
--
ALTER TABLE `production_stage`
  MODIFY `Stage_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `qc_check`
--
ALTER TABLE `qc_check`
  MODIFY `QC_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `worker`
--
ALTER TABLE `worker`
  MODIFY `Worker_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `worker_performance`
--
ALTER TABLE `worker_performance`
  MODIFY `Perf_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `defect_log`
--
ALTER TABLE `defect_log`
  ADD CONSTRAINT `defect_log_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`),
  ADD CONSTRAINT `defect_log_ibfk_2` FOREIGN KEY (`Found_By_Worker_ID`) REFERENCES `worker` (`Worker_ID`);

--
-- Constraints for table `finished_goods`
--
ALTER TABLE `finished_goods`
  ADD CONSTRAINT `finished_goods_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  ADD CONSTRAINT `maintenance_log_ibfk_1` FOREIGN KEY (`Machine_ID`) REFERENCES `machine` (`Machine_ID`) ON DELETE CASCADE;

--
-- Constraints for table `material_usage`
--
ALTER TABLE `material_usage`
  ADD CONSTRAINT `material_usage_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `material_usage_ibfk_2` FOREIGN KEY (`Material_ID`) REFERENCES `material` (`Material_ID`);

--
-- Constraints for table `ncr`
--
ALTER TABLE `ncr`
  ADD CONSTRAINT `ncr_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`) ON DELETE CASCADE;

--
-- Constraints for table `production_stage`
--
ALTER TABLE `production_stage`
  ADD CONSTRAINT `production_stage_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`) ON DELETE CASCADE;

--
-- Constraints for table `qc_check`
--
ALTER TABLE `qc_check`
  ADD CONSTRAINT `qc_check_ibfk_1` FOREIGN KEY (`Batch_ID`) REFERENCES `batch` (`Batch_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `qc_check_ibfk_2` FOREIGN KEY (`Stage_ID`) REFERENCES `production_stage` (`Stage_ID`) ON DELETE SET NULL;

--
-- Constraints for table `worker_performance`
--
ALTER TABLE `worker_performance`
  ADD CONSTRAINT `worker_performance_ibfk_1` FOREIGN KEY (`Worker_ID`) REFERENCES `worker` (`Worker_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
