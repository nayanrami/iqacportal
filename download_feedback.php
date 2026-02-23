<?php
/**
 * Download Feedback as PDF - Printable feedback report
 * Shows student info, department, college, all answers, and signature space
 */
require_once __DIR__ . '/includes/functions.php';

if (!isStudent()) {
    setFlash('danger', 'Please login to download your feedback.');
    redirect(APP_URL . '/login.php');
}

$responseId = intval($_GET['id'] ?? 0);
if (!$responseId) {
    setFlash('danger', 'Invalid response.');
    redirect(APP_URL);
}

$studentId = $_SESSION['student_id'];

// Fetch response with form, course, department details
$stmt = $pdo->prepare("
    SELECT r.*, ff.title as form_title, ff.form_type, ff.semester as form_semester,
           c.name as course_name, c.code as course_code, c.semester as course_semester,
           d.name as dept_name,
           s.name as student_name, s.enrollment_no, s.semester as student_semester
    FROM responses r
    JOIN feedback_forms ff ON ff.id = r.feedback_form_id
    LEFT JOIN courses c ON c.id = ff.course_id
    LEFT JOIN departments d ON d.id = ff.department_id
    JOIN students s ON s.id = r.student_id
    WHERE r.id = ? AND r.student_id = ?
");
$stmt->execute([$responseId, $studentId]);
$response = $stmt->fetch();

if (!$response) {
    setFlash('danger', 'Response not found or access denied.');
    redirect(APP_URL);
}

// Fetch answers with questions
$stmt = $pdo->prepare("
    SELECT ra.*, q.question_text, q.question_type, q.max_score,
           co.code as co_code, po.code as po_code
    FROM response_answers ra
    JOIN questions q ON q.id = ra.question_id
    LEFT JOIN course_outcomes co ON co.id = q.co_id
    LEFT JOIN program_outcomes po ON po.id = q.po_id
    WHERE ra.response_id = ?
    ORDER BY q.sort_order ASC
");
$stmt->execute([$responseId]);
$answers = $stmt->fetchAll();

$semester = $response['form_semester'] ?: $response['course_semester'] ?: $response['student_semester'];
$collegeName = APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Report - <?= sanitize($response['form_title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            background: #f8fafc;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Print-specific styles */
        @media print {
            body { background: white; }
            .container { padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
            .report { box-shadow: none; border: none; }
            .page-break { page-break-before: always; }
            @page { margin: 15mm; size: A4; }
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 16px 24px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .action-bar a, .action-bar button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-back { color: #6366f1; background: #eef2ff; }
        .btn-back:hover { background: #e0e7ff; }
        .btn-print { color: white; background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-print:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); }

        /* Report Card */
        .report {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* Header */
        .report-header {
            padding: 32px;
            background: linear-gradient(135deg, #f8faff, #eef2ff);
            border-bottom: 2px solid #e5e7eb;
            text-align: center;
        }

        .college-name {
            font-size: 22px;
            font-weight: 900;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .dept-name {
            font-size: 14px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 16px;
        }

        .report-title {
            font-size: 16px;
            font-weight: 800;
            color: #374151;
            padding: 8px 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            display: inline-block;
        }

        .report-meta {
            margin-top: 12px;
            font-size: 11px;
            color: #9ca3af;
            font-weight: 600;
        }

        /* Student Info */
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: #e5e7eb;
            border-bottom: 2px solid #e5e7eb;
        }

        .info-cell {
            padding: 12px 24px;
            background: white;
        }

        .info-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }

        /* Questions Table */
        .questions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .questions-table thead th {
            padding: 12px 20px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
        }

        .questions-table thead th:last-child,
        .questions-table thead th:nth-child(3) { text-align: center; }

        .questions-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .questions-table tbody tr:hover { background: #fafbfc; }
        .questions-table tbody tr:last-child { border-bottom: none; }

        .questions-table td {
            padding: 14px 20px;
            font-size: 13px;
            vertical-align: top;
        }

        .q-num {
            font-weight: 900;
            color: #6366f1;
            font-size: 12px;
        }

        .q-text { color: #374151; font-weight: 500; line-height: 1.5; }

        .q-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .badge-co { background: #f3e8ff; color: #7c3aed; }
        .badge-po { background: #fef3c7; color: #d97706; }

        .score-cell {
            text-align: center;
            font-weight: 800;
            font-size: 16px;
            color: #1f2937;
        }

        .max-score { font-size: 11px; color: #9ca3af; font-weight: 600; }

        /* Rating Stars */
        .stars { color: #fbbf24; font-size: 12px; letter-spacing: 2px; text-align: center; }
        .stars .empty { color: #e5e7eb; }

        /* Footer / Signature */
        .report-footer {
            padding: 32px;
            border-top: 2px solid #e5e7eb;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            border-radius: 12px;
            margin-bottom: 32px;
            border: 1px solid #e0e7ff;
        }

        .total-label { font-size: 14px; font-weight: 800; color: #4338ca; }
        .total-value { font-size: 24px; font-weight: 900; color: #1e1b4b; }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 48px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            width: 100%;
            border-bottom: 2px solid #1f2937;
            margin-bottom: 8px;
            height: 60px;
        }

        .signature-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .signature-name {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-top: 2px;
        }

        .timestamp {
            text-align: center;
            margin-top: 24px;
            font-size: 10px;
            color: #9ca3af;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Action Bar (hidden on print) -->
        <div class="action-bar no-print">
            <a href="<?= APP_URL ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Portal
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-download"></i> Download as PDF
            </button>
        </div>

        <!-- Report -->
        <div class="report">
            <!-- Header -->
            <div class="report-header">
                <div class="college-name"><?= sanitize($collegeName) ?></div>
                <div class="dept-name">Department of <?= sanitize($response['dept_name'] ?? 'General') ?></div>
                <div class="report-title">
                    <i class="fas fa-clipboard-check" style="color:#6366f1;margin-right:6px;"></i>
                    <?= sanitize($response['form_title']) ?>
                </div>
                <div class="report-meta">
                    <?= getFormTypeLabel($response['form_type']) ?>
                    <?php if ($response['course_name']): ?>
                        &nbsp;•&nbsp; <?= sanitize($response['course_code']) ?> - <?= sanitize($response['course_name']) ?>
                    <?php endif; ?>
                    &nbsp;•&nbsp; Semester <?= $semester ?>
                </div>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <div class="info-cell">
                    <div class="info-label">Student Name</div>
                    <div class="info-value"><?= sanitize($response['student_name']) ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Enrollment No.</div>
                    <div class="info-value"><?= sanitize($response['enrollment_no']) ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?= sanitize($response['dept_name'] ?? 'N/A') ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Date of Submission</div>
                    <div class="info-value"><?= date('d M Y, h:i A', strtotime($response['submitted_at'])) ?></div>
                </div>
            </div>

            <!-- Questions & Answers -->
            <table class="questions-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Question</th>
                        <th style="width:80px">Rating</th>
                        <th style="width:80px">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $totalScore = 0; $maxTotal = 0; foreach ($answers as $i => $a): 
                        $totalScore += $a['score'];
                        $maxTotal += $a['max_score'] ?: 5;
                    ?>
                    <tr>
                        <td class="q-num"><?= $i + 1 ?></td>
                        <td>
                            <div class="q-text"><?= sanitize($a['question_text']) ?></div>
                            <?php if ($a['co_code']): ?>
                                <span class="q-badge badge-co"><?= $a['co_code'] ?></span>
                            <?php endif; ?>
                            <?php if ($a['po_code']): ?>
                                <span class="q-badge badge-po"><?= $a['po_code'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="stars">
                                <?php 
                                $max = $a['max_score'] ?: 5;
                                for ($s = 1; $s <= $max; $s++): ?>
                                    <i class="fas fa-star <?= $s <= $a['score'] ? '' : 'empty' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td class="score-cell">
                            <?= $a['score'] ?><span class="max-score">/<?= $max ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="report-footer">
                <div class="total-row">
                    <div class="total-label">
                        <i class="fas fa-chart-bar" style="margin-right:8px;"></i>
                        Total Score
                    </div>
                    <div class="total-value">
                        <?= $totalScore ?> / <?= $maxTotal ?>
                        <span style="font-size:14px;color:#6366f1;margin-left:8px;">
                            (<?= $maxTotal > 0 ? round(($totalScore / $maxTotal) * 100, 1) : 0 ?>%)
                        </span>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Student's Signature</div>
                        <div class="signature-name"><?= sanitize($response['student_name']) ?></div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Department HOD</div>
                        <div class="signature-name">Dept. of <?= sanitize($response['dept_name'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <div class="timestamp">
                    Generated on <?= date('d M Y, h:i A') ?> • <?= sanitize($collegeName) ?> • NAAC Feedback System
                </div>
            </div>
        </div>
    </div>
</body>
</html>
