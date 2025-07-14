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

try {
    $pdo = connectDB();
    $user = getCurrentUser();
    $userId = $user['id'];

    // Get date range and validate format
    $startDate = isset($_GET['start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start']) 
        ? $_GET['start'] 
        : date('Y-m-d');
    
    $endDate = isset($_GET['end']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end']) 
        ? $_GET['end'] 
        : date('Y-m-d', strtotime('+6 days'));

    // Query untuk mengambil jadwal dalam rentang tanggal
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.care_type,
            s.schedule_time,
            s.notes,
            s.recurrence,
            s.pet_id,
            p.name as pet_name,
            si.id as instance_id,
            si.date,
            si.is_done,
            si.done_at,
            CASE 
                WHEN si.is_done = 1 THEN 'completed'
                WHEN si.is_done = 0 AND STR_TO_DATE(CONCAT(si.date, ' ', TIME(s.schedule_time)), '%Y-%m-%d %H:%i:%s') < NOW() THEN 'missed'
                ELSE 'upcoming'
            END as status
        FROM schedules s
        JOIN pets p ON s.pet_id = p.id
        LEFT JOIN schedule_instances si ON s.id = si.schedule_id 
            AND si.date BETWEEN ? AND ?
        WHERE s.user_id = ? AND s.is_active = 1
        ORDER BY si.date ASC, s.schedule_time ASC
    ");
    
    $stmt->execute([$startDate, $endDate, $userId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Jika tidak ada instance untuk jadwal berulang, buat instance otomatis
    $schedulesWithInstances = [];
    $processedSchedules = [];

    foreach ($schedules as $schedule) {
        if ($schedule['instance_id']) {
            // Instance sudah ada
            $schedulesWithInstances[] = $schedule;
            $processedSchedules[] = $schedule['id'] . '-' . $schedule['date'];
        }
    }

    // Cari jadwal yang belum punya instance dalam rentang tanggal
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.care_type,
            s.schedule_time,
            s.notes,
            s.recurrence,
            s.pet_id,
            p.name as pet_name,
            s.days
        FROM schedules s
        JOIN pets p ON s.pet_id = p.id
        WHERE s.user_id = ? AND s.is_active = 1
    ");
    
    $stmt->execute([$userId]);
    $allSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate instances untuk jadwal berulang
    foreach ($allSchedules as $schedule) {
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        // Create an array to track processed dates for this schedule
        $processedDatesForThisSchedule = [];

        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $scheduleKey = $schedule['id'] . '-' . $dateStr;

            // Skip jika instance sudah ada atau tanggal sudah diproses untuk jadwal ini
            if (in_array($scheduleKey, $processedSchedules) || 
                in_array($dateStr, $processedDatesForThisSchedule)) {
                $currentDate->modify('+1 day');
                continue;
            }
            
            // Track this date as processed for this schedule
            $processedDatesForThisSchedule[] = $dateStr;

            // Cek apakah jadwal berlaku untuk hari ini
            if (shouldScheduleRunOnDate($schedule, $currentDate)) {
                try {
                    // First check if this instance already exists
                    $checkStmt = $pdo->prepare("
                        SELECT id FROM schedule_instances 
                        WHERE schedule_id = ? AND date = ?
                    ");
                    $checkStmt->execute([$schedule['id'], $dateStr]);
                    $existingInstance = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingInstance) {
                        $instanceId = $existingInstance['id'];
                    } else {
                        // Buat instance baru
                        $insertStmt = $pdo->prepare("
                            INSERT INTO schedule_instances (schedule_id, date, is_done) 
                            VALUES (?, ?, 0)
                        ");
                        $insertStmt->execute([$schedule['id'], $dateStr]);
                        $instanceId = $pdo->lastInsertId();
                    }
                } catch (Exception $e) {
                    // Log kesalahan pada pembuatan instance
                    error_log("Error creating schedule instance: " . $e->getMessage());
                    continue; // Lanjutkan ke jadwal berikutnya
                }

                // Tentukan status
                $scheduleDateTime = new DateTime($dateStr . ' ' . $schedule['schedule_time']);
                $now = new DateTime();
                
                $status = 'upcoming';
                if ($scheduleDateTime < $now) {
                    $status = 'missed';
                }

                // Tambahkan ke hasil
                $schedulesWithInstances[] = [
                    'id' => $schedule['id'],
                    'care_type' => $schedule['care_type'],
                    'schedule_time' => $schedule['schedule_time'],
                    'notes' => $schedule['notes'],
                    'recurrence' => $schedule['recurrence'],
                    'pet_id' => $schedule['pet_id'],
                    'pet_name' => $schedule['pet_name'],
                    'instance_id' => $instanceId,
                    'date' => $dateStr,
                    'is_done' => 0,
                    'done_at' => null,
                    'status' => $status
                ];
            }

            $currentDate->modify('+1 day');
        }
    }

    // Sort by date and time
    usort($schedulesWithInstances, function($a, $b) {
        $dateTimeA = strtotime($a['date'] . ' ' . $a['schedule_time']);
        $dateTimeB = strtotime($b['date'] . ' ' . $b['schedule_time']);
        return $dateTimeA - $dateTimeB;
    });

    echo json_encode([
        'success' => true,
        'schedules' => $schedulesWithInstances
    ]);

} catch (Exception $e) {
    // Log kesalahan dengan detail lebih lengkap
    error_log("Error in get-schedules.php: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data jadwal: ' . $e->getMessage()
    ]);
}

function shouldScheduleRunOnDate($schedule, $date) {
    $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
    $dayOfWeekMap = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa', 
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu'
    ];
    
    $indonesianDay = $dayOfWeekMap[$dayOfWeek];
    
    switch ($schedule['recurrence']) {
        case 'Once':
            // For one-time schedules, check if the date matches the schedule date
            try {
                // Check if schedule_time has a valid date
                if (!isset($schedule['schedule_time']) || empty($schedule['schedule_time'])) {
                    error_log("Invalid schedule_time for schedule ID: {$schedule['id']}");
                    return false;
                }
                
                // Extract only the date part from schedule_time to avoid time comparison issues
                $scheduleTime = $schedule['schedule_time'];
                if (strpos($scheduleTime, ' ') !== false) {
                    // If it's a datetime string, extract just the date part
                    $scheduleDate = substr($scheduleTime, 0, 10);
                } else if (strtotime($scheduleTime) !== false) {
                    // If it's a valid time string but not a date, use today's date
                    $scheduleDate = $date->format('Y-m-d');
                } else {
                    // It's not a valid date or time
                    error_log("Invalid date format in schedule_time: {$scheduleTime} for schedule ID: {$schedule['id']}");
                    return false;
                }
                
                $currentDate = $date->format('Y-m-d');
                
                // Debug logging
                error_log("Schedule ID: {$schedule['id']}, Schedule date: {$scheduleDate}, Current date: {$currentDate}, Match: " . ($scheduleDate === $currentDate ? 'Yes' : 'No'));
                
                return $scheduleDate === $currentDate;
            } catch (Exception $e) {
                error_log("Error processing date for schedule ID {$schedule['id']}: " . $e->getMessage());
                return false;
            }
            
        case 'Daily':
            // Daily schedules appear every day
            return true;
            
        case 'Weekly':
            // Check if today is in the list of selected days
            if (empty($schedule['days'])) {
                return false; // If no days are selected, don't show
            }
            
            $days = explode(',', $schedule['days']);
            $daysArray = array_map('trim', $days);
            return !empty($daysArray) && in_array($indonesianDay, $daysArray);
            
        case 'Monthly':
            // Monthly schedules - run on the same day of the month
            try {
                if (!isset($schedule['schedule_time']) || empty($schedule['schedule_time'])) {
                    error_log("Invalid schedule_time for schedule ID: {$schedule['id']}");
                    return false;
                }
                
                $scheduleTime = $schedule['schedule_time'];
                if (strtotime($scheduleTime) !== false) {
                    $scheduleDate = new DateTime($scheduleTime);
                    return $date->format('d') === $scheduleDate->format('d');
                } else {
                    error_log("Invalid date format for monthly schedule: {$scheduleTime} for schedule ID: {$schedule['id']}");
                    return false;
                }
            } catch (Exception $e) {
                error_log("Error processing date for schedule ID {$schedule['id']}: " . $e->getMessage());
                return false;
            }
            
        default:
            return false;
    }
}
?>
