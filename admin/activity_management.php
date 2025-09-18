<?php
require_once '../auth/session.php';
requireLogin(['admin']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'add_activity':
                $category_id = $_POST['category_id'];
                $activity_name = $_POST['activity_name'];
                $document_evidence = $_POST['document_evidence'];
                $points_type = $_POST['points_type'];
                $min_points = $_POST['min_points'] ?: null;
                $max_points = $_POST['max_points'] ?: null;
                
                $activity_id = $db->query("INSERT INTO activities_master (category_id, activity_name, document_evidence, points_type, min_points, max_points) VALUES (?, ?, ?, ?, ?, ?)",
                    [$category_id, $activity_name, $document_evidence, $points_type, $min_points, $max_points]);
                
                // Add levels if points_type is Level
                if ($points_type === 'Level' && isset($_POST['levels'])) {
                    foreach ($_POST['levels'] as $level => $points) {
                        if ($points > 0) {
                            $db->query("INSERT INTO activity_levels (activity_id, level, points) VALUES (?, ?, ?)",
                                [$db->lastInsertId(), $level, $points]);
                        }
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'Activity added successfully';
                break;
                
            case 'update_activity':
                $id = $_POST['id'];
                $activity_name = $_POST['activity_name'];
                $document_evidence = $_POST['document_evidence'];
                $min_points = $_POST['min_points'] ?: null;
                $max_points = $_POST['max_points'] ?: null;
                
                $db->query("UPDATE activities_master SET activity_name = ?, document_evidence = ?, min_points = ?, max_points = ? WHERE id = ?",
                    [$activity_name, $document_evidence, $min_points, $max_points, $id]);
                
                // Update levels if provided
                if (isset($_POST['levels'])) {
                    $db->query("DELETE FROM activity_levels WHERE activity_id = ?", [$id]);
                    foreach ($_POST['levels'] as $level => $points) {
                        if ($points > 0) {
                            $db->query("INSERT INTO activity_levels (activity_id, level, points) VALUES (?, ?, ?)",
                                [$id, $level, $points]);
                        }
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'Activity updated successfully';
                break;
                
            case 'delete_activity':
                $id = $_POST['id'];
                $db->query("DELETE FROM activities_master WHERE id = ?", [$id]);
                
                $response['success'] = true;
                $response['message'] = 'Activity deleted successfully';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all activities with their levels
$activities = $db->fetchAll("
    SELECT am.*, c.name as category_name,
           GROUP_CONCAT(al.level || ':' || al.points) as levels
    FROM activities_master am
    LEFT JOIN categories c ON am.category_id = c.id
    LEFT JOIN activity_levels al ON am.id = al.activity_id
    GROUP BY am.id
    ORDER BY am.category_id, am.activity_name
");

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY id");

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-tasks"></i> Activity Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Activity Management</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Add Activity Button -->
    <div class="row mb-4">
        <div class="col-12">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="fas fa-plus"></i> Add Activity
            </button>
        </div>
    </div>
    
    <!-- Activities by Category -->
    <?php foreach ($categories as $category): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-layer-group"></i> Category <?php echo $category['id']; ?> - <?php echo htmlspecialchars($category['name']); ?></h5>
        </div>
        <div class="card-body">
            <?php
            $category_activities = array_filter($activities, function($activity) use ($category) {
                return $activity['category_id'] === $category['id'];
            });
            ?>
            
            <?php if ($category_activities): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Activity Name</th>
                                <th>Document Evidence</th>
                                <th>Points Type</th>
                                <th>Points/Levels</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_activities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['activity_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['document_evidence']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $activity['points_type'] === 'Fixed' ? 'success' : 'info'; ?>">
                                        <?php echo $activity['points_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($activity['points_type'] === 'Fixed'): ?>
                                        <strong><?php echo $activity['min_points']; ?></strong> points
                                    <?php else: ?>
                                        <?php if ($activity['levels']): ?>
                                            <?php
                                            $levels = explode(',', $activity['levels']);
                                            foreach ($levels as $level_info) {
                                                $parts = explode(':', $level_info);
                                                if (count($parts) === 2) {
                                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($parts[0]) . ': ' . $parts[1] . '</span>';
                                                }
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">No levels defined</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editActivity(<?php echo htmlspecialchars(json_encode($activity)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteActivity(<?php echo $activity['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No activities defined for this category.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Activity Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addActivityForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_activity">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" name="category_id" required>
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
                                <label for="points_type" class="form-label">Points Type *</label>
                                <select class="form-select" name="points_type" id="points_type" onchange="togglePointsFields()" required>
                                    <option value="">Select Points Type</option>
                                    <option value="Fixed">Fixed Points</option>
                                    <option value="Level">Level-based Points</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity_name" class="form-label">Activity Name *</label>
                        <input type="text" class="form-control" name="activity_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_evidence" class="form-label">Document Evidence Required *</label>
                        <input type="text" class="form-control" name="document_evidence" 
                               placeholder="e.g., Certificate, Report, Letter" required>
                    </div>
                    
                    <!-- Fixed Points Section -->
                    <div id="fixed_points_section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_points" class="form-label">Points</label>
                                    <input type="number" class="form-control" name="min_points" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_points" class="form-label">Max Points (if range)</label>
                                    <input type="number" class="form-control" name="max_points" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Level-based Points Section -->
                    <div id="level_points_section" style="display: none;">
                        <h6>Points by Level</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">College</label>
                                    <input type="number" class="form-control" name="levels[College]" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">District</label>
                                    <input type="number" class="form-control" name="levels[District]" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">State</label>
                                    <input type="number" class="form-control" name="levels[State]" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">National</label>
                                    <input type="number" class="form-control" name="levels[National]" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">International</label>
                                    <input type="number" class="form-control" name="levels[International]" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">University</label>
                                    <input type="number" class="form-control" name="levels[University]" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Activity Modal -->
<div class="modal fade" id="editActivityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editActivityForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_activity">
                    <input type="hidden" name="id" id="edit_activity_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <p class="form-control-plaintext" id="edit_category"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Points Type</label>
                            <p class="form-control-plaintext" id="edit_points_type"></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_activity_name" class="form-label">Activity Name *</label>
                        <input type="text" class="form-control" name="activity_name" id="edit_activity_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_document_evidence" class="form-label">Document Evidence Required *</label>
                        <input type="text" class="form-control" name="document_evidence" id="edit_document_evidence" required>
                    </div>
                    
                    <!-- Fixed Points Section -->
                    <div id="edit_fixed_points_section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_min_points" class="form-label">Points</label>
                                    <input type="number" class="form-control" name="min_points" id="edit_min_points" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_max_points" class="form-label">Max Points (if range)</label>
                                    <input type="number" class="form-control" name="max_points" id="edit_max_points" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Level-based Points Section -->
                    <div id="edit_level_points_section" style="display: none;">
                        <h6>Points by Level</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">College</label>
                                    <input type="number" class="form-control" name="levels[College]" id="edit_level_college" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">District</label>
                                    <input type="number" class="form-control" name="levels[District]" id="edit_level_district" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">State</label>
                                    <input type="number" class="form-control" name="levels[State]" id="edit_level_state" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">National</label>
                                    <input type="number" class="form-control" name="levels[National]" id="edit_level_national" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">International</label>
                                    <input type="number" class="form-control" name="levels[International]" id="edit_level_international" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">University</label>
                                    <input type="number" class="form-control" name="levels[University]" id="edit_level_university" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePointsFields() {
    const pointsType = document.getElementById('points_type').value;
    const fixedSection = document.getElementById('fixed_points_section');
    const levelSection = document.getElementById('level_points_section');
    
    if (pointsType === 'Fixed') {
        fixedSection.style.display = 'block';
        levelSection.style.display = 'none';
    } else if (pointsType === 'Level') {
        fixedSection.style.display = 'none';
        levelSection.style.display = 'block';
    } else {
        fixedSection.style.display = 'none';
        levelSection.style.display = 'none';
    }
}

// Form submission handlers
document.getElementById('addActivityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'addActivityModal');
});

document.getElementById('editActivityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'editActivityModal');
});

function submitForm(form, modalId) {
    const formData = new FormData(form);
    
    fetch('activity_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function editActivity(activity) {
    document.getElementById('edit_activity_id').value = activity.id;
    document.getElementById('edit_category').textContent = activity.category_id + ' - ' + activity.category_name;
    document.getElementById('edit_points_type').textContent = activity.points_type;
    document.getElementById('edit_activity_name').value = activity.activity_name;
    document.getElementById('edit_document_evidence').value = activity.document_evidence;
    
    // Reset sections
    document.getElementById('edit_fixed_points_section').style.display = 'none';
    document.getElementById('edit_level_points_section').style.display = 'none';
    
    if (activity.points_type === 'Fixed') {
        document.getElementById('edit_fixed_points_section').style.display = 'block';
        document.getElementById('edit_min_points').value = activity.min_points || '';
        document.getElementById('edit_max_points').value = activity.max_points || '';
    } else if (activity.points_type === 'Level') {
        document.getElementById('edit_level_points_section').style.display = 'block';
        
        // Reset level inputs
        ['College', 'District', 'State', 'National', 'International', 'University'].forEach(level => {
            document.getElementById('edit_level_' + level.toLowerCase()).value = '';
        });
        
        // Parse and populate existing levels
        if (activity.levels) {
            const levels = activity.levels.split(',');
            levels.forEach(levelInfo => {
                const parts = levelInfo.split(':');
                if (parts.length === 2) {
                    const level = parts[0];
                    const points = parts[1];
                    const input = document.getElementById('edit_level_' + level.toLowerCase());
                    if (input) {
                        input.value = points;
                    }
                }
            });
        }
    }
    
    new bootstrap.Modal(document.getElementById('editActivityModal')).show();
}

function deleteActivity(id) {
    if (confirm('Are you sure you want to delete this activity?')) {
        const formData = new FormData();
        formData.append('action', 'delete_activity');
        formData.append('id', id);
        
        fetch('activity_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
