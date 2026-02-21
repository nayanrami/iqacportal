<?php
/**
 * Migration - Add Department Support to Admins
 */
require_once __DIR__ . '/../functions.php';

try {
    // 1. Add department_id column to admins table if not exists
    $pdo->exec("ALTER TABLE admins ADD COLUMN department_id INT DEFAULT NULL AFTER full_name");
    $pdo->exec("ALTER TABLE admins ADD CONSTRAINT fk_admin_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE");
    
    // 2. Update 'IT' department code to 'BTECHIT' if requested
    $pdo->prepare("UPDATE departments SET code = 'BTECHIT' WHERE code = 'IT'")->execute();
    
    // 3. Get all departments to create logins
    $depts = $pdo->query("SELECT * FROM departments")->fetchAll();
    
    foreach ($depts as $d) {
        $username = $d['code'];
        $password = password_hash($d['code'], PASSWORD_BCRYPT);
        $fullName = $d['name'] . ' Coordinator';
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name, department_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $fullName, $d['id']]);
            echo "Created admin for department: {$d['code']}<br>";
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET department_id = ? WHERE username = ?");
            $stmt->execute([$d['id'], $username]);
            echo "Updated existing admin for department: {$d['code']}<br>";
        }
    }
    
    // 4. Ensure Superadmin 'iqac' has NULL department_id (Full Access)
    $pdo->exec("UPDATE admins SET department_id = NULL WHERE username = 'iqac'");

    echo "<div style='font-family:sans-serif; padding:20px; background:#dcfce7; color:#166534; border-radius:10px; margin:20px 0;'>
        <strong>Migration Success!</strong><br>
        Department-wise logins have been created. (Username = Department Code, Password = Same as Username)<br>
        <a href='../login.php'>Go to Login</a>
    </div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:10px; margin:20px 0;'>
        <strong>Migration Failed:</strong><br>
        " . $e->getMessage() . "
    </div>";
}
