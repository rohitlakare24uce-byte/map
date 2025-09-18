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
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS map_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'map_management' created successfully.\n";
    
    // Use the database
    $pdo->exec("USE map_management");
    
    // Create tables
    $tables = [
        // Students table
        "CREATE TABLE IF NOT EXISTS students (
            prn VARCHAR(20) PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            last_name VARCHAR(50) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            year INT NOT NULL,
            programme VARCHAR(50) NOT NULL,
            course_duration INT NOT NULL,
            admission_year VARCHAR(9) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Coordinators table
        "CREATE TABLE IF NOT EXISTS coordinators (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // HoDs table
        "CREATE TABLE IF NOT EXISTS hods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            dept VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Admins table
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id CHAR(1) PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB",
        
        // Activities master table
        "CREATE TABLE IF NOT EXISTS activities_master (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id CHAR(1) NOT NULL,
            activity_name VARCHAR(150) NOT NULL,
            document_evidence VARCHAR(150) NOT NULL,
            points_type ENUM('Fixed','Level') NOT NULL,
            min_points INT DEFAULT NULL,
            max_points INT DEFAULT NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        ) ENGINE=InnoDB",
        
        // Activity levels table
        "CREATE TABLE IF NOT EXISTS activity_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_id INT NOT NULL,
            level VARCHAR(50) NOT NULL,
            points INT NOT NULL,
            FOREIGN KEY (activity_id) REFERENCES activities_master(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Programme rules table
        "CREATE TABLE IF NOT EXISTS programme_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admission_year VARCHAR(9) NOT NULL,
            programme VARCHAR(50) NOT NULL,
            duration INT NOT NULL,
            technical INT NOT NULL,
            sports_cultural INT NOT NULL,
            community_outreach INT NOT NULL,
            innovation INT NOT NULL,
            leadership INT NOT NULL,
            total_points INT NOT NULL
        ) ENGINE=InnoDB",
        
        // Activities submitted by students
        "CREATE TABLE IF NOT EXISTS activities (
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (prn) REFERENCES students(prn)
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
        // Categories
        "INSERT IGNORE INTO categories (id, name) VALUES 
            ('A', 'Technical Skills'),
            ('B', 'Sports & Cultural'),
            ('C', 'Community Outreach & Social Initiatives'),
            ('D', 'Innovation / IPR / Entrepreneurship'),
            ('E', 'Leadership / Management')",
        
        // Sample admin user (password: admin123 - no hash as requested)
        "INSERT IGNORE INTO admins (id, name, password) VALUES 
            (1, 'System Administrator', 'admin123')",
        
        // Programme rules for 2025-2026
        "INSERT IGNORE INTO programme_rules 
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
        "INSERT IGNORE INTO programme_rules 
            (admission_year, programme, duration, technical, sports_cultural, community_outreach, innovation, leadership, total_points)
            VALUES
            ('2024-2025', 'B.Tech', 4, 30, 5, 10, 20, 10, 75),
            ('2024-2025', 'B.Tech (DSY)', 3, 20, 5, 5, 15, 5, 50),
            ('2024-2025', 'B.Com', 3, 15, 5, 5, 10, 10, 45),
            ('2024-2025', 'BBA', 3, 20, 5, 5, 5, 10, 45),
            ('2024-2025', 'MBA', 2, 10, 5, 5, 5, 5, 30)"
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
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type) VALUES
            ('A', 'Paper Presentation', 'Certificate', 'Level'),
            ('A', 'Project Competition', 'Certificate', 'Level'),
            ('A', 'Hackathons / Ideathons', 'Certificate', 'Level'),
            ('A', 'Poster Competitions', 'Certificate', 'Level'),
            ('A', 'Competitive Programming', 'Certificate', 'Level'),
            ('A', 'Workshop', 'Certificate', 'Level'),
            ('A', 'Industrial Training / Case Studies', 'Certificate', 'Level')",
        
        // Category A - Fixed points
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type, min_points, max_points) VALUES
            ('A', 'MOOC with Final Assessment', 'Certificate', 'Fixed', 5, 5),
            ('A', 'Internship / Professional Certification', 'Certificate', 'Fixed', 5, 5),
            ('A', 'Industrial / Exhibition Visit', 'Report', 'Fixed', 5, 5),
            ('A', 'Language Proficiency', 'Certificate', 'Fixed', 5, 10)",
        
        // Category B - Sports & Cultural
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type) VALUES
            ('B', 'Sports Participation', 'Certificate', 'Level'),
            ('B', 'Cultural Participation', 'Certificate', 'Level')",
        
        // Category C - Community Outreach
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type, min_points, max_points) VALUES
            ('C', 'Community Service (Two Day)', 'Certificate/Letter', 'Fixed', 3, 3),
            ('C', 'Community Service (Up to One Week)', 'Certificate/Letter', 'Fixed', 6, 6),
            ('C', 'Community Service (One Month)', 'Certificate/Letter', 'Fixed', 9, 9),
            ('C', 'Community Service (One Semester/Year)', 'Certificate/Letter', 'Fixed', 12, 12)",
        
        // Category D - Innovation
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type, min_points, max_points) VALUES
            ('D', 'Entrepreneurship / IPR Workshop', 'Certificate', 'Fixed', 5, 5),
            ('D', 'MSME Programme', 'Certificate', 'Fixed', 5, 5),
            ('D', 'Awards/Recognitions for Products', 'Certificate', 'Fixed', 10, 10),
            ('D', 'Completed Prototype Development', 'Report', 'Fixed', 15, 15),
            ('D', 'Filed a Patent', 'Certificate', 'Fixed', 5, 5),
            ('D', 'Published Patent', 'Certificate', 'Fixed', 10, 10),
            ('D', 'Patent Granted', 'Certificate', 'Fixed', 15, 15),
            ('D', 'Registered Start-up Company', 'Legal Proof', 'Fixed', 10, 10),
            ('D', 'Revenue/Profits Generated', 'Proof', 'Fixed', 15, 15),
            ('D', 'Attracted Investor Funding', 'Proof', 'Fixed', 15, 15),
            ('D', 'International Conference / Journal', 'Certificate', 'Fixed', 10, 10),
            ('D', 'Innovation Implemented by Industry', 'Proof', 'Fixed', 15, 15),
            ('D', 'Social Innovation / Grassroot Value Addition', 'Proof', 'Fixed', 10, 10),
            ('D', 'Business Hackathon', 'Certificate', 'Fixed', 10, 10),
            ('D', 'Social Enterprise Pilot', 'Certificate', 'Fixed', 10, 10)",
        
        // Category E - Leadership
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type) VALUES
            ('E', 'Club/Association Participation', 'Certificate', 'Level'),
            ('E', 'Club/Association Coordinator', 'Certificate', 'Level')",
        
        "INSERT IGNORE INTO activities_master (category_id, activity_name, document_evidence, points_type, min_points, max_points) VALUES
            ('E', 'Professional Society Membership', 'Certificate', 'Fixed', 5, 5),
            ('E', 'Special Initiatives for University', 'Proof', 'Fixed', 5, 5)"
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
    $level_activities = ['Paper Presentation', 'Project Competition', 'Hackathons / Ideathons', 
                        'Poster Competitions', 'Competitive Programming', 'Workshop', 
                        'Industrial Training / Case Studies', 'Sports Participation', 
                        'Cultural Participation', 'Club/Association Participation', 
                        'Club/Association Coordinator'];
    
    $levels = [
        'College' => [3, 2, 2, 2, 3, 2, 3, 2, 2, 2, 3],
        'District' => [6, 4, 4, 4, 6, 4, 6, 4, 4, 4, 6],
        'State' => [9, 6, 6, 6, 9, 6, 9, 6, 6, 6, 9],
        'National' => [12, 8, 8, 8, 12, 8, 12, 8, 8, 8, 12],
        'International' => [15, 10, 10, 10, 15, 10, 15, 10, 10, 10, 15],
        'University' => [6, 4, 4, 4, 6, 4, 6, 4, 4, 4, 6],
        'Dept' => [3, 2, 2, 2, 3, 2, 3, 2, 2, 2, 3]
    ];
    
    foreach ($level_activities as $index => $activity_name) {
        // Get activity ID
        $stmt = $pdo->prepare("SELECT id FROM activities_master WHERE activity_name = ?");
        $stmt->execute([$activity_name]);
        $activity = $stmt->fetch();
        
        if ($activity) {
            foreach ($levels as $level => $points_array) {
                $points = $points_array[$index];
                try {
                    $pdo->exec("INSERT IGNORE INTO activity_levels (activity_id, level, points) VALUES ({$activity['id']}, '$level', $points)");
                } catch (PDOException $e) {
                    echo "Error inserting level data: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // Insert sample data
    echo "\nInserting sample data...\n";
    
    // Sample students (passwords without hash as requested)
    $sample_students = [
        "INSERT IGNORE INTO students (prn, first_name, middle_name, last_name, dept, year, programme, course_duration, admission_year, password) VALUES
            ('2025001', 'Rahul', 'Kumar', 'Sharma', 'Computer Engineering', 1, 'B.Tech', 4, '2025-2026', 'student123'),
            ('2025002', 'Priya', 'Suresh', 'Patel', 'Information Technology', 1, 'B.Tech', 4, '2025-2026', 'student123'),
            ('2025003', 'Amit', 'Rajesh', 'Singh', 'Electronics Engineering', 2, 'B.Tech', 4, '2024-2025', 'student123'),
            ('2025004', 'Sneha', 'Mohan', 'Gupta', 'Computer Science', 1, 'BCA', 3, '2025-2026', 'student123'),
            ('2025005', 'Vikram', 'Anil', 'Joshi', 'Mechanical Engineering', 3, 'B.Tech', 4, '2023-2024', 'student123')"
    ];
    
    // Sample coordinators
    $sample_coordinators = [
        "INSERT IGNORE INTO coordinators (name, dept, password) VALUES
            ('Dr. Rajesh Kumar', 'Computer Engineering', 'coord123'),
            ('Prof. Sunita Sharma', 'Information Technology', 'coord123'),
            ('Dr. Anil Patil', 'Electronics Engineering', 'coord123'),
            ('Prof. Meera Joshi', 'Mechanical Engineering', 'coord123'),
            ('Dr. Suresh Gupta', 'Computer Science', 'coord123')"
    ];
    
    // Sample HoDs
    $sample_hods = [
        "INSERT IGNORE INTO hods (name, dept, password) VALUES
            ('Dr. Prakash Desai', 'Computer Engineering', 'hod123'),
            ('Prof. Kavita Mehta', 'Information Technology', 'hod123'),
            ('Dr. Ramesh Kulkarni', 'Electronics Engineering', 'hod123'),
            ('Prof. Sanjay Pawar', 'Mechanical Engineering', 'hod123'),
            ('Dr. Neha Agarwal', 'Computer Science', 'hod123')"
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
    
    echo "\n=== DATABASE SETUP COMPLETED SUCCESSFULLY ===\n";
    echo "Database: map_management\n";
    echo "Admin Login: ID=1, Password=admin123\n";
    echo "Sample Student Login: PRN=2025001, Password=student123\n";
    echo "Sample Coordinator Login: ID=1, Password=coord123\n";
    echo "Sample HoD Login: ID=1, Password=hod123\n";
    echo "\nYou can now access the system at: http://localhost/your-project-folder/\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>