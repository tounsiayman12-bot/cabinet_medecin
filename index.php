<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'doctor') {
        header('Location: doctor_dashboard.php');
    } elseif ($role === 'secretary') {
        header('Location: secretary_dashboard.php');
    } elseif ($role === 'patient') {
        header('Location: patient_dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $role = sanitize($_POST['role']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    
    if ($role === 'patient') {
        // Patient login
        $stmt = $conn->prepare("SELECT Internal_ID, first_name, last_name, password FROM patients WHERE phone = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['Internal_ID'];
                $_SESSION['role'] = 'patient';
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                header('Location: patient_dashboard.php');
                exit();
            } else {
                $error = 'Identifiants incorrects';
            }
        } else {
            $error = 'Identifiants incorrects';
        }
    } else {
        // Doctor/Secretary login
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, password, role FROM users WHERE username = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                if ($user['role'] === 'doctor') {
                    header('Location: doctor_dashboard.php');
                } else {
                    header('Location: secretary_dashboard.php');
                }
                exit();
            } else {
                $error = 'Identifiants incorrects';
            }
        } else {
            $error = 'Identifiants incorrects';
        }
    }
    
    $stmt->close();
    $conn->close();
}

// Handle patient registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        $conn = getDBConnection();
        
        // Check if phone already exists
        $stmt = $conn->prepare("SELECT Internal_ID FROM patients WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Ce numéro de téléphone est déjà enregistré';
        } else {
            // Insert new patient
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $first_name, $last_name, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Erreur lors de l\'inscription';
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Médical - Connexion</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h2>Cabinet Médical</h2>
                <p style="color: var(--gray-500); font-size: 0.875rem;">Système de Gestion Médicale</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div id="loginForm">
                <form method="POST" action="">
                    <div class="role-selection">
                        <label class="role-btn" onclick="selectRole('doctor')">
                            <input type="radio" name="role" value="doctor" style="display: none;" required>
                            <div class="icon"><i class="fas fa-user-md"></i></div>
                            <div class="label">Médecin</div>
                        </label>
                        <label class="role-btn" onclick="selectRole('secretary')">
                            <input type="radio" name="role" value="secretary" style="display: none;" required>
                            <div class="icon"><i class="fas fa-user-tie"></i></div>
                            <div class="label">Secrétaire</div>
                        </label>
                        <label class="role-btn" onclick="selectRole('patient')">
                            <input type="radio" name="role" value="patient" style="display: none;" required>
                            <div class="icon"><i class="fas fa-user"></i></div>
                            <div class="label">Patient</div>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> 
                            <span id="usernameLabel">Nom d'utilisateur / Téléphone</span>
                        </label>
                        <input type="text" name="username" class="form-control" required placeholder="Entrez votre identifiant">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" name="password" class="form-control" required placeholder="Entrez votre mot de passe">
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="#" onclick="showRegisterForm(); return false;" style="color: var(--primary-blue); text-decoration: none;">
                        Nouveau patient ? <strong>Inscrivez-vous ici</strong>
                    </a>
                </div>
            </div>
            
            <!-- Registration Form -->
            <div id="registerForm" style="display: none;">
                <form method="POST" action="">
                    <h3 style="margin-bottom: 1.5rem; text-align: center;">Inscription Patient</h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Prénom
                        </label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Nom
                        </label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Téléphone
                        </label>
                        <input type="text" name="phone" class="form-control" required placeholder="Ex: 12345678">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Confirmer le mot de passe
                        </label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> S'inscrire
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="#" onclick="showLoginForm(); return false;" style="color: var(--primary-blue); text-decoration: none;">
                        Déjà inscrit ? <strong>Connectez-vous</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectRole(role) {
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            const usernameLabel = document.getElementById('usernameLabel');
            if (role === 'patient') {
                usernameLabel.textContent = 'Numéro de téléphone';
            } else {
                usernameLabel.textContent = 'Nom d\'utilisateur';
            }
        }
        
        function showRegisterForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
        }
        
        function showLoginForm() {
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
        }
    </script>
</body>
</html>
