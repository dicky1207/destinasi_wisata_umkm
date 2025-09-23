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

// Cek apakah parameter ticket_id ada
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$ticket_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

// Ambil data tiket
$stmt = $pdo->prepare("
    SELECT t.*, d.name as destination_name, d.description, d.location, d.image as destination_image 
    FROM tickets t 
    JOIN destinations d ON t.destination_id = d.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data transaksi terkait tiket ini
$stmt_transaction = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE ticket_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt_transaction->execute([$ticket_id]);
$transaction = $stmt_transaction->fetch();

// Proses upload bukti pembayaran
$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_pembayaran'])) {
    $target_dir = "../uploads/payments/";
    
    // Buat direktori jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES["bukti_pembayaran"]["name"]);
    $target_file = $target_dir . $file_name;
    $upload_ok = 1;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Cek apakah file adalah gambar atau PDF
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($file_type, $allowed_types)) {
        $upload_error = "Hanya file JPG, JPEG, PNG & PDF yang diperbolehkan.";
        $upload_ok = 0;
    }
    
    // Cek ukuran file (maksimal 5MB)
    if ($_FILES["bukti_pembayaran"]["size"] > 1000000) {
        $upload_error = "Ukuran file terlalu besar. Maksimal 1MB.";
        $upload_ok = 0;
    }
    
    // Jika semua syarat terpenuhi, upload file
    if ($upload_ok == 1) {
        if (move_uploaded_file($_FILES["bukti_pembayaran"]["tmp_name"], $target_file)) {
            // Simpan path file ke database
            $stmt_update = $pdo->prepare("
                UPDATE transactions 
                SET bukti_pembayaran = ? 
                WHERE ticket_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            if ($stmt_update->execute([$file_name, $ticket_id])) {
                $upload_success = true;
                // Refresh data transaksi
                $stmt_transaction->execute([$ticket_id]);
                $transaction = $stmt_transaction->fetch();
            } else {
                $upload_error = "Gagal menyimpan informasi bukti pembayaran ke database.";
            }
        } else {
            $upload_error = "Terjadi kesalahan saat mengupload file.";
        }
    }
}

