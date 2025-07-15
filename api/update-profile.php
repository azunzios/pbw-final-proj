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
    $fullName = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validasi input
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Nama lengkap wajib diisi']);
        exit;
    }
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            exit;
        }
        
        // Cek apakah email sudah digunakan user lain
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh pengguna lain']);
            exit;
        }
        
        // Update data user dengan email baru
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $userId]);
    } else {
        // Update hanya nama lengkap
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute([$fullName, $userId]);
    }
    
    // Update session data
    $_SESSION['full_name'] = $fullName;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profil berhasil diperbarui'
    ]);
    
} catch (Exception $e) {
    error_log("Error in update-profile.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memperbarui profil'
    ]);
}
?>
