<?php
require_once 'includes/config.php';
requireRole('doctor');

$conn = getDBConnection();

// Get appointments for current week
$today = date('Y-m-d');
$start_of_week = isset($_GET['week']) && $_GET['week'] === 'prev' 
    ? date('Y-m-d', strtotime('monday last week'))
    : (isset($_GET['week']) && $_GET['week'] === 'next'
        ? date('Y-m-d', strtotime('monday next week'))
        : date('Y-m-d', strtotime('monday this week')));
$end_of_week = date('Y-m-d', strtotime($start_of_week . ' +6 days'));

$appointments = [];
$stmt = $conn->prepare("
    SELECT a.*, p.first_name, p.last_name, p.phone, p.CIN, p.Internal_ID 
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
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier - Médecin</title>
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
                <p style="font-size: 0.75rem;">Médecin</p>
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
                    <a href="doctor_settings.php" class="nav-link">
                        <i class="fas fa-cog icon"></i>
                        <span>Paramètres</span>
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
                <h1>Calendrier des consultations</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['name']; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-500);">Médecin</div>
                    </div>
                </div>
            </div>
            
            <!-- Calendar -->
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <h3>Semaine du <?php echo formatDate($start_of_week); ?> au <?php echo formatDate($end_of_week); ?></h3>
                    <div>
                        <button class="btn btn-secondary" onclick="location.href='?week=prev'">
                            <i class="fas fa-chevron-left"></i> Précédente
                        </button>
                        <button class="btn btn-secondary" onclick="location.href='doctor_calendar.php'">
                            Aujourd'hui
                        </button>
                        <button class="btn btn-secondary" onclick="location.href='?week=next'">
                            Suivante <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="calendar-days">
                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        $current_date = date('Y-m-d', strtotime($start_of_week . " +$i days"));
                        $day_name = date('l', strtotime($current_date));
                        $day_number = date('d/m', strtotime($current_date));
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
                                        <div class="appointment-item" onclick="openMedicalFile(<?php echo $apt['Internal_ID']; ?>, <?php echo $apt['appointment_id']; ?>)" style="cursor: pointer;">
                                            <strong><?php echo date('H:i', strtotime($apt['appointment_time'])); ?></strong><br>
                                            <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?><br>
                                            <i class="fas fa-phone"></i> <?php echo $apt['phone']; ?><br>
                                            <span class="badge <?php echo $apt['control_status'] === 'first_visit' ? 'badge-info' : 'badge-success'; ?>">
                                                <?php echo $apt['control_status'] === 'first_visit' ? 'Première visite' : 'Contrôle'; ?>
                                            </span>
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
    
    <script>
        function openMedicalFile(patientId, appointmentId) {
            window.location.href = 'doctor_medical_file.php?patient=' + patientId + '&appointment=' + appointmentId;
        }
    </script>
</body>
</html>
