<?php
require_once '../auth/session.php';
requireLogin(['admin']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get university-wide statistics
$university_stats = $db->fetch("
    SELECT 
        COUNT(DISTINCT s.prn) as total_students,
        COUNT(DISTINCT s.dept) as total_departments,
        COUNT(DISTINCT s.programme) as total_programmes,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_students,
        COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
        COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions,
        COUNT(CASE WHEN a.status = 'Rejected' THEN 1 END) as rejected_submissions,
        SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points_awarded
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
");

// Get department-wise breakdown
$department_stats = $db->fetchAll("
    SELECT 
        s.dept,
        COUNT(DISTINCT s.prn) as student_count,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_count,
        AVG(CASE WHEN pr.total_points > 0 THEN 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
            ELSE 0 END) as avg_completion,
        COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_count
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    GROUP BY s.dept
    ORDER BY s.dept
");

// Get programme-wise compliance
$programme_stats = $db->fetchAll("
    SELECT 
        s.programme,
        s.admission_year,
        COUNT(DISTINCT s.prn) as student_count,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_count,
        AVG(CASE WHEN pr.total_points > 0 THEN 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
            ELSE 0 END) as avg_completion
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    GROUP BY s.programme, s.admission_year
    ORDER BY s.programme, s.admission_year
");

// Get recent system activity
$recent_activities = $db->fetchAll("
    SELECT a.*, s.first_name, s.last_name, s.dept, c.name as category_name
    FROM activities a
    JOIN students s ON a.prn = s.prn
    LEFT JOIN categories c ON a.category = c.id
    ORDER BY a.created_at DESC
    LIMIT 10
");

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! University-wide MAP System Overview</p>
        </div>
    </div>
    
    <!-- Key Performance Indicators -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4><?php echo $university_stats['total_students']; ?></h4>
                    <p class="text-muted">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-building fa-3x text-info mb-3"></i>
                    <h4><?php echo $university_stats['total_departments']; ?></h4>
                    <p class="text-muted">Departments</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-graduation-cap fa-3x text-secondary mb-3"></i>
                    <h4><?php echo $university_stats['total_programmes']; ?></h4>
                    <p class="text-muted">Programmes</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4><?php echo $university_stats['compliant_students']; ?></h4>
                    <p class="text-muted">Compliant Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                    <h4><?php echo $university_stats['total_students'] > 0 ? round(($university_stats['compliant_students'] / $university_stats['total_students']) * 100, 1) : 0; ?>%</h4>
                    <p class="text-muted">Compliance Rate</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star fa-3x text-dark mb-3"></i>
                    <h4><?php echo $university_stats['total_points_awarded']; ?></h4>
                    <p class="text-muted">Total Points</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Activity Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h5><?php echo $university_stats['pending_submissions']; ?></h5>
                    <p class="text-muted">Pending Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-thumbs-up fa-2x text-success mb-2"></i>
                    <h5><?php echo $university_stats['approved_submissions']; ?></h5>
                    <p class="text-muted">Approved Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-thumbs-down fa-2x text-danger mb-2"></i>
                    <h5><?php echo $university_stats['rejected_submissions']; ?></h5>
                    <p class="text-muted">Rejected Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                    <h5><?php echo $university_stats['approved_submissions'] + $university_stats['rejected_submissions'] > 0 ? round(($university_stats['approved_submissions'] / ($university_stats['approved_submissions'] + $university_stats['rejected_submissions'])) * 100, 1) : 0; ?>%</h5>
                    <p class="text-muted">Approval Rate</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tasks"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="user_management.php" class="btn btn-primary">
                            <i class="fas fa-users-cog"></i> User Management
                        </a>
                        <a href="program_rules.php" class="btn btn-info">
                            <i class="fas fa-rules"></i> Program Rules
                        </a>
                        <a href="activity_management.php" class="btn btn-warning">
                            <i class="fas fa-tasks"></i> Activity Management
                        </a>
                        <a href="reports.php" class="btn btn-success">
                            <i class="fas fa-file-alt"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Department-wise Compliance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Students</th>
                                    <th>Compliant</th>
                                    <th>Compliance Rate</th>
                                    <th>Avg Completion</th>
                                    <th>Pending</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department_stats as $dept): ?>
                                <?php 
                                $compliance_rate = $dept['student_count'] > 0 ? 
                                    round(($dept['compliant_count'] / $dept['student_count']) * 100, 1) : 0;
                                $status_class = $compliance_rate >= 80 ? 'success' : 
                                               ($compliance_rate >= 60 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dept['dept']); ?></strong></td>
                                    <td><?php echo $dept['student_count']; ?></td>
                                    <td><?php echo $dept['compliant_count']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $compliance_rate; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?php echo $dept['avg_completion']; ?>%">
                                                <?php echo round($dept['avg_completion'], 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($dept['pending_count'] > 0): ?>
                                            <span class="badge bg-warning"><?php echo $dept['pending_count']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($compliance_rate >= 80): ?>
                                            <i class="fas fa-check-circle text-success"></i> Excellent
                                        <?php elseif ($compliance_rate >= 60): ?>
                                            <i class="fas fa-exclamation-triangle text-warning"></i> Good
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger"></i> Needs Attention
                                        <?php endif; ?>
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
    
    <!-- Charts and Analytics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Programme-wise Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="programmeChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-doughnut"></i> Overall Compliance Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="complianceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Recent System Activity</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_activities): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Activity</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Points</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $activity['prn']; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['dept']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $activity['category']; ?></span>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['category_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = $activity['status'] === 'Approved' ? 'success' : 
                                                          ($activity['status'] === 'Rejected' ? 'danger' : 'warning');
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $activity['status']; ?></span>
                                        </td>
                                        <td><?php echo $activity['points']; ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No recent activities found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Programme Distribution Chart
const programmeCtx = document.getElementById('programmeChart').getContext('2d');
const programmeChart = new Chart(programmeCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($programme_stats as $prog): ?>
            '<?php echo htmlspecialchars($prog['programme']); ?> (<?php echo $prog['admission_year']; ?>)',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Total Students',
            data: [
                <?php foreach ($programme_stats as $prog): ?>
                <?php echo $prog['student_count']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '#007bff'
        }, {
            label: 'Compliant Students',
            data: [
                <?php foreach ($programme_stats as $prog): ?>
                <?php echo $prog['compliant_count']; ?>,
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

// Compliance Status Chart
const complianceCtx = document.getElementById('complianceChart').getContext('2d');
const complianceChart = new Chart(complianceCtx, {
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
</script>

<?php include '../includes/footer.php'; ?>
