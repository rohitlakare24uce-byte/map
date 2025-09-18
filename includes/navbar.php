<?php
// Get current user information if logged in
$current_user = null;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $current_user = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'type' => $_SESSION['user_type'],
        'dept' => $_SESSION['user_dept'] ?? null,
        'programme' => $_SESSION['user_programme'] ?? null,
        'year' => $_SESSION['user_year'] ?? null
    ];
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $current_user ? ($current_dir . '/dashboard.php') : '/index.php'; ?>">
            <i class="fas fa-university fa-2x text-university-blue me-2"></i>
            <div>
                <div class="fw-bold text-university-blue">Sanjivani University</div>
                <small class="text-muted">MAP Management System</small>
            </div>
        </a>
        
        <?php if ($current_user): ?>
        <!-- Toggle button for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left side navigation based on user type -->
            <ul class="navbar-nav me-auto">
                <?php if ($current_user['type'] === 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'submit_activity.php' ? 'active' : ''; ?>" 
                           href="submit_activity.php">
                            <i class="fas fa-plus-circle me-1"></i>Submit Activity
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my_submissions.php' ? 'active' : ''; ?>" 
                           href="my_submissions.php">
                            <i class="fas fa-list me-1"></i>My Submissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'transcript.php' ? 'active' : ''; ?>" 
                           href="transcript.php">
                            <i class="fas fa-certificate me-1"></i>Transcript
                        </a>
                    </li>
                
                <?php elseif ($current_user['type'] === 'coordinator'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'verify_submissions.php' ? 'active' : ''; ?>" 
                           href="verify_submissions.php">
                            <i class="fas fa-check-circle me-1"></i>Verify Submissions
                            <?php
                            // Show pending count badge
                            $pending_count = 0;
                            if (isset($db)) {
                                $result = $db->fetch("
                                    SELECT COUNT(*) as count 
                                    FROM activities a
                                    JOIN students s ON a.prn = s.prn
                                    WHERE s.dept = ? AND a.status = 'Pending'
                                ", [$current_user['dept']]);
                                $pending_count = $result['count'];
                            }
                            if ($pending_count > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'student_compliance.php' ? 'active' : ''; ?>" 
                           href="student_compliance.php">
                            <i class="fas fa-users me-1"></i>Student Compliance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                    </li>
                
                <?php elseif ($current_user['type'] === 'hod'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'student_monitoring.php' ? 'active' : ''; ?>" 
                           href="student_monitoring.php">
                            <i class="fas fa-search me-1"></i>Student Monitoring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                    </li>
                
                <?php elseif ($current_user['type'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'user_management.php' ? 'active' : ''; ?>" 
                           href="user_management.php">
                            <i class="fas fa-users-cog me-1"></i>User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'program_rules.php' ? 'active' : ''; ?>" 
                           href="program_rules.php">
                            <i class="fas fa-clipboard-list me-1"></i>Program Rules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'activity_management.php' ? 'active' : ''; ?>" 
                           href="activity_management.php">
                            <i class="fas fa-tasks me-1"></i>Activities
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Right side - User info and logout -->
            <ul class="navbar-nav">
                <!-- Notifications (for students and coordinators) -->
                <?php if (in_array($current_user['type'], ['student', 'coordinator'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              id="notification-count" style="font-size: 0.6em;">
                            <?php
                            // Get notification count based on user type
                            $notification_count = 0;
                            if ($current_user['type'] === 'student' && isset($db)) {
                                $result = $db->fetch("
                                    SELECT COUNT(*) as count 
                                    FROM activities 
                                    WHERE prn = ? AND status != 'Pending' AND verified_at > datetime('now', '-7 days')
                                ", [$current_user['id']]);
                                $notification_count = $result['count'];
                            } elseif ($current_user['type'] === 'coordinator' && isset($db)) {
                                $result = $db->fetch("
                                    SELECT COUNT(*) as count 
                                    FROM activities a
                                    JOIN students s ON a.prn = s.prn
                                    WHERE s.dept = ? AND a.status = 'Pending'
                                ", [$current_user['dept']]);
                                $notification_count = $result['count'];
                            }
                            echo $notification_count > 0 ? $notification_count : '';
                            ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">Recent Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($notification_count > 0): ?>
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                You have <?php echo $notification_count; ?> new updates
                            </a></li>
                        <?php else: ?>
                            <li><span class="dropdown-item text-muted">No new notifications</span></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">View All Notifications</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="d-none d-md-block">
                            <div class="fw-semibold"><?php echo htmlspecialchars($current_user['name']); ?></div>
                            <small class="text-muted text-capitalize"><?php echo htmlspecialchars($current_user['type']); ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($current_user['name']); ?>
                        </h6></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- User Info -->
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">
                                <strong>Role:</strong> <?php echo ucfirst($current_user['type']); ?><br>
                                <?php if ($current_user['dept']): ?>
                                    <strong>Department:</strong> <?php echo htmlspecialchars($current_user['dept']); ?><br>
                                <?php endif; ?>
                                <?php if ($current_user['programme']): ?>
                                    <strong>Programme:</strong> <?php echo htmlspecialchars($current_user['programme']); ?><br>
                                <?php endif; ?>
                                <?php if ($current_user['year']): ?>
                                    <strong>Year:</strong> <?php echo $current_user['year']; ?><br>
                                <?php endif; ?>
                                <strong>ID:</strong> <?php echo htmlspecialchars($current_user['id']); ?>
                            </small>
                        </span></li>
                        
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Profile Actions -->
                        <li><a class="dropdown-item" href="#" onclick="changePassword()">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a></li>
                        
                        <?php if ($current_user['type'] === 'student'): ?>
                        <li><a class="dropdown-item" href="transcript.php">
                            <i class="fas fa-certificate me-2"></i>Download Transcript
                        </a></li>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item" href="#" onclick="showUserGuide()">
                            <i class="fas fa-question-circle me-2"></i>User Guide
                        </a></li>
                        
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Logout -->
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" class="ajax-form" action="../api/change_password.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               minlength="6" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Guide Modal -->
<div class="modal fade" id="userGuideModal" tabindex="-1" aria-labelledby="userGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userGuideModalLabel">
                    <i class="fas fa-question-circle me-2"></i>User Guide
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($current_user['type'] === 'student'): ?>
                    <h6>Student Guide</h6>
                    <div class="accordion" id="studentGuideAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    Submitting Activities
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#studentGuideAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Navigate to "Submit Activity" from the menu</li>
                                        <li>Select the appropriate category (A-E) for your activity</li>
                                        <li>Choose the specific activity type from the dropdown</li>
                                        <li>Select the level if applicable (College, District, State, etc.)</li>
                                        <li>Upload your certificate in PDF/JPG format</li>
                                        <li>Upload additional proof (geotagged photo, event photo, etc.)</li>
                                        <li>Fill in the date and any remarks</li>
                                        <li>Submit for coordinator review</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                    Tracking Progress
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#studentGuideAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Check your dashboard for overall progress</li>
                                        <li>View category-wise completion status</li>
                                        <li>Monitor pending submissions in "My Submissions"</li>
                                        <li>Download your transcript when needed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($current_user['type'] === 'coordinator'): ?>
                    <h6>Coordinator Guide</h6>
                    <div class="accordion" id="coordinatorGuideAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    Verifying Submissions
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#coordinatorGuideAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Go to "Verify Submissions" to see pending reviews</li>
                                        <li>Review uploaded certificates and proof documents</li>
                                        <li>Verify the authenticity of submitted documents</li>
                                        <li>Assign appropriate points based on activity level</li>
                                        <li>Approve or reject with appropriate remarks</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function changePassword() {
    new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
}

function showUserGuide() {
    new bootstrap.Modal(document.getElementById('userGuideModal')).show();
}

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/logout.php';
    }
}

// Password confirmation validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>
