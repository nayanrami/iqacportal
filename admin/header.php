<?php
/**
 * Admin Header - Light Theme with Sidebar
 */
require_once __DIR__ . '/../config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        function confirmDelete(url, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                window.location.href = url;
            }
        }
    </script>
</head>
<body class="font-sans text-gray-800" style="background:linear-gradient(135deg,#f0f4ff 0%,#faf5ff 50%,#ecfdf5 100%);">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="px-6 mb-8">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-lg shadow-lg">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-gray-800"><?= APP_NAME ?></h1>
                <p class="text-[10px] text-gray-400">Admin Panel</p>
            </div>
        </div>
    </div>
    <nav class="px-3 space-y-1">
        <?php
        $navItems = [
            ['index.php', 'Dashboard', 'fa-chart-pie', 'indigo'],
            ['feedback_forms.php', 'Feedback Forms', 'fa-clipboard-list', 'purple'],
            ['courses.php', 'Courses', 'fa-book', 'blue'],
            ['departments.php', 'Departments', 'fa-building', 'emerald'],
            ['responses.php', 'Responses', 'fa-comments', 'amber'],
            ['analysis.php', 'NAAC Analysis', 'fa-chart-bar', 'rose'],
            ['co_analysis.php', 'CO Analysis', 'fa-bullseye', 'cyan'],
            ['students.php', 'Students', 'fa-user-graduate', 'indigo'],
            ['seed_all.php', 'Seed Data', 'fa-database', 'violet'],
        ];

        $isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
        
        foreach ($navItems as $item):
            // Hide Departments and Seed Data for Dept Admins
            if ($isDeptAdmin && in_array($item[0], ['departments.php', 'seed_all.php'])) continue;
            
            $isActive = $currentPage === $item[0];
        ?>
            <a href="<?= APP_URL ?>/admin/<?= $item[0] ?>"
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                      <?= $isActive
                          ? 'bg-gradient-to-r from-' . $item[3] . '-50 to-' . $item[3] . '-50/50 text-' . $item[3] . '-700 shadow-sm border border-' . $item[3] . '-100'
                          : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' ?>">
                <i class="fas <?= $item[2] ?> w-5 text-center <?= $isActive ? 'text-' . $item[3] . '-500' : 'text-gray-400' ?>"></i>
                <?= $item[1] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="absolute bottom-4 left-0 right-0 px-6 space-y-2">
        <a href="<?= APP_URL ?>" target="_blank"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 transition">
            <i class="fas fa-external-link-alt w-4"></i> Public View
        </a>
        <a href="<?= APP_URL ?>/admin/logout.php"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
            <i class="fas fa-sign-out-alt w-4"></i> Logout
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="admin-content">
    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
            <p class="text-sm text-gray-400 mt-1">
                Welcome, <?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?>
                <?php if($isDeptAdmin): ?>
                    <span class="ml-2 px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[10px] font-bold uppercase tracking-wider">Dept Coord</span>
                <?php else: ?>
                    <span class="ml-2 px-2 py-0.5 bg-rose-50 text-rose-500 rounded text-[10px] font-bold uppercase tracking-wider">IQAC Coordinator</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="text-sm text-gray-400">
            <i class="fas fa-calendar mr-1"></i> <?= date('d M Y') ?>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if ($flash = getFlash()): ?>
        <div class="flash-<?= $flash['type'] ?> mb-6 animate-slide-down">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>
