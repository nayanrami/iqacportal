<?php
/**
 * Admin - Departments Management
 */
$pageTitle = 'Departments';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([intval($_GET['delete'])]);
    setFlash('success', 'Department deleted.');
    redirect(APP_URL . '/admin/departments.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $naac_code = trim($_POST['naac_code'] ?? '');
    $hod = trim($_POST['hod_name'] ?? '');
    $phone = trim($_POST['hod_phone'] ?? '');
    $email = trim($_POST['hod_email'] ?? '');
    $year = intval($_POST['est_year'] ?? 0);
    
    if ($name && $code) {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, code = ?, naac_code = ?, hod_name = ?, hod_phone = ?, hod_email = ?, est_year = ? WHERE id = ?");
            $stmt->execute([$name, $code, $naac_code, $hod, $phone, $email, $year ?: null, $id]);
            setFlash('success', "Department '$code' updated successfully.");
        } else {
            // Insert
            $pdo->prepare("INSERT INTO departments (name, code, naac_code, hod_name, hod_phone, hod_email, est_year) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$name, $code, $naac_code, $hod, $phone, $email, $year ?: null]);
            setFlash('success', 'Department created with NAAC details.');
        }
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
        <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-plus-circle text-emerald-500 mr-2"></i>Add Department & NAAC Details</h3>
    </div>
    <form method="POST" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Department Name *</label>
                <input type="text" name="name" placeholder="Information Technology" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Short Code *</label>
                <input type="text" name="code" placeholder="IT" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">NAAC Dept Code</label>
                <input type="text" name="naac_code" placeholder="D-IT-001" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Establishment Year</label>
                <input type="number" name="est_year" placeholder="2005" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div class="lg:col-span-1">
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Name</label>
                <input type="text" name="hod_name" placeholder="Dr. Jane Doe" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Phone</label>
                <input type="text" name="hod_phone" placeholder="+91..." class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Email</label>
                <input type="email" name="hod_email" placeholder="hod@adit.ac.in" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
            </div>
            <div class="lg:col-span-3 flex items-end">
                <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-lg shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-2"></i> Register Department
                </button>
            </div>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($departments as $d): ?>
        <div class="glass-card overflow-hidden group">
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-lg font-black text-gray-800"><?= sanitize($d['name']) ?></div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-black uppercase tracking-widest"><?= sanitize($d['code']) ?></span>
                            <?php if ($d['naac_code']): ?>
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded text-[9px] font-black uppercase tracking-widest">NAAC: <?= sanitize($d['naac_code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="editDept(<?= htmlspecialchars(json_encode($d)) ?>)" class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-indigo-500 rounded-lg hover:bg-indigo-500 hover:text-white transition shadow-sm">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <a href="?delete=<?= $d['id'] ?>" onclick="return confirm('Delete department and all associated data?')" class="w-8 h-8 flex items-center justify-center bg-rose-50 text-rose-500 rounded-lg hover:bg-rose-500 hover:text-white transition shadow-sm">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </a>
                    </div>
                </div>

                <div class="mb-6 space-y-2 py-3 border-y border-gray-50">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold text-gray-400 uppercase tracking-widest">Head of Dept</span>
                        <span class="font-black text-gray-700 italic"><?= sanitize($d['hod_name'] ?: 'Not Specified') ?></span>
                    </div>
                    <?php if ($d['hod_phone']): ?>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold text-gray-400 uppercase tracking-widest">Phone</span>
                        <span class="font-black text-gray-600"><?= sanitize($d['hod_phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($d['hod_email']): ?>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold text-gray-400 uppercase tracking-widest">Email</span>
                        <span class="font-black text-indigo-500 lowercase"><?= sanitize($d['hod_email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold text-gray-400 uppercase tracking-widest">Established</span>
                        <span class="font-black text-gray-700"><?= $d['est_year'] ?: 'N/A' ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-2 bg-blue-50/50 rounded-xl border border-blue-50">
                        <div class="text-lg font-black text-blue-600"><?= $d['course_count'] ?></div>
                        <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Courses</div>
                    </div>
                    <div class="p-2 bg-violet-50/50 rounded-xl border border-violet-50">
                        <div class="text-lg font-black text-violet-600"><?= $d['form_count'] ?></div>
                        <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Forms</div>
                    </div>
                    <div class="p-2 bg-amber-50/50 rounded-xl border border-amber-50">
                        <div class="text-lg font-black text-amber-600"><?= $d['po_count'] ?></div>
                        <div class="text-[8px] font-black text-gray-400 uppercase tracking-widest">POs</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-2xl w-full animate-scale-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-700">Edit Department</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Department Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Short Code *</label>
                    <input type="text" name="code" id="edit_code" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">NAAC Dept Code</label>
                    <input type="text" name="naac_code" id="edit_naac_code" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Establishment Year</label>
                    <input type="number" name="est_year" id="edit_est_year" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Name</label>
                    <input type="text" name="hod_name" id="edit_hod_name" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Phone</label>
                    <input type="text" name="hod_phone" id="edit_hod_phone" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">HOD Email</label>
                    <input type="email" name="hod_email" id="edit_hod_email" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-indigo-500 transition">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-lg shadow-lg hover:bg-indigo-700 transition">
                    Update Department Information
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editDept(d) {
    document.getElementById('edit_id').value = d.id;
    document.getElementById('edit_name').value = d.name;
    document.getElementById('edit_code').value = d.code;
    document.getElementById('edit_naac_code').value = d.naac_code || '';
    document.getElementById('edit_est_year').value = d.est_year || '';
    document.getElementById('edit_hod_name').value = d.hod_name || '';
    document.getElementById('edit_hod_phone').value = d.hod_phone || '';
    document.getElementById('edit_hod_email').value = d.hod_email || '';
    openModal('editModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
