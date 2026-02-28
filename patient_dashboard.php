<?php
require_once 'includes/config.php';
requireRole('patient');

$conn = getDBConnection();

// Get patient info
$stmt = $conn->prepare("SELECT * FROM patients WHERE Internal_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get patient appointments
$stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE patient_id = ? 
    ORDER BY appointment_date DESC, appointment_time DESC 
    LIMIT 10
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get patient prescriptions
$stmt = $conn->prepare("
    SELECT p.*, mr.diagnostic, a.appointment_date 
    FROM prescriptions p
    JOIN medical_records mr ON p.record_id = mr.record_id
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    WHERE p.patient_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle appointment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_appointment'])) {
    $requested_date = sanitize($_POST['requested_date']);
    $reason = sanitize($_POST['reason']);
    
    // Create pending appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments (patient_id, appointment_date, appointment_time, status, created_by) 
        VALUES (?, ?, '00:00:00', 'pending', ?)
    ");
    // Use a default user_id for patient-created appointments
    $default_user = 1;
    $stmt->bind_param("isi", $_SESSION['user_id'], $requested_date, $default_user);
    $stmt->execute();
    $stmt->close();
    
    $success_message = "Votre demande de rendez-vous a été envoyée. Le secrétariat vous contactera pour confirmer.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace Patient</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <p style="font-size: 0.75rem;">Patient</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="patient_dashboard.php" class="nav-link active">
                        <i class="fas fa-home icon"></i>
                        <span>Accueil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_records.php" class="nav-link">
                        <i class="fas fa-file-medical icon"></i>
                        <span>Mes documents</span>
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
                <h1>Mon Espace Patient</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['name']; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-500);">Patient</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Patient Info Card -->
            <div class="card" style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-teal)); color: white;">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h2 style="color: white; margin-bottom: 0.5rem;">
                            <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>
                        </h2>
                        <p style="opacity: 0.9;"><i class="fas fa-phone"></i> <?php echo $patient['phone']; ?></p>
                        <?php if ($patient['date_of_birth']): ?>
                            <p style="opacity: 0.9;"><i class="fas fa-birthday-cake"></i> <?php echo formatDate($patient['date_of_birth']); ?></p>
                        <?php endif; ?>
                        <?php if ($patient['cnam_number']): ?>
                            <p style="opacity: 0.9;"><i class="fas fa-id-card"></i> CNAM: <?php echo $patient['cnam_number']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Request Appointment -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-plus"></i> Demander un rendez-vous</h3>
                </div>
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; align-items: end;">
                        <div class="form-group">
                            <label class="form-label">Date souhaitée</label>
                            <input type="date" name="requested_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Motif (optionnel)</label>
                            <input type="text" name="reason" class="form-control" placeholder="Ex: Contrôle">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="request_appointment" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Envoyer la demande
                            </button>
                        </div>
                    </div>
                </form>
                <p style="color: var(--gray-500); font-size: 0.875rem; margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> Le secrétariat vous contactera pour confirmer l'heure exacte du rendez-vous.
                </p>
            </div>
            
            <!-- Appointments History -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Mes rendez-vous</h3>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Type</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--gray-500);">
                                        Aucun rendez-vous
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr>
                                        <td><?php echo formatDate($apt['appointment_date']); ?></td>
                                        <td><?php echo $apt['appointment_time'] !== '00:00:00' ? date('H:i', strtotime($apt['appointment_time'])) : 'À confirmer'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $apt['control_status'] === 'first_visit' ? 'badge-info' : 'badge-success'; ?>">
                                                <?php echo $apt['control_status'] === 'first_visit' ? 'Première visite' : 'Contrôle'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-info';
                                            $status_text = 'En attente';
                                            if ($apt['status'] === 'confirmed') {
                                                $badge_class = 'badge-success';
                                                $status_text = 'Confirmé';
                                            } elseif ($apt['status'] === 'completed') {
                                                $badge_class = 'badge-info';
                                                $status_text = 'Complété';
                                            } elseif ($apt['status'] === 'canceled') {
                                                $badge_class = 'badge-danger';
                                                $status_text = 'Annulé';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-pills"></i> Mes ordonnances récentes</h3>
                    <a href="patient_records.php" class="btn btn-secondary">
                        Voir tout
                    </a>
                </div>
                <?php if (empty($prescriptions)): ?>
                    <p style="color: var(--gray-500);">Aucune ordonnance disponible</p>
                <?php else: ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach (array_slice($prescriptions, 0, 3) as $prescription): ?>
                            <div style="padding: 1rem; background: var(--gray-50); border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo formatDate($prescription['appointment_date']); ?></strong>
                                        <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0.25rem 0 0 0;">
                                            <?php echo substr($prescription['diagnostic'], 0, 80); ?>...
                                        </p>
                                    </div>
                                    <a href="patient_records.php" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
