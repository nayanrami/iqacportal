<?php
/**
 * NAAC Feedback System - Configuration
 * CO Attainment | PO Exit Survey | Department Feedback
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'naac_feedback');
define('DB_USER', 'root');
define('DB_PASS', 'NEO007007');
define('DB_CHARSET', 'utf8mb4');

// App configuration
define('APP_NAME', 'IQAC Portal');
define('APP_INSTITUTE', 'IQAC Institute of Technology');
define('APP_DEPT', 'Department of Technical Education');
define('APP_URL', '/feedback');
define('APP_VERSION', '2.0.0');

// Database connection (PDO) — auto-creates DB & tables if not exists
try {
    $pdoInit = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoInit = null;

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // ── Core tables ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) DEFAULT 'IQAC Coordinator',
        `department_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `code` VARCHAR(20) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `courses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `department_id` INT NOT NULL,
        `name` VARCHAR(200) NOT NULL,
        `code` VARCHAR(30) NOT NULL,
        `semester` INT DEFAULT NULL,
        `course_type` VARCHAR(50) DEFAULT 'Core',
        `credits` INT DEFAULT NULL,
        `year` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Outcome tables ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS `program_outcomes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `department_id` INT NOT NULL,
        `type` ENUM('PO','PSO','PEO') NOT NULL DEFAULT 'PO',
        `code` VARCHAR(10) NOT NULL,
        `title` VARCHAR(100) DEFAULT NULL,
        `description` TEXT NOT NULL,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `course_outcomes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `course_id` INT NOT NULL,
        `code` VARCHAR(10) NOT NULL,
        `description` TEXT NOT NULL,
        `attainment_level` INT NOT NULL DEFAULT 3,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Feedback forms ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_forms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `form_type` ENUM('co_attainment','exit_survey','dept_feedback','general') NOT NULL DEFAULT 'general',
        `category` VARCHAR(100) DEFAULT 'General',
        `department_id` INT DEFAULT NULL,
        `course_id` INT DEFAULT NULL,
        `semester` INT DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` DATE DEFAULT NULL,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `questions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `feedback_form_id` INT NOT NULL,
        `question_text` TEXT NOT NULL,
        `question_type` ENUM('likert_5','likert_3','yes_no','text') NOT NULL DEFAULT 'likert_5',
        `co_id` INT DEFAULT NULL,
        `po_id` INT DEFAULT NULL,
        `max_score` INT NOT NULL DEFAULT 5,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`feedback_form_id`) REFERENCES `feedback_forms`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`co_id`) REFERENCES `course_outcomes`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`po_id`) REFERENCES `program_outcomes`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    // ── Student tables ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `enrollment_no` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `department_id` INT NOT NULL,
        `semester` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Response tables ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS `responses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `feedback_form_id` INT NOT NULL,
        `student_id` INT DEFAULT NULL,
        `student_name` VARCHAR(100) DEFAULT NULL,
        `student_roll` VARCHAR(50) DEFAULT NULL,
        `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        FOREIGN KEY (`feedback_form_id`) REFERENCES `feedback_forms`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `response_answers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `response_id` INT NOT NULL,
        `question_id` INT NOT NULL,
        `score` INT NOT NULL,
        `text_answer` TEXT DEFAULT NULL,
        FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ── Migrations for existing DBs ──
    $existingCols = array_column($pdo->query("SHOW COLUMNS FROM feedback_forms")->fetchAll(), 'Field');
    if (!in_array('category', $existingCols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN `category` VARCHAR(100) DEFAULT 'General' AFTER `title`");
    }
    if (!in_array('department_id', $existingCols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN `department_id` INT DEFAULT NULL AFTER `category`");
    }
    if (!in_array('semester', $existingCols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN `semester` INT DEFAULT NULL AFTER `course_id`");
    }
    if (!in_array('form_type', $existingCols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN `form_type` ENUM('co_attainment','exit_survey','dept_feedback','general') NOT NULL DEFAULT 'general' AFTER `title`");
    }

    $respCols = array_column($pdo->query("SHOW COLUMNS FROM responses")->fetchAll(), 'Field');
    if (!in_array('student_id', $respCols)) {
        $pdo->exec("ALTER TABLE responses ADD COLUMN `student_id` INT DEFAULT NULL AFTER `feedback_form_id` ");
        $pdo->exec("ALTER TABLE responses ADD CONSTRAINT fk_resp_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL");
    }

    $qCols = array_column($pdo->query("SHOW COLUMNS FROM questions")->fetchAll(), 'Field');
    if (!in_array('question_type', $qCols)) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN `question_type` ENUM('likert_5','likert_3','yes_no','text') NOT NULL DEFAULT 'likert_5' AFTER `question_text`");
    }
    if (!in_array('co_id', $qCols)) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN `co_id` INT DEFAULT NULL AFTER `question_type`");
    }
    if (!in_array('po_id', $qCols)) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN `po_id` INT DEFAULT NULL AFTER `co_id`");
    }

    // Migrate course_outcomes
    $coCols = array_column($pdo->query("SHOW COLUMNS FROM course_outcomes")->fetchAll(), 'Field');
    if (!in_array('attainment_level', $coCols)) {
        $pdo->exec("ALTER TABLE course_outcomes ADD COLUMN `attainment_level` INT NOT NULL DEFAULT 3 AFTER `description`");
    }

    $raCols = array_column($pdo->query("SHOW COLUMNS FROM response_answers")->fetchAll(), 'Field');
    if (!in_array('text_answer', $raCols)) {
        $pdo->exec("ALTER TABLE response_answers ADD COLUMN `text_answer` TEXT DEFAULT NULL AFTER `score`");
    }

    $cCols = array_column($pdo->query("SHOW COLUMNS FROM courses")->fetchAll(), 'Field');
    if (!in_array('course_type', $cCols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN `course_type` VARCHAR(50) DEFAULT 'Core' AFTER `semester`");
    }
    if (!in_array('credits', $cCols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN `credits` INT DEFAULT NULL AFTER `course_type`");
    }

    // Migrate admins table
    $adminCols = array_column($pdo->query("SHOW COLUMNS FROM admins")->fetchAll(), 'Field');
    if (!in_array('department_id', $adminCols)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN `department_id` INT DEFAULT NULL AFTER `full_name`");
    }

    // Insert default admin if not exists
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM admins");
    if ($stmt->fetch()['cnt'] == 0) {
        $hash = password_hash('iqac', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)")
            ->execute(['iqac', $hash, 'IQAC Coordinator']);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// CSRF Token helpers
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
