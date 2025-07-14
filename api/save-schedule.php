<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Ambil data dari form
    $scheduleId = $_POST['scheduleId'] ?? '';
    $petId = $_POST['pet_id'] ?? '';
    $careType = $_POST['care_type'] ?? '';
    $scheduleTime = $_POST['schedule_time'] ?? '';
    $scheduleDate = $_POST['schedule_date'] ?? '';
    $recurrence = $_POST['recurrence'] ?? 'Once';
    $days = $_POST['days'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validasi input berdasarkan jenis pengulangan
    if (empty($petId) || empty($careType) || empty($scheduleTime)) {
        echo json_encode(['success' => false, 'message' => 'Data peliharaan, jenis perawatan, dan waktu wajib diisi']);
        exit;
    }
    
    // Validasi tambahan berdasarkan jenis pengulangan
    if (($recurrence === 'Once' || $recurrence === 'Monthly') && empty($scheduleDate)) {
        echo json_encode(['success' => false, 'message' => 'Tanggal wajib diisi untuk jadwal sekali atau bulanan']);
        exit;
    }
    
    if ($recurrence === 'Weekly' && empty($days)) {
        echo json_encode(['success' => false, 'message' => 'Pilih setidaknya satu hari untuk jadwal mingguan']);
        exit;
    }

    // Cek apakah pet milik user
    $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$petId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Peliharaan tidak ditemukan']);
        exit;
    }

    // Process days for weekly recurrence
    if ($recurrence === 'Weekly' && isset($_POST['days'])) {
        if (is_array($_POST['days'])) {
            $days = implode(',', $_POST['days']);
        } else {
            $days = $_POST['days'];
        }
    }
    
    if (empty($scheduleId)) {
        // Tambah jadwal baru
        $stmt = $pdo->prepare("
            INSERT INTO schedules (user_id, pet_id, care_type, schedule_time, days, recurrence, notes, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        // Handle different recurrence types
        switch ($recurrence) {
            case 'Once':
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                // Untuk jadwal sekali, days kosong
                $days = '';
                break;
                
            case 'Daily':
                // Untuk jadwal harian, gunakan tanggal hari ini untuk memulai
                $scheduleDateTime = date('Y-m-d') . ' ' . $scheduleTime;
                $days = 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu';
                break;
                
            case 'Weekly':
                // Days already processed above
                $scheduleDateTime = date('Y-m-d') . ' ' . $scheduleTime;
                break;
                
            case 'Monthly':
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                // Untuk jadwal bulanan, days kosong karena berdasarkan tanggal
                $days = '';
                break;
                
            default:
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                $days = '';
        }
        
        // Ensure the time is properly formatted for MySQL
        $formattedDateTime = date('Y-m-d H:i:s', strtotime($scheduleDateTime));
        
        $stmt->execute([$userId, $petId, $careType, $formattedDateTime, $days, $recurrence, $notes]);
        $scheduleId = $pdo->lastInsertId();

        // Jika jadwal sekali, langsung buat instance
        if ($recurrence === 'Once') {
            $instanceStmt = $pdo->prepare("
                INSERT INTO schedule_instances (schedule_id, date, is_done) 
                VALUES (?, ?, 0)
            ");
            $instanceStmt->execute([$scheduleId, $scheduleDate]);
        }

        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil ditambahkan']);
    } else {
        // Update jadwal yang ada
        $stmt = $pdo->prepare("
            UPDATE schedules 
            SET pet_id = ?, care_type = ?, schedule_time = ?, days = ?, recurrence = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ");
        
        // Handle different recurrence types
        switch ($recurrence) {
            case 'Once':
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                // Untuk jadwal sekali, days kosong
                $days = '';
                break;
                
            case 'Daily':
                // Untuk jadwal harian, gunakan tanggal hari ini untuk memulai
                $scheduleDateTime = date('Y-m-d') . ' ' . $scheduleTime;
                $days = 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu';
                break;
                
            case 'Weekly':
                // Untuk jadwal mingguan, gunakan tanggal hari ini untuk memulai
                $scheduleDateTime = date('Y-m-d') . ' ' . $scheduleTime;
                // $days sudah diambil dari formulir
                break;
                
            case 'Monthly':
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                // Untuk jadwal bulanan, days kosong karena berdasarkan tanggal
                $days = '';
                break;
                
            default:
                $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
                $days = '';
        }
        
        // Ensure the time is properly formatted for MySQL
        $formattedDateTime = date('Y-m-d H:i:s', strtotime($scheduleDateTime));
        
        $stmt->execute([$petId, $careType, $formattedDateTime, $days, $recurrence, $notes, $scheduleId, $userId]);

        // Update atau buat instance jika perlu
        if ($recurrence === 'Once') {
            // Hapus instance lama dan buat yang baru
            $deleteStmt = $pdo->prepare("DELETE FROM schedule_instances WHERE schedule_id = ?");
            $deleteStmt->execute([$scheduleId]);
            
            $instanceStmt = $pdo->prepare("
                INSERT INTO schedule_instances (schedule_id, date, is_done) 
                VALUES (?, ?, 0)
            ");
            $instanceStmt->execute([$scheduleId, $scheduleDate]);
        }

        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil diperbarui']);
    }

} catch (Exception $e) {
    error_log("Error in save-schedule.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyimpan jadwal'
    ]);
}

// Fungsi getDaysForRecurrence sudah tidak dibutuhkan lagi karena days dikelola langsung di switch case
?>
