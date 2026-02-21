<?php
/**
 * Admin Login - Light Theme
 */
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) { redirect(APP_URL . '/admin/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_dept_id'] = $user['department_id']; // NULL for Superadmin
        
        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect(APP_URL . '/admin/');
    } else {
        setFlash('danger', 'Invalid username or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="font-sans min-h-screen flex items-center justify-center">
    <div class="bg-animated"></div>

    <div class="glass-card max-w-md w-full mx-5 overflow-hidden animate-scale-in">
        <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
        <div class="p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl shadow-indigo-500/20 mx-auto mb-4">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="text-2xl font-black gradient-text mb-1">Admin Login</h2>
                <p class="text-sm text-gray-400"><?= APP_NAME ?> — <?= APP_INSTITUTE ?></p>
            </div>

            <?php if ($flash = getFlash()): ?>
                <div class="flash-<?= $flash['type'] ?> mb-6">
                    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="text" name="username" required autofocus
                               class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 transition"
                               placeholder="Enter username">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="password" name="password" required
                               class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 transition"
                               placeholder="Enter password">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?= APP_URL ?>" class="text-xs text-gray-400 hover:text-indigo-500 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Feedback Portal
                </a>
            </div>
        </div>
    </div>
</body>
</html>
