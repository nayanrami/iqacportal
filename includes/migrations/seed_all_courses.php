<?php
/**
 * Final Institutional Seeder - ADIT
 * Uses extracted IT data and fallback branch data.
 */
require_once __DIR__ . '/../functions.php';
set_time_limit(600);

echo "Starting Final Institutional Seeding...\n";

// 1. Get Department Mapping
$deptMap = [];
foreach ($pdo->query("SELECT id, code FROM departments") as $row) {
    $deptMap[strtolower($row['code'])] = $row['id'];
}

// 2. Load IT Curriculum from JSON
$jsonPath = __DIR__ . '/../../it_courses_utf8.json';
if (!file_exists($jsonPath)) {
    echo "ERROR: JSON file not found at $jsonPath\n";
    $itCourses = [];
} else {
    $itCoursesRaw = file_get_contents($jsonPath);
    // Remove UTF-8 BOM if present
    $itCoursesRaw = str_replace("\xEF\xBB\xBF", "", $itCoursesRaw);
    $itCourses = json_decode($itCoursesRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: JSON Decode Error: " . json_last_error_msg() . "\n";
        $itCourses = [];
    }
}
$itCoursesCount = count($itCourses ?: []);
echo "Loaded $itCoursesCount IT courses.\n";

echo "Department Mapping:\n";
foreach ($deptMap as $c => $id) {
    echo " - $c => $id\n";
}

// 3. Fallback Curricula for other departments
$branchCurricula = [
    'cp' => [
        ['code' => '202040301', 'name' => 'Data Structures', 'sem' => 3],
        ['code' => '202040302', 'name' => 'Database Management Systems', 'sem' => 3],
        ['code' => '202040401', 'name' => 'Computer Organization', 'sem' => 4],
        ['code' => '202040402', 'name' => 'Operating Systems', 'sem' => 4],
        ['code' => '202045601', 'name' => 'Design & Analysis of Algorithms', 'sem' => 5],
        ['code' => '202045602', 'name' => 'Software Engineering', 'sem' => 5],
    ],
    'ai' => [
        ['code' => '202050301', 'name' => 'Discrete Mathematics', 'sem' => 3],
        ['code' => '202050302', 'name' => 'AI Fundamentals', 'sem' => 3],
        ['code' => '202050401', 'name' => 'Machine Learning', 'sem' => 4],
    ],
    // ... add more if needed
];

$fallbackCommon = [
    ['code' => 'BTECH-PROJ', 'name' => 'Major Project / IDP', 'sem' => 7],
    ['code' => 'BTECH-INT', 'name' => 'Industrial Training / Internship', 'sem' => 8],
];

// 4. Prepare Statements
$courseAdd = $pdo->prepare("INSERT INTO courses (department_id, name, code, semester) 
    VALUES (:dept_id, :name, :code, :semester) 
    ON DUPLICATE KEY UPDATE name = :name_upd, semester = :semester_upd");

$formInsert = $pdo->prepare("INSERT IGNORE INTO feedback_forms (title, form_type, category, department_id, course_id, semester, is_active) 
    VALUES (:title, 'co_attainment', 'Course Feedback', :dept_id, :course_id, :semester, 1)");

$qInsert = $pdo->prepare("INSERT IGNORE INTO questions (feedback_form_id, question_text, max_score, sort_order) 
    VALUES (:form_id, :question_text, 5, :sort_order)");

$questions = [
    'The course objectives were clearly defined',
    'The subject matter was covered in adequate depth',
    'The teaching methodology helped in understanding complex concepts',
    'Practical demonstrations/labs were synchronized with theory',
    'I feel confident in applying these concepts to real-world problems'
];

try {
    $pdo->beginTransaction();

    foreach ($deptMap as $code => $id) {
        echo "Processing Department: " . strtoupper($code) . "...\n";
        
        $curriculum = [];
        if ($code === 'it' || $code === 'btech-it') {
            $curriculum = $itCourses;
        } else {
            // Mix of branch specific and fallback
            $curriculum = array_merge($branchCurricula[$code] ?? [], $fallbackCommon);
        }

        foreach ($curriculum as $c) {
            try {
                // Add/Update Course
                $courseAdd->execute([
                    ':dept_id' => $id,
                    ':name' => $c['name'],
                    ':code' => $c['code'],
                    ':semester' => $c['sem'],
                    ':name_upd' => $c['name'],
                    ':semester_upd' => $c['sem']
                ]);
                
                // Get ID
                $stmt = $pdo->prepare("SELECT id FROM courses WHERE code = ? AND department_id = ?");
                $stmt->execute([$c['code'], $id]);
                $courseId = $stmt->fetchColumn();

                if ($courseId) {
                    // Create Feedback Form
                    $formTitle = "CO Attainment: " . $c['name'] . " (" . $c['code'] . ")";
                    $formInsert->execute([
                        ':title' => $formTitle,
                        ':dept_id' => $id,
                        ':course_id' => $courseId,
                        ':semester' => $c['sem']
                    ]);
                    
                    // Get form ID
                    $stmt = $pdo->prepare("SELECT id FROM feedback_forms WHERE title = ? AND course_id = ?");
                    $stmt->execute([$formTitle, $courseId]);
                    $formId = $stmt->fetchColumn();
                    
                    if ($formId) {
                        // Check if questions already exist
                        $count = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE feedback_form_id = ?");
                        $count->execute([$formId]);
                        if ($count->fetchColumn() == 0) {
                            foreach ($questions as $idx => $q) {
                                $qInsert->execute([
                                    ':form_id' => $formId,
                                    ':question_text' => $q,
                                    ':sort_order' => $idx + 1
                                ]);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                echo "Error inserting course {$c['code']}: " . $e->getMessage() . "\n";
            }
        }
        echo " - Seeded/Updated " . count($curriculum) . " courses and forms.\n";
    }

    $pdo->commit();
    echo "\nSUCCESS: Seeding complete for all departments.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
