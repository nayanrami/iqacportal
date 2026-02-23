<?php
/**
 * Seed All Data - Courses, COs, POs, and Feedback Forms
 * Run this from admin panel to populate all data
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$messages = [];

$isBackground = defined('BACKGROUND_TASK_ID');
$bgId = $isBackground ? BACKGROUND_TASK_ID : null;

function updateBGProgress($pdo, $id, $progress, $message) {
    if (!$id) return;
    $stmt = $pdo->prepare("UPDATE background_tasks SET progress = ?, message = ? WHERE id = ?");
    $stmt->execute([$progress, $message, $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed']) || $isBackground) {
    try {
        if (!$isBackground) $pdo->beginTransaction();

        updateBGProgress($pdo, $bgId, 10, "Seeding Departments...");

        // ══════════════════════════════════════════
        // 1. DEPARTMENTS
        // ══════════════════════════════════════════
        // Robust check: Update if exists, Insert if not. Preserves ID.
        $stmtDept = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE name = VALUES(name)");
        $stmtDept->execute(['Information Technology', 'IT']);
        
        $deptId = $pdo->query("SELECT id FROM departments WHERE code='IT'")->fetch()['id'];
        $messages[] = "✅ IT Department verified (ID: $deptId)";

        // ══════════════════════════════════════════
        updateBGProgress($pdo, $bgId, 20, "Seeding Courses...");
        $courses = [
            // Semester 1
            ['202000104', 'Calculus', 1, 'Basic Science', 4],
            ['202000110', 'Computer Programming with C', 1, 'Engineering Science', 4],
            ['202001203', 'Basics of Electrical and Electronics Engineering', 1, 'Engineering Science', 4],
            ['202001206', 'Constitution of India', 1, 'Mandatory', 2],
            ['202001209', 'Engineering Workshop', 1, 'Engineering Science', 2],
            ['202001215', 'Professional Communication', 1, 'Humanity & Social Science', 3],

            // Semester 2
            ['202000211', 'Linear Algebra, Vector Calculus and ODE', 2, 'Basic Science', 4],
            ['202000212', 'Object Oriented Programming', 2, 'Engineering Science', 4],
            ['202001202', 'Basic Mechanical Engineering', 2, 'Engineering Science', 4],
            ['202001207', 'Energy and Environment Science', 2, 'Basic Science', 3],
            ['202001208', 'Engineering Graphics', 2, 'Engineering Science', 4],
            ['202001213', 'Physics', 2, 'Basic Science', 4],

            // Semester 3
            ['202000303', 'Probability, Statistics and Numerical Methods', 3, 'Basic Science', 4],
            ['202003402', 'Fundamentals of Economics and Business Management', 3, 'Humanities & Social Science', 3],
            ['202003403', 'Indian Ethos and Value Education', 3, 'Mandatory', 2],
            ['202040301', 'Data Structures', 3, 'Professional Core', 5],
            ['202040302', 'Database Management Systems', 3, 'Professional Core', 5],
            ['202040303', 'Digital Fundamentals', 3, 'Engineering Science', 4],

            // Semester 4
            ['202003404', 'Technical Writing and Soft Skills', 4, 'Humanities & Social Science', 3],
            ['202003405', 'Entrepreneur Skills', 4, 'Mandatory', 2],
            ['202040401', 'Computer Organization and Architecture', 4, 'Professional Core', 4],
            ['202040402', 'Operating Systems', 4, 'Professional Core', 4],
            ['202040404', 'Seminar', 4, 'Mandatory', 1],
            ['202044501', 'Computer Networks', 4, 'Professional Core', 4],
            ['202044502', 'Programming with Java', 4, 'Professional Core', 4],

            // Semester 5
            ['202044503', 'Artificial Intelligence', 5, 'Professional Elective', 4],
            ['202044504', 'Programming with Python', 5, 'Professional Core', 4],
            ['202044505', 'Web Development', 5, 'Professional Core', 4],
            ['202045601', 'Design and Analysis of Algorithm', 5, 'Professional Core', 5],
            ['202045602', 'Software Engineering', 5, 'Professional Core', 4],
            ['202045604', '.NET Technology', 5, 'Professional Elective', 4],
            ['202045605', 'Advance Java Programming', 5, 'Professional Elective', 4],
            ['202045607', 'Cyber Security', 5, 'Professional Elective', 4],

            // Semester 6
            ['202040601', 'Mini Project', 6, 'Mandatory', 2],
            ['202045609', 'Machine Learning', 6, 'Professional Core', 4],
            ['202046701', 'Advanced Web Development', 6, 'Professional Elective', 4],
            ['202046705', 'Computer Vision and Image Processing', 6, 'Professional Elective', 4],
            ['202046706', 'Data Mining and Business Intelligence', 6, 'Professional Elective', 4],
            ['202046708', 'Information and Network Security', 6, 'Professional Core', 4],
            ['202046709', 'Internet of Things', 6, 'Professional Core', 4],
            ['202046713', 'Software Project Management', 6, 'Professional Elective', 4],

            // Semester 7
            ['202000701', 'Summer Training', 7, 'Mandatory', 2],
            ['202046703', 'Blockchain', 7, 'Professional Elective', 4],
            ['202046707', 'Data Science and Visualization', 7, 'Professional Core', 4],
            ['202046710', 'Introduction to Cloud Computing', 7, 'Professional Core', 4],
            ['202046711', 'Language Processors', 7, 'Professional Elective', 4],
            ['202046712', 'Mobile Application Development', 7, 'Professional Core', 4],
            ['202046715', 'UI/UX Design', 7, 'Professional Elective', 4],
            ['202047801', 'Advanced Software Engineering', 7, 'Professional Elective', 4],
            ['202047803', 'Big Data Analytics', 7, 'Professional Elective', 4],
            ['202047804', 'Deep Learning and Applications', 7, 'Professional Elective', 4],
            ['202047808', 'Management of IT Infrastructure', 7, 'Professional Elective', 4],

            // Semester 8
            ['202000801', 'Industrial Internship', 8, 'Internship', 16],
            ['202047802', 'Augmented Reality and Virtual Reality', 8, 'Professional Elective', 4],
            ['202047805', 'Geographical Information Systems', 8, 'Professional Elective', 4],
            ['202047806', 'High Performance Computing', 8, 'Professional Elective', 4],
            ['202047807', 'Introduction to Software Defined Networking', 8, 'Professional Elective', 4],
            ['202047809', 'Natural Language Processing', 8, 'Professional Elective', 4],
            ['202047810', 'Service Oriented Computing', 8, 'Professional Elective', 4],
            ['202080801', 'Industry/User Defined Project (IDP/UDP)', 8, 'Industrial Project', 8],
        ];

        $stmtC = $pdo->prepare("INSERT INTO courses (department_id, code, name, semester, course_type, credits, year) 
                                VALUES (?, ?, ?, ?, ?, ?, '2025-26')
                                ON DUPLICATE KEY UPDATE name = VALUES(name), semester = VALUES(semester), 
                                course_type = VALUES(course_type), credits = VALUES(credits)");
        foreach ($courses as $c) {
            $stmtC->execute([$deptId, $c[0], $c[1], $c[2], $c[3], $c[4]]);
        }
        $messages[] = "✅ " . count($courses) . " courses seeded";

        // ══════════════════════════════════════════
        // 3. PROGRAM OUTCOMES (POs, PSOs, PEOs)
        // ══════════════════════════════════════════
        $pdo->exec("DELETE FROM program_outcomes WHERE department_id = $deptId");

        $outcomes = [
            // PEOs
            ['PEO', 'PEO1', 'Professional Competence', 'Program graduates will work effectively in Information Technology or related profession at industry, academia or as entrepreneur.', 1],
            ['PEO', 'PEO2', 'Lifelong Learning', 'Program graduates will demonstrate life-long learning and successful adaptation to technological changes.', 2],
            ['PEO', 'PEO3', 'Ethics & Teamwork', 'Program graduates will communicate and work effectively, both individually and with team, with professional ethics, social awareness and environmental concerns.', 3],

            // POs
            ['PO', 'PO1', 'Engineering Knowledge', 'Apply the knowledge of mathematics, science, engineering fundamentals, and an engineering specialization to the solution of complex engineering problems.', 1],
            ['PO', 'PO2', 'Problem Analysis', 'Identify, formulate, review research literature, and analyze complex engineering problems reaching substantiated conclusions using first principles of mathematics, natural sciences, and engineering sciences.', 2],
            ['PO', 'PO3', 'Design/Development of Solutions', 'Design solutions for complex engineering problems and design system components or processes that meet the specified needs with appropriate consideration for the public health and safety, and the cultural, societal, and environmental considerations.', 3],
            ['PO', 'PO4', 'Conduct Investigations', 'Use research-based knowledge and research methods including design of experiments, analysis and interpretation of data, and synthesis of the information to provide valid conclusions.', 4],
            ['PO', 'PO5', 'Modern Tool Usage', 'Create, select, and apply appropriate techniques, resources, and modern engineering and IT tools including prediction and modeling to complex engineering activities with an understanding of the limitations.', 5],
            ['PO', 'PO6', 'The Engineer and Society', 'Apply reasoning informed by the contextual knowledge to assess societal, health, safety, legal and cultural issues and the consequent responsibilities relevant to the professional engineering practice.', 6],
            ['PO', 'PO7', 'Environment and Sustainability', 'Understand the impact of the professional engineering solutions in societal and environmental contexts, and demonstrate the knowledge of, and need for sustainable development.', 7],
            ['PO', 'PO8', 'Ethics', 'Apply ethical principles and commit to professional ethics and responsibilities and norms of the engineering practice.', 8],
            ['PO', 'PO9', 'Individual and Team Work', 'Function effectively as an individual, and as a member or leader in diverse teams, and in multidisciplinary settings.', 9],
            ['PO', 'PO10', 'Communication', 'Communicate effectively on complex engineering activities with the engineering community and with society at large, such as, being able to comprehend and write effective reports and design documentation, make effective presentations, and give and receive clear instructions.', 10],
            ['PO', 'PO11', 'Project Management and Finance', 'Demonstrate knowledge and understanding of the engineering and management principles and apply these to one\'s own work, as a member and leader in a team, to manage projects and in multidisciplinary environments.', 11],
            ['PO', 'PO12', 'Life-long Learning', 'Recognize the need for, and have the preparation and ability to engage in independent and life-long learning in the broadest context of technological change.', 12],

            // PSOs
            ['PSO', 'PSO1', 'Knowledge of Core Courses', 'Acquire knowledge of core courses like programming, algorithms, database technologies, software engineering and networking and apply them to solve real world problems.', 1],
            ['PSO', 'PSO2', 'Tools and Technologies', 'Use techniques, skills, software, equipment and modern engineering tools to analyze and solve problems.', 2],
            ['PSO', 'PSO3', 'Innovation and Development', 'Apply domain knowledge to transform innovative ideas into reality by developing effective IT solutions.', 3],
        ];

        updateBGProgress($pdo, $bgId, 60, "Seeding Program Outcomes...");
        $stmtPO = $pdo->prepare("INSERT INTO program_outcomes (department_id, type, code, title, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($outcomes as $o) {
            $stmtPO->execute([$deptId, $o[0], $o[1], $o[2], $o[3], $o[4]]);
        }
        $messages[] = "✅ " . count($outcomes) . " Program Outcomes seeded (3 PEOs, 12 POs, 3 PSOs)";

        // ══════════════════════════════════════════
        updateBGProgress($pdo, $bgId, 40, "Seeding Course Outcomes...");
        // 4. COURSE OUTCOMES (COs)
        // ══════════════════════════════════════════
        $courseRows = $pdo->query("SELECT id, code, name, course_type FROM courses WHERE department_id = $deptId ORDER BY semester, code")->fetchAll();

        // CO definitions for core IT courses
        $coDefinitions = [
            '202040301' => [ // Data Structures
                'Understand and implement linear data structures including arrays, linked lists, stacks and queues',
                'Implement tree data structures and apply various traversal algorithms',
                'Apply graph algorithms such as BFS, DFS, shortest path for problem solving',
                'Analyze time and space complexity of algorithms using asymptotic notations',
                'Design efficient solutions by selecting appropriate data structures for given problems',
            ],
            '202040302' => [ // DBMS
                'Understand relational database concepts, ER modeling and relational algebra',
                'Write SQL queries for data definition, manipulation and retrieval operations',
                'Apply normalization techniques to optimize database schema design',
                'Implement transaction management and concurrency control mechanisms',
                'Understand indexing, query optimization techniques and NoSQL concepts',
            ],
            '202040303' => [ // Digital Fundamentals
                'Understand number systems, codes, Boolean algebra and logic gates',
                'Design and analyze combinational circuits using K-maps and logic gates',
                'Design sequential circuits including flip-flops, counters and registers',
                'Understand memory organization and programmable logic devices',
            ],
            '202040401' => [ // COA
                'Understand computer organization, CPU structure and instruction set architecture',
                'Design and analyze arithmetic, logic and control units',
                'Understand memory hierarchy including cache, main memory and virtual memory',
                'Analyze pipelining concepts, hazards and performance improvement techniques',
                'Understand I/O organization, interrupts and DMA transfers',
            ],
            '202040402' => [ // OS
                'Understand operating system concepts, structures and system calls',
                'Analyze and implement process management including scheduling algorithms',
                'Implement process synchronization using semaphores and monitors',
                'Apply memory management techniques including paging and segmentation',
                'Understand file system organization and disk scheduling algorithms',
            ],
            '202044501' => [ // CN
                'Understand computer network models, protocols and layered architecture',
                'Analyze data link layer protocols, error detection and correction techniques',
                'Implement network layer routing algorithms and IP addressing schemes',
                'Understand transport layer protocols TCP and UDP and flow control',
                'Apply application layer protocols and network security concepts',
            ],
            '202044502' => [ // Java
                'Understand OOP concepts and implement classes, objects and inheritance in Java',
                'Apply exception handling, multithreading and I/O stream concepts',
                'Implement GUI applications using AWT and Swing frameworks',
                'Develop applications using Java Collection Framework',
                'Understand JDBC connectivity and basic web application development',
            ],
            '202044503' => [ // AI
                'Understand AI fundamentals, intelligent agents and problem-solving strategies',
                'Apply search algorithms including uninformed, informed and heuristic search',
                'Implement knowledge representation using logic and probabilistic reasoning',
                'Understand machine learning basics and neural network fundamentals',
                'Apply AI techniques to solve real-world problems',
            ],
            '202044504' => [ // Python
                'Understand Python programming fundamentals, data types and control structures',
                'Implement functions, modules, file handling and exception handling in Python',
                'Apply object-oriented programming concepts in Python',
                'Use Python libraries for data manipulation and scientific computing',
                'Develop applications using Python frameworks and APIs',
            ],
            '202044505' => [ // Web Dev
                'Design web pages using HTML5, CSS3 and responsive design principles',
                'Implement client-side scripting using JavaScript and DOM manipulation',
                'Develop server-side applications using PHP or Node.js',
                'Implement database connectivity and CRUD operations in web applications',
                'Apply web security principles and deploy web applications',
            ],
            '202045601' => [ // DAA
                'Analyze algorithms using asymptotic notations and recurrence relations',
                'Apply divide and conquer strategy to solve computational problems',
                'Implement greedy algorithms and dynamic programming techniques',
                'Understand graph algorithms and network flow problems',
                'Analyze NP-completeness and approximation algorithms',
            ],
            '202045602' => [ // SE
                'Understand software engineering principles, SDLC models and agile methodologies',
                'Apply requirements engineering techniques for software specification',
                'Design software systems using UML diagrams and design patterns',
                'Implement software testing strategies including unit, integration and system testing',
                'Apply project management, quality assurance and maintenance practices',
            ],
            '202045604' => [ // .NET
                'Understand .NET framework architecture and CLR fundamentals',
                'Develop applications using C# programming language features',
                'Implement Windows Forms and WPF applications using .NET',
                'Develop web applications using ASP.NET MVC framework',
                'Implement database connectivity using ADO.NET and Entity Framework',
            ],
            '202045605' => [ // Adv Java
                'Develop enterprise applications using Servlets and JSP technologies',
                'Implement design patterns and MVC architecture in Java applications',
                'Apply Java EE technologies including EJB and JPA for enterprise solutions',
                'Develop RESTful web services using JAX-RS framework',
                'Implement security and transaction management in enterprise applications',
            ],
            '202045607' => [ // Cyber Security
                'Understand cyber security fundamentals, threats and vulnerability assessment',
                'Apply cryptographic techniques for data security and authentication',
                'Implement network security measures including firewalls and IDS/IPS',
                'Understand web application security and OWASP top 10 vulnerabilities',
                'Apply security policies, risk management and incident response procedures',
            ],
            '202045609' => [ // ML
                'Understand machine learning fundamentals, types and evaluation metrics',
                'Implement supervised learning algorithms for classification and regression',
                'Apply unsupervised learning techniques including clustering and dimensionality reduction',
                'Implement ensemble methods and model selection techniques',
                'Apply ML algorithms to real-world datasets and interpret results',
            ],
            '202046701' => [ // Adv Web Dev
                'Develop modern web applications using React.js or Angular frameworks',
                'Implement server-side rendering and API development using Node.js',
                'Apply modern CSS frameworks and responsive design patterns',
                'Implement real-time communication using WebSockets and REST APIs',
                'Deploy web applications using cloud platforms and CI/CD pipelines',
            ],
            '202046708' => [ // Info & Network Security
                'Understand information security principles, policies and governance frameworks',
                'Apply symmetric and asymmetric cryptographic algorithms',
                'Implement network security protocols including SSL/TLS and IPSec',
                'Analyze network attacks and implement intrusion detection systems',
                'Apply digital forensics and incident response methodologies',
            ],
            '202046709' => [ // IoT
                'Understand IoT architecture, protocols and communication technologies',
                'Interface sensors, actuators and embedded systems for IoT applications',
                'Implement IoT data collection, processing and cloud integration',
                'Develop IoT applications using Arduino, Raspberry Pi platforms',
                'Apply IoT security measures and privacy considerations',
            ],
            '202046707' => [ // Data Science
                'Understand data science workflow, data collection and preprocessing techniques',
                'Apply statistical analysis and hypothesis testing methods',
                'Implement data visualization using matplotlib, seaborn and Tableau',
                'Apply machine learning models for predictive analytics',
                'Communicate insights through effective data storytelling and reports',
            ],
            '202046710' => [ // Cloud Computing
                'Understand cloud computing concepts, service models (IaaS, PaaS, SaaS) and deployment models',
                'Implement virtualization techniques and container orchestration',
                'Deploy applications on AWS, Azure or Google Cloud platforms',
                'Apply cloud security, compliance and cost optimization strategies',
                'Design scalable and fault-tolerant cloud architectures',
            ],
            '202046712' => [ // Mobile App Dev
                'Understand mobile application development lifecycle and architecture patterns',
                'Develop native Android applications using Kotlin or Java',
                'Implement UI/UX design principles for mobile interfaces',
                'Integrate mobile applications with backend services and databases',
                'Publish and maintain applications on mobile app stores',
            ],
            '202000110' => [ // C Programming
                'Understand programming fundamentals, data types, operators and control structures',
                'Implement functions, recursion and modular programming concepts',
                'Apply array, string and pointer operations for problem solving',
                'Implement file handling and dynamic memory allocation',
                'Design and implement structured programs for real-world applications',
            ],
            '202000212' => [ // OOP
                'Understand object-oriented programming paradigm and its advantages',
                'Implement classes, objects, constructors and method overloading',
                'Apply inheritance, polymorphism and abstraction concepts',
                'Implement exception handling and file I/O operations',
                'Design solutions using OOP principles and design patterns',
            ],
        ];

        $stmtCO = $pdo->prepare("INSERT INTO course_outcomes (course_id, code, description, sort_order) VALUES (?, ?, ?, ?)");
        $coCount = 0;

        foreach ($courseRows as $course) {
            $codeKey = $course['code'];
            if (isset($coDefinitions[$codeKey])) {
                $cos = $coDefinitions[$codeKey];
            } else {
                // Auto-generate generic COs for courses without specific definitions
                $cos = [
                    "Understand fundamental concepts and principles of " . $course['name'],
                    "Apply theoretical knowledge of " . $course['name'] . " to practical problems",
                    "Analyze and evaluate solutions using " . $course['name'] . " methodologies",
                    "Design and implement solutions using " . $course['name'] . " techniques",
                ];
            }

            foreach ($cos as $i => $coDesc) {
                $stmtCO->execute([$course['id'], 'CO' . ($i + 1), $coDesc, $i + 1]);
                $coCount++;
            }
        }
        $messages[] = "✅ $coCount Course Outcomes seeded across all courses";

        // ══════════════════════════════════════════
        updateBGProgress($pdo, $bgId, 70, "Seeding Feedback Forms...");
        // 5. FEEDBACK FORMS
        // ══════════════════════════════════════════
        $pdo->exec("DELETE FROM feedback_forms WHERE department_id = $deptId");

        // ---- 5a. CO Attainment Forms (one per core/professional course) ----
        $coreTypes = ['Professional Core', 'Professional Elective', 'Engineering Science'];
        $placeholders = implode(',', array_fill(0, count($coreTypes), '?'));
        $stmtCoreCourses = $pdo->prepare("SELECT id, code, name, semester FROM courses WHERE department_id = ? AND course_type IN ($placeholders) ORDER BY semester, code");
        $stmtCoreCourses->execute(array_merge([$deptId], $coreTypes));
        $coreCourses = $stmtCoreCourses->fetchAll();

        $stmtFF = $pdo->prepare("INSERT INTO feedback_forms (title, form_type, category, department_id, course_id, semester, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmtQ = $pdo->prepare("INSERT INTO questions (feedback_form_id, question_text, question_type, co_id, po_id, max_score, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $coFormCount = 0;
        foreach ($coreCourses as $cc) {
            $stmtFF->execute([
                "CO Attainment - " . $cc['name'],
                'co_attainment',
                'Course Outcome Attainment',
                $deptId,
                $cc['id'],
                $cc['semester'],
                "Rate how well each Course Outcome was achieved in " . $cc['name'] . " (" . $cc['code'] . ")"
            ]);
            $formId = $pdo->lastInsertId();

            // Get COs for this course
            $stmtGetCO = $pdo->prepare("SELECT id, code, description FROM course_outcomes WHERE course_id = ? ORDER BY sort_order");
            $stmtGetCO->execute([$cc['id']]);
            $cos = $stmtGetCO->fetchAll();

            foreach ($cos as $i => $co) {
                $stmtQ->execute([
                    $formId,
                    $co['code'] . ': ' . $co['description'],
                    'likert_3',
                    $co['id'],
                    null,
                    3,
                    $i + 1
                ]);
            }
            $coFormCount++;
        }
        $messages[] = "✅ $coFormCount CO Attainment feedback forms created";

        // ---- 5b. PO-wise Exit Survey ----
        $stmtFF->execute([
            'PO-wise Exit Survey - IT Department',
            'exit_survey',
            'Program Exit Survey',
            $deptId,
            null,
            null,
            'Rate your proficiency in each Program Outcome upon completing the B.Tech IT program. This survey helps assess overall program effectiveness.'
        ]);
        $exitFormId = $pdo->lastInsertId();

        $exitPOs = $pdo->prepare("SELECT id, type, code, title, description FROM program_outcomes WHERE department_id = ? AND type IN ('PO','PSO') ORDER BY type, sort_order");
        $exitPOs->execute([$deptId]);
        $allPOs = $exitPOs->fetchAll();

        foreach ($allPOs as $i => $po) {
            $qText = $po['code'] . ' - ' . $po['title'] . ': Rate your proficiency in - ' . $po['description'];
            $stmtQ->execute([
                $exitFormId,
                $qText,
                'likert_5',
                null,
                $po['id'],
                5,
                $i + 1
            ]);
        }
        $messages[] = "✅ PO-wise Exit Survey created with " . count($allPOs) . " questions";

        // ---- 5c. Department Feedback: Curriculum Design & Development ----
        $stmtFF->execute([
            'Curriculum Design & Development Feedback',
            'dept_feedback',
            'Curriculum Feedback',
            $deptId,
            null,
            null,
            'Provide feedback on the curriculum design, course structure, and academic content of the IT department.'
        ]);
        $currFormId = $pdo->lastInsertId();

        $currQs = [
            'The curriculum is well-structured and covers all essential topics in Information Technology',
            'The syllabus is updated regularly to include recent technological advancements',
            'The curriculum provides adequate balance between theory and practical components',
            'The elective courses offered are relevant to current industry requirements',
            'The curriculum includes sufficient project-based and experiential learning opportunities',
            'The curriculum promotes interdisciplinary learning and holistic development',
            'The course outcomes and program outcomes are clearly defined and communicated',
            'Laboratory and practical sessions are well-designed to complement theoretical concepts',
            'The curriculum provides adequate exposure to industry tools, software and technologies',
            'The assessment methods used are fair and evaluate understanding effectively',
            'The curriculum supports development of communication and soft skills',
            'Value-added courses and certification programs enhance employability',
        ];
        foreach ($currQs as $i => $q) {
            $stmtQ->execute([$currFormId, $q, 'likert_5', null, null, 5, $i + 1]);
        }
        $messages[] = "✅ Curriculum Design & Development form created (" . count($currQs) . " questions)";

        // ---- 5d. Department Feedback: Infrastructure & Resources ----
        $stmtFF->execute([
            'Infrastructure & Resources Feedback',
            'dept_feedback',
            'Infrastructure and Resources',
            $deptId,
            null,
            null,
            'Provide feedback on the infrastructure, lab facilities, and resources available in the IT department.'
        ]);
        $infraFormId = $pdo->lastInsertId();

        $infraQs = [
            'The computer laboratories are well-equipped with modern hardware and software',
            'Internet connectivity and network infrastructure meet academic requirements',
            'The department library has adequate books, journals and digital resources',
            'Classroom facilities including projectors and smart boards are adequate',
            'Licensed software and development tools are readily available for students',
            'The department provides access to cloud computing platforms and services',
            'Laboratory equipment is well-maintained and updated regularly',
            'Wi-Fi connectivity across campus is reliable and fast enough for academic use',
            'The department has adequate facilities for project work and research',
            'IT support and technical assistance are available when needed',
            'Online learning platforms and digital resources are accessible',
            'Safety and security measures in labs and department premises are adequate',
        ];
        foreach ($infraQs as $i => $q) {
            $stmtQ->execute([$infraFormId, $q, 'likert_5', null, null, 5, $i + 1]);
        }
        $messages[] = "✅ Infrastructure & Resources form created (" . count($infraQs) . " questions)";

        // ---- 5e. Department Exit Survey ----
        $stmtFF->execute([
            'Department Exit Survey',
            'dept_feedback',
            'Program Exit Survey',
            $deptId,
            null,
            null,
            'Overall feedback from graduating students about their experience in the IT department.'
        ]);
        $deptExitFormId = $pdo->lastInsertId();

        $deptExitQs = [
            'Overall quality of teaching and learning in the department is satisfactory',
            'Faculty members are knowledgeable, approachable and supportive',
            'The department provided adequate career guidance and placement support',
            'Industry interaction through guest lectures, workshops and seminars was sufficient',
            'The department fostered innovation, research and entrepreneurial thinking',
            'Mentoring and counselling support was effective and helpful',
            'Co-curricular and extra-curricular activities enhanced holistic development',
            'The department prepared you well for professional career or higher studies',
            'Communication and leadership skills were developed through the program',
            'Overall satisfaction with the B.Tech IT program at ADIT',
            'I would recommend the IT department at ADIT to prospective students',
            'The department follows ethical practices and values in academic processes',
        ];
        foreach ($deptExitQs as $i => $q) {
            $stmtQ->execute([$deptExitFormId, $q, 'likert_5', null, null, 5, $i + 1]);
        }
        $messages[] = "✅ Department Exit Survey created (" . count($deptExitQs) . " questions)";

        // ---- 5f. Teacher Evaluation Form ----
        $stmtFF->execute([
            'Teacher Evaluation Feedback',
            'dept_feedback',
            'Teacher Evaluation',
            $deptId,
            null,
            null,
            'Evaluate teaching effectiveness, communication and engagement of faculty members.'
        ]);
        $teacherFormId = $pdo->lastInsertId();

        $teacherQs = [
            'The teacher demonstrates thorough knowledge of the subject matter',
            'The teacher explains complex concepts in a clear and understandable manner',
            'The teacher uses appropriate teaching aids and technology effectively',
            'The teacher encourages student participation and interactive learning',
            'The teacher is punctual and regular in conducting classes',
            'The teacher provides timely feedback on assignments and examinations',
            'The teacher is approachable and available for doubt resolution outside class',
            'The teacher relates theoretical concepts to practical and real-world applications',
            'The teacher maintains a supportive and inclusive classroom environment',
            'Overall rating of the teacher\'s effectiveness and contribution to learning',
        ];
        foreach ($teacherQs as $i => $q) {
            $stmtQ->execute([$teacherFormId, $q, 'likert_5', null, null, 5, $i + 1]);
        }
        $messages[] = "✅ Teacher Evaluation form created (" . count($teacherQs) . " questions)";

        // ---- 5g. Student Satisfaction Survey ----
        $stmtFF->execute([
            'Student Satisfaction Survey (SSS)',
            'dept_feedback',
            'Student Satisfaction Survey',
            $deptId,
            null,
            null,
            'NAAC mandated Student Satisfaction Survey covering teaching, learning and campus experience.'
        ]);
        $sssFormId = $pdo->lastInsertId();

        $sssQs = [
            'How much of the syllabus was covered in the class?',
            'How well did the teachers prepare for the classes?',
            'How well were the teachers able to communicate?',
            'The teacher\'s approach to teaching can best be described as excellent',
            'Fairness of the internal evaluation process by the teachers',
            'Was your performance in assessments discussed with you?',
            'The institute takes active interest in promoting internship opportunities',
            'The teaching and mentoring process in your institution facilitates you in cognitive, social and emotional growth',
            'The institution provides multiple opportunities to learn and grow through curricular and extra-curricular activities',
            'Teachers inform you about your expected competencies, course outcomes and programme outcomes',
            'Your mentor is available when you need guidance and support',
            'The institution provides opportunity for innovation and creativity',
        ];
        foreach ($sssQs as $i => $q) {
            $stmtQ->execute([$sssFormId, $q, 'likert_5', null, null, 5, $i + 1]);
        }
        $messages[] = "✅ Student Satisfaction Survey created (" . count($sssQs) . " questions)";

        // ══════════════════════════════════════════
        updateBGProgress($pdo, $bgId, 85, "Generating Dummy Responses...");
        // 6. DUMMY RESPONSES (100 Students)
        // ══════════════════════════════════════════
        $formRows = $pdo->query("SELECT id, form_type FROM feedback_forms WHERE department_id = $deptId")->fetchAll();
        $studentNames = ['Amit', 'Sneha', 'Rahul', 'Priya', 'Vikram', 'Anjali', 'Deepak', 'Megha', 'Sanjay', 'Ritu'];
        $count = 0;

        foreach ($formRows as $form) {
            $formId = $form['id'];
            $formType = $form['form_type'];
            
            // Get questions for this form
            $stmtQs = $pdo->prepare("SELECT id, question_type, max_score FROM questions WHERE feedback_form_id = ?");
            $stmtQs->execute([$formId]);
            $questions = $stmtQs->fetchAll();

            if (empty($questions)) continue;

            for ($i = 1; $i <= 100; $i++) {
                $name = $studentNames[array_rand($studentNames)] . " " . chr(rand(65, 90));
                $roll = "22IT" . str_pad(rand(1, 150), 3, '0', STR_PAD_LEFT);
                
                // Insert response
                $stmtRes = $pdo->prepare("INSERT INTO responses (feedback_form_id, student_name, student_roll) VALUES (?, ?, ?)");
                $stmtRes->execute([$formId, $name, $roll]);
                $responseId = $pdo->lastInsertId();

                // Insert answers
                foreach ($questions as $q) {
                    $max = $q['max_score'] ?: 5;
                    // Random score: skewed towards 3-4-5 for Likert-5, and 2-3 for Likert-3
                    if ($max == 3) {
                        $score = rand(1, 100) > 20 ? rand(2, 3) : 1;
                    } else {
                        $score = rand(1, 100) > 30 ? rand(3, 5) : rand(1, 2);
                    }
                    
                    $stmtAns = $pdo->prepare("INSERT INTO response_answers (response_id, question_id, score) VALUES (?, ?, ?)");
                    $stmtAns->execute([$responseId, $q['id'], $score]);
                }
                $count++;
            }
        }
        $messages[] = "✅ $count dummy responses generated for 100 students across all forms";

        updateBGProgress($pdo, $bgId, 95, "Finalizing...");
        if (!$isBackground) $pdo->commit();
        
        // Final Action: Refresh session to ensure user has current IDs
        if (function_exists('refreshAdminSession')) {
            refreshAdminSession();
        }
        
        $messages[] = "🎉 All data seeded successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        $messages[] = "❌ Error: " . $e->getMessage();
    }
}

// Handle Remove All Forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    try {
        // TRUNCATE causes implicit commit in MySQL, so we DON'T use a transaction here.
        // Disable FK checks to allow truncation
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE response_answers");
        $pdo->exec("TRUNCATE TABLE responses");
        $pdo->exec("TRUNCATE TABLE questions");
        $pdo->exec("TRUNCATE TABLE feedback_forms");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $messages[] = "✅ All feedback forms, questions, and responses have been permanently removed.";
    } catch (Exception $e) {
        $messages[] = "❌ Error deleting forms: " . $e->getMessage();
    }
}

require_once __DIR__ . '/header.php';

// Get current counts
$counts = [
    'courses' => $pdo->query("SELECT COUNT(*) as c FROM courses")->fetch()['c'],
    'cos' => $pdo->query("SELECT COUNT(*) as c FROM course_outcomes")->fetch()['c'],
    'pos' => $pdo->query("SELECT COUNT(*) as c FROM program_outcomes")->fetch()['c'],
    'forms' => $pdo->query("SELECT COUNT(*) as c FROM feedback_forms")->fetch()['c'],
    'questions' => $pdo->query("SELECT COUNT(*) as c FROM questions")->fetch()['c'],
];
?>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm animate-slide-down">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
        <h2 class="font-bold text-gray-700"><i class="fas fa-database text-violet-500 mr-2"></i>Seed All Data</h2>
        <p class="text-sm text-gray-400 mt-1">Populate courses, COs, POs/PSOs/PEOs, and all feedback forms for IT Department</p>
    </div>
    <div class="p-6">
        <!-- Current Status -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <?php
            $statItems = [
                ['Courses', $counts['courses'], 'fa-book', 'blue'],
                ['Course Outcomes', $counts['cos'], 'fa-bullseye', 'violet'],
                ['Program Outcomes', $counts['pos'], 'fa-tasks', 'amber'],
                ['Forms', $counts['forms'], 'fa-clipboard-list', 'emerald'],
                ['Questions', $counts['questions'], 'fa-question-circle', 'rose'],
            ];
            foreach ($statItems as $s): ?>
                <div class="text-center p-4 bg-<?= $s[3] ?>-50 rounded-xl border border-<?= $s[3] ?>-100">
                    <i class="fas <?= $s[2] ?> text-<?= $s[3] ?>-500 text-xl mb-2"></i>
                    <div class="text-2xl font-bold text-gray-800"><?= $s[1] ?></div>
                    <div class="text-xs text-gray-500 font-medium"><?= $s[0] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($messages)): ?>
            <div class="mb-6 space-y-2">
                <?php foreach ($messages as $msg): ?>
                    <div class="px-4 py-2 rounded-lg text-sm <?= strpos($msg, '❌') !== false ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?>">
                        <?= $msg ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <p class="text-sm text-amber-800 font-medium">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Warning:</strong> This will delete all existing IT department data (courses, outcomes, forms) and reseed everything fresh.
            </p>
        </div>

        <div class="flex flex-wrap gap-4">
            <form method="POST">
                <button type="submit" name="seed" value="1"
                        class="px-6 py-3 bg-gradient-to-r from-violet-500 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm"
                        onclick="return confirm('This will reset all IT department data. Continue?')">
                    <i class="fas fa-magic mr-2"></i>Seed All Data
                </button>
            </form>

            <form method="POST">
                <button type="submit" name="delete_all" value="1"
                        class="px-6 py-3 bg-white border border-rose-200 text-rose-600 font-bold rounded-xl shadow-sm hover:bg-rose-50 hover:border-rose-300 transition-all text-sm"
                        onclick="return confirm('CRITICAL: This will PERMANENTLY DELETE ALL feedback forms, questions, and student responses from ALL departments. This action cannot be undone. Are you absolutely sure?')">
                    <i class="fas fa-trash-alt mr-2"></i>Remove All Forms
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
