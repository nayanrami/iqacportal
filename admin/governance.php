<?php
/**
 * Admin - Governance & System Control
 * Exclusive tools for IQAC / SuperAdmins.
 */
$pageTitle = 'Institutional Governance';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Restriction: Only SuperAdmins can access Governance
if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
    setFlash('danger', 'Access denied. Governance tools reserved for IQAC Coordinator.');
    redirect(APP_URL . '/admin/');
}

// ── Handle System Reset ──
if (isset($_POST['reset_system']) && $_POST['confirm_reset'] === 'RESET_ALL_DATA') {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE responses");
        $pdo->exec("TRUNCATE TABLE response_answers");
        $pdo->exec("TRUNCATE TABLE students");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        setFlash('success', 'System reset successful. All feedback and student data purged.');
    } catch (Exception $e) {
        setFlash('danger', 'Reset failed: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/governance.php');
}

// ── Handle Quick Setup ──
if (isset($_POST['save_setup'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE portal_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    setFlash('success', 'Institutional settings updated successfully.');
    redirect(APP_URL . '/admin/governance.php');
}

$settings = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
require_once __DIR__ . '/header.php';
?>

<main class="p-4 md:p-8">
    <div class="mb-10">
        <h1 class="text-4xl font-black text-gray-800 mb-2">Institutional Governance</h1>
        <p class="text-gray-400 text-sm font-bold uppercase tracking-widest">Portal Setup & Accreditation Controls</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- Setup Wizard -->
        <div class="glass-card p-10">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fas fa-magic"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-gray-800">Setup Wizard</h3>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Institutional Identity Management</p>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Portal Title</label>
                    <input type="text" name="settings[app_name]" value="<?= sanitize($settings['app_name'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Institute / College Name</label>
                    <input type="text" name="settings[app_institute]" value="<?= sanitize($settings['app_institute'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold text-sm outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Accreditation Cycle</label>
                    <input type="text" name="settings[naac_cycle]" value="<?= sanitize($settings['naac_cycle'] ?? 'Cycle 3') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold text-sm outline-none focus:border-indigo-500 transition">
                </div>
                
                <button type="submit" name="save_setup" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase tracking-[.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition">
                    Commit Changes
                </button>
            </form>
        </div>

        <!-- Data Reset Center -->
        <div class="space-y-10">
            <div class="glass-card p-10 border-2 border-rose-50">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-rose-600">Administrative Reset</h3>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Prepare System for New Cycle</p>
                    </div>
                </div>

                <div class="p-6 bg-rose-50 text-rose-700 rounded-2xl border border-rose-100 mb-8">
                    <p class="text-[10px] font-black uppercase mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Danger Zone</p>
                    <p class="text-xs leading-relaxed font-bold">This action will permanently delete all student responses, course attainment data, and student records. This cannot be undone.</p>
                </div>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 text-center">Type <span class="text-rose-600 font-black">RESET_ALL_DATA</span> to confirm</label>
                        <input type="text" name="confirm_reset" placeholder="RESET_ALL_DATA" required class="w-full px-4 py-3 bg-white border border-rose-100 rounded-xl font-black text-center text-rose-600 outline-none focus:border-rose-400 transition placeholder-rose-200">
                    </div>
                    
                    <button type="submit" name="reset_system" class="w-full py-4 bg-rose-600 text-white rounded-2xl font-black uppercase tracking-[.2em] shadow-xl shadow-rose-100 hover:bg-rose-700 transition">
                        Full System Reset
                    </button>
                </form>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-2 gap-6">
                <a href="<?= APP_URL ?>/admin/departments.php" class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition text-center group">
                    <i class="fas fa-building text-emerald-500 text-xl mb-2 block"></i>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-emerald-500">Departments</div>
                </a>
                <a href="<?= APP_URL ?>/admin/naac_hub.php" class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition text-center group">
                    <i class="fas fa-award text-indigo-500 text-xl mb-2 block"></i>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-indigo-500">Accreditation</div>
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
