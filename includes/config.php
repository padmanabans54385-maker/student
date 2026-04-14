<?php
// ============================================================
// config.php — Database & App Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Change to your MySQL username
define('DB_PASS', '');             // Change to your MySQL password
define('DB_NAME', 'sms_db');
define('APP_NAME', 'EduTrack SMS');
define('APP_VERSION', '3.0');
define('BASE_URL', 'http://localhost/sms/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Connection ──────────────────────────────────────
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── Auth Helpers ─────────────────────────────────────────────
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

function isStaffLoggedIn(): bool {
    return isset($_SESSION['staff_id']) && ($_SESSION['role'] ?? '') === 'staff';
}

function isStudentLoggedIn(): bool {
    return isset($_SESSION['student_id']) && ($_SESSION['role'] ?? '') === 'student';
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function requireStaff(): void {
    if (!isStaffLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function requireStudent(): void {
    if (!isStudentLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// ── Utility Helpers ──────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function getGrade(float $percentage): string {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 35) return 'D';
    return 'F';
}

function getGradeColor(string $grade): string {
    return match($grade) {
        'A+' => '#10b981',
        'A'  => '#3b82f6',
        'B+' => '#8b5cf6',
        'B'  => '#f59e0b',
        'C'  => '#f97316',
        'D'  => '#ef4444',
        default => '#6b7280'
    };
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flash(string $key, string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION["flash_$key"] = $msg;
        return null;
    }
    $val = $_SESSION["flash_$key"] ?? null;
    unset($_SESSION["flash_$key"]);
    return $val;
}

// ── Year & Degree Constants ──────────────────────────────────
function getYears(): array {
    return ['I', 'II', 'III'];
}

function getDegrees(): array {
    return ['MCA', 'BCA'];
}

// ── Exam Type Helpers ────────────────────────────────────────
function getTheoryExamTypes(): array {
    return ['Internal 1', 'Internal 2', 'Assignment 1', 'Assignment 2', 'Quiz', 'Seminar', 'External'];
}

function getLabExamTypes(): array {
    return ['Internal', 'External'];
}

function getAllExamTypes(): array {
    return array_unique(array_merge(getTheoryExamTypes(), getLabExamTypes()));
}