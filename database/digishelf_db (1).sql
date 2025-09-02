-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2025 at 03:06 PM
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
-- Database: `digishelf_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateDailyAnalytics` ()   BEGIN
    DECLARE daily_users INT DEFAULT 0;
    DECLARE books_borrowed INT DEFAULT 0;
    DECLARE reading_hours DECIMAL(10,2) DEFAULT 0;
    DECLARE new_registrations INT DEFAULT 0;
    
    -- Calculate daily active users (users who have activity today)
    SELECT COUNT(DISTINCT user_id) INTO daily_users 
    FROM reading_sessions 
    WHERE DATE(session_start) = CURDATE();
    
    -- Calculate books borrowed today
    SELECT COUNT(*) INTO books_borrowed 
    FROM transactions 
    WHERE DATE(created_at) = CURDATE();
    
    -- Calculate total reading hours today
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, session_start, session_end) / 60.0), 0) INTO reading_hours
    FROM reading_sessions 
    WHERE DATE(session_start) = CURDATE() AND session_end IS NOT NULL;
    
    -- Calculate new registrations today
    SELECT COUNT(*) INTO new_registrations 
    FROM users 
    WHERE DATE(created_at) = CURDATE();
    
    -- Insert or update analytics
    INSERT INTO analytics (metric_name, metric_value, date_recorded) VALUES
    ('daily_active_users', daily_users, CURDATE()),
    ('books_borrowed_today', books_borrowed, CURDATE()),
    ('total_reading_hours', reading_hours, CURDATE()),
    ('user_registrations', new_registrations, CURDATE())
    ON DUPLICATE KEY UPDATE 
    metric_value = VALUES(metric_value),
    created_at = NOW();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `name`, `created_at`) VALUES
