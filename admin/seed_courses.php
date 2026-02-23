<?php
/**
 * ADIT IT Department - Course & NAAC Form Seeder
 * 
 * Imports all courses from the ADIT IT curriculum (Sem 1-8)
 * and generates Course Feedback + Course Exit Survey forms for each.
 */
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Seed IT Courses & NAAC Forms';

// All courses extracted from https://adit.ac.in IT department curriculum
$courses = [
    // ══════════════════ SEMESTER 1 ══════════════════
    ['code' => '202000104', 'name' => 'Calculus', 'sem' => 1],
    ['code' => '202000110', 'name' => 'Computer Programming with C', 'sem' => 1],
    ['code' => '202001203', 'name' => 'Basics of Electrical and Electronics Engineering', 'sem' => 1],
    ['code' => '202001206', 'name' => 'Constitution of India', 'sem' => 1],
    ['code' => '202001209', 'name' => 'Engineering Workshop', 'sem' => 1],
    ['code' => '202001215', 'name' => 'Professional Communication', 'sem' => 1],

    // ══════════════════ SEMESTER 2 ══════════════════
    ['code' => '202000211', 'name' => 'Linear Algebra, Vector Calculus and ODE', 'sem' => 2],
    ['code' => '202000212', 'name' => 'Object Oriented Programming', 'sem' => 2],
    ['code' => '202001202', 'name' => 'Basic Mechanical Engineering', 'sem' => 2],
    ['code' => '202001207', 'name' => 'Energy and Environment Science', 'sem' => 2],
    ['code' => '202001208', 'name' => 'Engineering Graphics', 'sem' => 2],
    ['code' => '202001213', 'name' => 'Physics', 'sem' => 2],

    // ══════════════════ SEMESTER 3 ══════════════════
    ['code' => '202000303', 'name' => 'Probability - Statistics and Numerical Methods', 'sem' => 3],
    ['code' => '202003402', 'name' => 'Fundamentals of Economics and Business Management', 'sem' => 3],
    ['code' => '202003403', 'name' => 'Indian Ethos and Value Education', 'sem' => 3],
    ['code' => '202040301', 'name' => 'Data Structures', 'sem' => 3],
    ['code' => '202040302', 'name' => 'Database Management Systems', 'sem' => 3],
    ['code' => '202040303', 'name' => 'Digital Fundamentals', 'sem' => 3],
    ['code' => '900009901', 'name' => 'Creativity, Problem Solving and Innovation', 'sem' => 3],

    // ══════════════════ SEMESTER 4 ══════════════════
    ['code' => '202003404', 'name' => 'Technical Writing and Soft Skills', 'sem' => 4],
    ['code' => '202003405', 'name' => 'Entrepreneur Skills', 'sem' => 4],
    ['code' => '202040401', 'name' => 'Computer Organization and Architecture', 'sem' => 4],
    ['code' => '202040402', 'name' => 'Operating Systems', 'sem' => 4],
    ['code' => '202040404', 'name' => 'Seminar', 'sem' => 4],
    ['code' => '202044501', 'name' => 'Computer Networks', 'sem' => 4],
    ['code' => '202044502', 'name' => 'Programming with Java', 'sem' => 4],

    // ══════════════════ SEMESTER 5 ══════════════════
    ['code' => '202044503', 'name' => 'Artificial Intelligence', 'sem' => 5],
    ['code' => '202044504', 'name' => 'Programming with Python', 'sem' => 5],
    ['code' => '202044505', 'name' => 'Web Development', 'sem' => 5],
    ['code' => '202045601', 'name' => 'Design and Analysis of Algorithm', 'sem' => 5],
    ['code' => '202045602', 'name' => 'Software Engineering', 'sem' => 5],
    ['code' => '202045604', 'name' => '.NET Technology', 'sem' => 5],
    ['code' => '202045605', 'name' => 'Advance Java Programming', 'sem' => 5],
    ['code' => '202045607', 'name' => 'Cyber Security', 'sem' => 5],

    // ══════════════════ SEMESTER 6 ══════════════════
    ['code' => '202040601', 'name' => 'Mini Project', 'sem' => 6],
    ['code' => '202045609', 'name' => 'Machine Learning', 'sem' => 6],
    ['code' => '202046701', 'name' => 'Advanced Web Development', 'sem' => 6],
    ['code' => '202046705', 'name' => 'Computer Vision and Image Processing', 'sem' => 6],
    ['code' => '202046706', 'name' => 'Data Mining and Business Intelligence', 'sem' => 6],
    ['code' => '202046708', 'name' => 'Information and Network Security', 'sem' => 6],
    ['code' => '202046709', 'name' => 'Internet of Things', 'sem' => 6],
    ['code' => '202046713', 'name' => 'Software Project Management', 'sem' => 6],

    // ══════════════════ SEMESTER 7 ══════════════════
    ['code' => '202000701', 'name' => 'Summer Training', 'sem' => 7],
    ['code' => '202046703', 'name' => 'Blockchain', 'sem' => 7],
    ['code' => '202046707', 'name' => 'Data Science and Visualization', 'sem' => 7],
    ['code' => '202046710', 'name' => 'Introduction to Cloud Computing', 'sem' => 7],
    ['code' => '202046711', 'name' => 'Language Processors', 'sem' => 7],
    ['code' => '202046712', 'name' => 'Mobile Application Development', 'sem' => 7],
    ['code' => '202046715', 'name' => 'UI/UX Design', 'sem' => 7],
    ['code' => '202047801', 'name' => 'Advanced Software Engineering', 'sem' => 7],
    ['code' => '202047803', 'name' => 'Big Data Analytics', 'sem' => 7],
    ['code' => '202047804', 'name' => 'Deep Learning and Applications', 'sem' => 7],
    ['code' => '202047808', 'name' => 'Management of IT Infrastructure', 'sem' => 7],

    // ══════════════════ SEMESTER 8 ══════════════════
    ['code' => '202000801', 'name' => 'Industrial Internship', 'sem' => 8],
    ['code' => '202047802', 'name' => 'Augmented Reality and Virtual Reality', 'sem' => 8],
    ['code' => '202047805', 'name' => 'Geographical Information Systems', 'sem' => 8],
    ['code' => '202047806', 'name' => 'High Performance Computing', 'sem' => 8],
    ['code' => '202047807', 'name' => 'Introduction to Software Defined Networking', 'sem' => 8],
    ['code' => '202047809', 'name' => 'Natural Language Processing', 'sem' => 8],
    ['code' => '202047810', 'name' => 'Service Oriented Computing', 'sem' => 8],
    ['code' => '202080801', 'name' => 'Industry/User Defined Project (IDP/UDP)', 'sem' => 8],
];

