<?php
session_start();
require_once __DIR__.'/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] == '1';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Nama pengguna dan kata sandi harus diisi']);
    exit;
}

try {
    $pdo = connectDB();
    
    // Cari user berdasarkan username
    $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Nama pengguna atau kata sandi salah']);
        exit;
    }
    
    // Verifikasi password
    $password_hash = hash('sha256', $password);
    if ($password_hash !== $user['password_hash']) {
        echo json_encode(['success' => false, 'message' => 'Nama pengguna atau kata sandi salah']);
        exit;
    }
    
    // Login berhasil
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    
    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Simpan token di database
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires]);
        
        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Login berhasil']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>
