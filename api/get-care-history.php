<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check authentication for API
if (!checkApiAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Get filter parameters
    $petId = $_GET['pet_id'] ?? '';
    $careType = $_GET['care_type'] ?? '';
    $status = $_GET['status'] ?? '';
    $days = intval($_GET['days'] ?? 0);
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    // Build WHERE clause
    $whereConditions = ['s.user_id = ?'];
    $params = [$userId];

    // Pet filter
    if (!empty($petId)) {
        $whereConditions[] = 's.pet_id = ?';
        $params[] = $petId;
    }

    // Care type filter
    if (!empty($careType)) {
        $whereConditions[] = 's.care_type = ?';
        $params[] = $careType;
    }

    // Date range filter
    if (!empty($startDate) && !empty($endDate)) {
        // Custom date range
        $whereConditions[] = 'DATE(s.schedule_time) BETWEEN ? AND ?';
        $params[] = $startDate;
        $params[] = $endDate;
    } elseif ($days > 0) {
        // Predefined days filter
        $whereConditions[] = 's.schedule_time >= DATE_SUB(NOW(), INTERVAL ? DAY)';
        $params[] = $days;
    }
    // If days is 0 and no custom date, show all records (no date filter)

    // Status filter
    $statusCondition = '';
    if (!empty($status)) {
        switch ($status) {
            case 'completed':
                $statusCondition = 'AND s.completed_at IS NOT NULL';
                break;
            case 'missed':
                $statusCondition = 'AND s.completed_at IS NULL AND s.schedule_time < NOW()';
                break;
            case 'upcoming':
                $statusCondition = 'AND s.completed_at IS NULL AND s.schedule_time >= NOW()';
                break;
        }
    }

    $whereClause = implode(' AND ', $whereConditions);
    
    // Main query to get care history
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.care_type,
            s.description,
            s.schedule_time,
            s.completed_at,
            p.name as pet_name,
            p.type as pet_type,
            p.image_path as pet_photo,
            CASE 
                WHEN s.completed_at IS NOT NULL THEN 'completed'
                WHEN s.schedule_time < NOW() THEN 'missed'
                ELSE 'upcoming'
            END as status,
            DATE(s.schedule_time) as schedule_date,
            TIME(s.schedule_time) as schedule_time_only
        FROM schedules s
        JOIN pets p ON s.pet_id = p.id
        WHERE {$whereClause} {$statusCondition}
        ORDER BY s.schedule_time DESC
        LIMIT 100
    ");
    
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the data for frontend
    $careHistory = [];
    foreach ($schedules as $schedule) {
        // Get pet emoji based on type
        $petEmoji = getPetEmoji($schedule['pet_type']);
        
        // Format dates
        $scheduleDateTime = new DateTime($schedule['schedule_time']);
        $formattedDate = $scheduleDateTime->format('d M Y');
        $formattedTime = $scheduleDateTime->format('H:i');
        
        $careHistory[] = [
            'id' => $schedule['id'],
            'care_type' => $schedule['care_type'],
            'description' => $schedule['description'] ?: 'Tidak ada deskripsi',
            'pet_name' => $schedule['pet_name'],
            'pet_type' => $schedule['pet_type'],
            'pet_photo' => $schedule['pet_photo'],
            'pet_emoji' => $petEmoji,
            'status' => $schedule['status'],
            'schedule_time' => $schedule['schedule_time'],
            'schedule_datetime' => $schedule['schedule_time'], // Add this for compatibility
            'completed_at' => $schedule['completed_at'],
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'schedule_date' => $schedule['schedule_date'],
            'schedule_time_only' => $schedule['schedule_time_only']
        ];
    }

    // Get statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN s.completed_at IS NOT NULL THEN 1 END) as completed,
            COUNT(CASE WHEN s.completed_at IS NULL AND s.schedule_time < NOW() THEN 1 END) as missed,
            COUNT(CASE WHEN s.completed_at IS NULL AND s.schedule_time >= NOW() THEN 1 END) as upcoming
        FROM schedules s
        WHERE {$whereClause} {$statusCondition}
    ");
    
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $careHistory,
        'stats' => $stats,
        'total_records' => count($careHistory)
    ]);

} catch (Exception $e) {
    error_log("Error in get-care-history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat riwayat perawatan'.$e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

function getPetEmoji($petType) {
    $emojis = [
        'Anjing' => '🐕',
        'Kucing' => '🐱',
        'Burung' => '🐦',
        'Ikan' => '🐠',
        'Kelinci' => '🐰',
        'Hamster' => '🐹',
        'Iguana' => '🦎',
        'Kura-kura' => '🐢',
        'Ular' => '🐍',
        'Reptil' => '🦎',
        'Amfibi' => '🐸',
        'Serangga' => '🐛',
        'Tarantula' => '🕷️',
        'Laba-laba' => '🕷️'
    ];
    
    return $emojis[$petType] ?? '🐾';
}
?>
