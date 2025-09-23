<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? null;
    
    if (!$id || !$type) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    $user_id = $_SESSION['user']['id'];
    
    try {
        // Cek apakah item sudah ada di wishlist
        $stmt = $pdo->prepare("SELECT * FROM wishlists WHERE user_id = ? AND item_id = ? AND item_type = ?");
        $stmt->execute([$user_id, $id, $type]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Item sudah ada di wishlist']);
            exit;
        }
        
        // Tambahkan ke wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, item_id, item_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $id, $type]);
        
        echo json_encode(['success' => true, 'message' => 'Item berhasil ditambahkan ke wishlist']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
}