<?php
// MySQL Database Initialization Script for MAP Management System
// Run this file once to create the database and tables

$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database if it exists and create new one
    $pdo->exec("DROP DATABASE IF EXISTS map_management");
    $pdo->exec("CREATE DATABASE map_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'map_management' created successfully.\n";
    
    // Use the database
    $pdo->exec("USE map_management");
    
    // Create tables in proper order (considering foreign key constraints)
    $tables = [
        // Categories table (referenced by activities_master)
        "CREATE TABLE categories (
            id CHAR(1) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Students table
        "CREATE TABLE students (
            prn VARCHAR(20) PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            last_name VARCHAR(50) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            year INT NOT NULL,
            programme VARCHAR(50) NOT NULL,
            course_duration INT NOT NULL,
            admission_year VARCHAR(9) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(15),
            password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dept (dept),
            INDEX idx_year (year),
            INDEX idx_programme (programme),
            INDEX idx_admission_year (admission_year)
        ) ENGINE=InnoDB",
        
        // Coordinators table
        "CREATE TABLE coordinators (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(15),
            password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dept (dept)
        ) ENGINE=InnoDB",
        
        // HoDs table
        "CREATE TABLE hods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(15),
            password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dept (dept)
        ) ENGINE=InnoDB",
        
        // Admins table
        "CREATE TABLE admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(15),
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Programme rules table
        "CREATE TABLE programme_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admission_year VARCHAR(9) NOT NULL,
            programme VARCHAR(50) NOT NULL,
            duration INT NOT NULL,
            technical INT NOT NULL,
            sports_cultural INT NOT NULL,
            community_outreach INT NOT NULL,
            innovation INT NOT NULL,
            leadership INT NOT NULL,
            total_points INT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_programme_year (admission_year, programme),
            INDEX idx_admission_year (admission_year),
            INDEX idx_programme (programme)
        ) ENGINE=InnoDB",
        
        // Activities master table
        "CREATE TABLE activities_master (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id CHAR(1) NOT NULL,
            activity_name VARCHAR(150) NOT NULL,
            description TEXT,
            document_evidence VARCHAR(150) NOT NULL,
            points_type ENUM('Fixed','Level') NOT NULL,
            min_points INT DEFAULT NULL,
            max_points INT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            INDEX idx_category (category_id),
            INDEX idx_points_type (points_type)
        ) ENGINE=InnoDB",
        
        // Activity levels table
        "CREATE TABLE activity_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_id INT NOT NULL,
            level VARCHAR(50) NOT NULL,
            points INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (activity_id) REFERENCES activities_master(id) ON DELETE CASCADE,
            UNIQUE KEY unique_activity_level (activity_id, level),
            INDEX idx_activity_id (activity_id),
            INDEX idx_level (level)
        ) ENGINE=InnoDB",
        
        // Activities submitted by students
        "CREATE TABLE activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prn VARCHAR(20) NOT NULL,
            category CHAR(1) NOT NULL,
            activity_type VARCHAR(100) NOT NULL,
            level VARCHAR(20),
            certificate VARCHAR(255),
            date DATE NOT NULL,
            remarks TEXT,
            proof_type VARCHAR(50),
            proof_file VARCHAR(255),
            status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
            points INT DEFAULT 0,
            coordinator_remarks TEXT,
            verified_by INT,
            verified_at TIMESTAMP NULL,
            rejection_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (prn) REFERENCES students(prn) ON DELETE CASCADE,
            FOREIGN KEY (category) REFERENCES categories(id),
            INDEX idx_prn (prn),
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_date (date),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB",
        
        // System logs table
        "CREATE TABLE system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type ENUM('student', 'coordinator', 'hod', 'admin') NOT NULL,
            user_id VARCHAR(20) NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_type, user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB",
        
        // Notifications table
        "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type ENUM('student', 'coordinator', 'hod', 'admin') NOT NULL,
            user_id VARCHAR(20) NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            related_activity_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (related_activity_id) REFERENCES activities(id) ON DELETE SET NULL,
            INDEX idx_user (user_type, user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB",
        
        // File uploads table
        "CREATE TABLE file_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_size INT NOT NULL,
            upload_type ENUM('certificate', 'proof') NOT NULL,
            uploaded_by VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
            INDEX idx_activity_id (activity_id),
            INDEX idx_upload_type (upload_type),
            INDEX idx_uploaded_by (uploaded_by)
        ) ENGINE=InnoDB"
    ];
    
    // Execute table creation
    foreach ($tables as $table_sql) {
        try {
            $pdo->exec($table_sql);
            echo "Table created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert initial data
    $initial_data = [
        // Categories with descriptions
        "INSERT INTO categories (id, name, description) VALUES 
            ('A', 'Technical Skills', 'Activities related to technical competencies, programming, projects, and professional development'),
            ('B', 'Sports & Cultural', 'Participation in sports events, cultural activities, and artistic performances'),
            ('C', 'Community Outreach & Social Initiatives', 'Social service, community development, and outreach programs'),
            ('D', 'Innovation / IPR / Entrepreneurship', 'Innovation projects, intellectual property, patents, and entrepreneurial activities'),
            ('E', 'Leadership / Management', 'Leadership roles, management positions, and organizational activities')",
        
        // Sample admin user
        "INSERT INTO admins (id, name, email, password, role) VALUES 
            (1, 'System Administrator', 'admin@sanjivani.edu.in', 'admin123', 'super_admin')",
        
        // Programme rules for 2025-2026
        "INSERT INTO programme_rules 
            (admission_year, programme, duration, technical, sports_cultural, community_outreach, innovation, leadership, total_points)
            VALUES
            ('2025-2026', 'B.Tech', 4, 45, 10, 10, 25, 10, 100),
            ('2025-2026', 'B.Tech (DSY)', 3, 30, 10, 10, 15, 10, 75),
            ('2025-2026', 'Integrated B.Tech', 6, 50, 10, 15, 25, 15, 120),
            ('2025-2026', 'B.Pharm', 4, 45, 10, 15, 20, 10, 100),
            ('2025-2026', 'BCA', 3, 20, 10, 10, 10, 10, 60),
            ('2025-2026', 'MCA', 2, 20, 5, 10, 5, 10, 50),
            ('2025-2026', 'B.Sc', 3, 20, 10, 10, 10, 10, 60),
            ('2025-2026', 'M.Sc', 2, 20, 5, 5, 10, 10, 50),
            ('2025-2026', 'B.Com', 3, 20, 10, 10, 10, 10, 60),
            ('2025-2026', 'M.Com', 2, 20, 5, 5, 10, 10, 50),
            ('2025-2026', 'BBA', 3, 20, 10, 10, 10, 10, 60),
            ('2025-2026', 'MBA', 2, 20, 10, 10, 10, 10, 60)",
        
        // Programme rules for 2024-2025
        "INSERT INTO programme_rules 
            (admission_year, programme, duration, technical, sports_cultural, community_outreach, innovation, leadership, total_points)
            VALUES
            ('2024-2025', 'B.Tech', 4, 30, 5, 10, 20, 10, 75),
            ('2024-2025', 'B.Tech (DSY)', 3, 20, 5, 5, 15, 5, 50),
            ('2024-2025', 'B.Com', 3, 15, 5, 5, 10, 10, 45),
            ('2024-2025', 'BBA', 3, 20, 5, 5, 5, 10, 45),
            ('2024-2025', 'MBA', 2, 10, 5, 5, 5, 5, 30)",
        
        // Programme rules for 2023-2024
        "INSERT INTO programme_rules 
            (admission_year, programme, duration, technical, sports_cultural, community_outreach, innovation, leadership, total_points)
            VALUES
            ('2023-2024', 'B.Tech', 4, 25, 5, 8, 15, 7, 60),
            ('2023-2024', 'B.Tech (DSY)', 3, 18, 4, 4, 12, 4, 42),
            ('2023-2024', 'BCA', 3, 15, 5, 5, 8, 7, 40),
            ('2023-2024', 'B.Com', 3, 12, 4, 4, 8, 8, 36),
            ('2023-2024', 'BBA', 3, 15, 4, 4, 4, 8, 35)"
    ];
    
    foreach ($initial_data as $data_sql) {
        try {
            $pdo->exec($data_sql);
            echo "Initial data inserted successfully.\n";
        } catch (PDOException $e) {
            echo "Error inserting data: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert activities master data
    $activities_data = [
        // Category A - Technical Skills (Level-based)
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type) VALUES
            ('A', 'Paper Presentation', 'Technical paper presentation at conferences or symposiums', 'Certificate of Participation/Award', 'Level'),
            ('A', 'Project Competition', 'Participation in technical project competitions', 'Certificate of Participation/Award', 'Level'),
            ('A', 'Hackathons / Ideathons', 'Participation in coding competitions and innovation challenges', 'Certificate of Participation/Award', 'Level'),
            ('A', 'Poster Competitions', 'Technical poster presentations and competitions', 'Certificate of Participation/Award', 'Level'),
            ('A', 'Competitive Programming', 'Programming contests and coding competitions', 'Certificate of Participation/Award', 'Level'),
            ('A', 'Workshop Attendance', 'Technical workshops and skill development programs', 'Certificate of Completion', 'Level'),
            ('A', 'Industrial Training / Case Studies', 'Industry training programs and case study presentations', 'Certificate/Report', 'Level')",
        
        // Category A - Fixed points
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type, min_points, max_points) VALUES
            ('A', 'MOOC with Final Assessment', 'Online courses with certification from recognized platforms', 'Certificate of Completion', 'Fixed', 5, 5),
            ('A', 'Internship / Professional Certification', 'Industry internships and professional certifications', 'Certificate/Letter', 'Fixed', 10, 15),
            ('A', 'Industrial / Exhibition Visit', 'Educational visits to industries and technical exhibitions', 'Visit Report with Photos', 'Fixed', 3, 5),
            ('A', 'Language Proficiency', 'English proficiency tests and foreign language certifications', 'Certificate', 'Fixed', 5, 10),
            ('A', 'Technical Publication', 'Research papers published in journals or conferences', 'Publication Certificate', 'Fixed', 15, 25),
            ('A', 'Software Development Project', 'Individual or team software development projects', 'Project Report and Demo', 'Fixed', 10, 20)",
        
        // Category B - Sports & Cultural
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type) VALUES
            ('B', 'Sports Participation', 'Participation in sports events and competitions', 'Certificate of Participation/Award', 'Level'),
            ('B', 'Cultural Participation', 'Participation in cultural events, music, dance, drama', 'Certificate of Participation/Award', 'Level'),
            ('B', 'Sports Organization', 'Organizing sports events and tournaments', 'Event Report and Certificate', 'Level'),
            ('B', 'Cultural Organization', 'Organizing cultural events and festivals', 'Event Report and Certificate', 'Level')",
        
        // Category C - Community Outreach (Fixed)
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type, min_points, max_points) VALUES
            ('C', 'Community Service (Two Day)', 'Short-term community service activities', 'Certificate/Letter from Organization', 'Fixed', 3, 3),
            ('C', 'Community Service (Up to One Week)', 'Medium-term community service projects', 'Certificate/Letter from Organization', 'Fixed', 6, 6),
            ('C', 'Community Service (One Month)', 'Long-term community engagement projects', 'Certificate/Letter from Organization', 'Fixed', 9, 9),
            ('C', 'Community Service (One Semester/Year)', 'Extended community service commitments', 'Certificate/Letter from Organization', 'Fixed', 12, 15),
            ('C', 'Blood Donation', 'Blood donation drives and health awareness campaigns', 'Blood Donation Certificate', 'Fixed', 2, 2),
            ('C', 'Environmental Initiative', 'Tree plantation, cleanliness drives, environmental awareness', 'Activity Report with Photos', 'Fixed', 4, 8),
            ('C', 'Teaching/Tutoring', 'Teaching underprivileged children or peer tutoring', 'Certificate from Institution', 'Fixed', 5, 10)",
        
        // Category D - Innovation
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type, min_points, max_points) VALUES
            ('D', 'Entrepreneurship / IPR Workshop', 'Workshops on entrepreneurship and intellectual property', 'Certificate of Participation', 'Fixed', 5, 5),
            ('D', 'MSME Programme', 'Micro, Small and Medium Enterprises development programs', 'Certificate of Completion', 'Fixed', 8, 8),
            ('D', 'Awards/Recognitions for Products', 'Recognition for innovative products or solutions', 'Award Certificate', 'Fixed', 15, 20),
            ('D', 'Completed Prototype Development', 'Development of working prototypes', 'Project Report and Demo', 'Fixed', 20, 25),
            ('D', 'Filed a Patent', 'Patent application filing', 'Patent Filing Certificate', 'Fixed', 10, 10),
            ('D', 'Published Patent', 'Patent publication in official gazette', 'Patent Publication Certificate', 'Fixed', 15, 15),
            ('D', 'Patent Granted', 'Patent grant by patent office', 'Patent Grant Certificate', 'Fixed', 25, 25),
            ('D', 'Registered Start-up Company', 'Company registration and incorporation', 'Certificate of Incorporation', 'Fixed', 20, 20),
            ('D', 'Revenue/Profits Generated', 'Business revenue generation with proof', 'Financial Statements/Receipts', 'Fixed', 25, 30),
            ('D', 'Attracted Investor Funding', 'Securing investment for startup/project', 'Investment Agreement/Certificate', 'Fixed', 30, 35),
            ('D', 'International Conference / Journal', 'Publications in Scopus/UGC recognized venues', 'Publication Certificate', 'Fixed', 20, 25),
            ('D', 'Innovation Implemented by Industry', 'Industry adoption of student innovation', 'Implementation Certificate', 'Fixed', 25, 30),
            ('D', 'Social Innovation / Grassroot Value Addition', 'Innovations addressing social problems', 'Impact Report and Certificate', 'Fixed', 15, 20),
            ('D', 'Business Plan Competition', 'Participation in business plan contests', 'Certificate of Participation/Award', 'Fixed', 10, 15),
            ('D', 'Product Development', 'Complete product development cycle', 'Product Report and Demo', 'Fixed', 15, 20)",
        
        // Category E - Leadership (Level + Fixed)
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type) VALUES
            ('E', 'Club/Association Participation', 'Active membership in student clubs and associations', 'Membership Certificate', 'Level'),
            ('E', 'Club/Association Leadership', 'Leadership positions in student organizations', 'Appointment Letter/Certificate', 'Level'),
            ('E', 'Event Organization', 'Organizing college/department events', 'Event Report and Certificate', 'Level'),
            ('E', 'Student Government', 'Participation in student council and governance', 'Election Certificate/Appointment Letter', 'Level')",
        
        "INSERT INTO activities_master (category_id, activity_name, description, document_evidence, points_type, min_points, max_points) VALUES
            ('E', 'Professional Society Membership', 'Membership in IEEE, CSI, ACM, etc.', 'Membership Certificate', 'Fixed', 5, 5),
            ('E', 'Special Initiatives for University', 'Special projects for university development', 'Project Report and Approval', 'Fixed', 8, 12),
            ('E', 'Mentoring Program', 'Mentoring junior students or peers', 'Mentoring Certificate', 'Fixed', 6, 10),
            ('E', 'Volunteer Coordination', 'Coordinating volunteer activities', 'Coordination Certificate', 'Fixed', 5, 8)"
    ];
    
    foreach ($activities_data as $activity_sql) {
        try {
            $pdo->exec($activity_sql);
            echo "Activities data inserted successfully.\n";
        } catch (PDOException $e) {
            echo "Error inserting activities: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert activity levels for level-based activities
    $level_activities = [
        'Paper Presentation' => [
            'College' => 3, 'District' => 6, 'State' => 9, 'National' => 12, 'International' => 15, 'University' => 6, 'Dept' => 3
        ],
        'Project Competition' => [
            'College' => 4, 'District' => 8, 'State' => 12, 'National' => 16, 'International' => 20, 'University' => 8, 'Dept' => 4
        ],
        'Hackathons / Ideathons' => [
            'College' => 5, 'District' => 8, 'State' => 12, 'National' => 15, 'International' => 20, 'University' => 8, 'Dept' => 5
        ],
        'Poster Competitions' => [
            'College' => 2, 'District' => 4, 'State' => 6, 'National' => 8, 'International' => 10, 'University' => 4, 'Dept' => 2
        ],
        'Competitive Programming' => [
            'College' => 3, 'District' => 6, 'State' => 9, 'National' => 12, 'International' => 15, 'University' => 6, 'Dept' => 3
        ],
        'Workshop Attendance' => [
            'College' => 2, 'District' => 3, 'State' => 4, 'National' => 5, 'International' => 6, 'University' => 3, 'Dept' => 2
        ],
        'Industrial Training / Case Studies' => [
            'College' => 4, 'District' => 6, 'State' => 8, 'National' => 10, 'International' => 12, 'University' => 6, 'Dept' => 4
        ],
        'Sports Participation' => [
            'College' => 2, 'District' => 4, 'State' => 6, 'National' => 8, 'International' => 10, 'University' => 4, 'Dept' => 2
        ],
        'Cultural Participation' => [
            'College' => 2, 'District' => 4, 'State' => 6, 'National' => 8, 'International' => 10, 'University' => 4, 'Dept' => 2
        ],
        'Sports Organization' => [
            'College' => 3, 'District' => 5, 'State' => 7, 'National' => 9, 'International' => 12, 'University' => 5, 'Dept' => 3
        ],
        'Cultural Organization' => [
            'College' => 3, 'District' => 5, 'State' => 7, 'National' => 9, 'International' => 12, 'University' => 5, 'Dept' => 3
        ],
        'Club/Association Participation' => [
            'College' => 2, 'District' => 3, 'State' => 4, 'National' => 5, 'International' => 6, 'University' => 3, 'Dept' => 2
        ],
        'Club/Association Leadership' => [
            'College' => 4, 'District' => 6, 'State' => 8, 'National' => 10, 'International' => 12, 'University' => 6, 'Dept' => 4
        ],
        'Event Organization' => [
            'College' => 3, 'District' => 5, 'State' => 7, 'National' => 9, 'International' => 12, 'University' => 5, 'Dept' => 3
        ],
        'Student Government' => [
            'College' => 5, 'District' => 7, 'State' => 9, 'National' => 12, 'International' => 15, 'University' => 7, 'Dept' => 5
        ]
    ];
    
    foreach ($level_activities as $activity_name => $levels) {
        // Get activity ID
        $stmt = $pdo->prepare("SELECT id FROM activities_master WHERE activity_name = ?");
        $stmt->execute([$activity_name]);
        $activity = $stmt->fetch();
        
        if ($activity) {
            foreach ($levels as $level => $points) {
                try {
                    $pdo->exec("INSERT INTO activity_levels (activity_id, level, points) VALUES ({$activity['id']}, '$level', $points)");
                } catch (PDOException $e) {
                    echo "Error inserting level data for $activity_name: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // Insert sample data
    echo "\nInserting sample data...\n";
    
    // Sample students
    $sample_students = [
        "INSERT INTO students (prn, first_name, middle_name, last_name, dept, year, programme, course_duration, admission_year, email, phone, password) VALUES
            ('2025001', 'Rahul', 'Kumar', 'Sharma', 'Computer Engineering', 1, 'B.Tech', 4, '2025-2026', 'rahul.sharma@student.sanjivani.edu.in', '9876543210', 'student123'),
            ('2025002', 'Priya', 'Suresh', 'Patel', 'Information Technology', 1, 'B.Tech', 4, '2025-2026', 'priya.patel@student.sanjivani.edu.in', '9876543211', 'student123'),
            ('2025003', 'Amit', 'Rajesh', 'Singh', 'Electronics Engineering', 2, 'B.Tech', 4, '2024-2025', 'amit.singh@student.sanjivani.edu.in', '9876543212', 'student123'),
            ('2025004', 'Sneha', 'Mohan', 'Gupta', 'Computer Science', 1, 'BCA', 3, '2025-2026', 'sneha.gupta@student.sanjivani.edu.in', '9876543213', 'student123'),
            ('2025005', 'Vikram', 'Anil', 'Joshi', 'Mechanical Engineering', 3, 'B.Tech', 4, '2023-2024', 'vikram.joshi@student.sanjivani.edu.in', '9876543214', 'student123'),
            ('2025006', 'Anita', 'Prakash', 'Desai', 'Civil Engineering', 2, 'B.Tech', 4, '2024-2025', 'anita.desai@student.sanjivani.edu.in', '9876543215', 'student123'),
            ('2025007', 'Ravi', 'Santosh', 'Kumar', 'Electrical Engineering', 1, 'B.Tech', 4, '2025-2026', 'ravi.kumar@student.sanjivani.edu.in', '9876543216', 'student123'),
            ('2025008', 'Kavya', 'Ramesh', 'Nair', 'Biotechnology', 2, 'B.Tech', 4, '2024-2025', 'kavya.nair@student.sanjivani.edu.in', '9876543217', 'student123'),
            ('2025009', 'Arjun', 'Vijay', 'Patil', 'Computer Engineering', 3, 'B.Tech', 4, '2023-2024', 'arjun.patil@student.sanjivani.edu.in', '9876543218', 'student123'),
            ('2025010', 'Pooja', 'Ashok', 'Mehta', 'Information Technology', 1, 'B.Tech', 4, '2025-2026', 'pooja.mehta@student.sanjivani.edu.in', '9876543219', 'student123')"
    ];
    
    // Sample coordinators
    $sample_coordinators = [
        "INSERT INTO coordinators (name, dept, email, phone, password) VALUES
            ('Dr. Rajesh Kumar', 'Computer Engineering', 'rajesh.kumar@sanjivani.edu.in', '9876543220', 'coord123'),
            ('Prof. Sunita Sharma', 'Information Technology', 'sunita.sharma@sanjivani.edu.in', '9876543221', 'coord123'),
            ('Dr. Anil Patil', 'Electronics Engineering', 'anil.patil@sanjivani.edu.in', '9876543222', 'coord123'),
            ('Prof. Meera Joshi', 'Mechanical Engineering', 'meera.joshi@sanjivani.edu.in', '9876543223', 'coord123'),
            ('Dr. Suresh Gupta', 'Computer Science', 'suresh.gupta@sanjivani.edu.in', '9876543224', 'coord123'),
            ('Prof. Neha Agarwal', 'Civil Engineering', 'neha.agarwal@sanjivani.edu.in', '9876543225', 'coord123'),
            ('Dr. Manoj Verma', 'Electrical Engineering', 'manoj.verma@sanjivani.edu.in', '9876543226', 'coord123'),
            ('Prof. Sanjana Rao', 'Biotechnology', 'sanjana.rao@sanjivani.edu.in', '9876543227', 'coord123')"
    ];
    
    // Sample HoDs
    $sample_hods = [
        "INSERT INTO hods (name, dept, email, phone, password) VALUES
            ('Dr. Prakash Desai', 'Computer Engineering', 'prakash.desai@sanjivani.edu.in', '9876543230', 'hod123'),
            ('Prof. Kavita Mehta', 'Information Technology', 'kavita.mehta@sanjivani.edu.in', '9876543231', 'hod123'),
            ('Dr. Ramesh Kulkarni', 'Electronics Engineering', 'ramesh.kulkarni@sanjivani.edu.in', '9876543232', 'hod123'),
            ('Prof. Sanjay Pawar', 'Mechanical Engineering', 'sanjay.pawar@sanjivani.edu.in', '9876543233', 'hod123'),
            ('Dr. Neha Agarwal', 'Computer Science', 'neha.agarwal.cs@sanjivani.edu.in', '9876543234', 'hod123'),
            ('Prof. Rajesh Jain', 'Civil Engineering', 'rajesh.jain@sanjivani.edu.in', '9876543235', 'hod123'),
            ('Dr. Pradeep Singh', 'Electrical Engineering', 'pradeep.singh@sanjivani.edu.in', '9876543236', 'hod123'),
            ('Prof. Madhuri Kulkarni', 'Biotechnology', 'madhuri.kulkarni@sanjivani.edu.in', '9876543237', 'hod123')"
    ];
    
    $sample_data = array_merge($sample_students, $sample_coordinators, $sample_hods);
    
    foreach ($sample_data as $data_sql) {
        try {
            $pdo->exec($data_sql);
            echo "Sample data inserted successfully.\n";
        } catch (PDOException $e) {
            echo "Error inserting sample data: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert some sample activities for demonstration
    $sample_activities = [
        "INSERT INTO activities (prn, category, activity_type, level, date, status, points, remarks) VALUES
            ('2025001', 'A', 'Paper Presentation', 'College', '2024-10-15', 'Approved', 3, 'Presented paper on AI in Healthcare'),
            ('2025001', 'B', 'Sports Participation', 'District', '2024-09-20', 'Approved', 4, 'Participated in District Cricket Tournament'),
            ('2025002', 'A', 'Hackathons / Ideathons', 'State', '2024-11-05', 'Approved', 12, 'Won 2nd prize in State Level Hackathon'),
            ('2025003', 'C', 'Community Service (One Week)', NULL, '2024-08-10', 'Approved', 6, 'Taught underprivileged children for one week'),
            ('2025004', 'D', 'MOOC with Final Assessment', NULL, '2024-07-25', 'Approved', 5, 'Completed Python Programming Course'),
            ('2025005', 'E', 'Club/Association Leadership', 'College', '2024-06-01', 'Approved', 4, 'Secretary of Technical Club')"
    ];
    
    foreach ($sample_activities as $activity_sql) {
        try {
            $pdo->exec($activity_sql);
            echo "Sample activity inserted successfully.\n";
        } catch (PDOException $e) {
            echo "Error inserting sample activity: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== DATABASE SETUP COMPLETED SUCCESSFULLY ===\n";
    echo "Database: map_management\n";
    echo "Total Tables Created: " . count($tables) . "\n";
    echo "\n=== LOGIN CREDENTIALS ===\n";
    echo "Admin Login: ID=1, Password=admin123\n";
    echo "Sample Student Login: PRN=2025001, Password=student123\n";
    echo "Sample Coordinator Login: ID=1, Password=coord123\n";
    echo "Sample HoD Login: ID=1, Password=hod123\n";
    echo "\n=== SAMPLE DATA ===\n";
    echo "- 10 Sample Students across different departments\n";
    echo "- 8 Sample Coordinators\n";
    echo "- 8 Sample HoDs\n";
    echo "- Complete Activity Master Data with Level-based Points\n";
    echo "- Programme Rules for 2023-24, 2024-25, and 2025-26 batches\n";
    echo "- 6 Sample Activity Submissions\n";
    echo "\nYou can now access the system at: http://localhost/your-project-folder/\n";
    echo "\n=== FEATURES AVAILABLE ===\n";
    echo "✓ Enhanced database structure with proper indexing\n";
    echo "✓ System logs and notifications support\n";
    echo "✓ File upload tracking\n";
    echo "✓ Comprehensive activity master data\n";
    echo "✓ Multi-year programme rules support\n";
    echo "✓ Sample data for testing\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>