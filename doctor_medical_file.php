<?php
require_once 'includes/config.php';
requireRole('doctor');

$patient_id = isset($_GET['patient']) ? intval($_GET['patient']) : 0;
$appointment_id = isset($_GET['appointment']) ? intval($_GET['appointment']) : 0;

$conn = getDBConnection();

// Get patient info
$stmt = $conn->prepare("SELECT * FROM patients WHERE Internal_ID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    header('Location: doctor_calendar.php');
    exit();
}

// Get patient's medical history
$stmt = $conn->prepare("
    SELECT mr.*, a.appointment_date, a.appointment_time 
    FROM medical_records mr
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    WHERE mr.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get children if parent
$stmt = $conn->prepare("SELECT * FROM patients WHERE Parent_ID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle medical record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_record'])) {
    $motif = sanitize($_POST['motif']);
    $current_medication = sanitize($_POST['current_medication']);
    $functional_signs = sanitize($_POST['functional_signs']);
    $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
    $bp_systolic = !empty($_POST['bp_systolic']) ? intval($_POST['bp_systolic']) : null;
    $bp_diastolic = !empty($_POST['bp_diastolic']) ? intval($_POST['bp_diastolic']) : null;
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
    $spo2 = !empty($_POST['spo2']) ? intval($_POST['spo2']) : null;
    $heart_auscultation = sanitize($_POST['heart_auscultation']);
    $lung_auscultation = sanitize($_POST['lung_auscultation']);
    $physical_exam_comments = sanitize($_POST['physical_exam_comments']);
    $diagnostic = sanitize($_POST['diagnostic']);
    $treatment = sanitize($_POST['treatment']);
    $complementary_exams = sanitize($_POST['complementary_exams']);
    
    $stmt = $conn->prepare("
        INSERT INTO medical_records (
            appointment_id, patient_id, doctor_id, motif, current_medication, 
            functional_signs, heart_rate, blood_pressure_systolic, blood_pressure_diastolic,
            temperature, respiratory_rate, spo2, heart_auscultation, lung_auscultation,
            physical_exam_comments, diagnostic, treatment, complementary_exams
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiisssiiddiisssss",
        $appointment_id, $patient_id, $_SESSION['user_id'],
        $motif, $current_medication, $functional_signs,
        $heart_rate, $bp_systolic, $bp_diastolic, $temperature,
        $respiratory_rate, $spo2, $heart_auscultation, $lung_auscultation,
        $physical_exam_comments, $diagnostic, $treatment, $complementary_exams
    );
    $stmt->execute();
    $record_id = $stmt->insert_id;
    $stmt->close();
    
    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: doctor_medical_file.php?patient=$patient_id&appointment=$appointment_id&success=1");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier Médical - <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .medical-file-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
        }
        
        .patient-sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        
        .patient-info-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal));
            color: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1rem;
        }
        
        .history-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .history-item {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        
        .history-item:hover {
            background: var(--gray-100);
            transform: translateX(5px);
        }
        
        .form-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        @media (max-width: 968px) {
            .medical-file-layout {
                grid-template-columns: 1fr;
            }
            .vitals-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h3>Cabinet Médical</h3>
                <p><?php echo $_SESSION['name']; ?></p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="doctor_dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line icon"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calendar.php" class="nav-link active">
                        <i class="fas fa-calendar-alt icon"></i>
                        <span>Calendrier</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_patients.php" class="nav-link">
                        <i class="fas fa-users icon"></i>
                        <span>Historique patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_accounting.php" class="nav-link">
                        <i class="fas fa-calculator icon"></i>
                        <span>Comptabilité</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt icon"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div>
                    <a href="doctor_calendar.php" style="color: var(--primary-blue); text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Retour au calendrier
                    </a>
                    <h1 style="margin-top: 0.5rem;">Dossier Médical</h1>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Dossier médical enregistré avec succès
                </div>
            <?php endif; ?>
            
            <div class="medical-file-layout">
                <!-- Patient Sidebar -->
                <div class="patient-sidebar">
                    <div class="patient-info-card">
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 style="color: white; margin-bottom: 0.25rem;">
                                <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>
                            </h3>
                            <?php if ($patient['CIN']): ?>
                                <p style="font-size: 0.875rem; opacity: 0.9;">CIN: <?php echo $patient['CIN']; ?></p>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.875rem; opacity: 0.95;">
                            <p><i class="fas fa-phone"></i> <?php echo $patient['phone']; ?></p>
                            <?php if ($patient['date_of_birth']): ?>
                                <p><i class="fas fa-birthday-cake"></i> <?php echo formatDate($patient['date_of_birth']); ?></p>
                                <p><i class="fas fa-calendar"></i> <?php echo floor((time() - strtotime($patient['date_of_birth'])) / (365*24*60*60)); ?> ans</p>
                            <?php endif; ?>
                            <?php if ($patient['cnam_number']): ?>
                                <p><i class="fas fa-id-card"></i> CNAM: <?php echo $patient['cnam_number']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($children)): ?>
                    <div class="card">
                        <h4><i class="fas fa-child"></i> Enfants</h4>
                        <?php foreach ($children as $child): ?>
                            <div style="padding: 0.5rem; background: var(--gray-50); border-radius: 8px; margin-top: 0.5rem;">
                                <a href="doctor_medical_file.php?patient=<?php echo $child['Internal_ID']; ?>&appointment=0" style="text-decoration: none; color: var(--gray-800);">
                                    <?php echo $child['first_name'] . ' ' . $child['last_name']; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <h4><i class="fas fa-history"></i> Historique</h4>
                        <div class="history-list">
                            <?php if (empty($history)): ?>
                                <p style="color: var(--gray-500); font-size: 0.875rem;">Aucun historique</p>
                            <?php else: ?>
                                <?php foreach ($history as $record): ?>
                                    <div class="history-item">
                                        <strong><?php echo formatDate($record['appointment_date']); ?></strong>
                                        <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0.25rem 0 0 0;">
                                            <?php echo substr($record['motif'], 0, 60); ?>...
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Record Form -->
                <div>
                    <form method="POST" action="">
                        <!-- Chief Complaint Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-stethoscope"></i> Motif de consultation</h3>
                            <div class="form-group">
                                <label class="form-label">Motif *</label>
                                <textarea name="motif" class="form-control" rows="3" required placeholder="Décrivez le motif de la consultation"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Médicaments actuels</label>
                                <textarea name="current_medication" class="form-control" rows="2" placeholder="Listez les médicaments que le patient prend actuellement"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Signes fonctionnels</label>
                                <textarea name="functional_signs" class="form-control" rows="3" placeholder="Décrivez les symptômes et signes fonctionnels"></textarea>
                            </div>
                        </div>
                        
                        <!-- Physical Examination Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-thermometer"></i> Examen physique</h3>
                            
                            <h4 style="margin-bottom: 1rem;">Signes vitaux</h4>
                            <div class="vitals-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-heartbeat"></i> FC (bpm)
                                    </label>
                                    <input type="number" name="heart_rate" class="form-control" placeholder="Ex: 72">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-tachometer-alt"></i> TA Systolique
                                    </label>
                                    <input type="number" name="bp_systolic" class="form-control" placeholder="Ex: 120">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-tachometer-alt"></i> TA Diastolique
                                    </label>
                                    <input type="number" name="bp_diastolic" class="form-control" placeholder="Ex: 80">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-thermometer-half"></i> Temp (°C)
                                    </label>
                                    <input type="number" step="0.1" name="temperature" class="form-control" placeholder="Ex: 37.2">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lungs"></i> FR (rpm)
                                    </label>
                                    <input type="number" name="respiratory_rate" class="form-control" placeholder="Ex: 16">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-wind"></i> SpO2 (%)
                                    </label>
                                    <input type="number" name="spo2" class="form-control" placeholder="Ex: 98">
                                </div>
                            </div>
                            
                            <h4 style="margin: 1.5rem 0 1rem 0;">Auscultation</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Cardiaque</label>
                                    <textarea name="heart_auscultation" class="form-control" rows="2" placeholder="Bruits du cœur"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Pulmonaire</label>
                                    <textarea name="lung_auscultation" class="form-control" rows="2" placeholder="Bruits respiratoires"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Commentaires sur l'examen physique</label>
                                <textarea name="physical_exam_comments" class="form-control" rows="3" placeholder="Autres observations lors de l'examen physique"></textarea>
                            </div>
                        </div>
                        
                        <!-- Diagnosis and Treatment Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-notes-medical"></i> Diagnostic et traitement</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Diagnostic *</label>
                                <textarea name="diagnostic" class="form-control" rows="3" required placeholder="Diagnostic médical"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Traitement *</label>
                                <textarea name="treatment" class="form-control" rows="4" required placeholder="Prescription médicale détaillée"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Examens complémentaires</label>
                                <textarea name="complementary_exams" class="form-control" rows="3" placeholder="Analyses, imageries, ou autres examens prescrits"></textarea>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="save_record" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer le dossier
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="openPrintOptions()">
                                <i class="fas fa-print"></i> Imprimer documents
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="openUploadModal()">
                                <i class="fas fa-upload"></i> Importer analyses/imagerie
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function openPrintOptions() {
            alert('Fonctionnalité d\'impression à implémenter avec FPDF/TCPDF');
        }
        
        function openUploadModal() {
            alert('Fonctionnalité d\'upload à implémenter');
        }
    </script>
</body>
</html>
