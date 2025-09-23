<?php
session_start();
require_once '../config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Validasi role admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Query untuk statistik dashboard
$stmt_users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$users_count = $stmt_users->fetch()['count'];

$stmt_destinations = $pdo->query("SELECT COUNT(*) as count FROM destinations");
$destinations_count = $stmt_destinations->fetch()['count'];

$stmt_umkms = $pdo->query("SELECT COUNT(*) as count FROM umkms");
$umkms_count = $stmt_umkms->fetch()['count'];

$stmt_revenue = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'success'");
$revenue = $stmt_revenue->fetch()['total'] ?? 0;

// Query untuk statistik dinamis (bulan ini vs bulan lalu)
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// Statistik pengguna
$stmt_current_users = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_current_users->execute([$current_month]);
$current_month_users = $stmt_current_users->fetch()['count'];

$stmt_last_users = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_last_users->execute([$last_month]);
$last_month_users = $stmt_last_users->fetch()['count'];

$users_change = $last_month_users > 0 ? (($current_month_users - $last_month_users) / $last_month_users) * 100 : 100;

// Statistik destinasi
$stmt_current_destinations = $pdo->prepare("SELECT COUNT(*) as count FROM destinations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_current_destinations->execute([$current_month]);
$current_month_destinations = $stmt_current_destinations->fetch()['count'];

$stmt_last_destinations = $pdo->prepare("SELECT COUNT(*) as count FROM destinations WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_last_destinations->execute([$last_month]);
$last_month_destinations = $stmt_last_destinations->fetch()['count'];

$destinations_change = $last_month_destinations > 0 ? (($current_month_destinations - $last_month_destinations) / $last_month_destinations) * 100 : 100;

// Statistik UMKM
$stmt_current_umkms = $pdo->prepare("SELECT COUNT(*) as count FROM umkms WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_current_umkms->execute([$current_month]);
$current_month_umkms = $stmt_current_umkms->fetch()['count'];

$stmt_last_umkms = $pdo->prepare("SELECT COUNT(*) as count FROM umkms WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_last_umkms->execute([$last_month]);
$last_month_umkms = $stmt_last_umkms->fetch()['count'];

$umkms_change = $last_month_umkms > 0 ? (($current_month_umkms - $last_month_umkms) / $last_month_umkms) * 100 : 100;

// Statistik pendapatan
$stmt_current_revenue = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'success' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_current_revenue->execute([$current_month]);
$current_month_revenue = $stmt_current_revenue->fetch()['total'] ?? 0;

$stmt_last_revenue = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'success' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_last_revenue->execute([$last_month]);
$last_month_revenue = $stmt_last_revenue->fetch()['total'] ?? 0;

$revenue_change = $last_month_revenue > 0 ? (($current_month_revenue - $last_month_revenue) / $last_month_revenue) * 100 : 100;

// Query untuk aktivitas terbaru
$stmt_activities = $pdo->query("
    (SELECT 'user' as type, name as title, created_at, 'Pengguna baru mendaftar' as description FROM users ORDER BY created_at DESC LIMIT 3)
    UNION 
    (SELECT 'destination' as type, name as title, created_at, 'Destinasi baru ditambahkan' as description FROM destinations ORDER BY created_at DESC LIMIT 3)
    UNION 
    (SELECT 'umkm' as type, name as title, created_at, 'UMKM baru terdaftar' as description FROM umkms ORDER BY created_at DESC LIMIT 3)
    ORDER BY created_at DESC LIMIT 5
");
$activities = $stmt_activities->fetchAll();

// Fungsi untuk format waktu yang lalu
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
    <title>Dashboard Admin - Aplikasi Wisata & UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #334155;
            overflow-x: hidden;
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.2rem 1.2rem 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.5rem;
        }

        .sidebar-brand {
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand-icon {
            background: var(--primary-color);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-item {
            margin-bottom: 0.2rem;
            width: 100%;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.7rem 1.2rem;
            border-radius: 6px;
            margin: 0 0.4rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: var(--primary-color);
        }

        .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 0.95rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        /* Header */
        .dashboard-header {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .header-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }

        .header-subtitle {
            color: var(--secondary-color);
            font-weight: 400;
            font-size: 0.9rem;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark-color);
            margin-bottom: 0.2rem;
        }

        .stat-title {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: auto;
        }

        .change-positive {
            color: var(--success-color);
        }

        .change-negative {
            color: var(--danger-color);
        }

        /* Recent Activities */
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            height: 100%;
        }

        .activity-list {
            overflow-y: auto;
            max-height: 300px;
            padding-right: 5px;
        }

        .activity-list::-webkit-scrollbar {
            width: 5px;
        }

        .activity-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .activity-list::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 10px;
        }

        .activity-item {
            padding: 0.9rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 0.8rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 0.2rem;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .activity-desc {
            color: var(--secondary-color);
            font-size: 0.8rem;
            margin-bottom: 0.2rem;
        }

        .activity-time {
            color: var(--secondary-color);
            font-size: 0.75rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            color: var(--dark-color);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
            background: var(--light-color);
            color: var(--primary-color);
        }

        .action-text {
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.3rem;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .dashboard-header {
                padding: 1rem;
            }
            
            .header-title {
                font-size: 1.3rem;
            }
        }

        /* Toggle Button */
        .sidebar-toggle {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            border: none;
        }

        /* Utilities */
        .text-sm {
            font-size: 0.875rem;
        }
        
        /* Ensure equal card heights in row */
        .equal-height {
            display: flex;
            flex-wrap: wrap;
        }
        
        .equal-height > .col {
            display: flex;
            flex-direction: column;
        }
        
        .equal-height .stat-card {
            flex: 1;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <span>Wisata & UMKM</span>
                </div>
            </div>
            <div class="sidebar-content px-2">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="bi bi-speedometer2 nav-icon"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_users.php" class="nav-link">
                            <i class="bi bi-people nav-icon"></i>
                            <span>Manajemen User</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_destinasi.php" class="nav-link">
                            <i class="bi bi-geo-alt nav-icon"></i>
                            <span>Destinasi Wisata</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_umkm.php" class="nav-link">
                            <i class="bi bi-shop nav-icon"></i>
                            <span>Manajemen UMKM</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_tickets.php" class="nav-link">
                            <i class="bi bi-ticket-perforated nav-icon"></i>
                            <span>Tiket & Transaksi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_reviews.php" class="nav-link">
                            <i class="bi bi-star nav-icon"></i>
                            <span>Review & Rating</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="../auth/logout.php" class="nav-link">
                            <i class="bi bi-box-arrow-right nav-icon"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1 class="header-title">Dashboard Admin</h1>
                <p class="header-subtitle">Selamat datang, <?= htmlspecialchars($_SESSION['user']['name']) ?>! Berikut adalah ringkasan data aplikasi.</p>
            </div>

            <!-- Stats Section -->
            <div class="row equal-height mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3 class="stat-value"><?= $users_count ?></h3>
                        <h5 class="stat-title">Total Pengguna</h5>
                        <div class="stat-change <?= $users_change >= 0 ? 'change-positive' : 'change-negative' ?>">
                            <i class="bi <?= $users_change >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i>
                            <span><?= abs(round($users_change)) ?>% dari bulan lalu</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <h3 class="stat-value"><?= $destinations_count ?></h3>
                        <h5 class="stat-title">Destinasi Wisata</h5>
                        <div class="stat-change <?= $destinations_change >= 0 ? 'change-positive' : 'change-negative' ?>">
                            <i class="bi <?= $destinations_change >= 0 ? 'bi-plus-circle' : 'bi-dash-circle' ?>"></i>
                            <span><?= abs(round($destinations_change)) ?>% dari bulan lalu</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3 class="stat-value"><?= $umkms_count ?></h3>
                        <h5 class="stat-title">UMKM Terdaftar</h5>
                        <div class="stat-change <?= $umkms_change >= 0 ? 'change-positive' : 'change-negative' ?>">
                            <i class="bi <?= $umkms_change >= 0 ? 'bi-plus-circle' : 'bi-dash-circle' ?>"></i>
                            <span><?= abs(round($umkms_change)) ?>% dari bulan lalu</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h3 class="stat-value">Rp <?= number_format($revenue, 0, ',', '.') ?></h3>
                        <h5 class="stat-title">Pendapatan</h5>
                        <div class="stat-change <?= $revenue_change >= 0 ? 'change-positive' : 'change-negative' ?>">
                            <i class="bi <?= $revenue_change >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i>
                            <span><?= abs(round($revenue_change)) ?>% dari bulan lalu</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="recent-activity">
                        <h5 class="mb-3">Aktivitas Terbaru</h5>
                        <div class="activity-list">
                            <?php if (count($activities) > 0): ?>
                                <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon 
                                        <?= $activity['type'] == 'user' ? 'bg-primary' : '' ?>
                                        <?= $activity['type'] == 'destination' ? 'bg-success' : '' ?>
                                        <?= $activity['type'] == 'umkm' ? 'bg-warning' : '' ?>
                                        text-white">
                                        <i class="bi 
                                            <?= $activity['type'] == 'user' ? 'bi-person' : '' ?>
                                            <?= $activity['type'] == 'destination' ? 'bi-geo-alt' : '' ?>
                                            <?= $activity['type'] == 'umkm' ? 'bi-shop' : '' ?>
                                        "></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6 class="activity-title"><?= htmlspecialchars($activity['title']) ?></h6>
                                        <p class="activity-desc"><?= htmlspecialchars($activity['description']) ?></p>
                                        <span class="activity-time"><?= timeAgo($activity['created_at']) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <p class="text-muted">Belum ada aktivitas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="stat-card">
                        <h5 class="mb-3">Statistik Pengunjung</h5>
                        <div class="text-center">
                            <canvas id="visitorChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');

            // Change icon based on sidebar state
            const icon = this.querySelector('i');
            if (document.querySelector('.sidebar').classList.contains('active')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x');
            } else {
                icon.classList.remove('bi-x');
                icon.classList.add('bi-list');
            }
        });

        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('visitorChart').getContext('2d');
            const visitorChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Jumlah Pengunjung',
                        data: [120, 190, 140, 180, 210, 250, 220],
                        backgroundColor: 'rgba(37, 99, 235, 0.2)',
                        borderColor: '#2563eb',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>