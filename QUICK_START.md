# 🏥 Medical Clinic Management Platform - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Install XAMPP (5 minutes)
1. Download XAMPP from https://www.apachefriends.org/
2. Install and launch XAMPP Control Panel
3. Start **Apache** and **MySQL** services

### Step 2: Deploy Files (1 minute)
1. Extract the `medical_clinic` folder
2. Copy to: `C:\xampp\htdocs\medical_clinic`

### Step 3: Setup Database (3 minutes)
1. Open browser → http://localhost/phpmyadmin
2. Click "New" → Name: `medical_clinic` → Create
3. Click "Import" → Choose `database.sql` → Click "Go"

### Step 4: Generate Password Hashes (2 minutes)
1. Open: http://localhost/medical_clinic/generate_hash.php
2. Generate hash for `fares123`
3. Generate hash for `dalanda123`
4. In phpMyAdmin, go to `medical_clinic` database → `users` table
5. Update both password fields with the generated hashes

### Step 5: Verify Installation (1 minute)
1. Open: http://localhost/medical_clinic/install_check.php
2. Verify all checks are ✅ green
3. Click "Go to Login Page"

### Step 6: Login & Test (2 minutes)
**Doctor Login:**
- Username: `fares`
- Password: `fares123`

**Secretary Login:**
- Username: `dalanda`
- Password: `dalanda123`

---

## 🎯 Common Tasks

### As Secretary:

#### Add a New Appointment
1. Click "Ajouter un rendez-vous"
2. Enter CIN or Phone (system auto-fills if exists)
3. Complete form
4. Click "Enregistrer"

#### Search for Patient
1. Go to "Recherche"
2. Enter name, CIN, phone, or date
3. View results

### As Doctor:

#### Open Medical File
1. Go to "Calendrier"
2. Click any patient appointment
3. Medical file opens

#### Create Medical Record
1. Fill in all examination sections
2. Enter vitals, diagnosis, treatment
3. Click "Enregistrer le dossier"

#### View Patient History
1. Go to "Historique patients"
2. Search for patient
3. View complete history

### As Patient:

#### Request Appointment
1. Login with phone number
2. Select desired date
3. Click "Envoyer la demande"
4. Wait for secretary confirmation

#### View Records
1. Go to "Mes documents"
2. View prescriptions
3. Download documents

---

## 🔧 Troubleshooting

### "Database connection failed"
**Solution:** 
- Make sure MySQL is running in XAMPP
- Import database.sql in phpMyAdmin
- Check config.php settings

### "Login not working"
**Solution:**
- Use generate_hash.php to create password hashes
- Update passwords in database
- Clear browser cache

### "Page not found"
**Solution:**
- Verify files are in `C:\xampp\htdocs\medical_clinic`
- Check Apache is running
- Access via http://localhost/medical_clinic

### "Permission denied" on uploads
**Solution:**
- Right-click uploads folder → Properties
- Uncheck "Read-only"
- Apply to all subfolders

---

## 📱 Mobile Access

On same network:
1. Find computer IP: `ipconfig` (Windows) or `ifconfig` (Mac/Linux)
2. Access from mobile: `http://[YOUR_IP]/medical_clinic`
3. Example: `http://192.168.1.100/medical_clinic`

---

## 🔐 Security Checklist

- ✅ Change default passwords immediately
- ✅ Use strong passwords (12+ characters)
- ✅ Keep XAMPP updated
- ✅ Backup database regularly
- ✅ Don't expose to public internet without proper security

---

## 📊 Default Data

### Users (After password hash update)
| Role | Username | Password |
|------|----------|----------|
| Doctor | fares | fares123 |
| Secretary | dalanda | dalanda123 |

### Sample Patient Registration
- First Name: Test
- Last Name: Patient
- Phone: 12345678
- Password: test123

---

## 🎨 Customization

### Change Colors
Edit `css/style.css` → Lines 5-20
```css
:root {
    --primary-blue: #2563eb;  /* Change this */
    --secondary-teal: #0891b2; /* And this */
}
```

### Change Clinic Name
Edit each page → Find "Cabinet Médical" → Replace

---

## 📞 Support Resources

1. **README.md** - Complete documentation
2. **install_check.php** - Verify installation
3. **generate_hash.php** - Password utilities
4. **database.sql** - Database structure

---

## ✨ Features Overview

### ✅ Implemented
- User authentication (3 roles)
- Patient registration
- Appointment management
- 7-day calendar view
- Medical file creation
- Patient history
- Search functionality
- Statistics dashboard
- Responsive design

### 🔄 Partially Implemented
- PDF generation (requires FPDF/TCPDF)
- File upload system
- Accounting module
- Advanced analytics

### 📋 Planned
- Email notifications
- SMS reminders
- Automated backups
- Multi-language support

---

## 🚀 Next Steps

1. Test all features with sample data
2. Customize colors and branding
3. Add real patient data
4. Configure backups
5. Train staff on system usage

---

## 📝 Notes

- Database resets between sessions if not persisted
- Passwords must be hashed before use
- CIN is optional (for children)
- Parent-child relationships supported
- Secretary cannot view medical notes (by design)

---

**Version:** 1.0.0  
**Last Updated:** January 2026  
**Status:** Production Ready (Core Features)

---

Need help? Check the full **README.md** for detailed documentation!
