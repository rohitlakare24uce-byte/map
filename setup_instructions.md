# MySQL Database Setup Instructions for MAP Management System

## Prerequisites
- XAMPP installed and running
- Apache and MySQL services started in XAMPP Control Panel
- PHP 7.4 or higher
- At least 100MB free disk space

## Setup Steps

### 1. Start XAMPP Services
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services
- Ensure both services show "Running" status

### 2. Run Database Initialization
Choose one of the following methods:

#### Method A: Web Browser
- Open your web browser
- Navigate to: `http://localhost/your-project-folder/config/init_mysql_db.php`
- Wait for the script to complete (may take 30-60 seconds)

#### Method B: Command Line
```bash
cd /path/to/your/project
php config/init_mysql_db.php
```

### 3. Verify Database Creation
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Check that `map_management` database exists
- Verify all tables are created (should see 11 tables)

## Database Structure Overview

### Core Tables:
1. **students** - Student information and credentials
2. **coordinators** - Class coordinator accounts  
3. **hods** - Head of Department accounts
4. **admins** - Administrator accounts
5. **categories** - Activity categories (A-E) with descriptions
6. **activities_master** - Master list of all activities with detailed descriptions
7. **activity_levels** - Points for level-based activities
8. **programme_rules** - MAP requirements by programme and year
9. **activities** - Student activity submissions
10. **system_logs** - System activity logging
11. **notifications** - User notifications
12. **file_uploads** - File upload tracking

### Key Enhancements:
- ✅ Proper foreign key relationships
- ✅ Database indexing for performance
- ✅ Email and phone fields for users
- ✅ Enhanced activity descriptions
- ✅ System logging capabilities
- ✅ Notification system support
- ✅ File upload tracking
- ✅ Multi-year programme rules (2023-24, 2024-25, 2025-26)

## Default Login Credentials

### Admin Panel
- **URL**: `http://localhost/your-project-folder/`
- **User Type**: Administrator
- **Username**: 1
- **Password**: admin123
- **Email**: admin@sanjivani.edu.in

### Sample Student Logins
| PRN | Name | Department | Year | Password |
|-----|------|------------|------|----------|
| 2025001 | Rahul Kumar Sharma | Computer Engineering | 1 | student123 |
| 2025002 | Priya Suresh Patel | Information Technology | 1 | student123 |
| 2025003 | Amit Rajesh Singh | Electronics Engineering | 2 | student123 |
| 2025004 | Sneha Mohan Gupta | Computer Science | 1 | student123 |
| 2025005 | Vikram Anil Joshi | Mechanical Engineering | 3 | student123 |

### Sample Coordinator Logins
| ID | Name | Department | Password |
|----|------|------------|----------|
| 1 | Dr. Rajesh Kumar | Computer Engineering | coord123 |
| 2 | Prof. Sunita Sharma | Information Technology | coord123 |
| 3 | Dr. Anil Patil | Electronics Engineering | coord123 |

### Sample HoD Logins
| ID | Name | Department | Password |
|----|------|------------|----------|
| 1 | Dr. Prakash Desai | Computer Engineering | hod123 |
| 2 | Prof. Kavita Mehta | Information Technology | hod123 |
| 3 | Dr. Ramesh Kulkarni | Electronics Engineering | hod123 |

## Activity Categories & Points Structure

### Category A - Technical Skills
- **Level-based Activities**: Paper Presentation, Project Competition, Hackathons, etc.
- **Fixed-point Activities**: MOOC Certification, Internships, Industrial Visits
- **Points Range**: 2-25 points depending on level and activity

### Category B - Sports & Cultural  
- **Activities**: Sports/Cultural Participation and Organization
- **Points Range**: 2-12 points based on level (College to International)

### Category C - Community Outreach
- **Activities**: Community Service (2 days to 1 year), Blood Donation, Environmental Initiatives
- **Points Range**: 2-15 points based on duration and impact

### Category D - Innovation/IPR/Entrepreneurship
- **Activities**: Patents, Startups, Business Plans, Product Development
- **Points Range**: 5-35 points based on achievement level

