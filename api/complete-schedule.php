<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$schedule_id = intval($input['schedule_id'] ?? 0);

if (!$schedule_id) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap. Perlu schedule_id']);
    exit;
}

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    
    // Verifikasi schedule milik user
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ? AND user_id = ? AND is_active = 1");
    $stmt->execute([$schedule_id, $user['id']]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan atau tidak valid']);
        exit;
    }
    
    // Check if already completed
    if ($schedule['completed_at']) {
        echo json_encode(['success' => false, 'message' => 'Jadwal sudah diselesaikan sebelumnya']);
        exit;
    }
    
    // Mark as completed by adding completed timestamp
    $stmt = $pdo->prepare("UPDATE schedules SET completed_at = NOW() WHERE id = ?");
    $stmt->execute([$schedule_id]);
    
    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil diselesaikan']);
    
} catch (Exception $e) {
    error_log("Error in complete-schedule.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>