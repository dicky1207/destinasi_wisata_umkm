<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Konfigurasi pagination
$limit = 20; // Jumlah item per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total reviews
$stmt_count = $pdo->query("SELECT COUNT(*) FROM reviews");
$total_reviews = $stmt_count->fetchColumn();
$total_pages = ceil($total_reviews / $limit);

// Ambil data reviews dengan informasi terkait dengan pagination
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        u.name as user_name, 
        d.name as destination_name,
        um.name as umkm_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN destinations d ON r.destination_id = d.id
    LEFT JOIN umkms um ON r.umkm_id = um.id
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Proses penghapusan review
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Review berhasil dihapus";
        header("Location: admin_reviews.php?success=" . urlencode($success));
        exit;
    } else {
        $error = "Gagal menghapus review";
        header("Location: admin_reviews.php?error=" . urlencode($error));
        exit;
    }
}

// Proses perubahan status review (menampilkan/menyembunyikan)
if (isset($_POST['ubah_status_review'])) {
    $id = $_POST['review_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        $success = "Status review berhasil diubah";
        header("Location: admin_reviews.php?success=" . urlencode($success) . "&page=" . $page);
        exit;
    } else {
        $error = "Gagal mengubah status review";
        header("Location: admin_reviews.php?error=" . urlencode($error) . "&page=" . $page);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Review & Rating</title>
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

        /* Star Rating */
        .star-rating {
            color: #f59e0b;
        }
        
        /* Badge Styles */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .badge-active {
            background-color: #10b981;
            color: white;
        }
        
        .badge-inactive {
            background-color: #64748b;
            color: white;
        }

        /* Pagination Styles */
        .pagination {
            margin: 20px 0;
            justify-content: center;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-link {
            color: var(--primary-color);
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
            
            .table td {
                padding: 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
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
        
        /* Loading indicator */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
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
                        <a href="admin_tickets.php" class="nav-link">
                            <i class="bi bi-ticket-perforated nav-icon"></i>
                            <span>Tiket & Transaksi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_reviews.php" class="nav-link active">
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
                    <h1 class="header-title">Kelola Review & Rating</h1>
                    <p class="header-subtitle">Kelola semua review dan rating yang ada di aplikasi</p>
                </div>
                <div>
                    <span class="badge bg-primary">Total: <?= $total_reviews ?> Review</span>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="table-card">
                <div class="loading" id="loadingIndicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Destinasi/UMKM</th>
                                <th>Rating</th>
                                <th>Komentar</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reviews) > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?= htmlspecialchars($review['user_name']) ?></td>
                                <td>
                                    <?php 
                                    if ($review['destination_name']) {
                                        echo htmlspecialchars($review['destination_name']) . ' (Destinasi)';
                                    } elseif ($review['umkm_name']) {
                                        echo htmlspecialchars($review['umkm_name']) . ' (UMKM)';
                                    } else {
                                        echo 'Tidak diketahui';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="star-rating">
                                        <?php
                                        $fullStars = floor($review['rating']);
                                        $halfStar = ($review['rating'] - $fullStars) >= 0.5;
                                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                        
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="bi bi-star-fill"></i> ';
                                        }
                                        if ($halfStar) {
                                            echo '<i class="bi bi-star-half"></i> ';
                                        }
                                        for ($i = 0; $i < $emptyStars; $i++) {
                                            echo '<i class="bi bi-star"></i> ';
                                        }
                                        ?>
                                        <span class="ms-1">(<?= $review['rating'] ?>)</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(substr($review['comment'], 0, 50)) . (strlen($review['comment']) > 50 ? '...' : '') ?></td>
                                <td>
                                    <span class="badge badge-<?= $review['status'] === 'active' ? 'active' : 'inactive' ?>">
                                        <?= ucfirst($review['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($review['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailReviewModal" 
                                            data-user="<?= htmlspecialchars($review['user_name']) ?>"
                                            data-item="<?= htmlspecialchars($review['destination_name'] ? $review['destination_name'] . ' (Destinasi)' : ($review['umkm_name'] ? $review['umkm_name'] . ' (UMKM)' : 'Tidak diketahui')) ?>"
                                            data-rating="<?= $review['rating'] ?>"
                                            data-comment="<?= htmlspecialchars($review['comment']) ?>"
                                            data-date="<?= date('d M Y H:i', strtotime($review['created_at'])) ?>">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                        
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#ubahStatusReviewModal" 
                                            data-id="<?= $review['id'] ?>"
                                            data-status="<?= $review['status'] ?>">
                                            <i class="bi bi-pencil"></i> Status
                                        </button>
                                        
                                        <a href="admin_reviews.php?hapus=<?= $review['id'] ?>&page=<?= $page ?>" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Yakin ingin menghapus review ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada data review</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Modal Detail Review -->
    <div class="modal fade" id="detailReviewModal" tabindex="-1" aria-labelledby="detailReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailReviewModalLabel">Detail Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pengguna</label>
                        <p id="detail_user" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Destinasi/UMKM</label>
                        <p id="detail_item" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rating</label>
                        <p id="detail_rating" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Komentar</label>
                        <p id="detail_comment" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal</label>
                        <p id="detail_date" class="form-control-static"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Status Review -->
    <div class="modal fade" id="ubahStatusReviewModal" tabindex="-1" aria-labelledby="ubahStatusReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ubahStatusReviewModalLabel">Ubah Status Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_reviews.php" method="POST">
                    <input type="hidden" name="page" value="<?= $page ?>">
                    <input type="hidden" id="review_id" name="review_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active (Ditampilkan)</option>
                                <option value="inactive">Inactive (Disembunyikan)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="ubah_status_review" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengisi modal detail review dengan data
        var detailReviewModal = document.getElementById('detailReviewModal');
        detailReviewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var user = button.getAttribute('data-user');
            var item = button.getAttribute('data-item');
            var rating = button.getAttribute('data-rating');
            var comment = button.getAttribute('data-comment');
            var date = button.getAttribute('data-date');
            
            var modalTitle = detailReviewModal.querySelector('.modal-title');
            var modalUser = detailReviewModal.querySelector('#detail_user');
            var modalItem = detailReviewModal.querySelector('#detail_item');
            var modalRating = detailReviewModal.querySelector('#detail_rating');
            var modalComment = detailReviewModal.querySelector('#detail_comment');
            var modalDate = detailReviewModal.querySelector('#detail_date');
            
            modalTitle.textContent = 'Review dari ' + user;
            modalUser.textContent = user;
            modalItem.textContent = item;
            
            // Create star rating display
            let starHtml = '';
            const fullStars = Math.floor(rating);
            const halfStar = (rating - fullStars) >= 0.5;
            const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
            
            for (let i = 0; i < fullStars; i++) {
                starHtml += '<i class="bi bi-star-fill text-warning"></i> ';
            }
            if (halfStar) {
                starHtml += '<i class="bi bi-star-half text-warning"></i> ';
            }
            for (let i = 0; i < emptyStars; i++) {
                starHtml += '<i class="bi bi-star text-warning"></i> ';
            }
            starHtml += `<span class="ms-1">(${rating})</span>`;
            
            modalRating.innerHTML = starHtml;
            modalComment.textContent = comment;
            modalDate.textContent = date;
        });
        
        // Script untuk mengisi modal ubah status review dengan data
        var ubahStatusReviewModal = document.getElementById('ubahStatusReviewModal');
        ubahStatusReviewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var status = button.getAttribute('data-status');
            
            var modalTitle = ubahStatusReviewModal.querySelector('.modal-title');
            var modalId = ubahStatusReviewModal.querySelector('#review_id');
            var modalStatus = ubahStatusReviewModal.querySelector('#status');
            
            modalTitle.textContent = 'Ubah Status Review #' + id;
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
        
        // Tampilkan loading indicator saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
                
                // Sembunyikan loading indicator setelah 500ms
                setTimeout(function() {
                    loadingIndicator.style.display = 'none';
                }, 500);
            }
        });
    </script>
</body>
</html>