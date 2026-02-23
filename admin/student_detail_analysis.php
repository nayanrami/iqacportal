<?php
/**
 * Admin - Individual Student Detailed Analysis
 */
$pageTitle = 'Student Detail Analysis';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$studentId = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$studentId) {
    redirect(APP_URL . '/admin/student_analysis.php');
}

// Fetch student profile
$stmt = $pdo->prepare("SELECT s.*, d.name as dept_name FROM students s JOIN departments d ON d.id = s.department_id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student record not found.');
    redirect(APP_URL . '/admin/student_analysis.php');
}

// Fetch all feedback responses for this student with numeric attainment scores
$sql = "
    SELECT r.id as response_id, r.submitted_at, ff.title, ff.form_type, ff.category,
           c.code as course_code, c.name as course_name,
           COALESCE(ff.semester, c.semester) as semester,
           ROUND(AVG(ra.score), 2) as avg_score,
           COUNT(ra.id) as answers_count
    FROM responses r
    JOIN feedback_forms ff ON ff.id = r.feedback_form_id
    LEFT JOIN courses c ON c.id = ff.course_id
    JOIN response_answers ra ON ra.response_id = r.id
    WHERE r.student_id = ?
    GROUP BY r.id
    ORDER BY r.submitted_at DESC
";
// --- Advanced Pagination Logic ---
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$responses = [];

try {
    // Count total responses
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE student_id = ?");
    $countStmt->execute([$studentId]);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Fetch paginated responses
    $stmt = $pdo->prepare($sql . " LIMIT $limit OFFSET $offset");
    $stmt->execute([$studentId]);
    $responses = $stmt->fetchAll();
} catch (Exception $e) {
    $responses = [];
}

// Group by semester for count
$semStats = [];
for ($i = 1; $i <= 8; $i++) {
    $semStats[$i] = 0;
}

// Group by form type for summary
$formTypeStats = [
    'co_attainment' => ['count' => 0, 'avg' => 0, 'sum' => 0],
    'exit_survey' => ['count' => 0, 'avg' => 0, 'sum' => 0],
    'dept_feedback' => ['count' => 0, 'avg' => 0, 'sum' => 0],
    'general' => ['count' => 0, 'avg' => 0, 'sum' => 0],
];

foreach ($responses as $r) {
    // Semester stats
    if ($r['semester'] && isset($semStats[$r['semester']])) {
        $semStats[$r['semester']]++;
    }

    // Form type stats
    $t = $r['form_type'];
    if (isset($formTypeStats[$t])) {
        $formTypeStats[$t]['count']++;
        $formTypeStats[$t]['sum'] += $r['avg_score'];
    } else {
        $formTypeStats['general']['count']++;
        $formTypeStats['general']['sum'] += $r['avg_score'];
    }
}

