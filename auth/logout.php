<?php
session_start();
require_once __DIR__.'/../config/database.php';

// Hapus remember token dari database jika ada
if (isset($_COOKIE['remember_token'])) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (Exception $e) {
        // Ignore error
    }
    
    // Hapus cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

session_destroy();
header('Location: ../index.php');
exit;
?>
