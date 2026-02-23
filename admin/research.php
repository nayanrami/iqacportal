<?php
/**
 * Admin - Research Data Collection System
 * v1.0 - Department Level Research Data Entry
 */
$pageTitle = 'Research Data Management';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isDeptAdmin = isset($_SESSION['admin_dept_id']) && $_SESSION['admin_dept_id'] !== null;
$deptId = $isDeptAdmin ? intval($_SESSION['admin_dept_id']) : null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $pdo->prepare("SELECT id FROM research_records WHERE id = ?" . ($deptId ? " AND department_id = $deptId" : ""));
    $check->execute([$id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM research_records WHERE id = ?")->execute([$id]);
        setFlash('success', 'Research record deleted.');
    }
    redirect(APP_URL . '/admin/research.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_research'])) {
    $recId = isset($_POST['id']) ? intval($_POST['id']) : null;
    $targetDeptId = $deptId ?: intval($_POST['department_id']);
    $catId = intval($_POST['category_id']);
    $title = sanitize($_POST['title']);
    $faculty = sanitize($_POST['faculty_name']);
    $date = $_POST['publication_date'] ?: null;
    $venue = sanitize($_POST['journal_conference']);
    $issn_isbn = sanitize($_POST['issn_isbn']);
    $indexing = $_POST['indexing'];
    $role = sanitize($_POST['author_role']);
    $agency = sanitize($_POST['collaborating_agency']);
    $impact = floatval($_POST['impact_factor'] ?? 0);
    $funding = floatval($_POST['funding_amount'] ?? 0);
    $status = $_POST['status'];
    $desc = sanitize($_POST['description']);
    $link = sanitize($_POST['link']);

    if ($recId) {
        $stmt = $pdo->prepare("UPDATE research_records SET 
            category_id = ?, title = ?, faculty_name = ?, publication_date = ?, 
            journal_conference = ?, issn_isbn = ?, indexing = ?, author_role = ?, collaborating_agency = ?,
            impact_factor = ?, funding_amount = ?, 
            status = ?, description = ?, link = ?
            WHERE id = ? AND department_id = ?");
        $stmt->execute([$catId, $title, $faculty, $date, $venue, $issn_isbn, $indexing, $role, $agency, $impact, $funding, $status, $desc, $link, $recId, $targetDeptId]);
        setFlash('success', 'Research record updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO research_records 
            (department_id, category_id, title, faculty_name, publication_date, journal_conference, issn_isbn, indexing, author_role, collaborating_agency, impact_factor, funding_amount, status, description, link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targetDeptId, $catId, $title, $faculty, $date, $venue, $issn_isbn, $indexing, $role, $agency, $impact, $funding, $status, $desc, $link]);
        setFlash('success', 'New research record added.');
    }
    redirect(APP_URL . '/admin/research.php');
}

require_once __DIR__ . '/header.php';

$categories = $pdo->query("SELECT MIN(id) as id, name FROM research_categories GROUP BY name ORDER BY name")->fetchAll();
$deptFilter = $deptId ? " WHERE id = $deptId" : "";
$departments = $pdo->query("SELECT MIN(id) as id, name FROM departments $deptFilter GROUP BY name ORDER BY name")->fetchAll();
$allFaculties = $pdo->query("SELECT id, name, department_id FROM faculties ORDER BY name")->fetchAll();

// Filter logic
$recordWhere = $deptId ? " WHERE r.department_id = $deptId" : "";

// ── Advanced Pagination Logic ──
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if (!in_array($limit, [10, 20, 50, 100])) $limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalRecords = 0;
$records = [];

try {
    // Count total
    $countSql = "SELECT COUNT(*) FROM research_records r $recordWhere";
    $totalRecords = $pdo->query($countSql)->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Fetch paginated
    $recordsSql = "
        SELECT r.*, c.name as category_name, d.name as dept_name 
        FROM research_records r 
        JOIN research_categories c ON c.id = r.category_id 
        JOIN departments d ON d.id = r.department_id 
        $recordWhere 
        ORDER BY r.publication_date DESC, r.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $records = $pdo->query($recordsSql)->fetchAll();
} catch (Exception $e) {
    $records = [];
}
?>

<main class="p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black gradient-text">Research Data Collection</h1>
            <p class="text-gray-500 text-sm">Manage publications, grants, patents, and other research activities for NAAC.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <?php if ($deptId): ?>
                <a href="<?= APP_URL ?>/admin/research_dept_report.php?dept_id=<?= $deptId ?>" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition flex items-center gap-2 shadow-sm">
                    <i class="fas fa-file-contract text-indigo-500"></i> Consolidated Report
                </a>
            <?php else: ?>
                <div class="relative group">
                    <button class="px-5 py-2.5 bg-white border border-gray-200 text-gray-400 rounded-xl font-bold hover:bg-gray-50 transition flex items-center gap-2 shadow-sm italic cursor-help">
                        <i class="fas fa-file-contract text-gray-300"></i> Consolidated Report
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 p-4 hidden group-hover:block z-50">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Select Dept for Report</p>
                        <div class="space-y-1">
                            <?php foreach ($departments as $d): ?>
                                <a href="<?= APP_URL ?>/admin/research_dept_report.php?dept_id=<?= $d['id'] ?>" class="block px-3 py-2 text-[10px] font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition uppercase tracking-tighter"><?= htmlspecialchars($d['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <button onclick="openAddModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Add Research Data
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
        <?php
        $totalPubs = 0;
        $totalFunding = 0;
        $totalPatents = 0;
        foreach($records as $rec) {
            if (strpos($rec['category_name'], 'Publication') !== false) $totalPubs++;
            if ($rec['funding_amount'] > 0) $totalFunding += $rec['funding_amount'];
            if ($rec['category_name'] === 'Patent') $totalPatents++;
        }
        ?>
        <div class="glass-card p-6 border-l-4 border-blue-500">
            <div class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1">Total Entries</div>
            <div class="text-3xl font-black text-gray-800"><?= count($records) ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-purple-500">
            <div class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1">Publications</div>
            <div class="text-3xl font-black text-gray-800"><?= $totalPubs ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-emerald-500">
            <div class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1">Total Funding</div>
            <div class="text-2xl font-black text-gray-800">₹<?= number_format($totalFunding, 0) ?></div>
        </div>
        <div class="glass-card p-6 border-l-4 border-amber-500">
            <div class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1">Patents</div>
            <div class="text-3xl font-black text-gray-800"><?= $totalPatents ?></div>
        </div>
    </div>

    <!-- Records Table -->
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-4 text-center">Sr. No.</th>
                        <th class="px-6 py-4">Research details</th>
                        <th class="px-6 py-4">Faculty / Dept</th>
                        <th class="px-6 py-4">Type / Status</th>
                        <th class="px-6 py-4">Metrics</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($records)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No research records found. Click add to start.</td></tr>
                    <?php else: 
                        $srNo = $offset + 1;
                        foreach ($records as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition border-b border-gray-50 last:border-0 hover:border-indigo-100">
                        <td class="px-6 py-5 text-center text-xs font-black text-gray-400">
                            <?= $srNo++ ?>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-bold text-gray-800 leading-tight mb-1"><?= $r['title'] ?></div>
                            <div class="text-[10px] text-gray-400 font-medium">
                                <i class="fas fa-calendar-alt mr-1"></i> <?= $r['publication_date'] ?: 'N/A' ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-map-marker-alt mr-1"></i> <?= $r['journal_conference'] ?: 'Self-published' ?>
                                <?php if ($r['issn_isbn']): ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-barcode mr-1"></i> <?= $r['issn_isbn'] ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-medium text-gray-700"><?= $r['faculty_name'] ?: 'Departmental' ?></div>
                            <div class="text-[10px] text-indigo-500 font-black uppercase tracking-wider"><?= $r['dept_name'] ?></div>
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-[9px] font-black uppercase tracking-widest mb-1 block w-fit">
                                <?= $r['category_name'] ?>
                            </span>
                            <div class="flex flex-wrap gap-1 mb-1">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest w-fit block
                                    <?= $r['status'] === 'published' || $r['status'] === 'patented' || $r['status'] === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' ?>">
                                    <?= $r['status'] ?>
                                </span>
                                <?php if ($r['indexing'] !== 'None'): ?>
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-black uppercase tracking-widest w-fit block border border-indigo-100 italic">
                                        <i class="fas fa-star mr-1"></i><?= $r['indexing'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <?php if ($r['impact_factor'] > 0): ?>
                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Impact: <span class="text-indigo-600"><?= $r['impact_factor'] ?></span></div>
                            <?php endif; ?>
                            <?php if ($r['funding_amount'] > 0): ?>
                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Grant: <span class="text-emerald-600">₹<?= number_format($r['funding_amount'], 0) ?></span></div>
                            <?php endif; ?>
                            <?php if ($r['link']): ?>
                                <a href="<?= $r['link'] ?>" target="_blank" class="text-[9px] text-blue-500 hover:underline"><i class="fas fa-link mr-1"></i>View Link</a>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= APP_URL ?>/admin/research_view.php?id=<?= $r['id'] ?>" class="w-8 h-8 rounded-lg border border-gray-100 flex items-center justify-center text-gray-300 hover:text-emerald-600 hover:border-emerald-100 transition shadow-sm bg-white" title="Print Profile">
                                    <i class="fas fa-print text-xs"></i>
                                </a>
                                <button onclick='openEditModal(<?= json_encode($r) ?>)' class="w-8 h-8 rounded-lg border border-gray-100 flex items-center justify-center text-gray-300 hover:text-indigo-600 hover:border-indigo-100 transition shadow-sm bg-white">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button onclick="confirmDelete('?delete=<?= $r['id'] ?>', '<?= addslashes($r['title']) ?>')" class="w-8 h-8 rounded-lg border border-gray-100 flex items-center justify-center text-gray-300 hover:text-rose-600 hover:border-rose-100 transition shadow-sm bg-white">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalRecords > 0): ?>
        <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4 print:hidden px-6 pb-6">
            <div class="flex items-center gap-4">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> Results
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="limit" onchange="this.form.submit()" class="px-2 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold text-gray-500 outline-none focus:border-indigo-500 transition shadow-sm">
                        <?php foreach([10, 20, 50, 100] as $l): ?>
                            <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?> per page</option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="flex items-center gap-1">
                <?php 
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseLink = '?' . http_build_query($queryParams) . '&page=';
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?= $baseLink . ($page - 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-left text-[10px]"></i></a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);
                $startPage = max(1, $endPage - 4);
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                    if($i < 1) continue;
                ?>
                    <a href="<?= $baseLink . $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-black transition shadow-sm <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-200' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $baseLink . ($page + 1) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm"><i class="fas fa-chevron-right text-[10px]"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add/Edit Modal -->
<div id="researchModal" class="modal-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="glass-card max-w-2xl w-full animate-scale-in max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white/80 backdrop-blur shadow-sm z-10">
            <h3 id="modalTitle" class="text-xl font-black text-gray-800">Add New Research Record</h3>
            <button onclick="closeModal('researchModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-6">
            <input type="hidden" name="id" id="form_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Research Title / Topic</label>
                    <input type="text" name="title" id="form_title" required placeholder="Full title of publication, project or patent" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category</label>
                    <select name="category_id" id="form_category_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isDeptAdmin): ?>
                    <input type="hidden" name="department_id" id="form_department_id" value="<?= $deptId ?>">
                <?php else: ?>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Department</label>
                    <select name="department_id" id="form_department_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Primary Faculty / Investigator</label>
                    <input type="text" name="faculty_name" id="form_faculty_name" list="faculty_list" placeholder="Select or type faculty name" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                    <datalist id="faculty_list">
                        <?php foreach ($allFaculties as $f): ?>
                            <option value="<?= htmlspecialchars($f['name']) ?>" data-dept="<?= $f['department_id'] ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Publication / Start Date</label>
                    <input type="date" name="publication_date" id="form_publication_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Journal / Conference / Agency</label>
                    <input type="text" name="journal_conference" id="form_journal_conference" placeholder="Target venue or agency name" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">ISSN / ISBN (Mandatory for NAAC)</label>
                    <input type="text" name="issn_isbn" id="form_issn_isbn" placeholder="e.g. 1234-567X" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Journal Indexing</label>
                    <select name="indexing" id="form_indexing" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-bold text-indigo-600">
                        <option value="None">None / Peer Reviewed</option>
                        <option value="Scopus">Scopus</option>
                        <option value="Web of Science">Web of Science</option>
                        <option value="UGC Care">UGC Care</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Your Role</label>
                    <input type="text" name="author_role" id="form_author_role" placeholder="e.g. First Author, PI, Co-PI" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Collaborating Agency / Institute</label>
                    <input type="text" name="collaborating_agency" id="form_collaborating_agency" placeholder="Name of partnering organization (if any)" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition font-medium italic">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Impact Factor</label>
                    <input type="number" step="0.001" name="impact_factor" id="form_impact_factor" value="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Funding Amount (₹)</label>
                    <input type="number" name="funding_amount" id="form_funding_amount" value="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Status</label>
                    <select name="status" id="form_status" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                        <option value="proposed">Proposed</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="published">Published</option>
                        <option value="patented">Patented</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">External Link / URL</label>
                    <input type="url" name="link" id="form_link" placeholder="https://..." class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Research Description/Abstract</label>
                    <textarea name="description" id="form_description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-indigo-500 transition"></textarea>
                </div>
            </div>

            <button type="submit" name="save_research" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-lg shadow-indigo-100 mt-2">
                Save Research Record
            </button>
        </form>
    </div>
</div>

<script>
// Faculty Filtering Logic
const masterFaculties = <?= json_encode($allFaculties) ?>;
const deptSelect = document.getElementById('form_department_id');
const facultyList = document.getElementById('faculty_list');

function filterFaculties() {
    const selectedDeptId = deptSelect.value;
    facultyList.innerHTML = ''; // Clear current options
    
    // Filter and rebuild datalist
    masterFaculties.forEach(f => {
        if (!selectedDeptId || f.department_id == selectedDeptId) {
            const option = document.createElement('option');
            option.value = f.name;
            facultyList.appendChild(option);
        }
    });
}

// Add listener for department change
if (deptSelect) {
    deptSelect.addEventListener('change', filterFaculties);
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Research Record';
    document.getElementById('form_id').value = '';
    document.getElementById('form_title').value = '';
    document.getElementById('form_faculty_name').value = '';
    document.getElementById('form_publication_date').value = '';
    document.getElementById('form_journal_conference').value = '';
    document.getElementById('form_issn_isbn').value = '';
    document.getElementById('form_indexing').value = 'None';
    document.getElementById('form_author_role').value = '';
    document.getElementById('form_collaborating_agency').value = '';
    document.getElementById('form_impact_factor').value = '0';
    document.getElementById('form_funding_amount').value = '0';
    document.getElementById('form_description').value = '';
    document.getElementById('form_link').value = '';
    
    filterFaculties(); // Initial filter
    openModal('researchModal');
}

function openEditModal(r) {
    document.getElementById('modalTitle').textContent = 'Edit Research Record';
    document.getElementById('form_id').value = r.id;
    document.getElementById('form_title').value = r.title;
    document.getElementById('form_category_id').value = r.category_id;
    document.getElementById('form_department_id').value = r.department_id;
    document.getElementById('form_faculty_name').value = r.faculty_name;
    document.getElementById('form_publication_date').value = r.publication_date;
    document.getElementById('form_journal_conference').value = r.journal_conference;
    document.getElementById('form_issn_isbn').value = r.issn_isbn || '';
    document.getElementById('form_indexing').value = r.indexing || 'None';
    document.getElementById('form_author_role').value = r.author_role || '';
    document.getElementById('form_collaborating_agency').value = r.collaborating_agency || '';
    document.getElementById('form_impact_factor').value = r.impact_factor;
    document.getElementById('form_funding_amount').value = r.funding_amount;
    document.getElementById('form_status').value = r.status;
    document.getElementById('form_description').value = r.description;
    document.getElementById('form_link').value = r.link;
    
    filterFaculties(); // Filter based on the record's department
    openModal('researchModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
