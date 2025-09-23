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
    SELECT t.*, d.name as destination_name, d.location 
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

// Proses penggunaan tiket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update status tiket menjadi used
    $stmt_update = $pdo->prepare("UPDATE tickets SET used_at = NOW() WHERE id = ?");
    if ($stmt_update->execute([$ticket_id])) {
        $success = "Tiket berhasil digunakan! Terima kasih telah berkunjung.";
        
        // Tambahkan poin reward untuk user
        $points = $ticket['quantity'] * 10; // 10 poin per tiket
        $stmt_points = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt_points->execute([$points, $user_id]);
        
        // Update session user points
        $_SESSION['user']['points'] += $points;
    } else {
        $error = "Gagal menggunakan tiket. Silakan coba lagi.";
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gunakan Tiket - Aplikasi Wisata & UMKM</title>
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

        .use-ticket-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .use-ticket-card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            background: white;
            border: 1px solid var(--gray-200);
        }

        .use-ticket-header {
            background: linear-gradient(120deg, #4a6cf7, #6a79f6);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .use-ticket-body {
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

        .success-animation {
            text-align: center;
            margin: 2rem 0;
        }

        .success-animation i {
            font-size: 4rem;
            color: var(--secondary-color);
            animation: pulse 1.5s infinite;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .use-ticket-container {
                padding: 0 10px;
            }
            
            .use-ticket-body {
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

    <div class="use-ticket-container">
        <div class="use-ticket-card">
            <div class="use-ticket-header">
                <h2><i class="bi bi-ticket-perforated"></i> Gunakan Tiket</h2>
                <p class="mb-0"><?= htmlspecialchars($ticket['destination_name']) ?></p>
            </div>
            <div class="use-ticket-body">
                <?php if (isset($success)): ?>
                    <div class="success-animation">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="alert alert-success text-center">
                        <h4><?= $success ?></h4>
                        <p class="mb-0">Anda mendapatkan <?= $points ?> poin reward!</p>
                    </div>
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
                    </div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
                    </div>
                <?php else: ?>
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
                            <span><?= date('d M Y', strtotime($ticket['visit_date'])) ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <strong>Jumlah Tiket</strong>
                            <span><?= $ticket['quantity'] ?> orang</span>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Informasi Penting:</strong> Pastikan Anda berada di lokasi destinasi sebelum menggunakan tiket ini. 
                        Setelah tiket digunakan, tidak dapat dikembalikan.
                    </div>

                    <form method="POST">
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Konfirmasi Penggunaan Tiket
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button type="button" class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>