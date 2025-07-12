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
    $limit = 9; // 9 cards per page
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
            
            <!-- Pet Detail View (Hidden by default) -->
            <div id="petDetailView" class="pet-detail-view">
                <button class="back-btn" onclick="showPetsList()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"/>
                    </svg>
                    Kembali ke Daftar
                </button>
                
                <div class="pet-detail-header">
                    <div class="pet-detail-info">
                        <div class="pet-detail-avatar" id="detailAvatar">
                            <!-- Avatar will be populated by JavaScript -->
                        </div>
                        <div class="pet-detail-basic">
                            <div class="pet-detail-name" id="detailName">Nama Pet</div>
                            <div class="pet-detail-type" id="detailType">Jenis</div>
                            <div class="pet-detail-specs">
                                <span id="detailAge">Umur</span>
                                <span id="detailGender">Jenis Kelamin</span>
                                <span id="detailWeight">Berat</span>
                            </div>
                        </div>
                        <div class="pet-detail-actions">
                            <button class="btn-detail btn-edit" onclick="editCurrentPet()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </button>
                            <button class="btn-detail btn-delete" onclick="deleteCurrentPet()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="pet-detail-sections">
                    <div class="detail-section">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11H5a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h4m-4-8V9a2 2 0 0 1 2-2h4m-4 8h8m0-8h4a2 2 0 0 1 2 2v3c0 1.1-.9 2-2 2h-4m0-8V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/>
                            </svg>
                            Riwayat Perawatan
                        </h3>
                        <div class="care-history-list" id="careHistoryList">
                            <!-- Care history will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            Jadwal Mendatang
                        </h3>
                        <div class="care-history-list" id="upcomingSchedulesList">
                            <!-- Upcoming schedules will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pets Grid/List Container -->
            <div id="petsContainer" class="pets-grid">
                <!-- Pets will be populated by JavaScript -->
            </div>
            
            <!-- Old Static Content - Remove this entire section and replace with dynamic loading -->
            <div style="display: none;" id="oldStaticContent">
                <?php if (empty($pets)): ?>
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                            <path d="M21 9V7L15 1L9 7V9C9 10 9 11 11 13L12 14L13 13C15 11 15 10 15 9Z"/>
                        </svg>
                        <h3>Belum ada peliharaan</h3>
                        <p>Mulai dengan menambahkan peliharaan pertama Anda</p>
                        <button class="empty-action-btn" onclick="openAddModal()">Tambah Peliharaan</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                        <div class="pet-card" data-pet-id="<?php echo $pet['id']; ?>">
                            <div class="pet-image">
                                <?php if ($pet['image_path']): ?>
                                    <img src="uploads/pets/<?php echo htmlspecialchars($pet['image_path']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                                            <path d="M21 9V7L15 1L9 7V9C9 10 9 11 11 13L12 14L13 13C15 11 15 10 15 9Z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pet-info">
                                <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
                                <div class="pet-details">
                                    <span class="pet-type"><?php echo htmlspecialchars($pet['type']); ?></span>
                                </div>
                                <div class="pet-meta">
                                    <span class="pet-age">
                                        <?php 
                                        if ($pet['birth_date']) {
                                            $birthDate = new DateTime($pet['birth_date']);
                                            $now = new DateTime();
                                            $age = $now->diff($birthDate);
                                            if ($age->y > 0) {
                                                echo $age->y . ' tahun';
                                            } elseif ($age->m > 0) {
                                                echo $age->m . ' bulan';
                                            } else {
                                                echo $age->d . ' hari';
                                            }
                                        } else {
                                            echo 'Umur tidak diketahui';
                                        }
                                        ?>
                                    </span>
                                    <?php if ($pet['gender']): ?>
                                        <span class="pet-gender"><?php echo htmlspecialchars($pet['gender']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($pet['weight']): ?>
                                        <span class="pet-weight"><?php echo $pet['weight']; ?> kg</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="pet-actions">
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $pet['id']; ?>)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button class="action-btn delete-btn" onclick="deletePet(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name']); ?>')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
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
                        <input type="file" id="petPhoto" name="photo" accept="image/*" onchange="previewImage(this)">
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
        
        // Global variables for pet detail view
        let currentViewMode = 'grid';
        let currentDetailPet = null;
        
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
        
        function showPetDetail(petId) {
            fetch(`api/get-pet-details.php?id=${petId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDetailPet = data.pet;
                        populatePetDetail(data);
                        document.getElementById('petsContainer').style.display = 'none';
                        document.getElementById('petDetailView').style.display = 'block';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to load pet details'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat detail peliharaan');
                });
        }
        
        function showPetsList() {
            document.getElementById('petDetailView').style.display = 'none';
            document.getElementById('petsContainer').style.display = currentViewMode === 'list' ? 'block' : 'grid';
            currentDetailPet = null;
        }
        
        function populatePetDetail(data) {
            const pet = data.pet;
            
            // Calculate age from birth_date
            let ageText = 'Umur tidak diketahui';
            if (pet.birth_date) {
                const birthDate = new Date(pet.birth_date);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                if (age >= 1) {
                    ageText = `${age} tahun`;
                } else {
                    const months = monthDiff >= 0 ? monthDiff : 12 + monthDiff;
                    ageText = months > 0 ? `${months} bulan` : 'Kurang dari 1 bulan';
                }
            }
            
            // Update avatar
            const avatarContainer = document.getElementById('detailAvatar');
            if (pet.image_path) {
                avatarContainer.innerHTML = `<img src="uploads/pets/${pet.image_path}" alt="${pet.name}">`;
            } else {
                avatarContainer.innerHTML = `
                    <div class="default-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                            <path d="M21 9V7L15 1L9 7V9C9 10 9 11 11 13L12 14L13 13C15 11 15 10 15 9Z"/>
                        </svg>
                    </div>
                `;
            }
            
            // Update basic info
            document.getElementById('detailName').textContent = pet.name;
            document.getElementById('detailType').textContent = pet.type;
            document.getElementById('detailAge').textContent = ageText;
            document.getElementById('detailGender').textContent = pet.gender || 'Jenis kelamin tidak diketahui';
            document.getElementById('detailWeight').textContent = pet.weight ? `${pet.weight} kg` : 'Berat tidak diketahui';
            
            // Update care history
            const careHistoryContainer = document.getElementById('careHistoryList');
            if (data.careHistory && data.careHistory.length > 0) {
                careHistoryContainer.innerHTML = data.careHistory.map(item => `
                    <div class="care-history-item">
                        <div class="care-history-header">
                            <span class="care-history-type">${item.care_type}</span>
                            <span class="care-history-time">${formatDateTime(item.timestamp)}</span>
                        </div>
                        ${item.notes ? `<div class="care-history-notes">${item.notes}</div>` : ''}
                        ${item.done_by ? `<div class="care-history-notes">Oleh: ${item.done_by}</div>` : ''}
                    </div>
                `).join('');
            } else {
                careHistoryContainer.innerHTML = '<div class="empty-care-history">Belum ada riwayat perawatan</div>';
            }
            
            // Update upcoming schedules
            const schedulesContainer = document.getElementById('upcomingSchedulesList');
            if (data.upcomingSchedules && data.upcomingSchedules.length > 0) {
                schedulesContainer.innerHTML = data.upcomingSchedules.map(item => `
                    <div class="care-history-item">
                        <div class="care-history-header">
                            <span class="care-history-type">${item.care_type}</span>
                            <span class="care-history-time">${item.next_date ? formatDate(item.next_date) : 'Belum dijadwalkan'}</span>
                        </div>
                        ${item.notes ? `<div class="care-history-notes">${item.notes}</div>` : ''}
                    </div>
                `).join('');
            } else {
                schedulesContainer.innerHTML = '<div class="empty-care-history">Belum ada jadwal mendatang</div>';
            }
        }
        
        function editCurrentPet() {
            if (currentDetailPet) {
                openEditModal(currentDetailPet.id);
            }
        }
        
        function deleteCurrentPet() {
            if (currentDetailPet) {
                deletePet(currentDetailPet.id, currentDetailPet.name);
            }
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