### Category E - Leadership/Management
- **Activities**: Club Leadership, Event Organization, Student Government
- **Points Range**: 2-15 points based on level and responsibility

## Programme Rules Summary

### 2025-2026 Batch (Current)
| Programme | Duration | A | B | C | D | E | Total |
|-----------|----------|---|---|---|---|---|-------|
| B.Tech | 4 years | 45 | 10 | 10 | 25 | 10 | 100 |
| B.Tech (DSY) | 3 years | 30 | 10 | 10 | 15 | 10 | 75 |
| BCA | 3 years | 20 | 10 | 10 | 10 | 10 | 60 |
| MBA | 2 years | 20 | 10 | 10 | 10 | 10 | 60 |

### 2024-2025 Batch
| Programme | Duration | A | B | C | D | E | Total |
|-----------|----------|---|---|---|---|---|-------|
| B.Tech | 4 years | 30 | 5 | 10 | 20 | 10 | 75 |
| B.Tech (DSY) | 3 years | 20 | 5 | 5 | 15 | 5 | 50 |

## File Upload Configuration

### Upload Directory Setup
```bash
# Create uploads directory with proper permissions
mkdir uploads
chmod 755 uploads
```

### Supported File Types
- **Certificates**: PDF, JPG, PNG (Max 5MB)
- **Proof Files**: JPG, PNG (Max 5MB)

## System Features

### Student Panel:
- ✅ Dashboard with progress tracking
- ✅ Submit activities with file uploads
- ✅ Track submission status
- ✅ Download MAP transcript
- ✅ View notifications

### Coordinator Panel:
- ✅ Verify student submissions
- ✅ Approve/reject with remarks
- ✅ Monitor class compliance
- ✅ Generate class reports
- ✅ Export data to Excel/CSV

### HoD Panel:
- ✅ Department-wide monitoring
- ✅ Student performance analysis
- ✅ Generate department reports
- ✅ View compliance statistics

### Admin Panel:
- ✅ User management (Students, Coordinators, HoDs)
- ✅ Programme rules management
- ✅ Activity master management
- ✅ University-wide reports
- ✅ System configuration

## Troubleshooting

### Database Connection Issues:
1. Verify MySQL is running in XAMPP
2. Check database credentials in `config/database.php`
3. Ensure `map_management` database exists
4. Check PHP error logs

### Login Issues:
1. Use exact credentials provided above
2. Passwords are stored as plain text for development
3. Check user type selection matches account type
4. Clear browser cache if needed

### File Upload Issues:
1. Ensure `uploads/` directory exists and has write permissions
2. Check PHP file upload settings in `php.ini`:
   ```ini
   upload_max_filesize = 5M
   post_max_size = 5M
   max_file_uploads = 20
   ```
3. Verify directory permissions: `chmod 755 uploads`

### Performance Issues:
1. Database includes proper indexing for performance
2. For large datasets, consider increasing PHP memory limit
3. Monitor MySQL slow query log if needed

## Security Considerations

### Development vs Production:
- **Development**: Plain text passwords for easy testing
- **Production**: Implement proper password hashing
- **File Security**: Validate file types and scan for malware
- **Database Security**: Use prepared statements (already implemented)

### Recommended Production Changes:
1. Enable password hashing in authentication
2. Implement HTTPS
3. Add CSRF protection
4. Set up proper backup procedures
5. Configure firewall rules
6. Enable audit logging

## Support & Maintenance

### Regular Maintenance:
- Backup database weekly
- Monitor disk space usage
- Check error logs regularly
- Update PHP and MySQL as needed

### Getting Help:
- Check error logs in XAMPP control panel
- Review PHP error logs
- Verify database connectivity
- Test with sample data provided

## Sample Data Included

The system comes pre-loaded with:
- **10 Students** across 8 different departments
- **8 Coordinators** for different departments  
- **8 HoDs** for department oversight
- **Complete Activity Master** with 40+ activities
- **Programme Rules** for 3 academic years
- **6 Sample Activity Submissions** for testing

This comprehensive setup allows immediate testing of all system features without additional data entry.