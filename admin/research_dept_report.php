<?php
/**
 * Admin - Departmental Research Consolidated Report
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$reqDeptId = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : ($isDeptAdmin ? $_SESSION['admin_dept_id'] : null);

if (!$reqDeptId) {
    setFlash('warning', 'Please select a department to generate report.');
    redirect(APP_URL . '/admin/research.php');
}

// Fetch Department Data
$deptStmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$deptStmt->execute([$reqDeptId]);
$dept = $deptStmt->fetch();

if (!$dept) {
    setFlash('danger', 'Department not found.');
    redirect(APP_URL . '/admin/research.php');
}

// Fetch Research Data
$records = $pdo->query("
    SELECT r.*, c.name as cat_name 
    FROM research_records r 
    JOIN research_categories c ON c.id = r.category_id 
    WHERE r.department_id = $reqDeptId 
    ORDER BY r.publication_date DESC
")->fetchAll();

// Aggregates
$summary = [
    'total' => count($records),
    'publications' => 0,
    'funding' => 0,
    'patents' => 0,
    'avg_impact' => 0,
    'impact_count' => 0
];

foreach ($records as $r) {
    if (strpos($r['cat_name'], 'Publication') !== false) $summary['publications']++;
    if ($r['cat_name'] === 'Patent') $summary['patents']++;
    $summary['funding'] += $r['funding_amount'];
    if ($r['impact_factor'] > 0) {
        $summary['avg_impact'] += $r['impact_factor'];
        $summary['impact_count']++;
    }
}
$summary['avg_impact'] = $summary['impact_count'] > 0 ? round($summary['avg_impact'] / $summary['impact_count'], 2) : 0;

$settings = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$collegeName = $settings['app_institute'] ?? 'IQAC Institute';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidated Research Report - <?= htmlspecialchars($dept['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .page-break { page-break-before: always; }
        }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="p-4 md:p-12">

<div class="max-w-5xl mx-auto">
    <!-- Toolbar -->
    <div class="no-print flex justify-between items-center mb-8 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <a href="<?= APP_URL ?>/admin/research.php" class="text-gray-500 hover:text-gray-800 flex items-center gap-2 text-sm font-bold transition">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div class="flex gap-4">
             <button onclick="window.print()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-black shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Export Department Report
            </button>
        </div>
    </div>

    <!-- Official Document -->
    <div class="bg-white p-12 rounded shadow-sm border border-gray-100 min-h-[1200px] flex flex-col">
        <!-- Header -->
        <div class="text-center border-b-4 border-double border-gray-800 pb-8 mb-10">
            <h2 class="text-lg font-black text-gray-800 uppercase tracking-widest mb-1"><?= htmlspecialchars($collegeName) ?></h2>
            <h1 class="text-3xl font-black text-gray-900 mb-2">Consolidated Research Activity Report</h1>
            <h3 class="text-xl font-bold text-indigo-600"><?= htmlspecialchars($dept['name']) ?></h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-4">Academic Period: Continuous Monitoring</p>
        </div>

        <!-- executive summary -->
        <div class="mb-12">
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-l-4 border-indigo-600 pl-3">Executive Summary</h4>
            <div class="grid grid-cols-4 gap-6">
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                    <div class="text-2xl font-black text-gray-800"><?= $summary['total'] ?></div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Activities</div>
                </div>
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                    <div class="text-2xl font-black text-indigo-600"><?= $summary['publications'] ?></div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Publications</div>
                </div>
                <div class="p-6 bg-indigo-600 rounded-2xl text-center text-white shadow-lg shadow-indigo-100">
                    <div class="text-2xl font-black">₹<?= number_format($summary['funding'] / 100000, 2) ?>L</div>
                    <div class="text-[9px] font-bold opacity-70 uppercase tracking-widest">Total Grants</div>
                </div>
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-center">
                    <div class="text-2xl font-black text-emerald-600"><?= $summary['avg_impact'] ?></div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Avg. Impact Factor</div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="mb-12 flex-grow">
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-l-4 border-indigo-600 pl-3">Detailed Activity Registry</h4>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-white text-[9px] font-black uppercase tracking-widest text-left">
                        <th class="p-3 border border-gray-300">Sr.</th>
                        <th class="p-3 border border-gray-300">Research Title & Metadata</th>
                        <th class="p-3 border border-gray-300">Primary Investigator & Role</th>
                        <th class="p-3 border border-gray-300 text-center">Indexing & ISSN</th>
                        <th class="p-3 border border-gray-300">Venue / Agency</th>
                        <th class="p-3 border border-gray-300 text-center">Year</th>
                        <th class="p-3 border border-gray-300 text-right">Funding (₹)</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] text-gray-700">
                    <?php if (empty($records)): ?>
                        <tr><td colspan="7" class="p-8 text-center text-gray-400 grayscale italic border border-gray-200">No research records found for this department.</td></tr>
                    <?php else: foreach ($records as $i => $r): ?>
                        <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50/50' ?>">
                            <td class="p-3 border border-gray-200 text-center font-bold"><?= $i + 1 ?></td>
                            <td class="p-3 border border-gray-200">
                                <div class="font-black leading-tight mb-1"><?= htmlspecialchars($r['title']) ?></div>
                                <div class="text-[8px] text-indigo-500 font-bold uppercase"><?= htmlspecialchars($r['cat_name']) ?></div>
                            </td>
                            <td class="p-3 border border-gray-200">
                                <div class="font-bold"><?= htmlspecialchars($r['faculty_name'] ?: '-') ?></div>
                                <div class="text-[8px] text-gray-400 font-medium italic"><?= htmlspecialchars($r['author_role'] ?: '-') ?></div>
                            </td>
                            <td class="p-3 border border-gray-200 text-center">
                                <div class="font-black text-indigo-600"><?= $r['indexing'] !== 'None' ? $r['indexing'] : 'Peer-Review' ?></div>
                                <div class="text-[8px] text-gray-400 font-mono"><?= htmlspecialchars($r['issn_isbn'] ?: '-') ?></div>
                            </td>
                            <td class="p-3 border border-gray-200"><?= htmlspecialchars($r['journal_conference'] ?: '-') ?></td>
                            <td class="p-3 border border-gray-200 text-center font-bold"><?= $r['publication_date'] ? date('Y', strtotime($r['publication_date'])) : '-' ?></td>
                            <td class="p-3 border border-gray-200 text-right font-mono font-bold"><?= number_format($r['funding_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Signatures -->
        <div class="mt-20 grid grid-cols-3 gap-12">
            <div class="text-center">
                <div class="h-20 flex flex-col justify-end items-center">
                    <div class="w-32 h-0.5 bg-gray-200 mb-2"></div>
                    <div class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Prepared By</div>
                    <div class="text-[8px] text-gray-400 font-bold">Reseach Coordinator</div>
                </div>
            </div>
            <div class="text-center">
                <div class="h-20 flex flex-col justify-end items-center">
                    <div class="w-32 h-0.5 bg-gray-200 mb-2"></div>
                    <div class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Verified By</div>
                    <div class="text-[8px] text-gray-400 font-bold">HOD - <?= htmlspecialchars($dept['name']) ?></div>
                </div>
            </div>
            <div class="text-center">
                <div class="h-20 flex flex-col justify-end items-center">
                    <div class="w-32 h-0.5 bg-gray-200 mb-2"></div>
                    <div class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Approved By</div>
                    <div class="text-[8px] text-gray-400 font-bold">IQAC Coordinator / Principal</div>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-gray-100 text-center">
            <p class="text-[8px] text-gray-300 font-bold uppercase tracking-widest">Generated by IQAC Portal • <?= date('d M Y, h:i A') ?> • Page 1 of 1</p>
        </div>
    </div>
</div>

</body>
</html>
