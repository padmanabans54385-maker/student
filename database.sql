-- Student Management System Database (v3)
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

-- Staffs table
CREATE TABLE IF NOT EXISTS staffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    year VARCHAR(10) NOT NULL,
    degree VARCHAR(50) NOT NULL,
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
    year VARCHAR(10) NOT NULL,
    degree VARCHAR(50) NOT NULL,
    type ENUM('Theory','Lab') DEFAULT 'Theory',
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
    exam_type VARCHAR(50) NOT NULL DEFAULT 'External',
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
    target ENUM('All','Students','Staffs') DEFAULT 'All',
    target_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Default admin (password: password)
INSERT INTO admins (name, email, password) VALUES
('Super Admin', 'ad@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample staff (password: password)
INSERT INTO staffs (name, email, password, phone, department) VALUES
('Dr. Ramesh Kumar', 'ramesh@staff.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876500001', 'Computer Science'),
('Prof. Lakshmi Devi', 'lakshmi@staff.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876500002', 'Mathematics');

-- Sample students (password: password)
INSERT INTO students (student_id, name, email, password, year, degree, phone, gender) VALUES
('STU001', 'Arjun Sharma', 'arjun@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'I', 'MCA', '9876543210', 'Male'),
('STU002', 'Priya Patel', 'priya@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'I', 'MCA', '9876543211', 'Female'),
('STU003', 'Rahul Kumar', 'rahul@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'I', 'BCA', '9876543212', 'Male'),
('STU004', 'Sneha Reddy', 'sneha@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'II', 'MCA', '9876543213', 'Female'),
('STU005', 'Vikram Singh', 'vikram@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'II', 'BCA', '9876543214', 'Male'),
('STU006', 'Ananya Das', 'ananya@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'III', 'MCA', '9876543215', 'Female');

-- Sample subjects (Theory & Lab)
INSERT INTO subjects (name, code, year, degree, type, max_marks, pass_marks) VALUES
('Data Structures', 'DS101', 'I', 'MCA', 'Theory', 100, 35),
('Database Systems', 'DB101', 'I', 'MCA', 'Theory', 100, 35),
('Programming Lab', 'PL101', 'I', 'MCA', 'Lab', 100, 35),
('Web Technology', 'WT101', 'I', 'BCA', 'Theory', 100, 35),
('Computer Networks', 'CN201', 'II', 'MCA', 'Theory', 100, 35),
('Network Lab', 'NL201', 'II', 'MCA', 'Lab', 100, 35),
('Software Engineering', 'SE201', 'II', 'BCA', 'Theory', 100, 35),
('Advanced DBMS', 'AD301', 'III', 'MCA', 'Theory', 100, 35),
('Project Lab', 'PJ301', 'III', 'MCA', 'Lab', 100, 35);

-- Sample marks (new exam types)
INSERT INTO marks (student_id, subject_id, exam_type, marks_obtained, max_marks, grade, exam_date) VALUES
(1, 1, 'Internal 1', 42, 50, 'A', '2024-02-15'),
(1, 1, 'Internal 2', 38, 50, 'B+', '2024-03-15'),
(1, 1, 'External', 72, 100, 'B+', '2024-04-15'),
(1, 2, 'Internal 1', 45, 50, 'A+', '2024-02-16'),
(1, 2, 'External', 85, 100, 'A', '2024-04-16'),
(1, 3, 'Internal', 40, 50, 'A', '2024-03-10'),
(1, 3, 'External', 78, 100, 'B+', '2024-04-10'),
(2, 1, 'Internal 1', 48, 50, 'A+', '2024-02-15'),
(2, 1, 'External', 90, 100, 'A+', '2024-04-15'),
(3, 4, 'Internal 1', 35, 50, 'B+', '2024-02-15'),
(3, 4, 'External', 68, 100, 'B', '2024-04-15'),
(4, 5, 'Internal 1', 44, 50, 'A', '2024-02-15'),
(4, 5, 'External', 82, 100, 'A', '2024-04-15');

-- Sample attendance
INSERT INTO attendance (student_id, subject_id, date, status) VALUES
(1, 1, CURDATE(), 'Present'),
(1, 2, CURDATE(), 'Present'),
(2, 1, CURDATE(), 'Present'),
(2, 2, CURDATE(), 'Absent'),
(3, 4, CURDATE(), 'Late'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present'),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Absent');

-- Sample notices
INSERT INTO notices (title, content, posted_by, target) VALUES
('Annual Sports Day', 'Annual sports day will be held on 20th April 2024. All students must participate.', 1, 'All'),
('Exam Schedule Released', 'Internal examination schedule has been uploaded. Please check the notice board.', 1, 'Students');