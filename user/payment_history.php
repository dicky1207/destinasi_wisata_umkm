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

// Query untuk data user
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Set default avatar if not exists
if (empty($user['avatar'])) {
    $user['avatar'] = 'https://randomuser.me/api/portraits/men/32.jpg';
} else {
    $user['avatar'] = '../' . $user['avatar'];
}

// Query untuk mengambil data transaksi user
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        tr.payment_method,
        tr.status as transaction_status,
        tr.amount,
        tr.bukti_pembayaran,
        tr.created_at as transaction_date,
        d.name as destination_name,
        d.image as destination_image
    FROM transactions tr
    JOIN tickets t ON tr.ticket_id = t.id
    JOIN destinations d ON t.destination_id = d.id
    WHERE t.user_id = ?
    ORDER BY tr.created_at DESC
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

// Fungsi untuk format tanggal Indonesia
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
    <title>Riwayat Pembayaran - Aplikasi Wisata & UMKM</title>
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

        .transaction-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .transaction-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .transaction-title {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .transaction-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-success {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }

        .status-pending {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }

        .status-failed {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .transaction-content {
            display: flex;
            gap: 1.25rem;
        }

        .transaction-image {
            width: 100px;
            height: 100px;
            border-radius: var(--border-radius);
            object-fit: cover;
            flex-shrink: 0;
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-info {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .transaction-amount {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            margin-bottom: 0;
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

        @media (max-width: 768px) {
            .transaction-content {
                flex-direction: column;
            }

            .transaction-image {
                width: 100%;
                height: 160px;
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
                
                <a href="dashboard.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <h2 class="mb-4">Riwayat Pembayaran</h2>
        
        <?php if (count($transactions) > 0): ?>
            <?php foreach ($transactions as $transaction): 
                // Tentukan path gambar yang benar
                $imagePath = '';
                $altText = htmlspecialchars($transaction['destination_name']);
                
                if (!empty($transaction['destination_image'])) {
                    $imagePath = '../' . htmlspecialchars($transaction['destination_image']);
                } else {
                    // Gambar placeholder jika tidak ada gambar
                    $imagePath = 'https://via.placeholder.com/100x100?text=No+Image';
                }
            ?>
            <div class="transaction-card">
                <div class="transaction-header">
                    <h5 class="transaction-title"><?= htmlspecialchars($transaction['destination_name']) ?></h5>
                    <span class="transaction-status status-<?= $transaction['transaction_status'] ?>">
                        <?= ucfirst($transaction['transaction_status']) ?>
                    </span>
                </div>
                <div class="transaction-content">
                    <img src="<?= $imagePath ?>" alt="<?= $altText ?>" class="transaction-image">
                    <div class="transaction-details">
                        <div class="transaction-info">
                            <i class="bi bi-calendar"></i> <?= formatDate($transaction['visit_date']) ?>
                        </div>
                        <div class="transaction-info">
                            <i class="bi bi-people"></i> <?= $transaction['quantity'] ?> Tiket
                        </div>
                        <div class="transaction-info">
                            <i class="bi bi-upc-scan"></i> <?= htmlspecialchars($transaction['code']) ?>
                        </div>
                        <div class="transaction-info">
                            <i class="bi bi-credit-card"></i> Metode: <?= ucfirst($transaction['payment_method']) ?>
                        </div>
                        <div class="transaction-info">
                            <i class="bi bi-clock"></i> <?= formatDate($transaction['transaction_date']) ?>
                        </div>
                        <div class="transaction-amount">
                            Total: Rp <?= number_format($transaction['amount'], 0, ',', '.') ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-credit-card"></i>
                <p>Belum ada riwayat pembayaran.</p>
            </div>
        <?php endif; ?>
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