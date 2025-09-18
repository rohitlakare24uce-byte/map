<?php
require_once '../auth/session.php';
requireLogin(['hod']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get department overview statistics
$dept_overview = $db->fetch("
    SELECT 
        COUNT(DISTINCT s.prn) as total_students,
        COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
            (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
            THEN s.prn END) as compliant_students,
        COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
        COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions,
        SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points_awarded
    FROM students s
    LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
    LEFT JOIN activities a ON s.prn = a.prn
    WHERE s.dept = ?
", [$user['dept']]);

// Get class-wise breakdown
$class_breakdown = $db->fetchAll("
    SELECT 
        s.year,
        s.programme,
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
    WHERE s.dept = ?
    GROUP BY s.year, s.programme, pr.total_points
    ORDER BY s.year, s.programme
", [$user['dept']]);

// Get recent activity trends (last 30 days)
$activity_trends = $db->fetchAll("
    SELECT 
        DATE(a.created_at) as submission_date,
        COUNT(*) as submission_count,
        COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_count
    FROM activities a
    JOIN students s ON a.prn = s.prn
    WHERE s.dept = ? AND a.created_at >= DATE('now', '-30 days')
    GROUP BY DATE(a.created_at)
    ORDER BY submission_date
", [$user['dept']]);

// Get category-wise points distribution
$category_distribution = $db->fetchAll("
    SELECT 
        a.category,
        c.name as category_name,
        COUNT(*) as submission_count,
        SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as total_points
    FROM activities a
    JOIN students s ON a.prn = s.prn
    LEFT JOIN categories c ON a.category = c.id
    WHERE s.dept = ? AND a.status = 'Approved'
    GROUP BY a.category, c.name
    ORDER BY a.category
", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt"></i> HoD Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! (Department: <?php echo htmlspecialchars($user['dept']); ?>)</p>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4><?php echo $dept_overview['total_students']; ?></h4>
                    <p class="text-muted">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4><?php echo $dept_overview['compliant_students']; ?></h4>
                    <p class="text-muted">Compliant Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-percentage fa-3x text-info mb-3"></i>
                    <h4><?php echo $dept_overview['total_students'] > 0 ? round(($dept_overview['compliant_students'] / $dept_overview['total_students']) * 100, 1) : 0; ?>%</h4>
                    <p class="text-muted">Compliance Rate</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                    <h4><?php echo $dept_overview['pending_submissions']; ?></h4>
                    <p class="text-muted">Pending Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-thumbs-up fa-3x text-success mb-3"></i>
                    <h4><?php echo $dept_overview['approved_submissions']; ?></h4>
                    <p class="text-muted">Approved Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <h4><?php echo $dept_overview['total_points_awarded']; ?></h4>
                    <p class="text-muted">Total Points Awarded</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tasks"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="student_monitoring.php" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Student Monitoring
                        </a>
                        <a href="reports.php" class="btn btn-success">
                            <i class="fas fa-file-alt"></i> Generate Reports
                        </a>
                        <a href="../coordinator/verify_submissions.php" class="btn btn-warning">
                            <i class="fas fa-eye"></i> View Pending Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Category-wise Points Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Class-wise Compliance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-graduation-cap"></i> Class-wise Compliance Overview</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Programme</th>
                                    <th>Total Students</th>
                                    <th>Compliant Students</th>
                                    <th>Compliance Rate</th>
                                    <th>Average Completion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_breakdown as $class): ?>
                                <tr>
                                    <td><?php echo $class['year']; ?></td>
                                    <td><?php echo htmlspecialchars($class['programme']); ?></td>
                                    <td><?php echo $class['student_count']; ?></td>
                                    <td><?php echo $class['compliant_count']; ?></td>
                                    <td>
                                        <?php 
                                        $compliance_rate = $class['student_count'] > 0 ? 
                                            round(($class['compliant_count'] / $class['student_count']) * 100, 1) : 0;
                                        $badge_class = $compliance_rate >= 80 ? 'success' : 
                                                      ($compliance_rate >= 60 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $compliance_rate; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?php echo $class['avg_completion']; ?>%">
                                                <?php echo round($class['avg_completion'], 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="student_monitoring.php?year=<?php echo $class['year']; ?>&programme=<?php echo urlencode($class['programme']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Details
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
    </div>
    
    <!-- Activity Trends -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Activity Submission Trends (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> Areas Requiring Attention</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Identify classes/programmes with low compliance
                    $attention_areas = array_filter($class_breakdown, function($class) {
                        $compliance_rate = $class['student_count'] > 0 ? 
                            ($class['compliant_count'] / $class['student_count']) * 100 : 0;
                        return $compliance_rate < 60;
                    });
                    ?>
                    
                    <?php if ($attention_areas): ?>
                        <div class="list-group">
                            <?php foreach ($attention_areas as $area): ?>
                            <?php 
                            $compliance_rate = $area['student_count'] > 0 ? 
                                round(($area['compliant_count'] / $area['student_count']) * 100, 1) : 0;
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $area['year']; ?> Year <?php echo htmlspecialchars($area['programme']); ?></h6>
                                        <p class="mb-1 text-muted">
                                            <?php echo $area['compliant_count']; ?>/<?php echo $area['student_count']; ?> students compliant
                                        </p>
                                    </div>
                                    <span class="badge bg-danger"><?php echo $compliance_rate; ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">All classes are performing well!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Distribution Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php foreach ($category_distribution as $cat): ?>
            '<?php echo $cat['category']; ?> - <?php echo htmlspecialchars($cat['category_name']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach ($category_distribution as $cat): ?>
                <?php echo $cat['total_points']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                '#FF6384',
                '#36A2EB', 
                '#FFCE56',
                '#4BC0C0',
                '#9966FF'
            ]
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

// Activity Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
const trendsChart = new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($activity_trends as $trend): ?>
            '<?php echo date('M d', strtotime($trend['submission_date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Submissions',
            data: [
                <?php foreach ($activity_trends as $trend): ?>
                <?php echo $trend['submission_count']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4
        }, {
            label: 'Approved',
            data: [
                <?php foreach ($activity_trends as $trend): ?>
                <?php echo $trend['approved_count']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#4BC0C0',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
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
</script>

<?php include '../includes/footer.php'; ?>