(1, 'admin', 'admin@digishelf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '2025-09-01 14:59:25');

-- --------------------------------------------------------

--
-- Table structure for table `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `date_recorded` date NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analytics`
--

INSERT INTO `analytics` (`id`, `metric_name`, `metric_value`, `date_recorded`, `additional_data`, `created_at`) VALUES
(1, 'daily_active_users', 45.00, '2025-09-02', '{\"platform\": \"web\", \"new_users\": 5}', '2025-09-02 06:52:59'),
(2, 'books_borrowed_today', 12.00, '2025-09-02', '{\"category_breakdown\": {\"Fiction\": 5, \"Science\": 4, \"Technology\": 3}}', '2025-09-02 06:52:59'),
(3, 'total_reading_hours', 89.50, '2025-09-02', '{\"average_session\": 25}', '2025-09-02 06:52:59'),
(4, 'user_registrations', 3.00, '2025-09-02', '{\"source\": \"organic\"}', '2025-09-02 06:52:59'),
(5, 'books_returned_today', 8.00, '2025-09-02', '{\"on_time\": 6, \"overdue\": 2}', '2025-09-02 06:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available_quantity` int(11) DEFAULT 1,
  `cover_image` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pdfs_file` varchar(255) DEFAULT NULL,
  `pdfs_size` int(11) DEFAULT NULL,
  `total_pages` int(11) DEFAULT NULL,
  `reading_time_minutes` int(11) DEFAULT NULL,
  `pdf_size` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category_id`, `description`, `publisher`, `publication_year`, `pages`, `quantity`, `available_quantity`, `cover_image`, `pdf_file`, `status`, `created_at`, `updated_at`, `pdfs_file`, `pdfs_size`, `total_pages`, `reading_time_minutes`, `pdf_size`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', '978-0-7432-7356-5', 1, 'A classic American novel set in the Jazz Age', 'Scribner', '1925', 180, 3, 3, NULL, NULL, 'active', '2025-09-01 14:59:25', '2025-09-01 14:59:25', NULL, NULL, NULL, NULL, NULL),
(2, 'To Kill a Mockingbird', 'Harper Lee', '978-0-06-112008-4', 1, 'A gripping tale of racial injustice and childhood', 'J.B. Lippincott & Co.', '1960', 281, 2, 2, NULL, NULL, 'active', '2025-09-01 14:59:25', '2025-09-01 14:59:25', NULL, NULL, NULL, NULL, NULL),
(3, '1984', 'George Orwell', '978-0-452-28423-4', 1, 'A dystopian social science fiction novel', 'Secker & Warburg', '1949', 328, 4, 4, NULL, NULL, 'inactive', '2025-09-01 14:59:25', '2025-09-01 17:56:40', NULL, NULL, NULL, NULL, NULL),
(4, 'A Brief History of Time', 'Stephen Hawking', '978-0-553-38016-3', 3, 'A landmark volume in science writing', 'Bantam Books', '1988', 256, 2, 2, NULL, NULL, 'active', '2025-09-01 14:59:25', '2025-09-01 14:59:25', NULL, NULL, NULL, NULL, NULL),
(5, 'Steve Jobs', 'Walter Isaacson', '978-1-4516-4853-9', 5, 'The exclusive biography of Steve Jobs', 'Simon & Schuster', '2011', 656, 1, 1, NULL, NULL, 'active', '2025-09-01 14:59:25', '2025-09-01 14:59:25', NULL, NULL, NULL, NULL, NULL),
(6, 'JavaScript: The Good Parts', 'Douglas Crockford', '978-0-596-51774-8', 1, 'A comprehensive guide to JavaScript programming', 'O\'Reilly Media', '2008', 176, 3, 3, NULL, 'javascript_good_parts.pdf', 'active', '2025-09-02 06:52:59', '2025-09-02 06:52:59', NULL, NULL, 176, 240, 2048576),
(7, 'Clean Code', 'Robert C. Martin', '978-0-13-235088-4', 1, 'A handbook of agile software craftsmanship', 'Prentice Hall', '2008', 464, 2, 2, NULL, 'clean_code.pdf', 'active', '2025-09-02 06:52:59', '2025-09-02 06:52:59', NULL, NULL, 464, 580, 3145728),
(8, 'Introduction to Algorithms', 'Thomas H. Cormen', '978-0-262-03384-8', 1, 'Comprehensive introduction to algorithms', 'MIT Press', '2009', 1312, 1, 1, NULL, 'intro_algorithms.pdf', 'active', '2025-09-02 06:52:59', '2025-09-02 06:52:59', NULL, NULL, 1312, 1640, 8388608);

-- --------------------------------------------------------

--
-- Table structure for table `book_requests`
--

CREATE TABLE `book_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Fiction', 'Fictional books and novels', '2025-09-01 14:59:25'),
(2, 'Non-Fiction', 'Non-fictional books and documentaries', '2025-09-01 14:59:25'),
(3, 'Science', 'Science and technology books', '2025-09-01 14:59:25'),
(4, 'History', 'Historical books and references', '2025-09-01 14:59:25'),
(5, 'Biography', 'Biographical books', '2025-09-01 14:59:25'),
(6, 'Technology', 'Computer science and technology', '2025-09-01 14:59:25'),
(7, 'Literature', 'Classic and modern literature', '2025-09-01 14:59:25'),
(8, 'Business', 'Business and management books', '2025-09-01 14:59:25'),
(9, 'Programming', 'Software development and programming books', '2025-09-02 06:52:59'),
(10, 'Web Development', 'Frontend and backend web development', '2025-09-02 06:52:59'),
(11, 'Data Science', 'Data analysis, machine learning, and statistics', '2025-09-02 06:52:59'),
(12, 'Mobile Development', 'iOS, Android, and cross-platform development', '2025-09-02 06:52:59'),
(13, 'DevOps', 'Deployment, monitoring, and system administration', '2025-09-02 06:52:59');

-- --------------------------------------------------------

--
-- Stand-in structure for view `dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `dashboard_stats` (
`total_books` bigint(21)
,`total_users` bigint(21)
,`books_issued` bigint(21)
,`overdue_books` bigint(21)
,`total_fines` decimal(32,2)
,`reviews_this_week` bigint(21)
,`average_rating` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `read_status` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_sessions`
--

CREATE TABLE `reading_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `session_start` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_end` timestamp NULL DEFAULT NULL,
  `pages_read` int(11) DEFAULT 0,
  `current_page` int(11) DEFAULT 1,
  `device_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `helpful_count` int(11) DEFAULT 0,
  `report_count` int(11) DEFAULT 0,
  `status` enum('approved','pending','rejected') DEFAULT 'approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful','not_helpful') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'library_name', 'DigiShelf College Library', 'string', 'Name of the library', NULL, '2025-09-01 15:11:36'),
(2, 'max_books_per_user', '5', 'number', 'Maximum books a user can borrow', NULL, '2025-09-01 15:11:36'),
(3, 'loan_duration_days', '14', 'number', 'Default loan duration in days', NULL, '2025-09-01 15:11:36'),
(4, 'fine_per_day', '2.00', 'number', 'Fine amount per day for overdue books', NULL, '2025-09-01 15:11:36'),
(5, 'max_renewals', '2', 'number', 'Maximum number of renewals allowed', NULL, '2025-09-01 15:11:36'),
(6, 'library_email', 'library@college.edu', 'string', 'Library contact email', NULL, '2025-09-01 15:11:36'),
(7, 'library_phone', '+91-1234567890', 'string', 'Library contact phone', NULL, '2025-09-01 15:11:36'),
(8, 'working_hours', '9:00 AM - 6:00 PM', 'string', 'Library working hours', NULL, '2025-09-01 15:11:36'),
(9, 'enable_pdf_reading', 'true', 'boolean', 'Enable online PDF reading', NULL, '2025-09-01 15:11:36'),
(10, 'enable_notifications', 'true', 'boolean', 'Enable email notifications', NULL, '2025-09-01 15:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('issued','returned','overdue') DEFAULT 'issued',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `books` where `books`.`status` = 'active') AS `total_books`, (select count(0) from `users` where `users`.`status` = 'active') AS `total_users`, (select count(0) from `transactions` where `transactions`.`status` = 'issued') AS `books_issued`, (select count(0) from `transactions` where `transactions`.`status` = 'issued' and `transactions`.`due_date` < curdate()) AS `overdue_books`, (select coalesce(sum(`transactions`.`fine_amount`),0) from `transactions` where `transactions`.`fine_paid` = 0) AS `total_fines`, (select count(0) from `reviews` where `reviews`.`created_at` >= current_timestamp() - interval 7 day) AS `reviews_this_week`, (select coalesce(avg(`reviews`.`rating`),0) from `reviews` where `reviews`.`status` = 'approved') AS `average_rating` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_metric_date` (`metric_name`,`date_recorded`),
  ADD KEY `idx_analytics_date_metric` (`date_recorded`,`metric_name`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_books_pdf` (`pdf_file`);

--
-- Indexes for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`read_status`);

--
-- Indexes for table `reading_sessions`
--
ALTER TABLE `reading_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_book` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_reading_sessions_active` (`user_id`,`session_end`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_review` (`user_id`,`review_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_book` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `book_requests`
--
ALTER TABLE `book_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reading_sessions`
--
ALTER TABLE `reading_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD CONSTRAINT `book_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_sessions`
--
ALTER TABLE `reading_sessions`
  ADD CONSTRAINT `reading_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_sessions_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD CONSTRAINT `review_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
