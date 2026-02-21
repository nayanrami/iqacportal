<?php
/**
 * Admin - Detailed Course-wise CO Analysis
 * Shows per-course CO attainment with charts, response details, and printable report
 */
$pageTitle = 'Course-wise CO Analysis';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptRestriction = $isDeptAdmin ? " WHERE id = " . intval($_SESSION['admin_dept_id']) : "";

$departments = $pdo->query("SELECT * FROM departments $deptRestriction ORDER BY name")->fetchAll();
$selectedDept = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : intval($_GET['dept'] ?? ($departments[0]['id'] ?? 0));
$selectedSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$years = getYears($pdo);
$selectedYear = $_GET['year'] ?? ($years[0] ?? date('Y'));

// Get department info
$deptInfo = null;
foreach ($departments as $d) {
    if ($d['id'] == $selectedDept) { $deptInfo = $d; break; }
}

// Fetch CO data grouped by course
$coData = getCOAttainmentByDept($pdo, $selectedDept, $selectedSem, $selectedYear);

$courses = [];
foreach ($coData as $co) {
    $key = $co['course_id'];
    if (!isset($courses[$key])) {
        $courses[$key] = [
            'id' => $co['course_id'],
            'name' => $co['course_name'],
            'code' => $co['course_code'],
            'sem' => $co['semester'],
            'year' => $co['year'],
            'cos' => []
        ];
    }
    $courses[$key]['cos'][] = $co;
}

