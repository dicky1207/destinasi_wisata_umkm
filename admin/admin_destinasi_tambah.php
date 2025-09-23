<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $image = $_POST['image'];
    $rating = $_POST['rating'] ?? 0;

    $stmt = $pdo->prepare("INSERT INTO destinations (name, description, location, price, category, image, rating) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $location, $price, $category, $image, $rating])) {
        $success = "Destinasi berhasil ditambahkan";
        header('Location: admin_destinasi.php?success=' . urlencode($success));
    } else {
        $error = "Gagal menambah destinasi";
        header('Location: admin_destinasi.php?error=' . urlencode($error));
    }
    exit;
} else {
    header('Location: admin_destinasi.php');
    exit;
}
?>