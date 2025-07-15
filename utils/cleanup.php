<?php
require_once __DIR__.'/../config/database.php';

try {
    $pdo = connectDB();
    
    // Hapus remember tokens yang expired
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    
    echo "Cleanup completed successfully.\n";
    
} catch (Exception $e) {
    echo "Cleanup failed: " . $e->getMessage() . "\n";
}
?>
