// Global variables
let currentWeekStart = new Date();
let currentFilter = 'upcoming';
let schedules = [];

// Helper function to create absolute URLs
function getAbsoluteUrl(relativePath) {
    const basePath = window.location.pathname.replace(/[^/]*$/, '');
    return window.location.origin + basePath + relativePath;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeWeek();
    loadSchedules();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            setActiveFilter(this.dataset.filter);
        });
    });

    // Schedule form submission
    document.getElementById('scheduleForm').addEventListener('submit', handleScheduleSubmit);
    
    // Recurrence change handler
    document.getElementById('recurrence').addEventListener('change', updateScheduleForm);

    // Modal close on outside click
    document.getElementById('scheduleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeScheduleModal();
        }
    });
}

// Week navigation
function initializeWeek() {
    // Set to start of current week (Monday)
    const today = new Date();
    const dayOfWeek = today.getDay();
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Handle Sunday
    currentWeekStart = new Date(today);
    currentWeekStart.setDate(today.getDate() + mondayOffset);
    
    // We'll still keep Monday as the current week start internally
    // but our updateWeekDisplay will handle Sunday properly
    updateWeekDisplay();
}

function goToToday() {
    // Reset to the actual current week using a fresh date
    const today = new Date();
    const dayOfWeek = today.getDay();
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Handle Sunday
    currentWeekStart = new Date(today);
    currentWeekStart.setDate(today.getDate() + mondayOffset);
    
    updateWeekDisplay();
    loadSchedules();
}

function navigateWeek(direction) {
    currentWeekStart.setDate(currentWeekStart.getDate() + (direction * 7));
    updateWeekDisplay();
    loadSchedules();
}

function updateWeekDisplay() {
    const weekTitle = document.getElementById('weekTitle');
    // Minggu (Sunday) adalah satu hari sebelum Senin (Monday)
    const startDate = new Date(currentWeekStart);
    startDate.setDate(startDate.getDate() - 1); // Sunday
    const endDate = new Date(currentWeekStart);
    endDate.setDate(endDate.getDate() + 5); // Saturday

    const options = { day: 'numeric', month: 'short' };
    weekTitle.textContent = `${startDate.toLocaleDateString('id-ID', options)} - ${endDate.toLocaleDateString('id-ID', options)} ${endDate.getFullYear()}`;

    // Clear all "today" highlights first
    document.querySelectorAll('.day-column').forEach(col => {
        col.classList.remove('today');
    });

    // Get current date for comparison
    const today = new Date();
    const todayString = today.toDateString();

    // Update day dates
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    days.forEach((day, index) => {
        // Untuk Sunday (Minggu), gunakan tanggal 1 hari sebelum currentWeekStart
        const date = new Date(currentWeekStart);
        if (day === 'sunday') {
            date.setDate(date.getDate() - 1); // Sunday is 1 day before Monday
        } else {
            // Untuk hari-hari lainnya, index-1 karena kita mulai dari hari Minggu (index 0)
            date.setDate(date.getDate() + (index - 1));
        }
        
        const dateElement = document.getElementById(`${day}-date`);
        if (dateElement) {
            dateElement.textContent = date.getDate();
        }

        // Debug logs
        console.log(`${day}: ${date.toDateString()}, Today: ${todayString}, Match: ${date.toDateString() === todayString}`);

        // Highlight today - compare year, month, and day
        const dayColumn = document.querySelector(`[data-day="${day}"]`);
        if (date.getFullYear() === today.getFullYear() && 
            date.getMonth() === today.getMonth() && 
            date.getDate() === today.getDate()) {
            dayColumn.classList.add('today');
        }
    });
}

