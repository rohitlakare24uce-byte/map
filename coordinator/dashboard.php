<?php
require_once '../auth/session.php';
requireLogin(['coordinator']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get students in coordinator's department
$students = $db->fetchAll("
    SELECT s.*, 
           COUNT(a.id) as total_submissions,
           SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) as pending_submissions,
           SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END) as earned_points
    FROM students s
    LEFT JOIN activities a ON s.prn = a.prn
    WHERE s.dept = ?
    GROUP BY s.prn
    ORDER BY s.year, s.last_name
", [$user['dept']]);

// Get pending submissions count
$pending_count = $db->fetch("
    SELECT COUNT(*) as count 
    FROM activities a
    JOIN students s ON a.prn = s.prn
    WHERE s.dept = ? AND a.status = 'Pending'
", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt"></i> Coordinator Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! (Department: <?php echo htmlspecialchars($user['dept']); ?>)</p>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4><?php echo count($students); ?></h4>
                    <p class="text-muted">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                    <h4><?php echo $pending_count['count']; ?></h4>
                    <p class="text-muted">Pending Verifications</p>
                    <a href="verify_submissions.php" class="btn btn-warning btn-sm">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>
                        <?php 
                        $compliant = 0;
                        foreach ($students as $student) {
                            $rules = $db->fetch("SELECT total_points FROM programme_rules WHERE admission_year = ? AND programme = ?", 
                                [$student['admission_year'], $student['programme']]);
                            if ($rules && $student['earned_points'] >= $rules['total_points']) {
                                $compliant++;
                            }
                        }
                        echo $compliant;
                        ?>
                    </h4>
                    <p class="text-muted">Compliant Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                    <p class="text-muted">Compliance Rate</p>
                    <h4><?php echo count($students) > 0 ? round($compliant / count($students) * 100, 1) : 0; ?>%</h4>
                    <a href="student_compliance.php" class="btn btn-info btn-sm">Details</a>
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
                        <a href="verify_submissions.php" class="btn btn-primary">
                            <i class="fas fa-check"></i> Verify Submissions
                        </a>
                        <a href="student_compliance.php" class="btn btn-info">
                            <i class="fas fa-users"></i> Student Compliance
                        </a>
                        <a href="reports.php" class="btn btn-success">
                            <i class="fas fa-file-alt"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> Students Requiring Attention</h5>
                </div>
                <div class="card-body">
                    <?php
                    $attention_students = [];
                    foreach ($students as $student) {
                        $rules = $db->fetch("SELECT total_points FROM programme_rules WHERE admission_year = ? AND programme = ?", 
                            [$student['admission_year'], $student['programme']]);
                        if ($rules) {
                            $completion_rate = $rules['total_points'] > 0 ? ($student['earned_points'] / $rules['total_points']) * 100 : 0;
                            if ($completion_rate < 50) {
                                $attention_students[] = array_merge($student, ['completion_rate' => $completion_rate]);
                            }
                        }
                    }
                    ?>
                    
                    <?php if ($attention_students): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>PRN</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Completion</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($attention_students, 0, 5) as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['prn']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo $student['year']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar bg-danger" style="width: <?php echo $student['completion_rate']; ?>%">
                                                    <?php echo round($student['completion_rate'], 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="student_compliance.php?prn=<?php echo $student['prn']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($attention_students) > 5): ?>
                            <p class="text-muted text-center">
                                <a href="student_compliance.php">View all <?php echo count($attention_students); ?> students requiring attention</a>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-success text-center">
                            <i class="fas fa-check-circle"></i> All students are making good progress!
                        </p>
                    <?php endif; ?>
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
                    $recent_submissions = $db->fetchAll("
                        SELECT a.*, s.first_name, s.last_name, c.name as category_name
                        FROM activities a
                        JOIN students s ON a.prn = s.prn
                        LEFT JOIN categories c ON a.category = c.id
                        WHERE s.dept = ?
                        ORDER BY a.created_at DESC
                        LIMIT 10
                    ", [$user['dept']]);
                    ?>
                    
                    <?php if ($recent_submissions): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Activity</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $submission['prn']; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($submission['activity_type']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $submission['category']; ?></span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($submission['date'])); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = $submission['status'] === 'Approved' ? 'success' : 
                                                          ($submission['status'] === 'Rejected' ? 'danger' : 'warning');
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $submission['status']; ?></span>
                                        </td>
                                        <td><?php echo date('d M H:i', strtotime($submission['created_at'])); ?></td>
                                        <td>
                                            <?php if ($submission['status'] === 'Pending'): ?>
                                                <a href="verify_submissions.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-primary">
                                                    Review
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No recent submissions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
