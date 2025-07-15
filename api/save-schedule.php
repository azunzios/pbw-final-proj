<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// Check authentication for API (tidak melakukan redirect)
if (!checkApiAuth()) {
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
    $scheduleDate = $_POST['start_date'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validasi input
    if (empty($petId) || empty($careType) || empty($scheduleTime) || empty($scheduleDate)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
        exit;
    }

    // Cek apakah pet milik user
    $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$petId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Hewan peliharaan tidak ditemukan']);
        exit;
    }
    
    if (empty($scheduleId)) {
        // Tambah jadwal baru
        // Combine date and time into a single datetime value
        $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
        
        $stmt = $pdo->prepare("
            INSERT INTO schedules (user_id, pet_id, care_type, schedule_time, description, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $stmt->execute([$userId, $petId, $careType, $scheduleDateTime, $description]);
        $scheduleId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true, 
            'message' => 'Jadwal berhasil ditambahkan',
            'schedule_id' => $scheduleId
        ]);
    } else {
        // Update jadwal yang ada
        // Combine date and time into a single datetime value
        $scheduleDateTime = $scheduleDate . ' ' . $scheduleTime;
        
        $stmt = $pdo->prepare("
            UPDATE schedules 
            SET pet_id = ?, care_type = ?, schedule_time = ?, description = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([$petId, $careType, $scheduleDateTime, $description, $scheduleId, $userId]);

        echo json_encode([
            'success' => true, 
            'message' => 'Jadwal berhasil diperbarui'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in save-schedule.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyimpan jadwal'
    ]);
}
?>
