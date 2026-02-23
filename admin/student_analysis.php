<?php
/**
 * Admin - Student-Wise Analysis
 * Provides a granular view of feedback participation and attainment scores per student.
 */
$pageTitle = 'Student analysis';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// Filters
$filterSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$search = trim($_GET['search'] ?? '');

// Fetch Departments for filter (if Superadmin)
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
if (!$deptId && !empty($departments)) {
    $deptId = $departments[0]['id'];
}

// Data aggregation query
$sql = "
    SELECT s.id, s.name, s.enrollment_no, s.semester, s.division,
           COUNT(DISTINCT r.id) as forms_filled,
           COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_attainment
    FROM students s
    LEFT JOIN responses r ON r.student_id = s.id
    LEFT JOIN response_answers ra ON ra.response_id = r.id
    WHERE s.department_id = ?
";

$params = [$deptId];

if ($filterSem) {
    $sql .= " AND s.semester = ?";
    $params[] = $filterSem;
}
if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.enrollment_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY s.id ORDER BY s.semester, s.enrollment_no";

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$studentStats = [];

if ($deptId) {
    try {
        // Count total
        $countSql = "SELECT COUNT(*) FROM students s WHERE s.department_id = ?";
        $countParams = [$deptId];
        if ($filterSem) { $countSql .= " AND s.semester = ?"; $countParams[] = $filterSem; }
        if ($search) { $countSql .= " AND (s.name LIKE ? OR s.enrollment_no LIKE ?)"; $countParams[] = "%$search%"; $countParams[] = "%$search%"; }
        
        $totalStmt = $pdo->prepare($countSql);
        $totalStmt->execute($countParams);
        $totalRecords = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated
        $stmt = $pdo->prepare($sql . " LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $studentStats = $stmt->fetchAll();
    } catch (Exception $e) {
        $studentStats = [];
    }
}

// Get department name for title
$deptName = "Selected Department";
foreach ($departments as $d) {
    if ($d['id'] == $deptId) {
        $deptName = $d['name'];
        break;
    }
}

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Student-Wise Analysis</h1>
            <p class="text-gray-500 text-sm">Detailed participation and attainment report for <strong><?= sanitize($deptName) ?></strong>.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl font-bold shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card p-6 mb-8 print:hidden">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php if (!$isDeptAdmin): ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Department</label>
                <select name="dept_id" onchange="this.form.submit()" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Semester</label>
                <select name="sem" onchange="this.form.submit()" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <option value="">All Semesters</option>
                    <?php for($i=1;$i<=8;$i++): ?>
                        <option value="<?= $i ?>" <?= $filterSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">Search Student</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Name or Enrollment" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Analysis Table -->
    <?php 
    $isFiltered = isset($_GET['dept_id']) || isset($_GET['sem']) || isset($_GET['search']);
    if (!$isFiltered): ?>
        <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-sm">
            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users-cog text-3xl text-indigo-400"></i>
            </div>
            <h3 class="text-xl font-black text-gray-800">Please apply a filter to view Student Analysis</h3>
            <p class="text-gray-400 text-sm mt-1">Select a department, semester, or search for a student to view participation records.</p>
        </div>
    <?php else: ?>
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Sr. No.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Enrollment No</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Student Name</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Semester</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Forms Filled</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Avg Attainment</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($studentStats)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user-slash text-2xl text-gray-200"></i>
                                </div>
                                <h3 class="text-lg font-black text-gray-400">No student data found</h3>
                                <p class="text-gray-400 text-xs mt-1">Try adjusting your filters or search criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $srNo = $offset + 1;
                        foreach ($studentStats as $s): 
                            $attainment = $s['avg_attainment'];
                            $attainmentClass = 'bg-gray-100 text-gray-500';
                            if ($attainment >= 4.0) $attainmentClass = 'bg-emerald-100 text-emerald-600';
                            elseif ($attainment >= 3.0) $attainmentClass = 'bg-indigo-100 text-indigo-600';
                            elseif ($attainment > 0) $attainmentClass = 'bg-amber-100 text-amber-600';
                            
                            $status = $s['forms_filled'] > 0 ? 'Active' : 'Pending';
                            $statusClass = $s['forms_filled'] > 0 ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500';
                        ?>
                        <tr class="hover:bg-gray-50/50 transition border-b border-gray-50 last:border-0 hover:border-indigo-100">
                            <td class="px-6 py-4 text-center text-xs font-black text-gray-400">
                                <?= $srNo++ ?>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-black text-indigo-600 group-hover:text-indigo-700">
                                <a href="student_detail_analysis.php?id=<?= $s['id'] ?>" class="hover:underline"><?= $s['enrollment_no'] ?></a>
                            </td>
                            <td class="px-6 py-4">
                                <a href="student_detail_analysis.php?id=<?= $s['id'] ?>" class="block group">
                                    <div class="text-sm font-black text-gray-800 group-hover:text-indigo-600 transition"><?= sanitize($s['name']) ?></div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter mt-0.5"><?= $s['division'] ?: 'No Division' ?></div>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-black tracking-tight">SEM <?= $s['semester'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <span class="text-base font-black text-gray-700"><?= $s['forms_filled'] ?></span>
                                    <i class="fas fa-file-alt text-gray-200 text-[10px]"></i>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <span class="px-3 py-1 <?= $attainmentClass ?> rounded-lg text-xs font-black"><?= $attainment ?></span>
                                    <span class="text-[8px] font-bold text-gray-300 uppercase tracking-widest mt-1">Avg Score</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 <?= $statusClass ?> rounded-full text-[9px] font-black uppercase tracking-wider">
                                    <?= $status ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Pagination -->
    <?php if ($isFiltered && $totalRecords > 0): ?>
    <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4 print:hidden">
        <div class="flex items-center gap-4">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> Results
            </div>
            <form method="GET" class="flex items-center gap-2">
                <?php foreach($_GET as $k => $v): if($k != 'limit' && $k != 'page'): ?>
                <input type="hidden" name="<?= sanitize($k) ?>" value="<?= sanitize($v) ?>">
                <?php endif; endforeach; ?>
                <select name="limit" onchange="this.form.submit()" class="px-2 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold text-gray-500 outline-none focus:border-indigo-500 transition shadow-sm">
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
    <?php endif; ?>
</main>

<style>
@media print {
    .admin-sidebar, .admin-sidebar *, .glass-card.p-6, .flex.gap-2, button { display: none !important; }
    .admin-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    body { background: white !important; }
    .glass-card { border: 1px solid #eee !important; box-shadow: none !important; }
    .gradient-text { background: none !important; -webkit-text-fill-color: initial !important; color: #000 !important; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