// Load schedules from API
async function loadSchedules() {
    try {
        // Ambil startDate sebagai hari Minggu (1 hari sebelum hari Senin)
        const sundayStart = new Date(currentWeekStart);
        sundayStart.setDate(sundayStart.getDate() - 1);
        sundayStart.setHours(0, 0, 0, 0); // Set to beginning of day
        const startDate = formatDate(sundayStart);
        
        // endDate tetap 6 hari dari Senin (yaitu Sabtu)
        const endDate = new Date(currentWeekStart);
        endDate.setDate(endDate.getDate() + 5); // 5 days after Monday = Saturday
        endDate.setHours(23, 59, 59, 999); // Set to end of day
        const endDateStr = formatDate(endDate);

        const apiUrl = `api/get-schedules.php?start=${startDate}&end=${endDateStr}`;
        
        // Use absolute URL to avoid any base URL issues
        const absoluteUrl = getAbsoluteUrl(apiUrl);
        
        const response = await fetch(absoluteUrl);
        
        // Cek apakah response OK
        if (!response.ok) {
            throw new Error(`Server merespon dengan status ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();

        if (data.success) {
            schedules = Array.isArray(data.schedules) ? data.schedules : [];
            updateCalendarView();
            updateScheduleList();
        } else {
            console.error('Failed to load schedules:', data.message);
            showNotification(data.message || 'Gagal memuat data jadwal', 'error');
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
        showNotification('Gagal memuat jadwal. Silakan coba lagi nanti.', 'error');
    }
}

// Update calendar view
function updateCalendarView() {
    // Clear existing schedule cards
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    days.forEach(day => {
        const container = document.getElementById(`${day}-schedules`);
        if (container) {
            container.innerHTML = '';
        }
    });

    // Add schedule cards to appropriate days
    schedules.forEach(schedule => {
        const scheduleDate = new Date(schedule.date);
        const dayOfWeek = scheduleDate.getDay();
        
        let dayName;
        switch(dayOfWeek) {
            case 0: dayName = 'sunday'; break;  // Added Sunday
            case 1: dayName = 'monday'; break;
            case 2: dayName = 'tuesday'; break;
            case 3: dayName = 'wednesday'; break;
            case 4: dayName = 'thursday'; break;
            case 5: dayName = 'friday'; break;
            case 6: dayName = 'saturday'; break;
            default: return; // Should never happen
        }

        const container = document.getElementById(`${dayName}-schedules`);
        if (container) {
            container.appendChild(createScheduleCard(schedule));
        }
    });
}

// Create schedule card for calendar
function createScheduleCard(schedule) {
    const card = document.createElement('div');
    
    // Add status class (completed, missed, or upcoming)
    let statusClass = schedule.status;
    
    // Add recurrence type class for color coding
    let recurrenceClass = '';
    switch(schedule.recurrence) {
        case 'Once': recurrenceClass = 'once'; break;
        case 'Daily': recurrenceClass = 'daily'; break;
        case 'Weekly': recurrenceClass = 'weekly'; break;
        case 'Monthly': recurrenceClass = 'monthly'; break;
    }
    
    // Debug info for this schedule
    console.log(`Creating card for schedule ID ${schedule.id}, date: ${schedule.date}, type: ${schedule.care_type}, recurrence: ${schedule.recurrence}`);
    
    card.className = `schedule-card ${statusClass} ${recurrenceClass}`;
    card.onclick = () => editSchedule(schedule.id);

    card.innerHTML = `
        <div class="schedule-time">${formatTime(schedule.schedule_time)}</div>
        <div class="schedule-type">${schedule.care_type}</div>
        <div class="schedule-pet">🐾 ${schedule.pet_name}</div>
        <div class="schedule-label">${schedule.recurrence}</div>
        <div class="schedule-status ${schedule.status}"></div>
    `;

    return card;
}

// Update schedule list
function updateScheduleList() {
    const container = document.getElementById('scheduleList');
    
    let filteredSchedules = schedules.filter(schedule => {
        switch(currentFilter) {
            case 'upcoming':
                return schedule.status === 'upcoming';
            case 'completed':
                return schedule.status === 'completed';
            case 'missed':
                return schedule.status === 'missed';
            default:
                return true;
        }
    });

    // Sort by date and time for upcoming, by completion time for completed
    filteredSchedules.sort((a, b) => {
        if (currentFilter === 'completed') {
            // Sort completed by done_at time (most recent first)
            return new Date(b.done_at || 0) - new Date(a.done_at || 0);
        } else {
            // Sort others by scheduled time
            const dateTimeA = new Date(`${a.date} ${a.schedule_time}`);
            const dateTimeB = new Date(`${b.date} ${b.schedule_time}`);
            return dateTimeA - dateTimeB;
        }
    });

    if (filteredSchedules.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <p>Tidak ada jadwal ${getFilterText(currentFilter)}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = filteredSchedules.map(schedule => createScheduleListItem(schedule)).join('');
}

// Create schedule list item
function createScheduleListItem(schedule) {
    const scheduleDate = new Date(schedule.date);
    const today = new Date();
    const isToday = scheduleDate.toDateString() === today.toDateString();
    const isTomorrow = scheduleDate.toDateString() === new Date(today.getTime() + 24*60*60*1000).toDateString();
    
    let dateText;
    if (isToday) {
        dateText = 'Hari ini';
    } else if (isTomorrow) {
        dateText = 'Besok';
    } else {
        dateText = scheduleDate.toLocaleDateString('id-ID', { 
            weekday: 'short', 
            day: 'numeric', 
            month: 'short' 
        });
    }

    return `
        <div class="schedule-item" onclick="editSchedule(${schedule.id})">
            <div class="schedule-item-header">
                <div>
                    <div class="schedule-item-title">${schedule.care_type}</div>
                    <div class="schedule-item-time">${dateText} • ${formatTime(schedule.schedule_time)}</div>
                </div>
                <div class="schedule-item-actions" onclick="event.stopPropagation()">
                    ${schedule.status === 'upcoming' ? `<button class="btn-small btn-complete" onclick="completeSchedule(${schedule.instance_id})">Selesai</button>` : ''}
                    <button class="btn-small btn-edit" onclick="editSchedule(${schedule.id})">Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteSchedule(${schedule.id})">Hapus</button>
                </div>
            </div>
            <div class="schedule-item-pet">🐾 ${schedule.pet_name}</div>
            ${schedule.notes ? `<div class="schedule-item-notes">${schedule.notes}</div>` : ''}
        </div>
    `;
}

// Filter functions
function setActiveFilter(filter) {
    currentFilter = filter;
    
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
    
    updateScheduleList();
}

function getFilterText(filter) {
    switch(filter) {
        case 'upcoming': return 'yang akan datang';
        case 'completed': return 'yang sudah selesai';
        case 'missed': return 'yang terlewat';
        default: return '';
    }
}

// Modal functions
function openScheduleModal(scheduleId = null) {
    const modal = document.getElementById('scheduleModal');
    const form = document.getElementById('scheduleForm');
    const title = document.getElementById('modalTitle');
    
    if (scheduleId) {
        title.textContent = 'Edit Jadwal';
        loadScheduleData(scheduleId);
    } else {
        title.textContent = 'Tambah Jadwal Baru';
        form.reset();
        document.getElementById('scheduleId').value = '';
        
        // Set default date to today
        const today = new Date();
        document.getElementById('scheduleDate').value = formatDate(today);
        
        // Default ke sekali
        document.getElementById('recurrence').value = 'Once';
    }
    
    // Update form berdasarkan jenis pengulangan yang dipilih
    updateScheduleForm();
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Schedule CRUD operations
async function handleScheduleSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const scheduleId = formData.get('scheduleId');
    const recurrence = formData.get('recurrence');
    
    // Validasi berdasarkan jenis pengulangan
    if (recurrence === 'Weekly') {
        // Periksa apakah setidaknya satu hari dipilih untuk pengulangan mingguan
        const checkedDays = document.querySelectorAll('input[name="days[]"]:checked');
        if (checkedDays.length === 0) {
            showNotification('Pilih setidaknya satu hari untuk pengulangan mingguan', 'error');
            return;
        }
        
        // Kumpulkan hari-hari yang dipilih
        const selectedDays = Array.from(checkedDays).map(cb => cb.value);
        formData.set('days', selectedDays.join(','));
    }
    
    try {
        const response = await fetch(getAbsoluteUrl('api/save-schedule.php'), {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeScheduleModal();
            loadSchedules();
            showNotification('Jadwal berhasil disimpan!', 'success');
        } else {
            showNotification(data.message || 'Gagal menyimpan jadwal', 'error');
        }
    } catch (error) {
        console.error('Error saving schedule:', error);
        showNotification('Terjadi kesalahan saat menyimpan jadwal', 'error');
    }
}

async function loadScheduleData(scheduleId) {
    try {
        const response = await fetch(getAbsoluteUrl(`api/get-schedule-details.php?id=${scheduleId}`));
        const data = await response.json();
        
        if (data.success) {
            const schedule = data.schedule;
            
            document.getElementById('scheduleId').value = schedule.id;
            document.getElementById('petSelect').value = schedule.pet_id;
            document.getElementById('careType').value = schedule.care_type;
            document.getElementById('scheduleTime').value = schedule.schedule_time;
            document.getElementById('scheduleDate').value = schedule.date;
            document.getElementById('recurrence').value = schedule.recurrence || 'Once';
            document.getElementById('notes').value = schedule.notes || '';
            
            // Update form berdasarkan jenis pengulangan
            updateScheduleForm();
            
            // Jika pengulangan mingguan, cek hari-hari yang sesuai
            if (schedule.recurrence === 'Weekly' && schedule.days) {
                const days = schedule.days.split(',').map(day => day.trim());
                document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
                    if (days.includes(checkbox.value)) {
                        checkbox.checked = true;
                    }
                });
            }
        } else {
            showNotification('Gagal memuat data jadwal', 'error');
        }
    } catch (error) {
        console.error('Error loading schedule data:', error);
        showNotification('Terjadi kesalahan saat memuat data jadwal', 'error');
    }
}

function editSchedule(scheduleId) {
    openScheduleModal(scheduleId);
}

async function deleteSchedule(scheduleId) {
    if (!confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        return;
    }
    
    try {
        const response = await fetch(getAbsoluteUrl('api/delete-schedule.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: scheduleId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadSchedules();
            showNotification('Jadwal berhasil dihapus!', 'success');
        } else {
            showNotification(data.message || 'Gagal menghapus jadwal', 'error');
        }
    } catch (error) {
        console.error('Error deleting schedule:', error);
        showNotification('Terjadi kesalahan saat menghapus jadwal', 'error');
    }
}

async function completeSchedule(instanceId) {
    try {
        const response = await fetch(getAbsoluteUrl('api/complete-schedule.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ instance_id: instanceId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadSchedules();
            showNotification('Jadwal berhasil diselesaikan!', 'success');
        } else {
            showNotification(data.message || 'Gagal menyelesaikan jadwal', 'error');
        }
    } catch (error) {
        console.error('Error completing schedule:', error);
        showNotification('Terjadi kesalahan saat menyelesaikan jadwal', 'error');
    }
}

// Utility functions
function formatDate(date) {
    // Format date in YYYY-MM-DD format for Asia/Jakarta timezone
    const offset = 7 * 60; // UTC+7 for Asia/Jakarta in minutes
    const localTime = new Date(date.getTime() + offset * 60000);
    return localTime.toISOString().split('T')[0];
}

function formatTime(timeString) {
    return timeString.substring(0, 5); // HH:MM format
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        backgroundColor: type === 'success' ? 'var(--secondary-mint)' : 
                        type === 'error' ? 'var(--secondary-coral)' : 'var(--primary-blue)',
        color: 'white',
        padding: '12px 20px',
        borderRadius: '8px',
        boxShadow: 'var(--shadow-medium)',
        zIndex: '10000',
        opacity: '0',
        transform: 'translateX(100%)',
        transition: 'all 0.3s ease',
        maxWidth: '300px',
        wordWrap: 'break-word'
    });
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Function to update form based on recurrence type
function updateScheduleForm() {
    const recurrence = document.getElementById('recurrence').value;
    const daySelectionGroup = document.getElementById('daySelectionGroup');
    const dateSelectorGroup = document.getElementById('dateSelectorGroup');
    
    // Reset checkboxes
    document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    switch (recurrence) {
        case 'Once':
            // Sekali: Tampilkan tanggal, waktu
            daySelectionGroup.style.display = 'none';
            dateSelectorGroup.style.display = 'block';
            break;
            
        case 'Daily':
            // Harian: Hanya tampilkan waktu
            daySelectionGroup.style.display = 'none';
            dateSelectorGroup.style.display = 'none';
            break;
            
        case 'Weekly':
            // Mingguan: Tampilkan pilihan hari dan waktu
            daySelectionGroup.style.display = 'block';
            dateSelectorGroup.style.display = 'none';
            break;
            
        case 'Monthly':
            // Bulanan: Tampilkan tanggal dan waktu
            daySelectionGroup.style.display = 'none';
            dateSelectorGroup.style.display = 'block';
            break;
    }
}
