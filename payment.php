<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    // Jika request AJAX, kembalikan JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Anda harus login terlebih dahulu']);
        exit;
    } else {
        header('Location: auth/login.php');
        exit;
    }
}

// Cek apakah parameter ticket_code ada
if (!isset($_GET['ticket_code'])) {
    // Jika request AJAX, kembalikan JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Kode tiket tidak valid']);
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

$ticket_code = $_GET['ticket_code'];

// Ambil data tiket
try {
    $stmt = $pdo->prepare("
        SELECT t.*, d.name as destination_name, d.price as destination_price 
        FROM tickets t 
        JOIN destinations d ON t.destination_id = d.id 
        WHERE t.code = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_code, $_SESSION['user']['id']]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        // Jika request AJAX, kembalikan JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Tiket tidak ditemukan']);
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }
    
    // Cek apakah tiket sudah dibayar
    if ($ticket['status'] == 'paid') {
        $error = "Tiket ini sudah dibayar.";
        // Jika request AJAX, kembalikan JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Jika request AJAX, kembalikan JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
        exit;
    } else {
        $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
    }
}

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];

    // Proses upload bukti pembayaran
    $upload_success = false;
    $upload_error = '';

    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/payments/";
        
        // Buat direktori jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES["bukti_pembayaran"]["name"]);
        $target_file = $target_dir . $file_name;
        $upload_ok = 1;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Cek apakah file adalah gambar atau PDF
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file_type, $allowed_types)) {
            $upload_error = "Hanya file JPG, JPEG, PNG & PDF yang diperbolehkan.";
            $upload_ok = 0;
        }
        
        // Cek ukuran file (maksimal 1MB)
        if ($_FILES["bukti_pembayaran"]["size"] > 1000000) {
            $upload_error = "Ukuran file terlalu besar. Maksimal 1MB.";
            $upload_ok = 0;
        }
        
        // Jika semua syarat terpenuhi, upload file
        if ($upload_ok == 1) {
            if (move_uploaded_file($_FILES["bukti_pembayaran"]["tmp_name"], $target_file)) {
                $upload_success = true;
                
                // Simpan transaksi dengan status pending
                $stmt_transaction = $pdo->prepare("
                    INSERT INTO transactions (user_id, ticket_id, payment_method, status, amount, bukti_pembayaran) 
                    VALUES (?, ?, ?, 'pending', ?, ?)
                ");
                
                if ($stmt_transaction->execute([
                    $_SESSION['user']['id'],
                    $ticket['id'],
                    $payment_method,
                    $ticket['total_price'],
                    $file_name
                ])) {
                    // Jika request AJAX, kembalikan JSON
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil diupload. Menunggu konfirmasi admin.', 'redirect' => 'user/dashboard.php']);
                        exit;
                    } else {
                        // Redirect ke dashboard dengan pesan sukses
                        $_SESSION['payment_success'] = "Bukti pembayaran berhasil diupload. Menunggu konfirmasi admin.";
                        header("Location: user/dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Gagal menyimpan informasi pembayaran.";
                    
                    // Jika request AJAX, kembalikan JSON
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $error]);
                        exit;
                    }
                }
            } else {
                $error = "Terjadi kesalahan saat mengupload file.";
                
                // Jika request AJAX, kembalikan JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        } else {
            $error = $upload_error;
            
            // Jika request AJAX, kembalikan JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } else {
        $error = "Harap upload bukti pembayaran.";
        
        // Jika request AJAX, kembalikan JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
    
    // Jika sampai di sini, berarti ada error yang belum dihandle
    // Jika request AJAX, kembalikan JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan tidak terduga']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Tiket</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gradient-start: #4361ee;
            --gradient-end: #3a0ca3;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            overflow-x: hidden;
            position: relative;
            padding: 20px 0;
        }
        
        .floating-shape {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s infinite ease-in-out;
            z-index: 0;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -50px;
            animation-delay: 1s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 70%;
            animation-delay: 2s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }
        
        .payment-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .page-title {
            color: white;
            margin-bottom: 2rem;
            font-weight: 700;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .card-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: none;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            width: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }
        
        .payment-details {
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 10px;
            padding: 1.2rem;
            margin-top: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .payment-details h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.8rem;
        }
        
        #qris-details img {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0.8rem 0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .ticket-detail {
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .ticket-detail:last-child {
            border-bottom: none;
        }
        
        .ticket-detail strong {
            color: #495057;
            min-width: 150px;
        }
        
        .ticket-detail span {
            color: #6c757d;
            text-align: right;
        }
        
        @media (max-width: 768px) {
            .payment-container {
                padding: 0 15px;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .ticket-detail {
                flex-direction: column;
            }
            
            .ticket-detail span {
                text-align: left;
                margin-top: 0.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <div class="payment-container">
        <h2 class="page-title" data-aos="fade-down"><i class="fas fa-ticket-alt me-2"></i>Pembayaran Tiket</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" data-aos="fade-up"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-receipt"></i>Detail Pemesanan</h5>
                
                <div class="ticket-detail">
                    <strong>Destinasi:</strong>
                    <span><?= $ticket['destination_name'] ?></span>
                </div>
                
                <div class="ticket-detail">
                    <strong>Tanggal Kunjungan:</strong>
                    <span><?= date('d M Y', strtotime($ticket['visit_date'])) ?></span>
                </div>
                
                <div class="ticket-detail">
                    <strong>Jumlah Tiket:</strong>
                    <span><?= $ticket['quantity'] ?></span>
                </div>
                
                <div class="ticket-detail">
                    <strong>Total Harga:</strong>
                    <span>Rp <?= number_format($ticket['total_price'], 0, ',', '.') ?></span>
                </div>
                
                <div class="ticket-detail">
                    <strong>Kode Tiket:</strong>
                    <span class="text-primary"><?= $ticket['code'] ?></span>
                </div>
            </div>
        </div>
        
        <div class="card" data-aos="fade-up" data-aos-delay="200">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-credit-card"></i>Metode Pembayaran</h5>
                <form method="POST" id="payment-form">
                    <div class="form-group">
                        <label for="payment_method">Pilih Metode Pembayaran:</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                            <option value="cod">Bayar di Lokasi</option>
                        </select>
                    </div>

                    <!-- Form upload bukti pembayaran -->
                    <div class="form-group">
                        <label for="bukti_pembayaran">Upload Bukti Pembayaran:</label>
                        <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-text text-muted">Format: JPG, JPEG, PNG, PDF (maks. 1MB)</small>
                    </div>
                    
                    <div id="transfer-details" class="payment-details">
                        <h6><i class="fas fa-building me-2"></i>Informasi Transfer Bank:</h6>
                        <p class="mb-1">Bank: BSI</p>
                        <p class="mb-1">No. Rekening: 7326975421</p>
                        <p class="mb-0">a.n: Wisata UMKM</p>
                    </div>
                    
                    <div id="qris-details" class="payment-details" style="display: none;">
                        <h6><i class="fas fa-qrcode me-2"></i>Scan QR Code berikut:</h6>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=WISATAUMKM-<?= $ticket_code ?>" alt="QR Code">
                        <p class="mt-2 mb-0">Scan kode QR di atas menggunakan aplikasi e-wallet atau mobile banking Anda</p>
                    </div>
                    
                    <div id="cod-details" class="payment-details" style="display: none;">
                        <h6><i class="fas fa-money-bill-wave me-2"></i>Bayar di Lokasi:</h6>
                        <p class="mb-0">Anda dapat melakukan pembayaran langsung di lokasi destinasi pada hari kunjungan. Pastikan untuk menunjukkan kode tiket ini.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4" id="submit-btn">
                        <i class="fas fa-check-circle me-2"></i>Konfirmasi Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Inisialisasi AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Script untuk menampilkan detail pembayaran berdasarkan metode yang dipilih
            document.getElementById('payment_method').addEventListener('change', function() {
                // Sembunyikan semua detail pembayaran
                document.querySelectorAll('.payment-details').forEach(function(el) {
                    el.style.display = 'none';
                });
                
                // Tampilkan detail yang sesuai
                document.getElementById(this.value + '-details').style.display = 'block';
            });

            // Validasi form dan enable tombol setelah upload file
            document.getElementById('bukti_pembayaran').addEventListener('change', function() {
                if (this.files.length > 0) {
                    document.getElementById('submit-btn').disabled = false;
                } else {
                    document.getElementById('submit-btn').disabled = true;
                }
            });
            
            // Trigger change event saat halaman dimuat
            document.getElementById('payment_method').dispatchEvent(new Event('change'));
            
            // Handle form submission dengan AJAX dan SweetAlert2
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Tampilkan loading indicator pada tombol
                const submitBtn = document.getElementById('submit-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                submitBtn.disabled = true;
                
                // Kirim data form via AJAX
                const formData = new FormData(this);
                
                // Tambahkan header untuk mengidentifikasi request sebagai AJAX
                fetch('<?= $_SERVER['PHP_SELF'] . '?ticket_code=' . $ticket_code ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    // Cek status response
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    
                    // Cek jika response adalah JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // Jika bukan JSON, mungkin redirect atau error HTML
                        return response.text().then(text => {
                            // Coba parsing sebagai HTML untuk mendapatkan pesan error
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(text, 'text/html');
                            const errorElement = doc.querySelector('.alert.alert-danger');
                            const errorMsg = errorElement ? errorElement.textContent : 'Terjadi kesalahan tidak terduga';
                            
                            throw new Error(errorMsg);
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Tampilkan SweetAlert2 sukses
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil!',
                            text: 'Selamat menikmati liburan Anda.',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            willClose: () => {
                                // Redirect ke dashboard setelah alert tertutup
                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                } else {
                                    window.location.href = 'user/dashboard.php';
                                }
                            }
                        });
                    } else {
                        // Tampilkan error dari server jika ada
                        const errorMsg = data.error || 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Pembayaran Gagal',
                            text: errorMsg
                        });
                        
                        // Kembalikan tombol ke keadaan semula
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: error.message || 'Terjadi kesalahan pada sistem. Silakan coba lagi.'
                    });
                    
                    // Kembalikan tombol ke keadaan semula
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>