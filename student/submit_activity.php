<?php
require_once '../auth/session.php';
requireLogin(['student']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Get categories and activities
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY id");

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-plus-circle"></i> Submit Activity</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Submit Activity</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-form"></i> Activity Submission Form</h5>
                </div>
                <div class="card-body">
                    <form id="activityForm" action="../api/submit_activity.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo $category['id']; ?> - <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="activity_type" class="form-label">Activity Type *</label>
                                    <select class="form-select" id="activity_type" name="activity_type" required disabled>
                                        <option value="">Select Category First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Level</label>
                                    <select class="form-select" id="level" name="level">
                                        <option value="">Select Level (if applicable)</option>
                                        <option value="College">College</option>
                                        <option value="District">District</option>
                                        <option value="State">State</option>
                                        <option value="National">National</option>
                                        <option value="International">International</option>
                                        <option value="Dept">Department</option>
                                        <option value="University">University</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="certificate" class="form-label">Certificate/Proof Document *</label>
                            <input type="file" class="form-control" id="certificate" name="certificate" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Upload certificate or proof document (PDF, JPG, PNG - Max 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="proof_type" class="form-label">Additional Proof Type *</label>
                            <select class="form-select" id="proof_type" name="proof_type" required>
                                <option value="">Select Proof Type</option>
                                <option value="geotag">Geotagged Image</option>
                                <option value="event_image">Event Image</option>
                                <option value="group_image">Group Image</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="proof_file" class="form-label">Additional Proof File *</label>
                            <input type="file" class="form-control" id="proof_file" name="proof_file" 
                                   accept=".jpg,.jpeg,.png" required>
                            <div class="form-text">Upload additional proof as per selected type (JPG, PNG - Max 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                      placeholder="Enter any additional remarks or details about the activity"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Activity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load activities based on selected category
document.getElementById('category').addEventListener('change', function() {
    const category = this.value;
    const activitySelect = document.getElementById('activity_type');
    
    if (category) {
        fetch(`../api/get_activities.php?category=${category}`)
            .then(response => response.json())
            .then(data => {
                activitySelect.innerHTML = '<option value="">Select Activity Type</option>';
                data.forEach(activity => {
                    activitySelect.innerHTML += `<option value="${activity.activity_name}">${activity.activity_name}</option>`;
                });
                activitySelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading activities:', error);
            });
    } else {
        activitySelect.innerHTML = '<option value="">Select Category First</option>';
        activitySelect.disabled = true;
    }
});

// Form submission
document.getElementById('activityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../api/submit_activity.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Activity submitted successfully!');
            window.location.href = 'my_submissions.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the activity.');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
