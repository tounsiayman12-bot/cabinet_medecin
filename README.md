# Medical Clinic Management Platform

A comprehensive web-based medical clinic management system built with PHP, MySQL, HTML, CSS, and JavaScript for XAMPP environment.

## Features

### 1. **Three User Roles**
- **Doctor**: Full access to all features including medical records, statistics, and accounting
- **Secretary**: Manages appointments and patient registration (restricted from medical notes)
- **Patient**: Self-service portal for booking appointments and viewing records

### 2. **Authentication & Registration**
- Secure login system with password hashing
- Patient self-registration
- Default credentials:
  - Doctor: `fares` / `fares123`
  - Secretary: `dalanda` / `dalanda123`

### 3. **Secretary Interface**
- ✅ 7-day horizontal calendar view
- ✅ Add appointments with smart patient lookup
- ✅ Auto-fill existing patient data by CIN or phone
- ✅ Add child patients linked to parents
- ✅ Multi-criteria search functionality
- ✅ Calendar navigation by date
- ❌ Cannot view medical motifs or doctor's notes (restricted)

### 4. **Doctor Interface**

#### Dashboard
- ✅ Statistics for appointments (yearly/monthly)
- ✅ Confirmed vs Canceled appointments
- ✅ Total revenue tracking
- ✅ Interactive charts (Chart.js)

#### Clinical Calendar
- ✅ Weekly calendar view
- ✅ Click patient to open medical file
- ✅ View patient history sidebar
- ✅ Previous visit records with motifs

#### Medical File Management
- ✅ Complete patient information display
- ✅ Medical history sidebar
- ✅ Parent-child relationships
- ✅ New visit form with:
  - Motif de consultation
  - Current medications
  - Functional signs
  - Physical examination:
    - Vitals: HR, BP, Temp, RR, SpO2
    - Heart & Lung Auscultation
    - Comments
  - Diagnostic
  - Treatment plan
  - Complementary exams
- 🔄 PDF generation for:
  - Prescriptions
  - Complementary exams
  - Medical certificates (with rest days)
- 🔄 Import/Upload:
  - Lab analyses
  - Imaging (X-rays)

#### Patient History
- ✅ Global search for all patients
- ✅ Full history view
- ✅ Linked search for children's visits

#### Accounting (Comptabilité)
- 🔄 Track taxes: IRPP, TVA 7%, CNSS, Order fees, RCP Insurance, Rental tax
- 🔄 Track expenses: Salary, CNSS, Utilities, Software, etc.

#### Settings
- 🔄 Profile management

### 5. **Patient Interface**
- ✅ Request appointments online
- ✅ Secretary confirmation required
- ✅ View appointment history
- ✅ Access prescriptions (read-only)
- ✅ View complementary exams

## Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Custom CSS with Bootstrap-inspired design
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **PDF Generation**: FPDF/TCPDF (to be implemented)

## Database Structure

### Key Tables
1. **users** - Doctors and secretaries
2. **patients** - Patient records with parent-child relationships
3. **appointments** - Visit scheduling
4. **medical_records** - Complete medical examination records
5. **prescriptions** - Prescription records
6. **medical_certificates** - Medical certificates with rest days
7. **medical_documents** - Uploaded analyses and imaging
8. **revenue** - Payment tracking
9. **expenses** - Expense management

### Special Features
- `Internal_ID` as Primary Key for patients
- `CIN` is unique but nullable (for children)
- `Parent_ID` for parent-child relationships
- Cascading deletes for data integrity

## Installation Instructions

### Prerequisites
- XAMPP (with Apache and MySQL)
- PHP 7.4 or higher
- Modern web browser

### Step-by-Step Installation

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache and MySQL

2. **Copy Files**
   ```
   Copy the medical_clinic folder to:
   C:\xampp\htdocs\medical_clinic
   ```

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" to create a database
   - Name it: `medical_clinic`
   - Click "Import" tab
   - Select `database.sql` file
   - Click "Go" to import

