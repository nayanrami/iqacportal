<?php
/**
 * Migration - Add Department Support to Feedback Forms
 */
require_once __DIR__ . '/../includes/functions.php';

try {
    // 1. Add department_id column to feedback_forms table if not exists
    $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN department_id INT DEFAULT NULL AFTER category");
    $pdo->exec("ALTER TABLE feedback_forms ADD CONSTRAINT fk_form_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL");
    
    // 2. Add form_type if it doesn't exist (it seems I missed it in database.sql but used it in functions.php)
    // Check if column exists
    $stmt = $pdo->query("DESCRIBE feedback_forms");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('form_type', $cols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN form_type VARCHAR(50) DEFAULT 'general' AFTER title");
    }
    if (!in_array('category', $cols)) {
        $pdo->exec("ALTER TABLE feedback_forms ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER form_type");
    }

    echo "<div style='font-family:sans-serif; padding:20px; background:#dcfce7; color:#166534; border-radius:10px; margin:20px 0;'>
        <strong>Migration Success!</strong><br>
        feedback_forms table has been updated with department_id and form_type support.<br>
        <a href='../admin/'>Back to Dashboard</a>
    </div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:10px; margin:20px 0;'>
        <strong>Migration Failed:</strong><br>
        " . $e->getMessage() . "
    </div>";
}