// NAAC-relevant feedback questions by form type
$courseFeedbackQuestions = [
    'The course objectives and learning outcomes were clearly communicated',
    'The course content is relevant to the field of study',
    'The syllabus covers both theoretical and practical aspects adequately',
    'The teaching methodology was effective and engaging',
    'Faculty was approachable and helpful for doubt clarification',
    'Internal assessments were fair and aligned with course objectives',
    'Course materials (textbooks, notes, PPTs) were adequate and accessible',
    'The course helped develop analytical and problem-solving skills',
    'Overall, I am satisfied with this course',
];

$courseExitQuestions = [
    'The course met the stated learning objectives',
    'The knowledge gained will be useful in my career or higher studies',
    'The course helped develop critical thinking skills',
    'Practical/lab components were relevant and well-organized',
    'The assessment methods reflected the course content fairly',
    'The course contributed to my overall academic and professional growth',
];

// ══════════ General Department-Level Feedback Forms ══════════
$generalForms = [
    [
        'title' => 'Teacher Evaluation – IT Department',
        'category' => 'Teacher Evaluation',
        'description' => 'NAAC Criterion 2 – Evaluate faculty teaching quality, preparedness, and student engagement in the IT Department',
        'questions' => [
            'The teacher demonstrates thorough knowledge of the subject',
            'The teacher comes well-prepared for lectures',
            'The teacher communicates concepts clearly and effectively',
            'The teacher uses modern teaching aids and ICT tools',
            'The teacher encourages student participation and discussion',
            'The teacher is punctual and completes the syllabus on time',
            'The teacher provides timely feedback on assignments and assessments',
            'The teacher is approachable and supportive for doubt resolution',
        ]
    ],
    [
        'title' => 'Infrastructure & Lab Facilities – IT Department',
        'category' => 'Laboratory Feedback',
        'description' => 'NAAC Criterion 4 – Feedback on IT labs, hardware, software, and computing infrastructure',
        'questions' => [
            'Computer labs are well-equipped with up-to-date hardware',
            'Licensed and relevant software tools are available in the labs',
            'Internet connectivity and speed in labs is adequate',
            'Lab assistants and technical staff are helpful and responsive',
            'Lab timings and accessibility meet student needs',
            'Safety measures and maintenance of lab equipment is satisfactory',
            'The lab environment is clean, well-ventilated, and comfortable',
        ]
    ],
    [
        'title' => 'Library & Learning Resources – IT Department',
        'category' => 'Library Feedback',
        'description' => 'NAAC Criterion 4 – Evaluate availability of books, e-resources, journals, and digital library for IT',
        'questions' => [
            'The library has adequate textbooks and reference materials for IT courses',
            'Digital resources (e-books, online journals, IEEE/ACM access) are available',
            'Library timings and seating capacity are adequate',
            'The library catalogue and search system is easy to use',
            'The library staff is helpful and responsive to requests',
            'New editions and recent publications are regularly updated',
        ]
    ],
    [
        'title' => 'Student Satisfaction Survey – IT Department',
        'category' => 'Student Satisfaction Survey',
        'description' => 'NAAC Criterion 5 – Overall student satisfaction with the IT program, facilities, and academic environment',
        'questions' => [
            'I am satisfied with the overall quality of education in the IT department',
            'The curriculum is industry-relevant and up-to-date',
            'The department provides adequate opportunities for skill development',
            'The department supports student participation in co-curricular activities',
            'Grievance redressal mechanisms are effective and accessible',
            'The department fosters a positive and inclusive learning environment',
            'I would recommend this department to others',
        ]
    ],
    [
        'title' => 'Program Exit Survey – B.Tech IT',
        'category' => 'Program Exit Survey',
        'description' => 'NAAC Criterion 2 – Graduating students evaluate overall program effectiveness and learning outcomes',
        'questions' => [
            'The B.Tech IT program met my academic expectations',
            'The program prepared me well for industry or higher education',
            'Program outcomes (POs) were effectively addressed through coursework',
            'The program provided good exposure to emerging technologies',
            'Internship and project opportunities were adequate and well-organized',
            'The program helped develop effective communication and teamwork skills',
            'Overall, I am satisfied with my B.Tech IT education at ADIT',
        ]
    ],
    [
        'title' => 'Program Outcome Attainment – B.Tech IT',
        'category' => 'Program Outcome Survey',
        'description' => 'NAAC Criterion 2 – Measure attainment of Program Outcomes (POs) and Program Specific Outcomes (PSOs)',
        'questions' => [
            'The program developed my ability to apply knowledge of computing and mathematics',
            'I can analyze complex computing problems and identify solutions',
            'I can design and develop software solutions for real-world problems',
            'The program developed my ability to conduct research and investigation',
            'I understand professional ethics and social responsibilities as an IT professional',
            'The program prepared me for lifelong learning and adaptability',
        ]
    ],
    [
        'title' => 'Mentoring & Counselling Feedback – IT Department',
        'category' => 'Mentoring & Counselling',
        'description' => 'NAAC Criterion 5 – Evaluate the effectiveness of mentoring, academic counselling, and student support',
        'questions' => [
            'A faculty mentor was assigned and accessible throughout the semester',
            'The mentor provided guidance on academic matters and career planning',
            'The mentor-mentee interaction frequency was adequate',
            'The department provides adequate support for slow learners',
            'Counselling services for personal and academic issues are effective',
            'The mentoring system helped me perform better academically',
        ]
    ],
    [
        'title' => 'Placement & Career Services – IT Department',
        'category' => 'Placement & Career Services',
        'description' => 'NAAC Criterion 5 – Feedback on placement training, campus drives, and career guidance for IT students',
        'questions' => [
            'The department provides adequate pre-placement training (aptitude, technical, soft skills)',
            'Campus placement drives are well-organized and frequent',
            'Career guidance and counselling sessions are helpful',
            'The department supports entrepreneurship and startup initiatives',
            'Industry interaction (guest lectures, workshops, hackathons) is adequate',
            'I am satisfied with the placement support provided by the department',
        ]
    ],
    [
        'title' => 'Co-curricular & Extra-curricular Activities – IT Department',
        'category' => 'Co-curricular Activities',
        'description' => 'NAAC Criterion 5 – Evaluate student clubs, technical events, hackathons, and extra-curricular activities',
        'questions' => [
            'The department organizes adequate technical events (hackathons, coding contests, workshops)',
            'Student clubs and committees are active and well-managed',
            'Opportunities for paper presentations and project exhibitions are provided',
            'The department supports participation in external competitions and conferences',
            'Sports and cultural activities are adequately promoted',
            'These activities helped develop leadership and teamwork skills',
        ]
    ],
    [
        'title' => 'ICT & Digital Facilities – IT Department',
        'category' => 'ICT Facilities Feedback',
        'description' => 'NAAC Criterion 4 – Evaluate ICT infrastructure, smart classrooms, LMS, and digital tools',
        'questions' => [
            'Smart classrooms with projectors and audio-visual aids are available',
            'Learning Management System (LMS/Moodle) is effectively used for course delivery',
            'Wi-Fi connectivity on campus is reliable and adequate',
            'Online resources and recorded lectures are accessible',
            'The department uses modern tools for assessments and feedback',
            'ERP/student portal provides useful academic information',
        ]
    ],
];

