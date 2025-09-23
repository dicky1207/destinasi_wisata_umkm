<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Anda harus login untuk memberikan ulasan.";
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $item_type = $_POST['item_type'];
    $item_id = $_POST['item_id'];
    $rating = floatval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Validasi
    if (!in_array($item_type, ['destination', 'umkm']) || !is_numeric($item_id) || $rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['error'] = "Data tidak valid.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Cek apakah user sudah pernah mereview item ini
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND {$item_type}_id = ?");
    $stmt_check->execute([$user_id, $item_id]);
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "Anda sudah memberikan ulasan untuk ini.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Insert review
    if ($item_type === 'destination') {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, destination_id, rating, comment, status) VALUES (?, ?, ?, ?, 'active')");
    } else {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, umkm_id, rating, comment, status) VALUES (?, ?, ?, ?, 'active')");
    }

    if ($stmt->execute([$user_id, $item_id, $rating, $comment])) {
        $_SESSION['success'] = "Ulasan berhasil dikirim.";
    } else {
        $_SESSION['error'] = "Gagal mengirim ulasan.";
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    header('Location: index.php');
    exit;
}