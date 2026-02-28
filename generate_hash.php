<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #1e40af;
        }
        .result {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            word-break: break-all;
            font-family: monospace;
        }
        .info {
            background: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        <div class="info">
            <strong>⚠️ Important:</strong> Use this tool to generate secure password hashes for the database. 
            After generating, copy the hash and update it in phpMyAdmin or the SQL file.
        </div>
        
        <form method="POST">
            <label>Enter password to hash:</label>
            <input type="text" name="password" placeholder="e.g., fares123" required>
            <button type="submit" name="generate">Generate Hash</button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            echo '<div class="result">';
            echo '<strong>Generated Hash:</strong><br><br>';
            echo $hash;
            echo '</div>';
            
            echo '<div class="info" style="margin-top: 20px; background: #dcfce7; border-left-color: #10b981;">';
            echo '<strong>SQL Update Query:</strong><br><br>';
            echo "UPDATE users SET password = '$hash' WHERE username = 'your_username';<br><br>";
            echo '<strong>OR for patients:</strong><br>';
            echo "UPDATE patients SET password = '$hash' WHERE phone = 'phone_number';";
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
            <h3>Default Credentials</h3>
            <p><strong>Doctor:</strong></p>
            <ul>
                <li>Username: fares</li>
                <li>Password: fares123</li>
            </ul>
            
            <p><strong>Secretary:</strong></p>
            <ul>
                <li>Username: dalanda</li>
                <li>Password: dalanda123</li>
            </ul>
            
            <p style="color: #6b7280; font-size: 14px;">
                Generate hashes for these passwords and update them in the database.
            </p>
        </div>
    </div>
</body>
</html>
