# NAAC Portal - Official Institutional Registry (ADIT)

This document contains the master list of all administrative and departmental logins after the system re-initialization.

## 🏁 Central Administration
All central institutional roles use the base password: `naac123`

| Username | Role | Designation | Scope |
| :--- | :--- | :--- | :--- |
| `naac_iqac` | `superadmin` | IQAC Coordinator | Institutional (Master) |
| `naac_university` | `university` | Registrar (CVMU) | University Monitoring |
| `naac_c1` | `criterion_1` | C1 Coordinator | Curricular Aspects |
| `naac_c2` | `criterion_2` | C2 Coordinator | Teaching-Learning |
| `naac_c3` | `criterion_3` | C3 Coordinator | Research & Extension |
| `naac_c4` | `criterion_4` | C4 Coordinator | Infrastructure |
| `naac_c5` | `criterion_5` | C5 Coordinator | Student Progression |
| `naac_c6` | `criterion_6` | C6 Coordinator | Governance |
| `naac_c7` | `criterion_7` | C7 Coordinator | Best Practices |

## 🏢 Departmental Administration
Department logins now follow the format: `BTECH-[DEPT]` / `[DEPT]@2026`

| Username | Department | HOD / Coordinator | Password |
| :--- | :--- | :--- | :--- |
| `BTECH-AI` | Artificial Intelligence | Dr. Dinesh Prajapati | `AI@2026` |
| `BTECH-AE` | Automobile Engineering | Dr. Sanjay Patel | `AE@2026` |
| `BTECH-CIVIL` | Civil Engineering | Dr. Rajiv Bhatt | `CIVIL@2026` |
| `BTECH-CP` | Computer Engineering | Dr. Bhagirath Prajapati | `CP@2026` |
| `BTECH-CSD` | Comp. Science & Design | Dr. Gopi Bhatt | `CSD@2026` |
| `BTECH-DT` | Dairy Technology | Dr. Mitesh Shah | `DT@2026` |
| `BTECH-FPT` | Food Processing Tech. | Dr. S Srivastav | `FPT@2026` |
| `BTECH-EC` | Electronics & Comm. | Dr. Pravin R. Prajapati | `EC@2026` |
| `BTECH-EE` | Electrical Engineering | Dr. Hardik Shah | `EE@2026` |
| `BTECH-IT` | Information Technology | Dr. N C Chauhan | `IT@2026` |
| `BTECH-ME` | Mechanical Engineering | Dr. Y D Patel | `ME@2026` |
| `BTECH-MATHS` | Maths Cell | Prof. Mukesh Patel | `MATHS@2026` |

## 🔗 Functional Access
- **Main Portal**: `index.php` (Public / Student View)
- **Admin Panel**: `admin/index.php` (All Roles)
- **Setup Wizard**: `includes/migrations/setup_portal.php` (Developer Only)
- **Curriculum Seeder**: `includes/migrations/seed_all_courses.php` (Sem 1-8 populations)

---
*Credentials updated on 23 Feb 2026. Logins are strictly monitored for NAAC audit compliance.*