// Get per-course response details
$courseResponses = [];
if (!empty($courses)) {
    $courseIds = array_keys($courses);
    $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $pdo->prepare("
        SELECT r.id, r.student_name, r.student_roll, r.submitted_at,
               s.name as student_name_db, s.enrollment_no,
               ff.course_id, ff.title as form_title,
               ROUND(AVG(ra.score), 2) as avg_score,
               COUNT(ra.id) as answer_count
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN students s ON s.id = r.student_id
        LEFT JOIN response_answers ra ON ra.response_id = r.id
        WHERE ff.course_id IN ($placeholders) AND ff.form_type = 'co_attainment'
        GROUP BY r.id
        ORDER BY r.submitted_at DESC
    ");
    $stmt->execute($courseIds);
    foreach ($stmt->fetchAll() as $resp) {
        $courseResponses[$resp['course_id']][] = $resp;
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6 print:space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white/80 backdrop-blur-md border border-white/20 p-4 rounded-2xl shadow-sm flex flex-wrap items-center gap-4 animate-slide-down print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Department</label>
                <select name="dept" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $selectedDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Academic Year</label>
                <select name="year" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Semester</label>
                <select name="sem" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">All Semesters</option>
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?= $i ?>" <?= $selectedSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="ml-auto flex gap-2">
                <a href="<?= APP_URL ?>/admin/analysis.php?dept=<?= $selectedDept ?>" class="px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-xl text-sm font-bold hover:bg-indigo-100 transition shadow-sm">
                    <i class="fas fa-chart-pie mr-2"></i>NAAC Analysis
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                    <i class="fas fa-print mr-2"></i>Print Report
                </button>
            </div>
        </form>
    </div>

    <!-- Print Header (only visible on print) -->
    <div class="hidden print:block text-center mb-6 border-b-2 border-gray-800 pb-4">
        <h1 class="text-xl font-black uppercase"><?= APP_NAME ?></h1>
        <p class="text-sm font-bold text-gray-600 uppercase tracking-wider">Department of <?= sanitize($deptInfo['name'] ?? 'N/A') ?></p>
        <h2 class="text-base font-bold mt-2">Course Outcome (CO) Attainment Analysis Report</h2>
        <p class="text-xs text-gray-500 mt-1">Academic Year: <?= sanitize($selectedYear) ?> | Semester: <?= $selectedSem ?: 'All' ?></p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-slide-down" style="animation-delay:100ms">
        <?php
        $totalCourses = count($courses);
        $totalCOs = 0; $totalResponses = 0; $overallPct = 0; $coWithData = 0;
        foreach ($courses as $c) {
            $totalCOs += count($c['cos']);
            foreach ($c['cos'] as $co) {
                if ($co['total_responses'] > 0) {
                    $totalResponses += $co['total_responses'];
                    $max = $co['max_score'] ?: 3;
                    $overallPct += ($co['avg_score'] / $max) * 100;
                    $coWithData++;
                }
            }
        }
        $avgAttain = $coWithData > 0 ? round($overallPct / $coWithData, 1) : 0;
        $summStats = [
            ['Courses', $totalCourses, 'fa-book', 'from-indigo-500 to-blue-600'],
            ['Total COs', $totalCOs, 'fa-bullseye', 'from-violet-500 to-purple-600'],
            ['Responses', $totalResponses, 'fa-comments', 'from-emerald-500 to-green-600'],
            ['Avg Attainment', $avgAttain . '%', 'fa-chart-line', 'from-amber-500 to-orange-600'],
        ];
        foreach ($summStats as $i => $sc): ?>
            <div class="stat-card hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
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

    <!-- Overall CO Chart -->
    <?php if (!empty($courses)): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-sm text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-indigo-500"></i> Course-wise Average CO Attainment
            </h3>
            <div style="height: 300px; position: relative;">
                <canvas id="courseAvgChart"></canvas>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-sm text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-area text-emerald-500"></i> CO Attainment Level Distribution
            </h3>
            <div style="height: 300px; position: relative;">
                <canvas id="levelDistChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Per-Course Detailed Analysis -->
    <?php if (empty($courses)): ?>
        <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-sm">
            <i class="fas fa-inbox text-4xl text-gray-200 mb-4 block"></i>
            <h3 class="text-lg font-bold text-gray-500">No course data found</h3>
            <p class="text-sm text-gray-400 mt-1">No CO attainment data for this department/semester/year combination.</p>
        </div>
    <?php else: ?>
        <?php $courseIdx = 0; foreach ($courses as $courseId => $c):
            $totalPctCourse = 0; $coCountCourse = count($c['cos']);
            foreach ($c['cos'] as $co) {
                $max = $co['max_score'] ?: 3;
                $totalPctCourse += ($co['avg_score'] / $max) * 100;
            }
            $avgPctCourse = $coCountCourse > 0 ? round($totalPctCourse / $coCountCourse, 1) : 0;
            $al = getAttainmentLevel($avgPctCourse);
            $responses = $courseResponses[$courseId] ?? [];
            $courseIdx++;
        ?>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm page-break-before">
            <!-- Course Header -->
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-indigo-50 via-white to-purple-50">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-book text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-800"><?= sanitize($c['code']) ?> — <?= sanitize($c['name']) ?></h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-bold border border-indigo-100">Sem <?= $c['sem'] ?></span>
                                <span class="px-2 py-0.5 bg-gray-50 text-gray-500 rounded text-[10px] font-bold border border-gray-100"><?= $c['year'] ?></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase <?= $al['badge'] ?>"><?= $al['label'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-black text-gray-800"><?= $avgPctCourse ?>%</div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Avg Attainment</div>
                    </div>
                </div>
            </div>

            <!-- CO Breakdown Table + Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 border-b border-gray-100">
                <!-- CO Table -->
                <div class="overflow-x-auto border-r border-gray-100">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 tracking-wider">
                                <th class="px-4 py-3 text-left">CO Code</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-center">Avg Score</th>
                                <th class="px-4 py-3 text-center">Attainment</th>
                                <th class="px-4 py-3 text-center">Responses</th>
                                <th class="px-4 py-3 text-center">Level</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($c['cos'] as $co):
                                $max = $co['max_score'] ?: 3;
                                $pct = round(($co['avg_score'] / $max) * 100, 1);
                                $coAl = getAttainmentLevel($pct);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <span class="font-black text-indigo-600 text-xs"><?= $co['co_code'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 max-w-[200px] truncate" title="<?= sanitize($co['co_desc']) ?>">
                                    <?= sanitize(substr($co['co_desc'], 0, 50)) ?><?= strlen($co['co_desc']) > 50 ? '...' : '' ?>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-gray-700"><?= $co['avg_score'] ?>/<?= $max ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full <?= $pct >= 66 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-400') ?>" style="width:<?= min($pct, 100) ?>%"></div>
                                        </div>
                                        <span class="text-xs font-black <?= $pct >= 66 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-500') ?>"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-500"><?= $co['total_responses'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase <?= $coAl['badge'] ?>"><?= $coAl['label'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Per-Course CO Bar Chart -->
                <div class="p-6 flex items-center justify-center print:hidden">
                    <div style="width: 100%; height: 250px; position: relative;">
                        <canvas id="coChart<?= $courseIdx ?>"></canvas>
                    </div>
                </div>
            </div>

            <!-- Student Responses for this Course -->
            <?php if (!empty($responses)): ?>
            <div class="px-6 py-4">
                <details class="group">
                    <summary class="flex items-center justify-between cursor-pointer select-none">
                        <h4 class="text-xs font-black text-gray-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-users text-indigo-400"></i> Student Responses (<?= count($responses) ?>)
                        </h4>
                        <i class="fas fa-chevron-down text-gray-300 text-xs group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="mt-3 overflow-x-auto max-h-[300px] overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-50 text-[9px] font-black uppercase text-gray-400 tracking-wider">
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">Student</th>
                                    <th class="px-3 py-2 text-left">Enrollment</th>
                                    <th class="px-3 py-2 text-center">Avg Score</th>
                                    <th class="px-3 py-2 text-center">Answers</th>
                                    <th class="px-3 py-2 text-left">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($responses as $ri => $resp): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-3 py-2 text-gray-400 font-bold"><?= $ri + 1 ?></td>
                                    <td class="px-3 py-2 font-bold text-gray-700"><?= sanitize($resp['student_name_db'] ?: ($resp['student_name'] ?: 'Anonymous')) ?></td>
                                    <td class="px-3 py-2 text-gray-400 font-mono"><?= sanitize($resp['enrollment_no'] ?: ($resp['student_roll'] ?: 'N/A')) ?></td>
                                    <td class="px-3 py-2 text-center font-bold text-indigo-600"><?= $resp['avg_score'] ?></td>
                                    <td class="px-3 py-2 text-center text-gray-500"><?= $resp['answer_count'] ?></td>
                                    <td class="px-3 py-2 text-gray-400"><?= date('d M Y, h:i A', strtotime($resp['submitted_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>

    <!-- Signature Section (print only) -->
    <div class="hidden print:block mt-12 pt-8 border-t-2 border-gray-300">
        <div class="flex justify-between mt-16">
            <div class="text-center w-48">
                <div class="border-b-2 border-gray-800 mb-2 h-12"></div>
                <div class="text-xs font-bold text-gray-600">Faculty Coordinator</div>
                <div class="text-[10px] text-gray-400">Date: _______________</div>
            </div>
            <div class="text-center w-48">
                <div class="border-b-2 border-gray-800 mb-2 h-12"></div>
                <div class="text-xs font-bold text-gray-600">Department Coordinator</div>
                <div class="text-[10px] text-gray-400">Dept. of <?= sanitize($deptInfo['name'] ?? 'N/A') ?></div>
            </div>
            <div class="text-center w-48">
                <div class="border-b-2 border-gray-800 mb-2 h-12"></div>
                <div class="text-xs font-bold text-gray-600">HOD / Principal</div>
                <div class="text-[10px] text-gray-400"><?= APP_NAME ?></div>
            </div>
        </div>
        <div class="text-center mt-8 text-[10px] text-gray-400">
            Generated on <?= date('d M Y, h:i A') ?> • <?= APP_NAME ?> • NAAC Feedback System
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                titleFont: { size: 12, weight: 'bold' },
                bodyFont: { size: 12 },
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(ctx) { return ctx.parsed.y + '% Attainment'; }
                }
            }
        }
    };

    <?php
    // Course average chart data
    $courseLabels = []; $courseAvgs = []; $courseColors = [];
    $lvlHigh = 0; $lvlMed = 0; $lvlLow = 0; $lvlNone = 0;
    foreach ($courses as $c) {
        $t = 0; $cn = count($c['cos']);
        foreach ($c['cos'] as $co) {
            $max = $co['max_score'] ?: 3;
            $t += ($co['avg_score'] / $max) * 100;
        }
        $avg = $cn > 0 ? round($t / $cn, 1) : 0;
        $courseLabels[] = $c['code'];
        $courseAvgs[] = $avg;
        if ($avg >= 66) { $courseColors[] = 'rgba(16, 185, 129, 0.8)'; $lvlHigh++; }
        elseif ($avg >= 50) { $courseColors[] = 'rgba(245, 158, 11, 0.8)'; $lvlMed++; }
        elseif ($avg >= 33) { $courseColors[] = 'rgba(239, 68, 68, 0.8)'; $lvlLow++; }
        else { $courseColors[] = 'rgba(156, 163, 175, 0.8)'; $lvlNone++; }
    }
    ?>

    // Course Average Attainment Chart
    if (document.getElementById('courseAvgChart')) {
        new Chart(document.getElementById('courseAvgChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($courseLabels) ?>,
                datasets: [{
                    data: <?= json_encode($courseAvgs) ?>,
                    backgroundColor: <?= json_encode($courseColors) ?>,
                    borderRadius: 8,
                    barThickness: 20
                }]
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { ticks: { font: { size: 9, weight: 'bold' } }, grid: { display: false } }
                },
                plugins: {
                    ...sharedOptions.plugins,
                    annotation: {
                        annotations: {
                            line66: { type: 'line', yMin: 66, yMax: 66, borderColor: 'rgba(16,185,129,0.5)', borderWidth: 1, borderDash: [5,5] },
                            line50: { type: 'line', yMin: 50, yMax: 50, borderColor: 'rgba(245,158,11,0.5)', borderWidth: 1, borderDash: [5,5] }
                        }
                    }
                }
            }
        });
    }

    // Level Distribution Doughnut
    if (document.getElementById('levelDistChart')) {
        new Chart(document.getElementById('levelDistChart'), {
            type: 'doughnut',
            data: {
                labels: ['Level 3 (≥66%)', 'Level 2 (50-65%)', 'Level 1 (33-49%)', 'Below Threshold'],
                datasets: [{
                    data: [<?= $lvlHigh ?>, <?= $lvlMed ?>, <?= $lvlLow ?>, <?= $lvlNone ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#9ca3af'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11, weight: 'bold' }, padding: 16, usePointStyle: true }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Per-Course CO Charts
    <?php $chartIdx = 0; foreach ($courses as $c): $chartIdx++;
        $coLabels = []; $coValues = []; $coCols = [];
        foreach ($c['cos'] as $co) {
            $max = $co['max_score'] ?: 3;
            $pct = round(($co['avg_score'] / $max) * 100, 1);
            $coLabels[] = $co['co_code'];
            $coValues[] = $pct;
            $coCols[] = $pct >= 66 ? 'rgba(16,185,129,0.8)' : ($pct >= 50 ? 'rgba(245,158,11,0.8)' : 'rgba(239,68,68,0.8)');
        }
    ?>
    if (document.getElementById('coChart<?= $chartIdx ?>')) {
        new Chart(document.getElementById('coChart<?= $chartIdx ?>'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($coLabels) ?>,
                datasets: [{
                    data: <?= json_encode($coValues) ?>,
                    backgroundColor: <?= json_encode($coCols) ?>,
                    borderRadius: 6,
                    barThickness: 16
                }]
            },
            options: {
                ...sharedOptions,
                indexAxis: 'y',
                scales: {
                    x: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    y: { ticks: { font: { size: 11, weight: 'bold' } }, grid: { display: false } }
                }
            }
        });
    }
    <?php endforeach; ?>
});
</script>

<style>
@media print {
    .admin-sidebar, .admin-sidebar *, .filter-bar, button, footer, .print\\:hidden { display: none !important; }
    .admin-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; box-shadow: none !important; }
    body { background: white !important; padding: 0.5cm !important; font-size: 11px; }
    .hidden.print\\:block { display: block !important; }
    .page-break-before { page-break-before: always; }
    .bg-white { border: 1px solid #ddd !important; box-shadow: none !important; }
    .rounded-2xl, .rounded-3xl { border-radius: 4px !important; }
    canvas { max-width: 100% !important; height: 200px !important; }
    details { open: true; }
    details > summary { display: none; }
    details > div { display: block !important; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
