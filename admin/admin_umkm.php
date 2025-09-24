<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Ambil data UMKM
$stmt = $pdo->query("SELECT * FROM umkms ORDER BY created_at DESC");
$umkms = $stmt->fetchAll();

// Proses penghapusan UMKM
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Mulai transaction
    $pdo->beginTransaction();
    
    try {
        // Hapus data terkait di tabel wishlists (PERBAIKAN)
        $stmt_wishlists = $pdo->prepare("DELETE FROM wishlists WHERE item_id = ? AND item_type = 'umkm'");
        $stmt_wishlists->execute([$id]);
        
        // Hapus data terkait di tabel reviews
        $stmt_reviews = $pdo->prepare("DELETE FROM reviews WHERE umkm_id = ?");
        $stmt_reviews->execute([$id]);
        
        // Hapus UMKM
        $stmt = $pdo->prepare("DELETE FROM umkms WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "UMKM berhasil dihapus beserta semua data terkait";
        header("Location: admin_umkm.php?success=" . urlencode($success));
        exit;
    } catch (Exception $e) {
        // Rollback transaction jika ada error
        $pdo->rollBack();
        $error = "Gagal menghapus UMKM: " . $e->getMessage();
        header("Location: admin_umkm.php?error=" . urlencode($error));
        exit;
    }
}

