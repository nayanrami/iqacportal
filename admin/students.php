<?php
require_once __DIR__ . '/../functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Single Student addition
if (isset($_POST['add_student'])) {
    $enroll = trim($_POST['enrollment_no']);
    $name = trim($_POST['name']);
    $sem = intval($_POST['semester']);
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($enroll && $name && $sem && $targetDeptId) {
        $pass = password_hash($enroll, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO students (enrollment_no, name, password, department_id, semester) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = ?, semester = ?");
        $stmt->execute([$enroll, $name, $pass, $targetDeptId, $sem, $name, $sem]);
        setFlash('success', "Student record updated/added: $name ($enroll)");
    } else {
        setFlash('danger', "All fields are required.");
    }
    redirect(APP_URL . '/admin/students.php');
}

// Bulk Promotion
if (isset($_POST['promote'])) {
    $fromSem = intval($_POST['from_semester']);
    $toSem = intval($_POST['to_semester']);
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);
    
    if ($fromSem && $toSem && $targetDeptId) {
        $stmt = $pdo->prepare("UPDATE students SET semester = ? WHERE semester = ? AND department_id = ?");
        $stmt->execute([$toSem, $fromSem, $targetDeptId]);
        setFlash('success', "Students promoted from Semester $fromSem to $toSem.");
    } else {
        setFlash('danger', "Please select both semesters and department.");
    }
    redirect(APP_URL . '/admin/students.php');
}

// Bulk Upload (CSV: enrollment_no, name, semester)
if (isset($_POST['upload'])) {
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);
    if ($targetDeptId && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
        $count = 0;
        $pdo->beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($count === 0 && strtolower($data[0]) === 'enrollment_no') { $count++; continue; } // Header
                $enroll = trim($data[0]);
                $name = trim($data[1]);
                $sem = intval($data[2]);
                
                if ($enroll && $name && $sem) {
                    $pass = password_hash($enroll, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO students (enrollment_no, name, password, department_id, semester) 
                                         VALUES (?, ?, ?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE name = ?, semester = ?");
                    $stmt->execute([$enroll, $name, $pass, $targetDeptId, $sem, $name, $sem]);
                    $count++;
                }
            }
            $pdo->commit();
            setFlash('success', "Successfully processed " . ($count-1) . " student records.");
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', "Error: " . $e->getMessage());
        }
        fclose($handle);
    } else {
        setFlash('danger', "Please select a valid CSV file and department.");
    }
    redirect(APP_URL . '/admin/students.php');
}

// Delete student
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT id FROM students WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
        setFlash('success', 'Student record deleted.');
    }
    redirect(APP_URL . '/admin/students.php');
}

require_once __DIR__ . '/header.php';

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

$studentWhere = $deptId ? " WHERE s.department_id = $deptId" : "";
try {
    $students = $pdo->query("
        SELECT s.*, d.name as dept_name 
        FROM students s 
        JOIN departments d ON d.id = s.department_id 
        $studentWhere 
        ORDER BY s.semester, s.enrollment_no
    ")->fetchAll();
} catch (Exception $e) {
    $students = [];
}
if (!$students) $students = [];
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Student Management</h1>
            <p class="text-gray-500 text-sm">Upload, promote, and manage student enrollment records.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openModal('addModal')" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Add Student
            </button>
            <button onclick="openModal('uploadModal')" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-file-upload"></i> Bulk Upload
            </button>
            <button onclick="openModal('promoteModal')" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-bold shadow-lg shadow-purple-200 hover:bg-purple-700 transition flex items-center gap-2">
                <i class="fas fa-graduation-cap"></i> Bulk Promote
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="glass-card p-6 border-l-4 border-indigo-500">
            <div class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Total Students</div>
            <div class="text-3xl font-black text-gray-800"><?= count($students) ?></div>
        </div>
        <!-- Add more stats if needed -->
    </div>

    <!-- Student Table -->
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Enrollment No</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Department</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Semester</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($students as $s): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-bold text-gray-700"><?= $s['enrollment_no'] ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= $s['name'] ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <?= $s['dept_name'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">Sem <?= $s['semester'] ?></td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="confirmDelete('?delete=<?= $s['id'] ?>', '<?= $s['name'] ?>')" class="text-gray-300 hover:text-rose-500 transition p-2">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Student Modal -->
<div id="addModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-gray-800">Add New Student</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Full Name</label>
                <input type="text" name="name" required placeholder="Full Name" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Enrollment Number</label>
                <input type="text" name="enrollment_no" required placeholder="Enrollment No" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Semester</label>
                    <input type="number" name="semester" required min="1" max="8" value="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Department</label>
                    <select name="department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($deptId == $d['id'] ? 'selected' : '') ?>><?= $d['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 italic mt-2">* Password will be set to enrollment number by default.</p>
            <button type="submit" name="add_student" class="w-full py-4 bg-emerald-600 text-white font-black rounded-xl shadow-lg shadow-emerald-100 mt-2">
                Save Student Record
            </button>
        </form>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-gray-800">Bulk Upload Students</h3>
            <button onclick="closeModal('uploadModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Department</label>
                <select name="department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">CSV File (enrollment_no, name, semester)</label>
                <input type="file" name="csv_file" accept=".csv" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
            </div>
            <button type="submit" name="upload" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-100 mt-2">
                Start Processing
            </button>
        </form>
    </div>
</div>

<!-- Promote Modal -->
<div id="promoteModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-gray-800">Bulk Semester Promote</h3>
            <button onclick="closeModal('promoteModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Department</label>
                <select name="department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">From Semester</label>
                    <input type="number" name="from_semester" required min="1" max="8" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">To Semester</label>
                    <input type="number" name="to_semester" required min="1" max="8" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
            </div>
            <button type="submit" name="promote" class="w-full py-4 bg-purple-600 text-white font-black rounded-xl shadow-lg shadow-purple-100 mt-2">
                Promote All Students
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
