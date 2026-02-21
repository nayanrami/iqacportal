<?php
/**
 * NAAC Feedback System - Database Setup
 * Run this to initialize/reset the database
 */

$host = 'localhost';
$user = 'root';
$pass = 'NEO007007';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Drop old database for clean reset
    $pdo->exec("DROP DATABASE IF EXISTS `naac_feedback`");
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $pdo->exec($sql);
    
    // Now update the admin password with proper bcrypt hash
    $pdo->exec("USE naac_feedback");
    $hash = password_hash('iqac', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'iqac'");
    $stmt->execute([$hash]);
    
    echo "<div style='font-family:Inter,sans-serif;max-width:500px;margin:80px auto;padding:40px;background:#10b981;color:white;border-radius:16px;text-align:center;'>
        <h1>✅ Setup Complete!</h1>
        <p>Database <strong>naac_feedback</strong> created &amp; reset successfully.</p>
        <p>Admin: <strong>iqac</strong> / <strong>iqac</strong></p>
        <a href='login.php' style='display:inline-block;margin-top:20px;padding:12px 32px;background:white;color:#10b981;border-radius:8px;text-decoration:none;font-weight:600;'>Go to Login →</a>
        <br><a href='admin/seed_all.php' style='display:inline-block;margin-top:10px;padding:12px 32px;background:rgba(255,255,255,0.2);color:white;border-radius:8px;text-decoration:none;font-weight:600;'>Seed Data →</a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family:Inter,sans-serif;max-width:500px;margin:80px auto;padding:40px;background:#ef4444;color:white;border-radius:16px;text-align:center;'>
        <h1>❌ Setup Failed</h1>
        <p>" . $e->getMessage() . "</p>
    </div>";
}
