<?php
session_start();
// Perbaikan path ke database.php - menggunakan relative path yang benar
require_once __DIR__ . '/../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak sesuai";
    } else {
        try {
            // Cek apakah email sudah terdaftar
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email sudah terdaftar";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Simpan user baru
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $_SESSION['success'] = "Pendaftaran berhasil. Silakan login.";
                    header('Location: login.php');
                    exit;
                } else {
                    $error = "Terjadi kesalahan. Silakan coba lagi.";
                }
            }
        } catch (PDOException $e) {
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
    <title>Daftar - Aplikasi Wisata & UMKM</title>
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
            padding: 15px;
        }
        
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 200px;
            height: 200px;
            top: -80px;
            right: -40px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            bottom: -60px;
            left: -30px;
            animation-delay: 1s;
        }
        
        .shape-3 {
            width: 100px;
            height: 100px;
            top: 30%;
            right: 15%;
            animation-delay: 2s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(5deg);
            }
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            margin: 0 auto;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .register-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }
        
        .register-header h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 2px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .register-header p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.25);
            font-size: 0.95rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.35);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
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
            font-weight: 500;
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
        
        .password-strength {
            height: 6px;
            margin-top: 8px;
            border-radius: 8px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        
        .requirement i {
            margin-right: 5px;
            font-size: 0.65rem;
        }
        
        .requirement.met {
            color: #198754;
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
        
        .footer-text {
            font-size: 0.9rem;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <div class="container">
        <div class="register-container" data-aos="fade-up" data-aos-duration="1000">
            <div class="register-header">
                <h2><i class="bi bi-geo-alt-fill me-2"></i>Wisata<strong>UMKM</strong></h2>
                <p class="text-muted">Buat akun baru</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <input type="text" class="form-control" id="name" name="name" placeholder=" " required>
                    <label for="name"><i class="bi bi-person me-2"></i>Nama Lengkap</label>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" id="email" name="email" placeholder=" " required>
                    <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                    <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                </div>
                <div class="form-group">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement" id="req-length"><i class="bi bi-circle"></i> Minimal 8 karakter</div>
                        <div class="requirement" id="req-uppercase"><i class="bi bi-circle"></i> Huruf kecil & besar (a-Z)</div>
                        <div class="requirement" id="req-number"><i class="bi bi-circle"></i> Memiliki angka (0-9)</div>
                        <div class="requirement" id="req-special"><i class="bi bi-circle"></i> Karakter khusus (Contoh: @, #, $, &)</div>
                    </div>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder=" " required>
                    <label for="confirm_password"><i class="bi bi-shield-lock me-2"></i>Konfirmasi Password</label>
                    <small id="confirmPasswordHelp" class="form-text"></small>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3" id="submitButton">Daftar</button>
            </form>
            
            <div class="text-center">
                <p class="mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none text-accent">Login di sini</a></p>
            </div>
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
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');
            const submitButton = document.getElementById('submitButton');
            
            // Fungsi untuk mengecek kekuatan password
            function checkPasswordStrength(password) {
                let strength = 0;
                let hasLength = password.length >= 8;
                let hasUppercase = /[A-Z]/.test(password);
                let hasNumber = /[0-9]/.test(password);
                let hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                // Update requirement indicators
                updateRequirement(reqLength, hasLength);
                updateRequirement(reqUppercase, hasUppercase);
                updateRequirement(reqNumber, hasNumber);
                updateRequirement(reqSpecial, hasSpecial);
                
                if (hasLength) strength += 25;
                if (hasUppercase) strength += 25;
                if (hasNumber) strength += 25;
                if (hasSpecial) strength += 25;
                
                return {
                    strength: Math.min(strength, 100),
                    hasLength,
                    hasUppercase,
                    hasNumber,
                    hasSpecial
                };
            }
            
            function updateRequirement(element, met) {
                if (met) {
                    element.classList.add('met');
                    element.innerHTML = element.innerHTML.replace('bi-circle', 'bi-check-circle-fill');
                } else {
                    element.classList.remove('met');
                    element.innerHTML = element.innerHTML.replace('bi-check-circle-fill', 'bi-circle');
                }
            }
            
            // Update strength bar saat password diinput
            passwordInput.addEventListener('input', function() {
                const result = checkPasswordStrength(this.value);
                strengthBar.style.width = result.strength + '%';
                
                if (result.strength < 40) {
                    strengthBar.className = 'password-strength-bar progress-bar-weak';
                } else if (result.strength < 80) {
                    strengthBar.className = 'password-strength-bar progress-bar-medium';
                } else {
                    strengthBar.className = 'password-strength-bar progress-bar-strong';
                }
                
                validatePasswords();
            });
            
            // Validasi konfirmasi password
            confirmPasswordInput.addEventListener('input', validatePasswords);
            
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
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    confirmPasswordHelp.className = 'form-text text-danger';
                    confirmPasswordHelp.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Password tidak cocok';
                }
            });
            
            // Inisialisasi floating labels
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach(formGroup => {
                const input = formGroup.querySelector('.form-control');
                const label = formGroup.querySelector('label');
                
                // Periksa saat halaman dimuat
                if (input.value) {
                    label.classList.add('active');
                }
                
                // Tambahkan event listeners
                input.addEventListener('focus', () => {
                    label.classList.add('active');
                });
                
                input.addEventListener('blur', () => {
                    if (!input.value) {
                        label.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>