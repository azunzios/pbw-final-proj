<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/utils/timezone.php';

// Cek otentikasi dan dapatkan data pengguna
checkAuth();
$user = getCurrentUser();
$pageTitle = 'Jadwal Perawatan';

// Set timezone from user preferences or default to Asia/Jakarta
$timezone = getUserTimezone();
date_default_timezone_set($timezone);

try {
    $pdo = connectDB();
    $userId = $user['id'];

    // Ambil daftar pets untuk dropdown
    $stmt = $pdo->prepare("SELECT id, name FROM pets WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pets = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - PetCare Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/schedules.css">
</head>

<body>
    <?php include 'components/header.php'; ?>
    <div class="schedules-container">
        <?php include 'components/sidebar.php'; ?>
        <main class="main-content">
            <div class="schedules-layout">
                <!-- Kalender Mingguan -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button class="btn-nav" onclick="navigateWeek(-1)">‹</button>
                        <h3 class="week-title" id="weekTitle">Minggu ini</h3>
                        <button class="btn-nav" onclick="navigateWeek(1)">›</button>
                        <button class="btn-today" onclick="goToToday()">Hari Ini</button>
                    </div>

                    <div class="calendar-grid">
                        <div class="day-column" data-day="sunday">
                            <div class="day-header">
                                <span class="day-name">Minggu</span>
                                <span class="day-date" id="sunday-date"></span>
                            </div>
                            <div class="schedule-cards" id="sunday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="monday">
                            <div class="day-header">
                                <span class="day-name">Senin</span>
                                <span class="day-date" id="monday-date"></span>
                            </div>
                            <div class="schedule-cards" id="monday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="tuesday">
                            <div class="day-header">
                                <span class="day-name">Selasa</span>
                                <span class="day-date" id="tuesday-date"></span>
                            </div>
                            <div class="schedule-cards" id="tuesday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="wednesday">
                            <div class="day-header">
                                <span class="day-name">Rabu</span>
                                <span class="day-date" id="wednesday-date"></span>
                            </div>
                            <div class="schedule-cards" id="wednesday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="thursday">
                            <div class="day-header">
                                <span class="day-name">Kamis</span>
                                <span class="day-date" id="thursday-date"></span>
                            </div>
                            <div class="schedule-cards" id="thursday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="friday">
                            <div class="day-header">
                                <span class="day-name">Jumat</span>
                                <span class="day-date" id="friday-date"></span>
                            </div>
                            <div class="schedule-cards" id="friday-schedules"></div>
                        </div>
                        <div class="day-column" data-day="saturday">
                            <div class="day-header">
                                <span class="day-name">Sabtu</span>
                                <span class="day-date" id="saturday-date"></span>
                            </div>
                            <div class="schedule-cards" id="saturday-schedules"></div>
                        </div>
                    </div>
                </div>

                <!-- Panel Kelola Jadwal -->
                <div class="schedule-manager">
                    <div class="manager-header">
                        <h3>
                            Kelola Jadwal
                            <button class="add-btn" onclick="openScheduleModal()">
                                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                Tambah Jadwal
                            </button>
                        </h3>
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="upcoming">Akan Datang</button>
                            <button class="filter-tab" data-filter="completed">Selesai</button>
                            <button class="filter-tab" data-filter="missed">Terlewat</button>
                        </div>
                    </div>

                    <div class="schedule-list" id="scheduleList">
                        <!-- Daftar jadwal akan dimuat di sini -->
                    </div>
                </div>
            </div>
            </section>
        </main>
    </div>

    <!-- Modal Tambah/Edit Jadwal -->
    <div class="modal" id="scheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Jadwal Baru</h3>
                <button class="modal-close" onclick="closeScheduleModal()">&times;</button>
            </div>
            <form id="scheduleForm">
                <input type="hidden" id="scheduleId" name="scheduleId">

                <div class="form-group">
                    <label for="pet_id">Peliharaan</label>
                    <select id="pet_id" name="pet_id" required>
                        <option value="">Pilih peliharaan...</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="care_type">Jenis Perawatan</label>
                    <select id="care_type" name="care_type" required>
                        <option value="">Pilih jenis perawatan...</option>
                        <option value="Makan">Makan</option>
                        <option value="Minum">Minum</option>
                        <option value="Mandi">Mandi</option>
                        <option value="Vaksin">Vaksin</option>
                        <option value="Obat">Obat</option>
                        <option value="Olahraga">Olahraga</option>
                        <option value="Grooming">Grooming</option>
                        <option value="Checkup">Checkup</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="schedule_time">Waktu</label>
                    <input type="time" id="schedule_time" name="schedule_time" required>
                </div>

                <div class="form-group">
                    <label for="start_date">Tanggal</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="description">Catatan</label>
                    <textarea id="description" name="description" rows="3" placeholder="Catatan tambahan..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeScheduleModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/schedules.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>