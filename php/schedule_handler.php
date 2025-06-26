<?php
/**
 * schedule_handler.php
 * File ini berfungsi sebagai pusat kontrol untuk semua operasi CRUD (Create, Read, Update, Delete)
 * yang berkaitan dengan jadwal. Ia menerima 'action' dari form dan mengeksekusi logika yang sesuai.
 */

session_start();
require 'db_connection.php';

// Keamanan Tingkat 1: Pastikan pengguna sudah login sebelum melakukan aksi apapun.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Keamanan Tingkat 2: Pastikan request datang dari form (method POST) dan memiliki 'action'.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $action = $_POST['action'];
    $program_id = $_SESSION['active_program_id']; // Ambil program_id yang aktif dari session.

    // =================================================================
    // ACTION: MEMBUAT JADWAL BARU (DARI buat_jadwal_baru.php)
    // =================================================================
    if ($action == 'create') {
        $title = $_POST['agenda_name'];
        $pet_id = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : NULL;
        $label = $_POST['label'];
        $is_routine = isset($_POST['is_routine']);

        $stmt = $conn->prepare("INSERT INTO schedules (program_id, title, pet_id, schedule_time, type, label) VALUES (?, ?, ?, ?, ?, ?)");

        if (!$is_routine) {
            // --- LOGIKA UNTUK JADWAL SEKALI (TIDAK RUTIN) ---
            $schedule_datetime = $_POST['schedule_date'] . ' ' . $_POST['schedule_time'];
            $type = 'sekali';
            
            $stmt->bind_param("isssss", $program_id, $title, $pet_id, $schedule_datetime, $type, $label);
            $stmt->execute();

        } else {
            // --- LOGIKA UNTUK JADWAL RUTIN ---
            $routine_type = $_POST['routine_type'];
            $time = $_POST['routine_time'];
            
            // Generate instance jadwal untuk 90 hari ke depan untuk efisiensi query.
            $start_date = new DateTime();
            $end_date = (new DateTime())->modify('+90 days');
            $interval = new DateInterval('P1D'); // Interval 1 hari.
            $period = new DatePeriod($start_date, $interval, $end_date);

            foreach ($period as $date) {
                $match = false;
                if ($routine_type == 'harian') {
                    $match = true;
                } elseif ($routine_type == 'mingguan') {
                    // 'N' mengembalikan 1 untuk Senin s/d 7 untuk Minggu.
                    if ($date->format('N') == $_POST['day']) {
                        $match = true;
                    }
                } elseif ($routine_type == 'bulanan') {
                    // 'j' mengembalikan tanggal (1-31).
                    if ($date->format('j') == $_POST['date']) {
                        $match = true;
                    }
                }
                
                if ($match) {
                    $schedule_datetime = $date->format('Y-m-d') . ' ' . $time;
                    $stmt->bind_param("isssss", $program_id, $title, $pet_id, $schedule_datetime, $routine_type, $label);
                    $stmt->execute();
                }
            }
        }
        $stmt->close();
        header('Location: ../jadwal_lengkap.php'); // Arahkan ke kalender setelah berhasil.
        exit();
    }

    // =================================================================
    // ACTION: MENGUBAH JADWAL (DARI edit_jadwal.php)
    // =================================================================
    if ($action == 'update') {
        $schedule_id = (int)$_POST['schedule_id'];
        $title = $_POST['title'];
        $pet_id = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : NULL;
        $time = $_POST['schedule_time'];
        $label = $_POST['label'];

        // Ambil tanggal dari jadwal yang ada, karena kita hanya mengizinkan perubahan waktu, bukan tanggal.
        $stmt_date = $conn->prepare("SELECT schedule_time FROM schedules WHERE id = ?");
        $stmt_date->bind_param("i", $schedule_id);
        $stmt_date->execute();
        $old_schedule_time = new DateTime($stmt_date->get_result()->fetch_assoc()['schedule_time']);
        $new_datetime = $old_schedule_time->format('Y-m-d') . ' ' . $time;
        $stmt_date->close();

        $stmt = $conn->prepare("UPDATE schedules SET title = ?, pet_id = ?, schedule_time = ?, label = ? WHERE id = ?");
        $stmt->bind_param("sissi", $title, $pet_id, $new_datetime, $label, $schedule_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ../jadwal_lengkap.php'); // Arahkan ke kalender setelah berhasil.
            exit();
        } else {
            echo "Error saat mengupdate: " . $stmt->error;
        }
    }

    // =================================================================
    // ACTION: MENGHAPUS JADWAL (DARI jadwal_lengkap.php)
    // =================================================================
    if ($action == 'delete') {
        $schedule_id = (int)$_POST['schedule_id'];
        
        // Sebelum menghapus, kita bisa menambahkan pengecekan apakah jadwal ini milik program user.
        // Namun untuk saat ini kita asumsikan user hanya melihat jadwal miliknya.
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Kembali ke halaman tempat user menekan tombol hapus.
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            echo "Error saat menghapus: " . $stmt->error;
        }
    }

} else {
    // Jika file ini diakses secara langsung tanpa method POST atau action,
    // kembalikan saja ke halaman utama.
    header('Location: ../index.php');
    exit();
}

// Tutup koneksi database di akhir script.
$conn->close();

?>