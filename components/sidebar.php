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
<button class="hamburger" onclick="toggleSidebar()" autocomplete="off" type="button">
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
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-200q-99 0-169.5-13.5T240-246v-34h-73q-35 0-59-26t-21-61l27-320q2-31 25-52t55-21h572q32 0 55 21t25 52l27 320q3 35-21 61t-59 26h-73v34q0 19-70.5 32.5T480-200ZM167-360h626l-27-320H194l-27 320Zm313-160Z"/></svg>
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
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M640-120q-33 0-56.5-23.5T560-200v-160q0-33 23.5-56.5T640-440h160q33 0 56.5 23.5T880-360v160q0 33-23.5 56.5T800-120H640Zm0-80h160v-160H640v160ZM80-240v-80h360v80H80Zm560-280q-33 0-56.5-23.5T560-600v-160q0-33 23.5-56.5T640-840h160q33 0 56.5 23.5T880-760v160q0 33-23.5 56.5T800-520H640Zm0-80h160v-160H640v160ZM80-640v-80h360v80H80Zm640 360Zm0-400Z"/></svg>
                <span>Kelola Peliharaan</span>
            </a>
        </li>
        <li>
            <a href="measurements.php" <?php echo $currentPage === 'measurements' ? 'class="active"' : ''; ?>>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M280-280h80v-200h-80v200Zm320 0h80v-400h-80v400Zm-160 0h80v-120h-80v120Zm0-200h80v-80h-80v80ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg>
                <span>Pengukuran</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-bottom">
        <ul class="sidebar-menu">
            <li>
                <a href="settings.php" <?php echo $currentPage === 'profile' ? 'class="active"' : ''; ?>>
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
