<?php
session_start();

$users_file = 'users.json';
$cookie_name = 'rolex_user_auth';
$cookie_duration = 30 * 24 * 60 * 60;
$redirect_url = 'https://RolexCoderZ.xyz/Course';

// Email configuration
$smtp_config = [
    'host' => 'smtp.gmail.com', // Change to your SMTP host
    'port' => 587,
    'username' => 'your-email@gmail.com', // Your email
    'password' => 'your-app-password', // Your app password
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'RolexCoderZ Academy'
];

if (isset($_COOKIE[$cookie_name]) || isset($_SESSION['user_logged_in'])) {
    if (isset($_COOKIE[$cookie_name]) && !isset($_SESSION['user_logged_in'])) {
        $cookie_data = json_decode(base64_decode($_COOKIE[$cookie_name]), true);
        if ($cookie_data && isset($cookie_data['email'])) {
            $users = loadUsers($users_file);
            $user = findUser($users, $cookie_data['email']);
            if ($user) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
            }
        }
    }
    header("Location: $redirect_url");
    exit();
}

if (!file_exists($users_file)) {
    file_put_contents($users_file, json_encode([]));
}

function loadUsers($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function saveUsers($file, $users) {
    return file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

function findUser($users, $email) {
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function sendWelcomeEmail($email, $name) {
    global $smtp_config;
    
    $subject = "🎉 Welcome to RolexCoderZ Academy - Your Premium Education Journey Starts Now!";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .header { background: rgba(255,255,255,0.1); padding: 30px; text-align: center; }
            .content { background: white; padding: 40px; margin: 20px; border-radius: 10px; }
            .highlight { color: #667eea; font-weight: bold; }
            .courses { background: #f8f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: white; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='color: white; margin: 0;'>🎓 RolexCoderZ Academy</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0;'>Premium Education, Zero Cost</p>
            </div>
            <div class='content'>
                <h2>Welcome aboard, <span class='highlight'>$name</span>! 🚀</h2>
                <p>Congratulations on joining India's most trusted free education platform! You've just unlocked access to premium courses worth thousands of rupees - completely FREE!</p>
                
                <div class='courses'>
                    <h3>🎯 Your Learning Journey Includes:</h3>
                    <ul>
                        <li><strong>Class 9th-10th:</strong> Aarambh Batch - Foundation building courses</li>
                        <li><strong>Class 11th:</strong> Prarambh Batch - Advanced learning for all streams</li>
                        <li><strong>All Streams:</strong> Science, Commerce, Humanities</li>
                        <li><strong>Premium DPP Notes:</strong> Daily Practice Problems</li>
                        <li><strong>Expert Faculty:</strong> Top educators from across India</li>
                        <li><strong>Live Classes:</strong> Interactive learning sessions</li>
                    </ul>
                </div>
                
                <p><strong>What makes us special?</strong></p>
                <ul>
                    <li>✅ 100% Free premium content</li>
                    <li>✅ No hidden charges ever</li>
                    <li>✅ Quality education for every student</li>
                    <li>✅ Comprehensive study materials</li>
                    <li>✅ Regular assessments and feedback</li>
                </ul>
                
                <p>Your educational success is our mission. We believe every student deserves quality education regardless of their financial background.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$redirect_url' style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold;'>Start Learning Now 📚</a>
                </div>
                
                <p>Need help? Our support team is always ready to assist you on your learning journey.</p>
                
                <p>Best wishes for your academic success!</p>
                <p><strong>Team RolexCoderZ Academy</strong></p>
            </div>
            <div class='footer'>
                <p>© 2024 RolexCoderZ Academy - Empowering Students Across India</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $smtp_config['from_name'] . " <" . $smtp_config['from_email'] . ">" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = loadUsers($users_file);

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $user = findUser($users, $email);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $user['name'];

                $cookie_data = base64_encode(json_encode([
                    'email' => $email,
                    'name' => $user['name']
                ]));
                setcookie($cookie_name, $cookie_data, time() + $cookie_duration, '/');

                header("Location: $redirect_url");
                exit();
            } else {
                $message = 'Invalid email or password! Please check your credentials.';
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'signup') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $class = $_POST['class'];

            if (empty($name) || empty($email) || empty($password) || empty($class)) {
                $message = 'All fields are required to join our academy!';
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = 'Passwords do not match! Please try again.';
                $message_type = 'error';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters long for security!';
                $message_type = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Please enter a valid email address!';
                $message_type = 'error';
            } elseif (findUser($users, $email)) {
                $message = 'This email is already registered! Please login instead.';
                $message_type = 'error';
            } else {
                $batch = '';
                if (in_array($class, ['9th', '10th'])) {
                    $batch = 'Aarambh Batch';
                } elseif ($class === '11th') {
                    $batch = 'Prarambh Batch';
                }

                $new_user = [
                    'id' => uniqid(),
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'class' => $class,
                    'batch' => $batch,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $users[] = $new_user;

                if (saveUsers($users_file, $users)) {
                    // Send welcome email
                    if (sendWelcomeEmail($email, $name)) {
                        $message = "Welcome to RolexCoderZ Academy, $name! Check your email for course details.";
                        $message_type = 'success';
                    }
                    
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $name;

                    $cookie_data = base64_encode(json_encode([
                        'email' => $email,
                        'name' => $name
                    ]));
                    setcookie($cookie_name, $cookie_data, time() + $cookie_duration, '/');

                    // Redirect after 2 seconds to show success message
                    echo "<script>setTimeout(function(){ window.location.href = '$redirect_url'; }, 2000);</script>";
                } else {
                    $message = 'Error creating your account. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RolexCoderZ Academy - Free Premium Education for Class 9th-11th</title>
    <meta name="description" content="Join RolexCoderZ Academy for FREE premium courses for Class 9th-11th. Aarambh & Prarambh batches with expert faculty, DPP notes, and comprehensive study materials.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-tertiary: #16213e;
            --surface: rgba(255, 255, 255, 0.05);
            --surface-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-tertiary: rgba(255, 255, 255, 0.6);
            --glow-primary: 0 0 30px rgba(102, 126, 234, 0.3);
            --glow-secondary: 0 0 30px rgba(118, 75, 162, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            background: 
                radial-gradient(circle at 25% 25%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(118, 75, 162, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(240, 147, 251, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-tertiary) 100%);
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            border-radius: 50%;
            animation: float 20s infinite linear;
            opacity: 0.6;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 6s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 8s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 10s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 12s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 14s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 16s; }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        /* Grid Overlay */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: 
                linear-gradient(rgba(102, 126, 234, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(102, 126, 234, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 25s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .main-wrapper {
            display: flex;
            max-width: 1400px;
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            box-shadow: var(--glow-primary), 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .main-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), var(--secondary), transparent);
            opacity: 0.8;
        }

        /* Hero Section */
        .hero-section {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="30" x="10" font-size="20" fill="rgba(102,126,234,0.1)">📚</text><text y="60" x="70" font-size="16" fill="rgba(118,75,162,0.1)">🎓</text><text y="80" x="30" font-size="18" fill="rgba(240,147,251,0.1)">✨</text></svg>') repeat;
            background-size: 150px 150px;
            animation: floatIcons 30s linear infinite;
        }

        @keyframes floatIcons {
            0% { transform: translateY(0) translateX(0); }
            100% { transform: translateY(-150px) translateX(-150px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 32px;
            box-shadow: var(--glow-primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textGlow 3s ease-in-out infinite alternate;
        }

        @keyframes textGlow {
            from { filter: brightness(1); }
            to { filter: brightness(1.2); }
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.6;
            font-weight: 500;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .feature-card {
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: var(--surface-hover);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            display: block;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-tertiary);
            line-height: 1.4;
        }

        .stats-container {
            display: flex;
            gap: 32px;
            margin-top: 32px;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-tertiary);
        }

        /* Auth Form Section */
        .auth-form {
            flex: 0 0 550px;
            padding: 80px 60px;
            background: rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .form-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 32px;
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 24px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 16px;
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }

        .form-group input::placeholder {
            color: var(--text-tertiary);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.05);
            box-shadow: var(--glow-primary);
            transform: translateY(-2px);
        }

        .form-group select {
            cursor: pointer;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 24px;
            appearance: none;
        }

        .form-group select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 12px;
        }

        .submit-btn {
            width: 100%;
            padding: 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 16px;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow-primary), 0 20px 40px rgba(102, 126, 234, 0.4);
            filter: brightness(1.1);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .form-switch {
            text-align: center;
            margin-top: 40px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-switch a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .form-switch a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: width 0.3s ease;
        }

        .form-switch a:hover::after {
            width: 100%;
        }

        /* Message Styles */
        .message {
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            text-align: center;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid;
            animation: slideInMessage 0.5s ease;
        }

        @keyframes slideInMessage {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .hidden {
            display: none;
        }

        .form-transition {
            animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-wrapper {
                flex-direction: column;
                max-width: 700px;
            }

            .hero-section {
                padding: 60px 40px 40px;
            }

            .hero-title {
                font-size: 3rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .auth-form {
                padding: 50px 40px;
            }
        }

        @media (max-width: 768px) {
            .hero-section,
            .auth-form {
                padding: 40px 24px;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .form-title {
                font-size: 2.2rem;
            }

            .form-group input,
            .form-group select {
                padding: 20px;
                font-size: 15px;
            }

            .submit-btn {
                padding: 20px;
                font-size: 15px;
            }

            .stats-container {
                gap: 20px;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .form-title {
                font-size: 1.8rem;
            }

            .stats-container {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="grid-overlay"></div>

    <div class="container">
        <div class="main-wrapper">
            <div class="hero-section">
                <div class="hero-content">
                    <div class="hero-badge">
                        ✨ 100% FREE Premium Education
                    </div>
                    <h1 class="hero-title">Transform Your Future</h1>
                    <p class="hero-subtitle">Join thousands of students getting premium education absolutely FREE. No hidden costs, no compromises - just quality learning for Class 9th to 11th.</p>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <span class="feature-icon">📚</span>
                            <div class="feature-title">Aarambh Batch</div>
                            <div class="feature-desc">Foundation courses for Class 9th-10th with expert faculty</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">🚀</span>
                            <div class="feature-title">Prarambh Batch</div>
                            <div class="feature-desc">Advanced learning for Class 11th - All streams covered</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">📝</span>
                            <div class="feature-title">Premium DPP Notes</div>
                            <div class="feature-desc">Daily Practice Problems with detailed solutions</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">🎯</span>
                            <div class="feature-title">All Streams</div>
                            <div class="feature-desc">Science, Commerce, Humanities - Complete coverage</div>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat">
                            <span class="stat-number">10,000+</span>
                            <span class="stat-label">Happy Students</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Video Lectures</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Free Content</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-form">
                <div class="form-header">
                    <h2 class="form-title">Join Academy</h2>
                    <p class="form-subtitle">Start your premium learning journey today</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="POST" action="" class="form-transition">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <input type="email" name="email" required placeholder="Enter your email address">
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>

                    <button type="submit" class="submit-btn">
                        Access Dashboard
                        <span class="loading-spinner"></span>
                    </button>

                    <div class="form-switch">
                        New student? <a href="#" onclick="switchToSignup()">Join Free Now</a>
                    </div>
                </form>

                <form id="signupForm" method="POST" action="" class="hidden">
                    <input type="hidden" name="action" value="signup">
                    
                    <div class="form-group">
                        <input type="text" name="name" required placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <input type="email" name="email" required placeholder="Enter your email address">
                    </div>

                    <div class="form-group">
                        <select name="class" required>
                            <option value="">Select Your Class</option>
                            <option value="9th">Class 9th (Aarambh Batch)</option>
                            <option value="10th">Class 10th (Aarambh Batch)</option>
                            <option value="11th">Class 11th (Prarambh Batch)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" required placeholder="Create a strong password">
                    </div>

                    <div class="form-group">
                        <input type="password" name="confirm_password" required placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="submit-btn">
                        🎓 Join Academy FREE
                        <span class="loading-spinner"></span>
                    </button>

                    <div class="form-switch">
                        Already registered? <a href="#" onclick="switchToLogin()">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchToSignup() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const formTitle = document.querySelector('.form-title');
            const formSubtitle = document.querySelector('.form-subtitle');
            
            loginForm.style.animation = 'slideOut 0.4s ease-out forwards';
            setTimeout(() => {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                signupForm.classList.add('form-transition');
                formTitle.textContent = 'Create Account';
                formSubtitle.textContent = 'Join thousands of successful students';
            }, 400);
        }

        function switchToLogin() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const formTitle = document.querySelector('.form-title');
            const formSubtitle = document.querySelector('.form-subtitle');
            
            signupForm.style.animation = 'slideOut 0.4s ease-out forwards';
            setTimeout(() => {
                signupForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                loginForm.classList.add('form-transition');
                formTitle.textContent = 'Welcome Back';
                formSubtitle.textContent = 'Continue your learning journey';
            }, 400);
        }

        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Input focus effects
            document.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.closest('.form-group').style.transform = 'scale(1.02)';
                    this.closest('.form-group').style.transition = 'all 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.closest('.form-group').style.transform = 'scale(1)';
                });

                // Add typing animation effect
                input.addEventListener('input', function() {
                    this.style.borderColor = 'var(--primary)';
                    setTimeout(() => {
                        if (this !== document.activeElement) {
                            this.style.borderColor = 'var(--border)';
                        }
                    }, 1000);
                });
            });

            // Form submission with loading state
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const btn = this.querySelector('.submit-btn');
                    const spinner = btn.querySelector('.loading-spinner');
                    const originalText = btn.innerHTML;
                    
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.8';
                    spinner.style.display = 'inline-block';
                    
                    // Add success animation after form validation
                    const inputs = this.querySelectorAll('input[required], select[required]');
                    let allValid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            allValid = false;
                            input.style.borderColor = 'var(--error)';
                            input.style.animation = 'shake 0.5s ease';
                        }
                    });

                    if (!allValid) {
                        e.preventDefault();
                        btn.style.pointerEvents = 'auto';
                        btn.style.opacity = '1';
                        spinner.style.display = 'none';
                    }
                });
            });

            // Password strength indicator
            const passwordInput = document.querySelector('input[name="password"]');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const strength = calculatePasswordStrength(this.value);
                    updatePasswordStrengthIndicator(this, strength);
                });
            }

            // Email validation with visual feedback
            document.querySelectorAll('input[type="email"]').forEach(emailInput => {
                emailInput.addEventListener('blur', function() {
                    if (this.value && !isValidEmail(this.value)) {
                        this.style.borderColor = 'var(--error)';
                        showTooltip(this, 'Please enter a valid email address');
                    } else if (this.value) {
                        this.style.borderColor = 'var(--success)';
                    }
                });
            });
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength += 1;
            if (password.length >= 10) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            return strength;
        }

        function updatePasswordStrengthIndicator(input, strength) {
            const colors = ['var(--error)', '#f59e0b', '#f59e0b', 'var(--success)', 'var(--success)'];
            const messages = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            if (strength > 0) {
                input.style.borderColor = colors[strength - 1];
                input.setAttribute('title', `Password Strength: ${messages[strength - 1]}`);
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showTooltip(element, message) {
            const tooltip = document.createElement('div');
            tooltip.textContent = message;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--error);
                color: white;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 12px;
                top: -35px;
                left: 0;
                white-space: nowrap;
                z-index: 1000;
                animation: fadeIn 0.3s ease;
            `;
            
            element.parentElement.style.position = 'relative';
            element.parentElement.appendChild(tooltip);
            
            setTimeout(() => {
                tooltip.remove();
            }, 3000);
        }

        // Add slide out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                to {
                    opacity: 0;
                    transform: translateY(-30px);
                }
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);

        // Add smooth scrolling and page transitions
        window.addEventListener('load', function() {
            document.body.style.animation = 'fadeIn 0.8s ease';
        });

        // Dynamic particle generation
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
            particle.style.animationDelay = (Math.random() * 5) + 's';
            document.querySelector('.particles').appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 25000);
        }

        // Generate particles periodically
        setInterval(createParticle, 3000);
    </script>
</body>
</html>