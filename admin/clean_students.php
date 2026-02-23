<?php
/**
 * Utility to clean up all student data and responses
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

try {
    $pdo->beginTransaction();

    // Delete responses (this cascades to response_answers)
    $pdo->exec("DELETE FROM responses");
    
    // Delete students
    $pdo->exec("DELETE FROM students");

    $pdo->commit();
    setFlash('success', 'All student data and responses have been successfully removed.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Error cleaning data: ' . $e->getMessage());
}

redirect(APP_URL . '/admin/students.php');
