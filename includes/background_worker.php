<?php
/**
 * Background Task Worker
 * This script runs in the background and executes specific tasks.
 */
require_once __DIR__ . '/functions.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

$taskId = isset($argv[1]) ? intval($argv[1]) : 0;
if (!$taskId) die("No Task ID provided.");

// Fetch task
$stmt = $pdo->prepare("SELECT * FROM background_tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) die("Task not found.");

// Update status to running
$pdo->prepare("UPDATE background_tasks SET status = 'running', progress = 5 WHERE id = ?")->execute([$taskId]);

try {
    switch ($task['task_type']) {
        case 'seed':
            define('BACKGROUND_TASK_ID', $taskId);
            require_once __DIR__ . '/../admin/seed_all.php';
            break;
            
        case 'sync':
            define('BACKGROUND_TASK_ID', $taskId);
            require_once __DIR__ . '/migrations/sync_faculties.php';
            break;
            
        default:
            throw new Exception("Unknown task type: " . $task['task_type']);
    }

    // Mark as completed
    $pdo->prepare("UPDATE background_tasks SET status = 'completed', progress = 100, message = 'Task completed successfully' WHERE id = ?")->execute([$taskId]);

} catch (Exception $e) {
    // Mark as failed
    $pdo->prepare("UPDATE background_tasks SET status = 'failed', message = ? WHERE id = ?")->execute([$e->getMessage(), $taskId]);
}
