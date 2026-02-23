<?php
/**
 * NAAC Criteria-wise Feedback Forms Seeder
 * Creates comprehensive feedback forms for all 7 NAAC Criteria
 */
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$seeded = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed'])) {

    // ======================================================================
    // NAAC CRITERION 1: Curricular Aspects
    // ======================================================================
    $forms = [];

    $forms[] = [
        'title' => 'Curriculum Design & Relevance Feedback',
        'category' => 'Curriculum Feedback',
        'description' => 'NAAC Criterion 1 – Evaluate curriculum design, industry relevance, and academic flexibility',
        'questions' => [
            'The curriculum is well-structured and logically sequenced',
            'The curriculum is aligned with the program objectives and outcomes',
            'The curriculum incorporates recent developments and emerging trends in the field',
            'There is adequate flexibility to choose elective/open elective courses',
            'Cross-cutting issues (Gender, Environment, Sustainability, Human Values, Professional Ethics) are integrated',
            'The curriculum provides scope for experiential learning (projects, internships, fieldwork)',
            'The syllabus content is relevant to industry needs and employability',
            'Value-added courses and certificate programs complement the curriculum',
            'Curriculum revision is done periodically based on stakeholder feedback',
            'Overall satisfaction with the curriculum design'
        ]
    ];

    $forms[] = [
        'title' => 'Course Feedback (Theory)',
        'category' => 'Course Feedback',
        'description' => 'NAAC Criterion 1 – Student feedback on individual theory course delivery and content',
        'questions' => [
            'The course objectives were clearly communicated at the beginning',
            'The course content is adequate and well-organized',
            'The course has a good balance between theoretical concepts and practical applications',
            'The teaching materials (textbooks, references, handouts) are appropriate and accessible',
            'The course enhanced your subject knowledge and intellectual skills',
            'Assessment methods (tests, assignments, projects) are fair and comprehensive',
            'The difficulty level of the course is appropriate for the semester',
            'The course helped in developing problem-solving and analytical abilities',
            'Overall satisfaction with this course'
        ]
    ];

    $forms[] = [
        'title' => 'Curriculum Design & Development Feedback',
        'category' => 'Curriculum Design & Development',
        'description' => 'NAAC Criterion 1 – Feedback on curriculum development process and Board of Studies',
        'questions' => [
            'Stakeholder involvement (industry, alumni, parents) in curriculum design is adequate',
            'The curriculum follows the guidelines of regulatory bodies (UGC, AICTE, University)',
            'Program-specific outcomes are clearly defined and achievable',
            'Courses are well-mapped to Program Outcomes (POs) and Program Specific Outcomes (PSOs)',
            'The gap between academia and industry is addressed in the curriculum',
            'The curriculum promotes multidisciplinary and interdisciplinary learning',
            'Add-on and value-added courses offered supplement the main curriculum',
            'Feedback from employers and alumni is considered in curriculum revision'
        ]
    ];

    $forms[] = [
        'title' => 'Value Added Courses Feedback',
        'category' => 'Value Added Courses',
        'description' => 'NAAC Criterion 1 – Evaluate value-added and certificate courses offered',
        'questions' => [
            'The value-added course enhanced skills not covered in the regular curriculum',
            'The course content was relevant and up-to-date',
            'The duration and schedule of the course was convenient',
            'The resource persons/trainers were knowledgeable and effective',
            'The course helped improve your employability and practical skills',
            'Certification provided is recognized and valuable',
            'Overall satisfaction with the value-added course'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 2: Teaching-Learning and Evaluation
    // ======================================================================

    $forms[] = [
        'title' => 'Teacher Evaluation by Students',
        'category' => 'Teacher Evaluation',
        'description' => 'NAAC Criterion 2 – Comprehensive evaluation of teaching effectiveness',
        'questions' => [
            'The teacher is regular and punctual in conducting classes',
            'The teacher has thorough knowledge of the subject matter',
            'The teacher explains concepts clearly and effectively',
            'The teacher uses innovative and student-centric teaching methods',
            'The teacher uses ICT tools (PPTs, videos, online platforms) effectively',
            'The teacher encourages students to ask questions and participate in discussions',
            'The teacher provides timely feedback on assignments and evaluations',
            'The teacher is approachable and available for consultation outside class',
            'The teacher inspires curiosity and independent thinking',
            'The teacher relates theory to practical/real-world applications',
            'Overall satisfaction with the teacher\'s performance'
        ]
    ];

    $forms[] = [
        'title' => 'Teaching-Learning Process Feedback',
        'category' => 'Teaching-Learning Process',
        'description' => 'NAAC Criterion 2 – Evaluate overall teaching-learning methodology and environment',
        'questions' => [
            'Student-centric teaching methods (group discussions, case studies, projects) are used',
            'ICT-enabled tools and digital resources are integrated into teaching',
            'The learning environment encourages active participation and collaboration',
            'Tutorial and remedial sessions are conducted for slow learners',
            'Advanced learners are given enough opportunities (seminars, projects, research)',
            'Mentoring and academic guidance is provided effectively',
            'The institution uses a Learning Management System (LMS) effectively',
            'Internal evaluation is continuous, transparent, and fair',
            'The examination system effectively measures learning outcomes',
            'Overall satisfaction with the teaching-learning process'
        ]
    ];

    $forms[] = [
        'title' => 'Course Exit Survey',
        'category' => 'Course Exit Survey',
        'description' => 'NAAC Criterion 2 – End-of-course survey to measure course outcome attainment',
        'questions' => [
            'The course objectives were achieved by the end of the course',
            'I have gained sufficient knowledge of the core concepts',
            'I can apply the knowledge gained to solve real-world problems',
            'I developed analytical and critical thinking skills through this course',
            'The laboratory/practical sessions complemented the theory effectively',
            'The assessment and evaluation methods were fair and aligned with course outcomes',
            'The course content was covered within the scheduled time',
            'I would recommend this course to fellow students'
        ]
    ];

    $forms[] = [
        'title' => 'Course Outcome Attainment Survey',
        'category' => 'Course Outcome Attainment',
        'description' => 'NAAC Criterion 2 – Measure direct attainment of specific Course Outcomes (COs)',
        'questions' => [
            'CO1: I have achieved the first course outcome as stated in the syllabus',
            'CO2: I have achieved the second course outcome',
            'CO3: I have achieved the third course outcome',
            'CO4: I have achieved the fourth course outcome',
            'CO5: I have achieved the fifth course outcome (if applicable)',
            'Overall, the course helped me achieve the stated learning outcomes'
        ]
    ];

    $forms[] = [
        'title' => 'Mentoring & Counselling Feedback',
        'category' => 'Mentoring & Counselling',
        'description' => 'NAAC Criterion 2 – Evaluate mentoring and counselling support',
        'questions' => [
            'A faculty mentor is assigned and accessible for academic guidance',
            'The mentor takes regular follow-ups on academic progress',
            'Personal and psychological counselling services are available',
            'Career counselling and guidance sessions are helpful',
            'The mentor helps in resolving academic difficulties',
            'Overall satisfaction with mentoring and counselling support'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 3: Research, Innovations and Extension
    // ======================================================================

    $forms[] = [
        'title' => 'Research & Innovation Ecosystem Feedback',
        'category' => 'Research & Innovation',
        'description' => 'NAAC Criterion 3 – Evaluate research facilities, innovation, and ecosystem',
        'questions' => [
            'The institution promotes a research culture among students and faculty',
            'Adequate funding and seed money is provided for research projects',
            'Research facilities (labs, journals, databases) are accessible and adequate',
            'Students are encouraged to participate in research projects and publications',
            'Workshops, seminars, and conferences are regularly organized',
            'Collaboration with industry and other institutions for research exists',
            'Innovation and entrepreneurship initiatives (incubation centers, hackathons) are supported',
            'Intellectual property rights (IPR, patents, copyrights) awareness is promoted',
            'Overall satisfaction with the research and innovation ecosystem'
        ]
    ];

    $forms[] = [
        'title' => 'Workshop / Practical Training Feedback',
        'category' => 'Workshop / Practical Training',
        'description' => 'NAAC Criterion 3 – Feedback on workshops, training sessions, and extension activities',
        'questions' => [
            'The workshop/training content was relevant and up-to-date',
            'The trainer/resource person was knowledgeable and engaging',
            'Hands-on practical experience was adequate',
            'The workshop enhanced my technical/professional skills',
            'The duration and schedule were appropriate',
            'Learning materials and resources provided were useful',
            'Overall satisfaction with the workshop/training'
        ]
    ];

    $forms[] = [
        'title' => 'Product Design / Capstone Project Feedback',
        'category' => 'Product Design',
        'description' => 'NAAC Criterion 3 – Evaluate capstone projects, product design, and innovation assignments',
        'questions' => [
            'The project topic was relevant to current industry needs',
            'Adequate mentorship and guidance was provided by the project guide',
            'Resources and infrastructure for project development were sufficient',
            'The project helped develop problem-solving and design thinking skills',
            'Opportunities to showcase and present the project were provided',
            'The evaluation criteria for the project were fair and transparent',
            'Overall satisfaction with the project/product design experience'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 4: Infrastructure and Learning Resources
    // ======================================================================

    $forms[] = [
        'title' => 'Infrastructure & Facilities Feedback',
        'category' => 'Infrastructure and Resources',
        'description' => 'NAAC Criterion 4 – Evaluate physical infrastructure, classrooms, and campus facilities',
        'questions' => [
            'Classrooms are well-maintained, ventilated, and adequately furnished',
            'Smart classrooms and ICT tools are available and functional',
            'Campus Wi-Fi connectivity and internet speed is satisfactory',
            'Seminar halls, conference rooms, and auditoriums are adequate',
            'Canteen/cafeteria facilities and food quality are satisfactory',
            'Drinking water, washrooms, and sanitation facilities are clean and accessible',
            'Sports facilities and playgrounds are adequate',
            'Hostel facilities (if applicable) are comfortable and well-maintained',
            'Common rooms and recreational areas are available',
            'Ramps, lifts, and facilities for differently-abled students are adequate',
            'Overall satisfaction with campus infrastructure'
        ]
    ];

    $forms[] = [
        'title' => 'Laboratory Feedback',
        'category' => 'Laboratory Feedback',
        'description' => 'NAAC Criterion 4 – Evaluate laboratory facilities, equipment, and practical sessions',
        'questions' => [
            'Laboratory equipment and instruments are up-to-date and functional',
            'The number of workstations/computers is sufficient for all students',
            'Software tools and licensed applications required for practicals are available',
            'Lab manuals and experiment instructions are clear and comprehensive',
            'Technical staff provides adequate support during lab sessions',
            'Safety measures and protocols are followed in the laboratory',
            'Lab timings and scheduling are convenient',
            'The lab experiments help reinforce theoretical concepts',
            'Overall satisfaction with laboratory facilities'
        ]
    ];

    $forms[] = [
        'title' => 'Library Feedback',
        'category' => 'Library Feedback',
        'description' => 'NAAC Criterion 4 – Evaluate library resources, services, and digital access',
        'questions' => [
            'The library has adequate collection of textbooks and reference books',
            'E-journals, e-books, and digital databases (NPTEL, SWAYAM, etc.) are accessible',
            'The library management system (OPAC) is user-friendly',
            'Reading room facilities and seating capacity are adequate',
            'Library staff is helpful and responsive',
            'Library hours and access timings are convenient',
            'Inter-library loan and resource sharing services are available',
            'The library provides a conducive environment for study and research',
            'Overall satisfaction with library services'
        ]
    ];

    $forms[] = [
        'title' => 'ICT Facilities Feedback',
        'category' => 'ICT Facilities Feedback',
        'description' => 'NAAC Criterion 4 – Evaluate IT infrastructure, digital tools, and e-governance',
        'questions' => [
            'Computer labs have adequate and updated hardware and software',
            'Campus Wi-Fi coverage and internet bandwidth are adequate',
            'Learning Management System (LMS) is effective and accessible',
            'ERP/student portal for registration, results, and communication works well',
            'Online assessment tools and e-learning platforms are utilized effectively',
            'IT support and help desk response time is satisfactory',
            'Overall satisfaction with ICT facilities'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 5: Student Support and Progression
    // ======================================================================

    $forms[] = [
        'title' => 'Student Satisfaction Survey (SSS)',
        'category' => 'Student Satisfaction Survey',
        'description' => 'NAAC Criterion 5 – Comprehensive student satisfaction survey as per NAAC format',
        'questions' => [
            'How much of the syllabus was covered in class?',
            'How well did the teachers prepare for the classes?',
            'How well were the teachers able to communicate?',
            'The teachers\' approach to teaching can best be described as',
            'Fairness of the internal evaluation process',
            'Was your performance in assignments discussed with you?',
            'The institution provides multiple opportunities to learn and grow',
            'Teachers inform you about your expected competencies, course outcomes, and programme outcomes',
            'Your mentor provides guidance in academic and personal matters',
            'The institution provides support for students from disadvantaged communities',
            'Skill development and soft skills training is provided',
            'Overall satisfaction with the educational experience at the institution'
        ]
    ];

    $forms[] = [
        'title' => 'Alumni Feedback',
        'category' => 'Alumni Feedback',
        'description' => 'NAAC Criterion 5 – Feedback from alumni on their educational experience and career preparedness',
        'questions' => [
            'The education received helped you in your career/higher studies',
            'The curriculum was relevant to your current profession',
            'Faculty support and mentoring contributed to your development',
            'The institution provided adequate placement and career guidance',
            'Co-curricular and extra-curricular activities enriched your experience',
            'Infrastructure and learning resources were adequate during your time',
            'The institution prepared you for lifelong learning and professional growth',
            'You would recommend this institution to prospective students',
            'Overall satisfaction with your experience at the institution'
        ]
    ];

    $forms[] = [
        'title' => 'Placement & Career Services Feedback',
        'category' => 'Placement & Career Services',
        'description' => 'NAAC Criterion 5 – Evaluate placement cell, career guidance, and training programs',
        'questions' => [
            'The placement cell organizes sufficient campus drives and recruitment events',
            'Pre-placement training (aptitude, communication, technical) is effective',
            'Career counselling and guidance services are helpful',
            'Industry interaction, guest lectures, and networking opportunities are adequate',
            'Information about job opportunities and higher studies is shared timely',
            'Entrepreneurship development programs are available and helpful',
            'Overall satisfaction with placement and career services'
        ]
    ];

    $forms[] = [
        'title' => 'Co-curricular & Extra-curricular Activities Feedback',
        'category' => 'Co-curricular Activities',
        'description' => 'NAAC Criterion 5 – Evaluate student clubs, activities, sports, and cultural events',
        'questions' => [
            'The institution encourages student participation in co-curricular activities',
            'Technical clubs and societies are active and well-organized',
            'Cultural events, festivals, and inter-college competitions are regularly held',
            'Sports activities and competitions are adequately supported',
            'NSS/NCC/Community service opportunities are available',
            'Student council and governance participation is encouraged',
            'Overall satisfaction with co-curricular and extra-curricular activities'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 6: Governance, Leadership and Management
    // ======================================================================

    $forms[] = [
        'title' => 'Governance & Leadership Feedback',
        'category' => 'Governance and Leadership',
        'description' => 'NAAC Criterion 6 – Evaluate institutional governance, leadership, and management practices',
        'questions' => [
            'The institution has a clear vision and mission that guides its functioning',
            'Strategic planning and goal setting is done effectively',
            'Decentralization and participative management is practiced',
            'The IQAC (Internal Quality Assurance Cell) functions effectively',
            'E-governance is implemented in areas of administration, finance, and academics',
            'Faculty development programs and welfare measures are adequate',
            'Performance appraisal system for faculty is transparent and fair',
            'Financial management and resource mobilization is efficient',
            'The institution follows best practices in governance',
            'Overall satisfaction with institutional governance and leadership'
        ]
    ];

    // ======================================================================
    // NAAC CRITERION 7: Institutional Values and Best Practices
    // ======================================================================

    $forms[] = [
        'title' => 'Employer Feedback',
        'category' => 'Employer Feedback',
        'description' => 'NAAC Criterion 7 – Feedback from employers on graduates\' competency and performance',
        'questions' => [
            'The graduates possess adequate technical/domain knowledge',
            'The graduates demonstrate good communication and interpersonal skills',
            'The graduates show ability to work in teams and collaborate effectively',
            'The graduates exhibit problem-solving and critical thinking abilities',
            'The graduates are adaptable and open to learning new technologies',
            'The graduates demonstrate professional ethics and integrity',
            'The graduates show leadership qualities and initiative',
            'Overall satisfaction with the quality of graduates from this institution'
        ]
    ];

    $forms[] = [
        'title' => 'Parent Feedback',
        'category' => 'Parent Feedback',
        'description' => 'NAAC Criterion 7 – Feedback from parents on institutional performance and student welfare',
        'questions' => [
            'The institution provides a safe and supportive learning environment',
            'Communication from the institution about student progress is adequate',
            'The institution provides value for the fees charged',
            'Faculty members are competent and committed to teaching',
            'Infrastructure and campus facilities are satisfactory',
            'The institution focuses on overall personality development of students',
            'Placement and career guidance support is adequate',
            'The institution addresses grievances and complaints effectively',
            'Overall satisfaction with the institution'
        ]
    ];

    $forms[] = [
        'title' => 'Industry Feedback on Curriculum',
        'category' => 'Industry Feedback',
        'description' => 'NAAC Criterion 7 – Industry perspective on curriculum relevance and graduate preparedness',
        'questions' => [
            'The curriculum content is relevant to current industry requirements',
            'Graduates are well-prepared for entry-level positions in the industry',
            'The institution provides adequate industry exposure to students',
            'Collaboration between the institution and industry is effective',
            'Skill gaps observed in graduates are being addressed',
            'Industry inputs are considered in curriculum design and revision',
            'Overall assessment of the institution\'s contribution to industry readiness'
        ]
    ];

    $forms[] = [
        'title' => 'Gender Sensitization & Inclusion Feedback',
        'category' => 'Gender Sensitization',
        'description' => 'NAAC Criterion 7 – Evaluate gender equity, sensitization, and inclusiveness',
        'questions' => [
            'The institution promotes gender equity and sensitization through activities/programs',
            'Facilities for women (common rooms, safety measures, counselling) are adequate',
            'Anti-ragging and anti-sexual harassment mechanisms are effective',
            'The institution is inclusive and welcoming to students from all backgrounds',
            'Grievance redressal mechanisms for women are accessible and effective',
            'Overall satisfaction with gender equity and inclusiveness'
        ]
    ];

    $forms[] = [
        'title' => 'Environmental Awareness & Sustainability Feedback',
        'category' => 'Environmental Awareness',
        'description' => 'NAAC Criterion 7 – Evaluate environmental consciousness and sustainability practices',
        'questions' => [
            'The institution promotes environmental consciousness and sustainability',
            'Green campus initiatives (solar energy, rainwater harvesting, waste management) are implemented',
            'Energy conservation and water management practices are visible',
            'Environmental awareness programs and events are conducted',
            'The institution follows ban on use of plastic and promotes eco-friendly practices',
            'Overall satisfaction with environmental sustainability efforts'
        ]
    ];

    $forms[] = [
        'title' => 'Program Exit Survey',
        'category' => 'Program Exit Survey',
        'description' => 'NAAC Criterion 2 & 5 – Comprehensive exit survey for graduating students',
        'questions' => [
            'The program achieved its stated objectives and outcomes',
            'I have developed strong technical/domain competencies through this program',
            'The program enhanced my communication and soft skills',
            'I am confident in my ability to apply knowledge to real-world problems',
            'The program prepared me adequately for employment or higher studies',
            'Faculty quality and teaching effectiveness was satisfactory',
            'Infrastructure and learning resources supported my academic growth',
            'The overall learning environment was conducive to academic excellence',
            'I would recommend this program to prospective students',
            'Overall satisfaction with the program'
        ]
    ];

    $forms[] = [
        'title' => 'Program Outcome Survey',
        'category' => 'Program Outcome Survey',
        'description' => 'NAAC Criterion 2 – Evaluate attainment of program outcomes (POs)',
        'questions' => [
            'PO1: Engineering Knowledge – Apply knowledge of math, science, and engineering fundamentals',
            'PO2: Problem Analysis – Identify, formulate, and analyze complex engineering problems',
            'PO3: Design/Development – Design solutions for complex engineering problems',
            'PO4: Investigation – Conduct investigations of complex problems using research methods',
            'PO5: Modern Tool Usage – Use modern engineering and IT tools appropriately',
            'PO6: Engineer & Society – Apply reasoning to assess societal, health, safety, legal issues',
            'PO7: Environment & Sustainability – Understand impact of engineering solutions on environment',
            'PO8: Ethics – Apply ethical principles and commit to professional ethics',
            'PO9: Individual & Teamwork – Function effectively as an individual and in teams',
            'PO10: Communication – Communicate effectively on complex engineering activities',
            'PO11: Project Management – Apply engineering and management principles to projects',
            'PO12: Lifelong Learning – Engage in independent and lifelong learning'
        ]
    ];

    $forms[] = [
        'title' => 'Course Introductory Lecture Feedback',
        'category' => 'Course Introductory Lecture',
        'description' => 'NAAC Criterion 1 & 2 – Feedback on introductory lecture of a course',
        'questions' => [
            'The course objectives and outcomes were clearly explained',
            'The syllabus coverage plan and timeline were communicated',
            'Assessment methods and weightages were clearly explained',
            'Reference books and learning resources were shared',
            'The teacher created interest and motivation for the course',
            'Prerequisites for the course were discussed',
            'Overall effectiveness of the introductory lecture'
        ]
    ];

    // ======================================================================
    // INSERT ALL FORMS INTO DATABASE
    // ======================================================================

    try {
        $pdo->beginTransaction();

        foreach ($forms as $form) {
            // Check if similar form already exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM feedback_forms WHERE title = ?");
            $check->execute([$form['title']]);
            if ($check->fetchColumn() > 0) continue; // Skip if exists

            $stmt = $pdo->prepare("INSERT INTO feedback_forms (title, category, description, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$form['title'], $form['category'], $form['description']]);
            $formId = $pdo->lastInsertId();

            foreach ($form['questions'] as $i => $qText) {
                $stmt = $pdo->prepare("INSERT INTO questions (feedback_form_id, question_text, max_score, sort_order) VALUES (?, ?, 5, ?)");
                $stmt->execute([$formId, $qText, $i + 1]);
            }
            $seeded++;
        }

        $pdo->commit();
        setFlash('success', "Successfully created $seeded NAAC feedback forms with questions!");
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Error seeding forms: ' . $e->getMessage());
    }

    redirect(APP_URL . '/admin/feedback_forms.php');
}

// Show confirmation page
require_once __DIR__ . '/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm animate-slide-down">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50">
            <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-award text-indigo-500 mr-2"></i>Seed NAAC Feedback Forms (Criterion 1–7)</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-600 mb-6">This will create <strong>27 comprehensive feedback forms</strong> covering all 7 NAAC criteria with relevant questions. Forms that already exist (by title) will be skipped.</p>

            <div class="space-y-4 mb-8">
                <?php
                $criteria = [
                    1 => ['Curricular Aspects', 'indigo', 4, 'Curriculum Feedback, Course Feedback, Curriculum Design, Value Added Courses'],
                    2 => ['Teaching-Learning & Evaluation', 'purple', 7, 'Teacher Evaluation, Teaching-Learning Process, Course Exit Survey, CO Attainment, Mentoring, Program Exit/Outcome, Course Intro'],
                    3 => ['Research, Innovations & Extension', 'cyan', 3, 'Research & Innovation, Workshop/Training, Product Design'],
                    4 => ['Infrastructure & Learning Resources', 'emerald', 4, 'Infrastructure, Laboratory, Library, ICT Facilities'],
                    5 => ['Student Support & Progression', 'amber', 4, 'Student Satisfaction Survey, Alumni Feedback, Placement, Co-curricular Activities'],
                    6 => ['Governance, Leadership & Management', 'rose', 1, 'Governance and Leadership'],
                    7 => ['Institutional Values & Best Practices', 'teal', 4, 'Employer, Parent, Industry Feedback, Gender Sensitization, Environmental Awareness']
                ];
                foreach ($criteria as $num => $c):
                ?>
                <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                    <div class="w-10 h-10 bg-<?= $c[1] ?>-100 rounded-xl flex items-center justify-center text-<?= $c[1] ?>-600 font-bold text-sm flex-shrink-0">C<?= $num ?></div>
                    <div>
                        <div class="font-semibold text-gray-700 text-sm"><?= $c[0] ?></div>
                        <div class="text-xs text-gray-400 mt-0.5"><?= $c[2] ?> forms — <?= $c[3] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="seed" value="1">
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all text-sm">
                        <i class="fas fa-magic mr-2"></i> Create All NAAC Forms
                    </button>
                    <a href="feedback_forms.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm text-gray-500 hover:bg-gray-50 transition flex items-center">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
