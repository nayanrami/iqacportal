<?php
/**
 * Admin - NAAC Accreditation Hub
 * Central command center for NAAC Criteria management.
 */
$pageTitle = 'NAAC Accreditation Hub';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/header.php';

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? null;

// Mock counts or real counts for summary
$stats = getDashboardStats($pdo, $deptId);
$resRecords = $pdo->query("SELECT id FROM research_records" . ($deptId ? " WHERE department_id = $deptId" : ""))->fetchAll();
$totalRes = count($resRecords);
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black gradient-text mb-2">NAAC Accreditation Portal</h1>
        <p class="text-gray-500 text-sm font-medium tracking-wide uppercase">Institutional Quality Assurance & Compliance Hub</p>
    </div>

    <!-- Stats Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="glass-card p-6 border-l-4 border-indigo-500 flex items-center gap-5">
            <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">
                <i class="fas fa-file-contract"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Documentation Status</div>
                <div class="text-2xl font-black text-gray-800">In Progress</div>
            </div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500 flex items-center gap-5">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Research Volume</div>
                <div class="text-2xl font-black text-gray-800"><?= $totalRes ?> Entries</div>
            </div>
        </div>
        <div class="glass-card p-6 border-l-4 border-purple-500 flex items-center gap-5">
            <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Criterion Ready</div>
                <div class="text-2xl font-black text-gray-800">C2, C3, C7</div>
            </div>
        </div>
    </div>

    <!-- NAAC Criteria Grid -->
    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6"><i class="fas fa-sitemap mr-2"></i> Quality Assessment Criteria</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        
        <!-- Criterion 1 -->
        <div class="glass-card h-full flex flex-col group hover:border-indigo-200 transition-all duration-300">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[10px] font-black uppercase tracking-tighter">Criterion I</span>
                    <i class="fas fa-book-open text-gray-200 group-hover:text-indigo-500 transition-colors"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Curricular Aspects</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Feedback on curriculum, design & review system tracking.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[9px] font-black">Feedbacks</span>
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-500 rounded text-[9px] font-black">Syllabus</span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/admin/compliance.php" class="p-4 bg-gray-50 border-t border-gray-100 text-center text-[10px] font-bold text-indigo-600 uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all rounded-b-2xl">
                Open Compliance Tracker
            </a>
        </div>

        <!-- Criterion 2 -->
        <div class="glass-card h-full flex flex-col group border-indigo-100 ring-2 ring-indigo-50">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-indigo-600 text-white rounded-full text-[10px] font-black uppercase tracking-tighter shadow-lg shadow-indigo-100">Criterion II</span>
                    <i class="fas fa-user-graduate text-indigo-500"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Teaching-Learning</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Student satisfaction survey, CO-PO attainment analysis.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[9px] font-black">SSS Report</span>
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-500 rounded text-[9px] font-black">CO/PO Stats</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-px border-t border-gray-100 bg-gray-100 rounded-b-2xl overflow-hidden text-center">
                <a href="<?= APP_URL ?>/admin/co_analysis.php" class="p-4 bg-white text-[9px] font-bold text-gray-700 uppercase tracking-widest hover:bg-indigo-50 hover:text-indigo-600 transition-all">CO Analysis</a>
                <a href="<?= APP_URL ?>/admin/sss_management.php" class="p-4 bg-white text-[9px] font-bold text-gray-700 uppercase tracking-widest hover:bg-indigo-50 hover:text-indigo-600 transition-all">SSS Module</a>
            </div>
        </div>

        <!-- Criterion 3 -->
        <div class="glass-card h-full flex flex-col group border-purple-100 ring-2 ring-purple-50 scale-105 z-10">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-purple-600 text-white rounded-full text-[10px] font-black uppercase tracking-tighter shadow-lg shadow-purple-100">Criterion III</span>
                    <div class="flex items-center gap-1 text-[8px] font-black text-purple-600 uppercase animate-pulse">
                        <i class="fas fa-circle text-[6px]"></i> Live
                    </div>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Research & Extension</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Publications, grants, patents, and faculty research metrics.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-500 rounded text-[9px] font-black">Scopus/UGC</span>
                    <span class="px-2 py-0.5 bg-rose-50 text-rose-500 rounded text-[9px] font-black">Patents</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-px border-t border-gray-100 bg-gray-100 rounded-b-2xl overflow-hidden text-center">
                <a href="<?= APP_URL ?>/admin/research.php" class="p-4 bg-white text-[9px] font-bold text-gray-700 uppercase tracking-widest hover:bg-purple-50 hover:text-purple-600 transition-all">Manage Records</a>
                <a href="<?= APP_URL ?>/admin/research_analysis.php" class="p-4 bg-white text-[9px] font-bold text-gray-700 uppercase tracking-widest hover:bg-purple-50 hover:text-purple-600 transition-all">Deep Analysis</a>
            </div>
        </div>

        <!-- Criterion 4 -->
        <div class="glass-card h-full flex flex-col group hover:border-amber-200 transition-all duration-300">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[10px] font-black uppercase tracking-tighter">Criterion IV</span>
                    <i class="fas fa-building text-gray-200 group-hover:text-amber-500 transition-colors"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Infrastructure</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Library, IT infrastructure, and maintenance statistics.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-500 rounded text-[9px] font-black">IT Resources</span>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-500 rounded text-[9px] font-black">Library</span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/admin/infrastructure.php" class="p-4 bg-gray-50 border-t border-gray-100 text-center text-[10px] font-bold text-amber-600 uppercase tracking-widest hover:bg-amber-600 hover:text-white transition-all rounded-b-2xl">
                Open Analytics
            </a>
        </div>

        <!-- Criterion 5 -->
        <div class="glass-card h-full flex flex-col group hover:border-rose-200 transition-all duration-300">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[10px] font-black uppercase tracking-tighter">Criterion V</span>
                    <i class="fas fa-user-shield text-gray-200 group-hover:text-rose-500 transition-colors"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Student Progression</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Placement data, higher education tracking, and alumni support.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-rose-50 text-rose-500 rounded text-[9px] font-black">Placements</span>
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[9px] font-black">Alumni</span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/admin/progression.php" class="p-4 bg-gray-50 border-t border-gray-100 text-center text-[10px] font-bold text-rose-600 uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all rounded-b-2xl">
                Manage Progression
            </a>
        </div>

        <!-- Criterion 6 -->
        <div class="glass-card h-full flex flex-col group hover:border-emerald-200 transition-all duration-300">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[10px] font-black uppercase tracking-tighter">Criterion VI</span>
                    <i class="fas fa-landmark text-gray-200 group-hover:text-emerald-500 transition-colors"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Governance & Management</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Institutional vision, strategy, and portal setup hub.</p>
            </div>
            <?php if ($currentRole === 'superadmin'): ?>
            <a href="<?= APP_URL ?>/admin/governance.php" class="p-4 bg-gray-50 border-t border-gray-100 text-center text-[10px] font-bold text-emerald-600 uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all rounded-b-2xl">
                System Governance
            </a>
            <?php else: ?>
            <div class="p-4 bg-gray-50 border-t border-gray-100 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest rounded-b-2xl">
                Restricted Access
            </div>
            <?php endif; ?>
        </div>

        <!-- Criterion 7 -->
        <div class="glass-card h-full flex flex-col group border-emerald-100 ring-2 ring-emerald-50 scale-105 z-10 shadow-xl">
            <div class="p-6 flex-grow">
                <div class="flex items-center justify-between mb-6">
                    <span class="px-3 py-1 bg-emerald-600 text-white rounded-full text-[10px] font-black uppercase tracking-tighter shadow-lg shadow-emerald-100">Criterion VII</span>
                    <i class="fas fa-hand-holding-heart text-emerald-500"></i>
                </div>
                <h4 class="text-lg font-black text-gray-800 mb-2">Values & Best Practices</h4>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">Institutional distinctiveness and best practice reports.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-500 rounded text-[9px] font-black">Distinctiveness</span>
                    <span class="px-2 py-0.5 bg-rose-50 text-rose-500 rounded text-[9px] font-black">Social Impact</span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/admin/best_practices.php" class="p-4 bg-emerald-600 text-white text-center text-[10px] font-bold uppercase tracking-widest hover:bg-emerald-700 transition-all rounded-b-2xl">
                Open documentation
            </a>
        </div>

        <!-- Add Criterion -->
        <div class="border-2 border-dashed border-gray-200 rounded-2xl flex items-center justify-center p-8 opacity-50 hover:opacity-100 hover:border-indigo-400 transition cursor-pointer group">
            <div class="text-center">
                <div class="w-12 h-12 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center text-xl mb-3 mx-auto group-hover:bg-indigo-50 group-hover:text-indigo-500 transition">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">New Accreditation Area</div>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
