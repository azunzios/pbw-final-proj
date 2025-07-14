// measurements.js - JavaScript untuk halaman pengukuran

class MeasurementsManager {
    constructor() {
        this.currentPetId = null;
        this.currentView = 'grid';
        this.searchTimeout = null;
        this.measurements = [];
        this.pets = [];
        
        this.init();
    }

    // Get pet type emoji
    getPetTypeEmoji(type) {
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

    // Format date with user timezone (client-side approximation)
    formatDateWithUserTimezone(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    init() {
        this.loadPets();
        this.bindEvents();
        
        // Set default date to now
        const now = new Date();
        const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('recordedDate').value = localDate;
    }

    bindEvents() {
        // Pet search functionality
        const petSearch = document.getElementById('petSearch');
        petSearch.addEventListener('input', (e) => this.handlePetSearch(e.target.value));
        petSearch.addEventListener('keydown', (e) => this.handleSearchKeydown(e));

        // Form submission
        const measurementForm = document.getElementById('measurementForm');
        measurementForm.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Sort functionality
        document.getElementById('sortBy').addEventListener('change', (e) => {
            this.sortMeasurements(e.target.value);
        });

        // Modal close on backdrop click
        document.getElementById('measurementModal').addEventListener('click', (e) => {
            if (e.target.id === 'measurementModal') {
                this.closeMeasurementModal();
            }
        });

        // Auto-select pet if one is selected
        document.getElementById('petSelect').addEventListener('change', (e) => {
            if (e.target.value) {
                this.selectPet(parseInt(e.target.value));
            }
        });
    }

    async loadPets() {
        try {
            // Request all pets without pagination for measurements page
            const response = await fetch('api/get-pets.php?all=true');
            const data = await response.json();
            
            if (data.success) {
                this.pets = data.pets;
                this.renderPetsList();
                this.populatePetSelect();
            } else {
                console.error('Error loading pets:', data.message);
            }
        } catch (error) {
            console.error('Error loading pets:', error);
        }
    }

    renderPetsList() {
        const petsList = document.getElementById('petsList');
        
        if (this.pets.length === 0) {
            petsList.innerHTML = `
                <div class="empty-pets">
                    <div class="empty-pet-emoji">🐾</div>
                    <h4>Belum Ada Hewan Peliharaan</h4>
                    <p>Tambahkan hewan peliharaan terlebih dahulu untuk mulai melakukan pengukuran</p>
                </div>
            `;
            return;
        }

        petsList.innerHTML = this.pets.map(pet => `
            <div class="pet-item" data-pet-id="${pet.id}" onclick="measurementsManager.selectPet(${pet.id})">
                <div class="pet-avatar">
                    ${pet.image_path ? 
                        `<img src="uploads/pets/${pet.image_path}" alt="${pet.name}">` :
                        `<div class="default-pet-emoji">${this.getPetTypeEmoji(pet.type)}</div>`
                    }
                </div>
                <div class="pet-info">
                    <div class="pet-name">${pet.name}</div>
                    <div class="pet-details">
                        <span class="pet-type">${pet.type}</span>
                        ${pet.age ? `<span class="pet-age">${pet.age}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    populatePetSelect() {
        const petSelect = document.getElementById('petSelect');
        petSelect.innerHTML = '<option value="">Pilih hewan peliharaan...</option>';
        
        this.pets.forEach(pet => {
            petSelect.innerHTML += `<option value="${pet.id}">${pet.name} (${pet.type})</option>`;
        });
    }

    handlePetSearch(query) {
        clearTimeout(this.searchTimeout);
        
        if (query.length === 0) {
            this.hideSuggestions();
            this.renderPetsList();
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.showSuggestions(query);
        }, 200);
    }

    showSuggestions(query) {
        const suggestions = this.pets.filter(pet => 
            pet.name.toLowerCase().includes(query.toLowerCase()) ||
            pet.type.toLowerCase().includes(query.toLowerCase())
        );

        const suggestionsContainer = document.getElementById('searchSuggestions');
        
        if (suggestions.length === 0) {
            suggestionsContainer.innerHTML = `
                <div class="suggestion-item">
                    <div class="suggestion-info">
                        <div class="suggestion-name">Tidak ada hasil</div>
                    </div>
                </div>
            `;
        } else {
            suggestionsContainer.innerHTML = suggestions.map((pet, index) => `
                <div class="suggestion-item ${index === 0 ? 'active' : ''}" 
                     data-pet-id="${pet.id}" 
                     onclick="measurementsManager.selectPetFromSuggestion(${pet.id})">
                    <div class="suggestion-avatar">
                        ${pet.image_path ? 
                            `<img src="uploads/pets/${pet.image_path}" alt="${pet.name}">` :
                            `<div class="default-pet-emoji">${this.getPetTypeEmoji(pet.type)}</div>`
                        }
                    </div>
                    <div class="suggestion-info">
                        <div class="suggestion-name">${pet.name}</div>
                        <div class="suggestion-type">${pet.type}</div>
                    </div>
                </div>
            `).join('');
        }
        
        suggestionsContainer.classList.add('show');
    }

    hideSuggestions() {
        document.getElementById('searchSuggestions').classList.remove('show');
    }

    handleSearchKeydown(e) {
        const suggestions = document.querySelectorAll('.suggestion-item');
        const activeSuggestion = document.querySelector('.suggestion-item.active');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (activeSuggestion && activeSuggestion.nextElementSibling) {
                activeSuggestion.classList.remove('active');
                activeSuggestion.nextElementSibling.classList.add('active');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (activeSuggestion && activeSuggestion.previousElementSibling) {
                activeSuggestion.classList.remove('active');
                activeSuggestion.previousElementSibling.classList.add('active');
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeSuggestion) {
                const petId = parseInt(activeSuggestion.dataset.petId);
                this.selectPetFromSuggestion(petId);
            }
        } else if (e.key === 'Escape') {
            this.hideSuggestions();
        }
    }

    selectPetFromSuggestion(petId) {
        const pet = this.pets.find(p => p.id === petId);
        if (pet) {
            document.getElementById('petSearch').value = pet.name;
            this.hideSuggestions();
            this.selectPet(petId);
        }
    }

    async selectPet(petId) {
        this.currentPetId = petId;
        
        // Update UI selection
        document.querySelectorAll('.pet-item').forEach(item => {
            item.classList.remove('selected');
        });
        document.querySelector(`[data-pet-id="${petId}"]`)?.classList.add('selected');
        
        // Load measurements for this pet
        await this.loadMeasurements(petId);
        this.renderMeasurementsTable();
        this.updateInsights();
    }

    async loadMeasurements(petId) {
        try {
            const response = await fetch(`api/get-measurements.php?pet_id=${petId}`);
            const responseText = await response.text();
            
            console.log('Raw response:', responseText);
            
            try {
                const data = JSON.parse(responseText);
                if (data.success) {
                    this.measurements = data.measurements;
                } else {
                    console.error('Error loading measurements:', data.message);
                    this.measurements = [];
                }
            } catch (parseError) {
                console.error('JSON Parse error:', parseError);
                console.error('Response was:', responseText);
                this.measurements = [];
            }
        } catch (error) {
            console.error('Error loading measurements:', error);
            this.measurements = [];
        }
    }

    renderMeasurementsTable() {
        const tableBody = document.getElementById('growthTableBody');
        const emptyState = document.getElementById('emptyGrowth');
        
        if (this.measurements.length === 0) {
            tableBody.innerHTML = '';
            emptyState.style.display = 'flex';
            return;
        }
        
        emptyState.style.display = 'none';
        
        tableBody.innerHTML = this.measurements.map(measurement => `
            <tr>
                <td class="measurement-date">
                    ${this.formatDateWithUserTimezone(measurement.recorded_at)}
                </td>
                <td class="measurement-value">${measurement.weight || '-'}</td>
                <td class="measurement-value">${measurement.length || '-'}</td>
                <td class="measurement-notes" title="${measurement.notes || ''}">${measurement.notes || '-'}</td>
                <td class="measurement-actions">
                    <button class="action-btn-small edit" onclick="measurementsManager.editMeasurement(${measurement.id})" title="Edit">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </button>
                    <button class="action-btn-small delete" onclick="measurementsManager.deleteMeasurement(${measurement.id})" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    sortMeasurements(sortBy) {
        const [field, direction] = sortBy.split('_');
        
        this.measurements.sort((a, b) => {
            let aVal, bVal;
            
            switch (field) {
                case 'date':
                    aVal = new Date(a.recorded_at);
                    bVal = new Date(b.recorded_at);
                    break;
                case 'weight':
                    aVal = parseFloat(a.weight) || 0;
                    bVal = parseFloat(b.weight) || 0;
                    break;
                case 'length':
                    aVal = parseFloat(a.length) || 0;
                    bVal = parseFloat(b.length) || 0;
                    break;
                default:
                    return 0;
            }
            
            if (direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        this.renderMeasurementsTable();
    }

    updateInsights() {
        const insightsContainer = document.getElementById('growthInsights');
        
        if (!this.currentPetId || this.measurements.length === 0) {
            insightsContainer.innerHTML = '';
            return;
        }
        
        const insights = this.calculateInsights();
        
        insightsContainer.innerHTML = `
            <div class="insight-item">
                <div class="insight-label">Total Pengukuran</div>
                <div class="insight-value">${this.measurements.length}</div>
            </div>
            <div class="insight-item">
                <div class="insight-label">Berat Terakhir</div>
                <div class="insight-value">${insights.lastWeight || '-'}</div>
                <div class="insight-trend ${insights.weightTrend}">${insights.weightChange}</div>
            </div>
            <div class="insight-item">
                <div class="insight-label">Panjang Terakhir</div>
                <div class="insight-value">${insights.lastLength || '-'}</div>
                <div class="insight-trend ${insights.lengthTrend}">${insights.lengthChange}</div>
            </div>
            <div class="insight-item">
                <div class="insight-label">Periode</div>
                <div class="insight-value">${insights.period}</div>
            </div>
        `;
    }

    calculateInsights() {
        const sortedMeasurements = [...this.measurements].sort((a, b) => 
            new Date(a.recorded_at) - new Date(b.recorded_at)
        );
        
        const insights = {
            lastWeight: null,
            lastLength: null,
            weightTrend: 'neutral',
            lengthTrend: 'neutral',
            weightChange: '',
            lengthChange: '',
            period: ''
        };
        
        if (sortedMeasurements.length === 0) return insights;
        
        const latest = sortedMeasurements[sortedMeasurements.length - 1];
        const earliest = sortedMeasurements[0];
        
        // Last values
        if (latest.weight) {
            insights.lastWeight = `${latest.weight} kg`;
        }
        if (latest.length) {
            insights.lastLength = `${latest.length} cm`;
        }
        
        // Trends
        if (sortedMeasurements.length > 1) {
            const previous = sortedMeasurements[sortedMeasurements.length - 2];
            
            // Weight trend
            if (latest.weight && previous.weight) {
                const weightDiff = parseFloat(latest.weight) - parseFloat(previous.weight);
                if (weightDiff > 0) {
                    insights.weightTrend = 'positive';
                    insights.weightChange = `+${weightDiff.toFixed(1)} kg`;
                } else if (weightDiff < 0) {
                    insights.weightTrend = 'negative';
                    insights.weightChange = `${weightDiff.toFixed(1)} kg`;
                } else {
                    insights.weightChange = 'Tidak berubah';
                }
            }
            
            // Length trend
            if (latest.length && previous.length) {
                const lengthDiff = parseFloat(latest.length) - parseFloat(previous.length);
                if (lengthDiff > 0) {
                    insights.lengthTrend = 'positive';
                    insights.lengthChange = `+${lengthDiff.toFixed(1)} cm`;
                } else if (lengthDiff < 0) {
                    insights.lengthTrend = 'negative';
                    insights.lengthChange = `${lengthDiff.toFixed(1)} cm`;
                } else {
                    insights.lengthChange = 'Tidak berubah';
                }
            }
        }
        
        // Period calculation
        const daysDiff = Math.ceil((new Date(latest.recorded_at) - new Date(earliest.recorded_at)) / (1000 * 60 * 60 * 24));
        if (daysDiff === 0) {
            insights.period = 'Hari ini';
        } else if (daysDiff < 30) {
            insights.period = `${daysDiff} hari`;
        } else if (daysDiff < 365) {
            insights.period = `${Math.ceil(daysDiff / 30)} bulan`;
        } else {
            insights.period = `${Math.ceil(daysDiff / 365)} tahun`;
        }
        
        return insights;
    }

    openMeasurementModal(measurementId = null) {
        const modal = document.getElementById('measurementModal');
        const form = document.getElementById('measurementForm');
        const title = document.getElementById('modalTitle');
        
        if (measurementId) {
            title.textContent = 'Edit Pengukuran';
            this.populateFormForEdit(measurementId);
        } else {
            title.textContent = 'Tambah Pengukuran Baru';
            form.reset();
            document.getElementById('measurementId').value = '';
            
            // Pre-select current pet if available
            if (this.currentPetId) {
                document.getElementById('petSelect').value = this.currentPetId;
                document.getElementById('selectedPetId').value = this.currentPetId;
            }
            
            // Set current time
            const now = new Date();
            const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('recordedDate').value = localDate;
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    closeMeasurementModal() {
        const modal = document.getElementById('measurementModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    populateFormForEdit(measurementId) {
        const measurement = this.measurements.find(m => m.id === measurementId);
        if (!measurement) return;
        
        document.getElementById('measurementId').value = measurement.id;
        document.getElementById('petSelect').value = measurement.pet_id;
        document.getElementById('selectedPetId').value = measurement.pet_id;
        
        // Format datetime for input
        const date = new Date(measurement.recorded_at);
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('recordedDate').value = localDate;
        
        document.getElementById('weight').value = measurement.weight || '';
        document.getElementById('length').value = measurement.length || '';
        document.getElementById('notes').value = measurement.notes || '';
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const measurementId = document.getElementById('measurementId').value;
        
        formData.append('pet_id', document.getElementById('petSelect').value);
        formData.append('recorded_at', document.getElementById('recordedDate').value);
        formData.append('weight', document.getElementById('weight').value);
        formData.append('length', document.getElementById('length').value);
        formData.append('notes', document.getElementById('notes').value);
        
        if (measurementId) {
            formData.append('measurement_id', measurementId);
        }
        
        try {
            const response = await fetch('api/save-measurement.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeMeasurementModal();
                
                // Reload measurements for current pet
                if (this.currentPetId) {
                    await this.loadMeasurements(this.currentPetId);
                    this.renderMeasurementsTable();
                    this.updateInsights();
                }
                
                this.showNotification('Pengukuran berhasil disimpan!', 'success');
            } else {
                this.showNotification(data.message || 'Terjadi kesalahan saat menyimpan pengukuran', 'error');
            }
        } catch (error) {
            console.error('Error saving measurement:', error);
            this.showNotification('Terjadi kesalahan saat menyimpan pengukuran', 'error');
        }
    }

    async editMeasurement(measurementId) {
        this.openMeasurementModal(measurementId);
    }

    async deleteMeasurement(measurementId) {
        if (!confirm('Apakah Anda yakin ingin menghapus pengukuran ini?')) {
            return;
        }
        
        try {
            const response = await fetch('api/delete-measurement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ measurement_id: measurementId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload measurements for current pet
                if (this.currentPetId) {
                    await this.loadMeasurements(this.currentPetId);
                    this.renderMeasurementsTable();
                    this.updateInsights();
                }
                
                this.showNotification('Pengukuran berhasil dihapus!', 'success');
            } else {
                this.showNotification(data.message || 'Terjadi kesalahan saat menghapus pengukuran', 'error');
            }
        } catch (error) {
            console.error('Error deleting measurement:', error);
            this.showNotification('Terjadi kesalahan saat menghapus pengukuran', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.querySelector('.notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
        
        notification.textContent = message;
        notification.className = `notification show ${type}`;
        
        // Add styles if not already added
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 24px;
                    border-radius: 8px;
                    color: white;
                    font-size: 14px;
                    font-weight: 500;
                    z-index: 9999;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                }
                .notification.show {
                    transform: translateX(0);
                }
                .notification.success {
                    background-color: #4ECDC4;
                }
                .notification.error {
                    background-color: #FF6B6B;
                }
                .notification.info {
                    background-color: #45B7D1;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
}

// Global functions for onclick handlers
function openMeasurementModal() {
    measurementsManager.openMeasurementModal();
}

function closeMeasurementModal() {
    measurementsManager.closeMeasurementModal();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.measurementsManager = new MeasurementsManager();
});

