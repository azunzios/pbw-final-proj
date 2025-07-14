// Pet Management JavaScript
let currentPage = 1;
let searchQuery = '';
let currentEditingId = null;

// Make functions globally available
window.initializePetManagement = initializePetManagement;
window.editPet = editPet;
window.openEditModal = openEditModal;
window.deletePet = deletePet;
window.loadPets = loadPets;
window.openAddModal = openAddModal;
window.showPetDetail = showPetDetail;

function initializePetManagement() {
    setupEventListeners();
    loadPets();
    updateStats();
}

function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Add pet button
    const addBtn = document.querySelector('.add-btn');
    if (addBtn) {
        addBtn.addEventListener('click', openAddModal);
    }

    // Modal close events
    const modal = document.getElementById('petModal');
    const closeBtn = document.querySelector('.close-btn');
    const cancelBtn = document.querySelector('.btn-cancel');

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    // Form submission
    const petForm = document.getElementById('petForm');
    if (petForm) {
        petForm.addEventListener('submit', handleFormSubmit);
    }

    // Photo upload
    const photoInput = document.getElementById('petPhoto');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoUpload);
    }

    // Photo preview click
    const photoPreview = document.querySelector('.photo-preview');
    if (photoPreview) {
        photoPreview.addEventListener('click', function() {
            if (photoInput) photoInput.click();
        });
    }

    // Type selection with templates
    const typeSelect = document.getElementById('petType');
    if (typeSelect) {
        typeSelect.addEventListener('change', handleTypeChange);
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function handleSearch(e) {
    searchQuery = e.target.value.trim();
    currentPage = 1;
    loadPets();
}

function loadPets() {
    const container = document.getElementById('petsContainer');
    if (!container) return;

    // Show loading state
    container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b6b6bff;">Memuat peliharaan...</div>';

    const params = new URLSearchParams({
        page: currentPage,
        search: searchQuery
    });

    fetch(`api/get-pets.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPets(data.pets, data.pagination);
            } else {
                console.error('Error loading pets:', data.message);
                showEmptyState();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showEmptyState();
        });
}



function displayPets(pets, pagination) {
    const container = document.getElementById('petsContainer');
    
    if (pets.length === 0) {
        showEmptyState();
        return;
    }

    let petsHTML = '';
    pets.forEach(pet => {
        petsHTML += createPetCard(pet);
    });

    container.innerHTML = petsHTML;

    // Update pagination
    updatePagination(pagination);
}

function createPetCard(pet) {
    const photoSrc = pet.image_path 
        ? `uploads/pets/${pet.image_path}` 
        : null;

    const defaultAvatar = `
        <div class="default-avatar">
            <div class="default-pet-emoji">${getPetTypeEmoji(pet.type)}</div>
        </div>
    `;

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

    // Check if we're in list view to use different layout
    const viewMode = document.getElementById('petsContainer').className;
    
    if (viewMode === 'pets-list') {
        return `
            <div class="pet-card-list" data-pet-id="${pet.id}" onclick="showPetDetail(${pet.id})">
                <div class="pet-image">
                    ${photoSrc ? `<img src="${photoSrc}" alt="${pet.name}">` : defaultAvatar}
                </div>
                
                <div class="pet-info">
                    <h3 class="pet-name">${escapeHtml(pet.name)}</h3>
                    <div class="pet-details">
                        <span class="pet-type">${escapeHtml(pet.type)}</span>
                        ${pet.weight ? `<span class="pet-weight">${pet.weight} kg</span>` : ''}
                    </div>
                    <div class="pet-meta">
                        <span class="pet-age">${ageText}</span>
                        <span class="pet-gender">${pet.gender === 'Jantan' ? 'Jantan' : pet.gender === 'Betina' ? 'Betina' : 'Tidak diketahui'}</span>
                    </div>
                </div>
                
                <div class="pet-actions">
                    <button class="action-btn edit-btn" onclick="event.stopPropagation(); editPet(${pet.id})" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="action-btn delete-btn" onclick="event.stopPropagation(); deletePet(${pet.id}, '${escapeHtml(pet.name)}')" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"/>
                            <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    } else {
        return `
            <div class="pet-card" data-pet-id="${pet.id}" onclick="showPetDetail(${pet.id})">
                <div class="pet-image">
                    ${photoSrc ? `<img src="${photoSrc}" alt="${pet.name}">` : defaultAvatar}
                </div>
                
                <div class="pet-info">
                    <h3 class="pet-name">${escapeHtml(pet.name)}</h3>
                    
                    <div class="pet-details">
                        <span class="pet-type">${escapeHtml(pet.type)}</span>
                        ${pet.weight ? `<span class="pet-weight">${pet.weight} kg</span>` : ''}
                    </div>
                    
                    <div class="pet-meta">
                        <span class="pet-age">${ageText}</span>
                        <span class="pet-gender">${pet.gender === 'Jantan' ? 'Jantan' : pet.gender === 'Betina' ? 'Betina' : 'Tidak diketahui'}</span>
                    </div>
                </div>
                
                <div class="pet-actions">
                    <button class="action-btn edit-btn" onclick="event.stopPropagation(); editPet(${pet.id})" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="action-btn delete-btn" onclick="event.stopPropagation(); deletePet(${pet.id}, '${escapeHtml(pet.name)}')" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"/>
                            <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }
}

function getPetTypeEmoji(type) {
    const emojis = {
        'Anjing': '🐕',
        'Kucing': '🐱',
        'Burung': '🐦',
        'Ikan': '🐠',
        'Hamster': '🐹',
        'Kelinci': '🐰',
        'Kura-kura': '🐢',
        'Iguana': '🦎',
        'Ular': '🐍',
        'Ayam': '🐔',
        'Bebek': '🦆',
        'Angsa': '🦢',
        'Sapi': '🐄',
        'Kambing': '🐐',
        'Domba': '🐑',
        'Kuda': '🐎',
        'Babi': '🐷'
    };
    
    return emojis[type] || '🐾';
}

function showEmptyState() {
    const container = document.getElementById('petsContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-pet-emoji">🐾</div>
            <h3>Belum ada peliharaan</h3>
            <p>Mulai dengan menambahkan peliharaan pertama Anda</p>
            <button class="btn-primary" onclick="openAddModal()">
                <span>➕</span>
                Tambah Peliharaan
            </button>
        </div>
    `;
}

function updatePagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer || pagination.totalPages <= 1) {
        if (paginationContainer) paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    // Previous button
    if (pagination.currentPage > 1) {
        paginationHTML += `<a href="#" class="page-link" data-page="${pagination.currentPage - 1}">‹</a>`;
    }

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        if (i === pagination.currentPage) {
            paginationHTML += `<span class="page-link active">${i}</span>`;
        } else {
            paginationHTML += `<a href="#" class="page-link" data-page="${i}">${i}</a>`;
        }
    }

    // Next button
    if (pagination.currentPage < pagination.totalPages) {
        paginationHTML += `<a href="#" class="page-link" data-page="${pagination.currentPage + 1}">›</a>`;
    }

    paginationContainer.innerHTML = paginationHTML;

    // Add click listeners
    paginationContainer.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = parseInt(this.dataset.page);
            loadPets();
        });
    });
}

