<?php
/**
 * Admin Dashboard - Light Theme with CO/PO Stats
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../functions.php';
requireLogin();
require_once __DIR__ . '/header.php';

$deptId = $_SESSION['admin_dept_id'] ?? null;
$stats = getDashboardStats($pdo, $deptId);
$courseScores = getCourseWiseScores($pdo, $deptId);
$trends = getMonthlyTrends($pdo, $deptId);
$recent = getRecentResponses($pdo, 8, $deptId);
$naac = getNAACMetrics($pdo, $deptId);
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
        <a href="responses.php" class="text-xs text-indigo-500 hover:underline font-semibold">View All →</a>
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
