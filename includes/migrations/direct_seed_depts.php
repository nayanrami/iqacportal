<?php
require_once __DIR__ . '/../functions.php';

try {
    // 1. Ensure columns exist safely
    $cols = $pdo->query("DESCRIBE departments")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('hod_phone', $cols)) {
        $pdo->exec("ALTER TABLE departments ADD hod_phone VARCHAR(20) DEFAULT NULL AFTER hod_name");
    }
    if (!in_array('hod_email', $cols)) {
        $pdo->exec("ALTER TABLE departments ADD hod_email VARCHAR(100) DEFAULT NULL AFTER hod_phone");
    }
    
    echo "Schema updated with HOD contact columns.\n\n";

    $depts = [
        ['Artificial Intelligence', 'AI', 'Dr. Dinesh Prajapati', '+91-9925042680', 'coordinator.ai@adit.ac.in'],
        ['Automobile Engineering', 'AE', 'Dr. Sanjay Patel', '+91-9426233297', 'head.ae@adit.ac.in'],
        ['Civil Engineering', 'CIVIL', 'Dr. Rajiv Bhatt', '+91-9428488052', 'head.civil@adit.ac.in'],
        ['Computer Engineering', 'CP', 'Dr. Bhagirath Prajapati', '+91-9824337174', 'head.cp@adit.ac.in'],
        ['Comp. Science & Design', 'CSD', 'Dr. Gopi Bhatt', '+91-9979270920', 'head.cds@adit.ac.in'],
        ['Dairy Technology', 'DT', 'Dr. Mitesh Shah', '+91-9429543108', 'head.dt@adit.ac.in'],
        ['Food Processing Tech.', 'FPT', 'Dr. S Srivastav', '+91-9428901917', 'head.fpt@adit.ac.in'],
        ['Electronics & Comm.', 'EC', 'Dr. Pravin R. Prajapati', '+91-9429367045', 'head.ec@adit.ac.in'],
        ['Electrical Engineering', 'EE', 'Dr. Hardik Shah', '+91-9824394393', 'head.ee@adit.ac.in'],
        ['Information Technology', 'IT', 'Dr. N C Chauhan', '+91-9377559385', 'head.it@adit.ac.in'],
        ['Mechanical Engineering', 'ME', 'Dr. Y D Patel', '+91-9428799545', 'head.me@adit.ac.in']
    ];

    echo str_pad("DEPARTMENT", 30) . " | " . str_pad("USERNAME", 15) . " | " . "PASSWORD\n";
    echo str_repeat("-", 60) . "\n";

    foreach ($depts as $d) {
        $name = $d[0];
        $code = $d[1];
        $hod = $d[2];
        $phone = $d[3];
        $email = $d[4];

        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE code = ?");
        $stmt->execute([$code]);
        $deptId = $stmt->fetchColumn();

        if ($deptId) {
            $pdo->prepare("UPDATE departments SET name = ?, hod_name = ?, hod_phone = ?, hod_email = ?, est_year = 2005 WHERE id = ?")
                ->execute([$name, $hod, $phone, $email, $deptId]);
        } else {
            $pdo->prepare("INSERT INTO departments (name, code, hod_name, hod_phone, hod_email, est_year) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$name, $code, $hod, $phone, $email, 2005]);
            $deptId = $pdo->lastInsertId();
        }
        
        $username = "BTECH-" . $code;
        $password = $code . "@2026";
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        
        // Admin account
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO admins (username, password, full_name, department_id, role) VALUES (?, ?, ?, ?, ?)")
                ->execute([$username, $hashed, $hod, $deptId, 'deptadmin']);
        }

        echo str_pad($name, 30) . " | " . str_pad($username, 15) . " | " . $password . "\n";
    }

    echo "\nAll process complete.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
