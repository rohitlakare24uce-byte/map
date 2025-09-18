<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$db = new Database();

try {
    $user_type = $_SESSION['user_type'];
    $user_id = $_SESSION['user_id'];
    $response = ['success' => true, 'data' => []];
    
    switch ($user_type) {
        case 'student':
            // Get student's programme rules
            $rules = $db->fetch("
                SELECT pr.* FROM programme_rules pr
                JOIN students s ON pr.admission_year = s.admission_year AND pr.programme = s.programme
                WHERE s.prn = ?
            ", [$user_id]);
            
            // Get student's points by category
            $points = $db->fetchAll("
                SELECT 
                    category,
                    SUM(CASE WHEN status = 'Approved' THEN points ELSE 0 END) as earned_points,
                    COUNT(*) as total_submissions,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_submissions
                FROM activities 
                WHERE prn = ? 
                GROUP BY category
            ", [$user_id]);
            
            $response['data'] = [
                'rules' => $rules,
                'points' => $points
            ];
            break;
            
        case 'coordinator':
            // Get department statistics
            $dept_stats = $db->fetch("
                SELECT 
                    COUNT(DISTINCT s.prn) as total_students,
                    COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions,
                    COUNT(CASE WHEN a.status = 'Approved' THEN 1 END) as approved_submissions
                FROM students s
                LEFT JOIN activities a ON s.prn = a.prn
                WHERE s.dept = ?
            ", [$_SESSION['user_dept']]);
            
            $response['data'] = ['department_stats' => $dept_stats];
            break;
            
        case 'hod':
            // Get department overview
            $dept_overview = $db->fetch("
                SELECT 
                    COUNT(DISTINCT s.prn) as total_students,
                    COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
                        (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
                        THEN s.prn END) as compliant_students,
                    AVG(CASE WHEN pr.total_points > 0 THEN 
                        (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) * 100 
                        ELSE 0 END) as avg_completion
                FROM students s
                LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                LEFT JOIN activities a ON s.prn = a.prn
                WHERE s.dept = ?
            ", [$_SESSION['user_dept']]);
            
            $response['data'] = ['department_overview' => $dept_overview];
            break;
            
        case 'admin':
            // Get university-wide statistics
            $university_stats = $db->fetch("
                SELECT 
                    COUNT(DISTINCT s.prn) as total_students,
                    COUNT(DISTINCT s.dept) as total_departments,
                    COUNT(DISTINCT s.programme) as total_programmes,
                    COUNT(DISTINCT CASE WHEN pr.total_points > 0 AND 
                        (COALESCE(SUM(CASE WHEN a.status = 'Approved' THEN a.points ELSE 0 END), 0) / pr.total_points) >= 1 
                        THEN s.prn END) as compliant_students,
                    COUNT(CASE WHEN a.status = 'Pending' THEN 1 END) as pending_submissions
                FROM students s
                LEFT JOIN programme_rules pr ON s.admission_year = pr.admission_year AND s.programme = pr.programme
                LEFT JOIN activities a ON s.prn = a.prn
            ");
            
            $response['data'] = ['university_stats' => $university_stats];
            break;
            
        default:
            throw new Exception('Invalid user type');
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
