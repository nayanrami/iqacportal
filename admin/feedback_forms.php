<?php
/**
 * Admin - Feedback Forms CRUD
 */
$pageTitle = 'Feedback Forms';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$categories = [
    'Course Outcome Attainment', 'Program Exit Survey', 'Curriculum Feedback',
    'Infrastructure and Resources', 'Teacher Evaluation', 'Student Satisfaction Survey',
    'Research & Innovation', 'Placement & Career Services', 'General'
];

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Toggle active (with security check)
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $check = $pdo->prepare("SELECT id FROM feedback_forms WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE feedback_forms SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        setFlash('success', 'Form status toggled.');
    } else {
        setFlash('danger', 'Unauthorized access.');
    }
    redirect(APP_URL . '/admin/feedback_forms.php');
}

// Delete (with security check)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT id FROM feedback_forms WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM feedback_forms WHERE id = ?")->execute([$id]);
        setFlash('success', 'Feedback form deleted.');
    } else {
        setFlash('danger', 'Unauthorized access.');
    }
    redirect(APP_URL . '/admin/feedback_forms.php');
}

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $formType = trim($_POST['form_type'] ?? 'general');
    $category = trim($_POST['category'] ?? 'General');
    $selectedDeptId = intval($_POST['department_id'] ?? 0);
    $finalDeptId = $isDeptAdmin ? $deptId : ($selectedDeptId ?: null);
    $courseId = intval($_POST['course_id'] ?? 0) ?: null;
    $semester = intval($_POST['semester'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $questions = $_POST['questions'] ?? [];
    $maxScores = $_POST['max_scores'] ?? [];
    $questionTypes = $_POST['question_types'] ?? [];

    if ($title && !empty($questions)) {
        try {
            $pdo->beginTransaction();
            $stmtFF = $pdo->prepare("INSERT INTO feedback_forms (title, form_type, category, department_id, course_id, semester, description) VALUES (?,?,?,?,?,?,?)");
            $stmtFF->execute([$title, $formType, $category, $finalDeptId, $courseId, $semester, $description]);
            $formId = $pdo->lastInsertId();

            $stmtQ = $pdo->prepare("INSERT INTO questions (feedback_form_id, question_text, question_type, max_score, sort_order) VALUES (?,?,?,?,?)");
            foreach ($questions as $i => $qText) {
                $qText = trim($qText);
                if ($qText) {
                    $ms = intval($maxScores[$i] ?? 5);
                    $qt = $questionTypes[$i] ?? 'likert_5';
                    $stmtQ->execute([$formId, $qText, $qt, $ms, $i + 1]);
                }
            }
            $pdo->commit();
            setFlash('success', 'Form created successfully!');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    } else {
        setFlash('danger', 'Please provide a title and at least one question.');
    }
    redirect(APP_URL . '/admin/feedback_forms.php');
}

require_once __DIR__ . '/header.php';

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

$courseFilter = $deptId ? " WHERE c.department_id = $deptId" : "";
$courses = $pdo->query("SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON d.id = c.department_id $courseFilter ORDER BY c.semester, c.name")->fetchAll();

// --- Filter Logic ---
$filterType = $_GET['type'] ?? '';
$filterDept = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? intval($_GET['dept_id']) : null;
$filterSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$filterCourse = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? intval($_GET['course_id']) : null;
$search = trim($_GET['search'] ?? '');

$isFiltered = ($filterType !== '') || ($filterDept !== null) || ($filterSem !== null) || ($filterCourse !== null) || ($search !== '');

if ($isDeptAdmin) {
    $filterDept = $deptId;
    // $isFiltered = true; // Stay consistent: force them to select something if they want everything
}

$whereQuery = ["1=1"];
$params = [];

if ($filterType) {
    $whereQuery[] = "ff.form_type = ?";
    $params[] = $filterType;
}
if ($filterDept) {
    $whereQuery[] = "ff.department_id = ?";
    $params[] = $filterDept;
}
if ($filterSem) {
    $whereQuery[] = "ff.semester = ?";
    $params[] = $filterSem;
}
if ($filterCourse) {
    $whereQuery[] = "ff.course_id = ?";
    $params[] = $filterCourse;
}
if ($search) {
    $whereQuery[] = "(ff.title LIKE ? OR ff.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereString = implode(" AND ", $whereQuery);

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$forms = [];

if ($isFiltered) {
    try {
        // Count total
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback_forms ff WHERE $whereString");
        $totalStmt->execute($params);
        $totalRecords = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated
        $stmt = $pdo->prepare("
            SELECT ff.*, c.name as course_name, c.code as course_code,
                d.name as dept_name, d.code as dept_code,
                (SELECT COUNT(*) FROM questions WHERE feedback_form_id = ff.id) as qcount,
                (SELECT COUNT(*) FROM responses WHERE feedback_form_id = ff.id) as rcount
            FROM feedback_forms ff
            LEFT JOIN courses c ON c.id = ff.course_id
            LEFT JOIN departments d ON d.id = ff.department_id
            WHERE $whereString
            ORDER BY ff.form_type, ff.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $forms = $stmt->fetchAll();
    } catch (Exception $e) {
        $forms = [];
    }
}
?>

<!-- Create Form Section -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm animate-slide-down">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2 bg-gradient-to-r from-indigo-50 to-purple-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Create New Form</h3>
        <button onclick="startBackgroundTask('seed', 'This will reset and re-seed all courses and forms for your department. Continue?')" class="px-3 py-1.5 bg-violet-100 text-violet-600 rounded-lg text-xs font-bold hover:bg-violet-200 transition">
            <i class="fas fa-magic mr-1"></i> Seed All Data
        </button>
    </div>
    <form method="POST" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Form Title *</label>
                <input type="text" name="title" required
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Form Type *</label>
                <select name="form_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
                    <option value="co_attainment">CO Attainment</option>
                    <option value="exit_survey">Exit Survey</option>
                    <option value="dept_feedback">Department Feedback</option>
                    <option value="general">General</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Category</label>
                <select name="category" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Department</label>
                <select name="department_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
                    <option value="">-- None --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?> (<?= sanitize($d['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Course</label>
                <select name="course_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
                    <option value="">-- None --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">Semester</label>
                <select name="semester" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
                    <option value="">-- Any --</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>">Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-500 mb-1">Description</label>
            <input type="text" name="description" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 outline-none focus:border-indigo-500 transition">
        </div>
        <div id="questions-container" class="space-y-3 mb-4">
            <div class="flex gap-2 items-start">
                <input type="text" name="questions[]" placeholder="Question text" required
                       class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 outline-none focus:border-indigo-500 transition">
                <select name="question_types[]" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800">
                    <option value="likert_5">Likert 1-5</option>
                    <option value="likert_3">Likert 1-3</option>
                    <option value="yes_no">Yes/No</option>
                </select>
                <input type="number" name="max_scores[]" value="5" min="1" max="10"
                       class="w-16 px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-center">
            </div>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="addQuestion()"
                    class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                <i class="fas fa-plus mr-1"></i> Add Question
            </button>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-1"></i> Create Form
            </button>
        </div>
    </form>
</div>

<!-- Filter Section -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm">
    <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
        <h3 class="font-bold text-xs text-gray-700"><i class="fas fa-filter text-indigo-500 mr-2"></i>Filter Forms</h3>
        <?php if ($isFiltered): ?>
            <a href="feedback_forms.php" class="text-[10px] text-gray-400 hover:text-indigo-600 font-bold uppercase tracking-widest"><i class="fas fa-times-circle mr-1"></i> Clear</a>
        <?php endif; ?>
    </div>
    <form method="GET" class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <select name="type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                    <option value="">All Types</option>
                    <option value="co_attainment" <?= $filterType === 'co_attainment' ? 'selected' : '' ?>>CO Attainment</option>
                    <option value="exit_survey" <?= $filterType === 'exit_survey' ? 'selected' : '' ?>>Exit Survey</option>
                    <option value="dept_feedback" <?= $filterType === 'dept_feedback' ? 'selected' : '' ?>>Dept Feedback</option>
                    <option value="general" <?= $filterType === 'general' ? 'selected' : '' ?>>General</option>
                </select>
            </div>
            <?php if (!$isDeptAdmin): ?>
            <div>
                <select name="dept_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <select name="sem" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                    <option value="">All Semesters</option>
                    <?php for ($i=1;$i<=8;$i++): ?>
                        <option value="<?= $i ?>" <?= $filterSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search title..." class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-700 transition">
                    Apply Filter
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Existing Forms -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50 flex items-center justify-between">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-list text-emerald-500 mr-2"></i>All Forms (<?= $totalRecords ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Sr. No.</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Course / Dept</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Qs</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Responses</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (!$isFiltered): ?>
                <tr>
                    <td colspan="7" class="px-6 py-20 text-center">
                        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-filter text-2xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-lg font-black text-gray-800">Please apply a filter to view forms</h3>
                        <p class="text-gray-400 text-xs mt-1">Select a type, semester, or department to display records.</p>
                    </td>
                </tr>
                <?php elseif (empty($forms)): ?>
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 font-bold italic">No forms found for these filters.</td></tr>
                <?php else: ?>
                    <?php 
                    $srNo = $offset + 1;
                    foreach ($forms as $f): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-center text-[10px] font-black text-gray-400"><?= $srNo++ ?></td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-700"><?= sanitize($f['title']) ?></div>
                            <div class="text-[10px] text-gray-400"><?= sanitize($f['category']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="outcome-badge <?= $f['form_type'] === 'co_attainment' ? 'co' : ($f['form_type'] === 'exit_survey' ? 'po' : 'pso') ?>">
                                <?= getFormTypeLabel($f['form_type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= $f['course_name'] ? sanitize($f['course_code'] . ' - ' . $f['course_name']) : ($f['dept_name'] ? sanitize($f['dept_code']) : '—') ?></td>
                        <td class="px-4 py-3 text-center text-sm font-semibold text-gray-600"><?= $f['qcount'] ?></td>
                        <td class="px-4 py-3 text-center text-sm font-semibold text-indigo-600"><?= $f['rcount'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold <?= $f['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= $f['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="?toggle=<?= $f['id'] ?>" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:bg-indigo-50 hover:text-indigo-500 transition" title="Toggle">
                                    <i class="fas fa-power-off text-xs"></i>
                                </a>
                                <a href="analysis.php?form=<?= $f['id'] ?>" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-500 transition" title="Analysis">
                                    <i class="fas fa-chart-bar text-xs"></i>
                                </a>
                                <a href="?delete=<?= $f['id'] ?>" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition" title="Delete" onclick="return confirm('Delete this form?')">
                                    <i class="fas fa-trash text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($isFiltered && $totalRecords > 0): ?>
<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> Results
        </div>
        <form method="GET" class="flex items-center gap-2">
            <?php foreach($_GET as $k => $v): if($k != 'limit' && $k != 'page'): ?>
            <input type="hidden" name="<?= sanitize($k) ?>" value="<?= sanitize($v) ?>">
            <?php endif; endforeach; ?>
            <select name="limit" onchange="this.form.submit()" class="px-2 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold text-gray-500 outline-none focus:border-indigo-500 transition">
                <?php foreach([10, 20, 50, 100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?> per page</option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center gap-1">
        <?php 
        $queryParams = $_GET;
        unset($queryParams['page']);
        $baseLink = '?' . http_build_query($queryParams) . '&page=';
        ?>
        
        <?php if ($page > 1): ?>
            <a href="<?= $baseLink . ($page - 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-left text-[10px]"></i></a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $startPage + 4);
        $startPage = max(1, $endPage - 4);
        
        for ($i = $startPage; $i <= $endPage; $i++): 
            if($i < 1) continue;
        ?>
            <a href="<?= $baseLink . $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-black transition shadow-sm <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-200' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= $baseLink . ($page + 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-right text-[10px]"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function addQuestion() {
    const c = document.getElementById('questions-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-start animate-scale-in';
    div.innerHTML = `
        <input type="text" name="questions[]" placeholder="Question text" required
               class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 outline-none focus:border-indigo-500 transition">
        <select name="question_types[]" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800">
            <option value="likert_5">Likert 1-5</option>
            <option value="likert_3">Likert 1-3</option>
            <option value="yes_no">Yes/No</option>
        </select>
        <input type="number" name="max_scores[]" value="5" min="1" max="10"
               class="w-16 px-2 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-center">
        <button type="button" onclick="this.parentElement.remove()" class="w-8 h-8 rounded-lg text-red-400 hover:bg-red-50 flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>`;
    c.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
