<?php
/**
 * Admin - Responses Viewer
 * v2.1 - Added Student Details & Year filtering
 */
$pageTitle = 'Student Responses';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Delete response (with security check)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT r.id FROM responses r JOIN feedback_forms ff ON ff.id = r.feedback_form_id WHERE r.id = ?" . ($deptId ? " AND ff.department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM responses WHERE id = ?")->execute([$id]);
        setFlash('success', 'Response deleted.');
    } else {
        setFlash('danger', 'Unauthorized access.');
    }
    redirect(APP_URL . '/admin/responses.php');
}

require_once __DIR__ . '/header.php';

$filterType = $_GET['type'] ?? '';
$filterForm = intval($_GET['form'] ?? 0);
$years = getYears($pdo);
$filterYear = $_GET['year'] ?? '';
$filterSem = isset($_GET['sem']) ? intval($_GET['sem']) : '';
$filterCourse = intval($_GET['course'] ?? 0);

$sql = "SELECT r.*, ff.title as form_title, ff.form_type, ff.semester as form_semester,
               c.name as course_name, c.code as course_code, c.year as course_year, c.semester as course_semester,
               s.name as student_name_db, s.enrollment_no as student_enroll,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN courses c ON c.id = ff.course_id
        LEFT JOIN students s ON s.id = r.student_id
        LEFT JOIN response_answers ra ON ra.response_id = r.id";
$where = [];
if ($deptId) $where[] = "ff.department_id = $deptId";
$params = [];

if ($filterType) {
    $where[] = "ff.form_type = ?";
    $params[] = $filterType;
}
if ($filterForm) {
    $where[] = "ff.id = ?";
    $params[] = $filterForm;
}
if ($filterYear) {
    $where[] = "c.year = ?";
    $params[] = $filterYear;
}
if ($filterSem) {
    $where[] = "COALESCE(ff.semester, c.semester) = ?";
    $params[] = $filterSem;
}
if ($filterCourse) {
    $where[] = "c.id = ?";
    $params[] = $filterCourse;
}

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;

if ($where) $sql .= " WHERE " . implode(' AND ', $where);

try {
    // Count total
    $countSql = "SELECT COUNT(DISTINCT r.id) FROM responses r JOIN feedback_forms ff ON ff.id = r.feedback_form_id LEFT JOIN courses c ON c.id = ff.course_id";
    if ($where) $countSql .= " WHERE " . implode(' AND ', $where);
    $totalStmt = $pdo->prepare($countSql);
    $totalStmt->execute($params);
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Fetch paginated
    $sql .= " GROUP BY r.id ORDER BY r.submitted_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $responses = $stmt->fetchAll();
} catch (Exception $e) {
    $responses = [];
}

$forms = $pdo->query("SELECT id, title, form_type FROM feedback_forms ORDER BY form_type, title")->fetchAll();
$courses = $pdo->query("SELECT id, code, name, semester FROM courses ORDER BY semester, code")->fetchAll();
?>

