<?php
require_once '../auth/session.php';
requireLogin(['hod']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get filter parameters
$year_filter = $_GET['year'] ?? '';
$programme_filter = $_GET['programme'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Build the WHERE clause for filters
$where_conditions = ["s.dept = ?"];
$params = [$user['dept']];

if ($year_filter) {
    $where_conditions[] = "s.year = ?";
    $params[] = $year_filter;
}

if ($programme_filter) {
    $where_conditions[] = "s.programme = ?";
    $params[] = $programme_filter;
}

if ($search_filter) {
    $where_conditions[] = "(s.prn LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $search_param = '%' . $search_filter . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get students with their compliance data
$students = $db->fetchAll("
    SELECT s.*, 
           pr.technical, pr.sports_cultural, pr.community_outreach, 
           pr.innovation, pr.leadership, pr.total_points as required_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'A' THEN a.points ELSE 0 END), 0) as tech_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'B' THEN a.points ELSE 0 END), 0) as sports_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'C' THEN a.points ELSE 0 END), 0) as community_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'D' THEN a.points ELSE 0 END), 0) as innovation_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' AND a.category = 'E' THEN a.points ELSE 0 END), 0) as leadership_points,
           COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) as total_earned,
           COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
           COUNT(a.id) as total_submissions,
           MAX(a.created_at) as last_submission
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    WHERE $where_clause
    GROUP BY s.prn
    ORDER BY s.year, s.programme, s.last_name
", $params);

