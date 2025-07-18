<?php
header('Content-Type: application/json');
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/database.php';

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $scheduleId = $input['id'] ?? 0;

    if (!$scheduleId) {
        echo json_encode(['success' => false, 'message' => 'ID jadwal tidak valid']);
        exit;
    }

    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Cek apakah jadwal milik user
    $stmt = $pdo->prepare("SELECT id FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->execute([$scheduleId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
        exit;
    }

    // Hapus jadwal (simplified, no schedule_instances to worry about)
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->execute([$scheduleId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);

} catch (Exception $e) {
    error_log("Error in delete-schedule.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
