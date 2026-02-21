<?php
/**
 * IQAC Cell ADIT - Submit Feedback Form
 * Supports: CO Attainment (1–3), Exit Survey (1–5), Dept Feedback (1–5)
 */
require_once __DIR__ . '/functions.php';

$formId = intval($_GET['id'] ?? 0);
if (!$formId) { redirect(APP_URL); }

// Get form details
$stmtForm = $pdo->prepare("
    SELECT ff.*, c.name as course_name, c.code as course_code, c.semester as course_semester,
           d.name as dept_name, d.code as dept_code
    FROM feedback_forms ff
    LEFT JOIN courses c ON c.id = ff.course_id
    LEFT JOIN departments d ON d.id = ff.department_id
    WHERE ff.id = ? AND ff.is_active = 1
");
$stmtForm->execute([$formId]);
$form = $stmtForm->fetch();

if (!$form) {
    setFlash('danger', 'Feedback form not found or is currently inactive.');
    redirect(APP_URL);
}

// Get questions with CO/PO info
$stmtQ = $pdo->prepare("
    SELECT q.*, co.code as co_code, co.description as co_description,
           po.code as po_code, po.title as po_title, po.type as po_type, po.description as po_description
    FROM questions q
    LEFT JOIN course_outcomes co ON co.id = q.co_id
    LEFT JOIN program_outcomes po ON po.id = q.po_id
    WHERE q.feedback_form_id = ?
    ORDER BY q.sort_order ASC
");
$stmtQ->execute([$formId]);
$questions = $stmtQ->fetchAll();

if (empty($questions)) {
    setFlash('danger', 'This form has no questions configured.');
    redirect(APP_URL);
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = trim($_POST['student_name'] ?? '');
    $studentRoll = trim($_POST['student_roll'] ?? '');
    $scores = $_POST['scores'] ?? [];

    if (!empty($scores)) {
        try {
            $pdo->beginTransaction();

            $stmtR = $pdo->prepare("INSERT INTO responses (feedback_form_id, student_name, student_roll, ip_address) VALUES (?, ?, ?, ?)");
            $stmtR->execute([$formId, $studentName ?: null, $studentRoll ?: null, $_SERVER['REMOTE_ADDR'] ?? null]);
            $responseId = $pdo->lastInsertId();

            $stmtA = $pdo->prepare("INSERT INTO response_answers (response_id, question_id, score) VALUES (?, ?, ?)");
            foreach ($scores as $qId => $score) {
                $stmtA->execute([$responseId, intval($qId), intval($score)]);
            }

            $pdo->commit();
            setFlash('success', 'Your feedback has been submitted successfully!');
            redirect(APP_URL . '/thankyou.php?form=' . $formId);
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error submitting feedback. Please try again.');
        }
    } else {
        setFlash('danger', 'Please answer at least one question.');
    }
}

// Determine scale labels
$scaleLabels = [];
$formType = $form['form_type'];
if ($formType === 'co_attainment') {
    $scaleLabels = [1 => 'Low', 2 => 'Medium', 3 => 'High'];
} else {
    $scaleLabels = [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'];
}

// Get form type styling
$formColors = [
    'co_attainment' => ['from-violet-500 to-purple-600', 'violet', 'fa-bullseye'],
    'exit_survey' => ['from-amber-500 to-orange-600', 'amber', 'fa-door-open'],
    'dept_feedback' => ['from-emerald-500 to-teal-600', 'emerald', 'fa-building'],
    'general' => ['from-blue-500 to-indigo-600', 'blue', 'fa-clipboard-list'],
];
$colors = $formColors[$formType] ?? $formColors['general'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($form['title']) ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="font-sans min-h-screen">
    <div class="bg-animated"></div>

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200 px-8 h-[70px] flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= APP_URL ?>" class="flex items-center gap-3 text-gray-600 hover:text-indigo-600 transition">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-lg shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <h1 class="text-base font-bold text-gray-800"><?= APP_NAME ?></h1>
                    <p class="text-[10px] text-gray-400 font-medium"><?= APP_INSTITUTE ?></p>
                </div>
            </a>
        </div>
        <a href="<?= APP_URL ?>" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition">
            <i class="fas fa-arrow-left mr-1"></i> Back to Forms
        </a>
    </header>

    <div class="max-w-4xl mx-auto px-5 py-8">
        <!-- Flash messages -->
        <?php if ($flash = getFlash()): ?>
            <div class="flash-<?= $flash['type'] ?> mb-6 animate-slide-down">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Form Banner -->
        <div class="glass-card overflow-hidden mb-8 animate-slide-down">
            <div class="h-2 bg-gradient-to-r <?= $colors[0] ?>"></div>
            <div class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br <?= $colors[0] ?> rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg flex-shrink-0">
                        <i class="fas <?= $colors[2] ?>"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap gap-2 mb-1">
                            <span class="outcome-badge <?= $formType === 'co_attainment' ? 'co' : ($formType === 'exit_survey' ? 'po' : 'pso') ?>">
                                <?= getFormTypeLabel($formType) ?>
                            </span>
                            <?php if ($form['course_name']): ?>
                                <span class="outcome-badge co"><i class="fas fa-book mr-1"></i><?= sanitize($form['course_code']) ?></span>
                            <?php endif; ?>
                            <?php if ($form['semester']): ?>
                                <span class="outcome-badge peo"><i class="fas fa-layer-group mr-1"></i>Semester <?= $form['semester'] ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-1"><?= sanitize($form['title']) ?></h1>
                        <?php if ($form['description']): ?>
                            <p class="text-sm text-gray-500"><?= sanitize($form['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($form['dept_name']): ?>
                            <p class="text-xs text-gray-400 mt-1"><i class="fas fa-building mr-1"></i><?= sanitize($form['dept_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-center bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 flex-shrink-0">
                        <div class="text-2xl font-black text-gray-700"><?= count($questions) ?></div>
                        <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Questions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating Scale Legend -->
        <div class="glass-card p-4 mb-6 animate-slide-down" style="animation-delay:100ms">
            <div class="flex flex-wrap items-center gap-4 justify-center">
                <span class="text-xs font-semibold text-gray-500"><i class="fas fa-info-circle mr-1"></i>Rating Scale:</span>
                <?php foreach ($scaleLabels as $val => $label): ?>
                    <span class="flex items-center gap-1.5 text-xs">
                        <span class="w-6 h-6 bg-gradient-to-br <?= $colors[0] ?> text-white rounded-md flex items-center justify-center font-bold text-[10px]"><?= $val ?></span>
                        <span class="text-gray-500 font-medium"><?= $label ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="glass-card p-4 mb-6 animate-slide-down" style="animation-delay:150ms">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                <span class="font-semibold"><i class="fas fa-tasks mr-1"></i>Progress</span>
                <span id="progress-text">0 / <?= count($questions) ?> answered</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" id="progress-fill" style="width:0%"></div>
            </div>
        </div>

        <!-- Feedback Form -->
        <form method="POST" id="feedback-form">
            <!-- Student Info (optional) -->
            <div class="glass-card p-6 mb-6 animate-slide-down" style="animation-delay:200ms">
                <h3 class="text-sm font-bold text-gray-600 mb-4"><i class="fas fa-user text-gray-400 mr-2"></i>Student Information (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Full Name</label>
                        <input type="text" name="student_name" placeholder="Enter your name"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Enrollment / Roll Number</label>
                        <input type="text" name="student_roll" placeholder="e.g. 22IT001"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <?php foreach ($questions as $i => $q): ?>
                <div class="rating-group animate-slide-down" style="animation-delay:<?= ($i+3) * 50 ?>ms" id="q-group-<?= $q['id'] ?>">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="w-8 h-8 bg-gradient-to-br <?= $colors[0] ?> text-white rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"><?= $i + 1 ?></span>
                        <div class="flex-1">
                            <?php if ($q['co_code']): ?>
                                <span class="outcome-badge co mb-1"><i class="fas fa-bullseye mr-1"></i><?= sanitize($q['co_code']) ?></span>
                            <?php endif; ?>
                            <?php if ($q['po_code']): ?>
                                <span class="outcome-badge <?= $q['po_type'] === 'PSO' ? 'pso' : 'po' ?> mb-1"><i class="fas fa-tasks mr-1"></i><?= sanitize($q['po_code']) ?><?php if ($q['po_title']): ?> — <?= sanitize($q['po_title']) ?><?php endif; ?></span>
                            <?php endif; ?>
                            <p class="text-sm text-gray-700 font-medium leading-relaxed mt-1"><?= sanitize($q['question_text']) ?></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 ml-11">
                        <?php foreach ($scaleLabels as $val => $label): ?>
                            <label class="rating-option">
                                <input type="radio" name="scores[<?= $q['id'] ?>]" value="<?= $val ?>" onchange="updateProgress()">
                                <span class="rating-label" title="<?= $label ?>">
                                    <?= $val ?>
                                    <span class="ml-1 text-[10px] font-medium hidden md:inline"><?= $label ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Submit -->
            <div class="text-center py-8 animate-fade-in">
                <button type="submit"
                        class="px-10 py-4 bg-gradient-to-r <?= $colors[0] ?> text-white font-bold rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all text-sm">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                </button>
                <p class="text-xs text-gray-400 mt-3">Your responses are anonymous and will be used for NAAC accreditation purposes only.</p>
            </div>
        </form>
    </div>

    <script>
    const totalQuestions = <?= count($questions) ?>;

    function updateProgress() {
        const answered = document.querySelectorAll('#feedback-form input[type="radio"]:checked').length;
        const pct = Math.round((answered / totalQuestions) * 100);
        document.getElementById('progress-fill').style.width = pct + '%';
        document.getElementById('progress-text').textContent = answered + ' / ' + totalQuestions + ' answered';
    }

    // Highlight current question on focus
    document.querySelectorAll('.rating-group').forEach(group => {
        group.addEventListener('click', () => {
            document.querySelectorAll('.rating-group').forEach(g => g.style.borderColor = '');
            group.style.borderColor = 'rgba(99,102,241,0.3)';
        });
    });
    </script>
</body>
</html>
