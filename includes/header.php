<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sanjivani University Multi-Activity Points (MAP) Management System">
    <meta name="keywords" content="Sanjivani University, MAP, Activities, Student Management">
    <meta name="author" content="Sanjivani University">
    
    <title>
        <?php 
        $page_title = 'Sanjivani University - MAP Management System';
        if (isset($_SESSION['user_type'])) {
            switch ($_SESSION['user_type']) {
                case 'student':
                    $page_title = 'Student Portal - ' . $page_title;
                    break;
                case 'coordinator':
                    $page_title = 'Coordinator Panel - ' . $page_title;
                    break;
                case 'hod':
                    $page_title = 'HoD Panel - ' . $page_title;
                    break;
                case 'admin':
                    $page_title = 'Admin Panel - ' . $page_title;
                    break;
            }
        }
        echo htmlspecialchars($page_title);
        ?>
    </title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../assets/css/style.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- No-cache for sensitive pages -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <?php endif; ?>
</head>
<body class="bg-light">
    <!-- Skip to main content for accessibility -->
    <a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="d-none">
        <div class="spinner-container">
            <div class="spinner-border spinner-border-lg text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
