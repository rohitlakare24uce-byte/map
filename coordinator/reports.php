<?php
require_once '../auth/session.php';
requireLogin(['coordinator']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Generate report if requested
if (isset($_GET['generate']) && $_GET['generate'] === 'class_report') {
    $format = $_GET['format'] ?? 'pdf';
    
    // Get all students in department with their compliance data
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
               COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_count
        FROM students s
        LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
        LEFT JOIN activities a ON s.prn = a.prn
        WHERE s.dept = ?
        GROUP BY s.prn
        ORDER BY s.year, s.programme, s.last_name
    ", [$user['dept']]);
    
    if ($format === 'excel') {
        // Generate Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Class_Report_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th colspan='12'>Class Compliance Report - " . htmlspecialchars($user['dept']) . "</th></tr>";
        echo "<tr><th>PRN</th><th>Name</th><th>Year</th><th>Programme</th><th>Total Earned</th><th>Total Required</th><th>Completion %</th><th>Technical</th><th>Sports</th><th>Community</th><th>Innovation</th><th>Leadership</th></tr>";
        
        foreach ($students as $student) {
            $completion = $student['required_points'] > 0 ? round(($student['total_earned'] / $student['required_points']) * 100, 1) : 0;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['prn']) . "</td>";
            echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
            echo "<td>" . $student['year'] . "</td>";
            echo "<td>" . htmlspecialchars($student['programme']) . "</td>";
            echo "<td>" . $student['total_earned'] . "</td>";
            echo "<td>" . $student['required_points'] . "</td>";
            echo "<td>" . $completion . "%</td>";
            echo "<td>" . $student['tech_points'] . "/" . $student['technical'] . "</td>";
            echo "<td>" . $student['sports_points'] . "/" . $student['sports_cultural'] . "</td>";
            echo "<td>" . $student['community_points'] . "/" . $student['community_outreach'] . "</td>";
            echo "<td>" . $student['innovation_points'] . "/" . $student['innovation'] . "</td>";
            echo "<td>" . $student['leadership_points'] . "/" . $student['leadership'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit();
    }
}

// Get pending verifications for the department
$pending_verifications = $db->fetchAll("
    SELECT a.*, s.first_name, s.last_name, s.year, s.programme
    FROM activities a
    JOIN students s ON a.prn = s.prn
    WHERE s.dept = ? AND a.status = 'Pending'
    ORDER BY a.created_at ASC
", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-file-alt"></i> Reports</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Report Generation -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-download"></i> Generate Class Report</h5>
                </div>
                <div class="card-body">
                    <p>Generate a comprehensive report of all students in your department with their MAP compliance status.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="?generate=class_report&format=excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Download Excel Report
                        </a>
                        <a href="?generate=class_report&format=pdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Download PDF Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Export Pending Verifications</h5>
                </div>
                <div class="card-body">
                    <p>Export list of all pending submissions that require your verification.</p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" onclick="exportPendingVerifications()">
                            <i class="fas fa-file-csv"></i> Export Pending List (CSV)
                        </button>
                        <button class="btn btn-info" onclick="printPendingVerifications()">
                            <i class="fas fa-print"></i> Print Pending List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Department Summary</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate department statistics
                    $dept_stats = $db->fetch("
                        SELECT 
                            COUNT(DISTINCT s.prn) as total_students,
                            COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
                            COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions,
                            COUNT(CASE WHEN a.status = 'Rejected' THEN 1 END) as rejected_submissions,
                            SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points_awarded
                        FROM students s
                        LEFT JOIN activities a ON s.prn = a.prn
                        WHERE s.dept = ?
                    ", [$user['dept']]);
                    
                    // Get programme-wise breakdown
                    $programme_stats = $db->fetchAll("
                        SELECT 
                            s.programme,
                            COUNT(DISTINCT s.prn) as student_count,
                            AVG(CASE WHEN pr.total_points > 0 THEN 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
                                ELSE 0 END) as avg_completion
                        FROM students s
                        LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                        LEFT JOIN activities a ON s.prn = a.prn
                        WHERE s.dept = ?
                        GROUP BY s.programme, pr.total_points
                        ORDER BY s.programme
                    ", [$user['dept']]);
                    ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <h6>Overall Statistics</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-primary"><?php echo $dept_stats['total_students']; ?></h4>
                                        <small>Total Students</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-warning"><?php echo $dept_stats['pending_submissions']; ?></h4>
                                        <small>Pending Submissions</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-success"><?php echo $dept_stats['approved_submissions']; ?></h4>
                                        <small>Approved Submissions</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-info"><?php echo $dept_stats['total_points_awarded']; ?></h4>
                                        <small>Total Points Awarded</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h6>Programme-wise Completion</h6>
                            <?php foreach ($programme_stats as $prog): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <small><?php echo htmlspecialchars($prog['programme']); ?></small>
                                    <small><?php echo round($prog['avg_completion'], 1); ?>%</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" style="width: <?php echo $prog['avg_completion']; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Verifications List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Pending Verifications (<?php echo count($pending_verifications); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if ($pending_verifications): ?>
                        <div class="table-responsive" id="pendingTable">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Year</th>
                                        <th>Programme</th>
                                        <th>Activity</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Submitted</th>
                                        <th>Days Pending</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_verifications as $pending): ?>
                                    <tr>
                                        <td><?php echo $pending['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $pending['prn']; ?></small>
                                        </td>
                                        <td><?php echo $pending['year']; ?></td>
                                        <td><?php echo htmlspecialchars($pending['programme']); ?></td>
                                        <td><?php echo htmlspecialchars($pending['activity_type']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $pending['category']; ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($pending['date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($pending['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $days = floor((time() - strtotime($pending['created_at'])) / (60 * 60 * 24));
                                            $badge = $days > 7 ? 'danger' : ($days > 3 ? 'warning' : 'info');
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo $days; ?> days</span>
                                        </td>
                                        <td>
                                            <a href="verify_submissions.php?id=<?php echo $pending['id']; ?>" class="btn btn-sm btn-primary">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>No Pending Verifications</h5>
                            <p class="text-muted">All submissions have been verified!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportPendingVerifications() {
    let csv = 'ID,Student Name,PRN,Year,Programme,Activity,Category,Activity Date,Submitted Date,Days Pending\n';
    
    const table = document.querySelector('#pendingTable table tbody');
    if (table) {
        table.querySelectorAll('tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                const id = cells[0].textContent.trim();
                const studentName = cells[1].querySelector('strong').textContent.trim();
                const prn = cells[1].querySelector('small').textContent.trim();
                const year = cells[2].textContent.trim();
                const programme = cells[3].textContent.trim();
                const activity = cells[4].textContent.trim();
                const category = cells[5].textContent.trim();
                const activityDate = cells[6].textContent.trim();
                const submittedDate = cells[7].textContent.trim();
                const daysPending = cells[8].textContent.trim();
                
                csv += `"${id}","${studentName}","${prn}","${year}","${programme}","${activity}","${category}","${activityDate}","${submittedDate}","${daysPending}"\n`;
            }
        });
    }
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'pending_verifications_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printPendingVerifications() {
    const printContent = document.getElementById('pendingTable').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h2>Pending Verifications Report</h2>
            <p>Department: <?php echo htmlspecialchars($user['dept']); ?></p>
            <p>Generated: ${new Date().toLocaleDateString()}</p>
            ${printContent}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}
</script>

<?php include '../includes/footer.php'; ?>
