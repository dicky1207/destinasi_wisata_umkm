<?php
session_start();
require_once 'config/database.php';

// Pulihkan session user jika ada session original_user (dari index.php)
if (isset($_SESSION['landing_page_access']) && $_SESSION['landing_page_access'] && isset($_SESSION['original_user'])) {
    $_SESSION['user'] = $_SESSION['original_user'];
    unset($_SESSION['landing_page_access']);
    unset($_SESSION['original_user']);
}

// Jika kembali dari landing page ke dashboard, restore session
if (isset($_GET['restore_session']) && $_GET['restore_session'] == 1) {
    if (isset($_SESSION['user'])) {
        // Redirect ke halaman yang sesuai berdasarkan role
        if ($_SESSION['user']['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit;
    }
}

// Periksa apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$umkm_id = $_GET['id'];

// Query untuk mendapatkan detail UMKM
$stmt = $pdo->prepare("SELECT * FROM umkms WHERE id = ?");
$stmt->execute([$umkm_id]);
$umkm = $stmt->fetch();

// Jika UMKM tidak ditemukan, redirect ke index
if (!$umkm) {
    header('Location: index.php');
    exit();
}

function displayRating($rating) {
    $fullStars = floor($rating);
    $halfStar = (($rating - $fullStars) >= 0.5) ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    
    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="bi bi-star-half"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="bi bi-star"></i>';
    }
    return $html;
}

// Query untuk mendapatkan ulasan UMKM
$stmt_reviews = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.umkm_id = ? AND r.status = 'active'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt_reviews->execute([$umkm_id]);
$reviews = $stmt_reviews->fetchAll();

// Hitung rating rata-rata
$stmt_avg_rating = $pdo->prepare("
    SELECT AVG(rating) as avg_rating 
    FROM reviews 
    WHERE umkm_id = ? AND status = 'active'
");
$stmt_avg_rating->execute([$umkm_id]);
$avg_rating = $stmt_avg_rating->fetchColumn() ?: 0;


// Cek apakah user sudah memberikan review untuk UMKM ini
$can_review = false;
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt_check_review = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reviews 
        WHERE user_id = ? AND umkm_id = ?
    ");
    $stmt_check_review->execute([$user_id, $umkm_id]);
    $has_reviewed = $stmt_check_review->fetchColumn() > 0;
    $can_review = !$has_reviewed;
}

// Query untuk mendapatkan destinasi terdekat
$stmt_destinations = $pdo->prepare("
    SELECT * FROM destinations 
    WHERE category = ? 
    ORDER BY rating DESC 
    LIMIT 4
");
$stmt_destinations->execute([$umkm['category']]);
$nearby_destinations = $stmt_destinations->fetchAll();

// Cek apakah item sudah ada di wishlist user
$is_in_wishlist = false;
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt_wishlist = $pdo->prepare("
        SELECT COUNT(*) 
        FROM wishlists 
        WHERE user_id = ? AND item_id = ? AND item_type = 'umkm'
    ");
    $stmt_wishlist->execute([$user_id, $umkm_id]);
    $is_in_wishlist = $stmt_wishlist->fetchColumn() > 0;
}

// Tentukan halaman aktif untuk navbar
$active_page = 'umkm';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($umkm['name']) ?> - Detail UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6a79f6;
            --accent-color: #e74c3c;
            --dark-color: #1d2a38;
            --light-color: #f8fafc;
            --text-color: #333;
            --text-muted: #6c757d;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s ease;
            --gold: #D4AF37;
            --light-gold: #F1E5AC;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Navbar Styles - Enhanced */
        .navbar {
            background-color: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            transition: var(--transition);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            font-family: 'Playfair Display', serif;
        }

        .nav-link {
            font-weight: 500;
            margin: 0 15px;
            position: relative;
            transition: var(--transition);
            font-size: 1.05rem;
            color: var(--text-color) !important;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        /* Hero Section */
        .detail-hero {
            height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .detail-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(29, 42, 56, 0.7), rgba(74, 108, 247, 0.5));
        }

        .hero-content {
            position: absolute;
            bottom: 0;
            left: 0;
            padding: 40px;
            color: white;
            width: 100%;
        }

        .rating {
            color: var(--gold);
        }

        /* Card Styles */
        .detail-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            transition: var(--transition);
            border: none;
        }

        .detail-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-5px);
        }

        .contact-card {
            position: sticky;
            top: 120px;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            border: none;
        }

        .review-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
            border: none;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .destination-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 100%;
            border: none;
        }

        .destination-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
        }

        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 1.8rem;
            font-family: 'Playfair Display', serif;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 108, 247, 0.3);
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 10px;
            padding: 10px 23px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 108, 247, 0.3);
        }

        /* Wishlist Button */
        .wishlist-btn-container {
            margin-top: 1.5rem;
        }
        
        .btn-wishlist {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 10px;
        }
        
        .btn-wishlist.added {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-wishlist.added:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        /* Footer */
        footer {
            background: var(--dark-color);
            color: white;
            padding: 80px 0 30px;
            position: relative;
            margin-top: 80px;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .footer-title {
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 1.2rem;
            font-family: 'Playfair Display', serif;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }

        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 50px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Utility */
        .text-gradient {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .detail-hero {
                height: 350px;
            }
            
            .hero-content {
                padding: 20px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .nav-link {
                margin: 0 8px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-geo-alt-fill text-gradient me-2"></i>Wisata<strong class="text-gradient">UMKM</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=destinasi">Destinasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=umkm">UMKM</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user'])): ?>
                    <a href="?restore_session=1" class="btn btn-primary me-2">
                        <i class="bi bi-person-bounding-box me-1"></i>Kembali ke Dashboard
                    </a>
                    <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="detail-hero" style="background-image: url('<?= !empty($umkm['image']) ? htmlspecialchars($umkm['image']) : 'https://via.placeholder.com/1200x400?text=No+Image' ?>');">
        <div class="hero-content">
            <h1 class="display-4 fw-bold"><?= htmlspecialchars($umkm['name']) ?></h1>
            <div class="d-flex align-items-center flex-wrap">
                <span class="badge bg-success me-3 mb-2 fs-6"><?= htmlspecialchars($umkm['category']) ?></span>
                <span class="rating fs-5 mb-2">
                    <?php
                    $fullStars = floor($avg_rating);
                    $halfStar = (($avg_rating - $fullStars) >= 0.5) ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    
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
                    <span class="ms-1 text-white">(<?= number_format($avg_rating, 1) ?>)</span>
                </span>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div class="row">
            <!-- Informasi UMKM -->
            <div class="col-lg-8">
                <div class="detail-card">
                    <h2 class="section-title">Tentang UMKM</h2>
                    <p class="mb-4 fs-6"><?= nl2br(htmlspecialchars($umkm['description'])) ?></p>
                    
                    <h4 class="section-title">Lokasi</h4>
                    <div class="ratio ratio-16x9 mb-4">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3981.0584332535973!2d102.26348307450296!3d-3.797430343526186!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e36b022abf277f1%3A0xf14a10374e488cef!2sBund.%20Simpang%20Lima%2C%20Kec.%20Ratu%20Samban%2C%20Kota%20Bengkulu%2C%20Bengkulu%2038222!5e0!3m2!1sid!2sid!4v1758202741855!5m2!1sid!2sid" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                
                <!-- Ulasan Pelanggan -->
                <div class="detail-card">
                    <h2 class="section-title">Ulasan Pelanggan</h2>
                    
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <span class="text-white fw-bold"><?= strtoupper(substr($review['user_name'], 0, 1)) ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0"><?= htmlspecialchars($review['user_name']) ?></h5>
                                    <div class="rating">
                                        <?php
                                        $fullStars = floor($review['rating']);
                                        $halfStar = (($review['rating'] - $fullStars) >= 0.5) ? 1 : 0;
                                        $emptyStars = 5 - $fullStars - $halfStar;
                                        
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
                                    </div>
                                </div>
                                <small class="text-muted"><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Belum ada ulasan untuk UMKM ini.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Ulasan -->
                <?php if (isset($_SESSION['user']) && $can_review): ?>
                <div class="detail-card">
                    <h4 class="section-title">Beri Ulasan</h4>
                    <form action="submit_review.php" method="POST">
                        <input type="hidden" name="item_type" value="umkm">
                        <input type="hidden" name="item_id" value="<?= $umkm_id ?>">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="5">5 - Sangat Baik</option>
                                <option value="4">4 - Baik</option>
                                <option value="3">3 - Cukup</option>
                                <option value="2">2 - Kurang</option>
                                <option value="1">1 - Buruk</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Komentar</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Destinasi Terdekat -->
                <?php if (count($nearby_destinations) > 0): ?>
                <div class="detail-card">
                    <h2 class="section-title">Destinasi Terdekat</h2>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($nearby_destinations as $destination): ?>
                        <div class="col">
                            <div class="destination-card">
                                <div class="row g-0 h-100">
                                    <div class="col-4">
                                        <img src="<?= !empty($destination['image']) ? htmlspecialchars($destination['image']) : 'https://via.placeholder.com/100x100?text=No+Image' ?>" 
                                            alt="<?= htmlspecialchars($destination['name']) ?>" 
                                            class="img-fluid rounded-start h-100 w-100" style="object-fit: cover;">
                                    </div>
                                    <div class="col-8">
                                        <div class="card-body d-flex flex-column h-100 py-3">
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($destination['name']) ?></h6>
                                            <div class="rating small mb-2">
                                                <?php
                                                $fullStars = floor($destination['rating']);
                                                $halfStar = (($destination['rating'] - $fullStars) >= 0.5) ? 1 : 0;
                                                $emptyStars = 5 - $fullStars - $halfStar;
                                                
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
                                                <span class="text-muted ms-1"><?= number_format($destination['rating'], 1) ?></span>
                                            </div>
                                            <div class="mt-auto">
                                                <a href="destination_detail.php?id=<?= $destination['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Kontak & Info Card -->
            <div class="col-lg-4">
                <div class="contact-card">
                    <h3 class="mb-4">Kontak & Informasi</h3>
                    
                    <div class="mb-4">
                        <h5 class="mb-3">Jam Operasional</h5>
                        <table class="table table-sm">
                            <?php
                            $operational_hours = explode("\n", $umkm['operational_hours']);
                            foreach ($operational_hours as $hour):
                                if (!empty(trim($hour))):
                                $parts = explode(':', $hour, 2);
                                $day = trim($parts[0] ?? '');
                                $time = trim($parts[1] ?? '');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($day) ?></td>
                                <td><?= htmlspecialchars($time) ?></td>
                            </tr>
                            <?php endif; endforeach; ?>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">Kontak</h5>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-whatsapp text-success me-2"></i>
                            <span><?= htmlspecialchars($umkm['contact_phone']) ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-envelope text-primary me-2"></i>
                            <span><?= htmlspecialchars($umkm['contact_email']) ?></span>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="https://api.whatsapp.com/send/?phone=<?= htmlspecialchars($umkm['contact_phone']) ?>&text&app_absent=0" class="btn btn-success" target="_blank">
                            <i class="bi bi-whatsapp me-2"></i>Hubungi via WhatsApp
                        </a>
                    </div>

                    <!-- Wishlist Button -->
                    <div class="wishlist-btn-container mt-3">
                        <?php if (isset($_SESSION['user'])): ?>
                            <button class="btn <?= $is_in_wishlist ? 'btn-success added' : 'btn-outline-primary' ?> btn-wishlist" 
                                    data-id="<?= $umkm['id'] ?>" 
                                    data-type="umkm"
                                    <?= $is_in_wishlist ? 'disabled' : '' ?>>
                                <i class="bi bi-heart<?= $is_in_wishlist ? '-fill' : '' ?>"></i> 
                                <?= $is_in_wishlist ? 'Dalam Wishlist' : 'Tambah ke Wishlist' ?>
                            </button>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-outline-primary btn-wishlist">
                                <i class="bi bi-heart"></i> Login untuk Menambah Wishlist
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Bagikan UMKM Ini</h5>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5">
                    <h5 class="footer-title">Wisata<strong class="text-gradient">UMKM</strong></h5>
                    <p class="mb-4">Platform untuk menemukan destinasi wisata terbaik dan mendukung UMKM lokal di sekitarnya.</p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-5">
                    <h5 class="footer-title">Tautan</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="index.php?page=destinasi">Destinasi</a></li>
                        <li><a href="index.php?page=umkm">UMKM</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-5">
                    <h5 class="footer-title">Kontak</h5>
                    <ul class="footer-links">
                        <li><i class="bi bi-geo-alt me-2"></i> Bengkulu, Indonesia</li>
                        <li><i class="bi bi-envelope me-2"></i> info@wisataumkm.com</li>
                        <li><i class="bi bi-phone me-2"></i> +62 123 456 7890</li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-5">
                    <h5 class="footer-title">Jam Operasional</h5>
                    <ul class="footer-links">
                        <li>Senin - Jumat: 9:00 - 18:00</li>
                        <li>Sabtu: 10:00 - 16:00</li>
                        <li>Minggu: Tutup</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p class="mb-0">&copy; 2025 WisataUMKM. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Fungsi untuk menampilkan notifikasi
        function showNotification(message, type) {
            // Buat elemen div untuk notifikasi
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            // Hapus notifikasi setelah 5 detik
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        // Script untuk menambahkan ke wishlist
        document.querySelectorAll('.btn-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                // Jika tombol sudah disabled (sudah di wishlist), tidak perlu melakukan apa-apa
                if (this.disabled) return;
                
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                
                fetch('wishlist_add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&type=${type}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Berhasil ditambahkan ke wishlist', 'success');
                        // Perbarui tampilan tombol
                        this.innerHTML = '<i class="bi bi-heart-fill"></i> Dalam Wishlist';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success', 'added');
                        this.disabled = true;
                    } else if (data.message.includes('sudah ada')) {
                        showNotification('Item sudah ada di wishlist', 'info');
                        // Perbarui tampilan tombol
                        this.innerHTML = '<i class="bi bi-heart-fill"></i> Dalam Wishlist';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success', 'added');
                        this.disabled = true;
                    } else {
                        showNotification('Gagal menambahkan: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan: ' + error.message, 'error');
                });
            });
        });

        // Handle broken images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'https://via.placeholder.com/300x200?text=Image+Not+Found';
                this.alt = 'Gambar tidak ditemukan';
            });
        });
    </script>
</body>
</html>