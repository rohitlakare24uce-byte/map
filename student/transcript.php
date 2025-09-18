<?php
require_once '../auth/session.php';
requireLogin(['student']);

require_once '../config/database.php';
$db = new Database();
$user = getCurrentUser();

// If PDF download is requested
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    require_once '../vendor/autoload.php'; // Would need TCPDF or similar
    // For now, we'll create a simple HTML to PDF conversion
    
    ob_start();
    include 'transcript_pdf.php';
    $html = ob_get_clean();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="MAP_Transcript_' . $user['id'] . '.pdf"');
    
    // Simple HTML to PDF conversion (would need proper library in production)
    echo $html;
    exit();
}

// Get student information
$student = $db->fetch("SELECT * FROM students WHERE prn = ?", [$user['id']]);

// Get programme rules
$rules = $db->fetch("SELECT * FROM programme_rules WHERE admission_year = ? AND programme = ?", 
    [$user['admission_year'], $user['programme']]);

// Get all approved activities
$activities = $db->fetchAll("
    SELECT a.*, c.name as category_name 
    FROM activities a
    LEFT JOIN categories c ON a.category = c.id
    WHERE a.prn = ? AND a.status = 'Approved'
    ORDER BY a.category, a.date
", [$user['id']]);

// Calculate points by category
$points_by_category = [];
foreach ($activities as $activity) {
    if (!isset($points_by_category[$activity['category']])) {
        $points_by_category[$activity['category']] = 0;
    }
    $points_by_category[$activity['category']] += $activity['points'];
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-certificate"></i> MAP Transcript</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Transcript</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="?download=pdf" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card" id="transcript">
        <div class="card-header bg-primary text-white">
            <div class="text-center">
                <h3>Sanjivani University</h3>
                <h5>Multi-Activity Points (MAP) Transcript</h5>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Student Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6><strong>Student Information</strong></h6>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>PRN:</strong></td>
                            <td><?php echo htmlspecialchars($student['prn']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Department:</strong></td>
                            <td><?php echo htmlspecialchars($student['dept']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Programme:</strong></td>
                            <td><?php echo htmlspecialchars($student['programme']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6><strong>Academic Information</strong></h6>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Current Year:</strong></td>
                            <td><?php echo $student['year']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Admission Year:</strong></td>
                            <td><?php echo htmlspecialchars($student['admission_year']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Course Duration:</strong></td>
                            <td><?php echo $student['course_duration']; ?> years</td>
                        </tr>
                        <tr>
                            <td><strong>Generated On:</strong></td>
                            <td><?php echo date('d M Y H:i'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Points Summary -->
            <?php if ($rules): ?>
            <div class="mb-4">
                <h6><strong>MAP Points Summary</strong></h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Required Points</th>
                                <th>Earned Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>A</strong> - Technical Skills</td>
                                <td><?php echo $rules['technical']; ?></td>
                                <td><?php echo $points_by_category['A'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    $earned = $points_by_category['A'] ?? 0;
                                    $status = $earned >= $rules['technical'] ? 'Completed' : 'Shortfall';
                                    $badge = $earned >= $rules['technical'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>B</strong> - Sports & Cultural</td>
                                <td><?php echo $rules['sports_cultural']; ?></td>
                                <td><?php echo $points_by_category['B'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    $earned = $points_by_category['B'] ?? 0;
                                    $status = $earned >= $rules['sports_cultural'] ? 'Completed' : 'Shortfall';
                                    $badge = $earned >= $rules['sports_cultural'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>C</strong> - Community Outreach</td>
                                <td><?php echo $rules['community_outreach']; ?></td>
                                <td><?php echo $points_by_category['C'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    $earned = $points_by_category['C'] ?? 0;
                                    $status = $earned >= $rules['community_outreach'] ? 'Completed' : 'Shortfall';
                                    $badge = $earned >= $rules['community_outreach'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>D</strong> - Innovation</td>
                                <td><?php echo $rules['innovation']; ?></td>
                                <td><?php echo $points_by_category['D'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    $earned = $points_by_category['D'] ?? 0;
                                    $status = $earned >= $rules['innovation'] ? 'Completed' : 'Shortfall';
                                    $badge = $earned >= $rules['innovation'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>E</strong> - Leadership</td>
                                <td><?php echo $rules['leadership']; ?></td>
                                <td><?php echo $points_by_category['E'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    $earned = $points_by_category['E'] ?? 0;
                                    $status = $earned >= $rules['leadership'] ? 'Completed' : 'Shortfall';
                                    $badge = $earned >= $rules['leadership'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <tr class="table-secondary">
                                <td><strong>TOTAL</strong></td>
                                <td><strong><?php echo $rules['total_points']; ?></strong></td>
                                <td><strong><?php echo array_sum($points_by_category); ?></strong></td>
                                <td>
                                    <?php 
                                    $total_earned = array_sum($points_by_category);
                                    $overall_status = $total_earned >= $rules['total_points'] ? 'Eligible' : 'Not Eligible';
                                    $overall_badge = $total_earned >= $rules['total_points'] ? 'success' : 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $overall_badge; ?>"><?php echo $overall_status; ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Detailed Activities -->
            <div class="mb-4">
                <h6><strong>Detailed Activity Records</strong></h6>
                <?php if ($activities): ?>
                    <?php foreach (['A', 'B', 'C', 'D', 'E'] as $category): ?>
                        <?php 
                        $category_activities = array_filter($activities, function($a) use ($category) {
                            return $a['category'] === $category;
                        });
                        ?>
                        
                        <?php if ($category_activities): ?>
                        <div class="mb-3">
                            <h6 class="text-primary">Category <?php echo $category; ?> Activities</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Activity</th>
                                            <th>Level</th>
                                            <th>Date</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sr = 1; foreach ($category_activities as $activity): ?>
                                        <tr>
                                            <td><?php echo $sr++; ?></td>
                                            <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['level'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($activity['date'])); ?></td>
                                            <td><?php echo $activity['points']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-secondary">
                                            <td colspan="4"><strong>Subtotal</strong></td>
                                            <td><strong><?php echo $points_by_category[$category]; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No approved activities found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="row mt-5">
                <div class="col-12">
                    <hr>
                    <p class="text-muted text-center">
                        <small>
                            This transcript is computer generated and contains only approved activities as of <?php echo date('d M Y H:i'); ?>.
                            <br>
                            For verification, contact the Academic Office, Sanjivani University.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .breadcrumb, .btn, .no-print {
        display: none !important;
    }
    
    #transcript {
        box-shadow: none !important;
        border: none !important;
    }
    
    .card-header {
        background: #000 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
