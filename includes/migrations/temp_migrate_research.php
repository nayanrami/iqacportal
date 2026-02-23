<?php
require_once __DIR__ . '/functions.php';

try {
    $queries = [
        "ALTER TABLE research_records ADD COLUMN issn_isbn VARCHAR(50) DEFAULT NULL AFTER journal_conference",
        "ALTER TABLE research_records ADD COLUMN indexing ENUM('Scopus', 'Web of Science', 'UGC Care', 'Peer Reviewed', 'Others', 'None') DEFAULT 'None' AFTER issn_isbn",
        "ALTER TABLE research_records ADD COLUMN author_role VARCHAR(100) DEFAULT NULL AFTER indexing",
        "ALTER TABLE research_records ADD COLUMN collaborating_agency VARCHAR(255) DEFAULT NULL AFTER author_role"
    ];
    
    foreach ($queries as $q) {
        try {
            $pdo->exec($q);
            echo "Executed: $q\n";
        } catch (Exception $e) {
            echo "Skipped: $q (" . $e->getMessage() . ")\n";
        }
    }
    echo "Migration completed.\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
