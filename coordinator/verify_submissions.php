<?php
require_once '../auth/session.php';
requireLogin(['coordinator']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $submission_id = $_POST['submission_id'];
    $action = $_POST['action'];
    $points = intval($_POST['points']);
    $remarks = $_POST['remarks'];
    
    $status = $action === 'approve' ? 'Approved' : 'Rejected';
    $verified_points = $action === 'approve' ? $points : 0;
    
    try {
        $db->query("
            UPDATE activities 
            SET status = ?, points = ?, coordinator_remarks = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ", [$status, $verified_points, $remarks, $user['id'], $submission_id]);
        
        $message = "Submission {$status} successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error updating submission: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get pending submissions for the department
$pending_submissions = $db->fetchAll("
    SELECT a.*, s.first_name, s.last_name, s.programme, s.year, c.name as category_name,
           am.points_type, am.min_points, am.max_points
    FROM activities a
    JOIN students s ON a.prn = s.prn
    LEFT JOIN categories c ON a.category = c.id
    LEFT JOIN activities_master am ON a.activity_type = am.activity_name
    WHERE s.dept = ? AND a.status = 'Pending'
    ORDER BY a.created_at ASC
", [$user['dept']]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-check-circle"></i> Verify Submissions</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Verify Submissions</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($pending_submissions): ?>
        <div class="row">
            <?php foreach ($pending_submissions as $submission): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?>
                            <small class="text-muted">(<?php echo $submission['prn']; ?>)</small>
                        </h6>
                        <small class="text-muted">
                            <?php echo $submission['programme']; ?> - Year <?php echo $submission['year']; ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Activity:</strong> <?php echo htmlspecialchars($submission['activity_type']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Category:</strong> 
                            <span class="badge bg-secondary"><?php echo $submission['category']; ?></span>
                            <?php echo htmlspecialchars($submission['category_name']); ?>
                        </div>
                        <?php if ($submission['level']): ?>
                        <div class="mb-2">
                            <strong>Level:</strong> <?php echo htmlspecialchars($submission['level']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <strong>Date:</strong> <?php echo date('d M Y', strtotime($submission['date'])); ?>
                        </div>
                        <?php if ($submission['remarks']): ?>
                        <div class="mb-2">
                            <strong>Student Remarks:</strong>
                            <p class="small text-muted"><?php echo htmlspecialchars($submission['remarks']); ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <strong>Submitted:</strong> <?php echo date('d M Y H:i', strtotime($submission['created_at'])); ?>
                        </div>
                        
                        <!-- Documents -->
                        <div class="mb-3">
                            <div class="d-flex gap-2">
                                <?php if ($submission['certificate']): ?>
                                <a href="../uploads/<?php echo $submission['certificate']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-alt"></i> Certificate
                                </a>
                                <?php endif; ?>
                                <?php if ($submission['proof_file']): ?>
                                <a href="../uploads/<?php echo $submission['proof_file']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-image"></i> Proof
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success btn-sm" onclick="openVerificationModal(<?php echo $submission['id']; ?>, 'approve', '<?php echo htmlspecialchars($submission['activity_type']); ?>', '<?php echo $submission['points_type']; ?>', <?php echo $submission['min_points'] ?? 0; ?>, <?php echo $submission['max_points'] ?? 0; ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="openVerificationModal(<?php echo $submission['id']; ?>, 'reject', '<?php echo htmlspecialchars($submission['activity_type']); ?>')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5>No Pending Submissions</h5>
            <p class="text-muted">All submissions have been verified!</p>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Verify Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="submission_id" id="submission_id">
                    <input type="hidden" name="action" id="action">
                    
                    <div class="mb-3">
                        <label class="form-label">Activity:</label>
                        <p id="activity_name" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="mb-3" id="points_section">
                        <label for="points" class="form-label">Points to Award:</label>
                        <input type="number" class="form-control" id="points" name="points" min="0" required>
                        <div class="form-text" id="points_help"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks:</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter verification remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="submit_btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVerificationModal(submissionId, action, activityName, pointsType = null, minPoints = 0, maxPoints = 0) {
    document.getElementById('submission_id').value = submissionId;
    document.getElementById('action').value = action;
    document.getElementById('activity_name').textContent = activityName;
    
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submit_btn');
    const pointsSection = document.getElementById('points_section');
    const pointsInput = document.getElementById('points');
    const pointsHelp = document.getElementById('points_help');
    
    if (action === 'approve') {
        modalTitle.textContent = 'Approve Submission';
        submitBtn.textContent = 'Approve';
        submitBtn.className = 'btn btn-success';
        pointsSection.style.display = 'block';
        
        if (pointsType === 'Fixed') {
            pointsInput.value = minPoints;
            pointsInput.readOnly = true;
            pointsHelp.textContent = `Fixed points: ${minPoints}`;
        } else {
            pointsInput.value = minPoints;
            pointsInput.readOnly = false;
            pointsInput.min = minPoints;
            pointsInput.max = maxPoints || 50;
            pointsHelp.textContent = pointsType === 'Level' ? 
                `Points range: ${minPoints} - ${maxPoints || 50} (based on level)` : 
                `Enter points (minimum: ${minPoints})`;
        }
    } else {
        modalTitle.textContent = 'Reject Submission';
        submitBtn.textContent = 'Reject';
        submitBtn.className = 'btn btn-danger';
        pointsSection.style.display = 'none';
        pointsInput.value = 0;
    }
    
    new bootstrap.Modal(document.getElementById('verificationModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