// Run seeder
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['run'])) {
    $pdo->beginTransaction();
    
    try {
        // Find IT department
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE code LIKE '%IT%' LIMIT 1");
        $stmt->execute();
        $dept = $stmt->fetch();
        
        if (!$dept) {
            throw new Exception("IT department not found. Please create it first.");
        }
        $deptId = $dept['id'];
        
        $courseCount = 0;
        $formCount = 0;
        $questionCount = 0;
        $skipped = 0;
        $generalFormCount = 0;
        
        $insertCourse = $pdo->prepare("INSERT INTO courses (department_id, name, code, semester) VALUES (?, ?, ?, ?)");
        $checkCourse = $pdo->prepare("SELECT id FROM courses WHERE code = ?");
        $insertForm = $pdo->prepare("INSERT INTO feedback_forms (title, category, department_id, course_id, semester, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $insertQ = $pdo->prepare("INSERT INTO questions (feedback_form_id, question_text, max_score, sort_order) VALUES (?, ?, 5, ?)");
        
        // ── Insert courses & course-specific forms ──
        foreach ($courses as $c) {
            $checkCourse->execute([$c['code']]);
            $existing = $checkCourse->fetch();
            
            if ($existing) {
                $courseId = $existing['id'];
                $skipped++;
            } else {
                $insertCourse->execute([$deptId, $c['name'], $c['code'], $c['sem']]);
                $courseId = $pdo->lastInsertId();
                $courseCount++;
            }
            
            // Course Feedback
            $title = "Course Feedback – {$c['name']}";
            $desc = "NAAC Criterion 2 – Evaluate teaching-learning process for {$c['name']} ({$c['code']}) – Semester {$c['sem']}";
            $insertForm->execute([$title, 'Course Feedback', $deptId, $courseId, $c['sem'], $desc]);
            $formId = $pdo->lastInsertId();
            $formCount++;
            foreach ($courseFeedbackQuestions as $i => $q) {
                $insertQ->execute([$formId, $q, $i + 1]);
                $questionCount++;
            }
            
            // Course Exit Survey
            $title2 = "Course Exit Survey – {$c['name']}";
            $desc2 = "NAAC Criterion 2 – Exit survey for {$c['name']} ({$c['code']}) – Semester {$c['sem']}";
            $insertForm->execute([$title2, 'Course Exit Survey', $deptId, $courseId, $c['sem'], $desc2]);
            $formId2 = $pdo->lastInsertId();
            $formCount++;
            foreach ($courseExitQuestions as $i => $q) {
                $insertQ->execute([$formId2, $q, $i + 1]);
                $questionCount++;
            }
        }
        
        // ── Insert general department-level forms ──
        foreach ($generalForms as $gf) {
            $insertForm->execute([$gf['title'], $gf['category'], $deptId, null, null, $gf['description']]);
            $gfId = $pdo->lastInsertId();
            $generalFormCount++;
            $formCount++;
            foreach ($gf['questions'] as $i => $q) {
                $insertQ->execute([$gfId, $q, $i + 1]);
                $questionCount++;
            }
        }
        
        $pdo->commit();
        setFlash('success', "Seeded successfully: {$courseCount} courses added ({$skipped} already existed), {$formCount} feedback forms created ({$generalFormCount} general dept forms), {$questionCount} questions generated.");
        header('Location: feedback_forms.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', "Seeding failed: " . $e->getMessage());
        header('Location: feedback_forms.php');
        exit;
    }
}

// Show confirmation page
require_once __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-database text-cyan-500"></i> Import ADIT IT Courses & Generate NAAC Forms
            </h3>
            <p class="text-sm text-gray-500 mt-1">Source: <a href="https://adit.ac.in/departments/department.php?dept=it&page=curriculum&level=UG&program=it" target="_blank" class="text-indigo-500 hover:underline">ADIT IT Curriculum Page</a></p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                    <div class="text-2xl font-black text-indigo-600"><?= count($courses) ?></div>
                    <div class="text-xs text-indigo-400 font-semibold mt-1">Courses</div>
                </div>
                <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 text-center">
                    <div class="text-2xl font-black text-purple-600">8</div>
                    <div class="text-xs text-purple-400 font-semibold mt-1">Semesters</div>
                </div>
                <div class="bg-cyan-50 border border-cyan-100 rounded-xl p-4 text-center">
                    <div class="text-2xl font-black text-cyan-600"><?= count($courses) * 2 ?></div>
                    <div class="text-xs text-cyan-400 font-semibold mt-1">Feedback Forms</div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center">
                    <div class="text-2xl font-black text-amber-600"><?= count($courses) * (count($courseFeedbackQuestions) + count($courseExitQuestions)) ?></div>
                    <div class="text-xs text-amber-400 font-semibold mt-1">Questions</div>
                </div>
            </div>
            
            <!-- Course List Preview -->
            <div>
                <h4 class="font-bold text-sm text-gray-600 mb-3"><i class="fas fa-list-ul text-gray-400 mr-1"></i> Course List Preview</h4>
                <div class="max-h-[400px] overflow-y-auto border border-gray-200 rounded-xl">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-400 uppercase">Sem</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-400 uppercase">Code</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-400 uppercase">Course Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $lastSem = 0; foreach ($courses as $c): ?>
                                <?php if ($c['sem'] !== $lastSem): $lastSem = $c['sem']; ?>
                                    <tr class="bg-indigo-50/50"><td colspan="3" class="px-3 py-1.5 text-xs font-bold text-indigo-500">Semester <?= $c['sem'] ?></td></tr>
                                <?php endif; ?>
                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-gray-400 font-mono text-xs"><?= $c['sem'] ?></td>
                                    <td class="px-3 py-1.5 font-mono text-xs text-indigo-600"><?= $c['code'] ?></td>
                                    <td class="px-3 py-1.5 text-gray-700"><?= $c['name'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Forms to generate -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4">
                <h4 class="font-bold text-sm text-green-700 mb-2"><i class="fas fa-clipboard-list text-green-500 mr-1"></i> Forms Generated Per Course</h4>
                <ul class="text-sm text-green-600 space-y-1">
                    <li><i class="fas fa-check-circle text-green-400 mr-1"></i> <strong>Course Feedback</strong> — <?= count($courseFeedbackQuestions) ?> questions (NAAC Criterion 2)</li>
                    <li><i class="fas fa-check-circle text-green-400 mr-1"></i> <strong>Course Exit Survey</strong> — <?= count($courseExitQuestions) ?> questions (NAAC Criterion 2)</li>
                </ul>
            </div>
            
            <!-- Action -->
            <div class="flex items-center gap-4">
                <a href="?run=1" class="px-8 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/40 hover:-translate-y-0.5 transition-all text-sm">
                    <i class="fas fa-rocket mr-2"></i> Import All Courses & Generate Forms
                </a>
                <a href="feedback_forms.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm font-semibold text-gray-500 hover:text-gray-700 hover:border-gray-300 transition">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
