<?php
/**
 * Admin - Criterion 5: Student Support & Progression
 */
$pageTitle = 'Criterion V: Student Progression';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// ── Handle Progression Addition ──
if (isset($_POST['add_progression'])) {
    $studentId = intval($_POST['student_id']);
    $type = $_POST['progression_type'];
    $org = sanitize($_POST['organization']);
    $details = sanitize($_POST['details']);
    $year = sanitize($_POST['academic_year']);

    if ($studentId && $type) {
        $stmt = $pdo->prepare("INSERT INTO naac_student_progression (student_id, progression_type, organization_name, details, academic_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $type, $org, $details, $year]);
        setFlash('success', 'Progression record added successfully.');
    }
    redirect(APP_URL . '/admin/progression.php');
}

// ── Handle Deletion ──
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Check if progression belongs to a student in the dept
    $check = $pdo->prepare("SELECT p.id FROM naac_student_progression p JOIN students s ON s.id = p.student_id WHERE p.id = ?" . ($deptId ? " AND s.department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM naac_student_progression WHERE id = ?")->execute([$id]);
        setFlash('success', 'Progression record removed.');
    }
    redirect(APP_URL . '/admin/progression.php');
}

$deptName = "Institutional";
if ($deptId) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    $deptName = $stmt->fetchColumn();
}

// Fetch Students for dropdown
$studentQuery = $deptId ? " WHERE department_id = $deptId" : "";
$students = $pdo->query("SELECT id, name, enrollment_no FROM students $studentQuery ORDER BY name")->fetchAll();

// Fetch Progressions
$progQuery = "SELECT p.*, s.name as student_name, s.enrollment_no, d.name as dept_name 
              FROM naac_student_progression p 
              JOIN students s ON s.id = p.student_id 
              JOIN departments d ON d.id = s.department_id";
if ($deptId) {
    $progQuery .= " WHERE s.department_id = $deptId";
}
$progQuery .= " ORDER BY p.academic_year DESC, s.name ASC";
$progressions = $pdo->query($progQuery)->fetchAll();

// Stats
$placedCount = count(array_filter($progressions, fn($p) => $p['progression_type'] == 'Placement'));
$higherEdCount = count(array_filter($progressions, fn($p) => $p['progression_type'] == 'Higher Ed'));
$entrepreneurCount = count(array_filter($progressions, fn($p) => $p['progression_type'] == 'Entrepreneurship'));

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Student Progression</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">NAAC Criterion V Compliance Management</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Placement & Progression -->
        <div class="lg:col-span-2 glass-card">
            <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                <div>
                    <h3 class="text-xl font-black text-gray-800">Career Progression</h3>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Metric 5.2.1 Management</p>
                </div>
                <button onclick="openModal('progressionModal')" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-indigo-100">
                    <i class="fas fa-plus mr-2"></i> Add Record
                </button>
            </div>
            
            <div class="p-8">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
                    <div class="p-6 bg-indigo-50/50 rounded-2xl border border-indigo-50">
                        <div class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-1">Placed</div>
                        <div class="text-3xl font-black text-indigo-600"><?= $placedCount ?></div>
                    </div>
                    <div class="p-6 bg-emerald-50/50 rounded-2xl border border-emerald-50">
                        <div class="text-[9px] font-black text-emerald-400 uppercase tracking-widest mb-1">Higher Ed</div>
                        <div class="text-3xl font-black text-emerald-600"><?= $higherEdCount ?></div>
                    </div>
                    <div class="p-6 bg-amber-50/50 rounded-2xl border border-amber-50">
                        <div class="text-[9px] font-black text-amber-400 uppercase tracking-widest mb-1">Startups</div>
                        <div class="text-3xl font-black text-amber-600"><?= $entrepreneurCount ?></div>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php if (empty($progressions)): ?>
                        <div class="py-12 text-center text-gray-300 text-xs font-bold uppercase italic border-2 border-dashed border-gray-50 rounded-2xl">
                            No progression records found.
                        </div>
                    <?php else: foreach ($progressions as $p): ?>
                        <div class="p-5 border border-gray-100 rounded-2xl hover:bg-gray-50/50 transition flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-indigo-500 shadow-sm">
                                    <i class="fas <?= $p['progression_type'] == 'Placement' ? 'fa-briefcase' : ($p['progression_type'] == 'Higher Ed' ? 'fa-university' : 'fa-lightbulb') ?>"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-black text-gray-800"><?= $p['student_name'] ?> <span class="text-[10px] text-gray-400 font-mono ml-2"><?= $p['enrollment_no'] ?></span></div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight mt-0.5">
                                        <?= $p['organization_name'] ?> • <?= $p['academic_year'] ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[9px] font-black uppercase"><?= $p['progression_type'] ?></span>
                                <button onclick="confirmDelete('?delete=<?= $p['id'] ?>', '<?= $p['student_name'] ?>')" class="text-gray-300 hover:text-rose-500 transition">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Support -->
        <div class="glass-card p-10 bg-gradient-to-br from-white to-rose-50/30">
            <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-3">
                <i class="fas fa-hands-helping text-rose-500"></i> Support Hub
            </h3>
            
            <div class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-white rounded-xl shadow-sm border border-gray-50 hover:shadow-md transition">
                    <div>
                        <div class="text-[10px] font-black text-gray-800 uppercase tracking-tighter">Capacity Building</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase">Skill Enhancement Tracking</div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300"></i>
                </div>
                <div class="flex items-center justify-between p-4 bg-white rounded-xl shadow-sm border border-gray-50 hover:shadow-md transition">
                    <div>
                        <div class="text-[10px] font-black text-gray-800 uppercase tracking-tighter">Competitive Exams</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase">GATE/GRE/NET Statistics</div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300"></i>
                </div>
                <div class="flex items-center justify-between p-4 bg-white rounded-xl shadow-sm border border-gray-50 hover:shadow-md transition">
                    <div>
                        <div class="text-[10px] font-black text-gray-800 uppercase tracking-tighter">Student Grievances</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase">Redressal Documentation</div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300"></i>
                </div>
            </div>

            <button class="w-full mt-10 py-4 bg-rose-600 text-white text-[10px] font-black uppercase tracking-[.2em] rounded-2xl shadow-xl shadow-rose-100 hover:bg-rose-700 transition">
                Create Support Ticket
            </button>
        </div>
    </div>
</main>

<!-- Progression Modal -->
<div id="progressionModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800">Add Progression Record</h3>
            <button onclick="closeModal('progressionModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Student *</label>
                <select name="student_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'] ?> (<?= $s['enrollment_no'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Progression Type</label>
                <select name="progression_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                    <option value="Placement">Placement (Job)</option>
                    <option value="Higher Ed">Higher Education (PG/Research)</option>
                    <option value="Entrepreneurship">Entrepreneurship (Startup)</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Organization/University</label>
                <input type="text" name="organization" placeholder="Google / Stanford University" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Details (CTC/Course)</label>
                    <input type="text" name="details" placeholder="12 LPA / MS CS" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Academic Year</label>
                    <input type="text" name="academic_year" value="2023-24" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
            </div>
            <button type="submit" name="add_progression" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl shadow-indigo-100 uppercase tracking-[.2em] text-[10px]">
                Save Progress
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
