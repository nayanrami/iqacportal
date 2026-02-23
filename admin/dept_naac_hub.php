<?php
/**
 * Admin - Departmental NAAC Hub
 * Targeted command center for Department Coordinators.
 */
$pageTitle = 'Department NAAC Hub';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? null;

if (!$deptId && $currentRole === 'deptadmin') {
    die("Error: Department association missing for this account.");
}

// If SuperAdmin accesses this, they might need a dept filter
if (!$deptId && isset($_GET['dept_id'])) {
    $deptId = intval($_GET['dept_id']);
}

$deptName = "Institutional";
if ($deptId) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    $deptName = $stmt->fetchColumn();
}

$resRecords = $pdo->query("SELECT id FROM research_records" . ($deptId ? " WHERE department_id = $deptId" : ""))->fetchAll();
$totalRes = count($resRecords);
require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-widest">Department Portal</span>
            <h1 class="text-3xl font-black text-gray-800"><?= sanitize($deptName) ?> NAAC Hub</h1>
        </div>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">Criterion-wise Departmental Compliance & Documentation</p>
    </div>

    <!-- Quick Stats for Dept -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="glass-card p-6 border-l-4 border-indigo-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Publications (C3)</div>
            <div class="text-2xl font-black text-gray-800"><?= $totalRes ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">SSS Eligibility (C2)</div>
            <div class="text-2xl font-black text-gray-800">Ready</div>
        </div>
        <div class="glass-card p-6 border-l-4 border-purple-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Feedback (C1)</div>
            <div class="text-2xl font-black text-gray-800">Tracked</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Criterion 1: Curricular Aspects -->
        <div class="glass-card flex flex-col group">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-book"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion I</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Curricular Aspects</h4>
                <p class="text-xs text-gray-400 leading-relaxed">Departmental feedback collection, syllabus analysis, and review records.</p>
            </div>
            <a href="compliance.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-indigo-600 text-white text-center text-[10px] font-black uppercase tracking-widest rounded-b-2xl hover:bg-indigo-700 transition">
                Manage Compliance
            </a>
        </div>

        <!-- Criterion 2: Teaching-Learning & Evaluation -->
        <div class="glass-card flex flex-col group border-emerald-100 ring-2 ring-emerald-50">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-user-graduate"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion II</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Teaching-Learning</h4>
                <p class="text-xs text-gray-400 leading-relaxed">CO-PO Attainment, SSS participation, and departmental result analysis.</p>
            </div>
            <div class="grid grid-cols-2 gap-px bg-gray-100 border-t border-gray-100 rounded-b-2xl overflow-hidden">
                <a href="co_analysis.php" class="p-4 bg-white text-[9px] font-black text-gray-700 text-center uppercase tracking-widest hover:bg-emerald-50 hover:text-emerald-600 transition">CO-PO Hub</a>
                <a href="sss_management.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-white text-[9px] font-black text-gray-700 text-center uppercase tracking-widest hover:bg-emerald-50 hover:text-emerald-600 transition">SSS Stats</a>
            </div>
        </div>

        <!-- Criterion 3: Research -->
        <div class="glass-card flex flex-col group border-purple-100 ring-4 ring-purple-50/50">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-microscope"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion III</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Research & Extension</h4>
                <p class="text-xs text-gray-400 leading-relaxed">Publications, patents, and extension activities for <?= sanitize($deptName) ?>.</p>
            </div>
            <div class="grid grid-cols-2 gap-px bg-gray-100 border-t border-gray-100 rounded-b-2xl overflow-hidden">
                <a href="research.php" class="p-4 bg-white text-[9px] font-black text-gray-700 text-center uppercase tracking-widest hover:bg-purple-50 hover:text-purple-600 transition">Records</a>
                <a href="research_analysis.php" class="p-4 bg-white text-[9px] font-black text-gray-700 text-center uppercase tracking-widest hover:bg-purple-50 hover:text-purple-600 transition">Analytics</a>
            </div>
        </div>

        <!-- Criterion 4: Infrastructure -->
        <div class="glass-card flex flex-col group">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-laptop-code"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion IV</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Infrastructure</h4>
                <p class="text-xs text-gray-400 leading-relaxed">Departmental labs, IT resources, and library usage reports.</p>
            </div>
            <a href="infrastructure.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-amber-600 text-white text-center text-[10px] font-black uppercase tracking-widest rounded-b-2xl hover:bg-amber-700 transition">
                Lab & Resource Audit
            </a>
        </div>

        <!-- Criterion 5: Student Progression -->
        <div class="glass-card flex flex-col group border-rose-100 ring-2 ring-rose-50">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-running"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion V</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Student Progression</h4>
                <p class="text-xs text-gray-400 leading-relaxed">Placements, higher education data, and alumni networking.</p>
            </div>
            <a href="progression.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-rose-600 text-white text-center text-[10px] font-black uppercase tracking-widest rounded-b-2xl hover:bg-rose-700 transition">
                Track Progression
            </a>
        </div>

        <!-- Criterion 6: Governance -->
        <div class="glass-card flex flex-col group">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-users-cog"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion VI</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Governance</h4>
                <p class="text-xs text-gray-400 leading-relaxed">FDP participation, departmental meetings, and financial audit.</p>
            </div>
            <a href="governance.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-slate-700 text-white text-center text-[10px] font-black uppercase tracking-widest rounded-b-2xl hover:bg-slate-800 transition">
                Dept Governance
            </a>
        </div>

        <!-- Criterion 7: Best Practices -->
        <div class="glass-card flex flex-col group border-emerald-100 ring-2 ring-emerald-50">
            <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-award"></i>
                    </span>
                    <span class="text-[10px] font-black text-gray-300 uppercase">Criterion VII</span>
                </div>
                <h4 class="text-xl font-black text-gray-800 mb-2">Values & Habits</h4>
                <p class="text-xs text-gray-400 leading-relaxed">Institutional distinctiveness and best practice reports.</p>
            </div>
            <a href="best_practices.php<?= $deptId ? '?dept_id='.$deptId : '' ?>" class="p-4 bg-emerald-600 text-white text-center text-[10px] font-black uppercase tracking-widest rounded-b-2xl hover:bg-emerald-700 transition">
                Best Practices Hub
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
