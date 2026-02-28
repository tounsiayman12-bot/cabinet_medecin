<?php
require_once 'includes/config.php';
requireRole('secretary');

$conn = getDBConnection();

// Handle appointment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $cin = sanitize($_POST['cin']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $cnam_number = sanitize($_POST['cnam_number']);
    $dob = sanitize($_POST['date_of_birth']);
    $payment = floatval($_POST['payment']);
    $control_status = sanitize($_POST['control_status']);
    $appointment_date = sanitize($_POST['appointment_date']);
    $appointment_time = sanitize($_POST['appointment_time']);
    
    // Check if patient exists by CIN or phone
    $patient_id = null;
    if (!empty($cin)) {
        $stmt = $conn->prepare("SELECT Internal_ID FROM patients WHERE CIN = ?");
        $stmt->bind_param("s", $cin);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $patient_id = $result->fetch_assoc()['Internal_ID'];
        }
        $stmt->close();
    }
    
    if (!$patient_id && !empty($phone)) {
        $stmt = $conn->prepare("SELECT Internal_ID FROM patients WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $patient_id = $result->fetch_assoc()['Internal_ID'];
        }
        $stmt->close();
    }
    
    // Create new patient if doesn't exist
    if (!$patient_id) {
        $temp_password = password_hash('temp123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO patients (CIN, first_name, last_name, phone, cnam_number, date_of_birth, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $cin_value = !empty($cin) ? $cin : null;
        $stmt->bind_param("sssssss", $cin_value, $first_name, $last_name, $phone, $cnam_number, $dob, $temp_password);
        $stmt->execute();
        $patient_id = $stmt->insert_id;
        $stmt->close();
    }
    
    // Create appointment
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, payment_amount, control_status, status, created_by) VALUES (?, ?, ?, ?, ?, 'confirmed', ?)");
    $stmt->bind_param("issdsi", $patient_id, $appointment_date, $appointment_time, $payment, $control_status, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Add revenue
    $stmt = $conn->prepare("INSERT INTO revenue (appointment_id, amount, payment_date, revenue_date) VALUES (LAST_INSERT_ID(), ?, ?, ?)");
    $stmt->bind_param("dss", $payment, $appointment_date, $appointment_date);
    $stmt->execute();
    $stmt->close();
}

// Get current week appointments
$today = date('Y-m-d');
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));

$appointments = [];
$stmt = $conn->prepare("
    SELECT a.*, p.first_name, p.last_name, p.phone, p.CIN 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.Internal_ID 
    WHERE a.appointment_date BETWEEN ? AND ? 
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt->bind_param("ss", $start_of_week, $end_of_week);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    if (!isset($appointments[$date])) {
        $appointments[$date] = [];
    }
    $appointments[$date][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Secrétaire</title>
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
                <p style="font-size: 0.75rem;">Secrétaire</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="secretary_dashboard.php" class="nav-link active">
                        <i class="fas fa-calendar-alt icon"></i>
                        <span>Calendrier</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="secretary_search.php" class="nav-link">
                        <i class="fas fa-search icon"></i>
                        <span>Recherche</span>
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
                <h1>Calendrier des Rendez-vous</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['name']; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-500);">Secrétaire</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <button class="btn btn-primary" onclick="openAddAppointmentModal()">
                    <i class="fas fa-plus"></i> Ajouter un rendez-vous
                </button>
            </div>
            
            <!-- Calendar -->
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <h3>Semaine du <?php echo formatDate($start_of_week); ?> au <?php echo formatDate($end_of_week); ?></h3>
                    <div>
                        <button class="btn btn-secondary" onclick="location.href='?week=prev'">
                            <i class="fas fa-chevron-left"></i> Semaine précédente
                        </button>
                        <button class="btn btn-secondary" onclick="location.href='?week=next'">
                            Semaine suivante <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="calendar-days">
                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        $current_date = date('Y-m-d', strtotime($start_of_week . " +$i days"));
                        $day_name = date('l', strtotime($current_date));
                        $day_number = date('d', strtotime($current_date));
                        $is_today = ($current_date === $today);
                        
                        $day_appointments = isset($appointments[$current_date]) ? $appointments[$current_date] : [];
                    ?>
                        <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>">
                            <div class="day-number">
                                <?php echo $day_name; ?><br>
                                <strong><?php echo $day_number; ?></strong>
                            </div>
                            <div class="day-appointments">
                                <?php if (empty($day_appointments)): ?>
                                    <p style="color: var(--gray-400); font-size: 0.875rem;">Aucun rendez-vous</p>
                                <?php else: ?>
                                    <?php foreach ($day_appointments as $apt): ?>
                                        <div class="appointment-item">
                                            <strong><?php echo date('H:i', strtotime($apt['appointment_time'])); ?></strong><br>
                                            <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?><br>
                                            <i class="fas fa-phone"></i> <?php echo $apt['phone']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Appointment Modal -->
    <div id="addAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un rendez-vous</h3>
                <button class="modal-close" onclick="closeAddAppointmentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">CIN (optionnel)</label>
                        <input type="text" name="cin" id="cin" class="form-control" onblur="lookupPatient()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Téléphone *</label>
                        <input type="text" name="phone" id="phone" class="form-control" required onblur="lookupPatient()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Numéro CNAM</label>
                        <input type="text" name="cnam_number" id="cnam_number" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Paiement (TND) *</label>
                        <input type="number" step="0.01" name="payment" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Type de consultation *</label>
                        <select name="control_status" class="form-control" required>
                            <option value="first_visit">Première visite</option>
                            <option value="control">Contrôle</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date du rendez-vous *</label>
                        <input type="date" name="appointment_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Heure *</label>
                        <input type="time" name="appointment_time" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" name="add_appointment" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddAppointmentModal() {
            document.getElementById('addAppointmentModal').classList.add('active');
        }
        
        function closeAddAppointmentModal() {
            document.getElementById('addAppointmentModal').classList.remove('active');
        }
        
        function lookupPatient() {
            const cin = document.getElementById('cin').value;
            const phone = document.getElementById('phone').value;
            
            if (cin || phone) {
                fetch('api/lookup_patient.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({cin: cin, phone: phone})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('first_name').value = data.patient.first_name;
                        document.getElementById('last_name').value = data.patient.last_name;
                        document.getElementById('phone').value = data.patient.phone;
                        document.getElementById('cnam_number').value = data.patient.cnam_number || '';
                        document.getElementById('date_of_birth').value = data.patient.date_of_birth || '';
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
