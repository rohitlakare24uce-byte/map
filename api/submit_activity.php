<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
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
    $required_fields = ['category', 'activity_type', 'date', 'proof_type'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate files
    if (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Certificate file is required');
    }
    
    if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Proof file is required');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file types and sizes
    $allowed_cert_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $allowed_proof_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    // Validate certificate file
    $cert_file = $_FILES['certificate'];
    if (!in_array($cert_file['type'], $allowed_cert_types)) {
        throw new Exception('Certificate must be PDF, JPG, or PNG format');
    }
    if ($cert_file['size'] > $max_file_size) {
        throw new Exception('Certificate file size must be less than 5MB');
    }
    
    // Validate proof file
    $proof_file = $_FILES['proof_file'];
    if (!in_array($proof_file['type'], $allowed_proof_types)) {
        throw new Exception('Proof file must be JPG or PNG format');
    }
    if ($proof_file['size'] > $max_file_size) {
        throw new Exception('Proof file size must be less than 5MB');
    }
    
    // Generate unique file names
    $cert_extension = pathinfo($cert_file['name'], PATHINFO_EXTENSION);
    $proof_extension = pathinfo($proof_file['name'], PATHINFO_EXTENSION);
    
    $cert_filename = $_SESSION['user_id'] . '_' . time() . '_cert.' . $cert_extension;
    $proof_filename = $_SESSION['user_id'] . '_' . time() . '_proof.' . $proof_extension;
    
    $cert_path = $upload_dir . $cert_filename;
    $proof_path = $upload_dir . $proof_filename;
    
    // Move uploaded files
    if (!move_uploaded_file($cert_file['tmp_name'], $cert_path)) {
        throw new Exception('Failed to upload certificate file');
    }
    
    if (!move_uploaded_file($proof_file['tmp_name'], $proof_path)) {
        // Clean up certificate file if proof upload fails
        unlink($cert_path);
        throw new Exception('Failed to upload proof file');
    }
    
    // Insert activity record
    $query = "INSERT INTO activities (prn, category, activity_type, level, certificate, date, remarks, proof_type, proof_file, status, points, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 0, CURRENT_TIMESTAMP)";
    
    $params = [
        $_SESSION['user_id'],
        $_POST['category'],
        $_POST['activity_type'],
        $_POST['level'] ?? null,
        $cert_filename,
        $_POST['date'],
        $_POST['remarks'] ?? null,
        $_POST['proof_type'],
        $proof_filename
    ];
    
    $db->query($query, $params);
    
    $response['success'] = true;
    $response['message'] = 'Activity submitted successfully! It will be reviewed by your coordinator.';
    
} catch (Exception $e) {
    // Clean up uploaded files on error
    if (isset($cert_path) && file_exists($cert_path)) {
        unlink($cert_path);
    }
    if (isset($proof_path) && file_exists($proof_path)) {
        unlink($proof_path);
    }
    
    $response['message'] = $e->getMessage();
    error_log('Activity submission error: ' . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
?>
