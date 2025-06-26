<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $program_id = $_SESSION['active_program_id'];
    $name = $_POST['name'];
    $notes = $_POST['notes'];
    
    // Fungsi untuk handle upload foto
    function handle_photo_upload($file_input_name) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
            $target_dir = "../uploads/pets/";
            $filename = uniqid() . '_' . basename($_FILES[$file_input_name]["name"]);
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
                return 'uploads/pets/' . $filename; // Return path yang akan disimpan di DB
            }
        }
        return null; // Return null jika tidak ada file atau error
    }

    // --- LOGIKA CREATE ---
    if ($_POST['action'] == 'create') {
        $photo_path = handle_photo_upload('photo');
        
        $stmt = $conn->prepare("INSERT INTO pets (program_id, name, notes, photo_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $program_id, $name, $notes, $photo_path);
        
        if ($stmt->execute()) {
            header('Location: ../lihat_peliharaan.php');
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // --- LOGIKA UPDATE ---
    if ($_POST['action'] == 'update') {
        $pet_id = (int)$_POST['pet_id'];
        
        // Cek apakah ada foto baru yang diupload
        $new_photo_path = handle_photo_upload('photo');
        
        if ($new_photo_path) {
            // Jika ada foto baru, update path fotonya
            $stmt = $conn->prepare("UPDATE pets SET name = ?, notes = ?, photo_path = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $notes, $new_photo_path, $pet_id);
        } else {
            // Jika tidak ada foto baru, jangan update kolom photo_path
            $stmt = $conn->prepare("UPDATE pets SET name = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $notes, $pet_id);
        }
        
        if ($stmt->execute()) {
            header('Location: ../manajemen_pet.php?id=' . $pet_id . '&success=1');
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>