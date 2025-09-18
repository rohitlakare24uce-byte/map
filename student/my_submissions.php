<?php
require_once '../auth/session.php';
requireLogin(['student']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get all submissions for the student
$submissions = $db->fetchAll("
    SELECT a.*, c.name as category_name 
    FROM activities a
    LEFT JOIN categories c ON a.category = c.id
    WHERE a.prn = ? 
    ORDER BY a.created_at DESC
", [$user['id']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-list"></i> My Submissions</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Submissions</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php
        $stats = $db->fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'Approved' THEN points ELSE 0 END) as total_points
            FROM activities WHERE prn = ?
        ", [$user['id']]);
        ?>
        
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?php echo $stats['total']; ?></h4>
                    <small>Total Submissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning"><?php echo $stats['pending']; ?></h4>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success"><?php echo $stats['approved']; ?></h4>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger"><?php echo $stats['rejected']; ?></h4>
                    <small>Rejected</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-info"><?php echo $stats['total_points']; ?></h4>
                    <small>Total Points</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <a href="submit_activity.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Submit New
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submissions Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-table"></i> All Submissions</h5>
        </div>
        <div class="card-body">
            <?php if ($submissions): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="submissionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Activity</th>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Points</th>
                                <th>Submitted On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo htmlspecialchars($submission['activity_type']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $submission['category']; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($submission['category_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($submission['level'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d M Y', strtotime($submission['date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = $submission['status'] === 'Approved' ? 'success' : 
                                                  ($submission['status'] === 'Rejected' ? 'danger' : 'warning');
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo $submission['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $submission['points']; ?></strong>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($submission['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewSubmission(<?php echo $submission['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($submission['certificate']): ?>
                                    <a href="../uploads/<?php echo $submission['certificate']; ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No Submissions Yet</h5>
                    <p class="text-muted">You haven't submitted any activities yet.</p>
                    <a href="submit_activity.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Submit Your First Activity
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Submission Details Modal -->
<div class="modal fade" id="submissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="submissionDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable if we have submissions
<?php if ($submissions): ?>
$(document).ready(function() {
    $('#submissionsTable').DataTable({
        "order": [[ 7, "desc" ]],
        "pageLength": 25,
        "responsive": true
    });
});
<?php endif; ?>

function viewSubmission(id) {
    fetch(`../api/get_submission_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const submission = data.submission;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Activity Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Activity:</strong></td><td>${submission.activity_type}</td></tr>
                                <tr><td><strong>Category:</strong></td><td>${submission.category}</td></tr>
                                <tr><td><strong>Level:</strong></td><td>${submission.level || 'N/A'}</td></tr>
                                <tr><td><strong>Date:</strong></td><td>${new Date(submission.date).toLocaleDateString()}</td></tr>
                                <tr><td><strong>Points:</strong></td><td>${submission.points}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Status Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-${submission.status === 'Approved' ? 'success' : (submission.status === 'Rejected' ? 'danger' : 'warning')}">${submission.status}</span></td></tr>
                                <tr><td><strong>Submitted:</strong></td><td>${new Date(submission.created_at).toLocaleString()}</td></tr>
                                ${submission.verified_at ? `<tr><td><strong>Verified:</strong></td><td>${new Date(submission.verified_at).toLocaleString()}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                `;
                
                if (submission.remarks) {
                    html += `<div class="mt-3"><h6>Your Remarks</h6><p>${submission.remarks}</p></div>`;
                }
                
                if (submission.coordinator_remarks) {
                    html += `<div class="mt-3"><h6>Coordinator Remarks</h6><p>${submission.coordinator_remarks}</p></div>`;
                }
                
                if (submission.certificate) {
                    html += `<div class="mt-3"><h6>Documents</h6><a href="../uploads/${submission.certificate}" target="_blank" class="btn btn-sm btn-outline-primary">View Certificate</a></div>`;
                }
                
                document.getElementById('submissionDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('submissionModal')).show();
            } else {
                alert('Error loading submission details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading submission details');
        });
}
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<?php include '../includes/footer.php'; ?>
