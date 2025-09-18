<?php
require_once '../auth/session.php';
requireLogin(['hod']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Generate report if requested
if (isset($_GET['generate'])) {
    $report_type = $_GET['generate'];
    $format = $_GET['format'] ?? 'excel';
    
    if ($report_type === 'department_report') {
        // Get comprehensive department data
        $dept_data = $db->fetchAll("
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
            ORDER BY s.year, s.programme, s.last_name
        ", [$user['dept']]);
        
        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="Department_Report_' . date('Y-m-d') . '.xls"');
            
            echo "<table border='1'>";
            echo "<tr><th colspan='15'>Department Report - " . htmlspecialchars($user['dept']) . "</th></tr>";
            echo "<tr><th colspan='15'>Generated on: " . date('d M Y H:i') . "</th></tr>";
            echo "<tr></tr>";
            echo "<tr><th>PRN</th><th>Student Name</th><th>Year</th><th>Programme</th><th>Admission Year</th><th>Total Earned</th><th>Total Required</th><th>Completion %</th><th>Eligibility</th><th>Technical</th><th>Sports</th><th>Community</th><th>Innovation</th><th>Leadership</th><th>Status</th></tr>";
            
            foreach ($dept_data as $student) {
                $completion = $student['required_points'] > 0 ? round(($student['total_earned'] / $student['required_points']) * 100, 1) : 0;
                $eligibility = $completion >= 100 ? 'Eligible' : 'Not Eligible';
                $status = $completion >= 100 ? 'Compliant' : 
                         ($completion >= 50 ? 'On Track' : 
                         ($completion >= 25 ? 'At Risk' : 'Critical'));
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['prn']) . "</td>";
                echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
                echo "<td>" . $student['year'] . "</td>";
                echo "<td>" . htmlspecialchars($student['programme']) . "</td>";
                echo "<td>" . htmlspecialchars($student['admission_year']) . "</td>";
                echo "<td>" . $student['total_earned'] . "</td>";
                echo "<td>" . $student['required_points'] . "</td>";
                echo "<td>" . $completion . "%</td>";
                echo "<td>" . $eligibility . "</td>";
                echo "<td>" . $student['tech_points'] . "/" . $student['technical'] . "</td>";
                echo "<td>" . $student['sports_points'] . "/" . $student['sports_cultural'] . "</td>";
                echo "<td>" . $student['community_points'] . "/" . $student['community_outreach'] . "</td>";
                echo "<td>" . $student['innovation_points'] . "/" . $student['innovation'] . "</td>";
                echo "<td>" . $student['leadership_points'] . "/" . $student['leadership'] . "</td>";
                echo "<td>" . $status . "</td>";
                echo "</tr>";
            }
            
            // Summary statistics
            $total_students = count($dept_data);
            $eligible_students = array_filter($dept_data, function($s) {
                return $s['required_points'] > 0 && $s['total_earned'] >= $s['required_points'];
            });
            $eligible_count = count($eligible_students);
            
            echo "<tr></tr>";
            echo "<tr><th colspan='15'>SUMMARY</th></tr>";
            echo "<tr><td colspan='7'>Total Students</td><td>" . $total_students . "</td><td colspan='7'></td></tr>";
            echo "<tr><td colspan='7'>Eligible Students</td><td>" . $eligible_count . "</td><td colspan='7'></td></tr>";
            echo "<tr><td colspan='7'>Eligibility Rate</td><td>" . ($total_students > 0 ? round(($eligible_count / $total_students) * 100, 1) : 0) . "%</td><td colspan='7'></td></tr>";
            
            echo "</table>";
            exit();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-file-alt"></i> Department Reports</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Report Generation Options -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building"></i> Department Report</h5>
                </div>
                <div class="card-body">
                    <p>Generate a comprehensive report of all students in the department with detailed MAP compliance status.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="?generate=department_report&format=excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Download Excel Report
                        </a>
                        <a href="?generate=department_report&format=pdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Download PDF Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Eligibility Summary</h5>
                </div>
                <div class="card-body">
                    <p>Generate a summary report showing eligible vs ineligible students by programme and year.</p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-info" onclick="generateEligibilitySummary()">
                            <i class="fas fa-chart-pie"></i> View Eligibility Summary
                        </button>
                        <button class="btn btn-warning" onclick="exportEligibilitySummary()">
                            <i class="fas fa-download"></i> Export Summary (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Department Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Department Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get department statistics
                    $stats = $db->fetch("
                        SELECT 
                            COUNT(DISTINCT s.prn) as total_students,
                            COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
                                THEN s.prn END) as eligible_students,
                            AVG(CASE WHEN pr.total_points > 0 THEN 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
                                ELSE 0 END) as avg_completion,
                            COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
                            COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions,
                            SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points_awarded
                        FROM students s
                        LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                        LEFT JOIN activities a ON s.prn = a.prn
                        WHERE s.dept = ?
                    ", [$user['dept']]);
                    
                    $eligibility_rate = $stats['total_students'] > 0 ? 
                        round(($stats['eligible_students'] / $stats['total_students']) * 100, 1) : 0;
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-primary"><?php echo $stats['total_students']; ?></h4>
                                <small>Total Students</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-success"><?php echo $stats['eligible_students']; ?></h4>
                                <small>Eligible Students</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-info"><?php echo $eligibility_rate; ?>%</h4>
                                <small>Eligibility Rate</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-secondary"><?php echo round($stats['avg_completion'], 1); ?>%</h4>
                                <small>Avg Completion</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-warning"><?php echo $stats['pending_submissions']; ?></h4>
                                <small>Pending Submissions</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-dark"><?php echo $stats['total_points_awarded']; ?></h4>
                                <small>Total Points Awarded</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Programme-wise Breakdown -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-graduation-cap"></i> Programme-wise Eligibility</h5>
                </div>
                <div class="card-body">
                    <?php
                    $programme_stats = $db->fetchAll("
                        SELECT 
                            s.programme,
                            s.year,
                            COUNT(DISTINCT s.prn) as total_students,
                            COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
                                THEN s.prn END) as eligible_students,
                            AVG(CASE WHEN pr.total_points > 0 THEN 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
                                ELSE 0 END) as avg_completion
                        FROM students s
                        LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                        LEFT JOIN activities a ON s.prn = a.prn
                        WHERE s.dept = ?
                        GROUP BY s.programme, s.year
                        ORDER BY s.programme, s.year
                    ", [$user['dept']]);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Programme</th>
                                    <th>Year</th>
                                    <th>Total Students</th>
                                    <th>Eligible</th>
                                    <th>Eligibility Rate</th>
                                    <th>Avg Completion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programme_stats as $prog): ?>
                                <?php 
                                $prog_eligibility = $prog['total_students'] > 0 ? 
                                    round(($prog['eligible_students'] / $prog['total_students']) * 100, 1) : 0;
                                $badge_class = $prog_eligibility >= 80 ? 'success' : 
                                              ($prog_eligibility >= 60 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prog['programme']); ?></td>
                                    <td><?php echo $prog['year']; ?></td>
                                    <td><?php echo $prog['total_students']; ?></td>
                                    <td><?php echo $prog['eligible_students']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $prog_eligibility; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?php echo $prog['avg_completion']; ?>%">
                                                <?php echo round($prog['avg_completion'], 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Eligibility Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="eligibilityChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Trends and Analysis -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-area"></i> Monthly Activity Trends</h5>
                </div>
                <div class="card-body">
                    <?php
                    $monthly_trends = $db->fetchAll("
                        SELECT 
                            strftime('%Y-%m', a.created_at) as month,
                            COUNT(*) as total_submissions,
                            COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions,
                            SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as points_awarded
                        FROM activities a
                        JOIN students s ON a.prn = s.prn
                        WHERE s.dept = ? AND a.created_at >= DATE('now', '-12 months')
                        GROUP BY strftime('%Y-%m', a.created_at)
                        ORDER BY month
                    ", [$user['dept']]);
                    ?>
                    
                    <canvas id="trendsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Eligibility Summary Modal -->
<div class="modal fade" id="eligibilityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eligibility Summary Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eligibilityContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Eligibility Distribution Chart
const eligibilityCtx = document.getElementById('eligibilityChart').getContext('2d');
const eligibilityChart = new Chart(eligibilityCtx, {
    type: 'pie',
    data: {
        labels: ['Eligible', 'Not Eligible'],
        datasets: [{
            data: [<?php echo $stats['eligible_students']; ?>, <?php echo $stats['total_students'] - $stats['eligible_students']; ?>],
            backgroundColor: ['#28a745', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
const trendsChart = new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($monthly_trends as $trend): ?>
            '<?php echo date('M Y', strtotime($trend['month'] . '-01')); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Total Submissions',
            data: [
                <?php foreach ($monthly_trends as $trend): ?>
                <?php echo $trend['total_submissions']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }, {
            label: 'Approved Submissions',
            data: [
                <?php foreach ($monthly_trends as $trend): ?>
                <?php echo $trend['approved_submissions']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function generateEligibilitySummary() {
    // Create eligibility summary content
    const content = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Year</th>
                        <th>Total</th>
                        <th>Eligible</th>
                        <th>Not Eligible</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programme_stats as $prog): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prog['programme']); ?></td>
                        <td><?php echo $prog['year']; ?></td>
                        <td><?php echo $prog['total_students']; ?></td>
                        <td class="text-success"><?php echo $prog['eligible_students']; ?></td>
                        <td class="text-danger"><?php echo $prog['total_students'] - $prog['eligible_students']; ?></td>
                        <td><?php echo $prog['total_students'] > 0 ? round(($prog['eligible_students'] / $prog['total_students']) * 100, 1) : 0; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('eligibilityContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('eligibilityModal')).show();
}

function exportEligibilitySummary() {
    let csv = 'Programme,Year,Total Students,Eligible Students,Not Eligible,Eligibility Rate\n';
    
    <?php foreach ($programme_stats as $prog): ?>
    csv += '"<?php echo htmlspecialchars($prog['programme']); ?>","<?php echo $prog['year']; ?>","<?php echo $prog['total_students']; ?>","<?php echo $prog['eligible_students']; ?>","<?php echo $prog['total_students'] - $prog['eligible_students']; ?>","<?php echo $prog['total_students'] > 0 ? round(($prog['eligible_students'] / $prog['total_students']) * 100, 1) : 0; ?>%"\n';
    <?php endforeach; ?>
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'eligibility_summary_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>
