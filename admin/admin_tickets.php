<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Ambil data tiket dengan informasi terkait
$stmt = $pdo->query("
    SELECT 
        t.*, 
        u.name as user_name, 
        d.name as destination_name,
        tr.id as transaction_id,
        tr.payment_method,
        tr.status as transaction_status,
        tr.amount as transaction_amount
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN destinations d ON t.destination_id = d.id
    LEFT JOIN transactions tr ON t.id = tr.ticket_id
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();

// Proses perubahan status tiket
if (isset($_POST['ubah_status_tiket'])) {
    $id = $_POST['ticket_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        // Jika status diubah menjadi 'paid', berikan point kepada user
        if ($status == 'paid') {
            // Dapatkan user_id dari tiket
            $stmt_user = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
            $stmt_user->execute([$id]);
            $ticket_user = $stmt_user->fetch();
            
            if ($ticket_user) {
                // Berikan point (contoh: 10 point per tiket)
                $stmt_point = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
                $stmt_point->execute([$ticket_user['user_id']]);
            }
        }
        
        $success = "Status tiket berhasil diubah";
        header("Location: admin_tickets.php?success=" . urlencode($success));
        exit;
    } else {
        $error = "Gagal mengubah status tiket";
        header("Location: admin_tickets.php?error=" . urlencode($error));
        exit;
    }
}

// Proses perubahan status transaksi
if (isset($_POST['ubah_status_transaksi'])) {
    $id = $_POST['transaction_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        $success = "Status transaksi berhasil diubah";
        header("Location: admin_tickets.php?success=" . urlencode($success));
        exit;
    } else {
        $error = "Gagal mengubah status transaksi";
        header("Location: admin_tickets.php?error=" . urlencode($error));
        exit;
    }
}

// Proses penghapusan tiket
if (isset($_GET['hapus_tiket'])) {
    $id = $_GET['hapus_tiket'];
    
    // Hapus transaksi terkait terlebih dahulu (jika ada)
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE ticket_id = ?");
    $stmt->execute([$id]);
    
    // Hapus tiket
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Tiket berhasil dihapus";
        header("Location: admin_tickets.php?success=" . urlencode($success));
        exit;
    } else {
        $error = "Gagal menghapus tiket";
        header("Location: admin_tickets.php?error=" . urlencode($error));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tiket & Transaksi</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        /* Table Styles */
        .table-card {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--dark-color);
            padding: 0.75rem 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Button Styles */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        /* Alert Styles */
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: var(--card-shadow);
        }

        /* Badge Styles */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: #f59e0b;
            color: white;
        }
        
        .badge-paid {
            background-color: #10b981;
            color: white;
        }
        
        .badge-cancelled {
            background-color: #ef4444;
            color: white;
        }
        
        .badge-success {
            background-color: #10b981;
            color: white;
        }
        
        .badge-failed {
            background-color: #ef4444;
            color: white;
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
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-title {
                font-size: 1.3rem;
            }
            
            .btn-group-responsive {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group-responsive .btn {
                width: 100%;
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
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
                        <a href="dashboard.php" class="nav-link">
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
                        <a href="admin_tickets.php" class="nav-link active">
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
                <div>
                    <h1 class="header-title">Kelola Tiket & Transaksi</h1>
                    <p class="header-subtitle">Kelola semua tiket dan transaksi yang ada di aplikasi</p>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Kode Tiket</th>
                                <th>Pengguna</th>
                                <th>Destinasi</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Jumlah</th>
                                <th>Total Harga</th>
                                <th>Status Tiket</th>
                                <th>Status Transaksi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['code']) ?></td>
                                <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['destination_name']) ?></td>
                                <td><?= date('d M Y', strtotime($ticket['visit_date'])) ?></td>
                                <td><?= $ticket['quantity'] ?></td>
                                <td>Rp <?= number_format($ticket['total_price'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge badge-<?= $ticket['status'] ?>">
                                        <?= ucfirst($ticket['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ticket['transaction_status']): ?>
                                        <span class="badge badge-<?= $ticket['transaction_status'] === 'success' ? 'success' : ($ticket['transaction_status'] === 'pending' ? 'pending' : 'failed') ?>">
                                            <?= ucfirst($ticket['transaction_status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tidak ada transaksi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#ubahStatusTiketModal" 
                                            data-id="<?= $ticket['id'] ?>"
                                            data-status="<?= $ticket['status'] ?>">
                                            <i class="bi bi-pencil"></i> Status Tiket
                                        </button>
                                        
                                        <?php if ($ticket['transaction_id']): ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#ubahStatusTransaksiModal" 
                                            data-id="<?= $ticket['transaction_id'] ?>"
                                            data-status="<?= $ticket['transaction_status'] ?>">
                                            <i class="bi bi-currency-exchange"></i> Status Transaksi
                                        </button>
                                        <?php endif; ?>
                                        
                                        <a href="admin_tickets.php?hapus_tiket=<?= $ticket['id'] ?>" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Yakin ingin menghapus tiket ini? Tindakan ini juga akan menghapus transaksi terkait.')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Modal Ubah Status Tiket -->
    <div class="modal fade" id="ubahStatusTiketModal" tabindex="-1" aria-labelledby="ubahStatusTiketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ubahStatusTiketModalLabel">Ubah Status Tiket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_tickets.php" method="POST">
                    <input type="hidden" id="ticket_id" name="ticket_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ubah_status_tiket" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Status Transaksi -->
    <div class="modal fade" id="ubahStatusTransaksiModal" tabindex="-1" aria-labelledby="ubahStatusTransaksiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ubahStatusTransaksiModalLabel">Ubah Status Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_tickets.php" method="POST">
                    <input type="hidden" id="transaction_id" name="transaction_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="transaction_status" class="form-label">Status</label>
                            <select class="form-select" id="transaction_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="success">Success</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ubah_status_transaksi" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengisi modal ubah status tiket dengan data
        var ubahStatusTiketModal = document.getElementById('ubahStatusTiketModal');
        ubahStatusTiketModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var status = button.getAttribute('data-status');
            
            var modalTitle = ubahStatusTiketModal.querySelector('.modal-title');
            var modalId = ubahStatusTiketModal.querySelector('#ticket_id');
            var modalStatus = ubahStatusTiketModal.querySelector('#status');
            
            modalTitle.textContent = 'Ubah Status Tiket #' + id;
            modalId.value = id;
            modalStatus.value = status;
        });
        
        // Script untuk mengisi modal ubah status transaksi dengan data
        var ubahStatusTransaksiModal = document.getElementById('ubahStatusTransaksiModal');
        ubahStatusTransaksiModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var status = button.getAttribute('data-status');
            
            var modalTitle = ubahStatusTransaksiModal.querySelector('.modal-title');
            var modalId = ubahStatusTransaksiModal.querySelector('#transaction_id');
            var modalStatus = ubahStatusTransaksiModal.querySelector('#transaction_status');
            
            modalTitle.textContent = 'Ubah Status Transaksi #' + id;
            modalId.value = id;
            modalStatus.value = status;
        });
        
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
    </script>
</body>
</html>