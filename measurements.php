<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'utils/timezone.php';

checkAuth();
$user = getCurrentUser();

if (!$user) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Pengukuran';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Pet Care</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/measurements.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="measurements-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <!-- Actions Bar -->
            <div class="actions-bar">
                <div class="page-title">
                    <h1>📊 Pengukuran Pertumbuhan</h1>
                    <p>Monitor perkembangan kesehatan dan pertumbuhan hewan peliharaan Anda</p>
                </div>
                <button class="add-measurement-btn" onclick="openMeasurementModal()">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                    Tambah Pengukuran
                </button>
            </div>

            <!-- Main Content Grid -->
            <div class="measurements-grid">
                <!-- Pet Selection Panel -->
                <div class="pet-selection-panel">
                    <div class="panel-header">
                        <h3>
                            Pilih Hewan Peliharaan
                        </h3>
                    </div>
                    <div class="pet-search-container">
                        <div class="search-container">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                            </svg>
                            <input type="text" id="petSearch" placeholder="Cari hewan peliharaan..." autocomplete="off">
                            <div class="search-suggestions" id="searchSuggestions"></div>
                        </div>

                        <div class="pets-list" id="petsList">
                            <!-- Pets will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Right Panel Container -->
                <div class="right-panels">
                    <!-- Growth History Panel -->
                    <div class="growth-history-panel">
                        <div class="panel-header">
                            <h3>
                                Riwayat Pertumbuhan
                            </h3>
                            <div class="sort-controls">
                                <select id="sortBy">
                                    <option value="date_desc">Terbaru</option>
                                    <option value="date_asc">Terlama</option>
                                    <option value="weight_desc">Berat ↓</option>
                                    <option value="weight_asc">Berat ↑</option>
                                    <option value="length_desc">Panjang ↓</option>
                                    <option value="length_asc">Panjang ↑</option>
                                </select>
                            </div>
                        </div>
                        <div class="growth-table-container">
                                                     <!-- Growth Insights -->
                            <div class="growth-insights" id="growthInsights">
                                <!-- Insights will be loaded here -->
                            </div>
                            <table class="growth-table" id="growthTable">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Berat (kg)</th>
                                        <th>Panjang (cm)</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="growthTableBody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>

                            <div class="empty-growth" id="emptyGrowth" style="display: none;">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                                </svg>
                                <h4>Belum Ada Data Pengukuran</h4>
                                <p>Mulai tambahkan data pengukuran untuk melihat riwayat pertumbuhan</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Measurement Modal -->
    <div class="modal" id="measurementModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Pengukuran Baru</h2>
                <button class="close-btn" onclick="closeMeasurementModal()">&times;</button>
            </div>

            <form id="measurementForm">
                <input type="hidden" id="measurementId">
                <input type="hidden" id="selectedPetId">

                <div class="form-row">
                    <div class="form-group">
                        <label for="petSelect">Hewan Peliharaan *</label>
                        <select id="petSelect" required>
                            <option value="">Pilih hewan peliharaan...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="recordedDate">Tanggal Pengukuran *</label>
                        <input type="datetime-local" id="recordedDate" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="weight">Berat Badan (kg)</label>
                        <div class="weight-input-group">
                            <input type="number" id="weight" step="0.01" min="0" max="999.99" placeholder="0.00">
                            <span class="weight-unit">kg</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="length">Panjang Badan (cm)</label>
                        <div class="length-input-group">
                            <input type="number" id="length" step="0.1" min="0" max="999.9" placeholder="0.0">
                            <span class="length-unit">cm</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Catatan</label>
                    <textarea id="notes" rows="3" placeholder="Tambahkan catatan tentang kondisi atau observasi..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeMeasurementModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan Pengukuran</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/measurement.js"></script>
</body>

</html>