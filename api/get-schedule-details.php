<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $scheduleId = $_GET['id'] ?? 0;

    if (!$scheduleId) {
        echo json_encode(['success' => false, 'message' => 'ID jadwal tidak valid']);
        exit;
    }

    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Ambil detail jadwal
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            p.name as pet_name,
            DATE(s.schedule_time) as date,
            TIME(s.schedule_time) as schedule_time_only
        FROM schedules s
        JOIN pets p ON s.pet_id = p.id
        WHERE s.id = ? AND s.user_id = ?
    ");
    
    $stmt->execute([$scheduleId, $userId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
        exit;
    }

    // Format data untuk form
    $schedule['schedule_time'] = $schedule['schedule_time_only'];

    echo json_encode([
        'success' => true,
        'schedule' => $schedule
    ]);

} catch (Exception $e) {
    error_log("Error in get-schedule-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil detail jadwal'
    ]);
}
?>
