<?php
// components/header.php
$pageTitle = $pageTitle ?? 'Beranda';

// Include timezone utilities
require_once __DIR__ . '/../utils/timezone.php';

// Get user's timezone or default to Jakarta
$userTimezone = getUserTimezone();
?>

<!-- Top Header -->
<div class="top-header">
    <div class="header-left">
        <div class="logo-container">
            <div class="logo-squircle">
                <img src="assets/img/logo_header.png" alt="Pet Care Manager Logo" class="logo-icon">
            </div>
            <span class="logo-text">Pet Care Manager</span>
        </div>
    </div>
    <div class="header-right">
        <div class="datetime-info">
            <span class="date"><?php
                                // Create DateTime object with user's timezone
                                $currentTime = new DateTime();
                                $currentTime->setTimezone(new DateTimeZone($userTimezone));
                                
                                $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                                $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                                $dayName = $days[$currentTime->format('l')];
                                $day = $currentTime->format('d');
                                $month = $months[(int)$currentTime->format('n')];
                                echo "$dayName, $day $month";
                                ?></span>
            <span class="time"><?php
                                echo $currentTime->format('g:i A');
                                ?></span>
        </div>
    </div>
</div>