
CREATE DATABASE IF NOT EXISTS `student_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `student_portal`;

DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `email`            VARCHAR(255) NOT NULL UNIQUE,
    `password_hash`    VARCHAR(255) NOT NULL,
    `full_name`        VARCHAR(255) NOT NULL,
    `student_year`     INT NOT NULL DEFAULT 1,
    `major`            VARCHAR(255) NOT NULL,
    `faculty`          VARCHAR(255) NOT NULL DEFAULT '',
    `current_semester` INT NOT NULL DEFAULT 1,
    `gpa`              DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `account_status`   VARCHAR(50) NOT NULL DEFAULT 'Active',
    `date_of_birth`    DATE NULL,
    `gender`           VARCHAR(20) NOT NULL DEFAULT '',
    `phone_number`     VARCHAR(30) NOT NULL DEFAULT '',
    `physical_address` TEXT NOT NULL DEFAULT '',
    `admission_date`   DATE NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

