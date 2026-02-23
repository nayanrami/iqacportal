<?php
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'start') {
    $type = $_GET['type'] ?? '';
    if (!in_array($type, ['seed', 'sync'])) {
        echo json_encode(['error' => 'Invalid task type']);
        exit;
    }

    // Create task record
    $stmt = $pdo->prepare("INSERT INTO background_tasks (task_type, status, message) VALUES (?, 'pending', 'Initializing...')");
    $stmt->execute([$type]);
    $taskId = $pdo->lastInsertId();

    // Launch background worker (Windows compatible)
    $phpPath = 'C:\\xampp\\php\\php.exe';
    $workerPath = escapeshellarg(__DIR__ . '/../../includes/background_worker.php');
    $cmd = "start /B $phpPath $workerPath $taskId";
    
    pclose(popen($cmd, "r"));

    echo json_encode(['success' => true, 'task_id' => $taskId]);
    exit;
}

if ($action === 'status') {
    $taskId = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM background_tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    echo json_encode($stmt->fetch());
    exit;
}

if ($action === 'poll') {
    // Return all active/recent tasks
    $stmt = $pdo->query("SELECT * FROM background_tasks WHERE status IN ('pending', 'running') OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY created_at DESC LIMIT 5");
    echo json_encode($stmt->fetchAll());
    exit;
}

echo json_encode(['error' => 'Invalid action']);
