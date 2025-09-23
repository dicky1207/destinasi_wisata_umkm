<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Ambil data pengguna
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Proses perubahan role
if (isset($_POST['ubah_role'])) {
    $id = $_POST['user_id'];
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$role, $id])) {
        $success = "Role pengguna berhasil diubah";
        header("Location: admin_users.php?success=" . urlencode($success));
        exit;
    } else {
        $error = "Gagal mengubah role pengguna";
        header("Location: admin_users.php?error=" . urlencode($error));
        exit;
    }
}

// Proses penghapusan pengguna
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Mulai transaction
    $pdo->beginTransaction();
    
    try {
        // Hapus data terkait di tabel wishlists
        $stmt_wishlists = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ?");
        $stmt_wishlists->execute([$id]);
        
        // Hapus data terkait di tabel reviews
        $stmt_reviews = $pdo->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt_reviews->execute([$id]);
        
        // Hapus data terkait di tabel transactions
        $stmt_transactions = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt_transactions->execute([$id]);
        
        // Hapus data terkait di tabel tickets
        $stmt_tickets = $pdo->prepare("DELETE FROM tickets WHERE user_id = ?");
        $stmt_tickets->execute([$id]);
        
        // Hapus pengguna
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "Pengguna berhasil dihapus beserta semua data terkait";
        header("Location: admin_users.php?success=" . urlencode($success));
        exit;
    } catch (Exception $e) {
        // Rollback transaction jika ada error
        $pdo->rollBack();
        $error = "Gagal menghapus pengguna: " . $e->getMessage();
        header("Location: admin_users.php?error=" . urlencode($error));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen User</title>
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
        .badge-admin {
            background-color: var(--primary-color);
        }
        
        .badge-user {
            background-color: var(--secondary-color);
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
                        <a href="admin_users.php" class="nav-link active">
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
                <div>
                    <h1 class="header-title">Manajemen User</h1>
                    <p class="header-subtitle">Kelola semua user yang terdaftar di aplikasi</p>
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
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#ubahRoleModal" 
                                        data-id="<?= $user['id'] ?>"
                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                        data-role="<?= $user['role'] ?>">
                                        <i class="bi bi-person-gear"></i> Ubah Role
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                    <a href="admin_users.php?hapus=<?= $user['id'] ?>" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Yakin ingin menghapus pengguna ini? Semua data terkait (tiket, ulasan, wishlist) juga akan dihapus permanen.')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                    <?php endif; ?>
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

    <!-- Modal Ubah Role -->
    <div class="modal fade" id="ubahRoleModal" tabindex="-1" aria-labelledby="ubahRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ubahRoleModalLabel">Ubah Role Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_users.php" method="POST">
                    <input type="hidden" id="user_id" name="user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user_name" class="form-label">Nama Pengguna</label>
                            <input type="text" class="form-control" id="user_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ubah_role" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengisi modal ubah role dengan data
        var ubahRoleModal = document.getElementById('ubahRoleModal');
        ubahRoleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var role = button.getAttribute('data-role');
            
            var modalTitle = ubahRoleModal.querySelector('.modal-title');
            var modalId = ubahRoleModal.querySelector('#user_id');
            var modalName = ubahRoleModal.querySelector('#user_name');
            var modalRole = ubahRoleModal.querySelector('#role');
            
            modalTitle.textContent = 'Ubah Role: ' + name;
            modalId.value = id;
            modalName.value = name;
            modalRole.value = role;
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