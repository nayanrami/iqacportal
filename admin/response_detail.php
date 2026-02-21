<?php
/**
 * Admin - Individual Response Detail
 */
$pageTitle = 'Response Detail';
require_once __DIR__ . '/../functions.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$r = getResponseDetails($pdo, $id);

if (!$r) {
    setFlash('error', 'Response not found.');
    redirect('responses.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <!-- Header with Print -->
    <div class="flex items-center justify-between mb-2">
        <a href="responses.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold flex items-center gap-2 print:hidden">
            <i class="fas fa-arrow-left"></i> Back to Responses
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm print:hidden">
            <i class="fas fa-print mr-2"></i> Print Student Report
        </button>
    </div>

    <!-- Student Info Card -->
    <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm overflow-hidden relative">
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-gray-800"><?= sanitize($r['student_name']) ?></h3>
                    <p class="text-gray-400 text-sm font-medium"><?= sanitize($r['student_roll']) ?> | <?= sanitize($r['dept_name']) ?></p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-1">Submitted On</div>
                <div class="text-sm font-bold text-gray-700"><?= date('d M Y, h:i A', strtotime($r['submitted_at'])) ?></div>
            </div>
        </div>
        <div class="absolute -right-6 -bottom-6 opacity-5 text-8xl"><i class="fas fa-id-card"></i></div>
    </div>

    <!-- Form & Scores -->
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold text-gray-400">Feedback Form</span>
                <h4 class="font-bold text-gray-800"><?= sanitize($r['form_title']) ?></h4>
            </div>
            <?php if($r['course_name']): ?>
                <div class="text-right">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold"><?= sanitize($r['course_code']) ?>: <?= sanitize($r['course_name']) ?></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach($r['answers'] as $a): 
                    $pct = ($a['score'] / $a['max_score']) * 100;
                    $color = $pct >= 80 ? 'bg-emerald-500' : ($pct >= 60 ? 'bg-indigo-500' : ($pct >= 40 ? 'bg-amber-500' : 'bg-red-500'));
                ?>
                <div class="p-4 bg-gray-50/50 border border-gray-100 rounded-2xl transition hover:border-indigo-200">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div class="flex-1">
                            <p class="text-sm text-gray-700 font-medium"><?= sanitize($a['question_text']) ?></p>
                            <div class="flex gap-2 mt-2">
                                <?php if($a['co_code']): ?>
                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-600 border border-purple-100 rounded text-[10px] font-bold">CO: <?= $a['co_code'] ?></span>
                                <?php endif; ?>
                                <?php if($a['po_code']): ?>
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 border border-amber-100 rounded text-[10px] font-bold">PO: <?= $a['po_code'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-center w-12 flex-shrink-0">
                            <div class="text-xl font-black text-gray-800"><?= $a['score'] ?></div>
                            <div class="text-[10px] text-gray-400">/ <?= $a['max_score'] ?></div>
                        </div>
                    </div>
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full <?= $color ?> rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .admin-sidebar, .admin-sidebar *, .print\:hidden, a, button { display: none !important; }
    .admin-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    body { background: white !important; }
    .bg-white { border: 1px solid #eee !important; box-shadow: none !important; }
    .rounded-3xl, .rounded-2xl { border-radius: 1rem !important; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
