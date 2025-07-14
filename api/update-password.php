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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = connectDB();
    $userId = $_SESSION['user_id'];
    
    // Ambil data dari form
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Password baru dan konfirmasi tidak cocok']);
        exit;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
        exit;
    }
    
    // Verifikasi password saat ini
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || hash('sha256', $currentPassword) !== $user['password_hash']) {
        echo json_encode(['success' => false, 'message' => 'Password saat ini tidak valid']);
        exit;
    }
    
    // Update password
    $passwordHash = hash('sha256', $newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password berhasil diperbarui'
    ]);
    
} catch (Exception $e) {
    error_log("Error in update-password.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memperbarui password'
    ]);
}
?>
