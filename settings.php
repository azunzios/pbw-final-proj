<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get current user
$user = getCurrentUser();
$pageTitle = "Pengaturan";

// Load user preferences if not already in session
try {
    if (!isset($_SESSION['timezone'])) {
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
    }
} catch (Exception $e) {
    $_SESSION['timezone'] = 'Asia/Jakarta';  // Default on error
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PetCare</title>
    
    <!-- Include stylesheets -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="settings-container">
        <div class="main-content">
            <div class="settings-layout">
                <h1>Pengaturan Akun</h1>
                
                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button class="tab-btn active" data-tab="profile">Profil</button>
                    <button class="tab-btn" data-tab="security">Keamanan</button>
                    <button class="tab-btn" data-tab="preferences">Preferensi</button>
                </div>
                
                <!-- Profile Settings Panel -->
                <div id="profile-panel" class="settings-panel active">
                    <div class="panel-header">
                        <h2>Informasi Profil</h2>
                        <p>Perbarui informasi profil akun Anda</p>
                    </div>
                    
                    <form id="profile-form" class="settings-form">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <small>Username tidak dapat diubah</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="fullname">Nama Lengkap</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                            <small>Email digunakan untuk identifikasi akun</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
                
                <!-- Security Settings Panel -->
                <div id="security-panel" class="settings-panel">
                    <div class="panel-header">
                        <h2>Keamanan</h2>
                        <p>Perbarui password akun Anda</p>
                    </div>
                    
                    <form id="password-form" class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <small>Minimal 6 karakter</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Ubah Password</button>
                    </form>
                </div>
                
                <!-- Preferences Settings Panel -->
                <div id="preferences-panel" class="settings-panel">
                    <div class="panel-header">
                        <h2>Preferensi</h2>
                        <p>Atur preferensi penggunaan aplikasi</p>
                    </div>
                    
                    <form id="preferences-form" class="settings-form">
                        <div class="form-group">
                            <label for="timezone">Zona Waktu</label>
                            <select id="timezone" name="timezone">
                                <option value="Asia/Jakarta" <?php echo ($_SESSION['timezone'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : ''; ?>>Jakarta (WIB)</option>
                                <option value="Asia/Makassar" <?php echo ($_SESSION['timezone'] ?? '') == 'Asia/Makassar' ? 'selected' : ''; ?>>Makassar (WITA)</option>
                                <option value="Asia/Jayapura" <?php echo ($_SESSION['timezone'] ?? '') == 'Asia/Jayapura' ? 'selected' : ''; ?>>Jayapura (WIT)</option>
                                <option value="Asia/Singapore" <?php echo ($_SESSION['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : ''; ?>>Singapore</option>
                                <option value="Asia/Bangkok" <?php echo ($_SESSION['timezone'] ?? '') == 'Asia/Bangkok' ? 'selected' : ''; ?>>Bangkok</option>
                            </select>
                            <small>Pengaturan zona waktu untuk tampilan jadwal yang akurat</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Preferensi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/settings.js"></script>
</body>
</html>