function showPetDetail(petId) {
    // For now, just edit the pet since the detail view might not be fully implemented
    // You can enhance this later to show a proper detail view
    editPet(petId);
}

function openAddModal() {
    currentEditingId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Peliharaan';
    document.getElementById('petForm').reset();
    document.querySelector('.photo-preview').innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21,15 16,10 5,21"/>
        </svg>
        <p>Klik untuk upload foto</p>
    `;
    document.getElementById('petModal').style.display = 'block';
}

function editPet(petId) {
    openEditModal(petId);
}

function openEditModal(petId) {
    currentEditingId = petId;
    document.getElementById('modalTitle').textContent = 'Edit Peliharaan';
    
    // Fetch pet data
    fetch(`api/get-pets.php?id=${petId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.pet) {
                populateForm(data.pet);
                document.getElementById('petModal').style.display = 'block';
            } else {
                alert('Error: ' + (data.message || 'Failed to load pet data'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat data peliharaan');
        });
}

function populateForm(pet) {
    // Basic fields
    const petName = document.getElementById('petName');
    const petType = document.getElementById('petType');
    const petGender = document.getElementById('petGender');
    const petWeight = document.getElementById('petWeight');
    const petBirthDate = document.getElementById('petBirthDate');
    
    if (petName) petName.value = pet.name || '';
    if (petType) petType.value = pet.type || '';
    if (petGender) petGender.value = pet.gender || '';
    if (petWeight) petWeight.value = pet.weight || '';
    if (petBirthDate) petBirthDate.value = pet.birth_date || '';

    // Handle photo preview
    const photoPreview = document.querySelector('.photo-preview');
    if (photoPreview && pet.image_path) {
        photoPreview.innerHTML = `<img src="uploads/pets/${pet.image_path}" alt="${pet.name}">`;
    }

    // Handle custom type
    if (pet.type && !['Anjing', 'Kucing', 'Burung', 'Ikan'].includes(pet.type)) {
        if (petType) petType.value = 'other';
        const customType = document.getElementById('customType');
        if (customType) {
            customType.value = pet.type;
            customType.style.display = 'block';
            customType.required = true;
        }
    }
}

function deletePet(petId, petName) {
    if (confirm(`Apakah Anda yakin ingin menghapus "${petName}"? Tindakan ini tidak dapat dibatalkan.`)) {
        fetch('api/delete-pet.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: petId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadPets();
                updateStats();
                showNotification('Peliharaan berhasil dihapus', 'success');
            } else {
                alert('Error: ' + (data.message || 'Failed to delete pet'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus peliharaan');
        });
    }
}

function closeModal() {
    document.getElementById('petModal').style.display = 'none';
    currentEditingId = null;
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    
    // Get form elements safely
    const petName = document.getElementById('petName');
    const petGender = document.getElementById('petGender');
    const petWeight = document.getElementById('petWeight');
    const petBirthDate = document.getElementById('petBirthDate');
    
    if (petName) formData.append('name', petName.value);
    if (petGender) formData.append('gender', petGender.value);
    if (petWeight) formData.append('weight', petWeight.value);
    if (petBirthDate) formData.append('birth_date', petBirthDate.value);

    // Handle type (custom or predefined)
    const typeSelect = document.getElementById('petType');
    const customType = document.getElementById('customType');
    if (typeSelect && typeSelect.value === 'other' && customType && customType.value.trim()) {
        formData.append('type', customType.value.trim());
    } else if (typeSelect) {
        formData.append('type', typeSelect.value);
    }

    // Handle photo
    const photoInput = document.getElementById('petPhoto');
    if (photoInput && photoInput.files && photoInput.files[0]) {
        formData.append('photo', photoInput.files[0]);
    }

    if (currentEditingId) {
        formData.append('id', currentEditingId);
    }

    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;

    fetch('api/save-pet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadPets();
            updateStats();
            showNotification(
                currentEditingId ? 'Peliharaan berhasil diperbarui' : 'Peliharaan berhasil ditambahkan',
                'success'
            );
        } else {
            alert('Error: ' + (data.message || 'Failed to save pet'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.photo-preview').innerHTML = 
                `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
}

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



function updateStats() {
    // Update total pets count in header
    fetch('api/get-pets.php?stats=true')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                const totalElement = document.getElementById('totalPetsCount');
                if (totalElement) {
                    totalElement.textContent = data.stats.total || 0;
                }
            }
        })
        .catch(error => {
            console.error('Error updating stats:', error);
        });
}

function showNotification(message, type = 'info') {
    // Simple notification system
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 4px;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.opacity = '1', 100);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
