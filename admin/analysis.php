<?php
/**
 * Admin - Enhanced NAAC Analysis (CO/PO Attainment)
 * v2.2 - Added Year filtering, Formula explanations & PDF optimization
 */
$pageTitle = 'NAAC Detailed Analysis';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// Filters
$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptRestriction = $isDeptAdmin ? " WHERE id = " . intval($_SESSION['admin_dept_id']) : "";

$departments = $pdo->query("SELECT * FROM departments $deptRestriction ORDER BY name")->fetchAll();
$selectedDept = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : intval($_GET['dept'] ?? ($departments[0]['id'] ?? 0));
$selectedSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$years = getYears($pdo);
$selectedYear = $_GET['year'] ?? ($years[0] ?? date('Y'));
$selectedType = $_GET['type'] ?? 'all';

// Fetch Analysis Data
$coData = getCOAttainmentByDept($pdo, $selectedDept, $selectedSem, $selectedYear);
$poStats = getPOAttainmentStats($pdo, $selectedDept, $selectedYear);
$naacReport = getNAACCriterionReport($pdo, $selectedDept, $selectedYear);

// Group COs by Course
$coCourses = [];
foreach ($coData as $co) {
    if ($selectedType !== 'all' && $selectedType !== 'co_attainment') continue;
    $key = $co['course_code'];
    if (!isset($coCourses[$key])) {
        $coCourses[$key] = [
            'name' => $co['course_name'],
            'code' => $co['course_code'],
            'sem' => $co['semester'],
            'year' => $co['year'],
            'cos' => []
        ];
    }
    $coCourses[$key]['cos'][] = $co;
}

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6 print:space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white/80 backdrop-blur-md border border-white/20 p-4 rounded-2xl shadow-sm flex flex-wrap items-center gap-4 animate-slide-down print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-4 w-full">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Department</label>
                <select name="dept" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $selectedDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Academic Year</label>
                <select name="year" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Semester</label>
                <select name="sem" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="">All Semesters</option>
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?= $i ?>" <?= $selectedSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] uppercase font-bold text-gray-400 ml-1">Analysis Type</label>
                <select name="type" onchange="this.form.submit()" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="all" <?= $selectedType == 'all' ? 'selected' : '' ?>>All Analysis</option>
                    <option value="co_attainment" <?= $selectedType == 'co_attainment' ? 'selected' : '' ?>>CO Attainment</option>
                    <option value="po_attainment" <?= $selectedType == 'po_attainment' ? 'selected' : '' ?>>PO/PSO Attainment</option>
                    <option value="dept_feedback" <?= $selectedType == 'dept_feedback' ? 'selected' : '' ?>>Dept Feedback</option>
                </select>
            </div>

            <div class="ml-auto flex gap-2">
                <a href="responses.php?dept=<?= $selectedDept ?>" class="px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-xl text-sm font-bold hover:bg-indigo-100 transition shadow-sm">
                    <i class="fas fa-users mr-2"></i> View Students
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </button>
            </div>
        </form>
    </div>

    <!-- NAAC Formula Explanation (Collapsible) -->
    <div class="bg-indigo-900 text-white p-6 rounded-3xl shadow-xl overflow-hidden relative print:bg-white print:text-black print:border print:border-gray-200 print:shadow-none">
        <div class="relative z-10">
            <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                <i class="fas fa-calculator text-indigo-300"></i>
                Analysis Methodology & Formulas
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm opacity-90">
                <div class="border-l-2 border-indigo-500/30 pl-4">
                    <h4 class="font-bold text-indigo-200 mb-2">CO Attainment Calculation</h4>
                    <p class="text-xs leading-relaxed">
                        Attainment % = (Avg. Score Obtained / Max Score) &times; 100.<br>
                        <strong>Thresholds:</strong><br>
                        Level 3 (High): &ge; 66%<br>
                        Level 2 (Medium): 50% - 65%<br>
                        Level 1 (Low): 33% - 49%
                    </p>
                </div>
                <div class="border-l-2 border-indigo-500/30 pl-4">
                    <h4 class="font-bold text-indigo-200 mb-2">PO Attainment Mapping</h4>
                    <p class="text-xs leading-relaxed">
                        PO attainment is calculated by aggregating responses from the Exit Survey. Each question maps to one or more POs. 
                        Overall PO Level = Sum(Avg. of Questions mapping to PO) / Count.
                    </p>
                </div>
                <div class="border-l-2 border-indigo-500/30 pl-4">
                    <h4 class="font-bold text-indigo-200 mb-2">Departmental Index</h4>
                    <p class="text-xs leading-relaxed">
                        Derived from Stakeholder Feedback (Curriculum, Infrastructure, etc.). Values are normalized to a scale of 100 for global comparison.
                    </p>
                </div>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl print:hidden"><i class="fas fa-chart-line"></i></div>
    </div>

    <!-- NAAC Summary Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- (Stats cards same as before but ensured to load correctly) -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center"><i class="fas fa-smile"></i></div>
                <span class="text-xs font-bold text-emerald-500 bg-emerald-50 px-2 py-1 rounded-lg">SSS Index</span>
            </div>
            <div class="text-2xl font-black text-gray-800"><?= $naacReport['overall']['satisfaction_pct'] ?? 0 ?>%</div>
            <div class="text-xs text-gray-400 mt-1"><?= $selectedYear ?> Satisfaction</div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center"><i class="fas fa-bullseye"></i></div>
                <span class="text-xs font-bold text-purple-500 bg-purple-50 px-2 py-1 rounded-lg">CO Attainment</span>
            </div>
            <div class="text-2xl font-black text-gray-800"><?= $naacReport['co_summary']['avg_attainment_pct'] ?? 0 ?>%</div>
            <div class="text-xs text-gray-400 mt-1">Avg. Course Level</div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center"><i class="fas fa-tasks"></i></div>
                <span class="text-xs font-bold text-amber-500 bg-amber-50 px-2 py-1 rounded-lg">PO Attainment</span>
            </div>
            <?php 
                $poAvgTotal = 0; $poFound = 0;
                foreach($poStats as $ps) { if($ps['type'] === 'PO') { $max = $ps['max_score'] ?: 5; $poAvgTotal += ($ps['avg_score'] / $max) * 100; $poFound++; } }
                $poAvgDisplay = $poFound > 0 ? round($poAvgTotal / $poFound, 1) : 0;
            ?>
            <div class="text-2xl font-black text-gray-800"><?= $poAvgDisplay ?>%</div>
            <div class="text-xs text-gray-400 mt-1">Program Outcome Level</div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center"><i class="fas fa-users"></i></div>
            </div>
            <div class="text-2xl font-black text-gray-800"><?= $naacReport['overall']['total_responses'] ?? 0 ?></div>
            <div class="text-xs text-gray-400 mt-1">Student Evaluations</div>
        </div>
    </div>

    <!-- Graphs Section (Fixed Layout) -->
    <?php if ($selectedType === 'all' || $selectedType === 'po_attainment'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-2">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-sm text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-chart-column text-indigo-500"></i> PO Attainment Metrics
            </h3>
            <div style="height: 300px; position: relative;">
                <canvas id="poChart"></canvas>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-sm text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-chart-pie text-emerald-500"></i> PSO Radar Analysis
            </h3>
            <div style="height: 300px; position: relative;">
                <canvas id="psoChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CO Attainment Table with Years -->
    <?php if ($selectedType === 'all' || $selectedType === 'co_attainment'): ?>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm page-break-before">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-700">Course Outcome Attainment Detail</h3>
                <p class="text-xs text-gray-400">Detailed subject-wise analysis for Sem/Year filtered cohorts</p>
            </div>
            <a href="<?= APP_URL ?>/admin/co_analysis.php?dept=<?= $selectedDept ?>&sem=<?= $selectedSem ?>&year=<?= $selectedYear ?>" class="px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-xl text-sm font-bold hover:bg-indigo-100 transition shadow-sm print:hidden">
                <i class="fas fa-bullseye mr-2"></i>Detailed CO Analysis →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
                        <th class="px-6 py-4">Sem/Year</th>
                        <th class="px-6 py-4">Course</th>
                        <th class="px-6 py-4">CO Breakdown (Avg Score)</th>
                        <th class="px-6 py-4 text-center">Avg %</th>
                        <th class="px-6 py-4 text-center">NAAC Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($coCourses)): ?>
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No course data found. Ensure subjects are assigned to semester <?= $selectedSem ?: '' ?> in year <?= $selectedYear ?>.</td></tr>
                    <?php else: foreach ($coCourses as $c): 
                        $totalPct = 0; $coCount = count($c['cos']);
                        foreach($c['cos'] as $co) { 
                            $max = $co['max_score'] ?: 3;
                            $totalPct += ($co['avg_score'] / $max) * 100;
                        }
                        $avgPct = $coCount > 0 ? round($totalPct / $coCount, 1) : 0;
                        $al = getAttainmentLevel($avgPct);
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-[10px] font-bold text-gray-400">
                            S<?= $c['sem'] ?> | <?= $c['year'] ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-indigo-600"><?= $c['code'] ?></div>
                            <div class="text-[11px] text-gray-500 truncate w-48"><?= $c['name'] ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <?php foreach($c['cos'] as $co): 
                                    $p = round(($co['avg_score'] / ($co['max_score'] ?: 3)) * 100);
                                    $cClass = $p >= 66 ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : ($p >= 50 ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-red-600 bg-red-50 border-red-100');
                                ?>
                                    <span class="px-2 py-0.5 border rounded text-[10px] font-bold <?= $cClass ?>">
                                        <?= $co['co_code'] ?>: <?= $co['avg_score'] ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center font-black text-gray-700 text-base"><?= $avgPct ?>%</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?= $al['badge'] ?>">
                                <?= $al['label'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php
        $poLabels = []; $poValues = [];
        $psoLabels = []; $psoValues = [];
        foreach($poStats as $ps) {
            $val = round(($ps['avg_score'] / ($ps['max_score'] ?: 5)) * 100);
            if($ps['type'] === 'PO') { $poLabels[] = $ps['code']; $poValues[] = $val; }
            if($ps['type'] === 'PSO') { $psoLabels[] = $ps['code']; $psoValues[] = $val; }
        }
    ?>

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
                cornerRadius: 8
            }
        }
    };

    if (document.getElementById('poChart')) {
        new Chart(document.getElementById('poChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($poLabels) ?>,
                datasets: [{
                    data: <?= json_encode($poValues) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.85)',
                    hoverBackgroundColor: '#4f46e5',
                    borderRadius: 10,
                    barThickness: 15
                }]
            },
            options: {
                ...sharedOptions,
                indexAxis: 'x',
                scales: {
                    y: { min: 0, max: 100, ticks: { font: { size: 10 } }, grid: { display: false } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    if (document.getElementById('psoChart')) {
        new Chart(document.getElementById('psoChart'), {
            type: 'radar',
            data: {
                labels: <?= json_encode($psoLabels) ?>,
                datasets: [{
                    data: <?= json_encode($psoValues) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10b981',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10b981',
                    pointRadius: 4,
                    fill: true
                }]
            },
            options: {
                ...sharedOptions,
                scales: {
                    r: {
                        min: 0, max: 100,
                        angleLines: { color: 'rgba(0,0,0,0.05)' },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { display: false },
                        pointLabels: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    }
});
</script>

<style>
@media print {
    .admin-sidebar, .admin-sidebar *, .filter-bar, button, footer, a[href*="responses.php"] { display: none !important; }
    .admin-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; box-shadow: none !important; }
    body { background: white !important; padding: 1cm !important; }
    .page-break-before { page-break-before: always; }
    .bg-white { border: 1px solid #eee !important; box-shadow: none !important; }
    .rounded-2xl, .rounded-3xl { border-radius: 0.5rem !important; }
    canvas { max-width: 100% !important; height: 250px !important; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
