<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = connectDB();
    $userId = $_SESSION['user_id'];
    
    // Ambil informasi pengguna
    $stmt = $pdo->prepare("
        SELECT u.username, u.email, u.full_name
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ambil preferensi pengguna
    $stmt = $pdo->prepare("
        SELECT timezone
        FROM user_preferences
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika preferensi tidak ditemukan, gunakan default
    if (!$preferences) {
        $preferences = [
            'timezone' => 'Asia/Jakarta'
        ];
        
        // Simpan preferensi default
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences 
            (user_id, timezone, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, 'Asia/Jakarta']);
    }
    
    // Simpan ke session
    $_SESSION['timezone'] = $preferences['timezone'];
    
    // Siapkan daftar timezone untuk dropdown
    $timezones = [
        'Asia/Jakarta' => 'Jakarta (WIB)',
        'Asia/Makassar' => 'Makassar (WITA)',
        'Asia/Jayapura' => 'Jayapura (WIT)',
        'Asia/Singapore' => 'Singapore',
        'Asia/Kuala_Lumpur' => 'Kuala Lumpur',
        'Asia/Bangkok' => 'Bangkok',
        'Asia/Tokyo' => 'Tokyo',
        'Australia/Sydney' => 'Sydney',
        'Europe/London' => 'London',
        'America/New_York' => 'New York',
        'America/Los_Angeles' => 'Los Angeles'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'preferences' => $preferences,
            'available_timezones' => $timezones
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-user-settings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil pengaturan pengguna'
    ]);
}
?>
