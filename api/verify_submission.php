<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a coordinator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'coordinator') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$db = new Database();
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required_fields = ['submission_id', 'action'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $submission_id = intval($_POST['submission_id']);
    $action = $_POST['action'];
    $points = intval($_POST['points'] ?? 0);
    $remarks = $_POST['remarks'] ?? '';
    
    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }
    
    // Get submission details to verify it belongs to coordinator's department
    $submission = $db->fetch("
        SELECT a.*, s.dept 
        FROM activities a 
        JOIN students s ON a.prn = s.prn 
        WHERE a.id = ? AND a.status = 'Pending'
    ", [$submission_id]);
    
    if (!$submission) {
        throw new Exception('Submission not found or already processed');
    }
    
    // Verify coordinator has access to this department
    if ($submission['dept'] !== $_SESSION['user_dept']) {
        throw new Exception('You do not have permission to verify this submission');
    }
    
    // Update submission
    $status = $action === 'approve' ? 'Approved' : 'Rejected';
    $final_points = $action === 'approve' ? $points : 0;
    
    $db->query("
        UPDATE activities 
        SET status = ?, points = ?, coordinator_remarks = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ", [$status, $final_points, $remarks, $_SESSION['user_id'], $submission_id]);
    
    $response['success'] = true;
    $response['message'] = "Submission {$status} successfully!";
    
    // Log the verification action
    error_log("Submission {$submission_id} {$status} by coordinator {$_SESSION['user_id']} with {$final_points} points");
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Verification error: ' . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
?>
