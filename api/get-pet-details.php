<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID diperlukan']);
    exit;
}

try {
    $db = connectDB();
    $user_id = $_SESSION['user_id'];
    $pet_id = intval($_GET['id']);
    
    // Get pet details
    $petQuery = "SELECT * FROM pets WHERE id = :pet_id AND user_id = :user_id";
    $petStmt = $db->prepare($petQuery);
    $petStmt->bindParam(':pet_id', $pet_id);
    $petStmt->bindParam(':user_id', $user_id);
    $petStmt->execute();
    
    $pet = $petStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        echo json_encode(['success' => false, 'message' => 'Pet not found']);
        exit;
    }
    
    // Get care history from completed schedules
    $careHistoryQuery = "
        SELECT 
            s.id,
            s.care_type,
            s.start_date as timestamp,
            s.description as notes,
            'System' as done_by,
            s.schedule_time
        FROM schedules s
        WHERE s.pet_id = :pet_id AND s.user_id = :user_id AND s.is_active = 0
        ORDER BY s.start_date DESC, s.schedule_time DESC
        LIMIT 20
    ";
    
    $careStmt = $db->prepare($careHistoryQuery);
    $careStmt->bindParam(':pet_id', $pet_id);
    $careStmt->bindParam(':user_id', $user_id);
    $careStmt->execute();
    
    $careHistory = $careStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent measurements
    $measurementQuery = "
        SELECT 
            pm.id,
            pm.recorded_at,
            pm.weight,
            pm.length,
            pm.notes
        FROM pet_measurements pm
        WHERE pm.pet_id = :pet_id
        ORDER BY pm.recorded_at DESC
        LIMIT 10
    ";
    
    $measurementStmt = $db->prepare($measurementQuery);
    $measurementStmt->bindParam(':pet_id', $pet_id);
    $measurementStmt->execute();
    
    $measurements = $measurementStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming schedules (simplified for once-only schedules)
    $scheduleQuery = "
        SELECT 
            s.id,
            s.care_type,
            s.schedule_time,
            s.start_date,
            s.description,
            CASE 
                WHEN s.start_date < CURDATE() OR (s.start_date = CURDATE() AND s.schedule_time < CURTIME()) THEN 1
                ELSE 0
            END as is_done
        FROM schedules s
        WHERE s.pet_id = :pet_id AND s.user_id = :user_id AND s.is_active = 1
        AND s.start_date >= CURDATE()
        ORDER BY s.start_date ASC, s.schedule_time ASC
        LIMIT 5
    ";
    
    $scheduleStmt = $db->prepare($scheduleQuery);
    $scheduleStmt->bindParam(':pet_id', $pet_id);
    $scheduleStmt->bindParam(':user_id', $user_id);
    $scheduleStmt->execute();
    
    $upcomingSchedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate age
    $age = '';
    if ($pet['birth_date']) {
        $birthDate = new DateTime($pet['birth_date']);
        $now = new DateTime();
        $ageInterval = $now->diff($birthDate);
        
        if ($ageInterval->y > 0) {
            $age = $ageInterval->y . ' tahun';
            if ($ageInterval->m > 0) {
                $age .= ' ' . $ageInterval->m . ' bulan';
            }
        } elseif ($ageInterval->m > 0) {
            $age = $ageInterval->m . ' bulan';
        } else {
            $age = $ageInterval->d . ' hari';
        }
    } else {
        $age = 'Tidak diketahui';
    }
    
    $response = [
        'success' => true,
        'pet' => array_merge($pet, ['age' => $age]),
        'careHistory' => $careHistory,
        'measurements' => $measurements,
        'upcomingSchedules' => $upcomingSchedules,
        'stats' => [
            'totalCareActivities' => count($careHistory),
            'totalMeasurements' => count($measurements),
            'upcomingTasks' => count($upcomingSchedules)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
