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

// Query untuk statistik tiket aktif
$stmt_tiket_aktif = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE user_id = ? AND status = 'paid' AND visit_date >= CURDATE()");
$stmt_tiket_aktif->execute([$user_id]);
$tiket_aktif = $stmt_tiket_aktif->fetch()['count'];

// Query untuk statistik kunjungan
$stmt_kunjungan = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE user_id = ? AND (used_at IS NOT NULL OR (status = 'paid' AND visit_date < CURDATE()))");
$stmt_kunjungan->execute([$user_id]);
$kunjungan = $stmt_kunjungan->fetch()['count'];

// Query untuk statistik wishlist
$stmt_wishlist = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
$stmt_wishlist->execute([$user_id]);
$wishlist_count = $stmt_wishlist->fetch()['count'];

// Query untuk statistik review
$stmt_review = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
$stmt_review->execute([$user_id]);
$review = $stmt_review->fetch()['count'];

// Query untuk tiket aktif user (baik paid maupun pending)
$stmt_tiket = $pdo->prepare("
    SELECT t.*, d.name as destination_name, d.image as destination_image 
    FROM tickets t 
    JOIN destinations d ON t.destination_id = d.id 
    WHERE t.user_id = ? AND (t.status = 'paid' OR t.status = 'pending') AND t.visit_date >= CURDATE() AND t.used_at IS NULL
    ORDER BY t.visit_date ASC
");
$stmt_tiket->execute([$user_id]);
$tickets = $stmt_tiket->fetchAll();


// Query untuk wishlist user - DIPERBAIKI
$stmt_wishlist_items = $pdo->prepare("
    SELECT w.*, 
           d.name as destination_name, 
           d.image as destination_image,
           d.location as destination_location,
           d.rating as destination_rating,
           u.name as umkm_name,
           u.image as umkm_image,
           u.category as umkm_category,
           u.rating as umkm_rating
    FROM wishlists w
    LEFT JOIN destinations d ON (w.item_id = d.id AND w.item_type = 'destination')
    LEFT JOIN umkms u ON (w.item_id = u.id AND w.item_type = 'umkm')
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT 5
");
$stmt_wishlist_items->execute([$user_id]);
$wishlist_items = $stmt_wishlist_items->fetchAll();

// Query untuk aktivitas terbaru
$stmt_aktivitas = $pdo->prepare("
    (SELECT 'ticket' as type, t.created_at, t.code, d.name, NULL as rating, NULL as comment
     FROM tickets t 
     JOIN destinations d ON t.destination_id = d.id 
     WHERE t.user_id = ? 
     ORDER BY t.created_at DESC 
     LIMIT 5)
    UNION 
    (SELECT 'review' as type, r.created_at, NULL as code, d.name, r.rating, r.comment
     FROM reviews r 
     JOIN destinations d ON r.destination_id = d.id 
     WHERE r.user_id = ? 
     ORDER BY r.created_at DESC 
     LIMIT 5)
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt_aktivitas->execute([$user_id, $user_id]);
$activities = $stmt_aktivitas->fetchAll();

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

// Fungsi untuk waktu yang lalu
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'beberapa detik yang lalu';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        $months = floor($diff / 2592000);
        return $months . ' bulan yang lalu';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.2);
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
            box-shadow: var(--card-shadow-hover);
            border: 1px solid var(--gray-200);
            z-index: 1001; /* Pastikan lebih tinggi dari elemen lain */
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: var(--gray-200);
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

        /* Tombol Logout Modern */
        .logout-container {
            display: flex;
            align-items: center;
        }

        .logout-btn {
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

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: var(--border-radius);
            padding: 1.25rem 0.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            color: var(--gray-700);
            height: 100%;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            color: var(--primary-color);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            font-size: 1.4rem;
            background: var(--primary-light);
            color: var(--primary-color);
            transition: var(--transition);
        }

        .action-btn:hover .action-icon {
            background: var(--primary-color);
            color: white;
        }

        .action-text {
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            opacity: 0.1;
            background: currentColor;
            border-radius: 0 0 0 80px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .stat-icon-primary {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .stat-icon-success {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }

        .stat-icon-warning {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }

        .stat-icon-info {
            background-color: var(--info-light);
            color: var(--info-color);
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 0.2rem;
            line-height: 1.2;
        }

        .stat-title {
            color: var(--gray-600);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        /* Ticket Cards */
        .ticket-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 1rem;
            transition: var(--transition);
            overflow: hidden;
        }

        .ticket-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .ticket-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }

        .status-upcoming {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .status-used {
            background-color: var(--gray-200);
            color: var(--gray-600);
        }

        .ticket-content {
            display: flex;
            gap: 1.25rem;
        }

        .ticket-image {
            width: 100px;
            height: 100px;
            border-radius: var(--border-radius);
            object-fit: cover;
            flex-shrink: 0;
        }

        .ticket-details {
            flex: 1;
        }

        .ticket-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .ticket-info {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ticket-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        /* Activity List */
        .activity-list {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            height: 100%;
        }

        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: var(--transition);
        }

        .activity-item:hover {
            background-color: var(--gray-100);
            border-radius: 8px;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 0.3rem;
            color: var(--dark-color);
            font-size: 0.95rem;
        }

        .activity-desc {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .activity-time {
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        /* Wishlist Items - NEW DESIGN */
        .wishlist-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .wishlist-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .wishlist-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .wishlist-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .wishlist-content {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .wishlist-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .wishlist-details {
            flex: 1;
        }

        .wishlist-title {
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .wishlist-category {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .wishlist-rating {
            color: var(--warning-color);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .wishlist-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        /* Activity Section - NEW DESIGN */
        .activity-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .activity-list-new {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
            height: 100%;
        }

        .activity-item-new {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: var(--transition);
            border-radius: 8px;
        }

        .activity-item-new:hover {
            background-color: var(--primary-light);
            transform: translateX(5px);
        }

        .activity-item-new:last-child {
            border-bottom: none;
        }

        .activity-icon-new {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .activity-content-new {
            flex: 1;
        }

        .activity-title-new {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--dark-color);
            font-size: 0.95rem;
        }

        .activity-desc-new {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .activity-time-new {
            color: var(--gray-500);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* Section Titles */
        .section-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
            font-size: 1.25rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header-new {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
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

        /* Modern Button Styles */
        .btn-modern {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-modern-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-modern-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Glassmorphism Effect */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .wishlist-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.25rem 0;
                margin-bottom: 1.5rem;
            }
            
            .header-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .logout-container {
                margin-top: 0.5rem;
                align-self: stretch;
            }
            
            .logout-btn {
                width: 100%;
                justify-content: center;
            }
            
            .user-info {
                flex-direction: row;
                text-align: left;
                align-items: center;
                width: 100%;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
                margin-bottom: 1.5rem;
            }
            
            .action-icon {
                width: 42px;
                height: 42px;
                font-size: 1.2rem;
            }
            
            .ticket-content {
                flex-direction: column;
            }
            
            .ticket-image {
                width: 100%;
                height: 160px;
            }
            
            .wishlist-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .wishlist-image {
                width: 100%;
                height: 140px;
            }
            
            .wishlist-actions {
                width: 100%;
                justify-content: flex-end;
                margin-top: 1rem;
            }
            
            .stat-card::after {
                width: 60px;
                height: 60px;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .wishlist-container {
                grid-template-columns: 1fr;
            }

            .wishlist-section, .activity-section {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .ticket-actions {
                flex-direction: column;
            }
            
            .ticket-actions .btn {
                width: 100%;
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
                
                <div class="logout-container">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle logout-btn" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
        </div>
    </header>

    <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#tiket" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="action-text">Tiket Saya</div>
            </a>
            <a href="payment_history.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-credit-card"></i>
                </div>
                <div class="action-text">Pembayaran</div>
            </a>
            <a href="#wishlist" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-heart"></i>
                </div>
                <div class="action-text">Wishlist</div>
            </a>
            <a href="review_history.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-star"></i>
                </div>
                <div class="action-text">Review Saya</div>
            </a>
        </div>

        <!-- Stats Section -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <i class="bi bi-ticket-perforated"></i>
                    </div>
                    <h3 class="stat-value"><?= $tiket_aktif ?></h3>
                    <h5 class="stat-title">Tiket Aktif</h5>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="stat-value"><?= $kunjungan ?></h3>
                    <h5 class="stat-title">Kunjungan</h5>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <i class="bi bi-heart"></i>
                    </div>
                    <h3 class="stat-value"><?= $wishlist_count ?></h3>
                    <h5 class="stat-title">Wishlist</h5>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <i class="bi bi-star"></i>
                    </div>
                    <h3 class="stat-value"><?= $review ?></h3>
                    <h5 class="stat-title">Review</h5>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Tiket Aktif - Data Dinamis -->
            <div class="col-lg-8 mb-4" id="tiket">
                <div class="section-header">
                    <h3 class="section-title">Daftar Tiket Aktif</h3>
                </div>
                
                <?php if (count($tickets) > 0): ?>
                    <?php foreach ($tickets as $ticket): 
                        // Tentukan path gambar yang benar
                        $imagePath = '';
                        $altText = htmlspecialchars($ticket['destination_name']);
                        
                        if (!empty($ticket['destination_image'])) {
                            $imagePath = '../' . htmlspecialchars($ticket['destination_image']);
                        } else {
                            // Gambar placeholder jika tidak ada gambar
                            $imagePath = 'https://via.placeholder.com/100x100?text=No+Image';
                        }
                    ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <h5 class="ticket-title"><?= htmlspecialchars($ticket['destination_name']) ?></h5>
                            <span class="ticket-status status-<?= $ticket['status'] == 'paid' ? 'active' : 'upcoming' ?>">
                                <?= $ticket['status'] == 'paid' ? 'Aktif' : 'Menunggu Konfirmasi' ?>
                            </span>
                        </div>
                        <div class="ticket-content">
                            <img src="<?= $imagePath ?>" alt="<?= $altText ?>" class="ticket-image">
                            <div class="ticket-details">
                                <div class="ticket-info">
                                    <i class="bi bi-calendar"></i> <?= formatDate($ticket['visit_date']) ?>
                                </div>
                                <div class="ticket-info">
                                    <i class="bi bi-people"></i> <?= $ticket['quantity'] ?> Tiket
                                </div>
                                <div class="ticket-info">
                                    <i class="bi bi-upc-scan"></i> <?= htmlspecialchars($ticket['code']) ?>
                                </div>
                                <div class="ticket-actions">
                                    <a href="ticket_detail.php?id=<?= $ticket['id'] ?>" class="btn btn-outline-primary btn-modern">Detail</a>
                                    <?php if ($ticket['status'] == 'paid'): ?>
                                        <a href="use_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-primary btn-modern">Gunakan Tiket</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-ticket-perforated"></i>
                        <p>Anda tidak memiliki tiket aktif.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Aktivitas Terbaru - Data Dinamis -->
            <div class="col-lg-4 mb-4">
                <div class="activity-section">
                    <div class="section-header-new">
                        <h3 class="section-title">Aktivitas Terbaru</h3>
                        <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="activity-list-new">
                        <?php if (count($activities) > 0): ?>
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item-new">
                                <div class="activity-icon-new">
                                    <i class="bi <?= $activity['type'] == 'ticket' ? 'bi-ticket-perforated' : 'bi-star' ?>"></i>
                                </div>
                                <div class="activity-content-new">
                                    <h6 class="activity-title-new">
                                        <?= $activity['type'] == 'ticket' ? 'Pembelian Tiket' : 'Review Ditambahkan' ?>
                                    </h6>
                                    <p class="activity-desc-new">
                                        <?= htmlspecialchars($activity['name']) ?> 
                                        <?= $activity['type'] == 'ticket' ? ' - ' . htmlspecialchars($activity['code']) : ' - Rating: ' . $activity['rating'] ?>
                                    </p>
                                    <span class="activity-time-new">
                                        <i class="bi bi-clock"></i> <?= timeAgo($activity['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-activity"></i>
                                <p>Belum ada aktivitas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wishlist - Data Dinamis -->
        <div class="wishlist-section" id="wishlist">
            <div class="section-header-new">
                <h3 class="section-title">Wishlist Saya</h3>
                <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            
            <div class="wishlist-container">
                <?php if (count($wishlist_items) > 0): ?>
                    <?php foreach ($wishlist_items as $item): 
                        // Tentukan path gambar yang benar
                        $imagePath = '';
                        $altText = '';
                        
                        if (!empty($item['destination_image'])) {
                            $imagePath = '../' . htmlspecialchars($item['destination_image']);
                            $altText = htmlspecialchars($item['destination_name']);
                        } else if (!empty($item['umkm_image'])) {
                            $imagePath = '../' . htmlspecialchars($item['umkm_image']);
                            $altText = htmlspecialchars($item['umkm_name']);
                        } else {
                            // Gambar placeholder jika tidak ada gambar
                            $imagePath = 'https://via.placeholder.com/80x80?text=No+Image';
                            $altText = 'Tidak ada gambar';
                        }
                    ?>
                    <div class="wishlist-item">
                        <div class="wishlist-content">
                            <img src="<?= $imagePath ?>" alt="<?= $altText ?>" class="wishlist-image">
                            <div class="wishlist-details">
                                <h5 class="wishlist-title"><?= !empty($item['destination_name']) ? htmlspecialchars($item['destination_name']) : htmlspecialchars($item['umkm_name']) ?></h5>
                                <div class="wishlist-category">
                                    <?= !empty($item['destination_name']) ? 'Destinasi Wisata - ' . htmlspecialchars($item['destination_location']) : 'UMKM - ' . htmlspecialchars($item['umkm_category']) ?>
                                </div>
                                <div class="wishlist-rating">
                                    <?php
                                    $rating = !empty($item['destination_rating']) ? $item['destination_rating'] : $item['umkm_rating'];
                                    $fullStars = floor($rating);
                                    $halfStar = ($rating - $fullStars) >= 0.5;
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
                                    <span class="text-muted ms-1"><?= number_format($rating, 1) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="wishlist-actions">
                            <?php if (!empty($item['destination_name'])): ?>
                                <a href="../destination_detail.php?id=<?= $item['item_id'] ?>" class="btn btn-sm btn-primary">Beli Tiket</a>
                            <?php else: ?>
                                <a href="../umkm_detail.php?id=<?= $item['item_id'] ?>" class="btn btn-sm btn-primary">Kunjungi</a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger remove-wishlist" 
                                    data-id="<?= $item['id'] ?>" 
                                    data-type="<?= !empty($item['destination_name']) ? 'destination' : 'umkm' ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-heart"></i>
                            <p>Wishlist Anda masih kosong.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script sederhana untuk menandai notifikasi sebagai sudah dibaca
        document.addEventListener('DOMContentLoaded', function() {
            const activityItems = document.querySelectorAll('.activity-item-new');
            
            activityItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.style.opacity = '0.7';
                });
            });
        });

        // Script untuk menghapus wishlist
        document.querySelectorAll('.remove-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                const wishlistItem = this.closest('.wishlist-item');
                const itemName = wishlistItem ? wishlistItem.querySelector('.wishlist-title').textContent : 'Item';
                
                // Gunakan SweetAlert2 untuk konfirmasi
                Swal.fire({
                    title: 'Hapus dari Wishlist?',
                    html: `Apakah Anda yakin ingin menghapus <strong>${itemName}</strong> dari wishlist?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Gunakan path yang benar ke wishlist_remove.php (file berada di folder yang sama)
                        fetch('wishlist_remove.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${id}&type=${type}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Hapus elemen dari DOM
                                if (wishlistItem) {
                                    wishlistItem.remove();
                                }
                                
                                // Kurangi jumlah wishlist di statistik
                                const wishlistCountElement = document.querySelector('.stat-card:nth-child(3) .stat-value');
                                if (wishlistCountElement) {
                                    wishlistCountElement.textContent = parseInt(wishlistCountElement.textContent) - 1;
                                }
                                
                                // Tampilkan notifikasi sukses
                                Swal.fire({
                                    title: 'Terhapus!',
                                    text: 'Item berhasil dihapus dari wishlist',
                                    icon: 'success',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            } else {
                                // Tampilkan notifikasi error
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: data.message || 'Gagal menghapus item',
                                    icon: 'error',
                                    timer: 3000
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menghapus wishlist',
                                icon: 'error',
                                timer: 3000
                            });
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>