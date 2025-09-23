<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        try {
            // Cek apakah email terdaftar dan merupakan user (bukan admin)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate token unik
                $token = bin2hex(random_bytes(50));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Simpan token di database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);
                
                // Pastikan base URL benar untuk environment production
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $resetLink = "$protocol://$host$basePath/reset_password.php?token=" . urlencode($token);
                
                // Log informasi token dan reset link
                error_log("Token created for user {$user['id']}: $token, expires: $expires");
                error_log("Reset link: $resetLink");
                
                // Kirim email menggunakan PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';
                    
                    // Tambahkan opsi ini untuk mengatasi masalah SSL
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                    
                    // Recipients
                    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
                    $mail->addAddress($email);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Password - Aplikasi Wisata & UMKM';
                    $mail->Body    = "
                        <h2>Reset Password</h2>
                        <p>Anda telah meminta untuk reset password. Silakan klik link di bawah ini untuk membuat password baru.</p>
                        <p><a href='$resetLink' style='display:inline-block;padding:10px 20px;background-color:#3498db;color:white;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
                        <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                    ";
                    $mail->AltBody = "Reset Password: $resetLink - Link ini akan kadaluarsa dalam 1 jam.";
                    
                    $mail->send();
                    $message = "Email instruksi reset password telah dikirim ke $email. Periksa folder spam jika tidak ditemukan.";
                } catch (Exception $e) {
                    error_log("Email error for $email: {$mail->ErrorInfo}");
                    $error = "Email tidak dapat dikirim. Silakan coba lagi nanti.";
                }

            } else {
                // Untuk keamanan, jangan beri tahu jika email tidak ditemukan
                $message = "Jika email Anda terdaftar, instruksi reset password telah dikirim.";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Aplikasi Wisata & UMKM</title>
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
            width: 280px;
            height: 280px;
            top: -140px;
            left: -90px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 180px;
            height: 180px;
            bottom: -90px;
            right: -45px;
            animation-delay: 1s;
        }
        
        .shape-3 {
            width: 120px;
            height: 120px;
            top: 40%;
            right: 15%;
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
        
        .forgot-container {
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
        
        .forgot-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .forgot-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        
        .forgot-header h2::after {
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
        
        .forgot-header p {
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
        <div class="forgot-container" data-aos="fade-up" data-aos-duration="1000">
            <div class="forgot-header">
                <h2><i class="bi bi-geo-alt-fill me-2"></i>Wisata<strong>UMKM</strong></h2>
                <p class="text-muted">Reset Password</p>
            </div>
            
            <div class="illustration">
                <i class="bi bi-key-fill"></i>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="floating-label">
                        <input type="email" class="form-control" id="email" name="email" placeholder=" " required>
                        <label for="email"><i class="bi bi-envelope me-2"></i>Masukkan Email Anda</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Kirim Link Reset</button>
                </form>
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none text-accent">Kembali ke Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init();
            
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