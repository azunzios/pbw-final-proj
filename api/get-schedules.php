<?php
header('Content-Type: application/json');
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/database.php';

// Check authentication untuk API
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

    // Check if getting specific schedule by ID
    $scheduleId = $_GET['id'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    if (!empty($scheduleId)) {
        // Get specific schedule
        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.care_type,
                TIME(s.schedule_time) as schedule_time,
                DATE(s.schedule_time) as date,
                s.pet_id,
                p.name as pet_name,
                CASE 
                    WHEN s.completed_at IS NOT NULL THEN 1
                    ELSE 0
                END as is_done
            FROM schedules s
            JOIN pets p ON s.pet_id = p.id
            WHERE s.id = ? AND s.user_id = ? AND s.is_active = 1
        ");
        $stmt->execute([$scheduleId, $userId]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Get schedules for date range
        $whereClause = "s.user_id = ? AND s.is_active = 1";
        $params = [$userId];
        
        if (!empty($startDate) && !empty($endDate)) {
            $whereClause .= " AND DATE(s.schedule_time) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.care_type,
                TIME(s.schedule_time) as schedule_time,
                DATE(s.schedule_time) as date,
                s.description,
                s.pet_id,
                p.name as pet_name,
                CASE 
                    WHEN s.completed_at IS NOT NULL THEN 1
                    ELSE 0
                END as is_done
            FROM schedules s
            JOIN pets p ON s.pet_id = p.id
            WHERE {$whereClause}
            ORDER BY s.schedule_time ASC
        ");
        
        $stmt->execute($params);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'data' => $schedules
    ]);

} catch (Exception $e) {
    error_log("Error in get-schedules.php: " . $e->getMessage() . " at line " . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>