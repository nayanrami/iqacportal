<?php
/**
 * IQAC Portal - Public Landing Page v2.0
 * Three sections: CO Attainment | Exit Survey | Department Feedback
 * + Available Feedback Forms listing
 */
require_once __DIR__ . '/functions.php';

// Force Student Login (Admins are redirected out of here in login.php)
if (!isset($_SESSION['student_id'])) {
    redirect(APP_URL . '/login.php');
}

$studentId = $_SESSION['student_id'];
$deptId = $_SESSION['student_dept_id'];
$semester = $_SESSION['student_semester'];

// Get department info
$stmtDept = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmtDept->execute([$deptId]);
$department = $stmtDept->fetch();

// AJAX endpoints (Modified for student scoping)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Get ALL available forms for the logged-in student's department & semester
    if ($_GET['ajax'] === 'all_forms') {
        $stmt = $pdo->prepare("
            SELECT ff.*, c.name as course_name, c.code as course_code,
                   (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as question_count,
                   (SELECT id FROM responses WHERE feedback_form_id = ff.id AND student_id = ?) as filled_id
            FROM feedback_forms ff
            LEFT JOIN courses c ON c.id = ff.course_id
            WHERE ff.department_id = ? 
            AND (ff.semester IS NULL OR ff.semester = ?)
            AND ff.is_active = 1
            AND (ff.expires_at IS NULL OR ff.expires_at >= CURDATE())
            ORDER BY ff.form_type, ff.category, ff.semester, ff.title");
        $stmt->execute([$studentId, $deptId, $semester]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    echo json_encode([]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Feedback Portal</title>
    <meta name="description" content="IQAC Portal - Submit feedback for NAAC accreditation.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="font-sans min-h-screen text-gray-800">
    <div class="bg-animated"></div>

    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200 px-8 h-[70px] flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-lg shadow-lg shadow-indigo-500/20">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-gray-800"><?= APP_NAME ?></h1>
                <p class="text-[10px] text-gray-400 font-medium"><?= APP_INSTITUTE ?></p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-gray-700"><?= $_SESSION['student_name'] ?></p>
                <p class="text-[10px] text-indigo-500 font-black uppercase tracking-widest"><?= $department['code'] ?> • Sem <?= $semester ?></p>
            </div>
            <a href="logout.php" class="px-4 py-2 bg-gray-50 text-gray-500 rounded-xl text-sm font-bold hover:bg-gray-100 transition">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-5 py-10">
        <!-- Hero -->
        <div class="text-center py-8 mb-6 animate-slide-down">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 border border-indigo-200 rounded-full text-xs font-semibold text-indigo-600 mb-4">
                <i class="fas fa-university"></i> <?= $department['name'] ?>
            </div>
            <h2 class="text-4xl md:text-5xl font-black gradient-text leading-tight mb-3">Your Feedback Desk</h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">Help us improve by providing your valuable feedback for Semester <?= $semester ?>.</p>
        </div>

        <!-- Student Context Banner (Replaces Step 1) -->
        <div class="mb-8 animate-slide-down" style="animation-delay:100ms">
            <div class="glass-card p-6 flex flex-col md:flex-row items-center justify-between gap-6 border-l-4 border-indigo-500">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 text-2xl">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-800"><?= $_SESSION['student_name'] ?></h3>
                        <p class="text-sm text-gray-400">Enrollment: <?= $_SESSION['student_enrollment'] ?></p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="text-center px-6 border-x border-gray-100">
                        <div class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-1 text-center">Semester</div>
                        <div class="text-xl font-black text-gray-700">0<?= $semester ?></div>
                    </div>
                    <div class="text-center px-6">
                        <div class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-1">Department</div>
                        <div class="text-xl font-black text-gray-700"><?= $department['code'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div id="main-content" class="animate-slide-down" style="animation-delay:200ms">
            <div class="tab-nav mb-6">
                <button class="tab-btn active" data-tab="all" onclick="switchTab('all')">
                    <i class="fas fa-list"></i>
                    <span>All Available Forms</span>
                    <span class="badge" id="all-count">0</span>
                </button>
            </div>

            <!-- All Forms Tab -->
            <div class="tab-content active" id="tab-all">
                <div id="all-forms-grid" class="space-y-8"></div>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden text-center py-20">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-clipboard-check text-4xl text-gray-200"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-800 mb-2">You're All Caught Up!</h3>
                <p class="text-gray-400 max-w-sm mx-auto">There are no active feedback forms for your semester at this moment.</p>
            </div>
        </div>
    </div>

    <script>
    const typeConfig = {
        co_attainment: { icon: 'fa-bullseye', color: 'from-violet-500 to-purple-600', label: 'CO Attainment', badge: 'co' },
        exit_survey: { icon: 'fa-door-open', color: 'from-amber-500 to-orange-600', label: 'Exit Survey', badge: 'po' },
        dept_feedback: { icon: 'fa-building', color: 'from-emerald-500 to-teal-600', label: 'Dept Feedback', badge: '' },
        general: { icon: 'fa-clipboard-list', color: 'from-blue-500 to-indigo-600', label: 'General', badge: '' }
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadForms();
    });

    function loadForms() {
        fetch(`?ajax=all_forms`)
            .then(r => r.json())
            .then(forms => {
                document.getElementById('all-count').textContent = forms.length;
                if (forms.length === 0) {
                    document.getElementById('empty-state').classList.remove('hidden');
                    return;
                }
                renderAllForms(forms);
            });
    }

    function makeFormCard(f, colorClass, icon) {
        const courseBadge = f.course_name ? `<span class="outcome-badge co"><i class="fas fa-book mr-1"></i>${f.course_code}</span>` : '';
        const typeBadge = typeConfig[f.form_type] ? `<span class="outcome-badge ${typeConfig[f.form_type].badge}">${typeConfig[f.form_type].label}</span>` : '';
        const isFilled = f.filled_id !== null;

        return `
        <div class="form-card animate-scale-in ${isFilled ? 'opacity-70 grayscale-[0.5]' : ''}">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${colorClass}"></div>
            <div class="form-card-body">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br ${colorClass} rounded-xl flex items-center justify-center text-white shadow-lg flex-shrink-0">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="flex gap-1">${courseBadge} ${typeBadge}</div>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-2 leading-snug">${f.title}</h3>
                <div class="flex items-center justify-between mt-4">
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">
                        <i class="fas fa-list-ol mr-1"></i>${f.question_count} Questions
                    </span>
                    ${isFilled 
                        ? `<span class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black uppercase tracking-widest border border-emerald-100">
                            <i class="fas fa-check-circle mr-1"></i> Completed
                           </span>`
                        : `<a href="submit.php?id=${f.id}" class="btn-primary text-xs py-2 px-4 shadow-indigo-100">
                            <i class="fas fa-pen mr-1"></i> Start Feedback
                           </a>`
                    }
                </div>
            </div>
        </div>`;
    }

    function renderAllForms(forms) {
        const grid = document.getElementById('all-forms-grid');
        
        // Group by category to make it clean
        const grouped = {};
        forms.forEach(f => {
            const cat = f.category || 'General';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(f);
        });

        let html = '';
        for (const [cat, catForms] of Object.entries(grouped)) {
            html += `
            <div class="animate-slide-down">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-2 h-6 bg-indigo-500 rounded-full"></div>
                    <h4 class="font-black text-xs uppercase tracking-[0.2em] text-gray-400">${cat}</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    ${catForms.map(f => {
                        const cfg = typeConfig[f.form_type] || typeConfig.general;
                        return makeFormCard(f, cfg.color, cfg.icon);
                    }).join('')}
                </div>
            </div>`;
        }
        grid.innerHTML = html;
    }
    </script>

    <!-- Footer -->
    <footer class="mt-16 border-t border-gray-200 bg-white/60 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto px-5 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-sm shadow-lg">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-700"><?= APP_NAME ?></div>
                        <div class="text-[10px] text-gray-400"><?= APP_INSTITUTE ?></div>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400">NAAC Accreditation Feedback Collection System</p>
                    <p class="text-[10px] text-gray-300 mt-1">CO Attainment • PO Exit Survey • Department Feedback</p>
                </div>
                <div class="text-xs text-gray-400">&copy; <?= date('Y') ?> <?= APP_DEPT ?></div>
            </div>
        </div>
    </footer>
</body>
</html>
