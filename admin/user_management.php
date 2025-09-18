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
            case 'add_student':
                $prn = $_POST['prn'];
                $first_name = $_POST['first_name'];
                $middle_name = $_POST['middle_name'];
                $last_name = $_POST['last_name'];
                $dept = $_POST['dept'];
                $year = $_POST['year'];
                $programme = $_POST['programme'];
                $course_duration = $_POST['course_duration'];
                $admission_year = $_POST['admission_year'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $db->query("INSERT INTO students (prn, first_name, middle_name, last_name, dept, year, programme, course_duration, admission_year, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$prn, $first_name, $middle_name, $last_name, $dept, $year, $programme, $course_duration, $admission_year, $password]);
                
                $response['success'] = true;
                $response['message'] = 'Student added successfully';
                break;
                
            case 'add_coordinator':
                $name = $_POST['name'];
                $dept = $_POST['dept'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $db->query("INSERT INTO coordinators (name, dept, password) VALUES (?, ?, ?)",
                    [$name, $dept, $password]);
                
                $response['success'] = true;
                $response['message'] = 'Coordinator added successfully';
                break;
                
            case 'add_hod':
                $name = $_POST['name'];
                $dept = $_POST['dept'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $db->query("INSERT INTO hods (name, dept, password) VALUES (?, ?, ?)",
                    [$name, $dept, $password]);
                
                $response['success'] = true;
                $response['message'] = 'HoD added successfully';
                break;
                
            case 'reset_password':
                $user_type = $_POST['user_type'];
                $user_id = $_POST['user_id'];
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $table = $user_type === 'student' ? 'students' : $user_type . 's';
                $id_field = $user_type === 'student' ? 'prn' : 'id';
                
                $db->query("UPDATE $table SET password = ? WHERE $id_field = ?",
                    [$new_password, $user_id]);
                
                $response['success'] = true;
                $response['message'] = 'Password reset successfully';
                break;
                
            case 'delete_user':
                $user_type = $_POST['user_type'];
                $user_id = $_POST['user_id'];
                
                $table = $user_type === 'student' ? 'students' : $user_type . 's';
                $id_field = $user_type === 'student' ? 'prn' : 'id';
                
                $db->query("DELETE FROM $table WHERE $id_field = ?", [$user_id]);
                
                $response['success'] = true;
                $response['message'] = 'User deleted successfully';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all users
$students = $db->fetchAll("SELECT * FROM students ORDER BY dept, year, last_name");
$coordinators = $db->fetchAll("SELECT * FROM coordinators ORDER BY dept, name");
$hods = $db->fetchAll("SELECT * FROM hods ORDER BY dept, name");
$admins = $db->fetchAll("SELECT * FROM admins ORDER BY name");

// Get unique departments and programmes
$departments = $db->fetchAll("SELECT DISTINCT dept FROM students ORDER BY dept");
$programmes = $db->fetchAll("SELECT DISTINCT programme FROM students ORDER BY programme");
$admission_years = $db->fetchAll("SELECT DISTINCT admission_year FROM students ORDER BY admission_year DESC");

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-users-cog"></i> User Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">User Management</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Add User Forms -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">Students</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="coordinators-tab" data-bs-toggle="tab" data-bs-target="#coordinators" type="button" role="tab">Coordinators</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="hods-tab" data-bs-toggle="tab" data-bs-target="#hods" type="button" role="tab">HoDs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab">Admins</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="userTabContent">
                        <!-- Students Tab -->
                        <div class="tab-pane fade show active" id="students" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                        <i class="fas fa-plus"></i> Add Student
                                    </button>
                                    <button class="btn btn-info" onclick="importStudents()">
                                        <i class="fas fa-upload"></i> Import Students
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>PRN</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Year</th>
                                            <th>Programme</th>
                                            <th>Admission Year</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['prn']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['dept']); ?></td>
                                            <td><?php echo $student['year']; ?></td>
                                            <td><?php echo htmlspecialchars($student['programme']); ?></td>
                                            <td><?php echo htmlspecialchars($student['admission_year']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="resetPassword('student', '<?php echo $student['prn']; ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser('student', '<?php echo $student['prn']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Coordinators Tab -->
                        <div class="tab-pane fade" id="coordinators" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoordinatorModal">
                                        <i class="fas fa-plus"></i> Add Coordinator
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coordinators as $coordinator): ?>
                                        <tr>
                                            <td><?php echo $coordinator['id']; ?></td>
                                            <td><?php echo htmlspecialchars($coordinator['name']); ?></td>
                                            <td><?php echo htmlspecialchars($coordinator['dept']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="resetPassword('coordinator', '<?php echo $coordinator['id']; ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser('coordinator', '<?php echo $coordinator['id']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- HoDs Tab -->
                        <div class="tab-pane fade" id="hods" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHodModal">
                                        <i class="fas fa-plus"></i> Add HoD
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hods as $hod): ?>
                                        <tr>
                                            <td><?php echo $hod['id']; ?></td>
                                            <td><?php echo htmlspecialchars($hod['name']); ?></td>
                                            <td><?php echo htmlspecialchars($hod['dept']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="resetPassword('hod', '<?php echo $hod['id']; ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser('hod', '<?php echo $hod['id']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Admins Tab -->
                        <div class="tab-pane fade" id="admins" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td><?php echo $admin['id']; ?></td>
                                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="resetPassword('admin', '<?php echo $admin['id']; ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
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
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStudentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_student">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="prn" class="form-label">PRN *</label>
                                <input type="text" class="form-control" name="prn" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="dept" class="form-label">Department *</label>
                                <select class="form-select" name="dept" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['dept']); ?>"><?php echo htmlspecialchars($dept['dept']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="year" class="form-label">Year *</label>
                                <select class="form-select" name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="course_duration" class="form-label">Course Duration *</label>
                                <select class="form-select" name="course_duration" required>
                                    <option value="">Duration</option>
                                    <option value="2">2 Years</option>
                                    <option value="3">3 Years</option>
                                    <option value="4">4 Years</option>
                                    <option value="6">6 Years</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="programme" class="form-label">Programme *</label>
                                <select class="form-select" name="programme" required>
                                    <option value="">Select Programme</option>
                                    <?php foreach ($programmes as $prog): ?>
                                    <option value="<?php echo htmlspecialchars($prog['programme']); ?>"><?php echo htmlspecialchars($prog['programme']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admission_year" class="form-label">Admission Year *</label>
                                <select class="form-select" name="admission_year" required>
                                    <option value="">Select Admission Year</option>
                                    <?php foreach ($admission_years as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year['admission_year']); ?>"><?php echo htmlspecialchars($year['admission_year']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Coordinator Modal -->
<div class="modal fade" id="addCoordinatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Coordinator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCoordinatorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_coordinator">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="dept" class="form-label">Department *</label>
                        <select class="form-select" name="dept" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['dept']); ?>"><?php echo htmlspecialchars($dept['dept']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Coordinator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add HoD Modal -->
<div class="modal fade" id="addHodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add HoD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHodForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_hod">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="dept" class="form-label">Department *</label>
                        <select class="form-select" name="dept" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['dept']); ?>"><?php echo htmlspecialchars($dept['dept']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add HoD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_type" id="reset_user_type">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form submission handlers
document.getElementById('addStudentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'addStudentModal');
});

document.getElementById('addCoordinatorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'addCoordinatorModal');
});

document.getElementById('addHodForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'addHodModal');
});

document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm(this, 'resetPasswordModal');
});

function submitForm(form, modalId) {
    const formData = new FormData(form);
    
    fetch('user_management.php', {
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

function resetPassword(userType, userId) {
    document.getElementById('reset_user_type').value = userType;
    document.getElementById('reset_user_id').value = userId;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteUser(userType, userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_type', userType);
        formData.append('user_id', userId);
        
        fetch('user_management.php', {
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

function importStudents() {
    alert('Import functionality would be implemented here with CSV/Excel file upload');
}

// Initialize DataTable
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "pageLength": 25,
        "responsive": true
    });
});
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<?php include '../includes/footer.php'; ?>
