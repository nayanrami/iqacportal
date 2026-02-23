<?php
/**
 * Admin - Criterion 1: Curricular Aspects (Compliance)
 */
$pageTitle = 'Criterion I: Curricular Aspects';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// ── Role Specific Redirection ──
// Criterion 1 Coordinators can see institutional but often focus on dept if assigned
$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;

// ── Handle Syllabus Update ──
if (isset($_POST['update_syllabus'])) {
    $courseId = intval($_POST['course_id']);
    $pct = intval($_POST['completion']);
    $ay = sanitize($_POST['academic_year']);
    $sem = intval($_POST['semester']);
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($courseId && $targetDeptId) {
        $stmt = $pdo->prepare("INSERT INTO naac_syllabus_status (department_id, course_id, academic_year, semester, completion_percentage) 
                             VALUES (?, ?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE completion_percentage = ?, last_updated = CURRENT_TIMESTAMP");
        $stmt->execute([$targetDeptId, $courseId, $ay, $sem, $pct, $pct]);
        setFlash('success', 'Syllabus completion status updated.');
    }
    redirect(APP_URL . '/admin/compliance.php' . ($deptId ? '' : '?dept_id=' . $targetDeptId));
}

// ── Handle Value Added Course ──
if (isset($_POST['add_vac'])) {
    $name = sanitize($_POST['vac_name']);
    $code = sanitize($_POST['vac_code']);
    $hrs = intval($_POST['duration']);
    $enroll = intval($_POST['students']);
    $year = intval($_POST['year']);
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($name && $targetDeptId) {
        $stmt = $pdo->prepare("INSERT INTO naac_value_added_courses (department_id, course_name, course_code, duration_hours, students_enrolled, completion_year) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targetDeptId, $name, $code, $hrs, $enroll, $year]);
        setFlash('success', 'Value-added course record added.');
    }
    redirect(APP_URL . '/admin/compliance.php' . ($deptId ? '' : '?dept_id=' . $targetDeptId));
}

// ── Delete VAC ──
if (isset($_GET['del_vac'])) {
    $id = intval($_GET['del_vac']);
    $check = $pdo->prepare("SELECT id FROM naac_value_added_courses WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM naac_value_added_courses WHERE id = ?")->execute([$id]);
        setFlash('success', 'VAC record removed.');
    }
    redirect(APP_URL . '/admin/compliance.php');
}

$deptName = "Institutional";
if ($deptId) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    $deptName = $stmt->fetchColumn();
}

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

// Fetch Courses for syllabus tracking
$courseQuery = $deptId ? " WHERE department_id = $deptId" : "";
$courses = $pdo->query("SELECT * FROM courses $courseQuery ORDER BY semester, code")->fetchAll();

// Fetch existing syllabus records
$syllabusRecords = $pdo->query("
    SELECT s.*, c.code as course_code, c.name as course_name 
    FROM naac_syllabus_status s 
    JOIN courses c ON c.id = s.course_id 
    " . ($deptId ? " WHERE s.department_id = $deptId" : "") . "
    ORDER BY s.academic_year DESC, c.semester ASC
")->fetchAll();

// Fetch VAC records
$vacRecords = $pdo->query("
    SELECT * FROM naac_value_added_courses 
    " . ($deptId ? " WHERE department_id = $deptId" : "") . "
    ORDER BY completion_year DESC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Curricular Aspects</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">NAAC Criterion I Compliance - <?= sanitize($deptName) ?></p>
    </div>

    <!-- Feedback Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="glass-card p-6 border-l-4 border-indigo-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Student Feedback</div>
            <div class="text-2xl font-black text-gray-800 tracking-tight">Active</div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Teacher Feedback</div>
            <div class="text-2xl font-black text-gray-800 tracking-tight">Collected</div>
        </div>
        <div class="glass-card p-6 border-l-4 border-amber-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Employer Feedback</div>
            <div class="text-2xl font-black text-gray-800 tracking-tight">Pending</div>
        </div>
        <div class="glass-card p-6 border-l-4 border-purple-500">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Alumni Feedback</div>
            <div class="text-2xl font-black text-gray-800 tracking-tight">Ready</div>
        </div>
    </div>

    <!-- Implementation Areas -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
        <!-- Syllabus Completion (1.1) -->
        <div class="glass-card">
            <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-gray-800">Syllabus Completion</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Metric 1.1.1 Tracking</p>
                </div>
                <button onclick="openModal('syllabusModal')" class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition shadow-sm">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="p-8">
                <?php if (empty($syllabusRecords)): ?>
                    <div class="py-12 text-center text-gray-400 text-sm font-medium italic border-2 border-dashed border-gray-50 rounded-2xl">
                        No syllabus completion records found.
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($syllabusRecords as $sr): ?>
                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-black text-indigo-500 uppercase"><?= $sr['course_code'] ?> - <?= $sr['academic_year'] ?></div>
                                    <div class="text-sm font-bold text-gray-700"><?= $sr['course_name'] ?></div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-xs font-black text-gray-800"><?= $sr['completion_percentage'] ?>%</div>
                                        <div class="w-16 h-1 bg-gray-200 rounded-full mt-1 overflow-hidden">
                                            <div class="h-full bg-emerald-500" style="width: <?= $sr['completion_percentage'] ?>%"></div>
                                        </div>
                                    </div>
                                    <button class="text-gray-300 hover:text-rose-500 transition"><i class="fas fa-trash-alt text-[10px]"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Value Added Courses (1.2) -->
        <div class="glass-card">
            <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-gray-800">Value Added Courses</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Metric 1.2.2 Registry</p>
                </div>
                <button onclick="openModal('vacModal')" class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center hover:bg-purple-600 hover:text-white transition shadow-sm">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="p-8">
                <?php if (empty($vacRecords)): ?>
                    <div class="py-12 text-center text-gray-400 text-sm font-medium italic border-2 border-dashed border-gray-50 rounded-2xl">
                        No certificate courses recorded.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none border-b border-gray-50">
                                <tr>
                                    <th class="py-3">Course</th>
                                    <th class="py-3 text-center">Hrs</th>
                                    <th class="py-3 text-center">Students</th>
                                    <th class="py-3 text-right">Act</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($vacRecords as $vac): ?>
                                    <tr class="hover:bg-gray-50/30 transition">
                                        <td class="py-4">
                                            <div class="text-xs font-bold text-gray-700"><?= $vac['course_name'] ?></div>
                                            <div class="text-[9px] text-gray-400">Year: <?= $vac['completion_year'] ?></div>
                                        </td>
                                        <td class="py-4 text-center font-mono text-[10px]"><?= $vac['duration_hours'] ?></td>
                                        <td class="py-4 text-center">
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black"><?= $vac['students_enrolled'] ?></span>
                                        </td>
                                        <td class="py-4 text-right">
                                            <a href="?del_vac=<?= $vac['id'] ?>" class="text-gray-300 hover:text-rose-500 transition"><i class="fas fa-trash-alt text-[10px]"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback & Analysis Links -->
        <div class="xl:col-span-2 glass-card p-10 bg-indigo-600 text-white flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="max-w-xl text-center md:text-left">
                <h3 class="text-2xl font-black mb-2">Stakeholder Feedback Analysis</h3>
                <p class="text-indigo-100 text-sm leading-relaxed opacity-80">Finalize the feedback cycle for Metric 1.4.1. View detailed analytics for Students, Teachers, Employers, and Alumni to generate the required "Action Taken Report" (ATR).</p>
            </div>
            <div class="flex flex-wrap justify-center gap-4 shrink-0">
                <a href="analysis.php" class="px-8 py-3 bg-white text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 transition-all">Analytics Dashboard</a>
                <a href="feedback_forms.php" class="px-8 py-3 bg-indigo-500 text-white border border-indigo-400 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-400 transition-all">SOP Manager</a>
            </div>
        </div>
    </div>
</main>

<!-- Syllabus Modal -->
<div id="syllabusModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800">Track Completion</h3>
            <button onclick="closeModal('syllabusModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Course *</label>
                <select name="course_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['code'] ?> - <?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Academic Year</label>
                    <input type="text" name="academic_year" value="2023-24" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Semester</label>
                    <input type="number" name="semester" value="1" min="1" max="8" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Completion (%)</label>
                <input type="range" name="completion" min="0" max="100" value="100" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600" oninput="this.nextElementSibling.innerText = this.value + '%'">
                <div class="text-center font-black text-indigo-600 mt-2">100%</div>
            </div>
            <button type="submit" name="update_syllabus" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl shadow-indigo-100 uppercase tracking-[.2em] text-[10px]">
                Update Status
            </button>
        </form>
    </div>
</div>

<!-- VAC Modal -->
<div id="vacModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800 text-purple-600">Register Value Added Course</h3>
            <button onclick="closeModal('vacModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Course Name *</label>
                <input type="text" name="vac_name" placeholder="Python For Data Science" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-purple-500 transition font-bold text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Duration (Hrs)</label>
                    <input type="number" name="duration" value="30" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-purple-500 transition font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Enrolled Students</label>
                    <input type="number" name="students" value="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-purple-500 transition font-bold text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Completion Year</label>
                <input type="number" name="year" value="<?= date('Y') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-purple-500 transition font-bold text-sm">
            </div>
            <button type="submit" name="add_vac" class="w-full py-4 bg-purple-600 text-white font-black rounded-2xl shadow-xl shadow-purple-100 uppercase tracking-[.2em] text-[10px]">
                Save VAC Record
            </button>
        </form>
    </div>
</div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
