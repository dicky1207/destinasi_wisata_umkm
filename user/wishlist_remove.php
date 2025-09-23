<?php
session_start();
require_once '../config/database.php';

// Pastikan tidak ada output sebelum header
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

// Validasi login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

// Validasi method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

// Validasi input
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

if (!$id || !$type) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid']);
    exit;
}

$user_id = $_SESSION['user']['id'];

try {
    // Pastikan koneksi database valid
    if (!$pdo) {
        throw new Exception('Koneksi database gagal');
    }

    // Pastikan item yang dihapus milik user yang login
    $stmt = $pdo->prepare("DELETE FROM wishlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Berhasil dihapus dari wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan di wishlist atau sudah dihapus']);
    }
} catch (Exception $e) {
    error_log('Error in wishlist_remove.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}

exit;