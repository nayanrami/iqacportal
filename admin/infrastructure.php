<?php
/**
 * Admin - Criterion 4: Infrastructure & Learning Resources
 */
$pageTitle = 'Criterion IV: Infrastructure';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$currentRole = $_SESSION['admin_role'] ?? 'deptadmin';
$deptId = $_SESSION['admin_dept_id'] ?? (isset($_GET['dept_id']) ? intval($_GET['dept_id']) : null);

// ── Handle Asset Addition ──
if (isset($_POST['add_asset'])) {
    $name = sanitize($_POST['asset_name']);
    $type = $_POST['asset_type'];
    $qty = intval($_POST['quantity']);
    $year = intval($_POST['year']);
    $status = $_POST['status'];
    $targetDeptId = $deptId ?: intval($_POST['department_id'] ?? 0);

    if ($name && $targetDeptId) {
        $stmt = $pdo->prepare("INSERT INTO naac_infrastructure_assets (department_id, asset_name, asset_type, quantity, purchase_year, condition_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targetDeptId, $name, $type, $qty, $year, $status]);
        setFlash('success', 'Infrastructure asset recorded.');
    }
    redirect(APP_URL . '/admin/infrastructure.php' . ($deptId ? '' : '?dept_id=' . $targetDeptId));
}

// ── Handle Asset Deletion ──
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT id FROM naac_infrastructure_assets WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM naac_infrastructure_assets WHERE id = ?")->execute([$id]);
        setFlash('success', 'Asset record removed.');
    }
    redirect(APP_URL . '/admin/infrastructure.php');
}

$deptName = "Institutional";
if ($deptId) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);
    $deptName = $stmt->fetchColumn();
}

// Fetch all assets
$assetQuery = $deptId ? " WHERE department_id = $deptId" : "";
$assets = $pdo->query("SELECT * FROM naac_infrastructure_assets $assetQuery ORDER BY purchase_year DESC")->fetchAll();

// Aggregated Stats
$itCount = array_sum(array_column(array_filter($assets, fn($a) => $a['asset_type'] == 'IT'), 'quantity'));
$labCount = array_sum(array_column(array_filter($assets, fn($a) => $a['asset_type'] == 'Lab'), 'quantity'));
$libCount = array_sum(array_column(array_filter($assets, fn($a) => $a['asset_type'] == 'Library'), 'quantity'));

$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT * FROM departments $deptFilter ORDER BY name")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10 text-center md:text-left">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Infrastructure & Resources</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">NAAC Criterion IV Compliance Dashboard</p>
    </div>

    <!-- Infrastructure Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div class="glass-card p-8 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="fas fa-laptop"></i></div>
                <div class="text-3xl font-black text-gray-800"><?= $itCount ?></div>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">IT Resources</h3>
            <p class="text-[9px] text-gray-400 mt-1 uppercase">Total Bandwidth & Computing Units</p>
        </div>

        <div class="glass-card p-8 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center"><i class="fas fa-microscope"></i></div>
                <div class="text-3xl font-black text-gray-800"><?= $labCount ?></div>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Lab Equipment</h3>
            <p class="text-[9px] text-gray-400 mt-1 uppercase">Metric 4.4.1 Physical Facilities</p>
        </div>

        <div class="glass-card p-8 border-l-4 border-amber-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center"><i class="fas fa-book"></i></div>
                <div class="text-3xl font-black text-gray-800"><?= $libCount ?></div>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Library Assets</h3>
            <p class="text-[9px] text-gray-400 mt-1 uppercase">Metric 4.2.1 ILMS Automation</p>
        </div>
    </div>

    <!-- Asset Registry Table -->
    <div class="glass-card mb-8">
        <div class="p-8 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-xl font-black text-gray-800">Resource Registry</h3>
            <button onclick="openModal('assetModal')" class="px-6 py-2 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-gray-200">
                <i class="fas fa-plus mr-2"></i> Add Asset
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Asset Name</th>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Type</th>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Qty</th>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Year</th>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Condition</th>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($assets)): ?>
                        <tr><td colspan="6" class="px-8 py-12 text-center text-gray-400 italic font-medium">No assets recorded for departmental audit.</td></tr>
                    <?php else: foreach ($assets as $a): ?>
                        <tr class="hover:bg-gray-50/30 transition">
                            <td class="px-8 py-4 font-bold text-gray-700"><?= sanitize($a['asset_name']) ?></td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-black uppercase"><?= $a['asset_type'] ?></span>
                            </td>
                            <td class="px-8 py-4 text-center font-mono text-xs"><?= $a['quantity'] ?></td>
                            <td class="px-8 py-4 text-center text-xs text-gray-400"><?= $a['purchase_year'] ?></td>
                            <td class="px-8 py-4">
                                <?php 
                                $statusSet = [
                                    'Excellent' => 'bg-emerald-500',
                                    'Good' => 'bg-emerald-400',
                                    'Fair' => 'bg-amber-400',
                                    'Needs Repair' => 'bg-rose-400',
                                    'Scrapped' => 'bg-gray-400'
                                ];
                                $color = $statusSet[$a['condition_status']] ?? 'bg-gray-400';
                                ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full <?= $color ?>"></div>
                                    <span class="text-[10px] font-bold text-gray-600"><?= $a['condition_status'] ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button onclick="confirmDelete('?delete=<?= $a['id'] ?>', '<?= sanitize($a['asset_name']) ?>')" class="text-gray-300 hover:text-rose-500 transition">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Asset Modal -->
<div id="assetModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full animate-scale-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-xl font-black text-gray-800">Add New Resource</h3>
            <button onclick="closeModal('assetModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Asset Name *</label>
                <input type="text" name="asset_name" placeholder="Dell Optiplex 7000" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Type</label>
                    <select name="asset_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                        <option value="Lab">Lab Equipment</option>
                        <option value="IT">IT (Computers/Networking)</option>
                        <option value="Library">Library resource</option>
                        <option value="Physical">Physical facility</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Purchase Year</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" min="1900" max="<?= date('Y') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Condition</label>
                    <select name="status" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                        <option value="Excellent">Excellent</option>
                        <option value="Good" selected>Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Needs Repair">Needs Repair</option>
                        <option value="Scrapped">Scrapped</option>
                    </select>
                </div>
            </div>
            <?php if (!$isDeptAdmin): ?>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assign to Department</label>
                <select name="department_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-sm">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" name="add_asset" class="w-full py-4 bg-gray-900 text-white font-black rounded-2xl shadow-xl shadow-gray-100 uppercase tracking-[.2em] text-[10px]">
                Save Resource Record
            </button>
        </form>
    </div>
</div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
