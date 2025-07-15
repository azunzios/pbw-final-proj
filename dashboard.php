<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'utils/timezone.php';

// Cek otentikasi dan dapatkan data pengguna
checkAuth();
$user = getCurrentUser();
$pageTitle = 'Beranda';

// Inisialisasi variabel statistik untuk mencegah error jika query gagal
$pet_stats = ['total_pets' => 0, 'dogs' => 0, 'cats' => 0, 'birds' => 0, 'fish' => 0, 'others' => 0];
$schedule_stats = ['total_today' => 0, 'completed' => 0, 'missed' => 0, 'remaining' => 0];
$next_schedule = null;
$today = formatDateWithTimezone(new DateTime(), 'Y-m-d');

try {
    $pdo = connectDB();
    $userId = $user['id'];

    // 1. Ambil statistik peliharaan
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_pets,
            COUNT(CASE WHEN type = 'Anjing' THEN 1 END) as dogs,
            COUNT(CASE WHEN type = 'Kucing' THEN 1 END) as cats,
            COUNT(CASE WHEN type = 'Burung' THEN 1 END) as birds,
            COUNT(CASE WHEN type = 'Ikan' THEN 1 END) as fish,
            COUNT(CASE WHEN type NOT IN ('Anjing', 'Kucing', 'Burung', 'Ikan') THEN 1 END) as others
        FROM pets WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $pet_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1.1. Ambil semua tipe peliharaan dengan jumlahnya (untuk chart dinamis)
    $stmt = $pdo->prepare("
        SELECT type, COUNT(*) as count 
        FROM pets 
        WHERE user_id = ? 
        GROUP BY type 
        ORDER BY count DESC, type ASC
    ");
    $stmt->execute([$userId]);
    $pet_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Ambil statistik jadwal hari ini
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_today,
            COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed,
            COUNT(CASE WHEN completed_at IS NULL AND schedule_time < NOW() THEN 1 END) as missed,
            COUNT(CASE WHEN completed_at IS NULL AND schedule_time >= NOW() THEN 1 END) as remaining
        FROM schedules s 
        WHERE s.user_id = ? AND DATE(s.schedule_time) = ?
    ");
    $stmt->execute([$userId, $today]);
    $schedule_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Ambil jadwal terdekat yang belum lewat (prioritas: belum selesai yang akan datang)
    $stmt = $pdo->prepare("
        SELECT 
            s.care_type, 
            TIME(s.schedule_time) as schedule_time, 
            DATE(s.schedule_time) as schedule_date,
            s.schedule_time as full_schedule_time,
            p.name as pet_name, 
            s.id as schedule_id, 
            (CASE WHEN s.completed_at IS NOT NULL THEN 1 ELSE 0 END) as is_done
        FROM schedules s 
        JOIN pets p ON s.pet_id = p.id 
        WHERE s.user_id = ? 
            AND s.is_active = 1 
            AND s.completed_at IS NULL 
            AND s.schedule_time >= NOW()
        ORDER BY s.schedule_time ASC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $today_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure $today_schedule is always an array or false
    if ($today_schedule === false) {
        $today_schedule = null;
    }
} catch (Exception $e) {
    // Initialize as null if there's an error
    $schedule_stats = ['total_today' => 0, 'completed' => 0, 'missed' => 0, 'remaining' => 0];
    $today_schedule = null;
    error_log("Error in dashboard.php: " . $e->getMessage());
}

/**
 * Helper function untuk format waktu tersisa.
 */
function formatTimeUntil($scheduleTime, $scheduleDate = null)
{
    try {
        $timezone = getUserTimezone();
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($timezone));
        
        // Bersihkan dan normalisasi input
        $scheduleTime = trim($scheduleTime);
        
        // Jika input berupa waktu saja (HH:MM:SS)
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $scheduleTime)) {
            $targetDate = $scheduleDate ?: formatDateWithTimezone($now, 'Y-m-d');
            $target = new DateTime($targetDate . ' ' . $scheduleTime);
            $target->setTimezone(new DateTimeZone($timezone));
        } 
        // Jika input berupa datetime lengkap
        else {
            $target = new DateTime($scheduleTime);
            $target->setTimezone(new DateTimeZone($timezone));
        }
        
        if ($now > $target) return 'Telah lewat';

        $interval = $now->diff($target);
        
        // Hitung total menit dengan benar
        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        if ($totalMinutes >= 60) {
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            if ($minutes > 0) {
                return $hours . ' jam ' . $minutes . ' menit lagi';
            } else {
                return $hours . ' jam lagi';
            }
        }
        return $totalMinutes . ' menit lagi';
    } catch (Exception $e) {
        return 'Format waktu tidak valid';
    }
}

