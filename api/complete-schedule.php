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

$instance_id = intval($_POST['instance_id'] ?? 0);

if (!$instance_id) {
    echo json_encode(['success' => false, 'message' => 'ID instance tidak valid']);
    exit;
}

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    
    // Verifikasi bahwa schedule instance ini milik user yang login
    $stmt = $pdo->prepare("SELECT si.*, s.user_id 
                          FROM schedule_instances si 
                          JOIN schedules s ON si.schedule_id = s.id 
                          WHERE si.id = ?");
    $stmt->execute([$instance_id]);
    $instance = $stmt->fetch();
    
    if (!$instance) {
        echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
        exit;
    }
    
    if ($instance['user_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses']);
        exit;
    }
    
    if ($instance['is_done']) {
        echo json_encode(['success' => false, 'message' => 'Jadwal sudah selesai']);
        exit;
    }
    
    // Update schedule instance
    $stmt = $pdo->prepare("UPDATE schedule_instances 
                          SET is_done = 1, done_at = NOW() 
                          WHERE id = ?");
    $stmt->execute([$instance_id]);
    
    // Tambah ke care logs
    $stmt = $pdo->prepare("INSERT INTO care_logs (user_id, pet_id, schedule_id, care_type, done_by) 
                          SELECT s.user_id, s.pet_id, s.id, s.care_type, ? 
                          FROM schedules s 
                          WHERE s.id = ?");
    $stmt->execute([$user['full_name'], $instance['schedule_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil diselesaikan']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>
