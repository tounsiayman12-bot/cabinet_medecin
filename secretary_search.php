<?php
require_once 'includes/config.php';
requireRole('secretary');

$conn = getDBConnection();
$search_results = [];
$search_query = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_query = sanitize($_GET['search_query']);
    
    // Search in patients and appointments
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            p.Internal_ID, p.CIN, p.first_name, p.last_name, p.phone, p.date_of_birth,
            a.appointment_id, a.appointment_date, a.appointment_time, a.status
        FROM patients p
        LEFT JOIN appointments a ON p.Internal_ID = a.patient_id
        WHERE p.CIN LIKE ? 
           OR p.first_name LIKE ? 
           OR p.last_name LIKE ? 
           OR p.phone LIKE ?
           OR a.appointment_date LIKE ?
        ORDER BY a.appointment_date DESC, p.last_name ASC
    ");
    $search_param = "%$search_query%";
    $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche - Secrétaire</title>
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
                    <a href="secretary_dashboard.php" class="nav-link">
                        <i class="fas fa-calendar-alt icon"></i>
                        <span>Calendrier</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="secretary_search.php" class="nav-link active">
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
                <h1>Recherche de patients</h1>
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
            
            <!-- Search Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Recherche multi-critères</h3>
                </div>
                <form method="GET" action="">
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <input type="text" 
                                   name="search_query" 
                                   class="form-control" 
                                   placeholder="Rechercher par CIN, Nom, Prénom, Téléphone ou Date (YYYY-MM-DD)"
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   autofocus>
                        </div>
                        <button type="submit" name="search" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 1rem; color: var(--gray-500); font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> 
                    Entrez un CIN, nom, prénom, téléphone ou une date pour rechercher. Pour rechercher par date future, le calendrier affichera automatiquement cette date.
                </div>
            </div>
            
            <!-- Search Results -->
            <?php if (isset($_GET['search'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Résultats de recherche</h3>
                        <span class="badge badge-info">
                            <?php echo count($search_results); ?> résultat(s)
                        </span>
                    </div>
                    
                    <?php if (empty($search_results)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                            <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>Aucun résultat trouvé pour "<?php echo htmlspecialchars($search_query); ?>"</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>CIN</th>
                                        <th>Téléphone</th>
                                        <th>Date de naissance</th>
                                        <th>Prochain RDV</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $processed_patients = [];
                                    foreach ($search_results as $result): 
                                        // Group by patient to avoid duplicates
                                        $patient_key = $result['Internal_ID'];
                                        if (in_array($patient_key, $processed_patients)) continue;
                                        $processed_patients[] = $patient_key;
                                        
                                        // Get next appointment
                                        $next_appointment = null;
                                        foreach ($search_results as $r) {
                                            if ($r['Internal_ID'] === $result['Internal_ID'] && 
                                                $r['appointment_date'] && 
                                                strtotime($r['appointment_date']) >= strtotime('today')) {
                                                $next_appointment = $r;
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $result['first_name'] . ' ' . $result['last_name']; ?></strong>
                                            </td>
                                            <td><?php echo $result['CIN'] ?: '-'; ?></td>
                                            <td><i class="fas fa-phone"></i> <?php echo $result['phone']; ?></td>
                                            <td>
                                                <?php 
                                                if ($result['date_of_birth']) {
                                                    echo formatDate($result['date_of_birth']);
                                                    $age = floor((time() - strtotime($result['date_of_birth'])) / (365*24*60*60));
                                                    echo " ($age ans)";
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($next_appointment): ?>
                                                    <strong><?php echo formatDate($next_appointment['appointment_date']); ?></strong><br>
                                                    <small><?php echo date('H:i', strtotime($next_appointment['appointment_time'])); ?></small>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-400);">Aucun</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($next_appointment): ?>
                                                    <?php
                                                    $badge_class = 'badge-info';
                                                    $status_text = 'En attente';
                                                    if ($next_appointment['status'] === 'confirmed') {
                                                        $badge_class = 'badge-success';
                                                        $status_text = 'Confirmé';
                                                    } elseif ($next_appointment['status'] === 'canceled') {
                                                        $badge_class = 'badge-danger';
                                                        $status_text = 'Annulé';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($next_appointment): ?>
                                                    <button class="btn btn-secondary" onclick="navigateToDate('<?php echo $next_appointment['appointment_date']; ?>')">
                                                        <i class="fas fa-calendar"></i> Voir
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary" onclick="openAddAppointmentForPatient(<?php echo $result['Internal_ID']; ?>)">
                                                        <i class="fas fa-plus"></i> Ajouter RDV
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function navigateToDate(date) {
            // Calculate which week this date is in
            window.location.href = 'secretary_dashboard.php?date=' + date;
        }
        
        function openAddAppointmentForPatient(patientId) {
            window.location.href = 'secretary_dashboard.php?add_for=' + patientId;
        }
    </script>
</body>
</html>
