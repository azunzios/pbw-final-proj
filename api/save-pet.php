<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User tidak terautentikasi']);
    exit;
}

try {
    $db = connectDB();
    
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $custom_type = trim($_POST['custom_type'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Use custom type if "other" is selected
    if ($type === 'other' && !empty($custom_type)) {
        $type = $custom_type;
    }
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama peliharaan wajib diisi']);
        exit;
    }
    
    if (empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Jenis peliharaan wajib dipilih']);
        exit;
    }
    
    // Validate birth date if provided
    if (!empty($birth_date)) {
        $birthDateTime = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$birthDateTime) {
            echo json_encode(['success' => false, 'message' => 'Format tanggal lahir tidak valid']);
            exit;
        }
        
        $now = new DateTime();
        if ($birthDateTime > $now) {
            echo json_encode(['success' => false, 'message' => 'Tanggal lahir tidak boleh di masa depan']);
            exit;
        }
        
        // Check if age is reasonable (not more than 50 years)
        $age = $now->diff($birthDateTime)->y;
        if ($age > 50) {
            echo json_encode(['success' => false, 'message' => 'Umur peliharaan tidak valid (maksimal 50 tahun)']);
            exit;
        }
    }
    
    if (!empty($gender) && !in_array($gender, ['Jantan', 'Betina'])) {
        echo json_encode(['success' => false, 'message' => 'Jenis kelamin tidak valid']);
        exit;
    }
    
    // Handle photo upload
    $photoUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/pets/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $_FILES['photo']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExt, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP']);
            exit;
        }
        
        // Check file size (max 5MB)
        if ($_FILES['photo']['size'] > 1.5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 1.5MB']);
            exit;
        }
        
        // Generate unique filename
        $newFileName = uniqid('pet_') . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoUrl = $newFileName;
            
            // Delete old photo if updating
            if ($id) {
                $oldPhotoQuery = "SELECT image_path FROM pets WHERE id = :id";
                $oldPhotoStmt = $db->prepare($oldPhotoQuery);
                $oldPhotoStmt->bindParam(':id', $id);
                $oldPhotoStmt->execute();
                $oldPhoto = $oldPhotoStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($oldPhoto && $oldPhoto['image_path'] && file_exists($uploadDir . $oldPhoto['image_path'])) {
                    unlink($uploadDir . $oldPhoto['image_path']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload foto']);
            exit;
        }
    }
    
    if ($id) {
        // Update existing pet
        $query = "UPDATE pets SET 
                    name = :name, 
                    type = :type, 
                    birth_date = :birth_date, 
                    gender = :gender, 
                    weight = :weight, 
                    notes = :notes";
        
        if ($photoUrl) {
            $query .= ", image_path = :image_path";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':birth_date', $birth_date);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':notes', $notes);
        
        if ($photoUrl) {
            $stmt->bindParam(':image_path', $photoUrl);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Data peliharaan berhasil diperbarui',
                'id' => $id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data peliharaan']);
        }
        
    } else {
        // Insert new pet
        $user_id = $_SESSION['user_id'];
        
        $query = "INSERT INTO pets (user_id, name, type, birth_date, gender, weight, notes, image_path, created_at) 
                  VALUES (:user_id, :name, :type, :birth_date, :gender, :weight, :notes, :image_path, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':birth_date', $birth_date);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':image_path', $photoUrl);
        
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Peliharaan baru berhasil ditambahkan',
                'id' => $newId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan peliharaan baru']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
