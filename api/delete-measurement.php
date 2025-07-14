<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user = getCurrentUser();
if (!$user['id']) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$measurementId = $input['measurement_id'] ?? null;

if (!$measurementId) {
    echo json_encode(['success' => false, 'message' => 'Measurement ID is required']);
    exit();
}

try {
    $pdo = connectDB();
    
    // Delete measurement with ownership verification
    $stmt = $pdo->prepare("
        DELETE pm FROM pet_measurements pm
        JOIN pets p ON pm.pet_id = p.id
        WHERE pm.id = ? AND p.user_id = ?
    ");
    
    $stmt->execute([$measurementId, $user['id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Measurement not found or access denied']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Measurement deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting measurement: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
