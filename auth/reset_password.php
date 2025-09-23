<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Validasi token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = "Token reset password tidak valid.";
} else {
    $token = urldecode($_GET['token']);
    error_log("Token received: " . $token);
    
    // Cek token di database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Token reset password tidak valid atau telah kadaluarsa.";
        error_log("Token validation failed for: $token");
        
        // Debugging tambahan
        $stmt2 = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
        $stmt2->execute([$token]);
        $user_without_time_check = $stmt2->fetch();
        
        if ($user_without_time_check) {
            error_log("Token exists but expired. Expires: " . $user_without_time_check['reset_expires']);
            
            // Get current time
            $stmt_time = $pdo->query("SELECT NOW() as current_time");
            $current_time = $stmt_time->fetch();
            error_log("Current database time: " . $current_time['current_time']);
        } else {
            error_log("Token not found in database.");
        }
    }
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Hash password baru
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password dan hapus token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = "Password berhasil direset. Silakan login dengan password baru.";
        } else {
            $error = "Terjadi kesalahan saat mereset password. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-start: #4361ee;
            --gradient-end: #3a0ca3;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            overflow: hidden;
            position: relative;
        }
        
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 250px;
            height: 250px;
            top: -100px;
            right: -50px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 180px;
            height: 180px;
            bottom: -80px;
            left: -40px;
            animation-delay: 1s;
        }
        
        .shape-3 {
            width: 120px;
            height: 120px;
            top: 30%;
            right: 20%;
            animation-delay: 2s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }
        
        .reset-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            margin: 0 auto;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .reset-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        
        .reset-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }
        
        .reset-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 20px;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .text-accent {
            color: var(--accent-color);
        }
        
        .text-accent:hover {
            color: var(--secondary-color);
        }

        .form-group {
            position: relative;
            margin-bottom: 15px;
        }
        
        .form-group label {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            background-color: white;
            padding: 0 5px;
            color: #6c757d;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1;
            font-size: 0.9rem;
        }
        
        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 0.75rem;
            color: var(--primary-color);
        }
        
        .floating-label {
            position: relative;
            margin-bottom: 20px;
        }
        
        .floating-label label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            background-color: white;
            padding: 0 5px;
            color: #6c757d;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .floating-label input:focus + label,
        .floating-label input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 0.8rem;
            color: var(--primary-color);
        }
        
        .password-strength {
            height: 8px;
            margin-top: 5px;
            border-radius: 10px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .progress-bar-weak {
            background: linear-gradient(to right, #dc3545, #fd7e14);
        }
        
        .progress-bar-medium {
            background: linear-gradient(to right, #fd7e14, #ffc107);
        }
        
        .progress-bar-strong {
            background: linear-gradient(to right, #20c997, #198754);
        }
        
        .illustration {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .illustration i {
            font-size: 5rem;
            color: var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <div class="container">
        <div class="reset-container" data-aos="fade-up" data-aos-duration="1000">
            <div class="reset-header">
                <h2><i class="bi bi-geo-alt-fill me-2"></i>Wisata<strong>UMKM</strong></h2>
                <p class="text-muted">Buat Password Baru</p>
            </div>
            
            <div class="illustration">
                <i class="bi bi-shield-lock"></i>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
                </div>
            <?php elseif (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Login Sekarang</a>
                </div>
            <?php else: ?>
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                        <label for="password"><i class="bi bi-lock me-2"></i>Password Baru</label>
                    </div>
                    <div class="form-group">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder=" " required>
                        <label for="confirm_password"><i class="bi bi-shield-lock me-2"></i>Konfirmasi Password Baru</label>
                        <small id="confirmPasswordHelp" class="form-text"></small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3" id="submitButton">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init();
            
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');
            const submitButton = document.getElementById('submitButton');
            
            // Fungsi untuk mengecek kekuatan password
            function checkPasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 8) strength += 25;
                if (password.match(/[a-z]+/)) strength += 25;
                if (password.match(/[A-Z]+/)) strength += 25;
                if (password.match(/[0-9]+/)) strength += 25;
                
                return Math.min(strength, 100);
            }
            
            // Update strength bar saat password diinput
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const strength = checkPasswordStrength(this.value);
                    strengthBar.style.width = strength + '%';
                    
                    if (strength < 40) {
                        strengthBar.className = 'password-strength-bar progress-bar-weak';
                    } else if (strength < 80) {
                        strengthBar.className = 'password-strength-bar progress-bar-medium';
                    } else {
                        strengthBar.className = 'password-strength-bar progress-bar-strong';
                    }
                    
                    validatePasswords();
                });
            }
            
            // Validasi konfirmasi password
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validatePasswords);
            }
            
            function validatePasswords() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword === '') {
                    confirmPasswordHelp.textContent = '';
                    confirmPasswordHelp.className = 'form-text';
                    submitButton.disabled = false;
                    return;
                }
                
                if (password === confirmPassword) {
                    confirmPasswordHelp.className = 'form-text text-success';
                    confirmPasswordHelp.innerHTML = '<i class="bi bi-check-circle-fill"></i> Password cocok';
                    submitButton.disabled = false;
                } else {
                    confirmPasswordHelp.className = 'form-text text-danger';
                    confirmPasswordHelp.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Password tidak cocok';
                    submitButton.disabled = true;
                }
            }
            
            // Validasi form sebelum submit
            const resetForm = document.getElementById('resetForm');
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        confirmPasswordHelp.className = 'form-text text-danger';
                        confirmPasswordHelp.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Password tidak cocok';
                    }
                });
            }
            
            // Add animation to form elements
            const formElements = document.querySelectorAll('.form-control');
            formElements.forEach(element => {
                element.addEventListener('focus', () => {
                    element.parentElement.classList.add('focused');
                });
                element.addEventListener('blur', () => {
                    if (element.value === '') {
                        element.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>