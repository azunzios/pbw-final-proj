<?php
// components/header.php
$pageTitle = $pageTitle ?? 'Beranda';
?>

<!-- Top Header -->
<div class="top-header">
    <div class="header-left">
        <div class="logo-container">
            <div class="logo-squircle">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4.5 12.5C4.5 13.88 5.62 15 7 15C8.38 15 9.5 13.88 9.5 12.5C9.5 11.12 8.38 10 7 10C5.62 10 4.5 11.12 4.5 12.5ZM14.5 12.5C14.5 13.88 15.62 15 17 15C18.38 15 19.5 13.88 19.5 12.5C19.5 11.12 18.38 10 17 10C15.62 10 14.5 11.12 14.5 12.5ZM12 4C13.93 4 15.5 5.57 15.5 7.5C15.5 9.43 13.93 11 12 11C10.07 11 8.5 9.43 8.5 7.5C8.5 5.57 10.07 4 12 4ZM12 17.5C15.04 17.5 17.5 19.96 17.5 23H6.5C6.5 19.96 8.96 17.5 12 17.5Z"/>
                </svg>
            </div>
            <span class="logo-text">Pet Care Manager</span>
        </div>
    </div>
    <div class="header-right">
        <div class="datetime-info">
            <span class="date"><?php
                                $currentTime = new DateTime();
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