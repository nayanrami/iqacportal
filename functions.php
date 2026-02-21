<?php
/**
 * NAAC Feedback System - Shared Functions
 * v2.0 - CO Attainment | PO Exit Survey | Department Feedback
 */

require_once __DIR__ . '/config.php';

function isLoggedIn() {
    return isset($_SESSION['admin_id']) || isset($_SESSION['student_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function isStudent() {
    return isset($_SESSION['student_id']);
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Administrative login required.');
        redirect(APP_URL . '/login.php');
    }
}

function requireStudent() {
    if (!isStudent()) {
        setFlash('danger', 'Please log in to access the portal.');
        redirect(APP_URL . '/login.php');
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash($type = null) {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        if ($type !== null) {
            if ($flash['type'] === $type) {
                unset($_SESSION['flash']);
                return $flash['message'];
            }
            return null;
        }
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Dashboard Stats ──
function getDashboardStats($pdo, $deptId = null) {
    $stats = [];
    $where = $deptId ? " WHERE department_id = " . intval($deptId) : "";
    
    $stats['total_forms'] = $pdo->query("SELECT COUNT(*) as c FROM feedback_forms" . $where)->fetch()['c'];
    $stats['active_forms'] = $pdo->query("SELECT COUNT(*) as c FROM feedback_forms" . ($deptId ? $where . " AND is_active = 1" : " WHERE is_active = 1"))->fetch()['c'];
    
    // Joint queries with alias ff
    $whereAlias = $deptId ? " WHERE ff.department_id = " . intval($deptId) : "";
    
    $respQuery = "SELECT COUNT(DISTINCT r.id) as c FROM responses r JOIN feedback_forms ff ON ff.id = r.feedback_form_id" . $whereAlias;
    $stats['total_responses'] = $pdo->query($respQuery)->fetch()['c'];
    
    $scoreQuery = "SELECT COALESCE(ROUND(AVG(ra.score), 2), 0) as v FROM response_answers ra JOIN responses r ON r.id = ra.response_id JOIN feedback_forms ff ON ff.id = r.feedback_form_id" . $whereAlias;
    $stats['avg_score'] = $pdo->query($scoreQuery)->fetch()['v'];
    
    $stats['total_courses'] = $pdo->query("SELECT COUNT(*) as c FROM courses" . $where)->fetch()['c'];
    $stats['total_departments'] = $pdo->query("SELECT COUNT(*) as c FROM departments")->fetch()['c'];
    
    $stats['co_forms'] = $pdo->query("SELECT COUNT(*) as c FROM feedback_forms WHERE form_type='co_attainment'" . ($deptId ? " AND department_id = " . intval($deptId) : ""))->fetch()['c'];
    $stats['exit_forms'] = $pdo->query("SELECT COUNT(*) as c FROM feedback_forms WHERE form_type='exit_survey'" . ($deptId ? " AND department_id = " . intval($deptId) : ""))->fetch()['c'];
    $stats['dept_forms'] = $pdo->query("SELECT COUNT(*) as c FROM feedback_forms WHERE form_type='dept_feedback'" . ($deptId ? " AND department_id = " . intval($deptId) : ""))->fetch()['c'];
    
    return $stats;
}

// ── NAAC Attainment Index ──
function getAttainmentLevel($percentage) {
    if ($percentage >= 66) return ['level' => 3, 'label' => 'Level 3', 'class' => 'high', 'color' => '#10b981', 'badge' => 'bg-emerald-100 text-emerald-700'];
    if ($percentage >= 50) return ['level' => 2, 'label' => 'Level 2', 'class' => 'medium', 'color' => '#f59e0b', 'badge' => 'bg-amber-100 text-amber-700'];
    if ($percentage >= 33) return ['level' => 1, 'label' => 'Level 1', 'class' => 'low', 'color' => '#ef4444', 'badge' => 'bg-red-100 text-red-700'];
    return ['level' => 0, 'label' => 'Level 0', 'class' => 'low', 'color' => '#6b7280', 'badge' => 'bg-gray-100 text-gray-700'];
}

// ── Course-wise average scores ──
function getCourseWiseScores($pdo, $deptId = null) {
    $sql = "
        SELECT c.name as course_name, c.code as course_code,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score,
               COUNT(DISTINCT r.id) as response_count
        FROM courses c
        LEFT JOIN feedback_forms ff ON ff.course_id = c.id
        LEFT JOIN responses r ON r.feedback_form_id = ff.id
        LEFT JOIN response_answers ra ON ra.response_id = r.id
    ";
    $where = $deptId ? " WHERE c.department_id = ?" : "";
    $sql .= $where . " GROUP BY c.id, c.name, c.code HAVING response_count > 0 ORDER BY avg_score DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($deptId ? [$deptId] : []);
    return $stmt->fetchAll();
}

// ── Dept response distribution ──
function getDeptResponseDistribution($pdo) {
    $stmt = $pdo->query("
        SELECT d.name as dept_name, d.code as dept_code,
               COUNT(DISTINCT r.id) as response_count
        FROM departments d
        LEFT JOIN feedback_forms ff ON ff.department_id = d.id
        LEFT JOIN responses r ON r.feedback_form_id = ff.id
        GROUP BY d.id, d.name, d.code
        ORDER BY response_count DESC
    ");
    return $stmt->fetchAll();
}

// ── Monthly response trends ──
function getMonthlyTrends($pdo, $deptId = null) {
    $sql = "
        SELECT DATE_FORMAT(r.submitted_at, '%Y-%m') as month,
               COUNT(*) as response_count
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        WHERE r.submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    ";
    if ($deptId) $sql .= " AND ff.department_id = " . intval($deptId);
    $sql .= " GROUP BY month ORDER BY month ASC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// ── Form analytics ──
function getFormAnalytics($pdo, $formId) {
    $stmt = $pdo->prepare("
        SELECT q.id, q.question_text, q.question_type, q.max_score, q.sort_order,
               q.co_id, q.po_id,
               co.code as co_code, co.description as co_desc,
               po.code as po_code, po.title as po_title,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score,
               COALESCE(MIN(ra.score), 0) as min_score,
               COALESCE(MAX(ra.score), 0) as max_score_given,
               COUNT(ra.id) as answer_count
        FROM questions q
        LEFT JOIN course_outcomes co ON co.id = q.co_id
        LEFT JOIN program_outcomes po ON po.id = q.po_id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        WHERE q.feedback_form_id = ?
        GROUP BY q.id, q.question_text, q.question_type, q.max_score, q.sort_order,
                 q.co_id, q.po_id, co.code, co.description, po.code, po.title
        ORDER BY q.sort_order ASC
    ");
    $stmt->execute([$formId]);
    return $stmt->fetchAll();
}

// ── Score distribution ──
function getScoreDistribution($pdo, $questionId) {
    $stmt = $pdo->prepare("
        SELECT score, COUNT(*) as count
        FROM response_answers WHERE question_id = ?
        GROUP BY score ORDER BY score ASC
    ");
    $stmt->execute([$questionId]);
    return $stmt->fetchAll();
}

// ── Recent responses ──
function getRecentResponses($pdo, $limit = 10, $deptId = null) {
    $sql = "
        SELECT r.*, ff.title as form_title, ff.form_type, c.name as course_name
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN courses c ON c.id = ff.course_id
    ";
    if ($deptId) $sql .= " WHERE ff.department_id = " . intval($deptId);
    $sql .= " ORDER BY r.submitted_at DESC LIMIT " . intval($limit);
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// ── NAAC metrics ──
function getNAACMetrics($pdo, $deptId = null) {
    $sql = "
        SELECT 
            COALESCE(ROUND(AVG(ra.score), 2), 0) as overall_avg,
            COALESCE(ROUND(AVG(ra.score / q.max_score) * 100, 1), 0) as satisfaction_pct,
            COUNT(DISTINCT r.id) as total_responses,
            COUNT(DISTINCT ff.id) as forms_evaluated
        FROM response_answers ra
        JOIN questions q ON q.id = ra.question_id
        JOIN responses r ON r.id = ra.response_id
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
    ";
    if ($deptId) $sql .= " WHERE ff.department_id = " . intval($deptId);
    
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}

// ── Get Unique Academic Years ──
function getYears($pdo) {
    return $pdo->query("SELECT DISTINCT year FROM courses WHERE year IS NOT NULL ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
}

// ── CO Attainment by Department + Semester + Year ──
function getCOAttainmentByDept($pdo, $deptId, $semester = null, $year = null) {
    $sql = "
        SELECT c.id as course_id, c.code as course_code, c.name as course_name, c.semester, c.year,
               co.id as co_id, co.code as co_code, co.description as co_desc,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score,
               q.max_score, COUNT(ra.id) as total_responses
        FROM courses c
        JOIN course_outcomes co ON co.course_id = c.id
        LEFT JOIN questions q ON q.co_id = co.id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        WHERE c.department_id = ?
    ";
    $params = [$deptId];
    if ($semester) {
        $sql .= " AND c.semester = ?";
        $params[] = $semester;
    }
    if ($year) {
        $sql .= " AND c.year = ?";
        $params[] = $year;
    }
    $sql .= " GROUP BY c.id, c.code, c.name, c.semester, c.year, co.id, co.code, co.description, q.max_score
              ORDER BY c.year DESC, c.semester, c.code, co.sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── PO Attainment Stats (with year filter) ──
function getPOAttainmentStats($pdo, $deptId = null, $year = null) {
    $sql = "
        SELECT po.id, po.type, po.code, po.title, po.description,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score,
               COUNT(ra.id) as total_responses,
               q.max_score
        FROM program_outcomes po
        LEFT JOIN questions q ON q.po_id = po.id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        LEFT JOIN feedback_forms ff ON ff.id = q.feedback_form_id
        LEFT JOIN courses c ON c.id = ff.course_id
    ";
    $where = [];
    $params = [];
    if ($deptId) {
        $where[] = "po.department_id = ?";
        $params[] = $deptId;
    }
    if ($year) {
        $where[] = "c.year = ?";
        $params[] = $year;
    }
    
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    
    $sql .= " GROUP BY po.id, po.type, po.code, po.title, po.description, q.max_score ORDER BY po.type, po.sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Form Type Analysis ──
function getFormTypeAnalysis($pdo, $deptId, $year = null) {
    $sql = "
        SELECT ff.id as form_id, ff.title, ff.form_type, ff.category,
               q.id as q_id, q.question_text, q.sort_order, q.max_score,
               COALESCE(ROUND(AVG(ra.score), 2), 0) as avg_score,
               COUNT(ra.id) as answer_count,
               COUNT(DISTINCT r.id) as response_count
        FROM feedback_forms ff
        JOIN questions q ON q.feedback_form_id = ff.id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        LEFT JOIN responses r ON r.id = ra.response_id
        LEFT JOIN courses c ON c.id = ff.course_id
        WHERE ff.department_id = ?
    ";
    $params = [$deptId];
    if ($year) {
        $sql .= " AND c.year = ?";
        $params[] = $year;
    }
    $sql .= " GROUP BY ff.id, ff.title, ff.form_type, ff.category, q.id, q.question_text, q.sort_order, q.max_score
              ORDER BY ff.form_type, ff.title, q.sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── NAAC Consolidated Report ──
function getNAACCriterionReport($pdo, $deptId, $year = null) {
    $report = [];
    
    // Base WHERE clause and params for filtering
    $where = "ff.department_id = ?";
    $params = [$deptId];
    if ($year) {
        $where .= " AND c.year = ?";
        $params[] = $year;
    }

    // Overall metrics
    $stmt = $pdo->prepare("
        SELECT COALESCE(ROUND(AVG(ra.score / q.max_score) * 100, 1), 0) as satisfaction_pct,
               COUNT(DISTINCT r.id) as total_responses,
               COUNT(DISTINCT ff.id) as forms_used
        FROM response_answers ra
        JOIN questions q ON q.id = ra.question_id
        JOIN responses r ON r.id = ra.response_id
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN courses c ON c.id = ff.course_id
        WHERE $where
    ");
    $stmt->execute($params);
    $report['overall'] = $stmt->fetch();

    // Per form type summary
    $stmt = $pdo->prepare("
        SELECT ff.form_type,
               COUNT(DISTINCT ff.id) as form_count,
               COUNT(DISTINCT r.id) as response_count,
               COALESCE(ROUND(AVG(ra.score / q.max_score) * 100, 1), 0) as satisfaction_pct
        FROM feedback_forms ff
        LEFT JOIN questions q ON q.feedback_form_id = ff.id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        LEFT JOIN responses r ON r.id = ra.response_id
        LEFT JOIN courses c ON c.id = ff.course_id
        WHERE $where
        GROUP BY ff.form_type ORDER BY ff.form_type
    ");
    $stmt->execute($params);
    $report['by_type'] = $stmt->fetchAll();

    // CO attainment summary
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id) as courses_with_co,
               COUNT(DISTINCT co.id) as total_cos,
               COALESCE(ROUND(AVG(ra.score / q.max_score) * 100, 1), 0) as avg_attainment_pct
        FROM courses c
        JOIN course_outcomes co ON co.course_id = c.id
        LEFT JOIN questions q ON q.co_id = co.id
        LEFT JOIN response_answers ra ON ra.question_id = q.id
        WHERE c.department_id = ? " . ($year ? " AND c.year = ?" : "") . "
    ");
    $stmt->execute($params);
    $report['co_summary'] = $stmt->fetch();

    return $report;
}

// ── Individual Response Details ──
function getResponseDetails($pdo, $responseId) {
    $stmt = $pdo->prepare("
        SELECT r.*, ff.title as form_title, ff.form_type, d.name as dept_name, c.name as course_name, c.code as course_code
        FROM responses r
        JOIN feedback_forms ff ON ff.id = r.feedback_form_id
        LEFT JOIN departments d ON d.id = ff.department_id
        LEFT JOIN courses c ON c.id = ff.course_id
        WHERE r.id = ?
    ");
    $stmt->execute([$responseId]);
    $response = $stmt->fetch();
    
    if ($response) {
        $stmt = $pdo->prepare("
            SELECT ra.*, q.question_text, q.question_type, q.max_score, co.code as co_code, po.code as po_code
            FROM response_answers ra
            JOIN questions q ON q.id = ra.question_id
            LEFT JOIN course_outcomes co ON co.id = q.co_id
            LEFT JOIN program_outcomes po ON po.id = q.po_id
            WHERE ra.response_id = ?
            ORDER BY q.sort_order ASC
        ");
        $stmt->execute([$responseId]);
        $response['answers'] = $stmt->fetchAll();
    }
    
    return $response;
}

// ── Form type labels ──
function getFormTypeLabel($type) {
    $labels = [
        'co_attainment' => 'CO Attainment',
        'exit_survey' => 'Exit Survey',
        'dept_feedback' => 'Department Feedback',
        'general' => 'General'
    ];
    return $labels[$type] ?? 'General';
}

function getFormTypeColor($type) {
    $colors = [
        'co_attainment' => 'from-violet-500 to-purple-600',
        'exit_survey' => 'from-amber-500 to-orange-600',
        'dept_feedback' => 'from-emerald-500 to-teal-600',
        'general' => 'from-blue-500 to-indigo-600'
    ];
    return $colors[$type] ?? 'from-blue-500 to-indigo-600';
}

function getFormTypeIcon($type) {
    $icons = [
        'co_attainment' => 'fa-bullseye',
        'exit_survey' => 'fa-door-open',
        'dept_feedback' => 'fa-building',
        'general' => 'fa-clipboard-list'
    ];
    return $icons[$type] ?? 'fa-clipboard-list';
}

// ── Semester-wise feedback completion stats ──
function getSemesterWiseStats($pdo, $deptId = null) {
    $where = $deptId ? "AND ff.department_id = ?" : "";
    $params = $deptId ? [$deptId] : [];
    
    $sql = "
        SELECT 
            COALESCE(ff.semester, c.semester, 0) as semester,
            COUNT(DISTINCT ff.id) as total_forms,
            COUNT(DISTINCT r.id) as total_responses,
            COUNT(DISTINCT r.student_id) as unique_students,
            COUNT(DISTINCT s.id) as total_students_in_sem,
            ROUND(AVG(ra.score), 1) as avg_score
        FROM feedback_forms ff
        LEFT JOIN courses c ON c.id = ff.course_id
        LEFT JOIN responses r ON r.feedback_form_id = ff.id
        LEFT JOIN response_answers ra ON ra.response_id = r.id
        LEFT JOIN students s ON s.department_id = ff.department_id AND s.semester >= COALESCE(ff.semester, c.semester, 0)
        WHERE ff.is_active = 1 $where
        GROUP BY COALESCE(ff.semester, c.semester, 0)
        ORDER BY semester ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Student completion tracking ──
function getStudentCompletionStatus($pdo, $deptId = null, $semFilter = null) {
    $where = "WHERE 1=1";
    $params = [];
    if ($deptId) { $where .= " AND s.department_id = ?"; $params[] = $deptId; }
    if ($semFilter) { $where .= " AND s.semester = ?"; $params[] = $semFilter; }
    
    $sql = "
        SELECT 
            s.id, s.enrollment_no, s.name, s.semester, d.name as dept_name,
            (SELECT COUNT(DISTINCT ff2.id) 
             FROM feedback_forms ff2 
             LEFT JOIN courses c2 ON c2.id = ff2.course_id
             WHERE ff2.department_id = s.department_id 
             AND ff2.is_active = 1
             AND (COALESCE(ff2.semester, c2.semester) IS NULL OR COALESCE(ff2.semester, c2.semester) <= s.semester)
            ) as total_forms,
            (SELECT COUNT(DISTINCT r2.feedback_form_id) 
             FROM responses r2 
             JOIN feedback_forms ff3 ON ff3.id = r2.feedback_form_id
             WHERE r2.student_id = s.id
            ) as completed_forms
        FROM students s
        JOIN departments d ON d.id = s.department_id
        $where
        ORDER BY s.semester ASC, s.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Subject-wise completion per semester ──
function getSubjectWiseStats($pdo, $deptId = null, $semFilter = null) {
    $where = "WHERE ff.is_active = 1";
    $params = [];
    if ($deptId) { $where .= " AND ff.department_id = ?"; $params[] = $deptId; }
    if ($semFilter) { $where .= " AND COALESCE(ff.semester, c.semester) = ?"; $params[] = $semFilter; }
    
    $sql = "
        SELECT 
            ff.id as form_id, ff.title, ff.form_type,
            COALESCE(c.name, 'N/A') as course_name,
            COALESCE(c.code, '') as course_code,
            COALESCE(ff.semester, c.semester, 0) as semester,
            COUNT(DISTINCT r.id) as response_count,
            (SELECT COUNT(*) FROM students st 
             WHERE st.department_id = ff.department_id 
             AND st.semester >= COALESCE(ff.semester, c.semester, 0)
            ) as eligible_students,
            ROUND(AVG(ra.score), 1) as avg_score
        FROM feedback_forms ff
        LEFT JOIN courses c ON c.id = ff.course_id
        LEFT JOIN responses r ON r.feedback_form_id = ff.id
        LEFT JOIN response_answers ra ON ra.response_id = r.id
        $where
        GROUP BY ff.id
        ORDER BY semester ASC, course_name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
