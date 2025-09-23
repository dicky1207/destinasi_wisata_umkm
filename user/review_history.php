<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
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

// Query untuk mendapatkan semua review user
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        d.name as destination_name,
        d.image as destination_image,
        u.name as umkm_name,
        u.image as umkm_image,
        CASE 
            WHEN r.destination_id IS NOT NULL THEN 'destination'
            WHEN r.umkm_id IS NOT NULL THEN 'umkm'
        END as item_type
    FROM reviews r
    LEFT JOIN destinations d ON r.destination_id = d.id
    LEFT JOIN umkms u ON r.umkm_id = u.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

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
    <title>Riwayat Review - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-light: #ebf5ff;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #17a2b8;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-600: #6c757d;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
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

        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(120deg, #4a6cf7, #6a79f6);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            position: relative;
            overflow: hidden;
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

        .review-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .review-item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .review-item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .star-rating {
            color: #f39c12;
            font-size: 1.1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
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
            transform: translateY(-3px) scale(1.05);
            transition: transform 0.2s ease;
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
        <?php if (count($reviews) > 0): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <h5 class="mb-0">
                                    <?= $review['item_type'] == 'destination' ? 
                                        htmlspecialchars($review['destination_name']) : 
                                        htmlspecialchars($review['umkm_name']) ?>
                                </h5>
                                <span class="text-muted"><?= formatDate($review['created_at']) ?></span>
                            </div>
                            
                            <div class="review-item-info">
                                <?php 
                                $imagePath = '';
                                $altText = '';
                                
                                if ($review['item_type'] == 'destination' && !empty($review['destination_image'])) {
                                    $imagePath = '../' . htmlspecialchars($review['destination_image']);
                                    $altText = htmlspecialchars($review['destination_name']);
                                } else if ($review['item_type'] == 'umkm' && !empty($review['umkm_image'])) {
                                    $imagePath = '../' . htmlspecialchars($review['umkm_image']);
                                    $altText = htmlspecialchars($review['umkm_name']);
                                } else {
                                    $imagePath = 'https://via.placeholder.com/80x80?text=No+Image';
                                    $altText = 'Tidak ada gambar';
                                }
                                ?>
                                
                                <img src="<?= $imagePath ?>" alt="<?= $altText ?>" class="review-item-image">
                                
                                <div class="flex-grow-1">
                                    <div class="star-rating mb-2">
                                        <?php
                                        $fullStars = floor($review['rating']);
                                        $halfStar = ($review['rating'] - $fullStars) >= 0.5;
                                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                        
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="bi bi-star-fill"></i>';
                                        }
                                        if ($halfStar) {
                                            echo '<i class="bi bi-star-half"></i>';
                                        }
                                        for ($i = 0; $i < $emptyStars; $i++) {
                                            echo '<i class="bi bi-star"></i>';
                                        }
                                        ?>
                                        <span class="text-muted ms-1">(<?= number_format($review['rating'], 1) ?>)</span>
                                    </div>
                                    
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <span class="badge bg-<?= $review['status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= $review['status'] == 'active' ? 'Ditampilkan' : 'Disembunyikan' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-chat-square-text"></i>
                <h4>Belum ada review</h4>
                <p>Anda belum memberikan review untuk destinasi atau UMKM manapun.</p>
                <a href="../index.php" class="btn btn-primary mt-2">Jelajahi Sekarang</a>
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