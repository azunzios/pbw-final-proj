<?php
header('Content-Type: application/json');
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/database.php';

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = connectDB();
    $userId = $_SESSION['user_id'];
    
    // Ambil data dari form
    $timezone = $_POST['timezone'] ?? 'Asia/Jakarta';
    
    // Validasi timezone
    $validTimezones = DateTimeZone::listIdentifiers();
    if (!in_array($timezone, $validTimezones)) {
        $timezone = 'Asia/Jakarta'; // Default ke Asia/Jakarta jika tidak valid
    }
    
    // Cek apakah user sudah memiliki preferensi
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetchColumn() > 0;
    
    if ($exists) {
        // Update preferensi yang ada
        $stmt = $pdo->prepare("
            UPDATE user_preferences 
            SET timezone = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$timezone, $userId]);
    } else {
        // Buat preferensi baru
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences 
            (user_id, timezone, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $timezone]);
    }
    
    // Update session
    $_SESSION['timezone'] = $timezone;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Zona waktu berhasil diperbarui',
        'data' => [
            'timezone' => $timezone
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in update-preferences.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memperbarui preferensi'
    ]);
}
?>
