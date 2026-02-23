<?php
/**
 * Admin Header - Light Theme with Sidebar
 */
require_once __DIR__ . '/../includes/config.php';
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
        function toggleNavGroup(id) {
            const group = document.getElementById('group-' + id);
            const chevron = document.getElementById('chevron-' + id);
            
            if (group.classList.contains('max-h-0')) {
                group.classList.remove('max-h-0', 'opacity-0');
                group.classList.add('max-h-[500px]', 'opacity-100');
                chevron.classList.add('rotate-90');
            } else {
                group.classList.add('max-h-0', 'opacity-0');
                group.classList.remove('max-h-[500px]', 'opacity-100');
                chevron.classList.remove('rotate-90');
            }
        }
    </script>
</head>
<body class="font-sans text-gray-800" style="background:linear-gradient(135deg,#f0f4ff 0%,#faf5ff 50%,#ecfdf5 100%);">

<!-- Sidebar -->
<aside class="admin-sidebar bg-white border-r border-gray-100 shadow-2xl shadow-indigo-50/20">
            <?php 
            $hubLink = 'naac_hub.php';
            if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'deptadmin') {
                $hubLink = 'dept_naac_hub.php';
            }
            
            // Define Role Labels and Styles early for use in sidebar
            $role = $_SESSION['admin_role'] ?? 'deptadmin';
            $roleLabel = 'Admin';
            $roleClass = 'bg-gray-100 text-gray-600';
            
            if (strpos($role, 'criterion') !== false) {
                $roleLabel = strtoupper(str_replace('_', ' ', $role));
                $roleClass = 'bg-indigo-600 text-white shadow-lg shadow-indigo-100';
            } elseif ($role === 'superadmin') {
                $roleLabel = 'IQAC Coordinator';
                $roleClass = 'bg-rose-50 text-rose-500';
            } elseif ($role === 'university') {
                $roleLabel = 'University Registrar';
                $roleClass = 'bg-amber-50 text-amber-600 border border-amber-100';
            } elseif ($role === 'deptadmin') {
                $roleLabel = 'Dept Coordinator';
                $roleClass = 'bg-emerald-50 text-emerald-500';
            }
            
            // Branding
            $collegeLogo = APP_URL . '/assets/img/adit.png';

            $deptName = '';
            if (isset($_SESSION['admin_dept_id'])) {
                $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                $stmt->execute([$_SESSION['admin_dept_id']]);
                $deptName = $stmt->fetchColumn();
            }
            ?>
        </div>

    <!-- User Profile in Sidebar -->
    <div class="px-6 mb-6">
        <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/50">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-white overflow-hidden flex items-center justify-center shadow-lg shadow-indigo-100 border border-indigo-50">
                    <img src="<?= $collegeLogo ?>" alt="Avatar" class="w-7 h-7 object-contain">
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-black text-gray-800 truncate"><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></p>
                    <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-wider"><?= $roleLabel ?? 'Administrator' ?></p>
                </div>
            </div>
            <?php if ($deptName): ?>
            <div class="flex items-center gap-2 text-[9px] text-gray-500 font-medium bg-white/50 p-1.5 rounded-lg border border-indigo-50/50">
                <i class="fas fa-building text-indigo-300"></i>
                <span class="truncate"><?= sanitize($deptName) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <nav class="px-3 space-y-1">
        <?php
        $navItems = [
            ['index.php', 'Dashboard', 'fa-chart-pie', 'indigo', 'Core Overview'],
            ['naac_hub.php', 'NAAC Accreditation', 'fa-award', 'indigo', 'Core Overview'],
            
            ['departments.php', 'Departments', 'fa-building', 'emerald', 'Institutional'],
            ['faculties.php', 'Faculty Management', 'fa-user-tie', 'teal', 'Institutional'],
            ['students.php', 'Students', 'fa-user-graduate', 'indigo', 'Institutional'],
            
            ['courses.php', 'Courses', 'fa-book', 'blue', 'Academic & Feedback'],
            ['mapping.php', 'Course Mapping', 'fa-map-marked-alt', 'indigo', 'Academic & Feedback'],
            ['feedback_forms.php', 'Feedback Forms', 'fa-clipboard-list', 'purple', 'Academic & Feedback'],
            ['responses.php', 'Responses', 'fa-comments', 'amber', 'Academic & Feedback'],
            
            ['analysis.php', 'NAAC Analysis', 'fa-chart-bar', 'rose', 'Analysis & Insights'],
            ['student_analysis.php', 'Student Analysis', 'fa-user-check', 'indigo', 'Analysis & Insights'],
            ['co_analysis.php', 'CO Analysis', 'fa-bullseye', 'cyan', 'Analysis & Insights'],
            ['research.php', 'Research Data', 'fa-microscope', 'indigo', 'Analysis & Insights'],
            ['research_analysis.php', 'Research Analysis', 'fa-chart-line', 'purple', 'Analysis & Insights'],
            
            ['governance.php', 'Portal Governance', 'fa-landmark', 'emerald', 'System Administration'],
            ['settings.php', 'Portal Settings', 'fa-cog', 'slate', 'System Administration'],
        ];

        // Filter nav items based on role
        $role = $_SESSION['admin_role'] ?? 'deptadmin';
        $isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;

        $filteredNavItems = [];
        foreach ($navItems as $item) {
            $showItem = false;
            
            if ($role === 'superadmin' || $role === 'university') {
                $showItem = true;
            } elseif ($role === 'deptadmin') {
                if (!in_array($item[0], ['departments.php', 'settings.php', 'governance.php'])) {
                    $showItem = true;
                }
            } else {
                if (in_array($item[0], ['index.php', 'naac_hub.php'])) $showItem = true;
                if ($role === 'criterion_1' && in_array($item[0], ['compliance.php', 'feedback_forms.php'])) $showItem = true;
                if ($role === 'criterion_2' && in_array($item[0], ['sss_management.php', 'co_analysis.php', 'students.php'])) $showItem = true;
                if ($role === 'criterion_3' && in_array($item[0], ['research.php', 'research_analysis.php'])) $showItem = true;
                if ($role === 'criterion_4' && in_array($item[0], ['infrastructure.php'])) $showItem = true;
                if ($role === 'criterion_5' && in_array($item[0], ['progression.php', 'students.php'])) $showItem = true;
                if ($role === 'criterion_6' && in_array($item[0], ['governance.php'])) $showItem = true;
                if ($role === 'criterion_7' && in_array($item[0], ['best_practices.php'])) $showItem = true;
            }

            if ($showItem) $filteredNavItems[] = $item;
        }

        $lastCategory = '';
        $openFirst = true;
        foreach ($filteredNavItems as $index => $item):
            $isActive = $currentPage === $item[0];
            $color = $item[3];
            $category = $item[4] ?? '';
            $catId = strtolower(str_replace([' ', '&'], ['_', ''], $category));
            
            if ($category !== $lastCategory): 
                if ($lastCategory !== '') echo '</div></div>';
                
                // Check if any item in this category is active to auto-expand
                $isExpanded = false;
                foreach ($filteredNavItems as $sub) {
                    if (($sub[4] ?? '') === $category && $currentPage === $sub[0]) {
                        $isExpanded = true;
                        break;
                    }
                }
                ?>
                <div class="category-group mb-1">
                    <button onclick="toggleNavGroup('<?= $catId ?>')" 
                            class="w-full flex items-center justify-between px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-indigo-500 transition-colors">
                        <span><?= $category ?></span>
                        <i id="chevron-<?= $catId ?>" class="fas fa-chevron-right text-[8px] transition-transform duration-200 <?= $isExpanded ? 'rotate-90' : '' ?>"></i>
                    </button>
                    <div id="group-<?= $catId ?>" class="space-y-1 overflow-hidden transition-all duration-300 <?= $isExpanded ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0' ?>">
                <?php $lastCategory = $category; ?>
            <?php endif;
            
            // Tailwind classes... (rest of loop logic)
            $activeClasses = [
                'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                'purple' => 'bg-purple-50 text-purple-700 border-purple-100',
                'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
                'rose' => 'bg-rose-50 text-rose-700 border-rose-100',
                'teal' => 'bg-teal-50 text-teal-700 border-teal-100',
                'cyan' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
                'slate' => 'bg-slate-50 text-slate-700 border-slate-100'
            ];
            
            $iconClasses = [
                'indigo' => 'text-indigo-500',
                'emerald' => 'text-emerald-500',
                'purple' => 'text-purple-500',
                'blue' => 'text-blue-500',
                'amber' => 'text-amber-500',
                'rose' => 'text-rose-500',
                'teal' => 'text-teal-500',
                'cyan' => 'text-cyan-500',
                'slate' => 'text-slate-500'
            ];

            $activeStyle = $activeClasses[$color] ?? $activeClasses['indigo'];
            $iconStyle = $iconClasses[$color] ?? $iconClasses['indigo'];
        ?>
            <a href="<?= APP_URL ?>/admin/<?= $item[0] ?>"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-xl text-sm font-medium transition-all duration-200
                      <?= $isActive
                          ? "$activeStyle shadow-sm border"
                          : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' ?>">
                <i class="fas <?= $item[2] ?> w-5 text-center <?= $isActive ? $iconStyle : 'text-gray-400' ?>"></i>
                <?= $item[1] ?>
            </a>
        <?php endforeach; ?>
        </div></div> <!-- Close last group -->
    </nav>
    <div class="absolute bottom-4 left-0 right-0 px-6 space-y-2">
        <a href="<?= APP_URL ?>" target="_blank"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 transition">
            <i class="fas fa-external-link-alt w-4"></i> Public View
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="admin-content">
    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-8 bg-white/40 backdrop-blur-md p-4 rounded-3xl border border-white/60 shadow-xl shadow-indigo-50/20">
        <div class="flex items-center gap-4">
            <div class="hidden md:flex w-12 h-12 bg-white rounded-2xl items-center justify-center shadow-sm text-indigo-500 border border-gray-50">
                 <i class="fas <?= $navItems[array_search($currentPage, array_column($navItems, 0))][2] ?? 'fa-th-large' ?> text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-gray-800 leading-tight"><?= $pageTitle ?? 'Dashboard' ?></h2>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-black text-gray-800"><?= date('l') ?></p>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= date('d F Y') ?></p>
            </div>
            <div class="h-10 w-px bg-gray-100"></div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3 bg-white/50 border border-indigo-50 p-1.5 pr-4 rounded-2xl shadow-sm">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-50 to-purple-50 flex items-center justify-center text-indigo-500 border border-indigo-100 shadow-inner">
                        <i class="fas fa-bell text-xs"></i>
                    </div>
                    <div class="hidden lg:block">
                        <p class="text-[10px] font-black text-gray-800 leading-none mb-0.5"><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></p>
                        <p class="text-[8px] font-bold text-indigo-400 uppercase tracking-widest"><?= $roleLabel ?></p>
                    </div>
                </div>
                <a href="logout.php" class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 border border-rose-100 shadow-sm hover:bg-rose-500 hover:text-white transition-all cursor-pointer" title="Logout">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if ($flash = getFlash()): ?>
        <div class="flash-<?= $flash['type'] ?> mb-6 animate-slide-down">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>
