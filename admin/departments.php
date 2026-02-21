<?php
/**
 * Admin - Departments Management
 */
$pageTitle = 'Departments';
require_once __DIR__ . '/../functions.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([intval($_GET['delete'])]);
    setFlash('success', 'Department deleted.');
    redirect(APP_URL . '/admin/departments.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if ($name && $code) {
        $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)")->execute([$name, $code]);
        setFlash('success', 'Department created.');
    }
    redirect(APP_URL . '/admin/departments.php');
}

require_once __DIR__ . '/header.php';

$departments = $pdo->query("
    SELECT d.*,
           (SELECT COUNT(*) FROM courses WHERE department_id = d.id) as course_count,
           (SELECT COUNT(*) FROM feedback_forms WHERE department_id = d.id) as form_count,
           (SELECT COUNT(*) FROM program_outcomes WHERE department_id = d.id) as po_count
    FROM departments d ORDER BY d.name
")->fetchAll();
?>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm animate-slide-down">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-plus-circle text-emerald-500 mr-2"></i>Add Department</h3>
    </div>
    <form method="POST" class="p-6">
        <div class="flex gap-4">
            <input type="text" name="name" placeholder="Department Name *" required class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            <input type="text" name="code" placeholder="Code (e.g. IT) *" required class="w-40 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            <button type="submit" class="btn-primary text-xs"><i class="fas fa-plus"></i> Add</button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($departments as $d): ?>
        <div class="glass-card overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-lg font-bold text-gray-800"><?= sanitize($d['name']) ?></div>
                        <div class="text-xs text-gray-400 font-mono"><?= sanitize($d['code']) ?></div>
                    </div>
                    <a href="?delete=<?= $d['id'] ?>" onclick="return confirm('Delete department and all associated data?')" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></a>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <div class="text-lg font-bold text-blue-600"><?= $d['course_count'] ?></div>
                        <div class="text-[10px] text-gray-400">Courses</div>
                    </div>
                    <div class="p-2 bg-violet-50 rounded-lg">
                        <div class="text-lg font-bold text-violet-600"><?= $d['form_count'] ?></div>
                        <div class="text-[10px] text-gray-400">Forms</div>
                    </div>
                    <div class="p-2 bg-amber-50 rounded-lg">
                        <div class="text-lg font-bold text-amber-600"><?= $d['po_count'] ?></div>
                        <div class="text-[10px] text-gray-400">POs</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
