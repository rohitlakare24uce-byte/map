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
            case 'add_rule':
                $admission_year = $_POST['admission_year'];
                $programme = $_POST['programme'];
                $duration = $_POST['duration'];
                $technical = $_POST['technical'];
                $sports_cultural = $_POST['sports_cultural'];
                $community_outreach = $_POST['community_outreach'];
                $innovation = $_POST['innovation'];
                $leadership = $_POST['leadership'];
                $total_points = $_POST['total_points'];
                
                $db->query("INSERT INTO programme_rules (admission_year, programme, duration, technical, sports_cultural, community_outreach, innovation, leadership, total_points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$admission_year, $programme, $duration, $technical, $sports_cultural, $community_outreach, $innovation, $leadership, $total_points]);
                
                $response['success'] = true;
                $response['message'] = 'Programme rule added successfully';
                break;
                
            case 'update_rule':
                $id = $_POST['id'];
                $technical = $_POST['technical'];
                $sports_cultural = $_POST['sports_cultural'];
                $community_outreach = $_POST['community_outreach'];
                $innovation = $_POST['innovation'];
                $leadership = $_POST['leadership'];
                $total_points = $_POST['total_points'];
                
                $db->query("UPDATE programme_rules SET technical = ?, sports_cultural = ?, community_outreach = ?, innovation = ?, leadership = ?, total_points = ? WHERE id = ?",
                    [$technical, $sports_cultural, $community_outreach, $innovation, $leadership, $total_points, $id]);
                
                $response['success'] = true;
                $response['message'] = 'Programme rule updated successfully';
                break;
                
            case 'delete_rule':
                $id = $_POST['id'];
                $db->query("DELETE FROM programme_rules WHERE id = ?", [$id]);
                
                $response['success'] = true;
                $response['message'] = 'Programme rule deleted successfully';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all programme rules
$programme_rules = $db->fetchAll("SELECT * FROM programme_rules ORDER BY admission_year DESC, programme");

// Get unique programmes and admission years
$programmes = ['B.Tech', 'B.Tech (DSY)', 'Integrated B.Tech', 'B.Pharm', 'BCA', 'MCA', 'B.Sc', 'M.Sc', 'B.Com', 'M.Com', 'BBA', 'MBA'];
$admission_years = ['2025-2026', '2024-2025', '2023-2024', '2022-2023'];

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-clipboard-list"></i> Programme Rules Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Programme Rules</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Add Rule Button -->
    <div class="row mb-4">
        <div class="col-12">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                <i class="fas fa-plus"></i> Add Programme Rule
            </button>
        </div>
    </div>
    
    <!-- Programme Rules Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-table"></i> Current Programme Rules</h5>
        </div>
        <div class="card-body">
            <?php if ($programme_rules): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="rulesTable">
                        <thead>
                            <tr>
                                <th>Admission Year</th>
                                <th>Programme</th>
                                <th>Duration</th>
                                <th>Technical (A)</th>
                                <th>Sports (B)</th>
                                <th>Community (C)</th>
                                <th>Innovation (D)</th>
                                <th>Leadership (E)</th>
                                <th>Total Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programme_rules as $rule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rule['admission_year']); ?></td>
                                <td><?php echo htmlspecialchars($rule['programme']); ?></td>
                                <td><?php echo $rule['duration']; ?> years</td>
                                <td><?php echo $rule['technical']; ?></td>
                                <td><?php echo $rule['sports_cultural']; ?></td>
                                <td><?php echo $rule['community_outreach']; ?></td>
                                <td><?php echo $rule['innovation']; ?></td>
                                <td><?php echo $rule['leadership']; ?></td>
                                <td><strong><?php echo $rule['total_points']; ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editRule(<?php echo htmlspecialchars(json_encode($rule)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRule(<?php echo $rule['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>No Programme Rules Found</h5>
                    <p class="text-muted">Add programme rules to define MAP requirements for different admission years and programmes.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Rules Summary -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Rules by Admission Year</h5>
                </div>
                <div class="card-body">
                    <?php
                    $year_summary = [];
                    foreach ($programme_rules as $rule) {
                        if (!isset($year_summary[$rule['admission_year']])) {
                            $year_summary[$rule['admission_year']] = 0;
                        }
                        $year_summary[$rule['admission_year']]++;
                    }
                    ?>
                    
                    <?php foreach ($year_summary as $year => $count): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo htmlspecialchars($year); ?></span>
                        <span class="badge bg-primary"><?php echo $count; ?> programmes</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Points Distribution Guide</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Typical Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>A</strong></td>
                                    <td>Technical Skills</td>
                                    <td>20-50 points</td>
                                </tr>
                                <tr>
                                    <td><strong>B</strong></td>
                                    <td>Sports & Cultural</td>
                                    <td>5-10 points</td>
                                </tr>
                                <tr>
                                    <td><strong>C</strong></td>
                                    <td>Community Outreach</td>
                                    <td>5-15 points</td>
                                </tr>
                                <tr>
                                    <td><strong>D</strong></td>
                                    <td>Innovation/IPR</td>
                                    <td>5-25 points</td>
                                </tr>
                                <tr>
                                    <td><strong>E</strong></td>
                                    <td>Leadership</td>
                                    <td>5-15 points</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Programme Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRuleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_rule">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admission_year" class="form-label">Admission Year *</label>
                                <select class="form-select" name="admission_year" required>
                                    <option value="">Select Admission Year</option>
                                    <?php foreach ($admission_years as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="programme" class="form-label">Programme *</label>
                                <select class="form-select" name="programme" required>
                                    <option value="">Select Programme</option>
                                    <?php foreach ($programmes as $prog): ?>
                                    <option value="<?php echo $prog; ?>"><?php echo $prog; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="duration" class="form-label">Course Duration (Years) *</label>
                        <select class="form-select" name="duration" required>
                            <option value="">Select Duration</option>
                            <option value="2">2 Years</option>
                            <option value="3">3 Years</option>
                            <option value="4">4 Years</option>
                            <option value="6">6 Years</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="technical" class="form-label">Technical Skills (A) *</label>
                                <input type="number" class="form-control" name="technical" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sports_cultural" class="form-label">Sports & Cultural (B) *</label>
                                <input type="number" class="form-control" name="sports_cultural" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="community_outreach" class="form-label">Community Outreach (C) *</label>
                                <input type="number" class="form-control" name="community_outreach" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="innovation" class="form-label">Innovation/IPR (D) *</label>
                                <input type="number" class="form-control" name="innovation" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="leadership" class="form-label">Leadership (E) *</label>
                                <input type="number" class="form-control" name="leadership" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_points" class="form-label">Total Points *</label>
                                <input type="number" class="form-control" name="total_points" min="0" required readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Rule Modal -->
<div class="modal fade" id="editRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Programme Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRuleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_rule">
                    <input type="hidden" name="id" id="edit_rule_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Admission Year</label>
                            <p class="form-control-plaintext" id="edit_admission_year"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programme</label>
                            <p class="form-control-plaintext" id="edit_programme"></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_technical" class="form-label">Technical Skills (A) *</label>
                                <input type="number" class="form-control" name="technical" id="edit_technical" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_sports_cultural" class="form-label">Sports & Cultural (B) *</label>
                                <input type="number" class="form-control" name="sports_cultural" id="edit_sports_cultural" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_community_outreach" class="form-label">Community Outreach (C) *</label>
                                <input type="number" class="form-control" name="community_outreach" id="edit_community_outreach" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_innovation" class="form-label">Innovation/IPR (D) *</label>
                                <input type="number" class="form-control" name="innovation" id="edit_innovation" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_leadership" class="form-label">Leadership (E) *</label>
                                <input type="number" class="form-control" name="leadership" id="edit_leadership" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_total_points" class="form-label">Total Points *</label>
                                <input type="number" class="form-control" name="total_points" id="edit_total_points" min="0" required readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-calculate total points
function calculateTotal(formPrefix = '') {
    const technical = parseInt(document.querySelector(`input[name="${formPrefix}technical"]`).value) || 0;
    const sports = parseInt(document.querySelector(`input[name="${formPrefix}sports_cultural"]`).value) || 0;
    const community = parseInt(document.querySelector(`input[name="${formPrefix}community_outreach"]`).value) || 0;
    const innovation = parseInt(document.querySelector(`input[name="${formPrefix}innovation"]`).value) || 0;
    const leadership = parseInt(document.querySelector(`input[name="${formPrefix}leadership"]`).value) || 0;
    
    const total = technical + sports + community + innovation + leadership;
    document.querySelector(`input[name="${formPrefix}total_points"]`).value = total;
}

// Add event listeners for auto-calculation
document.addEventListener('DOMContentLoaded', function() {
    // Add form
    ['technical', 'sports_cultural', 'community_outreach', 'innovation', 'leadership'].forEach(field => {
        document.querySelector(`#addRuleForm input[name="${field}"]`).addEventListener('input', () => calculateTotal());
    });
    
    // Edit form
    ['technical', 'sports_cultural', 'community_outreach', 'innovation', 'leadership'].forEach(field => {
        document.querySelector(`#editRuleForm input[name="${field}"]`).addEventListener('input', () => calculateTotal());
    });
});

// Form submission handlers
document.getElementById('addRuleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'addRuleModal');
});

document.getElementById('editRuleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'editRuleModal');
});

function submitForm(form, modalId) {
    const formData = new FormData(form);
    
    fetch('program_rules.php', {
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

function editRule(rule) {
    document.getElementById('edit_rule_id').value = rule.id;
    document.getElementById('edit_admission_year').textContent = rule.admission_year;
    document.getElementById('edit_programme').textContent = rule.programme;
    document.getElementById('edit_technical').value = rule.technical;
    document.getElementById('edit_sports_cultural').value = rule.sports_cultural;
    document.getElementById('edit_community_outreach').value = rule.community_outreach;
    document.getElementById('edit_innovation').value = rule.innovation;
    document.getElementById('edit_leadership').value = rule.leadership;
    document.getElementById('edit_total_points').value = rule.total_points;
    
    new bootstrap.Modal(document.getElementById('editRuleModal')).show();
}

function deleteRule(id) {
    if (confirm('Are you sure you want to delete this programme rule?')) {
        const formData = new FormData();
        formData.append('action', 'delete_rule');
        formData.append('id', id);
        
        fetch('program_rules.php', {
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

// Initialize DataTable
$(document).ready(function() {
    $('#rulesTable').DataTable({
        "pageLength": 25,
        "responsive": true
    });
});
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<?php include '../includes/footer.php'; ?>
