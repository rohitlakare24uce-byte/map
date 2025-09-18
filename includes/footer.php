    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-university fa-2x text-university-gold me-3"></i>
                        <div>
                            <h5 class="mb-1 text-white">Sanjivani University</h5>
                            <p class="mb-0 text-light">Multi-Activity Points Management System</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mt-3 mt-md-0">
                        <p class="mb-1 text-light">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:map@sanjivani.edu.in" class="text-university-gold">map@sanjivani.edu.in</a>
                        </p>
                        <p class="mb-1 text-light">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:+919876543210" class="text-university-gold">+91 98765 43210</a>
                        </p>
                        <p class="mb-0 text-light">
                            <i class="fas fa-globe me-2"></i>
                            <a href="https://www.sanjivani.edu.in" target="_blank" class="text-university-gold">www.sanjivani.edu.in</a>
                        </p>
                    </div>
                </div>
            </div>
            <hr class="my-4 border-light">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">
                        &copy; <?php echo date('Y'); ?> Sanjivani University. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end mt-2 mt-md-0">
                        <a href="#" class="text-university-gold me-3" data-bs-toggle="modal" data-bs-target="#privacyModal">
                            <i class="fas fa-shield-alt me-1"></i>Privacy Policy
                        </a>
                        <a href="#" class="text-university-gold me-3" data-bs-toggle="modal" data-bs-target="#termsModal">
                            <i class="fas fa-file-contract me-1"></i>Terms of Use
                        </a>
                        <a href="#" class="text-university-gold" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle me-1"></i>Help
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Information Collection</h6>
                    <p>The MAP Management System collects only necessary academic and activity information required for evaluation and compliance tracking.</p>
                    
                    <h6>Data Security</h6>
                    <p>All personal and academic data is securely stored and protected using industry-standard security measures.</p>
                    
                    <h6>Information Sharing</h6>
                    <p>Student information is only shared with authorized university personnel for academic evaluation purposes.</p>
                    
                    <h6>Data Retention</h6>
                    <p>Data is retained for the duration of the student's academic program and as required by university policies.</p>
                    
                    <h6>Contact Information</h6>
                    <p>For privacy concerns, contact the university at <a href="mailto:privacy@sanjivani.edu.in">privacy@sanjivani.edu.in</a></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms of Use Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2"></i>Terms of Use
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Acceptable Use</h6>
                    <p>This system is for authorized university personnel and students only. Misuse may result in suspension of access.</p>
                    
                    <h6>Data Accuracy</h6>
                    <p>Users are responsible for ensuring the accuracy of submitted information and documentation.</p>
                    
                    <h6>System Availability</h6>
                    <p>The university strives to maintain system availability but cannot guarantee uninterrupted access.</p>
                    
                    <h6>Compliance</h6>
                    <p>All users must comply with university policies and applicable laws regarding data protection and academic integrity.</p>
                    
                    <h6>Modifications</h6>
                    <p>The university reserves the right to modify these terms and system functionality as needed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">
                        <i class="fas fa-question-circle me-2"></i>Help & Support
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Getting Started</h6>
                    <ul>
                        <li>Students: Use your PRN and assigned password to login</li>
                        <li>Staff: Use your employee ID and assigned password</li>
                        <li>Contact your coordinator for password reset</li>
                    </ul>
                    
                    <h6>Submitting Activities</h6>
                    <ul>
                        <li>Select the appropriate category (A-E) for your activity</li>
                        <li>Upload clear, legible certificates in PDF/JPG format</li>
                        <li>Provide additional proof as required (geotagged photos, etc.)</li>
                        <li>Wait for coordinator verification</li>
                    </ul>
                    
                    <h6>Technical Issues</h6>
                    <ul>
                        <li>Clear browser cache and cookies</li>
                        <li>Try using a different browser</li>
                        <li>Ensure stable internet connection</li>
                        <li>Contact IT support if issues persist</li>
                    </ul>
                    
                    <h6>Contact Support</h6>
                    <div class="alert alert-info">
                        <strong>Email:</strong> <a href="mailto:map-support@sanjivani.edu.in">map-support@sanjivani.edu.in</a><br>
                        <strong>Phone:</strong> <a href="tel:+919876543210">+91 98765 43210</a><br>
                        <strong>Office Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container for Notifications -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../assets/js/main.js"></script>
    
    <!-- Initialize app -->
    <script>
        // Set current year in footer
        document.addEventListener('DOMContentLoaded', function() {
            // Add current page highlight to navigation
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
            
            // Auto-logout after 30 minutes of inactivity
            let inactivityTimer;
            const TIMEOUT_DURATION = 30 * 60 * 1000; // 30 minutes
            
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(() => {
                    if (confirm('Your session will expire soon due to inactivity. Would you like to continue?')) {
                        resetTimer();
                    } else {
                        window.location.href = '../auth/logout.php';
                    }
                }, TIMEOUT_DURATION);
            }
            
            // Reset timer on user activity
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, resetTimer, true);
            });
            
            resetTimer();
        });
    </script>
    
    <?php
    // Display any PHP session messages as toasts
    if (isset($_SESSION['success_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Utils.showToast('" . addslashes($_SESSION['success_message']) . "', 'success');
            });
        </script>";
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Utils.showToast('" . addslashes($_SESSION['error_message']) . "', 'danger');
            });
        </script>";
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['warning_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Utils.showToast('" . addslashes($_SESSION['warning_message']) . "', 'warning');
            });
        </script>";
        unset($_SESSION['warning_message']);
    }
    
    if (isset($_SESSION['info_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Utils.showToast('" . addslashes($_SESSION['info_message']) . "', 'info');
            });
        </script>";
        unset($_SESSION['info_message']);
    }
    ?>
</body>
</html>
