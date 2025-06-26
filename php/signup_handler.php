<?php
// php/signup_handler.php

require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi sederhana
    if (empty($username) || empty($password)) {
        header("Location: ../signup.php?error=Username dan password tidak boleh kosong");
        exit();
    }

    // Cek apakah username sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: ../signup.php?error=Username sudah digunakan");
        exit();
    }

    // Hash password untuk keamanan
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Masukkan user baru ke database
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password_hash);

    if ($stmt->execute()) {
        // Jika berhasil, arahkan ke halaman login
        header("Location: ../login.php");
        exit();
    } else {
        header("Location: ../signup.php?error=Terjadi kesalahan, silakan coba lagi");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>