<?php
/**
 * Timezone utility functions
 */

/**
 * Format datetime according to user timezone preference
 */
function formatDateTimeWithTimezone($datetime, $format = 'Y-m-d H:i:s', $timezone = null) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Use session timezone or default to Asia/Jakarta
    if ($timezone === null) {
        $timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
    }
    
    try {
        // Create DateTime object from input
        if (is_string($datetime)) {
            $dt = new DateTime($datetime);
        } elseif ($datetime instanceof DateTime) {
            $dt = clone $datetime;
        } else {
            $dt = new DateTime();
        }
        
        // Set timezone
        $dt->setTimezone(new DateTimeZone($timezone));
        
        return $dt->format($format);
    } catch (Exception $e) {
        // Fallback to current time in default timezone
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dt->format($format);
    }
}

/**
 * Format date according to user timezone preference
 */
function formatDateWithTimezone($datetime, $format = 'Y-m-d', $timezone = null) {
    return formatDateTimeWithTimezone($datetime, $format, $timezone);
}

/**
 * Format time according to user timezone preference
 */
function formatTimeWithTimezone($datetime, $format = 'H:i', $timezone = null) {
    return formatDateTimeWithTimezone($datetime, $format, $timezone);
}

/**
 * Get relative time (e.g., "2 hours ago") according to user timezone
 */
function getRelativeTimeWithTimezone($datetime, $timezone = null) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($timezone === null) {
        $timezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
    }
    
    try {
        // Create DateTime objects
        if (is_string($datetime)) {
            $dt = new DateTime($datetime);
        } elseif ($datetime instanceof DateTime) {
            $dt = clone $datetime;
        } else {
            return 'Unknown time';
        }
        
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($timezone));
        $dt->setTimezone(new DateTimeZone($timezone));
        
        $diff = $now->diff($dt);
        
        if ($diff->days > 0) {
            if ($diff->days == 1) {
                return $diff->invert ? '1 hari yang lalu' : '1 hari lagi';
            } else {
                return $diff->invert ? $diff->days . ' hari yang lalu' : $diff->days . ' hari lagi';
            }
        } elseif ($diff->h > 0) {
            if ($diff->h == 1) {
                return $diff->invert ? '1 jam yang lalu' : '1 jam lagi';
            } else {
                return $diff->invert ? $diff->h . ' jam yang lalu' : $diff->h . ' jam lagi';
            }
        } elseif ($diff->i > 0) {
            if ($diff->i == 1) {
                return $diff->invert ? '1 menit yang lalu' : '1 menit lagi';
            } else {
                return $diff->invert ? $diff->i . ' menit yang lalu' : $diff->i . ' menit lagi';
            }
        } else {
            return $diff->invert ? 'Baru saja' : 'Sebentar lagi';
        }
    } catch (Exception $e) {
        return 'Unknown time';
    }
}

/**
 * Get user's current timezone
 */
function getUserTimezone() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['timezone'] ?? 'Asia/Jakarta';
}

/**
 * Convert datetime to user timezone before saving to database
 */
function convertToUserTimezone($datetime, $fromTimezone = 'UTC', $toTimezone = null) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($toTimezone === null) {
        $toTimezone = $_SESSION['timezone'] ?? 'Asia/Jakarta';
    }
    
    try {
        if (is_string($datetime)) {
            $dt = new DateTime($datetime, new DateTimeZone($fromTimezone));
        } elseif ($datetime instanceof DateTime) {
            $dt = clone $datetime;
            $dt->setTimezone(new DateTimeZone($fromTimezone));
        } else {
            return null;
        }
        
        $dt->setTimezone(new DateTimeZone($toTimezone));
        return $dt;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get pet type emoji
 */
function getPetTypeEmoji($type) {
    $emojis = [
        'Anjing' => '🐕',
        'Kucing' => '🐱',
        'Burung' => '🐦',
        'Ikan' => '🐠',
        'Hamster' => '🐹',
        'Kelinci' => '🐰',
        'Kura-kura' => '🐢',
        'Iguana' => '🦎',
        'Ular' => '🐍',
        'Ayam' => '🐔',
        'Bebek' => '🦆',
        'Angsa' => '🦢',
        'Sapi' => '🐄',
        'Kambing' => '🐐',
        'Domba' => '🐑',
        'Kuda' => '🐎',
        'Babi' => '🐷'
    ];
    
    return $emojis[$type] ?? '🐾';
}
