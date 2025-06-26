<?php
// php/program_handler.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require 'db_connection.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    if ($_POST['action'] == 'create') {
        $program_name = $_POST['program_name'];
        $alias = $_POST['alias'];
        $description = $_POST['description'];
        
        // Buat kode gabung yang unik
        $join_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);

        // Masukkan program baru ke tabel 'programs'
        $stmt = $conn->prepare("INSERT INTO programs (program_name, description, owner_user_id, join_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $program_name, $description, $user_id, $join_code);
        
        if ($stmt->execute()) {
            $program_id = $conn->insert_id; // Ambil ID dari program yang baru dibuat
            
            // Masukkan pembuat sebagai anggota pertama di 'program_members'
            $stmt_member = $conn->prepare("INSERT INTO program_members (user_id, program_id, alias) VALUES (?, ?, ?)");
            $stmt_member->bind_param("iis", $user_id, $program_id, $alias);
            $stmt_member->execute();
            $stmt_member->close();

            // Arahkan kembali ke beranda, yang sekarang akan menampilkan dasbor
            header('Location: ../index.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Nanti kita tambahkan logika untuk 'join' di sini
     // --- LOGIKA KELUAR DARI PROGRAM ---
    if ($_POST['action'] == 'leave_program') {
        $program_id = (int)$_POST['program_id'];
        $stmt = $conn->prepare("DELETE FROM program_members WHERE user_id = ? AND program_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $program_id);
        $stmt->execute();
        header('Location: ../atur_program.php');
    }

    // --- LOGIKA UPDATE DETAIL PROGRAM ---
    if ($_POST['action'] == 'update_details') {
        $program_id = (int)$_POST['program_id'];
        $program_name = $_POST['program_name'];
        $description = $_POST['description'];
        $stmt = $conn->prepare("UPDATE programs SET program_name = ?, description = ? WHERE id = ? AND owner_user_id = ?");
        $stmt->bind_param("ssii", $program_name, $description, $program_id, $_SESSION['user_id']);
        $stmt->execute();
        header('Location: ../edit_program.php?id=' . $program_id);
    }
    
    // --- LOGIKA MENGHAPUS ANGGOTA ---
    if ($_POST['action'] == 'remove_member') {
        $program_id = (int)$_POST['program_id'];
        $user_id_to_remove = (int)$_POST['user_id_to_remove'];
        // Keamanan tambahan: Cek lagi apa user adalah owner
        $stmt_owner = $conn->prepare("SELECT id FROM programs WHERE id = ? AND owner_user_id = ?");
        $stmt_owner->bind_param("ii", $program_id, $_SESSION['user_id']);
        $stmt_owner->execute();
        if ($stmt_owner->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM program_members WHERE user_id = ? AND program_id = ?");
            $stmt->bind_param("ii", $user_id_to_remove, $program_id);
            $stmt->execute();
        }
        header('Location: ../edit_program.php?id=' . $program_id);
    }

    // --- LOGIKA MENGHAPUS SELURUH PROGRAM ---
    if ($_POST['action'] == 'delete_program') {
        $program_id = (int)$_POST['program_id'];
        $stmt = $conn->prepare("DELETE FROM programs WHERE id = ? AND owner_user_id = ?");
        $stmt->bind_param("ii", $program_id, $_SESSION['user_id']);
        $stmt->execute();
        // Karena kita set ON DELETE CASCADE di database,
        // semua data di program_members, pets, dan schedules
        // yang terkait dengan program_id ini akan otomatis terhapus.
        header('Location: ../index.php');
    }
}

$conn->close();
?>