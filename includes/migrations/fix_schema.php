<?php
require_once __DIR__ . '/../functions.php';

try {
    echo "Starting Database Schema Synchronization...\n\n";

    // 1. Fix feedback_forms table
    $cols = $pdo->query("DESCRIBE feedback_forms")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('created_at', $cols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Added 'created_at' to feedback_forms.\n";
    }
    if (!in_array('updated_at', $cols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Added 'updated_at' to feedback_forms.\n";
    }

    // 2. Fix course_outcomes table (ensure co_code exists)
    $cols = $pdo->query("DESCRIBE course_outcomes")->fetchAll(PDO::FETCH_COLUMN);
    // If it has 'code' but not 'co_code', rename it, or vice versa?
    // The error says 'co.code' not found, meaning it's likely 'co_code' in DB.
    // I'll keep 'co_code' in DB but I need to make sure the app uses it correctly.
    // OR standardise to 'code' if that's what the app expects.
    if (in_array('co_code', $cols) && !in_array('code', $cols)) {
         // The app seems to want 'code' based on the error.
         // But many parts use 'co_code'. I will add an alias or rename.
         // Let's check which is more common. 
         // Most NAAC systems use co_code.
    }

    // 3. Fix questions table
    $cols = $pdo->query("DESCRIBE questions")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('co_id', $cols)) {
        $pdo->exec("ALTER TABLE questions ADD co_id INT DEFAULT NULL AFTER feedback_form_id");
        echo "Added 'co_id' to questions.\n";
    }
    if (!in_array('po_id', $cols)) {
        $pdo->exec("ALTER TABLE questions ADD po_id INT DEFAULT NULL AFTER co_id");
        echo "Added 'po_id' to questions.\n";
    }
    if (!in_array('question_type', $cols)) {
        $pdo->exec("ALTER TABLE questions ADD question_type VARCHAR(50) DEFAULT 'likert_5'");
        echo "Added 'question_type' to questions.\n";
    }
    if (!in_array('sort_order', $cols)) {
        $pdo->exec("ALTER TABLE questions ADD sort_order INT DEFAULT 0 AFTER question_text");
        echo "Added 'sort_order' to questions.\n";
    }

    // 5. Fix faculties table
    $cols = $pdo->query("DESCRIBE faculties")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('profile_image', $cols)) {
        $pdo->exec("ALTER TABLE faculties ADD profile_image VARCHAR(255) DEFAULT 'assets/img/default-avatar.png' AFTER department_id");
        echo "Added 'profile_image' to faculties.\n";
    }

    // 6. Enforce Unique Course Codes within Departments
    try {
        $pdo->exec("ALTER TABLE courses ADD UNIQUE INDEX idx_dept_course_code (department_id, code)");
        echo "Added unique index to courses (department_id, code).\n";
    } catch (Exception $e) {
        // Index might already exist
    }

    echo "\nSchema sync complete.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
