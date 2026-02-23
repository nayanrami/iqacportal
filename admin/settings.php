<?php
/**
 * Admin - Portal Settings Management
 */
$pageTitle = 'Portal Settings';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Only IQAC Coordinator (Super Admin) can access this
if (isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null) {
    setFlash('danger', 'Access denied. Only super admins can manage portal settings.');
    redirect(APP_URL . '/admin/index.php');
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE portal_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([sanitize($value), $key]);
        }
        $pdo->commit();
        setFlash('success', 'Portal settings updated successfully.');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Error updating settings: ' . $e->getMessage());
    }
    // Refresh to apply constants
    redirect(APP_URL . '/admin/settings.php');
}

require_once __DIR__ . '/header.php';

$stmt = $pdo->query("SELECT * FROM portal_settings ORDER BY id");
$dbSettings = $stmt->fetchAll();
?>

<main class="p-4 md:p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-black gradient-text">Portal Settings</h1>
        <p class="text-gray-500 text-sm">Configure global portal details like college name, institute, and department info.</p>
    </div>

    <div class="max-w-2xl">
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Global Configuration</h3>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <?php foreach ($dbSettings as $s): 
                    $label = ucwords(str_replace(['app_', '_'], ['', ' '], $s['setting_key']));
                    if ($s['setting_key'] == 'app_url') $label = 'Application URL Path';
                    if ($s['setting_key'] == 'app_name') $label = 'Portal Title';
                ?>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2"><?= $label ?></label>
                    <input type="text" name="settings[<?= $s['setting_key'] ?>]" value="<?= htmlspecialchars($s['setting_value']) ?>" required 
                           class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl outline-none focus:border-indigo-500 transition shadow-sm">
                    <?php if ($s['setting_key'] == 'app_url'): ?>
                        <p class="text-[10px] text-amber-500 mt-1 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Changing this might break links if not properly configured on server.</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="pt-4">
                    <button type="submit" name="save_settings" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-save shadow-sm"></i> Save Global Settings
                    </button>
                    <p class="text-center text-[10px] text-gray-400 mt-4 leading-relaxed italic">
                        * Changes will reflect in all headers, footers, and printable reports immediately.
                    </p>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