4. **Configure Database Connection**
   - Open `includes/config.php`
   - Verify settings:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'medical_clinic');
     ```

5. **Update Default Passwords (Important!)**
   - The default passwords in the SQL file need to be hashed properly
   - Open phpMyAdmin
   - Navigate to `medical_clinic` database
   - Click on `users` table
   - Update password hashes using PHP:
   
   ```php
   // Generate hashed passwords
   echo password_hash('fares123', PASSWORD_DEFAULT);
   echo password_hash('dalanda123', PASSWORD_DEFAULT);
   ```
   
   Or use the provided update script in the database.

6. **Access the Application**
   - Open browser and go to: http://localhost/medical_clinic
   - Login with default credentials

## Usage Guide

### For Secretaries

1. **Adding an Appointment**
   - Click "Ajouter un rendez-vous"
   - Enter CIN or Phone number
   - System auto-fills if patient exists
   - Complete remaining fields
   - Click "Enregistrer"

2. **Smart Lookup**
   - When entering CIN or phone, blur the field
   - System automatically searches for existing patient
   - Patient data is auto-filled if found

3. **Adding a Child**
   - When adding appointment for a parent
   - Use "Add Child" button
   - Link child to parent via Parent_ID

### For Doctors

1. **Viewing Calendar**
   - Navigate to "Calendrier"
   - Click on any patient to open medical file

2. **Creating Medical Record**
   - Fill in all sections:
     - Chief complaint
     - Physical examination
     - Vitals
     - Diagnosis
     - Treatment
   - Click "Enregistrer le dossier"

3. **Generating PDFs**
   - After saving record
   - Click "Imprimer documents"
   - Select type (Prescription, Certificate, etc.)

4. **Viewing Patient History**
   - History sidebar shows all previous visits
   - Click on any record to view details
   - Children's visits linked to parent

### For Patients

1. **Requesting Appointment**
   - Login to patient portal
   - Select desired date
   - Add optional reason
   - Wait for secretary confirmation

2. **Viewing Records**
   - Navigate to "Mes documents"
   - View prescriptions
   - Download complementary exam results

## Security Features

- Password hashing with bcrypt
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- Session-based authentication
- Role-based access control
- CSRF protection (to be added)

## File Structure

```
medical_clinic/
├── api/
│   └── lookup_patient.php
├── css/
│   └── style.css
├── includes/
│   └── config.php
├── uploads/
│   ├── analyses/
│   └── imaging/
├── pdfs/
├── database.sql
├── index.php
├── secretary_dashboard.php
├── doctor_dashboard.php
├── doctor_calendar.php
├── doctor_medical_file.php
├── patient_dashboard.php
├── logout.php
└── README.md
```

## Customization

### Changing Colors
Edit `css/style.css` and modify CSS variables:
```css
:root {
    --primary-blue: #2563eb;
    --secondary-teal: #0891b2;
    --accent-green: #10b981;
    /* ... */
}
```

### Adding New Fields
1. Modify database schema
2. Update relevant PHP forms
3. Update SQL queries

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify MySQL is running in XAMPP
   - Check database credentials in config.php
   - Ensure database exists

2. **Login Not Working**
   - Verify passwords are hashed correctly
   - Check users table in database
   - Clear browser cache and cookies

3. **PDF Generation Not Working**
   - Install FPDF or TCPDF library
   - Check file permissions on pdfs folder
   - Verify PHP extensions enabled

4. **File Upload Issues**
   - Check uploads folder permissions
   - Verify PHP upload settings in php.ini
   - Check max file size limits

## Development Roadmap

### Phase 1 (Current) ✅
- Basic authentication
- Calendar management
- Medical file creation
- Patient portal

### Phase 2 🔄
- PDF generation (FPDF/TCPDF)
- File upload system
- Accounting module completion
- Advanced search

### Phase 3 📋
- Email notifications
- SMS reminders
- Backup system
- Report generation

### Phase 4 📋
- Multi-doctor support
- Appointment conflicts
- Waiting list management
- Analytics dashboard

## Contributing

This is a portfolio/learning project. Contributions are welcome!

## License

Educational/Portfolio Project - Free to use and modify

## Support

For issues or questions:
- Check the troubleshooting section
- Review database structure
- Verify XAMPP configuration

## Credits

- Chart.js for data visualization
- Font Awesome for icons
- Google Fonts for typography

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Status**: Development (Core features implemented)

## Legend
✅ Implemented  
🔄 Partially implemented  
📋 Planned  
❌ Restricted by design
