<?php
/**
 * Admin - Criterion 7: Institutional Values & Best Practices
 * Tracks Best Practices, Social Responsibility, and Institutional Distinctiveness
 */
$pageTitle = 'Criterion VII: Institutional Values';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// ── Migration: Ensure table exists (if not in setup) ──
$pdo->exec("CREATE TABLE IF NOT EXISTS naac_best_practices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    outcomes TEXT,
    academic_year VARCHAR(20),
    category ENUM('Best Practice', 'Social Service', 'Environmental', 'Distinctiveness') DEFAULT 'Best Practice',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Handle Record Addition ──
if (isset($_POST['add_practice'])) {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $outcomes = sanitize($_POST['outcomes']);
    $year = sanitize($_POST['academic_year']);
    $category = $_POST['category'];
    $targetDeptId = $deptId ?: ($_POST['department_id'] ? intval($_POST['department_id']) : null);

    if ($title) {
        $stmt = $pdo->prepare("INSERT INTO naac_best_practices (department_id, title, description, outcomes, academic_year, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targetDeptId, $title, $desc, $outcomes, $year, $category]);
        setFlash('success', 'Institutional value/practice recorded.');
    }
    redirect(APP_URL . '/admin/best_practices.php');
}

// ── Handle Deletion ──
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM naac_best_practices WHERE id = ?")->execute([$id]);
    setFlash('success', 'Practice removed.');
    redirect(APP_URL . '/admin/best_practices.php');
}

// Fetch Practices
$query = "SELECT p.*, d.name as dept_name FROM naac_best_practices p LEFT JOIN departments d ON d.id = p.department_id";
if ($deptId) $query .= " WHERE p.department_id = $deptId";
$query .= " ORDER BY p.academic_year DESC";
$practices = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Institutional Values</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">NAAC Criterion VII • Best Practices & Social Responsibility</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <div class="lg:col-span-2 glass-card">
            <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/20">
                <div>
                    <h3 class="text-xl font-black text-gray-800">Practices Registry</h3>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Audit Documentation Suite</p>
                </div>
                <button onclick="openModal('practiceModal')" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-emerald-100">
                    <i class="fas fa-plus mr-2"></i> Record Entry
                </button>
            </div>

            <div class="p-8">
                <?php if (empty($practices)): ?>
                    <div class="py-12 text-center text-gray-300 text-xs font-bold uppercase italic border-2 border-dashed border-gray-50 rounded-2xl">
                        No institutional practices recorded.
                    </div>
                <?php else: foreach ($practices as $p): ?>
                    <div class="p-6 border border-gray-100 rounded-2xl mb-4 hover:bg-gray-50/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[9px] font-black uppercase tracking-widest mr-2"><?= $p['category'] ?></span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest"><?= $p['academic_year'] ?></span>
                                <h4 class="text-lg font-black text-gray-800 mt-2"><?= $p['title'] ?></h4>
                            </div>
                            <button onclick="confirmDelete('?delete=<?= $p['id'] ?>', '<?= $p['title'] ?>')" class="text-gray-300 hover:text-rose-500 transition">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed mb-4"><?= $p['description'] ?></p>
                        <div class="bg-indigo-50/30 p-4 rounded-xl border border-indigo-50/50">
                            <div class="text-[8px] font-black text-indigo-400 uppercase tracking-widest mb-1">Key Outcomes / Proof</div>
                            <p class="text-xs font-bold text-gray-600 italic">"<?= $p['outcomes'] ?>"</p>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="space-y-8">
            <div class="glass-card p-10 bg-gradient-to-br from-indigo-600 to-purple-700 text-white">
                <i class="fas fa-quote-left text-3xl opacity-20 mb-4 block"></i>
                <h3 class="text-xl font-black mb-4 leading-tight">Institutional Distinctiveness</h3>
                <p class="text-sm opacity-80 leading-relaxed italic font-medium">"To nurture strong foundations in computation and digitization, enabling students to adapt to evolving technological needs of society."</p>
                <div class="mt-8 pt-6 border-t border-white/10 flex items-center gap-3">
                    <img src="<?= $settings['college_logo'] ?? '' ?>" class="w-8 h-8 rounded-lg bg-white p-1">
                    <div class="text-[10px] uppercase font-black tracking-widest leading-tight">ADIT Official <br>Vision Statement</div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="practiceModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800">Add Practice / Initiative</h3>
            <button onclick="closeModal('practiceModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Practice Title *</label>
                    <input type="text" name="title" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Type / Category</label>
                    <select name="category" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
                        <option value="Best Practice">7.2.1: Best Practice</option>
                        <option value="Social Service">Social Responsibility</option>
                        <option value="Environmental">Environmental / Green</option>
                        <option value="Distinctiveness">7.3.1: Distinctiveness</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Academic Year</label>
                    <input type="text" name="academic_year" value="2023-24" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Summary / Methodology</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm"></textarea>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Evidence of Success / Outcomes</label>
                <input type="text" name="outcomes" placeholder="Number of beneficiaries / KPI achieved" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl font-bold text-sm">
            </div>
            <button type="submit" name="add_practice" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-xl shadow-emerald-100 uppercase tracking-[.2em] text-[10px]">
                Save Practice
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
