-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 09:19 AM
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
-- Database: `faculty_evaluation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`) VALUES
(2, 'administrator', 'P@ssw0rd');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_name`, `department_id`) VALUES
(1, 'Tourism Management', 1),
(2, 'BSIT', 2),
(3, 'BSEntrep', 3);

-- --------------------------------------------------------

--
-- Table structure for table `criteria`
--

CREATE TABLE `criteria` (
  `criteria_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `question` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criteria`
--

INSERT INTO `criteria` (`criteria_id`, `title`, `question`) VALUES
(12, 'Teacher Performance Evaluation', 'How well does the teacher address students’ individual needs?'),
(13, 'Teacher Performance Evaluation', 'How effective is the teacher in communicating the lesson material?'),
(14, 'Teacher Performance Evaluation', 'Does the teacher encourage student participation and critical thinking?'),
(15, 'Teacher Effectiveness Feedback', 'How clear and organized are the teacher’s lesson plans?'),
(16, 'Teacher Effectiveness Feedback', 'Does the teacher encourage student participation and critical thinking?'),
(17, 'Teacher Effectiveness Feedback', 'How approachable and supportive is the teacher?');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`) VALUES
(3, 'Entrep'),
(2, 'IT Department'),
(1, 'Tourism Department');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE `evaluation` (
  `evaluation_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `evaluation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation`
--

INSERT INTO `evaluation` (`evaluation_id`, `faculty_id`, `student_id`, `criteria_id`, `rating`, `comments`, `evaluation_date`) VALUES
(25, 1, 1, 12, 5, '', '2025-03-07 02:30:50'),
(26, 1, 1, 13, 4, '', '2025-03-07 02:30:50'),
(27, 1, 1, 14, 5, '', '2025-03-07 02:30:50'),
(28, 1, 1, 15, 5, '', '2025-03-07 02:30:50'),
(29, 1, 1, 16, 5, '', '2025-03-07 02:30:50'),
(30, 1, 1, 17, 5, '', '2025-03-07 02:30:50'),
(31, 3, 1, 12, 4, '', '2025-03-07 02:54:31'),
(32, 3, 1, 13, 4, '', '2025-03-07 02:54:31'),
(33, 3, 1, 14, 4, '', '2025-03-07 02:54:31'),
(34, 3, 1, 15, 3, '', '2025-03-07 02:54:31'),
(35, 3, 1, 16, 3, 'adjashdasdhasjkddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd', '2025-03-07 02:54:31'),
(36, 3, 1, 17, 4, '', '2025-03-07 02:54:31'),
(37, 1, 2, 12, 4, '', '2025-03-07 03:11:46'),
(38, 1, 2, 13, 3, '', '2025-03-07 03:11:46'),
(39, 1, 2, 14, 4, '', '2025-03-07 03:11:46'),
(40, 1, 2, 15, 4, '', '2025-03-07 03:11:46'),
(41, 1, 2, 16, 4, '', '2025-03-07 03:11:46'),
(42, 1, 2, 17, 4, '', '2025-03-07 03:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_settings`
--

CREATE TABLE `evaluation_settings` (
  `setting_id` int(11) NOT NULL,
  `is_open` tinyint(1) DEFAULT 1,
  `evaluation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_settings`
--

INSERT INTO `evaluation_settings` (`setting_id`, `is_open`, `evaluation_id`) VALUES
(1, 1, 0),
(2, 0, 0),
(3, 1, 0),
(4, 0, 0),
(5, 1, 0),
(6, 0, 0),
(7, 1, 0),
(8, 0, 0),
(9, 1, 0),
(10, 0, 0),
(11, 1, 0),
(12, 0, 0),
(13, 1, 0),
(14, 0, 0),
(15, 1, 0),
(16, 0, 0),
(17, 1, 0),
(18, 0, 0),
(19, 1, 0),
(20, 0, 0),
(21, 1, 0),
(22, 0, 0),
(23, 1, 0),
(24, 0, 0),
(25, 1, 0),
(26, 0, 0),
(27, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `name`, `department_id`, `email`) VALUES
(1, 'John Doe', 1, 'johndoe@example.com'),
(2, 'JL', 2, 'jl@paranaquecitycollege.edu.ph'),
(3, 'William Zayn', 1, 'William@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_subjects`
--

CREATE TABLE `faculty_subjects` (
  `faculty_subject_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_subjects`
--

INSERT INTO `faculty_subjects` (`faculty_subject_id`, `faculty_id`, `course_id`, `section_id`, `subject_name`) VALUES
(2, 1, 1, 1, 'basic knowledge'),
(3, 3, 1, 1, 'basic knowledge 2'),
(4, 3, 1, 1, '12');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `total_evaluations` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `faculty_id`, `total_evaluations`, `average_rating`, `generated_at`) VALUES
(17, 1, 6, 4.83, '2025-03-07 02:30:50'),
(18, 3, 6, 3.67, '2025-03-07 02:54:31'),
(19, 1, 12, 4.33, '2025-03-07 03:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`, `course_id`) VALUES
(1, 'Tourism Section A', 1),
(2, 'BSIT - A', 2),
(3, 'BSIT - B', 2);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `section_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `section_id`, `username`, `password`) VALUES
(1, 'John Syte Tiopes', 1, 'j.tiopes', '9fkPSDH1iQ'),
(2, 'ronnel', 1, 'ronnel', 'Zgz35PEjed');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_name` (`course_name`);

--
-- Indexes for table `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`criteria_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`evaluation_id`);

--
-- Indexes for table `evaluation_settings`
--
ALTER TABLE `evaluation_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD PRIMARY KEY (`faculty_subject_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_name` (`section_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `criteria`
--
ALTER TABLE `criteria`
  MODIFY `criteria_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `evaluation_settings`
--
ALTER TABLE `evaluation_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  MODIFY `faculty_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
