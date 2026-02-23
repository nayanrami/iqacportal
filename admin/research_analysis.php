<?php
/**
 * Admin - Research Data Analysis
 * v1.0 - Research Metrics for NAAC
 */
$pageTitle = 'Research Analysis';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

require_once __DIR__ . '/header.php';

// Fetch data for Analysis
$where = $deptId ? " WHERE department_id = $deptId" : "";
$records = $pdo->query("SELECT r.*, c.name as cat_name FROM research_records r JOIN research_categories c ON c.id = r.category_id $where")->fetchAll();

// Grouping logic for deeper analysis
$categoryCounts = [];
$fundingByYear = [];
$publicationsByYear = [];
$facultyContributions = [];
$deptContributions = [];
$indexingCounts = ['Scopus' => 0, 'Web of Science' => 0, 'UGC Care' => 0, 'Others/Peer Reviewed' => 0];
$roleCounts = [];
$impactFactorSum = 0;
$pubWithImpactCount = 0;

foreach ($records as $r) {
    // Category distribution
    $categoryCounts[$r['cat_name']] = ($categoryCounts[$r['cat_name']] ?? 0) + 1;
    
    // Year extraction
    $year = $r['publication_date'] ? date('Y', strtotime($r['publication_date'])) : 'Unknown';
    
    // Funding by year
    if ($r['funding_amount'] > 0) {
        $fundingByYear[$year] = ($fundingByYear[$year] ?? 0) + $r['funding_amount'];
    }
    
    // Faculty Analysis
    $faculty = $r['faculty_name'] ?: 'Unknown/Dept';
    $facultyContributions[$faculty] = ($facultyContributions[$faculty] ?? 0) + 1;

    // Dept Analysis (for ranking)
    $dName = $r['dept_name'] ?? 'General';
    $deptContributions[$dName] = ($deptContributions[$dName] ?? 0) + 1;
    
    // Indexing Analysis
    if (strpos($r['cat_name'], 'Publication') !== false) {
        $publicationsByYear[$year] = ($publicationsByYear[$year] ?? 0) + 1;
        
        $idx = $r['indexing'] ?? 'None';
        if ($idx === 'Scopus') $indexingCounts['Scopus']++;
        elseif ($idx === 'Web of Science') $indexingCounts['Web of Science']++;
        elseif ($idx === 'UGC Care') $indexingCounts['UGC Care']++;
        else $indexingCounts['Others/Peer Reviewed']++;

        if ($r['impact_factor'] > 0) {
            $impactFactorSum += $r['impact_factor'];
            $pubWithImpactCount++;
        }
    }

    // Role Analysis
    $role = $r['author_role'] ?: 'Unspecified';
    $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
}

// Ensure chronological order and top slices
ksort($fundingByYear);
ksort($publicationsByYear);
arsort($facultyContributions);
$facultyContributions = array_slice($facultyContributions, 0, 8); // Top 8 faculties
arsort($deptContributions);

$avgImpact = $pubWithImpactCount > 0 ? round($impactFactorSum / $pubWithImpactCount, 2) : 0;
?>

