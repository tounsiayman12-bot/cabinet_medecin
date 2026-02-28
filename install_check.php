<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Check - Medical Clinic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-item.success {
            background: #dcfce7;
            border-left: 4px solid #10b981;
        }
        .check-item.error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .check-item.warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .status {
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
        }
        .status.ok {
            background: #10b981;
            color: white;
        }
        .status.fail {
            background: #ef4444;
            color: white;
        }
        .status.warn {
            background: #f59e0b;
            color: white;
        }
        .action-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .action-btn:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }
        .info-box {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span style="font-size: 2.5rem;">🏥</span>
            Installation Check
        </h1>
        
        <?php
        $checks = [];
        $all_good = true;
        
        // Check PHP version
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '7.4', '>=');
        $checks[] = [
            'name' => 'PHP Version',
            'message' => "PHP $php_version " . ($php_ok ? '✓' : '✗ (Requires 7.4+)'),
            'status' => $php_ok ? 'success' : 'error',
            'label' => $php_ok ? 'OK' : 'FAIL'
        ];
        if (!$php_ok) $all_good = false;
        
        // Check MySQL connection
        $mysql_ok = false;
        $db_message = '';
        try {
            $conn = @new mysqli('localhost', 'root', '', 'medical_clinic');
            if ($conn->connect_error) {
                $mysql_ok = false;
                $db_message = 'Cannot connect: ' . $conn->connect_error;
            } else {
                $mysql_ok = true;
                $db_message = 'Connected successfully';
                $conn->close();
            }
        } catch (Exception $e) {
            $mysql_ok = false;
            $db_message = 'Database not found or connection failed';
        }
        $checks[] = [
            'name' => 'Database Connection',
            'message' => $db_message,
            'status' => $mysql_ok ? 'success' : 'error',
            'label' => $mysql_ok ? 'OK' : 'FAIL'
        ];
        if (!$mysql_ok) $all_good = false;
        
        // Check required directories
        $dirs = ['uploads/analyses', 'uploads/imaging', 'pdfs'];
        foreach ($dirs as $dir) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            $checks[] = [
                'name' => "Directory: $dir",
                'message' => $exists ? ($writable ? 'Exists and writable' : 'Exists but not writable') : 'Does not exist',
                'status' => $writable ? 'success' : ($exists ? 'warning' : 'error'),
                'label' => $writable ? 'OK' : ($exists ? 'WARN' : 'FAIL')
            ];
            if (!$writable) $all_good = false;
        }
        
        // Check required extensions
        $extensions = ['mysqli', 'session', 'json'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'name' => "PHP Extension: $ext",
                'message' => $loaded ? 'Loaded' : 'Not loaded',
                'status' => $loaded ? 'success' : 'error',
                'label' => $loaded ? 'OK' : 'FAIL'
            ];
            if (!$loaded) $all_good = false;
        }
        
        // Display checks
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<div>';
            echo '<strong>' . $check['name'] . '</strong><br>';
            echo '<small>' . $check['message'] . '</small>';
            echo '</div>';
            echo '<span class="status ' . ($check['label'] === 'OK' ? 'ok' : ($check['label'] === 'WARN' ? 'warn' : 'fail')) . '">';
            echo $check['label'];
            echo '</span>';
            echo '</div>';
        }
        
        // Summary
        if ($all_good) {
            echo '<div class="info-box" style="background: #dcfce7; border-left-color: #10b981;">';
            echo '<h3 style="color: #10b981; margin-top: 0;">✅ Installation Complete!</h3>';
            echo '<p>All checks passed. Your Medical Clinic Management Platform is ready to use.</p>';
            echo '<a href="index.php" class="action-btn">Go to Login Page</a>';
            echo '</div>';
        } else {
            echo '<div class="info-box" style="background: #fee2e2; border-left-color: #ef4444;">';
            echo '<h3 style="color: #ef4444; margin-top: 0;">❌ Installation Issues Detected</h3>';
            echo '<p><strong>Please fix the following:</strong></p>';
            echo '<ul>';
            if (!$php_ok) echo '<li>Upgrade PHP to version 7.4 or higher</li>';
            if (!$mysql_ok) echo '<li>Import database.sql into phpMyAdmin</li>';
            echo '<li>Ensure all required directories exist and are writable</li>';
            echo '<li>Enable all required PHP extensions</li>';
            echo '</ul>';
            echo '<p><strong>Need help?</strong> Check the README.md file for detailed installation instructions.</p>';
            echo '</div>';
        }
        ?>
        
        <div class="info-box">
            <h3 style="margin-top: 0; color: #2563eb;">📚 Quick Setup Guide</h3>
            <ol style="line-height: 1.8;">
                <li>Make sure XAMPP Apache and MySQL are running</li>
                <li>Import <code>database.sql</code> in phpMyAdmin</li>
                <li>Generate password hashes using <a href="generate_hash.php" style="color: #2563eb;">generate_hash.php</a></li>
                <li>Update user passwords in the database</li>
                <li>Access the application at <code>http://localhost/medical_clinic</code></li>
            </ol>
            
            <p><strong>Default Login Credentials:</strong></p>
            <ul style="line-height: 1.8;">
                <li><strong>Doctor:</strong> fares / fares123</li>
                <li><strong>Secretary:</strong> dalanda / dalanda123</li>
            </ul>
        </div>
    </div>
</body>
</html>
