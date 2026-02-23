<?php
require_once __DIR__ . '/../functions.php';

try {
    echo "Updating schema and seeding IT Faculty...\n";

    // 1. Update Schema
    $cols = $pdo->query("DESCRIBE faculties")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('qualification', $cols)) {
        $pdo->exec("ALTER TABLE faculties ADD qualification TEXT AFTER designation");
        echo "Added 'qualification' column.\n";
    }
    if (!in_array('experience', $cols)) {
        $pdo->exec("ALTER TABLE faculties ADD experience VARCHAR(50) AFTER qualification");
        echo "Added 'experience' column.\n";
    }

    // 2. Get IT Department ID
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE code = 'IT'");
    $stmt->execute();
    $itDeptId = $stmt->fetchColumn();

    if (!$itDeptId) {
        throw new Exception("IT Department not found. Please ensure departments are seeded first.");
    }

    $facultyData = [
        ['Dr. Narendrasinh Chauhan', 'Professor and Head of Department', 'Ph.D.(IIT Roorkee)M.E.(CE), B.E.(CE)', '23.1 Years', 'head.it@adit.ac.in', '9377559385'],
        ['Dr. Dinesh Prajapati', 'Associate Professor | Coordinator - AIDS Program', 'PhD (CSE, Nirma University)', '20.1 Years', 'it.djprajapati@adit.ac.in', null],
        ['Dr. Krunal Patel', 'Associate Professor', 'Ph.D Computer Engineering', '17.6 Years', 'it.krunalpatel@adit.ac.in', null],
        ['Dr. Shital Gondaliya', 'Associate Professor', 'B.E.(C.E.), M.E.(C.E.), Ph.D.', '20.1 Years', 'cp.shitalgondaliya@adit.ac.in', null],
        ['Dr. Anand Pandya', 'Assistant Professor', 'Ph.D., M.Tech.(CE), B.E.(IT)', '13.1 Years', 'it.anandpandya@adit.ac.in', null],
        ['Jitiksha Patel', 'Assistant Professor', 'PhD Pursuing, M.Tech(ICT), B.E(IT)', '12.1 Years', 'it.jitikshapatel@adit.ac.in', null],
        ['Hemanshu Patel', 'Assistant Professor', 'M.E ( IT ), B.E( IT )', '11.1 Years', 'it.hemanshu@adit.ac.in', null],
        ['Nayan Mali', 'Assistant Professor', 'PhD Pursuing, ME(IT), BE(IT)', '13.6 Years', 'nayankumar.mali@cvmu.edu.in', null],
        ['Keyur Patel', 'Assistant Professor', 'PhD Pursuing, M.Tech (IT),B.E(IT)', '11.7 Years', 'it.keyurpatel@adit.ac.in', null],
        ['Mayur Ajmeri', 'Assistant Professor', 'M.E( Computer Engineering),PhD Pursuing', '11.1 Years', 'it.mayurajmeri@adit.ac.in', null],
        ['Himani Joshi', 'Assistant Professor', 'M.E Computer Engineering', '8.1 Years', 'it.himanijoshi@adit.ac.in', null],
        ['Anjali Rajput', 'Assistant Professor', 'M.E (C.E.), B.E (CSE)', '4.6 Years', 'it.anjalirajput@adit.ac.in', null],
        ['Khushali Patel', 'Assistant Professor', 'M.E (C.E.), B.E ( C.E.)', '16.1 Years', 'it.khushalipatel@adit.ac.in', null],
        ['Riddhi Shukla', 'Assistant Professor', 'PhD Pursuing, M.E (C.E.), B.E.(I.T.)', '11.1 Years', 'it.riddhishukla@adit.ac.in', null],
        ['Ranna Makwana', 'Assistant Professor', 'PhD Pursuing, M.E (C.E.), B.E (C.E.)', '4.1 Years', 'it.rannamakwana@adit.ac.in', null],
        ['Vimal Bhatt', 'Assistant Professor', 'PhD Pursuing, M.TECH. (CSE), B. E. (COMPUTER)', '17.6 Years', 'it.vimalbhatt@adit.ac.in', null],
        ['Dr. Trilok Suthar', 'Assistant Professor', 'Ph.D, M.Tech', '11.1 Years', 'it.triloksuthar@adit.ac.in', null],
        ['Anu Chauhan', 'Assistant Professor', 'M.Tech. (C.E.), B.E. (C.E.), Diploma (C.E.)', '1.0 Years', 'it.anuchauhan@adit.ac.in', '9510625670'],
        ['Riya Joshi', 'Assistant Professor', 'M.Tech (C.E.),B.E.(Computer Engineering)', '6.1 Years', 'none@gmail.com', null],
        ['Khushi Bharadva', 'Assistant Professor', 'M.Tech.(IT)', '8.1 Years', 'it.khushibharadva@adit.ac.in', null],
        ['Sonam Singh', 'Assistant Professor', 'M.Tech, B.Tech', '2.6 Years', 'it.sonamsingh@adit.ac.in', null],
        ['Kavya Prajapati', 'Assistant Professor', 'BE (C.E.) MTech (CSE)', '5.1 Years', 'it.kavyaprajapati@adit.ac.in', null],
        ['Priyanka Gondaliya', 'Assistant Professor', 'Diploma(I.T.),B.E.(I.T.),M.Tech(information communication)', '16.0 Years', 'it.priyankagondaliya@adit.ac.in', null],
    ];

    $stmt = $pdo->prepare("INSERT INTO faculties (name, designation, qualification, experience, email, phone, department_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           designation = VALUES(designation), 
                           qualification = VALUES(qualification), 
                           experience = VALUES(experience), 
                           phone = VALUES(phone), 
                           department_id = VALUES(department_id)");

    foreach ($facultyData as $f) {
        $stmt->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $itDeptId]);
        echo "Processed: " . $f[0] . "\n";
    }

    echo "\nIT Faculty seeding complete.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
