<?php
/**
 * Thank You Page - Post submission confirmation
 */
require_once __DIR__ . '/includes/functions.php';

$formId = intval($_GET['form'] ?? 0);
$responseId = intval($_GET['response'] ?? 0);
$form = null;
if ($formId) {
    $stmt = $pdo->prepare("SELECT ff.*, c.name as course_name, d.name as dept_name FROM feedback_forms ff LEFT JOIN courses c ON c.id = ff.course_id LEFT JOIN departments d ON d.id = ff.department_id WHERE ff.id = ?");
    $stmt->execute([$formId]);
    $form = $stmt->fetch();
}
// If no response ID in URL, look up the most recent one for this student+form
if (!$responseId && $formId && isStudent()) {
    $stmt = $pdo->prepare("SELECT id FROM responses WHERE feedback_form_id = ? AND student_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$formId, $_SESSION['student_id']]);
    $row = $stmt->fetch();
    if ($row) $responseId = $row['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="font-sans min-h-screen flex items-center justify-center">
    <div class="bg-animated"></div>

    <div class="glass-card text-center max-w-lg mx-5 p-10 animate-scale-in">
        <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-green-500 rounded-3xl flex items-center justify-center text-white text-4xl shadow-xl shadow-emerald-500/20 mx-auto mb-6">
            <i class="fas fa-check"></i>
        </div>
        <h1 class="text-3xl font-black gradient-text mb-3">Thank You!</h1>
        <p class="text-gray-500 mb-2">Your feedback has been submitted successfully.</p>
        <?php if ($form): ?>
            <p class="text-sm text-gray-400 mb-6">
                <strong class="text-gray-600"><?= sanitize($form['title']) ?></strong>
                <?php if ($form['course_name']): ?> — <?= sanitize($form['course_name']) ?><?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-8">
            <p class="text-xs text-indigo-600 font-medium">
                <i class="fas fa-shield-alt mr-1"></i>
                Your responses are recorded anonymously for NAAC accreditation purposes.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= APP_URL ?>" class="btn-primary">
                <i class="fas fa-home"></i> Back to Forms
            </a>
            <?php if ($responseId): ?>
                <a href="<?= APP_URL ?>/download_feedback.php?id=<?= $responseId ?>" class="px-6 py-2.5 border-2 border-indigo-200 rounded-lg text-sm font-semibold text-indigo-600 hover:border-indigo-400 hover:bg-indigo-50 transition flex items-center gap-2">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Confetti Animation -->
    <script>
    const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#06b6d4'];
    for (let i = 0; i < 60; i++) {
        const c = document.createElement('div');
        c.className = 'confetti-piece';
        c.style.left = Math.random() * 100 + 'vw';
        c.style.background = colors[Math.floor(Math.random() * colors.length)];
        c.style.animationDuration = (2 + Math.random() * 2) + 's';
        c.style.animationDelay = Math.random() * 1.5 + 's';
        c.style.width = (6 + Math.random() * 8) + 'px';
        c.style.height = (6 + Math.random() * 8) + 'px';
        c.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        document.body.appendChild(c);
        setTimeout(() => c.remove(), 5000);
    }
    </script>
</body>
</html>
