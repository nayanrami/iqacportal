<?php
/**
 * IQAC Portal - Public Landing Page v2.0
 * Three sections: CO Attainment | Exit Survey | Department Feedback
 * + Available Feedback Forms listing
 */
require_once __DIR__ . '/functions.php';

// Get department
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// AJAX endpoints
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Get semesters for a department
    if ($_GET['ajax'] === 'semesters' && isset($_GET['dept_id'])) {
        $stmt = $pdo->prepare("SELECT DISTINCT semester FROM feedback_forms WHERE department_id = ? AND is_active = 1 AND semester IS NOT NULL AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY semester");
        $stmt->execute([intval($_GET['dept_id'])]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Get CO Attainment forms by semester
    if ($_GET['ajax'] === 'co_forms' && isset($_GET['dept_id'])) {
        $deptId = intval($_GET['dept_id']);
        $sem = isset($_GET['semester']) ? intval($_GET['semester']) : null;
        $sql = "SELECT ff.*, c.name as course_name, c.code as course_code,
                   (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as question_count
                FROM feedback_forms ff
                LEFT JOIN courses c ON c.id = ff.course_id
                WHERE ff.department_id = ? AND ff.form_type = 'co_attainment' AND ff.is_active = 1
                AND (ff.expires_at IS NULL OR ff.expires_at >= CURDATE())";
        $params = [$deptId];
        if ($sem) { $sql .= " AND ff.semester = ?"; $params[] = $sem; }
        $sql .= " ORDER BY ff.semester, c.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Get Exit Survey forms
    if ($_GET['ajax'] === 'exit_forms' && isset($_GET['dept_id'])) {
        $stmt = $pdo->prepare("SELECT ff.*, (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as question_count
                FROM feedback_forms ff WHERE ff.department_id = ? AND ff.form_type = 'exit_survey' AND ff.is_active = 1
                AND (ff.expires_at IS NULL OR ff.expires_at >= CURDATE()) ORDER BY ff.title");
        $stmt->execute([intval($_GET['dept_id'])]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Get Department Feedback forms
    if ($_GET['ajax'] === 'dept_forms' && isset($_GET['dept_id'])) {
        $stmt = $pdo->prepare("SELECT ff.*, (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as question_count
                FROM feedback_forms ff WHERE ff.department_id = ? AND ff.form_type = 'dept_feedback' AND ff.is_active = 1
                AND (ff.expires_at IS NULL OR ff.expires_at >= CURDATE()) ORDER BY ff.category, ff.title");
        $stmt->execute([intval($_GET['dept_id'])]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Get ALL available forms for a department
    if ($_GET['ajax'] === 'all_forms' && isset($_GET['dept_id'])) {
        $stmt = $pdo->prepare("SELECT ff.*, c.name as course_name, c.code as course_code,
                   (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as question_count
                FROM feedback_forms ff
                LEFT JOIN courses c ON c.id = ff.course_id
                WHERE ff.department_id = ? AND ff.is_active = 1
                AND (ff.expires_at IS NULL OR ff.expires_at >= CURDATE())
                ORDER BY ff.form_type, ff.category, ff.semester, ff.title");
        $stmt->execute([intval($_GET['dept_id'])]);
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
    <meta name="description" content="IQAC Cell - A D Patel Institute of Technology. Submit feedback for NAAC accreditation.">
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
        <a href="login.php" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-semibold text-gray-500 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50 transition">
            <i class="fas fa-lock mr-1"></i> Admin
        </a>
    </header>

    <div class="max-w-6xl mx-auto px-5 py-10">
        <!-- Hero -->
        <div class="text-center py-8 mb-6 animate-slide-down">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 border border-indigo-200 rounded-full text-xs font-semibold text-indigo-600 mb-4">
                <i class="fas fa-university"></i> <?= APP_INSTITUTE ?>
            </div>
            <h2 class="text-4xl md:text-5xl font-black gradient-text leading-tight mb-3"><?= APP_NAME ?></h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">NAAC Accreditation Feedback Portal — <?= APP_DEPT ?></p>
        </div>

        <!-- Department Selection -->
        <div class="mb-8 animate-slide-down" style="animation-delay:100ms">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg">1</span>
                <h3 class="text-lg font-bold text-gray-700">Select Department</h3>
            </div>
            <div class="flex flex-wrap gap-3" id="dept-grid">
                <?php foreach ($departments as $d): ?>
                    <button onclick="selectDept(<?= $d['id'] ?>, '<?= sanitize($d['name']) ?>')"
                            data-dept="<?= $d['id'] ?>"
                            class="dept-btn px-6 py-3 bg-white border-2 border-gray-200 rounded-xl text-center transition-all duration-300 hover:-translate-y-1 hover:border-indigo-400 hover:shadow-lg cursor-pointer group flex items-center gap-3">
                        <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-500 group-hover:bg-indigo-100 transition">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="text-left">
                            <div class="text-sm font-bold text-gray-700"><?= sanitize($d['code']) ?></div>
                            <div class="text-xs text-gray-400"><?= sanitize($d['name']) ?></div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab Navigation (hidden until department selected) -->
        <div id="main-content" class="hidden animate-slide-down" style="animation-delay:200ms">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg">2</span>
                <h3 class="text-lg font-bold text-gray-700">Choose Feedback Type — <span id="dept-name" class="text-indigo-600"></span></h3>
            </div>

            <div class="tab-nav mb-6">
                <button class="tab-btn active" data-tab="co" onclick="switchTab('co')">
                    <i class="fas fa-bullseye"></i>
                    <span>CO Attainment</span>
                    <span class="badge" id="co-count">0</span>
                </button>
                <button class="tab-btn" data-tab="exit" onclick="switchTab('exit')">
                    <i class="fas fa-door-open"></i>
                    <span>Exit Survey</span>
                    <span class="badge" id="exit-count">0</span>
                </button>
                <button class="tab-btn" data-tab="dept" onclick="switchTab('dept')">
                    <i class="fas fa-building"></i>
                    <span>Department Feedback</span>
                    <span class="badge" id="dept-count">0</span>
                </button>
                <button class="tab-btn" data-tab="all" onclick="switchTab('all')">
                    <i class="fas fa-list"></i>
                    <span>All Forms</span>
                    <span class="badge" id="all-count">0</span>
                </button>
            </div>

            <!-- CO Attainment Tab -->
            <div class="tab-content active" id="tab-co">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600 text-xs font-bold">
                        <i class="fas fa-filter"></i>
                    </span>
                    <h4 class="text-sm font-bold text-gray-600">Filter by Semester</h4>
                </div>
                <div class="flex flex-wrap gap-2 mb-6" id="sem-filter"></div>
                <div id="co-forms-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- Exit Survey Tab -->
            <div class="tab-content" id="tab-exit">
                <div id="exit-forms-grid" class="grid grid-cols-1 gap-4"></div>
            </div>

            <!-- Department Feedback Tab -->
            <div class="tab-content" id="tab-dept">
                <div id="dept-forms-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- All Forms Tab -->
            <div class="tab-content" id="tab-all">
                <div id="all-forms-grid" class="space-y-6"></div>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden text-center py-12">
                <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">No Feedback Forms Available</h3>
                <p class="text-gray-400 text-sm">No active forms found for the selected criteria.</p>
            </div>
        </div>
    </div>

    <script>
    let selectedDept = null;
    let currentTab = 'co';

    const typeConfig = {
        co_attainment: { icon: 'fa-bullseye', color: 'from-violet-500 to-purple-600', label: 'CO Attainment', badge: 'co' },
        exit_survey: { icon: 'fa-door-open', color: 'from-amber-500 to-orange-600', label: 'Exit Survey', badge: 'po' },
        dept_feedback: { icon: 'fa-building', color: 'from-emerald-500 to-teal-600', label: 'Dept Feedback', badge: '' },
        general: { icon: 'fa-clipboard-list', color: 'from-blue-500 to-indigo-600', label: 'General', badge: '' }
    };

    const catIcons = {
        'Course Outcome Attainment': 'fa-bullseye',
        'Program Exit Survey': 'fa-door-open',
        'Curriculum Feedback': 'fa-book-open',
        'Infrastructure and Resources': 'fa-server',
        'Teacher Evaluation': 'fa-user-tie',
        'Student Satisfaction Survey': 'fa-smile-beam',
    };

    function selectDept(id, name) {
        selectedDept = id;
        document.querySelectorAll('.dept-btn').forEach(b => {
            b.classList.remove('border-indigo-500', 'bg-indigo-50', 'shadow-lg');
            b.classList.add('border-gray-200', 'bg-white');
        });
        const el = document.querySelector(`[data-dept="${id}"]`);
        if (el) {
            el.classList.remove('border-gray-200', 'bg-white');
            el.classList.add('border-indigo-500', 'bg-indigo-50', 'shadow-lg');
        }
        document.getElementById('dept-name').textContent = name;
        document.getElementById('main-content').classList.remove('hidden');
        loadAllTabs();
    }

    function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function loadAllTabs() {
        // Load CO forms
        fetch(`?ajax=co_forms&dept_id=${selectedDept}`)
            .then(r => r.json())
            .then(forms => {
                document.getElementById('co-count').textContent = forms.length;
                renderSemesterFilter(forms);
                renderCOForms(forms);
            });

        // Load Exit forms
        fetch(`?ajax=exit_forms&dept_id=${selectedDept}`)
            .then(r => r.json())
            .then(forms => {
                document.getElementById('exit-count').textContent = forms.length;
                renderExitForms(forms);
            });

        // Load Dept forms
        fetch(`?ajax=dept_forms&dept_id=${selectedDept}`)
            .then(r => r.json())
            .then(forms => {
                document.getElementById('dept-count').textContent = forms.length;
                renderDeptForms(forms);
            });

        // Load All forms
        fetch(`?ajax=all_forms&dept_id=${selectedDept}`)
            .then(r => r.json())
            .then(forms => {
                document.getElementById('all-count').textContent = forms.length;
                renderAllForms(forms);
            });
    }

    function renderSemesterFilter(forms) {
        const semesters = [...new Set(forms.map(f => f.semester).filter(Boolean))].sort((a,b) => a-b);
        const container = document.getElementById('sem-filter');
        container.innerHTML = `<button class="sem-chip active" onclick="filterBySemester(null, this)"><i class="fas fa-layer-group"></i> All</button>`;
        semesters.forEach(s => {
            container.innerHTML += `<button class="sem-chip" onclick="filterBySemester(${s}, this)">Sem ${s}</button>`;
        });
    }

    function filterBySemester(sem, el) {
        document.querySelectorAll('.sem-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        let url = `?ajax=co_forms&dept_id=${selectedDept}`;
        if (sem) url += `&semester=${sem}`;
        fetch(url).then(r => r.json()).then(forms => renderCOForms(forms));
    }

    function makeFormCard(f, colorClass, icon) {
        const courseBadge = f.course_name ? `<span class="outcome-badge co"><i class="fas fa-book mr-1"></i>${f.course_code}</span>` : '';
        const semBadge = f.semester ? `<span class="text-xs text-gray-400"><i class="fas fa-layer-group mr-1"></i>Sem ${f.semester}</span>` : '';
        const qCount = `<span class="text-xs text-gray-400"><i class="fas fa-list-ol mr-1"></i>${f.question_count} Qs</span>`;
        const typeBadge = typeConfig[f.form_type] ? `<span class="outcome-badge ${typeConfig[f.form_type].badge}">${typeConfig[f.form_type].label}</span>` : '';

        return `
        <div class="form-card animate-scale-in">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${colorClass}"></div>
            <div class="form-card-body">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br ${colorClass} rounded-xl flex items-center justify-center text-white shadow-lg flex-shrink-0">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="flex gap-1">${courseBadge} ${typeBadge}</div>
                </div>
                <h3 class="text-sm font-bold text-gray-800 mb-2 leading-snug">${f.title}</h3>
                ${f.description ? `<p class="text-xs text-gray-400 mb-3 line-clamp-2">${f.description}</p>` : ''}
                <div class="flex items-center justify-between">
                    <div class="flex gap-3">${semBadge} ${qCount}</div>
                    <a href="submit.php?id=${f.id}" class="btn-primary text-xs py-2 px-4">
                        <i class="fas fa-pen"></i> Fill Form
                    </a>
                </div>
            </div>
        </div>`;
    }

    function renderCOForms(forms) {
        const grid = document.getElementById('co-forms-grid');
        if (forms.length === 0) { grid.innerHTML = '<div class="col-span-2 text-center py-8 text-gray-400"><i class="fas fa-bullseye text-4xl mb-3 block"></i>No CO Attainment forms available</div>'; return; }
        grid.innerHTML = forms.map(f => makeFormCard(f, 'from-violet-500 to-purple-600', 'fa-bullseye')).join('');
    }

    function renderExitForms(forms) {
        const grid = document.getElementById('exit-forms-grid');
        if (forms.length === 0) { grid.innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-door-open text-4xl mb-3 block"></i>No Exit Survey forms available</div>'; return; }
        grid.innerHTML = forms.map(f => makeFormCard(f, 'from-amber-500 to-orange-600', 'fa-door-open')).join('');
    }

    function renderDeptForms(forms) {
        const grid = document.getElementById('dept-forms-grid');
        if (forms.length === 0) { grid.innerHTML = '<div class="col-span-2 text-center py-8 text-gray-400"><i class="fas fa-building text-4xl mb-3 block"></i>No Department Feedback forms available</div>'; return; }
        grid.innerHTML = forms.map(f => {
            const icon = catIcons[f.category] || 'fa-clipboard-list';
            return makeFormCard(f, 'from-emerald-500 to-teal-600', icon);
        }).join('');
    }

    function renderAllForms(forms) {
        const grid = document.getElementById('all-forms-grid');
        if (forms.length === 0) { grid.innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-list text-4xl mb-3 block"></i>No forms available</div>'; return; }

        // Group by form_type
        const grouped = {};
        forms.forEach(f => {
            const type = f.form_type || 'general';
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(f);
        });

        let html = '';
        for (const [type, typeForms] of Object.entries(grouped)) {
            const cfg = typeConfig[type] || typeConfig.general;
            html += `
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-gradient-to-br ${cfg.color} rounded-lg flex items-center justify-center text-white text-sm shadow">
                        <i class="fas ${cfg.icon}"></i>
                    </div>
                    <h4 class="font-bold text-sm text-gray-600">${cfg.label} <span class="text-gray-400 font-normal">(${typeForms.length})</span></h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    ${typeForms.map(f => makeFormCard(f, cfg.color, cfg.icon)).join('')}
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
