<?php
/**
 * Admin Dashboard - Light Theme with CO/PO Stats
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/functions.php';
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
// Research Summary
$resRecords = $pdo->query("SELECT r.id, c.name as cat_name FROM research_records r JOIN research_categories c ON c.id = r.category_id WHERE 1=1" . ($deptId ? " AND r.department_id = $deptId" : ""))->fetchAll();
$pubCount = count(array_filter($resRecords, fn($r) => strpos($r['cat_name'], 'Publication') !== false));
$grantCount = count(array_filter($resRecords, fn($r) => strpos($r['cat_name'], 'Grant') !== false));

// Aggregate completion for summary
$overallSatisfaction = $naac['satisfaction_pct'];
$totalEligible = 0; $totalResponded = 0;
foreach($semesterStats as $s) {
    $totalEligible += $s['total_students_in_sem'];
    $totalResponded += $s['unique_students'];
}
$completionPct = $totalEligible > 0 ? round(($totalResponded / $totalEligible) * 100) : 0;
?>

<!-- At-a-Glance High Level Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="glass-card p-6 border-l-4 border-indigo-500 bg-white">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Feedback Volume</div>
        <div class="flex items-end justify-between">
            <div class="text-3xl font-black text-gray-800"><?= $stats['total_responses'] ?></div>
            <div class="text-[10px] text-indigo-500 font-bold bg-indigo-50 px-2 py-0.5 rounded tracking-tighter">
                <i class="fas fa-plus mr-1"></i> Responses
            </div>
        </div>
    </div>
    <div class="glass-card p-6 border-l-4 border-purple-500 bg-white">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Academic Performance</div>
        <div class="flex items-end justify-between">
            <div class="text-3xl font-black text-gray-800"><?= $stats['avg_score'] ?></div>
            <div class="text-[10px] text-purple-500 font-bold bg-purple-50 px-2 py-0.5 rounded tracking-tighter uppercase">
                Avg Rating
            </div>
        </div>
    </div>
    <div class="glass-card p-6 border-l-4 border-emerald-500 bg-white">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Student Participation</div>
        <div class="flex items-end justify-between">
            <div class="text-3xl font-black text-gray-800"><?= $completionPct ?>%</div>
            <div class="text-[10px] text-emerald-500 font-bold bg-emerald-50 px-2 py-0.5 rounded tracking-tighter">
                Overall Sync
            </div>
        </div>
    </div>
    <div class="glass-card p-6 border-l-4 border-rose-500 bg-white">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">IQAC Satisfaction</div>
        <div class="flex items-end justify-between">
            <div class="text-3xl font-black text-gray-800"><?= $overallSatisfaction ?>%</div>
            <div class="text-[10px] text-rose-500 font-bold bg-rose-50 px-2 py-0.5 rounded tracking-tighter uppercase">
                NAAC Metric
            </div>
        </div>
    </div>
</div>

<!-- Navigation & Module Hub -->
<div class="mb-8">
    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4"><i class="fas fa-th-large mr-2"></i> Management Modules</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- NAAC Portal Module - Visible to All -->
        <a href="<?= APP_URL ?>/admin/naac_hub.php" class="hub-card group p-6 glass-card bg-indigo-600 border border-indigo-400 hover:border-indigo-300 transition-all duration-300 shadow-xl shadow-indigo-100/30">
            <div class="w-12 h-12 bg-white/10 text-white rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-white group-hover:text-indigo-600 transition shadow-inner">
                <i class="fas fa-award"></i>
            </div>
            <h4 class="font-black text-white mb-1">NAAC Portal</h4>
            <p class="text-xs text-indigo-100 mb-4 leading-relaxed">Centralized Criterion Hub (1-7) & IQAC Documentation.</p>
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-white/80 uppercase">Open Hub →</span>
            </div>
        </a>

        <?php if (in_array($role, ['superadmin', 'university', 'deptadmin', 'criterion_1'])): ?>
        <!-- Compliance Module -->
        <a href="<?= APP_URL ?>/admin/compliance.php" class="hub-card group p-6 glass-card bg-white border border-gray-100 hover:border-indigo-200 transition-all duration-300">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition shadow-inner">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h4 class="font-black text-gray-800 mb-1">Feedback Compliance</h4>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[10px] font-bold text-indigo-500">View Detail →</span>
                <span class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-500 rounded-lg font-black"><?= $stats['total_responses'] ?> Entries</span>
            </div>
        </a>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin', 'university', 'deptadmin', 'criterion_2', 'criterion_5'])): ?>
        <!-- Student Analysis Module -->
        <a href="<?= APP_URL ?>/admin/student_analysis.php" class="hub-card group p-6 glass-card bg-white border border-gray-100 hover:border-indigo-200 transition-all duration-300">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition shadow-inner">
                <i class="fas fa-user-check"></i>
            </div>
            <h4 class="font-black text-gray-800 mb-1">Student Wise Analysis</h4>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[10px] font-bold text-indigo-500">View Attainment →</span>
                <span class="text-[10px] px-2 py-1 bg-indigo-50 text-indigo-500 rounded-lg font-black"><?= $stats['total_students'] ?? 0 ?> Students</span>
            </div>
        </a>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin', 'university', 'deptadmin', 'criterion_2', 'criterion_5'])): ?>
        <!-- Student Tracker Module -->
        <a href="<?= APP_URL ?>/admin/student_tracker.php" class="hub-card group p-6 glass-card bg-white border border-gray-100 hover:border-emerald-200 transition-all duration-300">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-emerald-600 group-hover:text-white transition shadow-inner">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h4 class="font-black text-gray-800 mb-1">Student Tracking</h4>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[10px] font-bold text-emerald-600">Compliance Log →</span>
                <?php if (count($incompleteStudents) > 0): ?>
                    <span class="text-[10px] px-2 py-1 bg-rose-50 text-rose-500 rounded-lg font-black"><?= count($incompleteStudents) ?> Pending</span>
                <?php endif; ?>
            </div>
        </a>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin', 'university', 'deptadmin', 'criterion_3'])): ?>
        <!-- Research Module -->
        <a href="<?= APP_URL ?>/admin/research.php" class="hub-card group p-6 glass-card bg-white border border-gray-100 hover:border-amber-200 transition-all duration-300">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-amber-600 group-hover:text-white transition shadow-inner">
                <i class="fas fa-microscope"></i>
            </div>
            <h4 class="font-black text-gray-800 mb-1">Research Records</h4>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[10px] font-bold text-amber-600">Research Hub →</span>
                <span class="text-[10px] px-2 py-1 bg-amber-50 text-amber-600 rounded-lg font-black"><?= count($resRecords) ?> Items</span>
            </div>
        </a>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin', 'university', 'deptadmin', 'criterion_2'])): ?>
        <!-- NAAC Analysis Module -->
        <a href="<?= APP_URL ?>/admin/analysis.php" class="hub-card group p-6 glass-card bg-white border border-gray-100 hover:border-violet-200 transition-all duration-300">
            <div class="w-12 h-12 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-violet-600 group-hover:text-white transition shadow-inner">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h4 class="font-black text-gray-800 mb-1">NAAC Analysis</h4>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[10px] font-bold text-violet-600">Analytics →</span>
                <span class="text-[10px] px-2 py-1 bg-violet-50 text-violet-500 rounded-lg font-black">Criterion 2.7</span>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-8">
    <div class="lg:col-span-3 space-y-8">
        <!-- Top performing Courses -->
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex justify-between items-center">
                <h3 class="font-black text-xs text-gray-500 uppercase tracking-widest leading-none">Top Rated courses</h3>
                <a href="<?= APP_URL ?>/admin/co_analysis.php" class="text-[10px] font-bold text-indigo-500 hover:underline">Analysis Hub →</a>
            </div>
            <div class="p-6">
                <div class="chart-box" style="height: 250px;"><canvas id="courseChart"></canvas></div>
            </div>
        </div>

        <!-- Participation Map at-a-glance -->
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-black text-xs text-gray-500 uppercase tracking-widest leading-none">Participation Sync</h3>
            </div>
            <div class="p-6">
                <div class="chart-box" style="height: 200px;"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
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
