<?php
require_once '../auth/session.php';
requireLogin(['coordinator']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get specific student if PRN is provided
$selected_prn = $_GET['prn'] ?? null;

// Get all students in department with their compliance status
$students = $db->fetchAll("
    SELECT s.*, 
           pr.technical, pr.sports_cultural, pr.community_outreach, 
           pr.innovation, pr.leadership, pr.total_points as required_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'A' THEN a.points ELSE 0 END), 0) as tech_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'B' THEN a.points ELSE 0 END), 0) as sports_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'C' THEN a.points ELSE 0 END), 0) as community_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'D' THEN a.points ELSE 0 END), 0) as innovation_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'E' THEN a.points ELSE 0 END), 0) as leadership_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) as total_earned
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    WHERE s.dept = ?
    GROUP BY s.prn
    ORDER BY s.year, s.last_name
", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-users"></i> Student Compliance</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Student Compliance</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="yearFilter" class="form-label">Filter by Year</label>
                            <select class="form-select" id="yearFilter">
                                <option value="">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Filter by Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Students</option>
                                <option value="compliant">Compliant</option>
                                <option value="at-risk">At Risk (&lt;50%)</option>
                                <option value="critical">Critical (&lt;25%)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="searchStudent" class="form-label">Search Student</label>
                            <input type="text" class="form-control" id="searchStudent" placeholder="Enter PRN or name">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                            <button class="btn btn-primary" onclick="exportCompliance()">Export Data</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar"></i> Student Compliance Overview</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="complianceTable">
                    <thead>
                        <tr>
                            <th>PRN</th>
                            <th>Name</th>
                            <th>Year</th>
                            <th>Programme</th>
                            <th>Total Progress</th>
                            <th>Technical (A)</th>
                            <th>Sports (B)</th>
                            <th>Community (C)</th>
                            <th>Innovation (D)</th>
                            <th>Leadership (E)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <?php
                        $total_progress = $student['required_points'] > 0 ? 
                            ($student['total_earned'] / $student['required_points']) * 100 : 0;
                        
                        $tech_progress = $student['technical'] > 0 ? 
                            ($student['tech_points'] / $student['technical']) * 100 : 0;
                        $sports_progress = $student['sports_cultural'] > 0 ? 
                            ($student['sports_points'] / $student['sports_cultural']) * 100 : 0;
                        $community_progress = $student['community_outreach'] > 0 ? 
                            ($student['community_points'] / $student['community_outreach']) * 100 : 0;
                        $innovation_progress = $student['innovation'] > 0 ? 
                            ($student['innovation_points'] / $student['innovation']) * 100 : 0;
                        $leadership_progress = $student['leadership'] > 0 ? 
                            ($student['leadership_points'] / $student['leadership']) * 100 : 0;
                        
                        $status = $total_progress >= 100 ? 'Compliant' : 
                                 ($total_progress >= 50 ? 'On Track' : 
                                 ($total_progress >= 25 ? 'At Risk' : 'Critical'));
                        
                        $status_class = $total_progress >= 100 ? 'success' : 
                                       ($total_progress >= 50 ? 'info' : 
                                       ($total_progress >= 25 ? 'warning' : 'danger'));
                        ?>
                        <tr class="student-row" 
                            data-year="<?php echo $student['year']; ?>" 
                            data-status="<?php echo strtolower(str_replace(' ', '-', $status)); ?>"
                            data-search="<?php echo strtolower($student['prn'] . ' ' . $student['first_name'] . ' ' . $student['last_name']); ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($student['prn']); ?></strong>
                                <?php if ($selected_prn === $student['prn']): ?>
                                    <span class="badge bg-primary">Selected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo $student['year']; ?></td>
                            <td><?php echo htmlspecialchars($student['programme']); ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                         style="width: <?php echo min(100, $total_progress); ?>%">
                                        <?php echo round($total_progress, 1); ?>%
                                    </div>
                                </div>
                                <small><?php echo $student['total_earned']; ?>/<?php echo $student['required_points']; ?> points</small>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, $tech_progress); ?>%"></div>
                                </div>
                                <small><?php echo $student['tech_points']; ?>/<?php echo $student['technical']; ?></small>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, $sports_progress); ?>%"></div>
                                </div>
                                <small><?php echo $student['sports_points']; ?>/<?php echo $student['sports_cultural']; ?></small>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, $community_progress); ?>%"></div>
                                </div>
                                <small><?php echo $student['community_points']; ?>/<?php echo $student['community_outreach']; ?></small>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, $innovation_progress); ?>%"></div>
                                </div>
                                <small><?php echo $student['innovation_points']; ?>/<?php echo $student['innovation']; ?></small>
                            </td>
                            <td>
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, $leadership_progress); ?>%"></div>
                                </div>
                                <small><?php echo $student['leadership_points']; ?>/<?php echo $student['leadership']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudentDetails('<?php echo $student['prn']; ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="../student/transcript.php?prn=<?php echo $student['prn']; ?>" 
                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-certificate"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Filter functions
document.getElementById('yearFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('searchStudent').addEventListener('input', applyFilters);

function applyFilters() {
    const yearFilter = document.getElementById('yearFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const searchFilter = document.getElementById('searchStudent').value.toLowerCase();
    
    const rows = document.querySelectorAll('.student-row');
    
    rows.forEach(row => {
        let show = true;
        
        // Year filter
        if (yearFilter && row.dataset.year !== yearFilter) {
            show = false;
        }
        
        // Status filter
        if (statusFilter && row.dataset.status !== statusFilter) {
            show = false;
        }
        
        // Search filter
        if (searchFilter && !row.dataset.search.includes(searchFilter)) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('yearFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchStudent').value = '';
    applyFilters();
}

function viewStudentDetails(prn) {
    fetch(`../api/get_student_details.php?prn=${prn}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate modal with student details
                document.getElementById('studentDetails').innerHTML = createStudentDetailsHTML(data);
                new bootstrap.Modal(document.getElementById('studentModal')).show();
            } else {
                alert('Error loading student details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading student details');
        });
}

function createStudentDetailsHTML(data) {
    const student = data.student;
    const activities = data.activities;
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Student Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>PRN:</strong></td><td>${student.prn}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${student.first_name} ${student.last_name}</td></tr>
                    <tr><td><strong>Programme:</strong></td><td>${student.programme}</td></tr>
                    <tr><td><strong>Year:</strong></td><td>${student.year}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Progress Summary</h6>
                <canvas id="studentProgressChart" width="300" height="200"></canvas>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h6>Recent Activities</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    activities.forEach(activity => {
        const badgeClass = activity.status === 'Approved' ? 'success' : 
                          (activity.status === 'Rejected' ? 'danger' : 'warning');
        html += `
            <tr>
                <td>${activity.activity_type}</td>
                <td><span class="badge bg-secondary">${activity.category}</span></td>
                <td>${new Date(activity.date).toLocaleDateString()}</td>
                <td><span class="badge bg-${badgeClass}">${activity.status}</span></td>
                <td>${activity.points}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    return html;
}

function exportCompliance() {
    // Create CSV content
    let csv = 'PRN,Name,Year,Programme,Total Points,Required Points,Completion %,Status\n';
    
    document.querySelectorAll('.student-row').forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            const prn = cells[0].textContent.trim();
            const name = cells[1].textContent.trim();
            const year = cells[2].textContent.trim();
            const programme = cells[3].textContent.trim();
            const progress = cells[4].querySelector('small').textContent;
            const status = cells[10].textContent.trim();
            
            csv += `"${prn}","${name}","${year}","${programme}","${progress}","${status}"\n`;
        }
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'student_compliance_report.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<?php include '../includes/footer.php'; ?>