<!-- Filters -->
<div class="flex flex-col gap-4 mb-8 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm animate-slide-down">
    <!-- Type filter pills -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= APP_URL ?>/admin/responses.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= !$filterType ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">All</a>
        <a href="?type=co_attainment" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'co_attainment' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">CO Attainment</a>
        <a href="?type=exit_survey" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'exit_survey' ? 'bg-amber-600 text-white shadow-md shadow-amber-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">Exit Survey</a>
        <a href="?type=dept_feedback" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'dept_feedback' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">Dept Feedback</a>
    </div>

    <!-- Dropdown filters -->
    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <?php if ($filterType): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
        
        <select name="sem" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Semester (All)</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?= $i ?>" <?= $filterSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
            <?php endfor; ?>
        </select>

        <select name="course" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-indigo-500 outline-none max-w-[200px]">
            <option value="">Subject (All)</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>>
                    [Sem <?= $c['semester'] ?>] <?= sanitize($c['code']) ?> - <?= sanitize(substr($c['name'], 0, 30)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="year" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Year (All)</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        
        <select name="form" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-indigo-500 outline-none max-w-[200px]">
            <option value="">All Forms</option>
            <?php foreach ($forms as $f): ?>
                <option value="<?= $f['id'] ?>" <?= $filterForm == $f['id'] ? 'selected' : '' ?>>
                    [<?= getFormTypeLabel($f['form_type']) ?>] <?= sanitize($f['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($filterSem || $filterCourse || $filterYear || $filterForm): ?>
            <a href="<?= APP_URL ?>/admin/responses.php<?= $filterType ? '?type='.$filterType : '' ?>" class="text-xs text-red-500 hover:underline font-semibold"><i class="fas fa-times mr-1"></i>Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-sm transition-all duration-300 hover:shadow-lg">
    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-black text-gray-800 tracking-tight">Student Evaluations</h3>
            <p class="text-xs text-gray-400">Viewing detailed feedback submissions</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <?php 
        $isFiltered = $filterType || $filterForm || $filterYear || $filterSem || $filterCourse;
        if (!$isFiltered): ?>
            <div class="px-6 py-20 text-center">
                <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-filter text-2xl text-indigo-400"></i>
                </div>
                <h3 class="text-lg font-black text-gray-800">Please apply a filter to view responses</h3>
                <p class="text-gray-400 text-xs mt-1">Select a type, semester, or course to display records.</p>
            </div>
        <?php else: ?>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                    <th class="px-6 py-4 text-center">Sr. No.</th>
                    <th class="px-6 py-4">Submission</th>
                    <th class="px-6 py-4">Student Identity</th>
                    <th class="px-6 py-4 text-center">Score Index</th>
                    <th class="px-6 py-4">Timeline</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($responses)): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 font-bold"><i class="fas fa-inbox text-4xl block mb-4 opacity-20"></i>No student data found for these filters.</td></tr>
                <?php else: 
                    $srNo = $offset + 1;
                    foreach ($responses as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-6 py-5 text-center text-xs font-black text-gray-400">
                            <?= $srNo++ ?>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-bold text-gray-700 leading-tight"><?= sanitize(substr($r['form_title'], 0, 60)) ?><?= strlen($r['form_title']) > 60 ? '...' : '' ?></div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase border <?= $r['form_type'] === 'co_attainment' ? 'text-purple-600 bg-purple-50 border-purple-100' : ($r['form_type'] === 'exit_survey' ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-emerald-600 bg-emerald-50 border-emerald-100') ?>">
                                    <?= getFormTypeLabel($r['form_type']) ?>
                                </span>
                                <?php $sem = $r['form_semester'] ?: $r['course_semester']; if ($sem): ?>
                                    <span class="text-[10px] text-indigo-500 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">Sem <?= $sem ?></span>
                                <?php endif; ?>
                                <?php if($r['course_code']): ?>
                                    <span class="text-[10px] text-gray-400 font-bold"><?= $r['course_code'] ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center text-xs font-black">
                                    <?= strtoupper(substr($r['student_name_db'] ?: ($r['student_name'] ?: 'A'), 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-700"><?= sanitize($r['student_name_db'] ?: ($r['student_name'] ?: 'Anonymous Student')) ?></div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter"><?= sanitize($r['student_enroll'] ?: ($r['student_roll'] ?: 'N/A')) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="inline-flex flex-col">
                                <span class="text-lg font-black text-indigo-600 leading-none"><?= $r['avg_score'] ?></span>
                                <span class="text-[9px] font-bold text-gray-300 uppercase mt-1">Avg Score</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-xs font-bold text-gray-500"><?= date('d M Y', strtotime($r['submitted_at'])) ?></div>
                            <div class="text-[10px] text-gray-400"><?= date('h:i A', strtotime($r['submitted_at'])) ?></div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="<?= APP_URL ?>/admin/response_detail.php?id=<?= $r['id'] ?>" class="w-8 h-8 bg-white border border-gray-200 rounded-xl flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm" title="View Detail">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                                <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Archive this response permanently?')" class="w-8 h-8 bg-white border border-gray-200 rounded-xl flex items-center justify-center text-gray-400 hover:text-red-600 hover:border-red-200 transition-all shadow-sm" title="Delete">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalRecords > 0): ?>
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

<?php require_once __DIR__ . '/footer.php'; ?>
