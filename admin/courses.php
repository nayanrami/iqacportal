<?php
/**
 * Admin - Courses Management
 */
$pageTitle = 'Courses';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

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

// Fetch Filters
$filterDept = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? intval($_GET['dept_id']) : null;
$filterSem = isset($_GET['sem']) && $_GET['sem'] !== '' ? intval($_GET['sem']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$isFiltered = ($filterDept !== null) || ($filterSem !== null) || ($search !== '');

if ($isDeptAdmin) {
    $filterDept = $deptId;
    // $isFiltered = true; // Removed to enforce filter-first policy
}

require_once __DIR__ . '/header.php';

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$courses = [];

if ($isFiltered) {
    $whereQuery = ["1=1"];
    $params = [];
    
    if ($filterDept) {
        $whereQuery[] = "c.department_id = ?";
        $params[] = $filterDept;
    }
    if ($filterSem) {
        $whereQuery[] = "c.semester = ?";
        $params[] = $filterSem;
    }
    if ($search) {
        $whereQuery[] = "(c.name LIKE ? OR c.code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereString = implode(" AND ", $whereQuery);

    try {
        // Count total
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $whereString");
        $totalStmt->execute($params);
        $totalRecords = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated
        $stmt = $pdo->prepare("
            SELECT c.*, d.name as dept_name, d.code as dept_code,
                (SELECT COUNT(*) FROM course_outcomes WHERE course_id = c.id) as co_count,
                (SELECT COUNT(*) FROM feedback_forms WHERE course_id = c.id) as form_count
            FROM courses c
            JOIN departments d ON d.id = c.department_id
            WHERE $whereString
            ORDER BY c.semester, c.code
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $courses = $stmt->fetchAll();
    } catch (Exception $e) {
        $courses = [];
    }
}
?>

<!-- Action Bar & Filter Section -->
<div class="flex flex-col md:flex-row gap-4 mb-6">
    <!-- Filter Card -->
    <div class="flex-grow bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="font-bold text-xs text-gray-700"><i class="fas fa-filter text-indigo-500 mr-2"></i>Filter Course List</h3>
            <?php if ($isFiltered): ?>
                <a href="courses.php" class="text-[10px] text-gray-400 hover:text-indigo-600 font-bold uppercase tracking-widest"><i class="fas fa-times-circle mr-1"></i> Clear</a>
            <?php endif; ?>
        </div>
        <form method="GET" class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php if (!$isDeptAdmin): ?>
                <div>
                    <select name="dept_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['code']) ?> - <?= sanitize($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <select name="sem" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                        <option value="">All Semesters</option>
                        <?php for ($i=1; $i<=8; $i++): ?>
                            <option value="<?= $i ?>" <?= $filterSem == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search code or name..." class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                </div>

                <div>
                    <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-700 transition">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="md:w-64 bg-indigo-600 rounded-2xl p-6 shadow-lg shadow-indigo-100 flex flex-col justify-center items-center text-center">
        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mb-3">
            <i class="fas fa-plus text-white text-xl"></i>
        </div>
        <h4 class="text-white font-bold text-sm mb-1">New Course</h4>
        <p class="text-indigo-100 text-[10px] mb-4">Register a new subject for feedback.</p>
        <button onclick="document.getElementById('addCourseForm').classList.toggle('hidden')" class="w-full py-2 bg-white text-indigo-600 text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-50 transition">
            Add Course
        </button>
    </div>
</div>

<div id="addCourseForm" class="<?= isset($_POST['name']) ? '' : 'hidden' ?> bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm animate-slide-down">
    <div class="px-6 py-4 border-b border-gray-100 bg-indigo-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Create New Course</h3>
    </div>
    <form method="POST" class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="col-span-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Course Name *</label>
                <input type="text" name="name" placeholder="Object Oriented Programming" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Course Code *</label>
                <input type="text" name="code" placeholder="CS501" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            
            <?php if (!$isDeptAdmin): ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Department *</label>
                <select name="department_id" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                    <option value="" disabled selected>Select Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= sanitize($d['code']) ?> - <?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="department_id" value="<?= $deptId ?>">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Department</label>
                    <div class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-500 font-bold"><?= !empty($departments) ? sanitize($departments[0]['code']) : 'No Dept' ?></div>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Semester</label>
                <select name="semester" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                    <option value="">Select Semester (N/A)</option>
                    <?php for ($i=1;$i<=8;$i++): ?><option value="<?=$i?>">Semester <?=$i?></option><?php endfor; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-2"></i> Register
                </button>
            </div>
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-book text-emerald-500 mr-2"></i>All Courses (<?= $totalRecords ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase">Sr. No.</th>
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
                <?php if (!$isFiltered): ?>
                <tr>
                    <td colspan="7" class="px-6 py-20 text-center">
                        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-filter text-2xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-lg font-black text-gray-800">Please apply a filter to view courses</h3>
                        <p class="text-gray-400 text-xs mt-1">Select a department or semester to display records.</p>
                    </td>
                </tr>
                <?php elseif (empty($courses)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-20 text-center text-gray-400 italic">
                        No courses found for the selected criteria.
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $srNo = $offset + 1;
                    foreach ($courses as $c): ?>
                        <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                            <td class="px-4 py-3 text-center text-[10px] font-black text-gray-400"><?= $srNo++ ?></td>
                            <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-600 bg-indigo-50/20"><?= sanitize($c['code']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?= sanitize($c['name']) ?></td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500"><?= $c['semester'] ? 'SEM ' . $c['semester'] : '—' ?></td>
                            <td class="px-4 py-3">
                                <?php if (!$isDeptAdmin): ?>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter"><?= sanitize($c['dept_code']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="outcome-badge co"><?= $c['co_count'] ?></span></td>
                            <td class="px-4 py-3 text-center text-sm font-medium text-gray-600"><?= $c['form_count'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="confirmDelete('?delete=<?= $c['id'] ?>', '<?= $c['name'] ?>')" class="text-gray-300 hover:text-red-500 transition p-2">
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

<?php require_once __DIR__ . '/footer.php'; ?>