// Format tanggal
function formatDate($date) {
    $months = array(
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    );
    
    $date = new DateTime($date);
    $formatted = $date->format('d F Y');
    return str_replace(array_keys($months), array_values($months), $formatted);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tiket - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-light: #ebf5ff;
            --secondary-color: #2ecc71;
            --secondary-light: #eefff6;
            --accent-color: #e74c3c;
            --warning-color: #f39c12;
            --warning-light: #fef5e7;
            --info-color: #17a2b8;
            --info-light: #e8f4f7;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .dashboard-header {
            background: linear-gradient(120deg, #4a6cf7, #6a79f6);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .ticket-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .ticket-card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            background: white;
            border: 1px solid var(--gray-200);
        }

        .ticket-header {
            background: linear-gradient(120deg, #4a6cf7, #6a79f6);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .ticket-body {
            padding: 2rem;
        }

        .ticket-info {
            margin-bottom: 1.5rem;
        }

        .ticket-info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--gray-300);
        }

        .ticket-info-item:last-child {
            border-bottom: none;
        }
        
        .payment-proof {
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: var(--gray-100);
            border-radius: var(--border-radius);
        }
        
        .payment-proof img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .payment-proof a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .payment-proof a:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .destination-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .upload-form {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: var(--gray-100);
            border-radius: var(--border-radius);
            border: 2px dashed var(--gray-300);
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
        }
        
        .file-input-label {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .file-input-label:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .file-name {
            margin-left: 1rem;
            font-style: italic;
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
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: var(--card-shadow-hover);
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

        @media (max-width: 768px) {
            .ticket-container {
                padding: 0 10px;
            }
            
            .ticket-body {
                padding: 1.5rem;
            }
            
            .ticket-info-item {
                flex-direction: column;
                gap: 0.25rem;
            }
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
    <header class="dashboard-header">
        <div class="container">
            <div class="header-container">
                <div class="user-info">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User Avatar" class="user-avatar">
                    <div class="flex-grow-1">
                        <div class="welcome-text">Selamat Datang,</div>
                        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                        <div class="user-points">
                            <i class="bi bi-award-fill"></i>
                            <span><?= $_SESSION['user']['points'] ?> Points</span>
                        </div>
                    </div>
                </div>
                
                <a href="dashboard.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div class="ticket-container">
        <div class="ticket-card">
            <div class="ticket-header">
                <h2><i class="bi bi-ticket-perforated"></i> Detail Tiket</h2>
                <p class="mb-0"><?= htmlspecialchars($ticket['destination_name']) ?></p>
            </div>
            <div class="ticket-body">
                <?php
                $imagePath = '';
                $altText = htmlspecialchars($ticket['destination_name']);
                
                if (!empty($ticket['destination_image'])) {
                    $imagePath = '../' . htmlspecialchars($ticket['destination_image']);
                } else {
                    $imagePath = 'https://via.placeholder.com/800x400?text=No+Image';
                }
                ?>
                <img src="<?= $imagePath ?>" alt="<?= $altText ?>" class="destination-image">
                
                <div class="ticket-info">
                    <div class="ticket-info-item">
                        <strong>Kode Tiket</strong>
                        <span><?= htmlspecialchars($ticket['code']) ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Destinasi</strong>
                        <span><?= htmlspecialchars($ticket['destination_name']) ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Lokasi</strong>
                        <span><?= htmlspecialchars($ticket['location']) ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Tanggal Kunjungan</strong>
                        <span><?= formatDate($ticket['visit_date']) ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Jumlah Tiket</strong>
                        <span><?= $ticket['quantity'] ?> orang</span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Total Harga</strong>
                        <span>Rp <?= number_format($ticket['total_price'], 0, ',', '.') ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <strong>Status</strong>
                        <span class="badge bg-<?= $ticket['status'] == 'paid' ? 'success' : ($ticket['status'] == 'used' ? 'info' : 'secondary') ?>">
                            <?= $ticket['status'] == 'paid' ? 'Aktif' : ($ticket['status'] == 'used' ? 'Telah Digunakan' : 'Pending') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Bagian Bukti Pembayaran -->
                <div class="payment-proof">
                    <h5>Bukti Pembayaran</h5>
                    
                    <?php if ($transaction && !empty($transaction['bukti_pembayaran'])): ?>
                        <p>Bukti pembayaran telah diupload:</p>
                        
                        <?php
                        $file_extension = strtolower(pathinfo($transaction['bukti_pembayaran'], PATHINFO_EXTENSION));
                        $file_path = '../uploads/payments/' . $transaction['bukti_pembayaran'];
                        ?>
                        
                        <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?= $file_path ?>" alt="Bukti Pembayaran" class="img-fluid">
                        <?php else: ?>
                            <p>File: <?= $transaction['bukti_pembayaran'] ?></p>
                        <?php endif; ?>
                        
                        <a href="<?= $file_path ?>" target="_blank" download class="btn btn-primary mt-2">
                            <i class="bi bi-download"></i> Download Bukti Pembayaran
                        </a>
                    <?php else: ?>
                        <p>Belum ada bukti pembayaran yang diupload.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button type="button" class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Menampilkan nama file yang dipilih
        document.getElementById('bukti_pembayaran').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            document.getElementById('file-name').textContent = fileName;
        });

        // Back to Top functionality
        document.addEventListener('DOMContentLoaded', function() {
            const backToTopButton = document.getElementById('backToTop');
            
            if (!backToTopButton) return;
            
            let isScrolling;
            
            function toggleBackToTop() {
                window.clearTimeout(isScrolling);
                
                isScrolling = setTimeout(function() {
                    if (window.pageYOffset > 300) {
                        backToTopButton.classList.add('show');
                    } else {
                        backToTopButton.classList.remove('show');
                    }
                }, 60);
            }
            
            function scrollToTop() {
                if (window.pageYOffset > 0) {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            }
            
            // Passive event listener for better performance
            window.addEventListener('scroll', toggleBackToTop, { passive: true });
            
            backToTopButton.addEventListener('click', function(e) {
                e.preventDefault();
                scrollToTop();
            });
            
            // Initial state
            toggleBackToTop();
        });
    </script>
</body>
</html>