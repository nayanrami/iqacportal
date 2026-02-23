<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Single Student addition
if (isset($_POST['add_student'])) {
    $enroll = trim($_POST['enrollment_no']);
    $name = trim($_POST['name']);
    $sem = intval($_POST['semester']);
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $category = trim($_POST['category'] ?? '');
    $division = trim($_POST['division'] ?? '');
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($enroll && $name && $sem && $targetDeptId) {
        $pass = password_hash($enroll, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO students (enrollment_no, name, password, department_id, semester, division, email, mobile, gender, category) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE name = ?, semester = ?, division = ?, email = ?, mobile = ?, gender = ?, category = ?");
        $stmt->execute([$enroll, $name, $pass, $targetDeptId, $sem, $division, $email, $mobile, $gender, $category, $name, $sem, $division, $email, $mobile, $gender, $category]);
        setFlash('success', "Student record updated/added: $name ($enroll)");
    } else {
        setFlash('danger', "All fields marked * are required.");
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
                    $email = trim($data[3] ?? '');
                    $mobile = trim($data[4] ?? '');
                    $gender = trim($data[5] ?? 'Male');
                    $category = trim($data[6] ?? 'General');

                    $pass = password_hash($enroll, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO students (enrollment_no, name, password, department_id, semester, email, mobile, gender, category) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE name = ?, semester = ?, email = ?, mobile = ?, gender = ?, category = ?");
                    $stmt->execute([$enroll, $name, $pass, $targetDeptId, $sem, $email, $mobile, $gender, $category, $name, $sem, $email, $mobile, $gender, $category]);
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

// For dropdowns in modals: Super Admins see all, Dept Admins see theirs
$allDepartments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

// Fallback if session ID is stale (dept exists but ID changed)
if ($isDeptAdmin && empty($departments)) {
    // Attempt to recover by name if possible, or just show all if recovery fails
    // For now, let's just make sure $departments isn't empty so the UI doesn't break
    $departments = $allDepartments; 
}

// ── Filtering Logic ──
$filterSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$filterDept = $deptId ?: (isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? intval($_GET['dept_id']) : null);
$search = trim($_GET['search'] ?? '');
$isFiltered = ($filterSem !== null) || ($filterDept !== null) || ($search !== '');

if ($isDeptAdmin) {
    $filterDept = $deptId;
    // $isFiltered = true; // Removed to enforce filter-first policy
}

$whereQuery = ["1=1"];
$params = [];

if ($filterSem) {
    $whereQuery[] = "s.semester = ?";
    $params[] = $filterSem;
}
if ($filterDept) {
    $whereQuery[] = "s.department_id = ?";
    $params[] = $filterDept;
}
if ($search) {
    $whereQuery[] = "(s.name LIKE ? OR s.enrollment_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereString = implode(" AND ", $whereQuery);

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$students = [];

if ($isFiltered) {
    try {
        // Count total for pagination
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE $whereString");
        $totalStmt->execute($params);
        $totalRecords = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated records
        $stmt = $pdo->prepare("
            SELECT s.*, d.name as dept_name 
            FROM students s 
            JOIN departments d ON d.id = s.department_id 
            WHERE $whereString
            ORDER BY s.semester, s.enrollment_no
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (Exception $e) {
        $students = [];
    }
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
            <a href="sss_management.php" class="px-5 py-2.5 bg-gray-600 text-white rounded-xl font-bold hover:bg-gray-700 transition flex items-center gap-2">
                <i class="fas fa-certificate"></i> SSS Compliance
            </a>
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

    <!-- Dynamic Filters -->
    <div class="glass-card p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search Enrollment / Name" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
            </div>
            
            <select name="sem" class="px-4 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                <option value="">All Semesters</option>
                <?php for($i=1;$i<=8;$i++): ?>
                    <option value="<?= $i ?>" <?= $filterSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>

            <?php if (!$isDeptAdmin): ?>
            <select name="dept_id" class="px-4 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['code']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
                <div class="px-4 py-2 bg-gray-100 border border-gray-100 rounded-lg text-xs text-gray-400 font-bold uppercase flex items-center gap-2">
                    <i class="fas fa-lock"></i> <?= !empty($departments) ? sanitize($departments[0]['code']) : 'No Dept' ?> Only
                </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Filter</button>
                <a href="students.php" class="px-4 py-2 bg-gray-100 text-gray-500 rounded-lg hover:bg-gray-200 transition"><i class="fas fa-sync-alt text-xs"></i></a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6 border-l-4 border-indigo-500">
            <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Result</div>
            <div class="text-2xl font-black text-gray-800"><?= $totalRecords ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500">
            <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">SSS Eligible</div>
            <div class="text-2xl font-black text-emerald-600"><?= count(array_filter($students, fn($s) => !empty($s['email']))) ?></div>
        </div>
    </div>

    <!-- Student Table -->
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Sr. No.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">SSS</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Enrollment No</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Name / Profile</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Sem / Div</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Contact Info</th>
                        <?php if (!$isDeptAdmin): ?>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Department</th>
                        <?php endif; ?>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (!$isFiltered): ?>
                    <tr>
                        <td colspan="<?= $isDeptAdmin ? 6 : 7 ?>" class="px-6 py-20 text-center">
                            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-filter text-2xl text-indigo-400"></i>
                            </div>
                            <h3 class="text-lg font-black text-gray-800">Please apply a filter to view records</h3>
                            <p class="text-gray-400 text-xs mt-1">Select a semester, department or search to begin.</p>
                        </td>
                    </tr>
                    <?php elseif (empty($students)): ?>
                    <tr>
                        <td colspan="<?= $isDeptAdmin ? 6 : 7 ?>" class="px-6 py-20 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users-slash text-2xl text-gray-200"></i>
                            </div>
                            <h3 class="text-lg font-black text-gray-400">No records found</h3>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $srNo = $offset + 1;
                        foreach ($students as $s): ?>
                        <tr class="hover:bg-gray-50/50 transition border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4 text-center text-xs font-black text-gray-400">
                                <?= $srNo++ ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($s['email'] && $s['mobile']): ?>
                                    <i class="fas fa-check-circle text-emerald-500" title="SSS Verified"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-amber-400" title="Missing Info"></i>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-black text-indigo-600 bg-indigo-50/30"><?= $s['enrollment_no'] ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-gray-800"><?= $s['name'] ?></div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter"><?= $s['category'] ?: 'General' ?></span>
                                    <span class="w-1 h-1 rounded-full bg-gray-200"></span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter"><?= $s['gender'] ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col gap-1 items-center">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-[9px] font-black">SEM <?= $s['semester'] ?></span>
                                    <?php if ($s['division']): ?>
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-black"><?= $s['division'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="text-[10px] text-gray-600 flex items-center gap-1.5">
                                        <i class="fas fa-envelope text-gray-300 w-3"></i> <?= $s['email'] ?: '<span class="text-gray-300">N/A</span>' ?>
                                    </div>
                                    <div class="text-[10px] text-gray-600 flex items-center gap-1.5">
                                        <i class="fas fa-phone text-gray-300 w-3"></i> <?= $s['mobile'] ?: '<span class="text-gray-300">N/A</span>' ?>
                                    </div>
                                </div>
                            </td>
                            <?php if (!$isDeptAdmin): ?>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                                    <?= $s['dept_name'] ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 text-right">
                                <button onclick="confirmDelete('?delete=<?= $s['id'] ?>', '<?= $s['name'] ?>')" class="text-gray-300 hover:text-rose-500 transition p-2">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($isFiltered && $totalRecords > 0): ?>
    <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> Results
            </div>
            <form method="GET" class="flex items-center gap-2">
                <?php foreach($_GET as $k => $v): if($k != 'limit' && $k != 'page'): ?>
                <input type="hidden" name="<?= sanitize($k) ?>" value="<?= sanitize($v) ?>">
                <?php endif; endforeach; ?>
                <select name="limit" onchange="this.form.submit()" class="px-2 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold text-gray-500 outline-none focus:border-indigo-500 transition">
                    <?php foreach([10, 20, 50, 100] as $l): ?>
                        <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?> per page</option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex items-center gap-1">
            <?php 
            $queryParams = $_GET;
            unset($queryParams['page']);
            $baseLink = '?' . http_build_query($queryParams) . '&page=';
            ?>
            
            <?php if ($page > 1): ?>
                <a href="<?= $baseLink . ($page - 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-left text-[10px]"></i></a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $startPage + 4);
            $startPage = max(1, $endPage - 4);
            
            for ($i = $startPage; $i <= $endPage; $i++): 
                if($i < 1) continue;
            ?>
                <a href="<?= $baseLink . $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-black transition shadow-sm <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-200' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseLink . ($page + 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-right text-[10px]"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
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
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Enrollment Number *</label>
                <input type="text" name="enrollment_no" required placeholder="Enrollment No" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Email for NAAC SSS</label>
                    <input type="email" name="email" placeholder="student@example.com" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Mobile Number</label>
                    <input type="text" name="mobile" placeholder="9876543210" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Gender</label>
                    <select name="gender" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category (General/SC/OBC)</label>
                    <input type="text" name="category" placeholder="General" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Semester</label>
                    <input type="number" name="semester" required min="1" max="8" value="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Division (e.g. IT-B)</label>
                    <input type="text" name="division" placeholder="Division" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Department</label>
                <select name="department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-emerald-500 transition">
                    <?php 
                    $addDepts = $isDeptAdmin ? $departments : $allDepartments;
                    foreach ($addDepts as $d): 
                    ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
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
                    <?php 
                    $modalDepts = $isDeptAdmin ? $departments : $allDepartments;
                    foreach ($modalDepts as $d): 
                    ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= $d['name'] ?></option>
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
                    <?php 
                    $modalDepts = $isDeptAdmin ? $departments : $allDepartments;
                    foreach ($modalDepts as $d): 
                    ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= $d['name'] ?></option>
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
