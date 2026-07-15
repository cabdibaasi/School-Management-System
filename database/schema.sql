-- SCHOOL MANAGEMENT SYSTEM DATABASE SCHEMA
-- Create Database
CREATE DATABASE IF NOT EXISTS `school_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_management`;

-- Drop tables if they exist to allow clean reinstall (respect dependencies)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `book_borrows`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `fee_payments`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `teacher_attendance`;
DROP TABLE IF EXISTS `student_attendance`;
DROP TABLE IF EXISTS `timetables`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Core authentication entity)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- 2. Settings Table
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NULL
) ENGINE=InnoDB;

-- 3. Classes Table
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_name` VARCHAR(50) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `class_section_year` (`class_name`, `section`, `academic_year`)
) ENGINE=InnoDB;

-- 4. Students Table
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `admission_number` VARCHAR(50) NOT NULL UNIQUE,
  `student_id_card` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `nationality` VARCHAR(50) DEFAULT 'US',
  `blood_group` VARCHAR(10) NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `parent_name` VARCHAR(100) NOT NULL,
  `parent_phone` VARCHAR(20) NOT NULL,
  `parent_email` VARCHAR(100) NULL,
  `class_id` INT NULL,
  `roll_number` VARCHAR(20) NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `photo` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  INDEX `idx_admission` (`admission_number`),
  INDEX `idx_student_status` (`status`),
  INDEX `idx_student_class` (`class_id`)
) ENGINE=InnoDB;

-- 5. Teachers Table
CREATE TABLE `teachers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `qualification` VARCHAR(255) NOT NULL,
  `salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `date_joined` DATE NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `photo` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_employee` (`employee_id`),
  INDEX `idx_teacher_status` (`status`)
) ENGINE=InnoDB;

-- 5b. Teacher Payments Table (Payroll/Salary Payouts)
CREATE TABLE `teacher_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `month_paid` VARCHAR(7) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Subjects Table
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_name` VARCHAR(100) NOT NULL,
  `subject_code` VARCHAR(50) NOT NULL,
  `teacher_id` INT NULL,
  `class_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `subject_code_class` (`subject_code`, `class_id`),
  INDEX `idx_subject_class` (`class_id`),
  INDEX `idx_subject_teacher` (`teacher_id`)
) ENGINE=InnoDB;

-- 7. Timetable Table
CREATE TABLE `timetables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `classroom` VARCHAR(50) NOT NULL,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  INDEX `idx_timetable_class` (`class_id`)
) ENGINE=InnoDB;

-- 8. Student Attendance Table
CREATE TABLE `student_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `student_date` (`student_id`, `date`),
  INDEX `idx_stud_att_date` (`date`)
) ENGINE=InnoDB;

-- 9. Teacher Attendance Table
CREATE TABLE `teacher_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `teacher_date` (`teacher_id`, `date`),
  INDEX `idx_teach_att_date` (`date`)
) ENGINE=InnoDB;

-- 10. Exams Table
CREATE TABLE `exams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_name` VARCHAR(100) NOT NULL,
  `class_id` INT NOT NULL,
  `exam_date` DATE NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  INDEX `idx_exam_class` (`class_id`)
) ENGINE=InnoDB;

-- 11. Marks Table
CREATE TABLE `marks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `marks_obtained` DECIMAL(5,2) NOT NULL,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `exam_student_subject` (`exam_id`, `student_id`, `subject_id`),
  INDEX `idx_marks_student` (`student_id`),
  INDEX `idx_marks_exam` (`exam_id`)
) ENGINE=InnoDB;

-- 12. Fees Table
CREATE TABLE `fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `fee_type` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  INDEX `idx_fee_student` (`student_id`),
  INDEX `idx_fee_status` (`status`)
) ENGINE=InnoDB;

-- 13. Fee Payments Table
CREATE TABLE `fee_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fee_id` INT NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `payment_method` ENUM('cash', 'card', 'bank_transfer', 'cheque') NOT NULL,
  `transaction_reference` VARCHAR(100) NULL,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE,
  INDEX `idx_payment_fee` (`fee_id`),
  INDEX `idx_receipt_num` (`receipt_number`)
) ENGINE=InnoDB;

-- 14. Library Books Table
CREATE TABLE `books` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(255) NOT NULL,
  `isbn` VARCHAR(50) NOT NULL UNIQUE,
  `quantity` INT NOT NULL DEFAULT 1,
  `available_quantity` INT NOT NULL DEFAULT 1,
  `category` VARCHAR(100) NULL,
  `shelf_location` VARCHAR(50) NULL,
  INDEX `idx_book_isbn` (`isbn`),
  INDEX `idx_book_title` (`title`)
) ENGINE=InnoDB;

-- 15. Book Borrowing Table
CREATE TABLE `book_borrows` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `borrow_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE NULL,
  `status` ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
  `fine_amount` DECIMAL(10,2) DEFAULT 0.00,
  `fine_paid` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  INDEX `idx_borrow_student` (`student_id`),
  INDEX `idx_borrow_status` (`status`)
) ENGINE=InnoDB;

-- 16. Announcements Table
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `target_role` ENUM('all', 'admin', 'teacher', 'student') DEFAULT 'all',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_announcement_role` (`target_role`)
) ENGINE=InnoDB;

-- Insert initial settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'St. Andrew Academy'),
('school_email', 'info@standrew.edu'),
('school_phone', '+1 (555) 019-2834'),
('school_address', '100 Academy Blvd, Boston, MA 02108'),
('school_logo', ''),
('current_academic_year', '2026-2027'),
('currency', 'USD'),
('language', 'English');

-- Seed default administrator (admin / admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('admin', 'admin@school.com', '$2y$10$dosBx.EtTjNaOtIQHf34juIHYCKwKmHwh0KNRQfSGvqkfQzlhY8oi', 'admin', 'active');
