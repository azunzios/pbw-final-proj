<?php
// php/login_handler.php

session_start(); // Mulai session untuk menyimpan status login
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=Username dan password tidak boleh kosong");
        exit();
    }

    // Ambil data user dari database
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $user['password_hash'])) {
            // Password benar, simpan user ID di session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            
            // Arahkan ke halaman utama/beranda
            header("Location: ../index.php");
            exit();
        } else {
            header("Location: ../login.php?error=Username atau password salah");
            exit();
        }
    } else {
        header("Location: ../login.php?error=Username atau password salah");
        exit();
    }
     
    $stmt->close();
    $conn->close();

}
?>