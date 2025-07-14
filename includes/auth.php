<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function loadUserTimezone() {
    // Load user timezone preferences if not already in session
    if (!isset($_SESSION['timezone']) && isset($_SESSION['user_id'])) {
        try {
            $pdo = connectDB();
            $userId = $_SESSION['user_id'];
            
            // Get timezone from database
            $stmt = $pdo->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $_SESSION['timezone'] = $result['timezone'];
            } else {
                $_SESSION['timezone'] = 'Asia/Jakarta';  // Default
            }
        } catch (Exception $e) {
            $_SESSION['timezone'] = 'Asia/Jakarta';  // Default on error
        }
    }
}

function checkAuth() {
    // Cek session login biasa
    if (isset($_SESSION['user_id'])) {
        loadUserTimezone();
        return;
    }
    
    // Cek remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.full_name 
                FROM users u 
                JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE rt.token = ? AND rt.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Load user timezone preferences
                loadUserTimezone();
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                return;
            } else {
                // Token tidak valid, hapus cookie
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            }
        } catch (Exception $e) {
            // Token error, hapus cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    // Jika tidak ada session dan tidak ada remember token yang valid
    header('Location: index.php');
    exit;
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? ''
    ];
}
?>
