<?php
// components/sidebar.php
if (!isset($user)) {
    $user = getCurrentUser();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Function untuk membuat inisial dari nama lengkap
function generateInitials($fullName) {
    $words = explode(' ', trim($fullName));
    $initials = '';
    
    // Ambil huruf pertama dari setiap kata (maksimal 2)
    for ($i = 0; $i < min(2, count($words)); $i++) {
        if (!empty($words[$i])) {
            $initials .= strtoupper(substr($words[$i], 0, 1));
        }
    }
    
    return $initials ?: 'U'; // Default 'U' jika tidak ada inisial
}

// Function untuk generate warna background berdasarkan nama
function generateAvatarColor($fullName) {
    $colors = [
        '#FF6B6B', // Red
        '#4ECDC4', // Teal
        '#45B7D1', // Blue
        '#96CEB4', // Green
        '#FFEAA7', // Yellow
        '#DDA0DD', // Plum
        '#98D8C8', // Mint
        '#F7DC6F', // Light Yellow
        '#BB8FCE', // Light Purple
        '#85C1E9'  // Light Blue
    ];
    
    // Generate index berdasarkan hash dari nama
    $hash = crc32($fullName);
    $index = abs($hash) % count($colors);
    
    return $colors[$index];
}

$userInitials = generateInitials($user['full_name']);
$avatarColor = generateAvatarColor($user['full_name']);
?>

<!-- Hamburger Menu untuk Mobile -->
<button class="hamburger" onclick="toggleSidebar()">
    <span class="hamburger-icon">☰</span>
    <span class="close-icon">✕</span>
</button>

<!-- Sidebar Overlay untuk Mobile -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar" style="background-color: <?php echo $avatarColor; ?>">
                <?php echo $userInitials; ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="greeting-text"><?php 
                $hour = date('H');
                if ($hour >= 5 && $hour < 12) echo 'Selamat Pagi';
                elseif ($hour >= 12 && $hour < 15) echo 'Selamat Siang'; 
                elseif ($hour >= 15 && $hour < 18) echo 'Selamat Sore';
                else echo 'Selamat Malam';
            ?></div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" <?php echo $currentPage === 'dashboard' ? 'class="active"' : ''; ?>>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
                <span>Beranda</span>
            </a>
        </li>
        <li>
            <a href="schedules.php" <?php echo $currentPage === 'schedules' ? 'class="active"' : ''; ?>>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Kelola Jadwal</span>
            </a>
        </li>
        <li>
            <a href="pets.php" <?php echo $currentPage === 'pets' ? 'class="active"' : ''; ?>>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                    <path d="M21 9V7L15 1L9 7V9C9 10 9 11 11 13L12 14L13 13C15 11 15 10 15 9Z"/>
                    <path d="M8 14C8.5 14 9 14.5 9 15V18C9 18.5 8.5 19 8 19C7.5 19 7 18.5 7 18V15C7 14.5 7.5 14 8 14Z"/>
                    <path d="M16 14C16.5 14 17 14.5 17 15V18C17 18.5 16.5 19 16 19C15.5 19 15 18.5 15 18V15C15 14.5 15.5 14 16 14Z"/>
                </svg>
                <span>Kelola Peliharaan</span>
            </a>
        </li>
        <li>
            <a href="measurements.php" <?php echo $currentPage === 'measurements' ? 'class="active"' : ''; ?>>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Pengukuran</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-bottom">
        <ul class="sidebar-menu">
            <li>
                <a href="profile.php" <?php echo $currentPage === 'profile' ? 'class="active"' : ''; ?>>
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Atur Profil</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-logout">
            <button class="logout-btn" onclick="logout()">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16,17 21,12 16,7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Keluar</span>
            </button>
        </div>
    </div>
</nav>
