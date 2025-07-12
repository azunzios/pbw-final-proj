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

try {
    $db = connectDB();
    $user_id = $_SESSION['user_id'];
    
    // Get parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 9; // 9 cards per page
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $stats = isset($_GET['stats']) && $_GET['stats'] === 'true';
    
    // If requesting stats only
    if ($stats) {
        $countQuery = "SELECT COUNT(*) as total FROM pets WHERE user_id = :user_id";
        $countStmt = $db->prepare($countQuery);
        $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => intval($count['total'])
            ]
        ]);
        exit;
    }
    
    // If requesting single pet
    if ($id) {
        $query = "SELECT * FROM pets WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pet) {
            echo json_encode([
                'success' => true,
                'pet' => $pet
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Pet not found'
            ]);
        }
        exit;
    }
    
    // Build search query
    $whereClause = 'WHERE user_id = :user_id';
    $params = [':user_id' => $user_id];
    
    if (!empty($search)) {
        $whereClause .= " AND (name LIKE :search OR type LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM pets $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get pets with pagination
    $query = "SELECT * FROM pets $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'success' => true,
        'pets' => $pets,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
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
