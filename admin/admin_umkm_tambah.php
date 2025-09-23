<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $image = $_POST['image'];
    $rating = $_POST['rating'];

    // Validasi sederhana
    if (empty($name) || empty($category)) {
        $error = "Nama dan kategori harus diisi!";
        header("Location: admin_umkm.php?error=" . urlencode($error));
        exit;
    }

    // Query untuk menambahkan UMKM
    $stmt = $pdo->prepare("INSERT INTO umkms (name, description, category, image, rating) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $description, $category, $image, $rating]);
        $success = "UMKM berhasil ditambahkan!";
        header("Location: admin_umkm.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menambahkan UMKM: " . $e->getMessage();
        header("Location: admin_umkm.php?error=" . urlencode($error));
        exit;
    }
} else {
    header('Location: admin_umkm.php');
    exit;
}