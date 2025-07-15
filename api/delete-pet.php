<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID peliharaan tidak valid']);
    exit;
}

try {
    $db = connectDB();
    $user_id = $_SESSION['user_id'];
    $pet_id = intval($input['id']);
    
    // First, get the pet data to check ownership and get photo path
    $checkQuery = "SELECT image_path FROM pets WHERE id = :id AND user_id = :user_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $pet_id, PDO::PARAM_INT);
    $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    $pet = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        echo json_encode(['success' => false, 'message' => 'Peliharaan tidak ditemukan atau tidak memiliki akses']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete related records first (schedules and measurements)
        // Note: No need to delete care_logs or schedule_instances since we removed those features
        
        // 1. Delete schedules
        $deleteSchedulesQuery = "DELETE FROM schedules WHERE pet_id = :pet_id";
        $deleteSchedulesStmt = $db->prepare($deleteSchedulesQuery);
        $deleteSchedulesStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
        $deleteSchedulesStmt->execute();
        
        // 2. Delete measurements
        $deleteMeasurementsQuery = "DELETE FROM pet_measurements WHERE pet_id = :pet_id";
        $deleteMeasurementsStmt = $db->prepare($deleteMeasurementsQuery);
        $deleteMeasurementsStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
        $deleteMeasurementsStmt->execute();
        
        // 3. Finally delete the pet
        $deletePetQuery = "DELETE FROM pets WHERE id = :id AND user_id = :user_id";
        $deletePetStmt = $db->prepare($deletePetQuery);
        $deletePetStmt->bindParam(':id', $pet_id, PDO::PARAM_INT);
        $deletePetStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $deletePetStmt->execute();
        
        // Commit transaction
        $db->commit();
        
        // Delete photo file if it exists
        if ($pet['image_path']) {
            $photoPath = '../uploads/pets/' . $pet['image_path'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Peliharaan berhasil dihapus'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
