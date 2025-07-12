<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $db = connectDB();
    
    // Get total pets count
    $totalQuery = "SELECT COUNT(*) as total FROM pets";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalPets = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get unique types count
    $typesQuery = "SELECT COUNT(DISTINCT type) as types FROM pets";
    $typesStmt = $db->prepare($typesQuery);
    $typesStmt->execute();
    $totalTypes = $typesStmt->fetch(PDO::FETCH_ASSOC)['types'];
    
    // Get average age
    $avgAgeQuery = "SELECT ROUND(AVG(age), 1) as avg_age FROM pets";
    $avgAgeStmt = $db->prepare($avgAgeQuery);
    $avgAgeStmt->execute();
    $avgAge = $avgAgeStmt->fetch(PDO::FETCH_ASSOC)['avg_age'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => intval($totalPets),
            'types' => intval($totalTypes),
            'avgAge' => floatval($avgAge)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'stats' => [
            'total' => 0,
            'types' => 0,
            'avgAge' => 0
        ]
    ]);
}
?>