// Get unique programmes and years for filters
$programmes = $db->fetchAll("SELECT DISTINCT programme FROM students WHERE dept = ? ORDER BY programme", [$user['dept']]);
$years = $db->fetchAll("SELECT DISTINCT year FROM students WHERE dept = ? ORDER BY year", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-search"></i> Student Monitoring</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Student Monitoring</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" name="year" id="year">
                                <option value="">All Years</option>
                                <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year['year']; ?>" <?php echo $year_filter == $year['year'] ? 'selected' : ''; ?>>
                                    Year <?php echo $year['year']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="programme" class="form-label">Programme</label>
                            <select class="form-select" name="programme" id="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($programmes as $prog): ?>
                                <option value="<?php echo htmlspecialchars($prog['programme']); ?>" 
                                        <?php echo $programme_filter == $prog['programme'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['programme']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Student</label>
                            <input type="text" class="form-control" name="search" id="search" 
                                   value="<?php echo htmlspecialchars($search_filter); ?>" 
                                   placeholder="Enter PRN or name">
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        
                        <div class="col-md-2">
                            <a href="student_monitoring.php" class="btn btn-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <?php
        $total_students = count($students);
        $compliant_students = 0;
        $at_risk_students = 0;
        $critical_students = 0;
        
        foreach ($students as $student) {
            if ($student['required_points'] > 0) {
                $completion_rate = ($student['total_earned'] / $student['required_points']) * 100;
                if ($completion_rate >= 100) {
                    $compliant_students++;
                } elseif ($completion_rate < 25) {
                    $critical_students++;
                } elseif ($completion_rate < 50) {
                    $at_risk_students++;
                }
            }
        }
        ?>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?php echo $total_students; ?></h4>
                    <p class="text-muted">Total Students</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success"><?php echo $compliant_students; ?></h4>
                    <p class="text-muted">Compliant Students</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning"><?php echo $at_risk_students; ?></h4>
                    <p class="text-muted">At Risk Students</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger"><?php echo $critical_students; ?></h4>
                    <p class="text-muted">Critical Students</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-users"></i> Student Details (<?php echo count($students); ?> students)</h5>
            <div>
                <button class="btn btn-outline-primary btn-sm" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="printReport()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if ($students): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="studentsTable">
                        <thead>
                            <tr>
                                <th>PRN</th>
                                <th>Name</th>
                                <th>Year</th>
                                <th>Programme</th>
                                <th>Overall Progress</th>
                                <th>Technical</th>
                                <th>Sports</th>
                                <th>Community</th>
                                <th>Innovation</th>
                                <th>Leadership</th>
                                <th>Submissions</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <?php
                            $overall_progress = $student['required_points'] > 0 ? 
                                ($student['total_earned'] / $student['required_points']) * 100 : 0;
                            
                            $status = $overall_progress >= 100 ? 'Compliant' : 
                                     ($overall_progress >= 50 ? 'On Track' : 
                                     ($overall_progress >= 25 ? 'At Risk' : 'Critical'));
                            
                            $status_class = $overall_progress >= 100 ? 'success' : 
                                           ($overall_progress >= 50 ? 'info' : 
                                           ($overall_progress >= 25 ? 'warning' : 'danger'));
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['prn']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo $student['year']; ?></td>
                                <td><?php echo htmlspecialchars($student['programme']); ?></td>
                                <td>
                                    <div class="progress mb-1" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                             style="width: <?php echo min(100, $overall_progress); ?>%">
                                            <?php echo round($overall_progress, 1); ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $student['total_earned']; ?>/<?php echo $student['required_points']; ?> points
                                    </small>
                                    <br>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <?php 
                                        $tech_progress = $student['technical'] > 0 ? 
                                            min(100, ($student['tech_points'] / $student['technical']) * 100) : 0;
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $tech_progress; ?>%"></div>
                                    </div>
                                    <small><?php echo $student['tech_points']; ?>/<?php echo $student['technical']; ?></small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <?php 
                                        $sports_progress = $student['sports_cultural'] > 0 ? 
                                            min(100, ($student['sports_points'] / $student['sports_cultural']) * 100) : 0;
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $sports_progress; ?>%"></div>
                                    </div>
                                    <small><?php echo $student['sports_points']; ?>/<?php echo $student['sports_cultural']; ?></small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <?php 
                                        $community_progress = $student['community_outreach'] > 0 ? 
                                            min(100, ($student['community_points'] / $student['community_outreach']) * 100) : 0;
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $community_progress; ?>%"></div>
                                    </div>
                                    <small><?php echo $student['community_points']; ?>/<?php echo $student['community_outreach']; ?></small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <?php 
                                        $innovation_progress = $student['innovation'] > 0 ? 
                                            min(100, ($student['innovation_points'] / $student['innovation']) * 100) : 0;
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $innovation_progress; ?>%"></div>
                                    </div>
                                    <small><?php echo $student['innovation_points']; ?>/<?php echo $student['innovation']; ?></small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <?php 
                                        $leadership_progress = $student['leadership'] > 0 ? 
                                            min(100, ($student['leadership_points'] / $student['leadership']) * 100) : 0;
                                        ?>
                                        <div class="progress-bar" style="width: <?php echo $leadership_progress; ?>%"></div>
                                    </div>
                                    <small><?php echo $student['leadership_points']; ?>/<?php echo $student['leadership']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $student['total_submissions']; ?></strong> total
                                    <?php if ($student['pending_submissions'] > 0): ?>
                                        <br><span class="badge bg-warning"><?php echo $student['pending_submissions']; ?> pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student['last_submission']): ?>
                                        <?php echo date('d M Y', strtotime($student['last_submission'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No submissions</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewStudentDetails('<?php echo $student['prn']; ?>')">
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
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No Students Found</h5>
                    <p class="text-muted">No students match the current filter criteria.</p>
                </div>
            <?php endif; ?>
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
function viewStudentDetails(prn) {
    fetch(`../api/get_student_details.php?prn=${prn}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
    const rules = data.rules;
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Student Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>PRN:</strong></td><td>${student.prn}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${student.first_name} ${student.last_name}</td></tr>
                    <tr><td><strong>Programme:</strong></td><td>${student.programme}</td></tr>
                    <tr><td><strong>Year:</strong></td><td>${student.year}</td></tr>
                    <tr><td><strong>Department:</strong></td><td>${student.dept}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Compliance Status</h6>
                <canvas id="studentComplianceChart" width="300" height="200"></canvas>
            </div>
        </div>
    `;
    
    if (activities.length > 0) {
        html += `
            <div class="row">
                <div class="col-12">
                    <h6>Activity History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Category</th>
                                    <th>Level</th>
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
                    <td>${new Date(activity.date).toLocaleDateString()}</td>
                    <td>${activity.activity_type}</td>
                    <td><span class="badge bg-secondary">${activity.category}</span></td>
                    <td>${activity.level || 'N/A'}</td>
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
    } else {
        html += `
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>No Activities</h5>
                <p class="text-muted">This student has not submitted any activities yet.</p>
            </div>
        `;
    }
    
    return html;
}

function exportData() {
    let csv = 'PRN,Name,Year,Programme,Total Points,Required Points,Completion %,Technical,Sports,Community,Innovation,Leadership,Total Submissions,Status\n';
    
    document.querySelectorAll('#studentsTable tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const prn = cells[0].textContent.trim();
            const name = cells[1].textContent.trim();
            const year = cells[2].textContent.trim();
            const programme = cells[3].textContent.trim();
            const progressText = cells[4].querySelector('small').textContent.trim();
            const status = cells[4].querySelector('.badge').textContent.trim();
            const technical = cells[5].querySelector('small').textContent.trim();
            const sports = cells[6].querySelector('small').textContent.trim();
            const community = cells[7].querySelector('small').textContent.trim();
            const innovation = cells[8].querySelector('small').textContent.trim();
            const leadership = cells[9].querySelector('small').textContent.trim();
            const submissions = cells[10].textContent.replace(/\s+/g, ' ').trim();
            
            csv += `"${prn}","${name}","${year}","${programme}","${progressText}","${technical}","${sports}","${community}","${innovation}","${leadership}","${submissions}","${status}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'student_monitoring_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    window.print();
}
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "order": [[ 4, "desc" ]],
        "pageLength": 25,
        "responsive": true
    });
});
</script>

<?php include '../includes/footer.php'; ?>
