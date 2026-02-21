<?php
/**
 * Admin - Responses Viewer
 * v2.1 - Added Student Details & Year filtering
 */
$pageTitle = 'Student Responses';
require_once __DIR__ . '/../functions.php';
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

$sql = "SELECT r.*, ff.title as form_title, ff.form_type, c.name as course_name, c.code as course_code, c.year as course_year,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN courses c ON c.id = ff.course_id
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

if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " GROUP BY r.id ORDER BY r.submitted_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll();

$forms = $pdo->query("SELECT id, title, form_type FROM feedback_forms ORDER BY form_type, title")->fetchAll();
?>

<!-- Filters -->
<div class="flex flex-wrap gap-4 mb-8 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm animate-slide-down">
    <div class="flex flex-wrap items-center gap-2">
        <a href="responses.php" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= !$filterType ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">All</a>
        <a href="?type=co_attainment" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'co_attainment' ? 'bg-purple-600 text-white shadow-md shadow-purple-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">CO Attainment</a>
        <a href="?type=exit_survey" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'exit_survey' ? 'bg-amber-600 text-white shadow-md shadow-amber-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">Exit Survey</a>
        <a href="?type=dept_feedback" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $filterType === 'dept_feedback' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">Dept Feedback</a>
    </div>

    <form method="GET" class="ml-auto flex flex-wrap gap-2">
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
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-sm transition-all duration-300 hover:shadow-lg">
    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-black text-gray-800 tracking-tight">Student Evaluations (<?= count($responses) ?>)</h3>
            <p class="text-xs text-gray-400">Viewing detailed feedback submissions</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                    <th class="px-6 py-4">Submission</th>
                    <th class="px-6 py-4">Student Identity</th>
                    <th class="px-6 py-4 text-center">Score Index</th>
                    <th class="px-6 py-4">Timeline</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($responses)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 font-bold"><i class="fas fa-inbox text-4xl block mb-4 opacity-20"></i>No student data found for these filters.</td></tr>
                <?php else: foreach ($responses as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="text-sm font-bold text-gray-700 leading-tight"><?= sanitize(substr($r['form_title'], 0, 60)) ?><?= strlen($r['form_title']) > 60 ? '...' : '' ?></div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase border <?= $r['form_type'] === 'co_attainment' ? 'text-purple-600 bg-purple-50 border-purple-100' : ($r['form_type'] === 'exit_survey' ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-emerald-600 bg-emerald-50 border-emerald-100') ?>">
                                    <?= getFormTypeLabel($r['form_type']) ?>
                                </span>
                                <?php if($r['course_year']): ?>
                                    <span class="text-[10px] text-gray-300 font-bold"><?= $r['course_year'] ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center text-xs font-black">
                                    <?= strtoupper(substr($r['student_name'] ?: 'A', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-700"><?= sanitize($r['student_name'] ?: 'Anonymous Student') ?></div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter"><?= sanitize($r['student_roll'] ?: 'N/A') ?></div>
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
                                <a href="response_detail.php?id=<?= $r['id'] ?>" class="w-8 h-8 bg-white border border-gray-200 rounded-xl flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm" title="View Detail">
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
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
