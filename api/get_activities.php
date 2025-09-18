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
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        throw new Exception('Category parameter is required');
    }
    
    // Validate category
    if (!in_array($category, ['A', 'B', 'C', 'D', 'E'])) {
        throw new Exception('Invalid category');
    }
    
    // Get activities for the specified category
    $activities = $db->fetchAll("
        SELECT am.*, 
               GROUP_CONCAT(al.level || ':' || al.points) as levels
        FROM activities_master am
        LEFT JOIN activity_levels al ON am.id = al.activity_id
        WHERE am.category_id = ?
        GROUP BY am.id
        ORDER BY am.activity_name
    ", [$category]);
    
    // Format the response
    $formatted_activities = [];
    foreach ($activities as $activity) {
        $formatted_activity = [
            'id' => $activity['id'],
            'activity_name' => $activity['activity_name'],
            'document_evidence' => $activity['document_evidence'],
            'points_type' => $activity['points_type'],
            'min_points' => $activity['min_points'],
            'max_points' => $activity['max_points'],
            'levels' => []
        ];
        
        // Parse levels if they exist
        if ($activity['levels']) {
            $levels = explode(',', $activity['levels']);
            foreach ($levels as $level_info) {
                $parts = explode(':', $level_info);
                if (count($parts) === 2) {
                    $formatted_activity['levels'][$parts[0]] = intval($parts[1]);
                }
            }
        }
        
        $formatted_activities[] = $formatted_activity;
    }
    
    header('Content-Type: application/json');
    echo json_encode($formatted_activities);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
