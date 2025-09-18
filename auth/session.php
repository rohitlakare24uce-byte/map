<?php
function requireLogin($allowed_types = []) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header("Location: ../index.php?error=Please login to continue");
        exit();
    }
    
    if (!empty($allowed_types) && !in_array($_SESSION['user_type'], $allowed_types)) {
        header("Location: ../auth/logout.php");
        exit();
    }
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'],
        'type' => $_SESSION['user_type'],
        'name' => $_SESSION['user_name'],
        'dept' => $_SESSION['user_dept'] ?? null,
        'programme' => $_SESSION['user_programme'] ?? null,
        'year' => $_SESSION['user_year'] ?? null,
        'admission_year' => $_SESSION['admission_year'] ?? null
    ];
}
?>
