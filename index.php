<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanjivani University MAP Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3><i class="fas fa-university"></i> Sanjivani University</h3>
                        <p class="mb-0">MAP Management System</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="auth/login.php" method="POST">
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Login As</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="">Select User Type</option>
                                    <option value="student">Student</option>
                                    <option value="coordinator">Class Coordinator</option>
                                    <option value="hod">Head of Department</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username/PRN</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
