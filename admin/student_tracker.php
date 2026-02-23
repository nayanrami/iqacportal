<?php
/**
 * Admin - Student Completion Tracker Detail Page
 */
$pageTitle = 'Student Tracker';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$deptId = $_SESSION['admin_dept_id'] ?? null;
$semFilter = isset($_GET['sem']) ? intval($_GET['sem']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$studentStatus = getStudentCompletionStatus($pdo, $deptId, $semFilter);

// Apply Search if any
if ($search) {
    $studentStatus = array_filter($studentStatus, function($s) use ($search) {
        return stripos($s['name'], $search) !== false || stripos($s['enrollment_no'], $search) !== false;
    });
}

$incompleteCount = count(array_filter($studentStatus, fn($s) => $s['completed_forms'] < $s['total_forms']));
$completeCount = count($studentStatus) - $incompleteCount;

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Student Tracker</h1>
            <p class="text-gray-500 text-sm">Monitor individual student participation and identify pending feedback.</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= APP_URL ?>/admin/index.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition flex items-center gap-2 text-sm">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="<?= APP_URL ?>/admin/students.php" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition flex items-center gap-2 text-sm">
                <i class="fas fa-user-plus"></i> Manage Students
            </a>
        </div>
    </div>

    <!-- Filters & Stats Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Search & Filter Card -->
        <div class="lg:col-span-2 glass-card p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Search Student</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?= $search ?>" placeholder="Name or Enrollment No..." 
                               class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition shadow-inner">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Semester</label>
                    <select name="sem" onchange="this.form.submit()" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition shadow-inner">
                        <option value="">All Semesters</option>
                        <?php for($i=1;$i<=8;$i++): ?>
                            <option value="<?= $i ?>" <?= $semFilter == $i ? 'selected' : '' ?>>Sem <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <input type="submit" class="hidden">
            </form>
        </div>

        <!-- Participation Card -->
        <div class="glass-card p-6 flex flex-col justify-center">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500">Participation Overview</span>
                <span class="text-[10px] font-black text-gray-400 uppercase"><?= count($studentStatus) ?> Total</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <div class="h-4 bg-gray-100 rounded-full overflow-hidden flex shadow-inner">
                        <?php 
                        $total = count($studentStatus) ?: 1;
                        $compPct = ($completeCount / $total) * 100;
                        ?>
                        <div class="h-full bg-emerald-500 shadow-sm" style="width: <?= $compPct ?>%"></div>
                        <div class="h-full bg-rose-400 shadow-sm" style="width: <?= 100 - $compPct ?>%"></div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-black text-gray-800"><?= round($compPct) ?>%</div>
                </div>
            </div>
            <div class="flex justify-between mt-4">
                <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> <?= $completeCount ?> Complete
                </div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span> <?= $incompleteCount ?> Pending
                </div>
            </div>
        </div>
    </div>

    <!-- Student List -->
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/80 border-b border-gray-100">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-4">Student Identity</th>
                        <th class="px-6 py-4 text-center">Semester</th>
                        <th class="px-6 py-4 text-center">Form completion</th>
                        <th class="px-6 py-4 text-center">Progress</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($studentStatus)): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No students found matching your criteria.</td></tr>
                    <?php else: foreach ($studentStatus as $s): 
                        $isComp = $s['completed_forms'] >= $s['total_forms'] && $s['total_forms'] > 0;
                        $pct = $s['total_forms'] > 0 ? round(($s['completed_forms'] / $s['total_forms']) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50/50 transition <?= !$isComp ? 'bg-rose-50/10' : '' ?>">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold <?= $isComp ? 'text-gray-800' : 'text-rose-900 shadow-sm transition' ?>"><?= sanitize($s['name']) ?></div>
                            <div class="text-[10px] text-gray-400 font-mono"><?= sanitize($s['enrollment_no']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-black uppercase tracking-widest italic">
                                Sem <?= $s['semester'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-sm">
                            <span class="<?= $isComp ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $s['completed_forms'] ?></span>
                            <span class="text-gray-300">/ <?= $s['total_forms'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-3">
                                <div class="w-20 h-1 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $isComp ? 'bg-emerald-500 shadow-emerald-200 shadow-inner' : 'bg-amber-400 shadow-inner' ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="text-[9px] font-black text-gray-400"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($isComp): ?>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[9px] font-black uppercase tracking-widest border border-emerald-100 shadow-sm"><i class="fas fa-check-circle mr-1"></i>Compliant</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-rose-50 text-rose-500 rounded-full text-[9px] font-black uppercase tracking-widest border border-rose-100 shadow-sm"><i class="fas fa-exclamation-circle mr-1"></i>Action Required</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
