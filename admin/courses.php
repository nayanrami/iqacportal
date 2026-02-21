<?php
/**
 * Admin - Courses Management
 */
$pageTitle = 'Courses';
require_once __DIR__ . '/../functions.php';
requireLogin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT id FROM courses WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
        setFlash('success', 'Course deleted.');
    } else {
        setFlash('danger', 'Unauthorized access.');
    }
    redirect(APP_URL . '/admin/courses.php');
}

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $selectedDeptId = intval($_POST['department_id'] ?? 0);
    $finalDeptId = $isDeptAdmin ? $deptId : $selectedDeptId; // Force dept for coords
    $semester = intval($_POST['semester'] ?? 0) ?: null;

    if ($name && $code && $finalDeptId) {
        $pdo->prepare("INSERT INTO courses (department_id, name, code, semester) VALUES (?,?,?,?)")
            ->execute([$finalDeptId, $name, $code, $semester]);
        setFlash('success', 'Course created successfully.');
    } else {
        setFlash('danger', 'Name and code are required.');
    }
    redirect(APP_URL . '/admin/courses.php');
}

require_once __DIR__ . '/header.php';

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

$courseWhere = $deptId ? " WHERE c.department_id = $deptId" : "";
$courses = $pdo->query("
    SELECT c.*, d.name as dept_name, d.code as dept_code,
           (SELECT COUNT(*) FROM course_outcomes WHERE course_id = c.id) as co_count,
           (SELECT COUNT(*) FROM feedback_forms WHERE course_id = c.id) as form_count
    FROM courses c
    JOIN departments d ON d.id = c.department_id
    $courseWhere
    ORDER BY c.semester, c.code
")->fetchAll();
?>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm animate-slide-down">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-plus-circle text-blue-500 mr-2"></i>Add Course</h3>
    </div>
    <form method="POST" class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <input type="text" name="name" placeholder="Course Name *" required class="col-span-2 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            <input type="text" name="code" placeholder="Code *" required class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            <select name="department_id" required class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none">
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= sanitize($d['code']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="semester" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none">
                <option value="">Sem</option>
                <?php for ($i=1;$i<=8;$i++): ?><option value="<?=$i?>">Sem <?=$i?></option><?php endfor; ?>
            </select>
            <button type="submit" class="btn-primary text-xs justify-center"><i class="fas fa-plus"></i> Add</button>
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-book text-emerald-500 mr-2"></i>All Courses (<?= count($courses) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Name</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Sem</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase">Type</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">COs</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Forms</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Act</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($courses as $c): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-600"><?= sanitize($c['code']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?= sanitize($c['name']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500"><?= $c['semester'] ?: '—' ?></td>
                        <td class="px-4 py-3"><span class="text-xs text-gray-500"><?= sanitize($c['course_type'] ?? '') ?></span></td>
                        <td class="px-4 py-3 text-center"><span class="outcome-badge co"><?= $c['co_count'] ?></span></td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-600"><?= $c['form_count'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Delete?')" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
