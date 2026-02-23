<?php
/**
 * Migration: Departmental Academic Outcomes (PO, PEO, PSO, CO)
 * Sets up the schema for mapping accreditation benchmarks to departments.
 */
require_once __DIR__ . '/config.php';

// Use constants from config.php
try {
    $existingCols = array_column($pdo->query("SHOW COLUMNS FROM departments")->fetchAll(), 'Field');
    
    if (!in_array('vision', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN vision TEXT DEFAULT NULL AFTER name");
    }
    if (!in_array('mission', $existingCols)) {
        $pdo->exec("ALTER TABLE departments ADD COLUMN mission TEXT DEFAULT NULL AFTER vision");
    }
    echo "Department table enhanced with vision and mission fields.\n";
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
