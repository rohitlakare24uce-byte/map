# MySQL Database Setup Instructions for MAP Management System

## Prerequisites
- XAMPP installed and running
- Apache and MySQL services started in XAMPP Control Panel

## Setup Steps

### 1. Start XAMPP Services
- Open XAMPP Control Panel
- Start Apache and MySQL services

### 2. Run Database Initialization
- Open your web browser
- Navigate to your project folder: `http://localhost/your-project-folder/config/init_mysql_db.php`
- Or run the file directly via command line: `php config/init_mysql_db.php`

### 3. Verify Database Creation
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Check that `map_management` database is created with all tables

## Default Login Credentials

### Admin Panel
- **URL**: `http://localhost/your-project-folder/`
- **User Type**: Administrator
- **Username**: 1
- **Password**: admin123

### Sample Student Login
- **User Type**: Student
- **PRN**: 2025001
- **Password**: student123

### Sample Coordinator Login
- **User Type**: Class Coordinator
- **Username**: 1
- **Password**: coord123

### Sample HoD Login
- **User Type**: Head of Department
- **Username**: 1
- **Password**: hod123

## Database Structure

### Main Tables Created:
1. **students** - Student information and credentials
2. **coordinators** - Class coordinator accounts
3. **hods** - Head of Department accounts
4. **admins** - Administrator accounts
5. **categories** - Activity categories (A-E)
6. **activities_master** - Master list of all activities
7. **activity_levels** - Points for level-based activities
8. **programme_rules** - MAP requirements by programme and year
9. **activities** - Student activity submissions

### Sample Data Included:
- 5 sample students across different departments
- 5 coordinators for different departments
- 5 HoDs for different departments
- Complete activity master data with level-based points
- Programme rules for 2024-25 and 2025-26 batches

## Features Available:

### Student Panel:
- Dashboard with progress tracking
- Submit new activities
- View submission status
- Download transcript

### Coordinator Panel:
- Verify student submissions
- Monitor class compliance
- Generate reports

### HoD Panel:
- Department-wide monitoring
- Student performance analysis
- Generate department reports

### Admin Panel:
- User management
- Programme rules management
- Activity management
- University-wide reports

## File Upload Directory
Make sure the `uploads/` directory exists and has write permissions:
```bash
mkdir uploads
chmod 755 uploads
```

## Troubleshooting

### Database Connection Issues:
- Verify MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure `map_management` database exists

### Login Issues:
- Use exact credentials provided above
- Passwords are stored as plain text (no hashing)
- Check user type selection matches the account type

### File Upload Issues:
- Ensure `uploads/` directory exists
- Check PHP file upload settings in `php.ini`
- Verify directory permissions

## Security Note
This setup uses plain text passwords for development purposes only. In production, implement proper password hashing and security measures.