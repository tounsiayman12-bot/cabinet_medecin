<?php
require_once 'includes/config.php';
requireRole('doctor');

$conn = getDBConnection();

// Get current year and month
$current_year = date('Y');
$current_month = date('m');

// Total appointments this year
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE YEAR(appointment_date) = ?");
$stmt->bind_param("i", $current_year);
$stmt->execute();
$total_year = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total appointments this month
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?");
$stmt->bind_param("ii", $current_year, $current_month);
$stmt->execute();
$total_month = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Confirmed vs Canceled
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE YEAR(appointment_date) = ? GROUP BY status");
$stmt->bind_param("i", $current_year);
$stmt->execute();
$result = $stmt->get_result();
$status_stats = ['confirmed' => 0, 'canceled' => 0, 'completed' => 0, 'pending' => 0];
while ($row = $result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
$stmt->close();

// Total revenue this year
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM revenue WHERE YEAR(revenue_date) = ?");
$stmt->bind_param("i", $current_year);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly revenue for chart
$monthly_revenue = [];
for ($m = 1; $m <= 12; $m++) {
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM revenue WHERE YEAR(revenue_date) = ? AND MONTH(revenue_date) = ?");
    $stmt->bind_param("ii", $current_year, $m);
    $stmt->execute();
    $monthly_revenue[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Médecin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="doctor_dashboard.php" class="nav-link active">
                        <i class="fas fa-chart-line icon"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calendar.php" class="nav-link">
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
                <h1>Tableau de bord</h1>
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
            
            <!-- Statistics Cards -->
            <div class="stat-grid">
                <div class="stat-card blue">
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_year; ?></div>
                    <div class="stat-label">Rendez-vous cette année</div>
                </div>
                
                <div class="stat-card green">
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_month; ?></div>
                    <div class="stat-label">Rendez-vous ce mois</div>
                </div>
                
                <div class="stat-card amber">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $status_stats['confirmed'] + $status_stats['completed']; ?></div>
                    <div class="stat-label">Confirmés</div>
                </div>
                
                <div class="stat-card red">
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $status_stats['canceled']; ?></div>
                    <div class="stat-label">Annulés</div>
                </div>
            </div>
            
            <!-- Revenue Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Revenus <?php echo $current_year; ?></h3>
                    <div class="stat-value" style="color: var(--accent-green);">
                        <?php echo number_format($total_revenue, 2); ?> TND
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <h4>Revenus mensuels</h4>
                    </div>
                    <canvas id="revenueChart"></canvas>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4>Statut des rendez-vous</h4>
                    </div>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [{
                    label: 'Revenus (TND)',
                    data: <?php echo json_encode($monthly_revenue); ?>,
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Confirmés', 'Complétés', 'Annulés', 'En attente'],
                datasets: [{
                    data: [
                        <?php echo $status_stats['confirmed']; ?>,
                        <?php echo $status_stats['completed']; ?>,
                        <?php echo $status_stats['canceled']; ?>,
                        <?php echo $status_stats['pending']; ?>
                    ],
                    backgroundColor: [
                        'rgb(37, 99, 235)',
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