/**
 * Helper function untuk mendapatkan status jadwal.
 */
function getScheduleStatus($schedule, $scheduleDate = null)
{
    if ($schedule['is_done'] == 1) {
        return ['status' => 'completed', 'text' => '✅ Selesai', 'class' => 'text-success'];
    }
    
    try {
        $timezone = getUserTimezone();
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($timezone));
        
        // Use full_schedule_time if available, otherwise construct from date and time
        if (isset($schedule['full_schedule_time'])) {
            $target = new DateTime($schedule['full_schedule_time']);
        } else {
            // Fallback to old method for compatibility
            $scheduleTime = trim($schedule['schedule_time']);
            
            // If input is time only (HH:MM:SS)
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $scheduleTime)) {
                $targetDate = $scheduleDate ?: formatDateWithTimezone($now, 'Y-m-d');
                $target = new DateTime($targetDate . ' ' . $scheduleTime);
            } else {
                $target = new DateTime($scheduleTime);
            }
        }
        
        $target->setTimezone(new DateTimeZone($timezone));
        
        if ($now > $target) {
            return ['status' => 'missed', 'text' => '❌ Terlewat', 'class' => 'text-danger'];
        } else {
            return ['status' => 'upcoming', 'text' => '⏰ Akan Datang', 'class' => 'text-warning'];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'text' => '❓ Error', 'class' => 'text-secondary'];
    }
}

