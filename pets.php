<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

checkAuth();
$user = getCurrentUser();
$pageTitle = 'Kelola Peliharaan';

try {
    $pdo = connectDB();
    
    // Ambil data peliharaan dengan search dan pagination
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 3;
    $offset = ($page - 1) * $limit;
    
    // Query untuk search
    $searchQuery = '';
    $params = [$user['id']];
    
    if (!empty($search)) {
        $searchQuery = " AND (name LIKE ? OR type LIKE ? OR breed LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Count total pets
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE user_id = ?" . $searchQuery);
    $stmt->execute($params);
    $totalPets = $stmt->fetchColumn();
    $totalPages = ceil($totalPets / $limit);
    
    // Get pets for current page
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE user_id = ?" . $searchQuery . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $pets = $stmt->fetchAll();
    
} catch (Exception $e) {
    $pets = [];
    $totalPets = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peliharaan - PetCare Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/pets.css">
</head>
<body>
    <div class="pets-container">
        <?php 
        $pageTitle = 'Kelola Peliharaan'; 
        include 'components/header.php'; 
        ?>
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Actions Bar -->
            <div class="actions-bar">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Cari peliharaan..." value="<?php echo htmlspecialchars($search); ?>">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <div class="search-suggestion" id="searchSuggestions" style="display: none;">
                        <!-- Suggestions will be populated here -->
                    </div>
                </div>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Grid View">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"/>
                                <rect x="14" y="3" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                            </svg>
                        </button>
                        <button class="view-btn" data-view="list" title="List View">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <button class="add-btn" onclick="openAddModal()">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Tambah Peliharaan</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="stats-summary">
                <div class="stat-item">
                    <span class="stat-number" id="totalPetsCount"><?php echo $totalPets; ?></span>
                    <span class="stat-label">Total Peliharaan</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php 
                        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT type) FROM pets WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        echo $stmt->fetchColumn();
                    ?></span>
                    <span class="stat-label">Jenis</span>
                </div>
            </div>
            
            <!-- Pets Grid/List Container -->
            <div id="petsContainer" class="pets-grid">
            <!-- Pets will be populated by JavaScript -->
            </div>
            

            
            <!-- Pagination Container -->
            <div id="paginationContainer" class="pagination">
                <!-- Pagination will be populated by JavaScript -->
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Pet Modal -->
    <div id="petModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Peliharaan</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="petForm" enctype="multipart/form-data">
                <input type="hidden" id="petId" name="pet_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="petName">Nama Peliharaan *</label>
                        <input type="text" id="petName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="petType">Jenis Peliharaan *</label>
                        <select id="petType" name="type" required onchange="handleTypeChange()">
                            <option value="">Pilih jenis</option>
                            <option value="Anjing">Anjing</option>
                            <option value="Kucing">Kucing</option>
                            <option value="Burung">Burung</option>
                            <option value="Ikan">Ikan</option>
                            <option value="other">Lainnya (tulis sendiri)</option>
                        </select>
                        <input type="text" id="customType" name="custom_type" placeholder="Sebutkan jenis peliharaan" style="display:none; margin-top: 8px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="petGender">Jenis Kelamin</label>
                        <select id="petGender" name="gender">
                            <option value="">Pilih</option>
                            <option value="Jantan">Jantan</option>
                            <option value="Betina">Betina</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="petBirthDate">Tanggal Lahir</label>
                        <input type="date" id="petBirthDate" name="birth_date">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-group-weight">
                        <label for="petWeight">Berat</label>
                        <div class="weight-input-group">
                            <input type="number" id="petWeight" name="weight" step="0.1" min="0" placeholder="0.0">
                            <span class="weight-unit">kg</span>
                        </div>
                    </div>
                    <div class="form-group form-group-spacer">
                        <!-- Empty space for layout balance -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="petPhoto">Foto Peliharaan</label>
                    <div class="photo-upload">
                        <input type="file" id="petPhoto" name="photo" accept="image/*">
                        <div class="photo-preview" id="photoPreview">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21,15 16,10 5,21"/>
                            </svg>
                            <p>Klik untuk upload foto</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="petNotes">Catatan</label>
                    <textarea id="petNotes" name="notes" rows="3" placeholder="Catatan tambahan tentang peliharaan..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/pets.js"></script>
    <script>
        // Handle custom type input visibility
        function handleTypeChange() {
            const typeSelect = document.getElementById('petType');
            const customInput = document.getElementById('customType');
            
            if (typeSelect.value === 'other') {
                customInput.style.display = 'block';
                customInput.required = true;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
        
        // Global variables for view management
        let currentViewMode = 'grid';
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            initializePetManagement();
            setupViewToggle();
        });
        
        function setupViewToggle() {
            const viewButtons = document.querySelectorAll('.view-btn');
            viewButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const view = this.dataset.view;
                    switchView(view);
                });
            });
        }
        
        function switchView(view) {
            currentViewMode = view;
            
            // Update button states
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            // Update container class
            const container = document.getElementById('petsContainer');
            if (view === 'list') {
                container.className = 'pets-list';
            } else {
                container.className = 'pets-grid';
            }
            
            // Reload pets with new view
            loadPets();
        }
        
        function formatDateTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleDateString('id-ID') + ' ' + date.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID');
        }
        
        // Override the existing loadPets function to update total count
        const originalLoadPets = window.loadPets;
        window.loadPets = function() {
            if (originalLoadPets) {
                originalLoadPets();
            }
            updateTotalPetsCount();
        };
        
        function updateTotalPetsCount() {
            fetch('api/get-pets.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.pagination) {
                        document.getElementById('totalPetsCount').textContent = data.pagination.totalRecords;
                    }
                })
                .catch(error => {
                    console.error('Error updating pets count:', error);
                });
        }
    </script>
</body>
</html>
