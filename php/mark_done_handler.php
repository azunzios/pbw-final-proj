<?php
// php/mark_done_handler.php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {

    $schedule_id = (int)$_POST['schedule_id'];
    $user_id = $_SESSION['user_id'];
    $redirect_url = $_POST['redirect_url'] ?? '../jadwal_lengkap.php';
    
    $photo_path = null;

    // --- LOGIKA UPLOAD FOTO ---
    if (isset($_FILES['done_photo']) && $_FILES['done_photo']['error'] == 0) {
        $target_dir = "../uploads/activities/";
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = $schedule_id . '_' . uniqid() . '_' . basename($_FILES['done_photo']["name"]);
        $target_file = $target_dir . $filename;
        
        // Coba pindahkan file yang diupload
        if (move_uploaded_file($_FILES['done_photo']["tmp_name"], $target_file)) {
            $photo_path = 'uploads/activities/' . $filename;
        }
    }
    
    // --- LOGIKA UPDATE DATABASE ---
    $sql = "UPDATE schedules 
            SET is_done = 1, 
                done_by_user_id = ?, 
                done_at = NOW(), 
                done_photo_path = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $user_id, $photo_path, $schedule_id);
    
    if ($stmt->execute()) {
        // Berhasil, kembali ke halaman sebelumnya
        header('Location: ' . '..' . $redirect_url);
        exit();
    } else {
        // Gagal
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    // Jika akses tidak sah, kembalikan ke beranda
    header('Location: ../index.php');
    exit();
}