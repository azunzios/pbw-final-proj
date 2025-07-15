<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../utils/timezone.php';

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

// Get form data
$petId = $_POST['pet_id'] ?? null;
$recordedAt = $_POST['recorded_at'] ?? null;
$weight = $_POST['weight'] ?? null;
$length = $_POST['length'] ?? null;
$notes = $_POST['notes'] ?? '';
$measurementId = $_POST['measurement_id'] ?? null;

// Validation
if (!$petId) {
    echo json_encode(['success' => false, 'message' => 'Pet ID is required']);
    exit();
}

if (!$recordedAt) {
    echo json_encode(['success' => false, 'message' => 'Recorded date is required']);
    exit();
}

if (empty($weight) && empty($length)) {
    echo json_encode(['success' => false, 'message' => 'At least weight or length must be provided']);
    exit();
}

// Validate numeric values
if (!empty($weight) && (!is_numeric($weight) || $weight < 0)) {
    echo json_encode(['success' => false, 'message' => 'Weight must be a valid positive number']);
    exit();
}

if (!empty($length) && (!is_numeric($length) || $length < 0)) {
    echo json_encode(['success' => false, 'message' => 'Length must be a valid positive number']);
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
    
    // Convert empty strings to null for database
    $weight = empty($weight) ? null : floatval($weight);
    $length = empty($length) ? null : floatval($length);
    $notes = trim($notes) ?: null;
    
    if ($measurementId) {
        // Update existing measurement
        $stmt = $pdo->prepare("
            UPDATE pet_measurements 
            SET recorded_at = ?, weight = ?, length = ?, notes = ?
            WHERE id = ? AND pet_id IN (SELECT id FROM pets WHERE user_id = ?)
        ");
        
        $stmt->execute([
            $recordedAt,
            $weight,
            $length,
            $notes,
            $measurementId,
            $user['id']
        ]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Measurement not found or access denied']);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Measurement updated successfully',
            'measurement_id' => $measurementId
        ]);
        
    } else {
        // Create new measurement
        $stmt = $pdo->prepare("
            INSERT INTO pet_measurements (pet_id, recorded_at, weight, length, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $petId,
            $recordedAt,
            $weight,
            $length,
            $notes
        ]);
        
        $measurementId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Measurement saved successfully',
            'measurement_id' => $measurementId
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error saving measurement: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
