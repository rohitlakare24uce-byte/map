<?php
require_once '../auth/session.php';
requireLogin(['student']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get programme rules for the student
$rules = $db->fetch("SELECT * FROM programme_rules WHERE admission_year = ? AND programme = ?", 
    [$user['admission_year'], $user['programme']]);

// Get student's activity points by category
$points = $db->fetchAll("
    SELECT 
        UPPER(category) as category,
        SUM(CASE WHEN status = 'Approved' THEN points ELSE 0 END) as earned_points
    FROM activities 
    WHERE prn = ? 
    GROUP BY category
", [$user['id']]);

$earned_points = [];
foreach ($points as $point) {
    $earned_points[$point['category']] = $point['earned_points'];
}

// Calculate totals
$total_earned = array_sum($earned_points);
$total_required = $rules ? $rules['total_points'] : 0;

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt"></i> Student Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
        </div>
    </div>
    
    <!-- Progress Overview -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> MAP Points Progress</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h3 class="text-primary"><?php echo $total_earned; ?> / <?php echo $total_required; ?></h3>
                            <p class="text-muted">Total Points</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $total_required > 0 ? ($total_earned / $total_required * 100) : 0; ?>%">
                                    <?php echo $total_required > 0 ? round($total_earned / $total_required * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($rules): ?>
                    <!-- Category-wise breakdown -->
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Required</th>
                                    <th>Earned</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>A</strong> - Technical Skills</td>
                                    <td><?php echo $rules['technical']; ?></td>
                                    <td><?php echo $earned_points['A'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($earned_points['A'] ?? 0) / $rules['technical'] * 100); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>B</strong> - Sports & Cultural</td>
                                    <td><?php echo $rules['sports_cultural']; ?></td>
                                    <td><?php echo $earned_points['B'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($earned_points['B'] ?? 0) / $rules['sports_cultural'] * 100); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>C</strong> - Community Outreach</td>
                                    <td><?php echo $rules['community_outreach']; ?></td>
                                    <td><?php echo $earned_points['C'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($earned_points['C'] ?? 0) / $rules['community_outreach'] * 100); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>D</strong> - Innovation</td>
                                    <td><?php echo $rules['innovation']; ?></td>
                                    <td><?php echo $earned_points['D'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($earned_points['D'] ?? 0) / $rules['innovation'] * 100); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>E</strong> - Leadership</td>
                                    <td><?php echo $rules['leadership']; ?></td>
                                    <td><?php echo $earned_points['E'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($earned_points['E'] ?? 0) / $rules['leadership'] * 100); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Points Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="pointsChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                    <h5>Submit Activity</h5>
                    <a href="submit_activity.php" class="btn btn-primary">Submit New</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-list fa-3x text-info mb-3"></i>
                    <h5>My Submissions</h5>
                    <a href="my_submissions.php" class="btn btn-info">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                    <h5>Transcript</h5>
                    <a href="transcript.php" class="btn btn-success">Download</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                    <h5>Notifications</h5>
                    <span class="badge bg-danger">
                        <?php 
                        $notifications = $db->fetch("SELECT COUNT(*) as count FROM activities WHERE prn = ? AND status != 'Pending'", [$user['id']]);
                        echo $notifications['count'];
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Submissions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent = $db->fetchAll("
                        SELECT * FROM activities 
                        WHERE prn = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ", [$user['id']]);
                    
                    if ($recent): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php foreach ($recent as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $activity['category']; ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($activity['date'])); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = $activity['status'] === 'Approved' ? 'success' : 
                                                          ($activity['status'] === 'Rejected' ? 'danger' : 'warning');
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $activity['status']; ?></span>
                                        </td>
                                        <td><?php echo $activity['points']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No submissions yet. <a href="submit_activity.php">Submit your first activity</a>!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Pie chart for points distribution
const ctx = document.getElementById('pointsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Technical', 'Sports & Cultural', 'Community', 'Innovation', 'Leadership'],
        datasets: [{
            data: [
                <?php echo $earned_points['A'] ?? 0; ?>,
                <?php echo $earned_points['B'] ?? 0; ?>,
                <?php echo $earned_points['C'] ?? 0; ?>,
                <?php echo $earned_points['D'] ?? 0; ?>,
                <?php echo $earned_points['E'] ?? 0; ?>
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
</script>

<?php include '../includes/footer.php'; ?>
