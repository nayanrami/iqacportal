<?php
/**
 * Admin - Individual Research Record View (Printable)
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) redirect(APP_URL . '/admin/research.php');

$stmt = $pdo->prepare("
    SELECT r.*, c.name as category_name, d.name as dept_name 
    FROM research_records r 
    JOIN research_categories c ON c.id = r.category_id 
    JOIN departments d ON d.id = r.department_id 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('danger', 'Research record not found.');
    redirect(APP_URL . '/admin/research.php');
}

// Security: Dept admins can only view their own records
if (isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null && $_SESSION['admin_dept_id'] != $record['department_id']) {
    setFlash('danger', 'Unauthorized access.');
    redirect(APP_URL . '/admin/research.php');
}

$settings = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$collegeName = $settings['app_institute'] ?? 'IQAC Institute';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Report - <?= htmlspecialchars($record['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-border { border: 2px solid #333 !important; }
        }
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; }
    </style>
</head>
<body class="p-4 md:p-12">

<div class="max-w-4xl mx-auto">
    <!-- Toolbar -->
    <div class="no-print flex justify-between items-center mb-8 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <a href="<?= APP_URL ?>/admin/research.php" class="text-gray-500 hover:text-gray-800 flex items-center gap-2 text-sm font-bold transition">
            <i class="fas fa-arrow-left"></i> Back to Research
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-black shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition flex items-center gap-2">
            <i class="fas fa-print"></i> Save as PDF / Print
        </button>
    </div>

    <!-- Official Document -->
    <div class="bg-white p-12 rounded shadow-sm border border-gray-100 min-h-[1000px] flex flex-col">
        <!-- Header -->
        <div class="text-center border-b-2 border-indigo-600 pb-8 mb-12">
            <h2 class="text-lg font-black text-indigo-600 uppercase tracking-widest mb-1"><?= htmlspecialchars($collegeName) ?></h2>
            <h1 class="text-3xl font-black text-gray-800 mb-2">Research Activity Report</h1>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Internal Quality Assurance Cell (IQAC)</p>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 gap-y-8 mb-12">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Reporting Department</label>
                <div class="text-sm font-black text-gray-800"><?= htmlspecialchars($record['dept_name']) ?></div>
            </div>
            <div class="text-right">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Activity Date</label>
                <div class="text-sm font-black text-gray-800"><?= $record['publication_date'] ? date('d M Y', strtotime($record['publication_date'])) : 'Pending' ?></div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Research Category</label>
                <div class="text-sm font-black text-indigo-600 uppercase"><?= htmlspecialchars($record['category_name']) ?></div>
            </div>
            <div class="text-right">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Current Status</label>
                <div class="text-sm font-black uppercase <?= $record['status'] === 'published' ? 'text-emerald-600' : 'text-amber-500' ?>"><?= $record['status'] ?></div>
            </div>
        </div>

        <!-- Content -->
        <div class="space-y-10 flex-grow">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 border-l-4 border-indigo-600 pl-3">Investigation Title</label>
                <h3 class="text-2xl font-black text-gray-800 leading-tight"><?= htmlspecialchars($record['title']) ?></h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Primary Investigator / Author</label>
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="font-black text-gray-800 italic"><?= htmlspecialchars($record['faculty_name'] ?: 'Departmental Project') ?></div>
                        <?php if ($record['author_role']): ?>
                            <div class="text-[10px] text-indigo-600 font-bold uppercase mt-1">Role: <?= htmlspecialchars($record['author_role']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Venue / Journal / Agency</label>
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="font-black text-gray-800"><?= htmlspecialchars($record['journal_conference'] ?: 'Internal / Non-disclosed') ?></div>
                        <?php if ($record['issn_isbn']): ?>
                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">ISSN/ISBN: <?= htmlspecialchars($record['issn_isbn']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Journal Indexing (NAAC Evidence)</label>
                    <div class="p-4 border-2 border-indigo-50 rounded-xl bg-indigo-50/20">
                        <div class="text-sm font-black text-indigo-700 uppercase tracking-tighter italic">
                            <i class="fas fa-certificate mr-2"></i><?= $record['indexing'] !== 'None' ? $record['indexing'] : 'Peer Reviewed / Others' ?>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Collaborating Agency</label>
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 italic">
                        <?= htmlspecialchars($record['collaborating_agency'] ?: 'Individual / Independent') ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Technical Metrics & Funding</label>
                <div class="grid grid-cols-3 gap-6">
                    <div class="p-4 border-2 border-gray-50 rounded-2xl text-center">
                        <div class="text-2xl font-black text-indigo-600"><?= $record['impact_factor'] ?: '0.00' ?></div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Impact Factor</div>
                    </div>
                    <div class="p-4 border-2 border-gray-50 rounded-2xl text-center">
                        <div class="text-2xl font-black text-emerald-600">₹<?= number_format($record['funding_amount'], 2) ?></div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Grant Amount</div>
                    </div>
                    <div class="p-4 border-2 border-gray-50 rounded-2xl text-center">
                        <div class="text-2xl font-black text-gray-800"><?= $record['id'] ?></div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Record Ref #</div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Abstract / Description</label>
                <div class="text-sm text-gray-600 leading-relaxed text-justify bg-gray-50/50 p-6 rounded-2xl border border-gray-100">
                    <?= nl2br(htmlspecialchars($record['description'] ?: 'No description provided for this research activity.')) ?>
                </div>
            </div>

            <?php if ($record['link']): ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Reference Link</label>
                <a href="<?= $record['link'] ?>" class="text-xs font-bold text-blue-500 hover:underline break-all"><?= $record['link'] ?></a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Signatures -->
        <div class="mt-20 pt-12 border-t border-gray-100 grid grid-cols-2 gap-12">
            <div class="text-center">
                <div class="h-16 mb-4"></div>
                <div class="border-t-2 border-gray-800 pt-2 inline-block px-12">
                    <div class="text-xs font-black uppercase text-gray-800">Faculty Investigator</div>
                </div>
            </div>
            <div class="text-center">
                <div class="h-16 mb-4"></div>
                <div class="border-t-2 border-gray-800 pt-2 inline-block px-12">
                    <div class="text-xs font-black uppercase text-gray-800">Department Head</div>
                </div>
            </div>
        </div>

        <div class="mt-auto pt-12 text-center">
            <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.2em]">Generated by <?= APP_NAME ?> • <?= date('d M Y, h:i A') ?></p>
        </div>
    </div>
</div>

</body>
</html>