function getPetTypeColor($type)
{
    $colors = [
        'Anjing' => '#ff9800',
        'Kucing' => '#e91e63',
        'Burung' => '#2196f3',
        'Ikan' => '#00bcd4',
        'Kelinci' => '#9c27b0',
        'Hamster' => '#ff5722',
        'Iguana' => '#4caf50',
        'Kura-kura' => '#795548',
        'Ular' => '#607d8b',
        'Reptil' => '#4caf50',
        'Amfibi' => '#8bc34a',
        'Serangga' => '#ffc107',
        'Tarantula' => '#424242',
        'Laba-laba' => '#424242'
    ];
    
    return isset($colors[$type]) ? $colors[$type] : '#9e9e9e';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - PetCare Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>
    <?php 
    $pageTitle = 'Beranda'; 
    include 'components/header.php';
    ?>
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>
        <main class="main-content">
            <!-- Statistics Section -->
            <section class="section">
                <div class="section-title">
                    <h2 class="section-title-text">Ikhtisar Statistik</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$pet_stats['total_pets'] ?></div>
                        <div class="stat-label">Total Peliharaan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$schedule_stats['total_today'] ?></div>
                        <div class="stat-label">Jadwal Hari Ini</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-success"><?= (int)$schedule_stats['completed'] ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?= (int)$schedule_stats['missed'] ?></div>
                        <div class="stat-label">Terlewat</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$schedule_stats['remaining'] ?></div>
                        <div class="stat-label">Tersisa</div>
                    </div>
                </div>
            </section>

            <!-- Pet Categories Chart Section -->
            <section class="section">
                <div class="section-title">
                    <h2 class="section-title-text">Peliharaan per Kategori</h2>
                </div>
                <div class="stats-grid">
                    <?php if (!empty($pet_types)): ?>
                        <?php foreach ($pet_types as $pet_type): ?>
                            <div class="stat-card" style="border-color: <?= getPetTypeColor($pet_type['type']) ?>;">
                                <div class="stat-number"><?= (int)$pet_type['count'] ?></div>
                                <div class="stat-label"><?= getPetTypeEmoji($pet_type['type']) ?> <?= htmlspecialchars($pet_type['type']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stat-card">
                            <div class="stat-number">0</div>
                            <div class="stat-label">🐾 Belum ada peliharaan</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Next Schedule Section -->
            <section class="section">
                <div class="section-title">
                    <h2 class="section-title-text">Jadwal Terdekat</h2>
                </div>
                <?php if ($today_schedule): ?>
                    <?php 
                        $status = getScheduleStatus($today_schedule, $today_schedule['schedule_date'] ?? $today);
                        $isToday = ($today_schedule['schedule_date'] ?? $today) === $today;
                    ?>
                    <div class="schedule-card">
                        <div class="schedule-info">
                            <h3><?= htmlspecialchars($today_schedule['care_type']) ?></h3>
                            <div class="schedule-time">
                                <?= htmlspecialchars($today_schedule['pet_name']) ?> |
                                <?php if (!$isToday): ?>
                                    <?= date('d M Y', strtotime($today_schedule['schedule_date'])) ?> |
                                <?php endif; ?>
                                <?= date('H:i', strtotime($today_schedule['schedule_time'])) ?>
                                <?php if ($status['status'] == 'upcoming'): ?>
                                    <span class="<?= $status['class'] ?>">(<?= formatTimeUntil($today_schedule['schedule_time'], $today_schedule['schedule_date'] ?? $today) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="schedule-status <?= $status['class'] ?>"><?= $status['text'] ?></div>
                        </div>
                        <div class="schedule-actions">
                            <?php if ($status['status'] == 'upcoming' || $status['status'] == 'missed'): ?>
                                <button class="btn btn-complete" onclick="completeSchedule(<?= (int)$today_schedule['schedule_id'] ?>)">
                                    ✓ Tandai Selesai
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-schedule-card">
                        <div class="no-schedule-icon">📅</div>
                        <div class="no-schedule-text">Belum ada jadwal yang akan datang</div>
                        <div class="no-schedule-desc">Silakan buat jadwal perawatan untuk peliharaan Anda</div>
                        <a href="schedules.php" class="btn btn-primary">Buat Jadwal</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Quick Actions Section -->
            <section class="section">
                <div class="section-title">
                    <h2 class="section-title-text">Pintasan</h2>
                </div>
                <div class="shortcuts-grid">
                    <a href="schedules.php" class="shortcut-card">
                        <div class="shortcut-icon">📅</div>
                        <div class="shortcut-title">Kelola Jadwal</div>
                        <div class="shortcut-desc">Atur jadwal perawatan</div>
                    </a>
                    <a href="pets.php" class="shortcut-card">
                        <div class="shortcut-icon">🐾</div>
                        <div class="shortcut-title">Kelola Peliharaan</div>
                        <div class="shortcut-desc">Tambah dan kelola data</div>
                    </a>
                    <a href="measurements.php" class="shortcut-card">
                        <div class="shortcut-icon">📊</div>
                        <div class="shortcut-title">Pengukuran</div>
                        <div class="shortcut-desc">Pantau perkembangan</div>
                    </a>
                    <a href="profile.php" class="shortcut-card">
                        <div class="shortcut-icon">⚙️</div>
                        <div class="shortcut-title">Pengaturan</div>
                        <div class="shortcut-desc">Atur profil & preferensi</div>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Horizontal scroll with mouse wheel for stats grid
        document.addEventListener('DOMContentLoaded', function() {
            const statsGrids = document.querySelectorAll('.stats-grid');
            
            statsGrids.forEach(grid => {
                grid.addEventListener('wheel', function(e) {
                    if (this.scrollWidth > this.clientWidth) {
                        e.preventDefault();
                        this.scrollLeft += e.deltaY;
                    }
                });
                
                grid.classList.add('horizontal-scroll');
            });

            // Update waktu tersisa secara real-time
            updateTimeRemaining();
            setInterval(updateTimeRemaining, 60000); // Update setiap menit
        });

        function updateTimeRemaining() {
            const scheduleTimeElement = document.querySelector('.schedule-time span.text-warning');
            if (!scheduleTimeElement) return;

            const scheduleTimeText = document.querySelector('.schedule-time').textContent;
            const timeMatch = scheduleTimeText.match(/(\d{2}:\d{2})/);
            if (!timeMatch) return;

            const scheduleTime = timeMatch[1];
            const now = new Date();
            const [hours, minutes] = scheduleTime.split(':');
            const targetTime = new Date();
            targetTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

            if (now > targetTime) {
                scheduleTimeElement.textContent = '(Telah lewat)';
                scheduleTimeElement.className = 'text-danger';
                return;
            }

            const diffMs = targetTime - now;
            const diffMinutes = Math.floor(diffMs / (1000 * 60));

            if (diffMinutes >= 60) {
                const hoursLeft = Math.floor(diffMinutes / 60);
                const minutesLeft = diffMinutes % 60;
                if (minutesLeft > 0) {
                    scheduleTimeElement.textContent = `(${hoursLeft} jam ${minutesLeft} menit lagi)`;
                } else {
                    scheduleTimeElement.textContent = `(${hoursLeft} jam lagi)`;
                }
            } else {
                scheduleTimeElement.textContent = `(${diffMinutes} menit lagi)`;
            }
        }

        function completeSchedule(scheduleId) {
            if (!confirm('Apakah Anda yakin jadwal ini sudah selesai?')) return;

            fetch('api/complete-schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ schedule_id: scheduleId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Terjadi kesalahan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                });
        }
    </script>
</body>

</html>