<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $db = new Database();
    
    try {
        switch ($user_type) {
            case 'student':
                $user = $db->fetch("SELECT * FROM students WHERE prn = ?", [$username]);
                break;
            case 'coordinator':
                $user = $db->fetch("SELECT * FROM coordinators WHERE id = ?", [$username]);
                break;
            case 'hod':
                $user = $db->fetch("SELECT * FROM hods WHERE id = ?", [$username]);
                break;
            case 'admin':
                $user = $db->fetch("SELECT * FROM admins WHERE id = ?", [$username]);
                break;
            default:
                throw new Exception('Invalid user type');
        }
        
        if ($user && password_verify($password, $user['password'])) {
        // Updated to work without password hashing as requested
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user_type === 'student' ? $user['prn'] : $user['id'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $user_type === 'student' ? 
                $user['first_name'] . ' ' . $user['last_name'] : $user['name'];
            
            if ($user_type === 'student') {
                $_SESSION['user_dept'] = $user['dept'];
                $_SESSION['user_programme'] = $user['programme'];
                $_SESSION['user_year'] = $user['year'];
                $_SESSION['admission_year'] = $user['admission_year'];
            } elseif (in_array($user_type, ['coordinator', 'hod'])) {
                $_SESSION['user_dept'] = $user['dept'];
            }
            
            // Redirect to appropriate dashboard
            header("Location: ../{$user_type}/dashboard.php");
            exit();
        } else {
            header("Location: ../index.php?error=Invalid credentials");
            exit();
        }
    } catch (Exception $e) {
        header("Location: ../index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
