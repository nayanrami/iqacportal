<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Handle Faculty Actions
if (isset($_POST['add_faculty'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $desig = trim($_POST['designation'] ?? '');
    $exp = trim($_POST['experience'] ?? '');
    $spec = trim($_POST['specialization'] ?? '');
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($name && $email && $targetDeptId) {
        $stmt = $pdo->prepare("INSERT INTO faculties (name, email, phone, designation, experience, specialization, department_id) 
                             VALUES (?, ?, ?, ?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE name = ?, phone = ?, designation = ?, experience = ?, specialization = ?");
        $stmt->execute([$name, $email, $phone, $desig, $exp, $spec, $targetDeptId, $name, $phone, $desig, $exp, $spec]);
        setFlash('success', "Faculty record updated/added: $name");
    } else {
        setFlash('danger', "Name, Email, and Department are required.");
    }
    redirect(APP_URL . '/admin/faculties.php');
}

if (isset($_GET['delete'])) {
    $fid = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM faculties WHERE id = ? " . ($isDeptAdmin ? "AND department_id = $deptId" : ""));
    $stmt->execute([$fid]);
    setFlash('success', "Faculty member removed.");
    redirect(APP_URL . '/admin/faculties.php');
}

// Fetch Filters
$filterDept = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? intval($_GET['dept_id']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$isFiltered = ($filterDept !== null) || ($search !== '');

// If dept admin, they are already filtered by department
if ($isDeptAdmin) {
    $filterDept = $deptId;
    $isFiltered = true;
}

// Data Fetching
$allDepartments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$deptFilter = $isDeptAdmin ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

$currentDeptName = "All Departments";
if ($isDeptAdmin) {
    if (!empty($departments)) {
        $currentDeptName = $departments[0]['name'] . " (" . $departments[0]['code'] . ")";
    } else {
        $currentDeptName = "Unknown Department (ID: $deptId)";
    }
}

$whereQuery = ["1=1"];
$params = [];

if ($isDeptAdmin) {
    $whereQuery[] = "f.department_id = ?";
    $params[] = $deptId;
    $filterDept = $deptId;
} elseif ($filterDept) {
    $whereQuery[] = "f.department_id = ?";
    $params[] = $filterDept;
}
if ($search) {
    $whereQuery[] = "(f.name LIKE ? OR f.email LIKE ?)";
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
$faculties = [];

if ($isFiltered) {
    try {
        // Count total
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM faculties f WHERE $whereString");
        $totalStmt->execute($params);
        $totalRecords = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated
        $stmt = $pdo->prepare("
            SELECT f.*, d.name as dept_name, d.code as dept_code
            FROM faculties f
            JOIN departments d ON d.id = f.department_id
            WHERE $whereString
            ORDER BY d.name, f.name
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $faculties = $stmt->fetchAll();
    } catch (Exception $e) {
        $faculties = [];
    }
}

$pageTitle = "Faculty Management";
require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Faculty Directory</h1>
            <p class="text-gray-500 text-sm">Manage institutional staff profiles and academic specializations.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openModal('addModal')" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Add Faculty
            </button>
            <button onclick="startBackgroundTask('sync', 'This will scrape faculty data from the ADIT website. Start background sync?')" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition flex items-center gap-2">
                <i class="fas fa-sync"></i> Sync from ADIT
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search Name / Email" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
            </div>
            
            <?php if (!$isDeptAdmin): ?>
            <select name="dept_id" class="px-4 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs outline-none focus:border-indigo-500 transition">
                <option value="">All Departments</option>
                <?php foreach ($allDepartments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['code']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
                <div class="px-4 py-2 bg-gray-100 border border-gray-100 rounded-lg text-xs text-gray-400 font-bold uppercase flex items-center gap-2">
                    <i class="fas fa-lock"></i> <?= sanitize($currentDeptName) ?> Only
                </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Filter</button>
                <a href="faculties.php" class="px-4 py-2 bg-gray-100 text-gray-500 rounded-lg hover:bg-gray-200 transition"><i class="fas fa-sync-alt text-xs"></i></a>
            </div>
        </form>
    </div>

    <!-- Faculty Table -->
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50 flex items-center justify-between">
            <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-list text-indigo-500 mr-2"></i>Faculty Directory (<?= $totalRecords ?>)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Sr. No.</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Name & Designation</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Contact Info</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Specialization</th>
                        <?php if(!$isDeptAdmin): ?>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Department</th>
                        <?php endif; ?>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!$isFiltered): ?>
                    <tr>
                        <td colspan="<?= $isDeptAdmin ? 5 : 6 ?>" class="px-6 py-20 text-center">
                            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-filter text-2xl text-indigo-400"></i>
                            </div>
                            <h3 class="text-lg font-black text-gray-800">Please apply a filter</h3>
                            <p class="text-gray-400 text-xs mt-1">Select a department or search by name to view faculty records.</p>
                        </td>
                    </tr>
                    <?php elseif (empty($faculties)): ?>
                    <tr>
                        <td colspan="<?= $isDeptAdmin ? 5 : 6 ?>" class="px-6 py-20 text-center text-gray-400 italic font-medium">
                            No faculty records found for the selected criteria.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $srNo = $offset + 1;
                        foreach ($faculties as $f): 
                            $spec = isset($f['specialization']) ? $f['specialization'] : 'General Engineering';
                        ?>
                        <tr class="hover:bg-gray-50/50 transition border-b border-gray-50 last:border-0 h-20">
                            <td class="px-6 py-4 text-center text-xs font-black text-gray-400">
                                <?= $srNo++ ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-gray-800"><?= sanitize($f['name']) ?></div>
                                <div class="text-[9px] font-bold text-indigo-500 uppercase tracking-wider"><?= sanitize($f['designation']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                                    <i class="fas fa-envelope-open text-[10px] text-gray-300"></i>
                                    <span><?= sanitize($f['email']) ?></span>
                                </div>
                                <?php if($f['phone']): ?>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <i class="fas fa-phone-alt text-[10px] text-gray-300"></i>
                                    <span><?= sanitize($f['phone']) ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600 italic">
                                <div class="line-clamp-2 max-w-xs"><?= sanitize($spec) ?></div>
                                <div class="text-[8px] font-black text-gray-300 uppercase tracking-widest mt-1"><?= sanitize($f['experience'] ?: '0 Years') ?> Exp.</div>
                            </td>
                            <?php if(!$isDeptAdmin): ?>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-black uppercase tracking-widest"><?= sanitize($f['dept_code']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="mapping.php?faculty_id=<?= $f['id'] ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-300 hover:bg-emerald-50 hover:text-emerald-500 transition" title="Mappings">
                                        <i class="fas fa-project-diagram text-xs"></i>
                                    </a>
                                    <button onclick="editFaculty(<?= htmlspecialchars(json_encode($f)) ?>)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-300 hover:bg-indigo-50 hover:text-indigo-500 transition" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button onclick="confirmDelete('?delete=<?= $f['id'] ?>', '<?= $f['name'] ?>')" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-300 hover:bg-rose-50 hover:text-rose-500 transition" title="Delete">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
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
    <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4">
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

<!-- Add/Edit Modal -->
<div id="addModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-black text-gray-800">Add Faculty Member</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Full Name *</label>
                <input type="text" name="name" id="f_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Email Address *</label>
                    <input type="email" name="email" id="f_email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Mobile Number</label>
                    <input type="text" name="phone" id="f_phone" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Designation</label>
                    <input type="text" name="designation" id="f_desig" placeholder="e.g. Assistant Professor" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Experience (Years)</label>
                    <input type="text" name="experience" id="f_exp" placeholder="e.g. 15 Years" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Department *</label>
                <select name="department_id" id="f_dept" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($deptId == $d['id'] ? 'selected' : '') ?>><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Specialization / Subjects</label>
                <textarea name="specialization" id="f_spec" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition"></textarea>
            </div>
            <button type="submit" name="add_faculty" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-100 mt-2">
                Save Faculty Record
            </button>
        </form>
    </div>
</div>

<script>
function editFaculty(f) {
    document.getElementById('modalTitle').innerText = 'Edit Faculty Record';
    document.getElementById('f_name').value = f.name;
    document.getElementById('f_email').value = f.email;
    document.getElementById('f_phone').value = f.phone;
    document.getElementById('f_desig').value = f.designation;
    document.getElementById('f_exp').value = f.experience;
    document.getElementById('f_dept').value = f.department_id;
    document.getElementById('f_spec').value = f.specialization;
    openModal('addModal');
}

function confirmDelete(url, name) {
    if (confirm(`Are you sure you want to remove ${name} from the directory?`)) {
        window.location.href = url;
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
