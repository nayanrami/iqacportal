<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Handle Mapping Actions
if (isset($_POST['add_mapping'])) {
    $facultyId = intval($_POST['faculty_id']);
    $courseId = intval($_POST['course_id']);
    $acadYear = trim($_POST['academic_year']);
    $sem = intval($_POST['semester']);
    $role = trim($_POST['role'] ?? 'Primary');

    if ($facultyId && $courseId && $acadYear) {
        $stmt = $pdo->prepare("INSERT INTO faculty_course_mapping (faculty_id, course_id, academic_year, semester, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$facultyId, $courseId, $acadYear, $sem, $role]);
        setFlash('success', "Course assignment saved successfully.");
    }
    redirect(APP_URL . '/admin/mapping.php' . (isset($_GET['faculty_id']) ? '?faculty_id='.$facultyId : ''));
}

if (isset($_GET['delete'])) {
    $mid = intval($_GET['delete']);
    $pdo->exec("DELETE FROM faculty_course_mapping WHERE id = $mid");
    setFlash('success', "Assignment removed.");
    redirect(APP_URL . '/admin/mapping.php');
}

// Fetch Logic
$filterFaculty = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : null;
$filterDept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : $deptId;

$allDepartments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$departments = $allDepartments;

// Pick ALL faculties for assignment (Cross-department mapping)
$allFaculties = $pdo->query("SELECT * FROM faculties ORDER BY name")->fetchAll();

// Pick only department faculties for the Filter Dropdown
$faculties = $pdo->prepare("SELECT * FROM faculties WHERE (department_id = ? OR ? IS NULL) ORDER BY name");
$faculties->execute([$filterDept, $filterDept]);
$faculties = $faculties->fetchAll();

// Pick only department courses for assignment
$courses = $pdo->prepare("SELECT * FROM courses WHERE (department_id = ? OR ? IS NULL) ORDER BY semester, name");
$courses->execute([$filterDept, $filterDept]);
$courses = $courses->fetchAll();

// Mapping Data - Filter by Course's Department for Dept Admin
$where = "1=1";
if ($filterFaculty) $where .= " AND m.faculty_id = $filterFaculty";
if ($isDeptAdmin) {
    $where .= " AND c.department_id = $deptId";
} elseif ($filterDept) {
    $where .= " AND c.department_id = $filterDept";
}

$mappings = $pdo->query("
    SELECT m.*, f.name as fac_name, c.name as course_name, c.code as course_code, d.name as dept_name
    FROM faculty_course_mapping m
    JOIN faculties f ON f.id = m.faculty_id
    JOIN courses c ON c.id = m.course_id
    JOIN departments d ON d.id = f.department_id
    WHERE $where
    ORDER BY f.name, m.academic_year DESC
")->fetchAll();

$pageTitle = "Course Mapping";
require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Academic Mapping</h1>
            <p class="text-gray-500 text-sm">Assign faculty members to specific courses and laboratories.</p>
        </div>
        <button onclick="openModal('addMappingModal')" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center gap-2">
            <i class="fas fa-link"></i> New Assignment
        </button>
    </div>

    <!-- Mapping Table -->
    <div class="glass-card overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-wrap gap-4 items-center justify-between">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest leading-none">Mapping Registry</h3>
            <form method="GET" class="flex gap-2">
                <?php if (!$isDeptAdmin): ?>
                <select name="dept_id" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-bold outline-none">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= $d['code'] ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <select name="faculty_id" onchange="this.form.submit()" class="px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-lg text-[10px] font-bold outline-none">
                    <option value="">All Faculty</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $filterFaculty == $f['id'] ? 'selected' : '' ?>><?= $f['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Faculty Name</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Course Assigned</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Semester</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Academic Year</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($mappings as $m): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="text-sm font-black text-gray-800"><?= $m['fac_name'] ?></div>
                            <div class="text-[10px] text-indigo-500 font-bold"><?= $m['dept_name'] ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-700"><?= $m['course_name'] ?></div>
                            <div class="text-[10px] text-gray-400 font-mono"><?= $m['course_code'] ?> (<?= $m['role'] ?>)</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-[10px] font-black">SEM <?= $m['semester'] ?: 'N/A' ?></span>
                        </td>
                        <td class="px-6 py-4 text-center font-mono text-xs text-indigo-600 font-black">
                            <?= $m['academic_year'] ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="confirmDeleteMapping('?delete=<?= $m['id'] ?>')" class="text-gray-300 hover:text-rose-500 transition p-2">
                                <i class="fas fa-unlink text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($mappings)): ?>
                <div class="py-12 text-center text-gray-400 italic">No course assignments found.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add Mapping Modal -->
<div id="addMappingModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-gray-800">Assign Faculty to Course</h3>
            <button onclick="closeModal('addMappingModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Faculty Member (All Institutional Staff)</label>
                <select name="faculty_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <?php 
                    $deptNames = array_column($allDepartments, 'code', 'id');
                    foreach ($allFaculties as $f): 
                    ?>
                        <option value="<?= $f['id'] ?>" <?= $filterFaculty == $f['id'] ? 'selected' : '' ?>><?= $f['name'] ?> (<?= $deptNames[$f['department_id']] ?? 'N/A' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Course</label>
                <select name="course_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['code'] ?> - <?= $c['name'] ?> (Sem <?= $c['semester'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Academic Year</label>
                    <input type="text" name="academic_year" value="2025-26" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Semester</label>
                    <input type="number" name="semester" min="1" max="8" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
            </div>
            <button type="submit" name="add_mapping" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-100 mt-2">
                Confirm Assignment
            </button>
        </form>
    </div>
</div>

<script>
function confirmDeleteMapping(url) {
    if (confirm('Are you sure you want to remove this faculty-course assignment?')) {
        window.location.href = url;
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
