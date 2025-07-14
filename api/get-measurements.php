<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../utils/timezone.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit();
}

$user = getCurrentUser();
if (!$user['id']) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit();
}
$petId = $_GET['pet_id'] ?? null;
$sortBy = $_GET['sort'] ?? 'date_desc';

if (!$petId) {
    echo json_encode(['success' => false, 'message' => 'Pet ID required']);
    exit();
}

try {
    $pdo = connectDB();
    
    // Verify pet belongs to user
    $petCheckStmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $petCheckStmt->execute([$petId, $user['id']]);
    
    if (!$petCheckStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Pet not found']);
        exit();
    }
    
    // Build sort clause
    $orderClause = 'recorded_at DESC';
    switch ($sortBy) {
        case 'date_asc':
            $orderClause = 'recorded_at ASC';
            break;
        case 'weight_desc':
            $orderClause = 'weight DESC, recorded_at DESC';
            break;
        case 'weight_asc':
            $orderClause = 'weight ASC, recorded_at DESC';
            break;
        case 'length_desc':
            $orderClause = 'length DESC, recorded_at DESC';
            break;
        case 'length_asc':
            $orderClause = 'length ASC, recorded_at DESC';
            break;
        default:
            $orderClause = 'recorded_at DESC';
    }
    
    // Get measurements
    $stmt = $pdo->prepare("
        SELECT 
            id,
            pet_id,
            recorded_at,
            weight,
            length,
            notes
        FROM pet_measurements 
        WHERE pet_id = ? 
        ORDER BY {$orderClause}
    ");
    
    $stmt->execute([$petId]);
    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'measurements' => $measurements
    ]);
    
} catch (Exception $e) {
    error_log("Error getting measurements: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
