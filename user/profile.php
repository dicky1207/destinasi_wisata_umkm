<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Jika user adalah admin, redirect ke admin dashboard
if ($_SESSION['user']['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Cek jika ada notifikasi success dari session
if (isset($_SESSION['profile_success'])) {
    $success = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']); // Hapus session setelah digunakan
}

// Query untuk data user
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Proses update profile dan password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    
    // Validasi input
    if (empty($name) || empty($email)) {
        $error = 'Nama dan email harus diisi';
    } else {
        try {
            // Cek jika email sudah digunakan oleh user lain
            $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->execute([$email, $user_id]);
            
            if ($stmt_check_email->rowCount() > 0) {
                $error = 'Email sudah digunakan oleh user lain';
            } else {
                // Handle file upload
                $avatarPath = $user['avatar']; // Default to current avatar
                
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    $fileType = $_FILES['avatar']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        $uploadDir = '../uploads/avatars/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                        $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
                        $destination = $uploadDir . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                            // Delete old avatar if it exists and is not the default
                            if (!empty($user['avatar']) && $user['avatar'] !== 'default_avatar.jpg' && file_exists('../' . $user['avatar'])) {
                                unlink('../' . $user['avatar']);
                            }
                            
                            $avatarPath = 'uploads/avatars/' . $filename;
                        } else {
                            $error = 'Gagal mengupload foto profil';
                        }
                    } else {
                        $error = 'Format file tidak didukung. Hanya PNG, JPG, dan JPEG yang diperbolehkan';
                    }
                }
                
                // Handle password change if provided
                $passwordUpdate = '';
                if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
                    $current_password = $_POST['current_password'];
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $error = 'Semua field password harus diisi jika ingin mengubah password';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Password baru dan konfirmasi password tidak cocok';
                    } elseif (strlen($new_password) < 8) {
                        $error = 'Password baru harus minimal 8 karakter';
                    } else {
                        // Verify current password
                        if (password_verify($current_password, $user['password'])) {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $passwordUpdate = ", password = ?";
                            $updateParams = [$name, $email, $phone, $address, $avatarPath, $hashed_password, $user_id];
                        } else {
                            $error = 'Password saat ini tidak valid';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Update data user
                    if (!empty($passwordUpdate)) {
                        $stmt_update = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, avatar = ? $passwordUpdate WHERE id = ?");
                        $stmt_update->execute($updateParams);
                    } else {
                        $stmt_update = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, avatar = ? WHERE id = ?");
                        $stmt_update->execute([$name, $email, $phone, $address, $avatarPath, $user_id]);
                    }
                    
                    // Update session
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['avatar'] = $avatarPath;
                    
                    // Simpan notifikasi success di session
                    $_SESSION['profile_success'] = 'Profile berhasil diperbarui' . (!empty($passwordUpdate) ? ' dan password telah diubah' : '');
                    
                    // Redirect untuk menghindari resubmission form
                    header('Location: profile.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Query ulang data user setelah update
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Set default avatar if not exists
if (empty($user['avatar'])) {
    $user['avatar'] = 'https://randomuser.me/api/portraits/men/32.jpg';
} else {
    $user['avatar'] = '../' . $user['avatar'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-light: #ebf5ff;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --border-radius: 12px;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #2c3e50;
            line-height: 1.6;
        }

        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(120deg, #4a6cf7, #6a79f6);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            position: relative;
            overflow: visible;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
            overflow: visible;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
            z-index: 1000;
        }

        .dropdown-menu {
            border-radius: var(--border-radius);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            z-index: 1001;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            color: #495057;
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
        }

        /* Pastikan header tidak memotong dropdown */
        .dashboard-header {
            z-index: 999;
            position: relative;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .welcome-text {
            font-weight: 500;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .user-name {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0.2rem;
        }

        .user-points {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
        }

        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-info h2 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .profile-info p {
            color: #6c757d;
            margin-bottom: 0;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
            margin-bottom: 1rem;
        }

        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-button:hover {
            background-color: #e9ecef;
        }

        .file-name {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .password-section {
            border-top: 1px solid #e9ecef;
            padding-top: 2rem;
            margin-top: 2rem;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(74, 108, 247, 0.4);
        }

        .section-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        @media (max-width: 768px) {
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="dashboard-header" style="background: linear-gradient(120deg, #4a6cf7, #6a79f6); color: white; padding: 1.5rem 0; margin-bottom: 2rem;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="User Avatar" class="user-avatar">
                    <div class="flex-grow-1">
                        <div class="welcome-text">Selamat Datang,</div>
                        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                        <div class="user-points">
                            <i class="bi bi-award-fill"></i>
                            <span><?= $_SESSION['user']['points'] ?> Points</span>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); color: white; padding: 0.6rem 1.2rem; border-radius: 30px;">
                        <i class="bi bi-person-circle"></i>
                        <span>Menu</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuDropdown">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-info">
                            <h2>Edit Profil</h2>
                            <p>Kelola informasi profil Anda untuk mengontrol, melindungi dan mengamankan akun</p>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div id="success-message" data-message="<?= $success ?>" style="display: none;"></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="avatar-upload">
                            <img id="avatarPreview" src="<?= htmlspecialchars($user['avatar']) ?>" alt="Preview Avatar" class="avatar-preview">
                            <div class="file-input-wrapper">
                                <div class="file-input-button">
                                    <i class="bi bi-camera me-1"></i> Pilih Foto
                                </div>
                                <input type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png">
                            </div>
                            <div id="fileName" class="file-name">Format: PNG, JPG, JPEG. Maks: 2MB</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Points</label>
                                <input type="text" class="form-control" value="<?= $user['points'] ?>" disabled>
                                <div class="form-text">Points tidak dapat diubah</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <!-- Password Change Section -->
                        <div class="password-section">
                            <h4 class="section-title">Ubah Password (Opsional)</h4>
                            <p class="text-muted">Isi field berikut hanya jika ingin mengubah password</p>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Minimal 8 karakter</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="dashboard.php" class="btn btn-secondary me-md-2">Kembali</a>
                            <button type="submit" class="btn btn-primary">Update Profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button type="button" class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            const fileName = document.getElementById('fileName');
            
            if (avatarInput) {
                avatarInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        // Check file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Format file tidak didukung. Hanya PNG, JPG, dan JPEG yang diperbolehkan');
                            this.value = '';
                            return;
                        }
                        
                        // Check file size (max 2MB)
                        if (file.size > 2 * 1024 * 1024) {
                            alert('Ukuran file terlalu besar. Maksimal 2MB');
                            this.value = '';
                            return;
                        }
                        
                        // Preview image
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarPreview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                        
                        // Show file name
                        fileName.textContent = file.name;
                    }
                });
            }

            // SweetAlert2 untuk notifikasi sukses
            const successMessage = document.getElementById('success-message');
            if (successMessage && successMessage.dataset.message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: successMessage.dataset.message,
                    confirmButtonColor: '#3498db',
                    timer: 2500,
                    timerProgressBar: true
                });
            }

            // Back to Top functionality
            const backToTopButton = document.getElementById('backToTop');
            
            function toggleBackToTop() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            }
            
            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
            
            window.addEventListener('scroll', toggleBackToTop);
            backToTopButton.addEventListener('click', function(e){
                e.preventDefault();
                scrollToTop();
            });
            toggleBackToTop();

        });
    </script>
</body>
</html>