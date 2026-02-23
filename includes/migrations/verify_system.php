<?php
require_once __DIR__ . '/../functions.php';

echo "--- SYSTEM VERIFICATION REPORT ---\n";

$counts = [
    'Departments' => 'departments',
    'Admins' => 'admins',
    'Courses' => 'courses',
    'Faculties' => 'faculties',
    'Feedback Forms' => 'feedback_forms',
    'Questions' => 'questions',
    'Students' => 'students',
    'Course Mapping' => 'faculty_course_mapping'
];

foreach ($counts as $label => $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    echo str_pad($label . ":", 20) . " $count\n";
}

echo "----------------------------------\n";
echo "Integrity Check: ";
$unmappedCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE department_id NOT IN (SELECT id FROM departments)")->fetchColumn();
echo ($unmappedCourses == 0 ? "PASSED" : "FAILED (Unmapped Courses: $unmappedCourses)") . "\n";
?>
