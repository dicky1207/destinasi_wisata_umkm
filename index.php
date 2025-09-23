<?php
session_start();
require_once 'config/database.php';

// Hapus session user jika mengakses halaman landing page
// Ini memastikan landing page selalu tampil tanpa info login
if (isset($_SESSION['user'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page == 'index.php') {
        // Simpan data user sementara lalu hapus session
        $temp_user = $_SESSION['user'];
        unset($_SESSION['user']);
        
        // Simpan dalam session khusus untuk mengetahui aslinya sudah login
        $_SESSION['landing_page_access'] = true;
        $_SESSION['original_user'] = $temp_user;
    }
}

// Jika kembali dari landing page ke dashboard, restore session
if (isset($_SESSION['landing_page_access']) && $_SESSION['landing_page_access'] && 
    isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) == 'index.php' &&
    isset($_GET['restore_session'])) {
    if (isset($_SESSION['original_user'])) {
        $_SESSION['user'] = $_SESSION['original_user'];
        unset($_SESSION['landing_page_access']);
        unset($_SESSION['original_user']);
        
        // Redirect ke halaman yang sesuai berdasarkan role
        if ($_SESSION['user']['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php'); // Ganti dengan halaman user dashboard jika ada
        }
        exit;
    }
}

// Fungsi pencarian destinasi
$search_destinasi = isset($_GET['search_destinasi']) ? $_GET['search_destinasi'] : '';
$search_umkm = isset($_GET['search_umkm']) ? $_GET['search_umkm'] : '';

// Query untuk destinasi populer
$stmt_destinasi = $pdo->query("SELECT * FROM destinations ORDER BY rating DESC LIMIT 6");
$destinasi_populer = $stmt_destinasi->fetchAll();

// Query untuk UMKM terdekat
$stmt_umkm = $pdo->query("SELECT * FROM umkms ORDER BY rating DESC LIMIT 8");
$umkm_terdekat = $stmt_umkm->fetchAll();

// Query untuk statistik dinamis
$stmt_stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM destinations) as total_destinasi,
        (SELECT COUNT(*) FROM umkms) as total_umkm,
        (SELECT COUNT(*) FROM users) as total_pengguna,
        (SELECT AVG(rating) FROM destinations WHERE rating > 0) as rata_rata_rating
");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Format data statistik
$total_destinasi = $stats['total_destinasi'] ?? 0;
$total_umkm = $stats['total_umkm'] ?? 0;
$total_pengguna = $stats['total_pengguna'] ?? 0;
$rata_rata_rating = number_format($stats['rata_rata_rating'] ?? 0, 1);

// Query untuk semua destinasi dengan pencarian
$limit = 9;
$page_destinasi = isset($_GET['page_destinasi']) ? (int)$_GET['page_destinasi'] : 1;
$offset_destinasi = ($page_destinasi - 1) * $limit;

// Field yang akan dicari untuk destinasi
$destinasi_search_fields = ['name', 'location'];

// Query dasar dengan kondisi pencarian
$sql_all_destinasi = "SELECT * FROM destinations";
$count_sql_destinasi = "SELECT COUNT(*) FROM destinations";

if (!empty($search_destinasi)) {
    $search_conditions = [];
    foreach ($destinasi_search_fields as $index => $field) {
        $param_name = ":search_destinasi_{$index}";
        $search_conditions[] = "{$field} LIKE {$param_name}";
    }
    
    $sql_all_destinasi .= " WHERE " . implode(" OR ", $search_conditions);
    $count_sql_destinasi .= " WHERE " . implode(" OR ", $search_conditions);
}

$sql_all_destinasi .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$stmt_all_destinasi = $pdo->prepare($sql_all_destinasi);
$stmt_total_destinasi = $pdo->prepare($count_sql_destinasi);

if (!empty($search_destinasi)) {
    $search_term_destinasi = "%$search_destinasi%";
    foreach ($destinasi_search_fields as $index => $field) {
        $param_name = ":search_destinasi_{$index}";
        $stmt_all_destinasi->bindValue($param_name, $search_term_destinasi, PDO::PARAM_STR);
        $stmt_total_destinasi->bindValue($param_name, $search_term_destinasi, PDO::PARAM_STR);
    }
}