<main class="p-4 md:p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-black gradient-text">Research Data Analysis</h1>
        <p class="text-gray-500 text-sm">Qualitative and Quantitative Research Metrics for NAAC Accreditation.</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6 flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 text-xl shadow-inner">
                <i class="fas fa-microscope"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Total Research</div>
                <div class="text-2xl font-black text-gray-800"><?= count($records) ?></div>
            </div>
        </div>
        <div class="glass-card p-6 flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-xl shadow-inner">
                <i class="fas fa-star"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Avg Impact Factor</div>
                <div class="text-2xl font-black text-gray-800"><?= $avgImpact ?></div>
            </div>
        </div>
        <div class="glass-card p-6 flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl shadow-inner">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Funding (Lakhs)</div>
                <div class="text-2xl font-black text-gray-800">₹<?= round(array_sum($fundingByYear) / 100000, 2) ?>L</div>
            </div>
        </div>
        <div class="glass-card p-6 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-xl shadow-inner">
                <i class="fas fa-certificate"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Total Patents</div>
                <div class="text-2xl font-black text-gray-800"><?= $categoryCounts['Patent'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Publication Trends -->
        <div class="glass-card p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-chart-line text-indigo-500"></i> Publication Trends (Annual)
            </h3>
            <div class="h-[300px]">
                <canvas id="pubChart"></canvas>
            </div>
        </div>

        <!-- Faculty Contributions -->
        <div class="glass-card p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-users text-purple-500"></i> Top Research Contributors (Faculty)
            </h3>
            <div class="h-[300px]">
                <canvas id="facultyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Funding Analysis -->
        <div class="lg:col-span-2 glass-card p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-coins text-emerald-500"></i> Funding Grant Distribution
            </h3>
            <div class="h-[350px]">
                <canvas id="fundingChart"></canvas>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="glass-card p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-tasks text-amber-500"></i> Research Status
            </h3>
            <div class="h-[300px]">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Specialized NAAC Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Indexing Quality -->
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-award text-indigo-500"></i> Journal Indexing Quality
                </h3>
                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-black uppercase">NAAC 3.4.2</span>
            </div>
            <div class="h-[300px]">
                <canvas id="indexingChart"></canvas>
            </div>
        </div>

        <!-- Role Leadership -->
        <div class="glass-card p-6">
            <h3 class="text-sm font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-user-tie text-rose-500"></i> Contributor Role Distribution
            </h3>
            <div class="h-[300px]">
                <canvas id="roleChart"></canvas>
            </div>
        </div>
    </div>

    <?php if (!$deptId): ?>
    <!-- Department Ranking (Super Admin Only) -->
    <div class="glass-card overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-700 italic">Departmental Research Ranking</h3>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">NAAC Criterion 3.1</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/30">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-4">Department Name</th>
                        <th class="px-6 py-4 text-center">Total Activities</th>
                        <th class="px-6 py-4">Productivity Index</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php 
                    $maxContrib = max($deptContributions ?: [1]);
                    foreach ($deptContributions as $name => $count): 
                        $rankPct = round(($count / $maxContrib) * 100);
                    ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-black text-gray-700 uppercase tracking-tighter"><?= htmlspecialchars($name) ?></td>
                        <td class="px-6 py-4 text-center font-black text-indigo-600 text-lg"><?= $count ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-grow h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500" style="width: <?= $rankPct ?>%"></div>
                                </div>
                                <span class="text-[10px] font-black text-gray-400 w-8"><?= $rankPct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Publication Trends Chart
    new Chart(document.getElementById('pubChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($publicationsByYear)) ?>,
            datasets: [{
                label: 'Publications',
                data: <?= json_encode(array_values($publicationsByYear)) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#4f46e5'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. Faculty Contribution Chart
    new Chart(document.getElementById('facultyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($facultyContributions)) ?>,
            datasets: [{
                label: 'Research Items',
                data: <?= json_encode(array_values($facultyContributions)) ?>,
                backgroundColor: '#8b5cf6',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // 3. Category Distribution Chart
    new Chart(document.getElementById('typeChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($categoryCounts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($categoryCounts)) ?>,
                backgroundColor: ['#4f46e5', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#64748b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 10 } } }
            },
            cutout: '70%'
        }
    });

    // 4. Funding Chart
    new Chart(document.getElementById('fundingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($fundingByYear)) ?>,
            datasets: [{
                label: 'Grant Amount (₹)',
                data: <?= json_encode(array_values($fundingByYear)) ?>,
                backgroundColor: '#10b981',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 5. Indexing Chart
    new Chart(document.getElementById('indexingChart').getContext('2d'), {
        type: 'polarArea',
        data: {
            labels: <?= json_encode(array_keys($indexingCounts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($indexingCounts)) ?>,
                backgroundColor: ['#4f46e5', '#8b5cf6', '#06b6d4', '#94a3b8']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 10 } } }
            }
        }
    });

    // 6. Role Distribution Chart
    new Chart(document.getElementById('roleChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($roleCounts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($roleCounts)) ?>,
                backgroundColor: ['#f43f5e', '#fb923c', '#fbbf24', '#2dd4bf', '#a78bfa']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 6, font: { size: 10 } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
