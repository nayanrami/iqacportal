<?php
/**
 * Admin Dashboard - Light Theme with CO/PO Stats
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../functions.php';
requireAdmin();
require_once __DIR__ . '/header.php';

$deptId = $_SESSION['admin_dept_id'] ?? null;
$stats = getDashboardStats($pdo, $deptId);
$courseScores = getCourseWiseScores($pdo, $deptId);
$trends = getMonthlyTrends($pdo, $deptId);
$recent = getRecentResponses($pdo, 8, $deptId);
$naac = getNAACMetrics($pdo, $deptId);

// New: Semester & student completion data
$semesterStats = getSemesterWiseStats($pdo, $deptId);
$semFilter = isset($_GET['sem']) ? intval($_GET['sem']) : null;
$subjectStats = getSubjectWiseStats($pdo, $deptId, $semFilter);
$studentStatus = getStudentCompletionStatus($pdo, $deptId, $semFilter);
$incompleteStudents = array_filter($studentStatus, fn($s) => $s['completed_forms'] < $s['total_forms']);
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <?php
    $statCards = [
        ['Total Forms', $stats['total_forms'], 'fa-clipboard-list', 'from-indigo-500 to-blue-600'],
        ['Active Forms', $stats['active_forms'], 'fa-check-circle', 'from-emerald-500 to-green-600'],
        ['Total Responses', $stats['total_responses'], 'fa-comments', 'from-amber-500 to-orange-600'],
        ['Avg Score', $stats['avg_score'], 'fa-star', 'from-rose-500 to-pink-600'],
    ];
    foreach ($statCards as $i => $sc): ?>
        <div class="stat-card hover:-translate-y-1 hover:shadow-xl transition-all duration-300 animate-slide-down" style="animation-delay:<?= $i * 80 ?>ms">
            <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-semibold text-gray-400"><?= $sc[0] ?></div>
                <div class="w-10 h-10 bg-gradient-to-br <?= $sc[3] ?> rounded-xl flex items-center justify-center text-white shadow-lg">
                    <i class="fas <?= $sc[2] ?>"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-gray-800"><?= $sc[1] ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Form Type Breakdown -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <?php
    $typeCards = [
        ['CO Attainment', $stats['co_forms'], 'fa-bullseye', 'violet', 'Course Outcome based'],
        ['Exit Survey', $stats['exit_forms'], 'fa-door-open', 'amber', 'PO-wise Exit Survey'],
        ['Dept Feedback', $stats['dept_forms'], 'fa-building', 'emerald', 'Department level forms'],
    ];
    foreach ($typeCards as $tc): ?>
        <div class="stat-card text-center">
            <div class="w-12 h-12 bg-<?= $tc[3] ?>-100 rounded-2xl flex items-center justify-center text-<?= $tc[3] ?>-600 text-xl mx-auto mb-3">
                <i class="fas <?= $tc[2] ?>"></i>
            </div>
            <div class="text-3xl font-black text-gray-800"><?= $tc[1] ?></div>
            <div class="text-sm font-bold text-gray-600 mt-1"><?= $tc[0] ?></div>
            <div class="text-[10px] text-gray-400"><?= $tc[4] ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- NAAC Metrics -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center shadow-sm">
        <div class="text-4xl font-black gradient-text"><?= $naac['overall_avg'] ?></div>
        <div class="text-sm text-gray-400 mt-1">Overall Average</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center shadow-sm">
        <div class="text-4xl font-black text-emerald-600"><?= $naac['satisfaction_pct'] ?>%</div>
        <div class="text-sm text-gray-400 mt-1">Satisfaction Rate</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center shadow-sm">
        <div class="text-4xl font-black text-blue-600"><?= $naac['forms_evaluated'] ?></div>
        <div class="text-sm text-gray-400 mt-1">Forms Evaluated</div>
    </div>
</div>

<!-- Semester Filter -->
<div class="mb-6">
    <form method="GET" class="flex items-center gap-3 flex-wrap">
        <label class="text-sm font-bold text-gray-500"><i class="fas fa-filter mr-1"></i>Filter by Semester:</label>
        <select name="sem" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 outline-none">
            <option value="">All Semesters</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?= $i ?>" <?= $semFilter == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
            <?php endfor; ?>
        </select>
        <?php if ($semFilter): ?>
            <a href="<?= APP_URL ?>/admin/index.php" class="text-xs text-red-500 hover:underline font-semibold"><i class="fas fa-times mr-1"></i>Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Semester-wise Completion Stats -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm mb-8">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-layer-group text-violet-500 mr-2"></i>Semester-wise Feedback Progress</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Semester</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Forms</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Responses</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Students Responded</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Eligible Students</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Avg Score</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Completion</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($semesterStats)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-inbox text-2xl block mb-2"></i>No data</td></tr>
                <?php else: foreach ($semesterStats as $ss): 
                    $pct = $ss['total_students_in_sem'] > 0 ? round(($ss['unique_students'] / $ss['total_students_in_sem']) * 100) : 0;
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <a href="?sem=<?= $ss['semester'] ?>" class="font-bold text-sm text-indigo-600 hover:underline">
                                <i class="fas fa-layer-group mr-1"></i> Semester <?= $ss['semester'] ?: 'General' ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-700"><?= $ss['total_forms'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $ss['total_responses'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $ss['unique_students'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $ss['total_students_in_sem'] ?></td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-700"><?= $ss['avg_score'] ?? '-' ?></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $pct >= 80 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-400') ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="text-xs font-black <?= $pct >= 80 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-500') ?>"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Subject-wise Completion Stats -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm mb-8">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
        <h3 class="font-bold text-sm text-gray-700">
            <i class="fas fa-book text-blue-500 mr-2"></i>Subject-wise Feedback Stats
            <?php if ($semFilter): ?><span class="text-indigo-500"> — Semester <?= $semFilter ?></span><?php endif; ?>
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Subject / Form</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Sem</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Responses</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Eligible</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Avg Score</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($subjectStats)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-inbox text-2xl block mb-2"></i>No forms found</td></tr>
                <?php else: foreach ($subjectStats as $sub): 
                    $subPct = $sub['eligible_students'] > 0 ? round(($sub['response_count'] / $sub['eligible_students']) * 100) : 0;
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="text-sm font-bold text-gray-700"><?= sanitize($sub['title']) ?></div>
                            <?php if ($sub['course_code']): ?>
                                <div class="text-[10px] text-gray-400 font-medium"><?= sanitize($sub['course_code']) ?> — <?= sanitize($sub['course_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="outcome-badge peo">Sem <?= $sub['semester'] ?: '-' ?></span></td>
                        <td class="px-4 py-3 text-center"><span class="outcome-badge <?= $sub['form_type'] === 'co_attainment' ? 'co' : ($sub['form_type'] === 'exit_survey' ? 'po' : 'pso') ?>"><?= getFormTypeLabel($sub['form_type']) ?></span></td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-700"><?= $sub['response_count'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500"><?= $sub['eligible_students'] ?></td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-700"><?= $sub['avg_score'] ?? '-' ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($subPct >= 100): ?>
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black border border-emerald-100"><i class="fas fa-check-circle mr-1"></i>Complete</span>
                            <?php elseif ($subPct >= 50): ?>
                                <span class="px-2 py-1 bg-amber-50 text-amber-600 rounded-lg text-[10px] font-black border border-amber-100"><i class="fas fa-clock mr-1"></i><?= $subPct ?>%</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-red-50 text-red-500 rounded-lg text-[10px] font-black border border-red-100"><i class="fas fa-exclamation-triangle mr-1"></i><?= $subPct ?>%</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Student Completion Tracker -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm mb-8">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-pink-50 flex items-center justify-between">
        <h3 class="font-bold text-sm text-gray-700">
            <i class="fas fa-user-graduate text-rose-500 mr-2"></i>Student Completion Tracker
            <?php if ($semFilter): ?><span class="text-rose-500"> — Semester <?= $semFilter ?></span><?php endif; ?>
            <?php if (!empty($incompleteStudents)): ?>
                <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-[10px] font-black"><?= count($incompleteStudents) ?> Incomplete</span>
            <?php endif; ?>
        </h3>
        <div class="flex gap-2 text-[10px] font-bold">
            <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-100"><i class="fas fa-check mr-1"></i>Completed</span>
            <span class="px-2 py-1 bg-red-50 text-red-500 rounded-lg border border-red-100"><i class="fas fa-times mr-1"></i>Pending</span>
        </div>
    </div>
    <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
        <table class="w-full">
            <thead class="sticky top-0 z-10">
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Student</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Enrollment</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Semester</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Completed</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Progress</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($studentStatus)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-inbox text-2xl block mb-2"></i>No students found</td></tr>
                <?php else: foreach ($studentStatus as $stu): 
                    $stuPct = $stu['total_forms'] > 0 ? round(($stu['completed_forms'] / $stu['total_forms']) * 100) : 0;
                    $isComplete = $stu['completed_forms'] >= $stu['total_forms'] && $stu['total_forms'] > 0;
                    $rowClass = $isComplete ? '' : 'bg-red-50/40';
                ?>
                    <tr class="<?= $rowClass ?> hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <span class="text-sm font-bold <?= $isComplete ? 'text-gray-700' : 'text-red-700' ?>">
                                <?php if (!$isComplete): ?><i class="fas fa-exclamation-circle text-red-400 mr-1"></i><?php endif; ?>
                                <?= sanitize($stu['name']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-mono text-gray-500"><?= sanitize($stu['enrollment_no']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="outcome-badge peo">Sem <?= $stu['semester'] ?></span></td>
                        <td class="px-4 py-3 text-center text-sm font-bold <?= $isComplete ? 'text-emerald-600' : 'text-red-500' ?>"><?= $stu['completed_forms'] ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500"><?= $stu['total_forms'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $isComplete ? 'bg-emerald-500' : ($stuPct >= 50 ? 'bg-amber-500' : 'bg-red-400') ?>" style="width:<?= $stuPct ?>%"></div>
                                </div>
                                <span class="text-[10px] font-black text-gray-400"><?= $stuPct ?>%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($isComplete): ?>
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black border border-emerald-100"><i class="fas fa-check-circle mr-1"></i>Done</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-red-50 text-red-500 rounded-lg text-[10px] font-black border border-red-100"><i class="fas fa-clock mr-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Charts & Recent Responses -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Course Scores Chart -->
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50">
            <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-chart-bar text-indigo-500 mr-2"></i>Course-wise Scores</h3>
        </div>
        <div class="p-6"><div class="chart-box"><canvas id="courseChart"></canvas></div></div>
    </div>

    <!-- Response Trends -->
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-teal-50">
            <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-chart-line text-cyan-500 mr-2"></i>Response Trends</h3>
        </div>
        <div class="p-6"><div class="chart-box"><canvas id="trendChart"></canvas></div></div>
    </div>
</div>

<!-- Recent Responses Table -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-amber-50 to-orange-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-clock text-amber-500 mr-2"></i>Recent Responses</h3>
        <a href="<?= APP_URL ?>/admin/responses.php" class="text-xs text-indigo-500 hover:underline font-semibold">View All →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Form</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($recent)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-inbox text-2xl block mb-2"></i>No responses yet</td></tr>
                <?php else: foreach ($recent as $r): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-medium text-gray-700"><?= sanitize($r['form_title']) ?></td>
                        <td class="px-4 py-3"><span class="outcome-badge <?= $r['form_type'] === 'co_attainment' ? 'co' : ($r['form_type'] === 'exit_survey' ? 'po' : 'pso') ?>"><?= getFormTypeLabel($r['form_type']) ?></span></td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= sanitize($r['student_name'] ?: 'Anonymous') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-400"><?= date('d M Y, h:i A', strtotime($r['submitted_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(0,0,0,0.06)';
Chart.defaults.font.family = 'Inter, sans-serif';

const courseData = <?= json_encode($courseScores) ?>;
if (courseData.length > 0) {
    new Chart(document.getElementById('courseChart'), {
        type: 'bar',
        data: {
            labels: courseData.map(c => c.course_code || c.course_name.substring(0,15)),
            datasets: [{
                label: 'Avg Score',
                data: courseData.map(c => c.avg_score),
                backgroundColor: courseData.map((_, i) => `hsla(${240 + i * 15}, 70%, 60%, 0.7)`),
                borderRadius: 6, barThickness: 20
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins:{legend:{display:false}} }
    });
}

const trendData = <?= json_encode($trends) ?>;
if (trendData.length > 0) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(t => t.month),
            datasets: [{
                label: 'Responses',
                data: trendData.map(t => t.response_count),
                borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)',
                fill: true, tension: 0.4, pointRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins:{legend:{display:false}} }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