$stmt_all_destinasi->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_all_destinasi->bindValue(':offset', $offset_destinasi, PDO::PARAM_INT);
$stmt_all_destinasi->execute();
$all_destinasi = $stmt_all_destinasi->fetchAll();

$stmt_total_destinasi->execute();
$total_destinasi = $stmt_total_destinasi->fetchColumn();
$total_pages_destinasi = ceil($total_destinasi / $limit);

// Query untuk semua UMKM dengan pencarian
$page_umkm = isset($_GET['page_umkm']) ? (int)$_GET['page_umkm'] : 1;
$offset_umkm = ($page_umkm - 1) * $limit;

// Field yang akan dicari untuk UMKM
$umkm_search_fields = ['name', 'description'];

// Query dasar dengan kondisi pencarian
$sql_all_umkm = "SELECT * FROM umkms";
$count_sql_umkm = "SELECT COUNT(*) FROM umkms";

if (!empty($search_umkm)) {
    $search_conditions = [];
    foreach ($umkm_search_fields as $index => $field) {
        $param_name = ":search_umkm_{$index}";
        $search_conditions[] = "{$field} LIKE {$param_name}";
    }
    
    $sql_all_umkm .= " WHERE " . implode(" OR ", $search_conditions);
    $count_sql_umkm .= " WHERE " . implode(" OR ", $search_conditions);
}

$sql_all_umkm .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$stmt_all_umkm = $pdo->prepare($sql_all_umkm);
$stmt_total_umkm = $pdo->prepare($count_sql_umkm);

if (!empty($search_umkm)) {
    $search_term_umkm = "%$search_umkm%";
    foreach ($umkm_search_fields as $index => $field) {
        $param_name = ":search_umkm_{$index}";
        $stmt_all_umkm->bindValue($param_name, $search_term_umkm, PDO::PARAM_STR);
        $stmt_total_umkm->bindValue($param_name, $search_term_umkm, PDO::PARAM_STR);
    }
}

$stmt_all_umkm->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_all_umkm->bindValue(':offset', $offset_umkm, PDO::PARAM_INT);
$stmt_all_umkm->execute();
$all_umkm = $stmt_all_umkm->fetchAll();

$stmt_total_umkm->execute();
$total_umkm = $stmt_total_umkm->fetchColumn();
$total_pages_umkm = ceil($total_umkm / $limit);

// Buat fungsi helper untuk menampilkan rating bintang
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

