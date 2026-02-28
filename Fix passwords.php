<?php
/**
 * Password Fix Script
 * This script generates correct password hashes and updates the database
 * Run this ONCE after importing database.sql
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'medical_clinic';

// Connect to database
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Password Fix - Medical Clinic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 30px;
        }
        .success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .info {
            background: #f0f9ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1e40af;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Password Fix Script</h1>";

// Hash the passwords
$fares_password = 'fares123';
$dalanda_password = 'dalanda123';

$fares_hash = password_hash($fares_password, PASSWORD_DEFAULT);
$dalanda_hash = password_hash($dalanda_password, PASSWORD_DEFAULT);

echo "<div class='info'><strong>🔄 Generating password hashes...</strong></div>";

// Update fares password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'fares'");
$stmt->bind_param("s", $fares_hash);
if ($stmt->execute()) {
    echo "<div class='success'>✅ <strong>Doctor password updated successfully!</strong><br>
          Username: <code>fares</code><br>
          Password: <code>fares123</code></div>";
} else {
    echo "<div class='error'>❌ Failed to update doctor password: " . $stmt->error . "</div>";
}
$stmt->close();

// Update dalanda password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'dalanda'");
$stmt->bind_param("s", $dalanda_hash);
if ($stmt->execute()) {
    echo "<div class='success'>✅ <strong>Secretary password updated successfully!</strong><br>
          Username: <code>dalanda</code><br>
          Password: <code>dalanda123</code></div>";
} else {
    echo "<div class='error'>❌ Failed to update secretary password: " . $stmt->error . "</div>";
}
$stmt->close();

$conn->close();

echo "<div class='info'>
        <h3 style='margin-top: 0;'>📋 Next Steps:</h3>
        <ol>
            <li>Go to the <a href='index.php' style='color: #2563eb;'>login page</a></li>
            <li>Select your role (Médecin or Secrétaire)</li>
            <li>Enter your credentials</li>
            <li>You can delete this file (<code>fix_passwords.php</code>) for security</li>
        </ol>
      </div>
      
      <div style='margin-top: 30px; padding: 20px; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;'>
        <strong>⚠️ Security Note:</strong><br>
        For production use, please:
        <ul>
            <li>Change these default passwords immediately</li>
            <li>Use strong passwords (12+ characters)</li>
            <li>Delete this script after use</li>
            <li>Enable SSL/HTTPS</li>
        </ul>
      </div>
      
      <a href='index.php' class='btn'>🚀 Go to Login Page</a>
    </div>
</body>
</html>";
?>