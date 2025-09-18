<?php
require_once '../auth/session.php';
requireLogin(['admin']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Generate report if requested
if (isset($_GET['generate'])) {
    $report_type = $_GET['generate'];
    $format = $_GET['format'] ?? 'excel';
    
    if ($report_type === 'university_report') {
        // Get comprehensive university data
        $university_data = $db->fetchAll("
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
            GROUP BY s.prn
            ORDER BY s.dept, s.year, s.programme, s.last_name
        ");
        
        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="University_MAP_Report_' . date('Y-m-d') . '.xls"');
            
            echo "<table border='1'>";
            echo "<tr><th colspan='18'>Sanjivani University - MAP Compliance Report</th></tr>";
            echo "<tr><th colspan='18'>Generated on: " . date('d M Y H:i') . "</th></tr>";
            echo "<tr></tr>";
            echo "<tr><th>PRN</th><th>Student Name</th><th>Department</th><th>Year</th><th>Programme</th><th>Admission Year</th><th>Total Earned</th><th>Total Required</th><th>Completion %</th><th>Eligibility</th><th>Technical (A)</th><th>Sports (B)</th><th>Community (C)</th><th>Innovation (D)</th><th>Leadership (E)</th><th>Status</th><th>Risk Level</th><th>Compliance</th></tr>";
            
            foreach ($university_data as $student) {
                $completion = $student['required_points'] > 0 ? round(($student['total_earned'] / $student['required_points']) * 100, 1) : 0;
                $eligibility = $completion >= 100 ? 'Eligible' : 'Not Eligible';
                $status = $completion >= 100 ? 'Compliant' : 
                         ($completion >= 75 ? 'On Track' : 
                         ($completion >= 50 ? 'At Risk' : 
                         ($completion >= 25 ? 'Critical' : 'Very Critical')));
                
                $risk_level = $completion >= 75 ? 'Low' : 
                             ($completion >= 50 ? 'Medium' : 
                             ($completion >= 25 ? 'High' : 'Very High'));
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['prn']) . "</td>";
                echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['dept']) . "</td>";
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
                echo "<td>" . $risk_level . "</td>";
                echo "<td>" . ($completion >= 100 ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            
            // Summary by department
            $dept_summary = [];
            foreach ($university_data as $student) {
                if (!isset($dept_summary[$student['dept']])) {
                    $dept_summary[$student['dept']] = ['total' => 0, 'compliant' => 0];
                }
                $dept_summary[$student['dept']]['total']++;
                if ($student['required_points'] > 0 && $student['total_earned'] >= $student['required_points']) {
                    $dept_summary[$student['dept']]['compliant']++;
                }
            }
            
            echo "<tr></tr>";
            echo "<tr><th colspan='18'>DEPARTMENT SUMMARY</th></tr>";
            echo "<tr><th>Department</th><th>Total Students</th><th>Compliant Students</th><th>Compliance Rate</th><th colspan='14'></th></tr>";
            
            foreach ($dept_summary as $dept => $summary) {
                $compliance_rate = $summary['total'] > 0 ? round(($summary['compliant'] / $summary['total']) * 100, 1) : 0;
                echo "<tr><td>" . htmlspecialchars($dept) . "</td><td>" . $summary['total'] . "</td><td>" . $summary['compliant'] . "</td><td>" . $compliance_rate . "%</td><td colspan='14'></td></tr>";
            }
            
            echo "</table>";
            exit();
        }
    }
}

// Get university statistics
$university_stats = $db->fetch("
    SELECT 
        COUNT(DISTINCT s.prn) as total_students,
        COUNT(DISTINCT s.dept) as total_departments,
        COUNT(DISTINCT s.programme) as total_programmes,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_students,
        COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
        SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points_awarded
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
");

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-file-alt"></i> University Reports</h2>
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
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-university"></i> University-wide Report</h5>
                </div>
                <div class="card-body">
                    <p>Generate a comprehensive report of all students across the university with detailed MAP compliance analysis.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="?generate=university_report&format=excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Download Excel Report
                        </a>
                        <a href="?generate=university_report&format=pdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Download PDF Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building"></i> Department Reports</h5>
                </div>
                <div class="card-body">
                    <p>Generate department-wise reports for detailed analysis of student performance and compliance.</p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-info" onclick="generateDepartmentReports()">
                            <i class="fas fa-chart-bar"></i> View Department Analysis
                        </button>
                        <button class="btn btn-warning" onclick="exportDepartmentSummary()">
                            <i class="fas fa-download"></i> Export Summary (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Analytics Dashboard</h5>
                </div>
                <div class="card-body">
                    <p>View real-time analytics and trends across the university MAP system.</p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="showAnalyticsDashboard()">
                            <i class="fas fa-chart-pie"></i> View Analytics
                        </button>
                        <button class="btn btn-secondary" onclick="exportAnalytics()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- University Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> University Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-primary"><?php echo $university_stats['total_students']; ?></h4>
                                <small>Total Students</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-info"><?php echo $university_stats['total_departments']; ?></h4>
                                <small>Departments</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-secondary"><?php echo $university_stats['total_programmes']; ?></h4>
                                <small>Programmes</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-success"><?php echo $university_stats['compliant_students']; ?></h4>
                                <small>Compliant Students</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-warning"><?php echo $university_stats['pending_submissions']; ?></h4>
                                <small>Pending Submissions</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-3">
                                <h4 class="text-dark"><?php echo $university_stats['total_points_awarded']; ?></h4>
                                <small>Total Points</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Department-wise Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building"></i> Department-wise Performance</h5>
                </div>
                <div class="card-body">
                    <?php
                    $dept_analysis = $db->fetchAll("
                        SELECT 
                            s.dept,
                            COUNT(DISTINCT s.prn) as total_students,
                            COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
                                THEN s.prn END) as compliant_students,
                            AVG(CASE WHEN pr.total_points > 0 THEN 
                                (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
                                ELSE 0 END) as avg_completion,
                            COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
                            SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points
                        FROM students s
                        LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                        LEFT JOIN activities a ON s.prn = a.prn
                        GROUP BY s.dept
                        ORDER BY s.dept
                    ");
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="deptAnalysisTable">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Students</th>
                                    <th>Compliant</th>
                                    <th>Compliance Rate</th>
                                    <th>Avg Completion</th>
                                    <th>Pending</th>
                                    <th>Total Points</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dept_analysis as $dept): ?>
                                <?php 
                                $compliance_rate = $dept['total_students'] > 0 ? 
                                    round(($dept['compliant_students'] / $dept['total_students']) * 100, 1) : 0;
                                $performance = $compliance_rate >= 90 ? 'Excellent' :
                                              ($compliance_rate >= 75 ? 'Good' :
                                              ($compliance_rate >= 60 ? 'Average' : 'Needs Improvement'));
                                $performance_class = $compliance_rate >= 90 ? 'success' :
                                                    ($compliance_rate >= 75 ? 'info' :
                                                    ($compliance_rate >= 60 ? 'warning' : 'danger'));
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dept['dept']); ?></strong></td>
                                    <td><?php echo $dept['total_students']; ?></td>
                                    <td><?php echo $dept['compliant_students']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $performance_class; ?>" 
                                                 style="width: <?php echo $compliance_rate; ?>%">
                                                <?php echo $compliance_rate; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo round($dept['avg_completion'], 1); ?>%</td>
                                    <td>
                                        <?php if ($dept['pending_submissions'] > 0): ?>
                                            <span class="badge bg-warning"><?php echo $dept['pending_submissions']; ?></span>
                                        <?php else: ?>
                                            <span class="text-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $dept['total_points']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $performance_class; ?>">
                                            <?php echo $performance; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Programme-wise Analysis -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-graduation-cap"></i> Programme-wise Compliance</h5>
                </div>
                <div class="card-body">
                    <canvas id="programmeComplianceChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Overall Compliance</h5>
                </div>
                <div class="card-body">
                    <canvas id="overallComplianceChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Analytics Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="analyticsContent">
                <!-- Analytics content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Programme Compliance Chart
<?php
$programme_compliance = $db->fetchAll("
    SELECT 
        s.programme,
        COUNT(DISTINCT s.prn) as total_students,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_students
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    GROUP BY s.programme
    ORDER BY s.programme
");
?>

const programmeCtx = document.getElementById('programmeComplianceChart').getContext('2d');
const programmeChart = new Chart(programmeCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($programme_compliance as $prog): ?>
            '<?php echo htmlspecialchars($prog['programme']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Total Students',
            data: [
                <?php foreach ($programme_compliance as $prog): ?>
                <?php echo $prog['total_students']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '#007bff'
        }, {
            label: 'Compliant Students',
            data: [
                <?php foreach ($programme_compliance as $prog): ?>
                <?php echo $prog['compliant_students']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '#28a745'
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

// Overall Compliance Chart
const overallCtx = document.getElementById('overallComplianceChart').getContext('2d');
const overallChart = new Chart(overallCtx, {
    type: 'doughnut',
    data: {
        labels: ['Compliant', 'Non-Compliant'],
        datasets: [{
            data: [
                <?php echo $university_stats['compliant_students']; ?>,
                <?php echo $university_stats['total_students'] - $university_stats['compliant_students']; ?>
            ],
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

function generateDepartmentReports() {
    // Show department analysis in modal or new page
    alert('Department reports functionality would show detailed breakdown by department');
}

function exportDepartmentSummary() {
    let csv = 'Department,Total Students,Compliant Students,Compliance Rate,Average Completion,Pending Submissions,Total Points\n';
    
    document.querySelectorAll('#deptAnalysisTable tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const dept = cells[0].textContent.trim();
            const total = cells[1].textContent.trim();
            const compliant = cells[2].textContent.trim();
            const rate = cells[3].querySelector('.progress-bar').textContent.trim();
            const avg = cells[4].textContent.trim();
            const pending = cells[5].textContent.trim();
            const points = cells[6].textContent.trim();
            
            csv += `"${dept}","${total}","${compliant}","${rate}","${avg}","${pending}","${points}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'department_summary_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function showAnalyticsDashboard() {
    // Load analytics content
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Monthly Submission Trends</h6>
                <canvas id="monthlyTrendsChart" width="400" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <h6>Category-wise Points Distribution</h6>
                <canvas id="categoryDistributionChart" width="400" height="200"></canvas>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <h6>Risk Analysis</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Risk Level</th>
                                <th>Number of Students</th>
                                <th>Percentage</th>
                                <th>Action Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-success">
                                <td>Low (>75%)</td>
                                <td id="lowRisk">-</td>
                                <td id="lowRiskPct">-</td>
                                <td>Continue monitoring</td>
                            </tr>
                            <tr class="table-warning">
                                <td>Medium (50-75%)</td>
                                <td id="mediumRisk">-</td>
                                <td id="mediumRiskPct">-</td>
                                <td>Guidance required</td>
                            </tr>
                            <tr class="table-danger">
                                <td>High (25-50%)</td>
                                <td id="highRisk">-</td>
                                <td id="highRiskPct">-</td>
                                <td>Immediate intervention</td>
                            </tr>
                            <tr class="table-dark">
                                <td>Critical (<25%)</td>
                                <td id="criticalRisk">-</td>
                                <td id="criticalRiskPct">-</td>
                                <td>Urgent action required</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('analyticsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('analyticsModal')).show();
}

function exportAnalytics() {
    alert('Analytics export functionality would generate comprehensive analytical report');
}

// Initialize DataTable
$(document).ready(function() {
    $('#deptAnalysisTable').DataTable({
        "pageLength": 25,
        "responsive": true
    });
});
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<?php include '../includes/footer.php'; ?>