// Tentukan halaman aktif berdasarkan parameter URL
$active_page = 'beranda';
if (isset($_GET['page'])) {
    $active_page = $_GET['page'];
} elseif (isset($_GET['search_destinasi']) || isset($_GET['page_destinasi'])) {
    $active_page = 'destinasi';
} elseif (isset($_GET['search_umkm']) || isset($_GET['page_umkm'])) {
    $active_page = 'umkm';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            overflow-x: hidden;
        }

        /* Navbar Styles - Enhanced */
        .navbar {
            background-color: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.1) !important;
            padding: 15px 0 !important;
        }

        .navbar-brand {
            font-weight: 800 !important;
            font-size: 1.8rem !important;
            font-family: 'Playfair Display', serif !important;
        }

        .nav-link {
            font-weight: 500 !important;
            margin: 0 15px !important;
            position: relative !important;
            font-size: 1.05rem !important;
            color: var(--text-color) !important;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '' !important;
            position: absolute !important;
            bottom: -5px !important;
            left: 0 !important;
            width: 0 !important;
            height: 2px !important;
            background: var(--primary-color) !important;
            transition: var(--transition) !important;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100% !important;
        }

        .btn-primary, .btn-outline-primary {
            border-radius: 10px !important;
            padding: 10px 20px !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)) !important;
            border: none !important;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color) !important;
            color: var(--primary-color) !important;
            background: transparent !important;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color) !important;
            color: white !important;
        }

        .text-gradient {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }

        /* Hero Section - Redesigned */
        .hero-section {
            position: relative;
            color: white;
            padding: 0;
            margin-bottom: 80px;
            overflow: hidden;
            height: 100vh;
            min-height: 700px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translateX(-50%) translateY(-50%);
            object-fit: cover;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(29, 42, 56, 0.85), rgba(74, 108, 247, 0.7));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            padding: 0 20px;
        }

        .hero-title {
            font-weight: 700;
            font-size: 4.5rem;
            margin-bottom: 30px;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1s ease-out;
            font-family: 'Playfair Display', serif;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 40px;
            font-weight: 300;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Animated text */
        .typewriter-container {
            min-height: 80px;
            position: relative;
            margin: 40px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .typewriter {
            position: relative;
            padding: 20px 50px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            display: inline-block;
            min-width: 500px;
            overflow: hidden;
        }

        .typewriter-text {
            font-size: 1.4rem;
            font-weight: 400;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .cursor {
            display: inline-block;
            width: 3px;
            height: 1.2em;
            background-color: white;
            margin-left: 4px;
            animation: blink 1s infinite;
            vertical-align: middle;
        }

        /* Scroll indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0.8;
            transition: var(--transition);
        }

        .scroll-indicator:hover {
            opacity: 1;
        }

        .scroll-text {
            margin-bottom: 10px;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .scroll-arrow {
            width: 24px;
            height: 24px;
            border-right: 2px solid white;
            border-bottom: 2px solid white;
            transform: rotate(45deg);
            animation: bounce 2s infinite;
        }

        /* Section Styles */
        .section {
            padding: 100px 0;
        }

        .section-title {
            position: relative;
            padding-bottom: 20px;
            margin-bottom: 60px;
            font-weight: 700;
            text-align: center;
            font-size: 2.5rem;
            font-family: 'Playfair Display', serif;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        /* Card Styles - Enhanced */
        .destination-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 100%;
            background: white;
            position: relative;
            border: none;
        }

        .destination-card:hover {
            transform: translateY(-12px);
            box-shadow: var(--card-shadow-hover);
        }

        .destination-img-container {
            position: relative;
            overflow: hidden;
            height: 240px;
        }

        .destination-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .destination-card:hover .destination-img {
            transform: scale(1.1);
        }

        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 2;
        }

        .card-body {
            padding: 1.8rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }

        .card-text {
            color: var(--text-muted);
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .rating {
            color: var(--gold);
            margin-bottom: 1.2rem;
        }

        .price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        /* UMKM Card - Enhanced */
        .umkm-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 100%;
            background: white;
            border: none;
        }

        .umkm-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow-hover);
        }

        .umkm-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.7s ease;
        }

        .umkm-card:hover .umkm-img {
            transform: scale(1.1);
        }

        /* Search Form Styles - Improved */
        .search-form {
            margin-bottom: 2rem;
        }

        .search-form .input-group {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .search-form .form-control {
            border: none;
            padding: 12px 20px;
        }

        .search-form .btn {
            padding: 12px 20px;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Newsletter Section */
        .newsletter-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 80px 0;
            text-align: center;
        }

        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
            position: relative;
        }

        .newsletter-input {
            padding: 15px 25px;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            width: 100%;
            font-size: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .newsletter-input:focus {
            outline: none;
            box-shadow: 0 5px 20px rgba(74, 108, 247, 0.2);
            border-color: var(--primary-color);
        }

        .newsletter-btn {
            position: absolute;
            right: 5px;
            top: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            color: white;
            font-weight: 500;
            transition: var(--transition);
        }

        .newsletter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Footer - Enhanced */
        footer {
            background: var(--dark-color);
            color: white;
            padding: 100px 0 40px;
            position: relative;
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
            font-size: 1.4rem;
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
            position: relative;
            padding-left: 0;
        }

        .footer-links a::before {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background: white;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 10px;
        }

        .footer-links a:hover::before {
            width: 100%;
        }

        .footer-map-container {
            margin-top: 10px;
        }

        .footer-map {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            height: 200px;
        }

        .footer-map:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }

        .footer-map iframe {
            border: none;
            width: 100%;
            height: 100%;
            display: block;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-5px);
        }

        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            margin-top: 70px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) rotate(45deg);
            }
            40% {
                transform: translateY(-10px) rotate(45deg);
            }
            60% {
                transform: translateY(-5px) rotate(45deg);
            }
        }

        @keyframes blink {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0;
            }
        }

        @keyframes typewriter {
            from {
                width: 0;
            }
            to {
                width: 100%;
            }
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

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 3.8rem;
            }
        }

        @media (max-width: 992px) {
            .hero-title {
                font-size: 3.2rem;
            }
            .hero-subtitle {
                font-size: 1.3rem;
            }
            .typewriter {
                min-width: 400px;
            }
            .typewriter-text {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-subtitle {
                font-size: 1.1rem;
            }
            .typewriter {
                min-width: 300px;
                padding: 15px 30px;
            }
            .typewriter-text {
                font-size: 1rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 2.5rem;
            }
            .nav-link {
                margin: 0 8px;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
            .typewriter {
                min-width: 250px;
                padding: 12px 20px;
            }
            .section {
                padding: 70px 0;
            }
        }

        @media (max-width: 768px) {
            .footer-map {
                height: 180px;
            }
        }

        @media (max-width: 576px) {
            .footer-map {
                height: 160px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="?page=beranda">
                <i class="bi bi-geo-alt-fill text-gradient me-2"></i>Wisata<strong class="text-gradient">UMKM</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page == 'beranda' ? 'active' : '' ?>" href="?page=beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page == 'destinasi' ? 'active' : '' ?>" href="?page=destinasi">Destinasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_page == 'umkm' ? 'active' : '' ?>" href="?page=umkm">UMKM</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['original_user'])): ?>
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

    <!-- Halaman Beranda -->
    <div id="beranda" class="page" style="display: <?= $active_page == 'beranda' ? 'block' : 'none' ?>;">
        <!-- Hero Section dengan Video Background -->
        <section class="hero-section">
            <div class="hero-video-container">
                <video autoplay muted loop class="hero-video">
                    <source src="https://assets.mixkit.co/videos/preview/mixkit-aerial-view-of-a-tropical-beach-4065-large.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            
            <div class="container text-center hero-content">
                <h1 class="hero-title">Temukan Keindahan <span class="text-gradient">Indonesia</span></h1>
                <p class="hero-subtitle">Jelajahi destinasi wisata menakjubkan dan dukung ekonomi lokal melalui UMKM terbaik di seluruh nusantara</p>
                
                <!-- Typewriter Effect -->
                <div class="typewriter-container">
                    <div class="typewriter">
                        <span class="typewriter-text" id="typewriter"></span>
                        <span class="cursor"></span>
                    </div>
                </div>
            </div>
            
            <!-- Scroll Indicator -->
            <div class="scroll-indicator">
                <span class="scroll-text">Scroll</span>
                <div class="scroll-arrow"></div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $total_destinasi ?>">0</div>
                            <div class="stat-label">Destinasi Wisata</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $total_umkm ?>">0</div>
                            <div class="stat-label">UMKM Terdaftar</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $total_pengguna ?>">0</div>
                            <div class="stat-label">Pengguna Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $rata_rata_rating ?>">0</div>
                            <div class="stat-label">Rating Rata-rata</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Destinasi Populer -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">Destinasi Populer</h2>
                <div class="row g-4">
                    <?php if (count($destinasi_populer) > 0): ?>
                        <?php foreach ($destinasi_populer as $destinasi): ?>
                        <div class="col-md-4 mb-4">
                            <div class="destination-card">
                                <div class="destination-img-container">
                                    <img src="<?= !empty($destinasi['image']) ? htmlspecialchars($destinasi['image']) : 'https://via.placeholder.com/300x200?text=No+Image' ?>" 
                                        class="destination-img" alt="<?= htmlspecialchars($destinasi['name']) ?>">
                                    <div class="category-badge"><?= htmlspecialchars($destinasi['category']) ?></div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($destinasi['name']) ?></h5>
                                        <span class="rating">
                                            <?php
                                            $fullStars = floor($destinasi['rating']);
                                            $halfStar = (($destinasi['rating'] - $fullStars) >= 0.5) ? 1 : 0;
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
                                            <span class="text-muted ms-1"><?= number_format($destinasi['rating'], 1) ?></span>
                                        </span>
                                    </div>
                                    <p class="card-text"><?= htmlspecialchars($destinasi['location']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="price">Rp <?= number_format($destinasi['price'], 0, ',', '.') ?></span>
                                        <a href="destination_detail.php?id=<?= $destinasi['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-data">
                                <i class="bi bi-geo-alt"></i>
                                <h4>Belum ada destinasi wisata</h4>
                                <p>Admin belum menambahkan destinasi wisata.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-5">
                    <a href="?page=destinasi" class="btn btn-outline-primary">Lihat Semua Destinasi</a>
                </div>
            </div>
        </section>

        <!-- UMKM Terdekat -->
        <section class="section bg-light">
            <div class="container">
                <h2 class="section-title">UMKM Terdekat</h2>
                <div class="row g-4">
                    <?php if (count($umkm_terdekat) > 0): ?>
                        <?php foreach ($umkm_terdekat as $umkm): ?>
                        <div class="col-md-3 mb-4">
                            <div class="umkm-card">
                                <img src="<?= !empty($umkm['image']) ? htmlspecialchars($umkm['image']) : 'https://via.placeholder.com/300x200?text=No+Image' ?>" 
                                    class="umkm-img" alt="<?= htmlspecialchars($umkm['name']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($umkm['name']) ?></h5>
                                    <div class="rating mb-2">
                                        <?php
                                        $fullStars = floor($umkm['rating']);
                                        $halfStar = (($umkm['rating'] - $fullStars) >= 0.5) ? 1 : 0;
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
                                        <span class="text-muted ms-1"><?= number_format($umkm['rating'], 1) ?></span>
                                    </div>
                                    <p class="card-text text-muted small"><?= !empty($umkm['description']) ? (strlen($umkm['description']) > 100 ? substr(htmlspecialchars($umkm['description']), 0, 100) . '...' : htmlspecialchars($umkm['description'])) : 'Tidak ada deskripsi' ?></p>
                                    <a href="umkm_detail.php?id=<?= $umkm['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">Kunjungi</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-data">
                                <i class="bi bi-shop"></i>
                                <h4>Belum ada UMKM</h4>
                                <p>Admin belum menambahkan UMKM.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-5">
                    <a href="?page=umkm" class="btn btn-outline-primary">Lihat Semua UMKM</a>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter-section">
            <div class="container">
                <h2 class="section-title">Tetap Terhubung</h2>
                <p class="text-center text-muted mb-5">Berlangganan newsletter kami untuk mendapatkan update terbaru tentang destinasi wisata dan UMKM</p>
                
                <form class="newsletter-form">
                    <input type="email" class="newsletter-input" placeholder="Masukkan email Anda">
                    <button type="submit" class="newsletter-btn">Berlangganan</button>
                </form>
            </div>
        </section>
    </div>

    <!-- Halaman Destinasi -->
    <div id="destinasi" class="page" style="display: <?= $active_page == 'destinasi' ? 'block' : 'none' ?>;">
        <div class="section">
            <div class="container">
                <h2 class="section-title">Semua Destinasi Wisata</h2>
                <p class="text-muted text-center mb-5">Temukan berbagai destinasi wisata menarik di seluruh Indonesia</p>

                <div class="row justify-content-center mb-5">
                    <div class="col-md-6 mb-3">
                        <form method="GET" action="" class="search-form">
                            <input type="hidden" name="page" value="destinasi">
                            <input type="hidden" name="page_destinasi" value="1">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search_destinasi" placeholder="Cari destinasi..." value="<?= htmlspecialchars($search_destinasi) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search me-1"></i>Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-4">
                    <?php if (count($all_destinasi) > 0): ?>
                        <?php foreach ($all_destinasi as $destinasi): ?>
                        <div class="col-md-4 mb-4">
                            <div class="destination-card">
                                <div class="destination-img-container">
                                    <img src="<?= !empty($destinasi['image']) ? htmlspecialchars($destinasi['image']) : 'https://via.placeholder.com/300x200?text=No+Image' ?>" 
                                        class="destination-img" alt="<?= htmlspecialchars($destinasi['name']) ?>">
                                    <div class="category-badge"><?= htmlspecialchars($destinasi['category']) ?></div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($destinasi['name']) ?></h5>
                                        <span class="rating">
                                            <?php
                                            $fullStars = floor($destinasi['rating']);
                                            $halfStar = (($destinasi['rating'] - $fullStars) >= 0.5) ? 1 : 0;
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
                                            <span class="text-muted ms-1"><?= number_format($destinasi['rating'], 1) ?></span>
                                        </span>
                                    </div>
                                    <p class="card-text"><?= htmlspecialchars($destinasi['location']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="price">Rp <?= number_format($destinasi['price'], 0, ',', '.') ?></span>
                                        <a href="destination_detail.php?id=<?= $destinasi['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-data text-center py-5">
                                <i class="bi bi-geo-alt display-1 text-muted"></i>
                                <h4 class="mt-3">Belum ada destinasi wisata</h4>
                                <p class="text-muted">Admin belum menambahkan destinasi wisata.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages_destinasi > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page_destinasi <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=destinasi&page_destinasi=<?= $page_destinasi - 1 ?>&search_destinasi=<?= urlencode($search_destinasi) ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages_destinasi; $i++): ?>
                        <li class="page-item <?= $i == $page_destinasi ? 'active' : '' ?>">
                            <a class="page-link" href="?page=destinasi&page_destinasi=<?= $i ?>&search_destinasi=<?= urlencode($search_destinasi) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page_destinasi >= $total_pages_destinasi ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=destinasi&page_destinasi=<?= $page_destinasi + 1 ?>&search_destinasi=<?= urlencode($search_destinasi) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Halaman UMKM -->
    <div id="umkm" class="page" style="display: <?= $active_page == 'umkm' ? 'block' : 'none' ?>;">
        <div class="section">
            <div class="container">
                <h2 class="section-title">Semua UMKM</h2>
                <p class="text-muted text-center mb-5">Dukung usaha mikro, kecil, dan menengah di sekitar destinasi wisata</p>

                <div class="row justify-content-center mb-5">
                    <div class="col-md-6 mb-3">
                        <form method="GET" action="" class="search-form">
                            <input type="hidden" name="page" value="umkm">
                            <input type="hidden" name="page_umkm" value="1">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search_umkm" placeholder="Cari UMKM..." value="<?= htmlspecialchars($search_umkm) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search me-1"></i>Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-4">
                    <?php if (count($all_umkm) > 0): ?>
                        <?php foreach ($all_umkm as $umkm): ?>
                        <div class="col-md-4 mb-4">
                            <div class="umkm-card">
                                <img src="<?= !empty($umkm['image']) ? htmlspecialchars($umkm['image']) : 'https://via.placeholder.com/300x200?text=No+Image' ?>" 
                                    class="umkm-img" alt="<?= htmlspecialchars($umkm['name']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($umkm['name']) ?></h5>
                                    <div class="rating mb-2">
                                        <?php
                                        $fullStars = floor($umkm['rating']);
                                        $halfStar = (($umkm['rating'] - $fullStars) >= 0.5) ? 1 : 0;
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
                                        <span class="text-muted ms-1"><?= number_format($umkm['rating'], 1) ?></span>
                                    </div>
                                    <p class="card-text text-muted small"><?= !empty($umkm['description']) ? (strlen($umkm['description']) > 100 ? substr(htmlspecialchars($umkm['description']), 0, 100) . '...' : htmlspecialchars($umkm['description'])) : 'Tidak ada deskripsi' ?></p>
                                    <a href="umkm_detail.php?id=<?= $umkm['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">Kunjungi</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-data text-center py-5">
                                <i class="bi bi-shop display-1 text-muted"></i>
                                <h4 class="mt-3">Belum ada UMKM</h4>
                                <p class="text-muted">Admin belum menambahkan UMKM.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages_umkm > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page_umkm <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=umkm&page_umkm=<?= $page_umkm - 1 ?>&search_umkm=<?= urlencode($search_umkm) ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages_umkm; $i++): ?>
                        <li class="page-item <?= $i == $page_umkm ? 'active' : '' ?>">
                            <a class="page-link" href="?page=umkm&page_umkm=<?= $i ?>&search_umkm=<?= urlencode($search_umkm) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page_umkm >= $total_pages_umkm ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=umkm&page_umkm=<?= $page_umkm + 1 ?>&search_umkm=<?= urlencode($search_umkm) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 mb-5">
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
                        <li><a href="?page=beranda">Beranda</a></li>
                        <li><a href="?page=destinasi">Destinasi</a></li>
                        <li><a href="?page=umkm">UMKM</a></li>
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
                <div class="col-lg-4 col-md-6 mb-5">
                    <h5 class="footer-title">Lokasi Kami</h5>
                    <div class="footer-map-container">
                        <div class="footer-map">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127389.73553205006!2d102.22217615366425!3d-3.825341955555865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e36b01e37e39279%3A0xa079b576e790a6ea!2sBengkulu%2C%20Kota%20Bengkulu%2C%20Bengkulu!5e0!3m2!1sid!2sid!4v1758613744741!5m2!1sid!2sid" 
                                width="100%" 
                                height="200" 
                                style="border:0; border-radius: 8px;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Peta Lokasi WisataUMKM di Bengkulu">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p class="mb-0">&copy; 2025 WisataUMKM. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Typewriter effect
            const texts = [
                "Temukan pengalaman wisata tak terlupakan...",
                "Dukung ekonomi lokal melalui UMKM terbaik...",
                "Jelajahi keindahan nusantara yang mempesona..."
            ];
            let textIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            let typingSpeed = 100;
            
            function typeWriter() {
                const currentText = texts[textIndex];
                const typewriterElement = document.getElementById('typewriter');
                
                if (isDeleting) {
                    // Deleting text
                    typewriterElement.textContent = currentText.substring(0, charIndex - 1);
                    charIndex--;
                    typingSpeed = 50;
                } else {
                    // Writing text
                    typewriterElement.textContent = currentText.substring(0, charIndex + 1);
                    charIndex++;
                    typingSpeed = 100;
                }
                
                // Check if current text is complete
                if (!isDeleting && charIndex === currentText.length) {
                    isDeleting = true;
                    typingSpeed = 1000; // Pause at the end of text
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    textIndex = (textIndex + 1) % texts.length;
                    typingSpeed = 500; // Pause before starting next text
                }
                
                setTimeout(typeWriter, typingSpeed);
            }
            
            // Start typewriter effect
            typeWriter();

            // Animated counter for stats
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;
            
            function animateCounter() {
                counters.forEach(counter => {
                    const target = +counter.getAttribute('data-count');
                    const count = +counter.innerText;
                    const increment = Math.ceil(target / speed);
                    
                    if (count < target) {
                        counter.innerText = Math.min(count + increment, target);
                        setTimeout(animateCounter, 1);
                    }
                });
            }
            
            // Start counter animation when stats section is in view
            const statsSection = document.querySelector('.stats-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            observer.observe(statsSection);

            // Smooth scrolling for navigation
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Scroll to content when scroll indicator is clicked
            document.querySelector('.scroll-indicator').addEventListener('click', function() {
                document.querySelector('.stats-section').scrollIntoView({
                    behavior: 'smooth'
                });
            });

            // Handle broken images
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('error', function() {
                    this.src = 'https://via.placeholder.com/300x200?text=Image+Not+Found';
                    this.alt = 'Gambar tidak ditemukan';
                });
            });

            // Parallax effect on scroll
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const heroSection = document.querySelector('.hero-section');
                if (heroSection) {
                    heroSection.style.backgroundPosition = `center ${scrolled * 0.4}px`;
                }
            });

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
            
            // Event listeners
            window.addEventListener('scroll', toggleBackToTop);
            backToTopButton.addEventListener('click', scrollToTop);
            
            // Initialize on page load
            toggleBackToTop();
            });
    </script>
</body>
</html>