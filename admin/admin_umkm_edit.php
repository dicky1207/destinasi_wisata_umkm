<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
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

    // Query untuk mengupdate UMKM
    $stmt = $pdo->prepare("UPDATE umkms SET name = ?, description = ?, category = ?, image = ?, rating = ? WHERE id = ?");
    try {
        $stmt->execute([$name, $description, $category, $image, $rating, $id]);
        $success = "UMKM berhasil diupdate!";
        header("Location: admin_umkm.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Gagal mengupdate UMKM: " . $e->getMessage();
        header("Location: admin_umkm.php?error=" . urlencode($error));
        exit;
    }
} else {
    header('Location: admin_umkm.php');
    exit;
}