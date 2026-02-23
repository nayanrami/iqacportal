<?php
/**
 * NAAC Portal - Master Setup & System Reset Wizard
 * This script wipes the database and performs a fresh installation with ADIT institutional data.
 */

require_once __DIR__ . '/../config.php';

// --- SETUP CONFIGURATION ---
$default_password = password_hash('naac123', PASSWORD_BCRYPT);

try {
    echo "Starting System Reset...\n";
    
    // 1. Disable Foreign Keys to allow Truncate/Drop
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. List of tables to clear
    $tables = [
        'response_answers', 'responses', 'questions', 'feedback_forms',
        'course_outcomes', 'program_outcomes', 'students', 'courses',
        'naac_syllabus_status', 'naac_value_added_courses', 'naac_infrastructure_assets',
        'naac_student_progression', 'naac_faculty_empowerment',
        'admins', 'departments', 'portal_settings', 'faculties', 'faculty_course_mapping'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table` ");
        echo "Resetting table `$table`...\n";
    }

    // 3. Re-run Core Schema & Extensions
    $naac_schema = [
        "CREATE TABLE IF NOT EXISTS portal_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE,
            setting_value TEXT
        )",
        "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            naac_code VARCHAR(50) DEFAULT NULL,
            hod_name VARCHAR(100) DEFAULT NULL,
            hod_phone VARCHAR(20) DEFAULT NULL,
            hod_email VARCHAR(100) DEFAULT NULL,
            est_year INT DEFAULT NULL,
            vision TEXT,
            mission TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            department_id INT DEFAULT NULL,
            role ENUM('superadmin', 'deptadmin', 'criterion_1', 'criterion_2', 'criterion_3', 'criterion_4', 'criterion_5', 'criterion_6', 'criterion_7', 'university') DEFAULT 'deptadmin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            code VARCHAR(30) NOT NULL,
            semester INT DEFAULT NULL,
            course_type VARCHAR(50) DEFAULT 'Core',
            credits INT DEFAULT NULL,
            year VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS program_outcomes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            po_code VARCHAR(10) NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS course_outcomes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            co_code VARCHAR(10) NOT NULL,
            description TEXT NOT NULL,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS faculties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20) DEFAULT NULL,
            designation VARCHAR(100) DEFAULT NULL,
            department_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS faculty_course_mapping (
            id INT AUTO_INCREMENT PRIMARY KEY,
            faculty_id INT NOT NULL,
            course_id INT NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            semester INT NOT NULL,
            role VARCHAR(50) DEFAULT 'Primary',
            FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS feedback_forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            course_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            form_type ENUM('co_attainment', 'exit_survey', 'employer', 'alumni', 'parent', 'sss') NOT NULL,
            is_active TINYINT DEFAULT 1,
            academic_year VARCHAR(20) DEFAULT '2025-26',
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feedback_form_id INT NOT NULL,
            question_text TEXT NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            max_score INT DEFAULT 5,
            FOREIGN KEY (feedback_form_id) REFERENCES feedback_forms(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            enrollment_no VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            semester INT NOT NULL,
            password VARCHAR(255) NOT NULL,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feedback_form_id INT NOT NULL,
            student_id INT DEFAULT NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (feedback_form_id) REFERENCES feedback_forms(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
        )",
        "CREATE TABLE IF NOT EXISTS response_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            response_id INT NOT NULL,
            question_id INT NOT NULL,
            score INT NOT NULL,
            FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS naac_syllabus_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            course_id INT NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            semester INT NOT NULL,
            completion_percentage INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (department_id, course_id, academic_year),
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS naac_value_added_courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            course_name VARCHAR(255) NOT NULL,
            course_code VARCHAR(50),
            duration_hours INT,
            students_enrolled INT,
            completion_year INT,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS research_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE
        )",
        "CREATE TABLE IF NOT EXISTS research_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NOT NULL,
            category_id INT NOT NULL,
            title TEXT NOT NULL,
            faculty_name VARCHAR(150),
            publication_date DATE,
            journal_conference VARCHAR(255),
            issn_isbn VARCHAR(50),
            indexing VARCHAR(50) DEFAULT 'None',
            author_role VARCHAR(100),
            collaborating_agency VARCHAR(255),
            impact_factor DECIMAL(10,3) DEFAULT 0,
            funding_amount DECIMAL(15,2) DEFAULT 0,
            status VARCHAR(50),
            description TEXT,
            link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES research_categories(id) ON DELETE CASCADE
        )"
    ];

    foreach ($naac_schema as $sql) {
        $pdo->exec($sql);
    }
    echo "Refining Database Schema...\n";

    // 4. Seeding: Portal Settings (Core only)
    $settings = [
        'app_name' => 'IQAC Portal',
        'app_institute' => 'A.D. Patel Institute of Technology',
        'app_url' => '/feedback',
        'university_name' => 'Charutar Vidyamandal University (CVMU)',
        'college_logo' => 'assets/img/adit.png',
        'university_logo' => 'assets/img/cvmu.png'
    ];

    foreach ($settings as $key => $val) {
        $pdo->prepare("INSERT INTO portal_settings (setting_key, setting_value) VALUES (?, ?)")
            ->execute([$key, $val]);
    }

    // 5. Seeding: IQAC Superadmin
    $pdo->prepare("INSERT INTO admins (username, password, full_name, role) VALUES (?, ?, ?, ?)")
        ->execute(['naac_iqac', $default_password, 'IQAC Coordinator', 'superadmin']);

    // 6. Seeding: All Departments & Coordinators (Institutional Data)
    $depts = [
        ['Artificial Intelligence', 'AI', 'Dr. Dinesh Prajapati', '+91-9925042680', 'coordinator.ai@adit.ac.in'],
        ['Automobile Engineering', 'AE', 'Dr. Sanjay Patel', '+91-9426233297', 'head.ae@adit.ac.in'],
        ['Civil Engineering', 'CIVIL', 'Dr. Rajiv Bhatt', '+91-9428488052', 'head.civil@adit.ac.in'],
        ['Computer Engineering', 'CP', 'Dr. Bhagirath Prajapati', '+91-9824337174', 'head.cp@adit.ac.in'],
        ['Comp. Science & Design', 'CSD', 'Dr. Gopi Bhatt', '+91-9979270920', 'head.cds@adit.ac.in'],
        ['Dairy Technology', 'DT', 'Dr. Mitesh Shah', '+91-9429543108', 'head.dt@adit.ac.in'],
        ['Food Processing Tech.', 'FPT', 'Dr. S Srivastav', '+91-9428901917', 'head.fpt@adit.ac.in'],
        ['Electronics & Comm.', 'EC', 'Dr. Pravin R. Prajapati', '+91-9429367045', 'head.ec@adit.ac.in'],
        ['Electrical Engineering', 'EE', 'Dr. Hardik Shah', '+91-9824394393', 'head.ee@adit.ac.in'],
        ['Information Technology', 'IT', 'Dr. N C Chauhan', '+91-9377559385', 'head.it@adit.ac.in'],
        ['Mechanical Engineering', 'ME', 'Dr. Y D Patel', '+91-9428799545', 'head.me@adit.ac.in']
    ];

    foreach ($depts as $d) {
        $pdo->prepare("INSERT INTO departments (name, code, hod_name, hod_phone, hod_email, est_year) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$d[0], $d[1], $d[2], $d[3], $d[4], 2005]);
        $deptId = $pdo->lastInsertId();
        
        $username = "BTECH-" . $d[1];
        $password = password_hash($d[1] . "@2026", PASSWORD_BCRYPT); // Pattern: DEPTCODE@2026
        
        $pdo->prepare("INSERT INTO admins (username, password, full_name, department_id, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$username, $password, $d[2], $deptId, 'deptadmin']);
    }

    // 7. Seeding: Research Categories
    $categories = [
        'Journal Publication', 'Conference Paper', 'Research Grant', 'Patent', 'Book Chapter', 'Ongoing Project'
    ];
    foreach ($categories as $cat) {
        $pdo->prepare("INSERT INTO research_categories (name) VALUES (?)")->execute([$cat]);
    }
    
    // 8. Seeding: Sample Research Data for IT
    $itDept = $pdo->query("SELECT id FROM departments WHERE code = 'IT'")->fetchColumn();
    $catId = $pdo->query("SELECT id FROM research_categories WHERE name = 'Journal Publication'")->fetchColumn();
    $pdo->prepare("INSERT INTO research_records (department_id, category_id, title, faculty_name, status, publication_date) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$itDept, $catId, 'AI in Higher Education', 'Dr. N C Chauhan', 'published', date('Y-m-d')]);
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nSUCCESS: System initialized (WordPress-style).\n";
    echo "IQAC LOGIN: naac_iqac / naac123\n";
    echo "Please log in to set up departments and users.\n";

} catch (Exception $e) {
    echo "FATAL ERROR during setup: " . $e->getMessage() . "\n";
}
