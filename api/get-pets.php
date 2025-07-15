<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check authentication for API (tidak melakukan redirect)
if (!checkApiAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 9; // 9 cards per page
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $stats = isset($_GET['stats']) && $_GET['stats'] === 'true';
    $all = isset($_GET['all']) && $_GET['all'] === 'true'; // New parameter for getting all pets
    
    // If requesting stats only
    if ($stats) {
        $countQuery = "SELECT COUNT(*) as total FROM pets WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
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
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
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
    $params = [':user_id' => $userId];
    
    if (!empty($search)) {
        $whereClause .= " AND (name LIKE :search OR type LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM pets $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get pets - with or without pagination
    if ($all) {
        // Get all pets without pagination
        $query = "SELECT * FROM pets $whereClause ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format response for all pets
        $response = [
            'success' => true,
            'pets' => $pets,
            'total' => $totalRecords
        ];
    } else {
        // Get pets with pagination
        $query = "SELECT * FROM pets $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format response with pagination
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
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
