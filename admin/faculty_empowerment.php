<?php
/**
 * Admin - Criterion 6: Faculty Empowerment
 * Tracks FDPs, STTPs, and Conferences for Metric 6.3.3
 */
$pageTitle = 'Criterion VI: Faculty Empowerment';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// ── Handle Record Addition ──
if (isset($_POST['add_empowerment'])) {
    $facultyName = sanitize($_POST['faculty_name']);
    $type = $_POST['activity_type'];
    $title = sanitize($_POST['title']);
    $duration = intval($_POST['duration_days']);
    $startDate = $_POST['start_date'];
    $targetDeptId = $deptId ?: intval($_POST['department_id']);

    if ($facultyName && $type && $targetDeptId) {
        $stmt = $pdo->prepare("INSERT INTO naac_faculty_empowerment (department_id, faculty_name, activity_type, title, duration_days, start_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targetDeptId, $facultyName, $type, $title, $duration, $startDate]);
        setFlash('success', 'Faculty empowerment record added successfully.');
    }
    redirect(APP_URL . '/admin/faculty_empowerment.php');
}

// ── Handle Deletion ──
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Auth check
    $check = $pdo->prepare("SELECT id FROM naac_faculty_empowerment WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM naac_faculty_empowerment WHERE id = ?")->execute([$id]);
        setFlash('success', 'Record removed.');
    }
    redirect(APP_URL . '/admin/faculty_empowerment.php');
}

// Fetch Records
$query = "SELECT e.*, d.name as dept_name FROM naac_faculty_empowerment e JOIN departments d ON d.id = e.department_id";
if ($deptId) {
    $query .= " WHERE e.department_id = $deptId";
}
$query .= " ORDER BY e.start_date DESC";
$records = $pdo->query($query)->fetchAll();

// Stats
$fdpCount = count(array_filter($records, fn($r) => $r['activity_type'] == 'FDP'));
$confCount = count(array_filter($records, fn($r) => $r['activity_type'] == 'Conference'));

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Faculty Empowerment</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">NAAC Criterion VI • Professional Development Tracking</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Stats Sidebar -->
        <div class="space-y-6">
            <div class="glass-card p-6 bg-indigo-600 text-white">
                <div class="text-[9px] font-black uppercase tracking-widest mb-1 opacity-60">Total Activities</div>
                <div class="text-4xl font-black"><?= count($records) ?></div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-white/20 rounded text-[8px] font-bold uppercase tracking-widest">Metric 6.3.3</span>
                </div>
            </div>
            
            <div class="glass-card p-6">
                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-4">Activity Breakdown</div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-600">FDP / STTP</span>
                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-black"><?= $fdpCount ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-600">Conferences</span>
                        <span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded text-[10px] font-black"><?= $confCount ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Registry -->
        <div class="lg:col-span-3 space-y-8">
            <div class="glass-card">
                <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-800">Development Registry</h3>
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Institutional Audit Log</p>
                    </div>
                    <button onclick="openModal('facultyModal')" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-indigo-100">
                        <i class="fas fa-plus mr-2"></i> Add Activity
                    </button>
                </div>
                
                <div class="p-8">
                    <div class="space-y-4">
                        <?php if (empty($records)): ?>
                            <div class="py-12 text-center text-gray-300 text-xs font-bold uppercase italic border-2 border-dashed border-gray-50 rounded-2xl">
                                No empowerment records found.
                            </div>
                        <?php else: foreach ($records as $r): ?>
                            <div class="p-5 border border-gray-100 rounded-2xl hover:bg-gray-50/50 transition flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                                        <i class="fas <?= $r['activity_type'] == 'FDP' ? 'fa-chalkboard-teacher' : 'fa-microphone' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-gray-800"><?= $r['faculty_name'] ?></div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight mt-0.5">
                                            <?= $r['title'] ?> • <?= $r['duration_days'] ?> Days
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-right mr-4 hidden md:block">
                                        <div class="text-[9px] font-black text-gray-800 uppercase"><?= $r['dept_name'] ?></div>
                                        <div class="text-[8px] text-gray-400 font-bold uppercase"><?= date('M Y', strtotime($r['start_date'])) ?></div>
                                    </div>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[9px] font-black uppercase"><?= $r['activity_type'] ?></span>
                                    <button onclick="confirmDelete('?delete=<?= $r['id'] ?>', '<?= $r['faculty_name'] ?>')" class="text-gray-300 hover:text-rose-500 transition">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="facultyModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800">Add Empowerment Record</h3>
            <button onclick="closeModal('facultyModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <?php if (!$deptId): ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Department *</label>
                <select name="department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
                    <?php 
                    $depts = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
                    foreach ($depts as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Faculty Name *</label>
                <input type="text" name="faculty_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Activity Type</label>
                <select name="activity_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
                    <option value="FDP">FDP / STTP</option>
                    <option value="Conference">International Conference</option>
                    <option value="Workshop">Workshop</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Activity Title</label>
                <input type="text" name="title" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Duration (Days)</label>
                    <input type="number" name="duration_days" value="5" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Start Date</label>
                    <input type="date" name="start_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm transition">
                </div>
            </div>
            <button type="submit" name="add_empowerment" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl shadow-indigo-100 uppercase tracking-[.2em] text-[10px]">
                Commit Record
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
