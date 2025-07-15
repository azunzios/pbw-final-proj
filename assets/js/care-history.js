// Global variables
let careHistory = [];
let pets = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadPets();
    loadCareHistory();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Filter change handlers
    document.getElementById('petFilter').addEventListener('change', loadCareHistory);
    document.getElementById('careTypeFilter').addEventListener('change', loadCareHistory);
    document.getElementById('statusFilter').addEventListener('change', loadCareHistory);
    document.getElementById('dateFilter').addEventListener('change', loadCareHistory);
}

// Load pets for filter
async function loadPets() {
    try {
        const response = await fetch('api/get-pets.php?all=true');
        const data = await response.json();
        
        if (data.success) {
            pets = data.pets || [];
            populatePetFilter();
        } else {
            console.error('Failed to load pets:', data.message);
        }
    } catch (error) {
        console.error('Error loading pets:', error);
    }
}

// Populate pet filter dropdown
function populatePetFilter() {
    const petFilter = document.getElementById('petFilter');
    
    // Clear existing options except the first one
    while (petFilter.children.length > 1) {
        petFilter.removeChild(petFilter.lastChild);
    }
    
    // Add pet options
    pets.forEach(pet => {
        const option = document.createElement('option');
        option.value = pet.id;
        option.textContent = `${pet.name} (${pet.type})`;
        petFilter.appendChild(option);
    });
}

// Load care history
async function loadCareHistory() {
    try {
        // Show loading state
        const historyList = document.getElementById('careHistoryList');
        if (!historyList) {
            console.error('careHistoryList element not found');
            return;
        }
        
        historyList.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Memuat riwayat perawatan...</p>
            </div>
        `;
        
        // Get filter values
        const petId = document.getElementById('petFilter').value;
        const careType = document.getElementById('careTypeFilter').value;
        const status = document.getElementById('statusFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;
        
        // Build query parameters
        const params = new URLSearchParams();
        if (petId) params.append('pet_id', petId);
        if (careType) params.append('care_type', careType);
        if (status) params.append('status', status);
        
        // Handle date filtering
        if (dateFilter !== 'all') {
            params.append('days', dateFilter);
        }
        
        const response = await fetch(`api/get-care-history.php?${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            careHistory = data.data;
            updateStatistics(data.stats);
            renderCareHistory();
        } else {
            showError('Gagal memuat riwayat perawatan: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error loading care history:', error);
        showError('Terjadi kesalahan jaringan saat memuat data');
    }
}

// Update statistics
function updateStatistics(stats) {
    document.getElementById('totalActivities').textContent = stats.total || 0;
    document.getElementById('completedActivities').textContent = stats.completed || 0;
    document.getElementById('missedActivities').textContent = stats.missed || 0;
    document.getElementById('upcomingActivities').textContent = stats.upcoming || 0;
}

// Render care history
function renderCareHistory() {
    const historyList = document.getElementById('careHistoryList');
    if (!historyList) {
        console.error('careHistoryList element not found');
        return;
    }
    
    if (careHistory.length === 0) {
        historyList.innerHTML = `
            <table class="care-history-table">
                <tbody>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-state-content">
                                <div class="empty-state-icon">📋</div>
                                <h3>Belum ada riwayat perawatan</h3>
                                <p>Riwayat perawatan akan muncul di sini setelah Anda menambahkan jadwal dan melakukan aktivitas perawatan.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
        return;
    }

    const rowsHTML = careHistory.map(item => {
        // Use pre-formatted dates from API if available, otherwise parse schedule_time
        let formattedDate = 'No Date';
        let formattedTime = 'No Time';
        
        if (item.formatted_date && item.formatted_time) {
            // Use pre-formatted data from API
            formattedDate = item.formatted_date;
            formattedTime = item.formatted_time;
        } else if (item.schedule_time) {
            // Parse schedule_time as fallback
            try {
                const dateTime = new Date(item.schedule_time);
                if (!isNaN(dateTime.getTime())) {
                    formattedDate = dateTime.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                    formattedTime = dateTime.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });
                }
            } catch (e) {
                // Silent fallback to default values
                formattedDate = 'Invalid Date';
                formattedTime = 'Invalid Time';
            }
        }

        // Status with icon
        const statusIcon = item.status === 'completed' ? '✅' : 
                          item.status === 'missed' ? '❌' : '⏰';
        const statusText = item.status === 'completed' ? 'Selesai' : 
                          item.status === 'missed' ? 'Terlewat' : 'Akan Datang';

        return `
            <tr class="care-history-row">
                <td class="pet-cell">
                    <div class="pet-info">
                        <span class="pet-emoji">${item.pet_emoji || '🐾'}</span>
                        <span class="pet-name">${escapeHtml(item.pet_name)}</span>
                    </div>
                </td>
                <td class="care-type-cell">
                    <span class="care-type-badge">${escapeHtml(item.care_type)}</span>
                </td>
                <td class="description-cell">
                    <span class="description">${escapeHtml(item.description)}</span>
                </td>
                <td class="datetime-cell">
                    <div class="datetime-info">
                        <span class="date">${formattedDate}</span>
                        <span class="time">${formattedTime}</span>
                    </div>
                </td>
                <td class="status-cell">
                    <div class="status-badge status-${item.status}">
                        <span class="status-icon">${statusIcon}</span>
                        <span class="status-text">${statusText}</span>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    historyList.innerHTML = `
        <table class="care-history-table">
            <tbody>
                ${rowsHTML}
            </tbody>
        </table>
    `;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return 'Tidak ada deskripsi';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show error message
function showError(message) {
    const historyList = document.getElementById('careHistoryList');
    if (!historyList) {
        console.error('careHistoryList element not found');
        return;
    }
    
    historyList.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">⚠️</div>
            <h3>Terjadi Kesalahan</h3>
            <p>${message}</p>
            <button type="button" onclick="loadCareHistory()" class="btn btn-primary">Coba Lagi</button>
        </div>
    `;
}