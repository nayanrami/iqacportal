<?php
/**
 * Admin - NAAC SSS Management (Criterion 2.7)
 */
$pageTitle = 'SSS Management (Criterion 2.7)';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

$where = $deptId ? " WHERE s.department_id = $deptId" : "";
$students = $pdo->query("
    SELECT s.*, d.name as dept_name,
           (SELECT COUNT(*) FROM responses r WHERE r.student_id = s.id) as response_count
    FROM students s
    JOIN departments d ON d.id = s.department_id
    $where
    ORDER BY s.enrollment_no
")->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black text-gray-800 mb-2">Student Satisfaction Survey</h1>
            <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">Compliance Management (Criterion 2.7)</p>
        </div>
        
        <div class="flex items-center gap-3">
            <select onchange="window.location.href='?dept_id=' + this.value" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-black uppercase tracking-widest focus:border-indigo-500 outline-none transition shadow-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= $d['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                <i class="fas fa-file-export mr-2"></i> Export NAAC File
            </button>
        </div>
    </div>

    <!-- SSS Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="glass-card p-6 border-l-4 border-indigo-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Students</div>
            <div class="text-3xl font-black text-gray-800"><?= count($students) ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">SSS Participated</div>
            <?php 
                $participated = count(array_filter($students, fn($s) => $s['response_count'] > 0)); 
                $percent = count($students) > 0 ? round(($participated / count($students)) * 100, 1) : 0;
            ?>
            <div class="text-3xl font-black text-gray-800"><?= $participated ?></div>
            <div class="text-[9px] font-bold text-emerald-600 mt-1"><?= $percent ?>% Participation Rate</div>
        </div>
        <div class="glass-card p-6 border-l-4 border-amber-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Email Verified</div>
            <?php $emailCount = count(array_filter($students, fn($s) => !empty($s['email']))); ?>
            <div class="text-3xl font-black text-gray-800"><?= $emailCount ?></div>
            <div class="text-[9px] font-bold text-gray-400 mt-1">Ready for SSS Data Upload</div>
        </div>
    </div>

    <!-- Student List Table -->
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">SSS Eligibility Registry</h3>
            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[9px] font-black uppercase tracking-tighter">Criterion 2.7.1</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white border-b border-gray-100">
                    <tr class="text-[9px] font-black text-gray-400 uppercase tracking-widest px-6">
                        <th class="px-6 py-4">Enrollment</th>
                        <th class="px-6 py-4">Student Name</th>
                        <th class="px-6 py-4">Email / Mobile</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    <?php foreach ($students as $s): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-black text-gray-700"><?= $s['enrollment_no'] ?></td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800"><?= sanitize($s['name']) ?></div>
                            <div class="text-[10px] text-gray-400 uppercase font-black"><?= $s['dept_name'] ?> • Sem <?= $s['semester'] ?></div>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-600">
                            <div><?= sanitize($s['email'] ?: 'Missing Email') ?></div>
                            <div class="text-[10px] text-gray-400"><?= sanitize($s['mobile'] ?: 'No Mobile') ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($s['response_count'] > 0): ?>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-[9px] font-black uppercase tracking-widest"><i class="fas fa-check-circle mr-1"></i> Participated</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-500 rounded text-[9px] font-black uppercase tracking-widest"><i class="fas fa-clock mr-1"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-500"><?= $s['category'] ?: 'General' ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="students.php?id=<?= $s['id'] ?>" class="p-2 text-indigo-400 hover:text-indigo-600 transition"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