// Proses tambah/edit UMKM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $rating = $_POST['rating'];
    $operational_hours = $_POST['operational_hours'];
    $contact_phone = $_POST['contact_phone'];
    $contact_email = $_POST['contact_email'];

    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    
    // Handle file upload
    $image = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/umkms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = 'uploads/umkms/' . $fileName;
                
                // Delete old image if exists
                if (!empty($_POST['existing_image']) && file_exists('../' . $_POST['existing_image'])) {
                    unlink('../' . $_POST['existing_image']);
                }
            } else {
                $error = "Gagal mengupload gambar.";
                header("Location: admin_umkm.php?error=" . urlencode($error));
                exit;
            }
        } else {
            $error = "File bukan gambar.";
            header("Location: admin_umkm.php?error=" . urlencode($error));
            exit;
        }
    }
    
    if ($id) {
        // Edit existing UMKM
        $stmt = $pdo->prepare("
            UPDATE umkms 
            SET name = ?, description = ?, category = ?, image = ?, rating = ?, 
                operational_hours = ?, contact_phone = ?, contact_email = ?, 
                latitude = ?, longitude = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $description, $category, $image, $rating, $operational_hours, $contact_phone, $contact_email, $latitude, $longitude, $id])) {
            $success = "UMKM berhasil diperbarui";
            header("Location: admin_umkm.php?success=" . urlencode($success));
            exit;
        } else {
            $error = "Gagal memperbarui UMKM";
            header("Location: admin_umkm.php?error=" . urlencode($error));
            exit;
        }
    } else {
        // Add new UMKM
        $stmt = $pdo->prepare("
            INSERT INTO umkms (name, description, category, image, rating, operational_hours, contact_phone, contact_email, latitude, longitude) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $description, $category, $image, $rating, $operational_hours, $contact_phone, $contact_email, $latitude, $longitude])) {
            $success = "UMKM berhasil ditambahkan";
            header("Location: admin_umkm.php?success=" . urlencode($success));
            exit;
        } else {
            $error = "Gagal menambahkan UMKM";
            header("Location: admin_umkm.php?error=" . urlencode($error));
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen UMKM</title>
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
        .table-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
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
                        <a href="admin_umkm.php" class="nav-link active">
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
                    <h1 class="header-title">Manajemen UMKM</h1>
                    <p class="header-subtitle">Kelola semua UMKM yang terdaftar di aplikasi</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                    <i class="bi bi-plus-circle"></i> Tambah UMKM
                </button>
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
                                <th>Gambar</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Rating</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($umkms as $umkm): ?>
                            <tr>
                                <td>
                                    <?php if ($umkm['image']): ?>
                                        <img src="../<?= htmlspecialchars($umkm['image']) ?>" class="table-image" alt="<?= htmlspecialchars($umkm['name']) ?>">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($umkm['name']) ?></td>
                                <td><?= htmlspecialchars($umkm['category']) ?></td>
                                <td>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-star-fill"></i> <?= $umkm['rating'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($umkm['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal" 
                                        data-name="<?= htmlspecialchars($umkm['name']) ?>"
                                        data-description="<?= htmlspecialchars($umkm['description']) ?>"
                                        data-category="<?= htmlspecialchars($umkm['category']) ?>"
                                        data-image="<?= htmlspecialchars($umkm['image']) ?>"
                                        data-rating="<?= $umkm['rating'] ?>">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" 
                                        data-id="<?= $umkm['id'] ?>"
                                        data-name="<?= htmlspecialchars($umkm['name']) ?>"
                                        data-description="<?= htmlspecialchars($umkm['description']) ?>"
                                        data-category="<?= htmlspecialchars($umkm['category']) ?>"
                                        data-image="<?= htmlspecialchars($umkm['image']) ?>"
                                        data-rating="<?= $umkm['rating'] ?>"
                                        data-operational_hours="<?= htmlspecialchars($umkm['operational_hours']) ?>"
                                        data-contact_phone="<?= htmlspecialchars($umkm['contact_phone']) ?>"
                                        data-contact_email="<?= htmlspecialchars($umkm['contact_email']) ?>"
                                        data-latitude="<?= $umkm['latitude'] ?? '' ?>"
                                        data-longitude="<?= $umkm['longitude'] ?? '' ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <a href="admin_umkm.php?hapus=<?= $umkm['id'] ?>" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Yakin ingin menghapus UMKM ini? Semua data terkait (ulasan, wishlist) juga akan dihapus permanen.')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
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

    <!-- Modal Tambah -->
    <div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">Tambah UMKM Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_umkm.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama UMKM</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Kategori</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Makanan & Minuman">Makanan & Minuman</option>
                                        <option value="Kerajinan Tangan">Kerajinan Tangan</option>
                                        <option value="Fashion">Fashion</option>
                                        <option value="Jasa">Jasa</option>
                                        <option value="Pertanian">Pertanian</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <input type="number" class="form-control" id="rating" name="rating" min="0" max="5" step="0.1" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="image" class="form-label">Gambar</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="operational_hours" class="form-label">Jam Operasional (format: Hari: Jam, pisahkan dengan enter)</label>
                                    <textarea class="form-control" id="operational_hours" name="operational_hours" rows="3" required><?= isset($umkm) ? $umkm['operational_hours'] : '' ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label">No. Handphone</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= isset($umkm) ? $umkm['contact_phone'] : '' ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= isset($umkm) ? $umkm['contact_email'] : '' ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Contoh: -3.7956">
                                </div>
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Contoh: 102.2592">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail UMKM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="detail_image" src="" class="img-fluid rounded" alt="UMKM Image" style="max-height: 200px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama UMKM</label>
                        <p id="detail_name" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <p id="detail_category" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rating</label>
                        <p id="detail_rating" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <p id="detail_description" class="form-control-static"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit UMKM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin_umkm.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" id="edit_existing_image" name="existing_image">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Nama UMKM</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_category" class="form-label">Kategori</label>
                                    <select class="form-select" id="edit_category" name="category" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Makanan & Minuman">Makanan & Minuman</option>
                                        <option value="Kerajinan Tangan">Kerajinan Tangan</option>
                                        <option value="Fashion">Fashion</option>
                                        <option value="Jasa">Jasa</option>
                                        <option value="Pertanian">Pertanian</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_rating" class="form-label">Rating</label>
                                    <input type="number" class="form-control" id="edit_rating" name="rating" min="0" max="5" step="0.1">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label">Gambar</label>
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                    <div id="edit_image_preview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="5"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_operational_hours" class="form-label">Jam Operasional (format: Hari: Jam, pisahkan dengan enter)</label>
                                    <textarea class="form-control" id="edit_operational_hours" name="operational_hours" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_contact_phone" class="form-label">No. Handphone</label>
                                    <input type="text" class="form-control" id="edit_contact_phone" name="contact_phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_contact_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_contact_email" name="contact_email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_latitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="edit_latitude" name="latitude" placeholder="Contoh: -3.7956">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_longitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="edit_longitude" name="longitude" placeholder="Contoh: 102.2592">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengisi modal edit dengan data
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var description = button.getAttribute('data-description');
            var category = button.getAttribute('data-category');
            var image = button.getAttribute('data-image');
            var rating = button.getAttribute('data-rating');
            var operationalHours = button.getAttribute('data-operational_hours');
            var contactPhone = button.getAttribute('data-contact_phone');
            var contactEmail = button.getAttribute('data-contact_email');
            var latitude = button.getAttribute('data-latitude');
            var longitude = button.getAttribute('data-longitude');
            
            var modalTitle = editModal.querySelector('.modal-title');
            var modalId = editModal.querySelector('#edit_id');
            var modalExistingImage = editModal.querySelector('#edit_existing_image');
            var modalName = editModal.querySelector('#edit_name');
            var modalDescription = editModal.querySelector('#edit_description');
            var modalCategory = editModal.querySelector('#edit_category');
            var modalRating = editModal.querySelector('#edit_rating');
            var modalImagePreview = editModal.querySelector('#edit_image_preview');
            var modalOperationalHours = editModal.querySelector('#edit_operational_hours');
            var modalContactPhone = editModal.querySelector('#edit_contact_phone');
            var modalContactEmail = editModal.querySelector('#edit_contact_email');
            var modalLatitude = editModal.querySelector('#edit_latitude');
            var modalLongitude = editModal.querySelector('#edit_longitude');
            
            modalTitle.textContent = 'Edit UMKM: ' + name;
            modalId.value = id;
            modalExistingImage.value = image;
            modalName.value = name;
            modalDescription.value = description;
            modalCategory.value = category;
            modalRating.value = rating;
            modalOperationalHours.value = operationalHours;
            modalContactPhone.value = contactPhone;
            modalContactEmail.value = contactEmail;
            modalLatitude.value = latitude;
            modalLongitude.value = longitude;
            
            // Show existing image preview
            if (image) {
                modalImagePreview.innerHTML = '<img src="../' + image + '" class="img-thumbnail" style="max-height: 150px;" alt="Current image">';
            } else {
                modalImagePreview.innerHTML = '<span class="text-muted">Tidak ada gambar</span>';
            }
        });
        
        // Script untuk mengisi modal detail dengan data
        var detailModal = document.getElementById('detailModal');
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var name = button.getAttribute('data-name');
            var description = button.getAttribute('data-description');
            var category = button.getAttribute('data-category');
            var image = button.getAttribute('data-image');
            var rating = button.getAttribute('data-rating');
            
            var modalTitle = detailModal.querySelector('.modal-title');
            var modalImage = detailModal.querySelector('#detail_image');
            var modalName = detailModal.querySelector('#detail_name');
            var modalCategory = detailModal.querySelector('#detail_category');
            var modalRating = detailModal.querySelector('#detail_rating');
            var modalDescription = detailModal.querySelector('#detail_description');
            
            modalTitle.textContent = 'Detail: ' + name;
            modalImage.src = image ? '../' + image : 'https://via.placeholder.com/300x200?text=No+Image';
            modalName.textContent = name;
            modalCategory.textContent = category;
            modalRating.textContent = rating + ' / 5';
            modalDescription.textContent = description || 'Tidak ada deskripsi';
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