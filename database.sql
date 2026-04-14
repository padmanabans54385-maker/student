-- Student Management System Database
-- Run this SQL to set up the database

CREATE DATABASE IF NOT EXISTS sms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_db;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    class VARCHAR(50) NOT NULL,
    section VARCHAR(10) DEFAULT 'A',
    phone VARCHAR(20),
    address TEXT,
    dob DATE,
    gender ENUM('Male','Female','Other') DEFAULT 'Male',
    profile_image VARCHAR(255) DEFAULT 'default.png',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    class VARCHAR(50) NOT NULL,
    max_marks INT DEFAULT 100,
    pass_marks INT DEFAULT 35,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent','Late') DEFAULT 'Present',
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, subject_id, date)
);

-- Marks/Results table
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_type ENUM('Unit Test','Mid Term','Final','Assignment') DEFAULT 'Final',
    marks_obtained DECIMAL(5,2) DEFAULT 0,
    max_marks INT DEFAULT 100,
    grade VARCHAR(5),
    remarks TEXT,
    exam_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Notices/Announcements table
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT,
    target ENUM('All','Students','Class') DEFAULT 'All',
    target_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Default admin (password: admin123)
INSERT INTO admins (name, email, password) VALUES
('Super Admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample students (password: student123)
INSERT INTO students (student_id, name, email, password, class, section, phone, gender) VALUES
('STU001', 'Arjun Sharma', 'arjun@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 10', 'A', '9876543210', 'Male'),
('STU002', 'Priya Patel', 'priya@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 10', 'A', '9876543211', 'Female'),
('STU003', 'Rahul Kumar', 'rahul@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 10', 'B', '9876543212', 'Male'),
('STU004', 'Sneha Reddy', 'sneha@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 11', 'A', '9876543213', 'Female'),
('STU005', 'Vikram Singh', 'vikram@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 11', 'B', '9876543214', 'Male'),
('STU006', 'Ananya Das', 'ananya@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Class 12', 'A', '9876543215', 'Female');

-- Sample subjects
INSERT INTO subjects (name, code, class, max_marks, pass_marks) VALUES
('Mathematics', 'MATH10', 'Class 10', 100, 35),
('Science', 'SCI10', 'Class 10', 100, 35),
('English', 'ENG10', 'Class 10', 100, 35),
('Social Studies', 'SST10', 'Class 10', 100, 35),
('Hindi', 'HIN10', 'Class 10', 100, 35),
('Physics', 'PHY11', 'Class 11', 100, 35),
('Chemistry', 'CHE11', 'Class 11', 100, 35),
('Biology', 'BIO11', 'Class 11', 100, 35),
('Mathematics', 'MATH11', 'Class 11', 100, 35),
('Physics', 'PHY12', 'Class 12', 100, 35),
('Chemistry', 'CHE12', 'Class 12', 100, 35),
('Mathematics', 'MATH12', 'Class 12', 100, 35);

-- Sample marks
INSERT INTO marks (student_id, subject_id, exam_type, marks_obtained, max_marks, grade, exam_date) VALUES
(1, 1, 'Final', 87, 100, 'A', '2024-03-15'),
(1, 2, 'Final', 92, 100, 'A+', '2024-03-16'),
(1, 3, 'Final', 78, 100, 'B+', '2024-03-17'),
(1, 4, 'Final', 85, 100, 'A', '2024-03-18'),
(1, 5, 'Final', 91, 100, 'A+', '2024-03-19'),
(2, 1, 'Final', 95, 100, 'A+', '2024-03-15'),
(2, 2, 'Final', 88, 100, 'A', '2024-03-16'),
(2, 3, 'Final', 92, 100, 'A+', '2024-03-17'),
(3, 1, 'Final', 72, 100, 'B', '2024-03-15'),
(3, 2, 'Final', 68, 100, 'B', '2024-03-16'),
(4, 6, 'Final', 89, 100, 'A', '2024-03-15'),
(4, 7, 'Final', 76, 100, 'B+', '2024-03-16'),
(5, 6, 'Final', 65, 100, 'B', '2024-03-15'),
(5, 9, 'Final', 83, 100, 'A', '2024-03-17');

-- Sample attendance (last 7 days)
INSERT INTO attendance (student_id, subject_id, date, status) VALUES
(1, 1, CURDATE(), 'Present'),
(1, 2, CURDATE(), 'Present'),
(2, 1, CURDATE(), 'Present'),
(2, 2, CURDATE(), 'Absent'),
(3, 1, CURDATE(), 'Late'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Absent');

-- Sample notice
INSERT INTO notices (title, content, posted_by, target) VALUES
('Annual Sports Day', 'Annual sports day will be held on 20th April 2024. All students must participate.', 1, 'All'),
('Exam Schedule Released', 'Final examination schedule has been uploaded. Please check the notice board.', 1, 'Students');