foreach ($formTypeStats as $k => $v) {
    if ($v['count'] > 0) {
        $formTypeStats[$k]['avg'] = round($v['sum'] / $v['count'], 2);
    }
}

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="student_analysis.php" class="w-10 h-10 bg-white border border-gray-100 rounded-xl flex items-center justify-center text-gray-400 hover:text-indigo-600 transition shadow-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-black gradient-text"><?= sanitize($student['name']) ?></h1>
                <p class="text-gray-500 text-sm">Detailed Performance & Participation Analysis</p>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl font-bold shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Student Identity Card -->
        <div class="lg:col-span-1">
            <div class="glass-card p-6 bg-white space-y-6">
                <div class="flex items-center gap-4 border-b border-gray-50 pb-6">
                    <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-500 text-2xl font-black">
                        <?= substr($student['name'], 0, 1) ?>
                    </div>
                    <div>
                        <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Profile Info</div>
                        <div class="text-lg font-black text-gray-800"><?= $student['enrollment_no'] ?></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Academic Info</div>
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-500 text-sm italic">Department</span>
                                <span class="font-bold text-gray-800 text-sm"><?= sanitize($student['dept_name']) ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-500 text-sm italic">Semester</span>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg font-black text-xs">SEM <?= $student['semester'] ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-500 text-sm italic">Division</span>
                                <span class="font-bold text-gray-800 text-sm"><?= $student['division'] ?: 'N/A' ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Contact Summary</div>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="fas fa-envelope text-indigo-300 w-4"></i> <?= $student['email'] ?: 'No Email' ?>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="fas fa-phone text-indigo-300 w-4"></i> <?= $student['mobile'] ?: 'No Mobile' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="lg:col-span-2">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4"><i class="fas fa-history mr-2"></i>Semester-Wise Submission Count</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <?php for ($i=1; $i<=8; $i++): 
                    $count = $semStats[$i];
                    $isActive = $count > 0;
                ?>
                <div class="glass-card p-4 border-l-4 <?= $isActive ? 'border-indigo-500 bg-white' : 'border-gray-100 bg-gray-50/30 opacity-60' ?>">
                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Semester <?= $i ?></div>
                    <div class="flex items-end gap-2">
                        <div class="text-xl font-black <?= $isActive ? 'text-gray-800' : 'text-gray-300' ?>"><?= $count ?></div>
                        <div class="text-[8px] text-gray-400 font-bold mb-1 uppercase tracking-tighter">Submissions</div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="glass-card p-6 bg-white">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-black text-xs text-gray-400 uppercase tracking-widest">Overall Participation Insight</h3>
                    <span class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-500 rounded-lg font-black">Total: <?= count($responses) ?></span>
                </div>
                <div class="space-y-4">
                    <?php 
                    $totalForms = (new class($pdo, $student) {
                        public function count($pdo, $s) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) FROM feedback_forms ff 
                                LEFT JOIN courses c ON c.id = ff.course_id 
                                WHERE ff.department_id = ? AND ff.is_active = 1
                                AND (COALESCE(ff.semester, c.semester) IS NULL OR COALESCE(ff.semester, c.semester) <= ?)
                            ");
                            $stmt->execute([$s['department_id'], $s['semester']]);
                            return $stmt->fetchColumn();
                        }
                    })->count($pdo, $student);
                    $pct = $totalForms > 0 ? round((count($responses) / $totalForms) * 100) : 0;
                    ?>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-bold text-gray-500">Compliance Rate</span>
                        <span class="text-xs font-black text-indigo-600"><?= $pct ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full rounded-full transition-all duration-1000" style="width: <?= $pct ?>%"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 italic">This student has completed <?= count($responses) ?> out of <?= $totalForms ?> mandatory feedback forms.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Subject Results -->
    <div class="glass-card overflow-hidden bg-white shadow-sm ring-1 ring-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-black text-xs text-gray-500 uppercase tracking-widest leading-none">Submission History & Subject-Wise Breakup</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Sr. No.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Submission Date</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Feedback Form / Module</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Type</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Avg Score</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-right">Attainment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php elseif (empty($responses)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-file-invoice text-2xl text-gray-100"></i>
                                </div>
                                <h3 class="text-lg font-black text-gray-300 italic">No submissions found</h3>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $srNo = $offset + 1;
                        foreach ($responses as $r): 
                            $attainment = round($r['avg_score']);
                            $level = getAttainmentLevel(($r['avg_score'] / 5) * 100);
                        ?>
                        <tr class="hover:bg-indigo-50/30 transition border-b border-gray-50">
                            <td class="px-6 py-4 text-center text-xs font-black text-gray-400">
                                <?= $srNo++ ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-gray-700"><?= date('M d, Y', strtotime($r['submitted_at'])) ?></div>
                                <div class="text-[9px] text-gray-400 font-mono"><?= date('H:i A', strtotime($r['submitted_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($r['course_code']): ?>
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[9px] font-black"><?= $r['course_code'] ?></span>
                                        <span class="text-sm font-black text-gray-800"><?= sanitize($r['course_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="text-xs text-indigo-600 font-medium"><?= sanitize($r['title']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 border border-indigo-100 text-indigo-600 rounded text-[9px] font-black uppercase tracking-tighter">
                                    <?= getFormTypeLabel($r['form_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-base font-black text-gray-700"><?= $r['avg_score'] ?></div>
                                <div class="text-[9px] text-gray-400 font-bold tracking-widest mt-0.5">/ 5</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="px-3 py-1 <?= $level['badge'] ?> rounded-full text-[9px] font-black uppercase tracking-wider">
                                        <?= $level['label'] ?>
                                    </span>
                                    <span class="text-[8px] text-gray-300 font-bold uppercase tracking-tighter"><?= $level['target'] ?></span>
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
    <?php if ($totalRecords > 0): ?>
    <div class="px-6 py-4 bg-gray-50/30 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4 print:hidden">
        <div class="flex items-center gap-4">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> Results
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="id" value="<?= $studentId ?>">
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
</div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
