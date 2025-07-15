<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/auth.php';
require_once 'config/database.php';

checkAuth();
$user = getCurrentUser();

if (!$user) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Riwayat Perawatan';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Pet Care</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/care-history.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="care-history-container-main"> <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-content">
                <div class="filter-section">
                    <div class="filter-group">
                        <label for="petFilter">Hewan Peliharaan:</label>
                        <select id="petFilter" class="filter-select">
                            <option value="">Semua Hewan</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="careTypeFilter">Jenis Perawatan:</label>
                        <select id="careTypeFilter" class="filter-select">
                            <option value="">Semua Jenis</option>
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

                    <div class="filter-group">
                        <label for="statusFilter">Status:</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="">Semua Status</option>
                            <option value="completed">Selesai</option>
                            <option value="missed">Terlewat</option>
                            <option value="upcoming">Akan Datang</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="dateFilter">Rentang Tanggal:</label>
                        <select id="dateFilter" class="filter-select">
                            <option value="7">7 Hari Terakhir</option>
                            <option value="30" selected>30 Hari Terakhir</option>
                            <option value="90">3 Bulan Terakhir</option>
                            <option value="365">1 Tahun Terakhir</option>
                            <option value="all">Semua Waktu</option>
                        </select>
                    </div>
                </div>

                <div class="care-history-container">
                    <div class="care-history-header">
                        <h3>Statistik Perawatan</h3>
                        <div class="history-stats">
                            <div class="stat-item">
                                <span class="stat-number" id="totalActivities">0</span>
                                <span class="stat-label">Total Aktivitas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="completedActivities">0</span>
                                <span class="stat-label">Selesai</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="missedActivities">0</span>
                                <span class="stat-label">Terlewat</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="upcomingActivities">0</span>
                                <span class="stat-label">Akan Datang</span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive-wrapper">
                        <div class="care-history-list">
                            <table class="care-history-table">
                                <thead>
                                    <tr>
                                        <th>Hewan Peliharaan</th>
                                        <th>Jenis Perawatan</th>
                                        <th>Deskripsi</th>
                                        <th>Waktu</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="careHistoryList">
                                    <tr class="loading-state">
                                        <td colspan="5">
                                            <div class="loading-spinner"></div>
                                            <p>Memuat riwayat perawatan...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/care-history.js"></script>
</body>

</html>