<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check authentication untuk API (tidak melakukan redirect)
if (!checkApiAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Get date range and validate format
    $startDate = isset($_GET['start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start']) 
        ? $_GET['start'] 
        : date('Y-m-d');
    
    $endDate = isset($_GET['end']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end']) 
        ? $_GET['end'] 
        : date('Y-m-d', strtotime('+6 days'));

    // Query untuk mengambil schedule history dalam rentang tanggal (only completed schedules)
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.care_type,
            s.start_date as date,
            s.schedule_time,
            s.description,
            s.pet_id,
            p.name as pet_name,
            CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END as is_done
        FROM schedules s
        JOIN pets p ON s.pet_id = p.id
        WHERE s.user_id = ? 
            AND s.start_date BETWEEN ? AND ?
            AND s.is_active = 0
        ORDER BY s.start_date ASC, s.schedule_time ASC
    ");
    
    $stmt->execute([$userId, $startDate, $endDate]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $schedules
    ]);

} catch (Exception $e) {
    error_log("Error in get-schedule-history.php: " . $e->getMessage() . " at line " . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
        'instances' => $instances
    ]);

} catch (Exception $e) {
    error_log("Error in get-schedule-history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil history jadwal: ' . $e->getMessage()
    ]);
}
?>
