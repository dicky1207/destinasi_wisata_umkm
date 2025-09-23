<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Konfirmasi password tidak sesuai";
        header('Location: register.php');
        exit;
    }

    // Cek apakah email sudah terdaftar
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email sudah terdaftar";
        header('Location: register.php');
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Simpan user baru
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    if ($stmt->execute([$name, $email, $hashed_password])) {
        $_SESSION['success'] = "Pendaftaran berhasil. Silakan login.";
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
        header('Location: register.php');
        exit;
    }
}
?>