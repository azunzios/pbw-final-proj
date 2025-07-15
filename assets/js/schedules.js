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
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    currentWeekStart = new Date(today);
    currentWeekStart.setDate(today.getDate() + mondayOffset);
    
    updateWeekDisplay();
}

function goToToday() {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
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

    // Update day dates
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    days.forEach((day, index) => {
        const date = new Date(currentWeekStart);
        if (day === 'sunday') {
            date.setDate(date.getDate() - 1);
        } else {
            date.setDate(date.getDate() + (index - 1));
        }
        
        const dateElement = document.getElementById(`${day}-date`);
        if (dateElement) {
            dateElement.textContent = date.getDate();
        }

        // Highlight today
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
        // Calculate week range
        const startDate = new Date(currentWeekStart);
        startDate.setDate(startDate.getDate() - 1); // Sunday start
        const endDate = new Date(currentWeekStart);
        endDate.setDate(endDate.getDate() + 5); // Saturday end
        
        const startDateStr = formatDate(startDate);
        const endDateStr = formatDate(endDate);
        
        const response = await fetch(getAbsoluteUrl(`api/get-schedules.php?start_date=${startDateStr}&end_date=${endDateStr}`));
        
        if (!response.ok) {
            throw new Error('Gagal memuat data jadwal');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            showNotification('Gagal memuat data jadwal', 'error');
            return;
        }
        
        schedules = data.data.map(schedule => ({
            ...schedule,
            status: getScheduleStatus(schedule)
        }));
        
        updateCalendarView();
        updateScheduleList();
        
    } catch (error) {
        console.error('Error loading schedules:', error);
        showNotification('Gagal memuat jadwal. Silakan coba lagi nanti.', 'error');
    }
}

// Determine schedule status
function getScheduleStatus(schedule) {
    const now = new Date();
    const scheduleDateTime = new Date(`${schedule.date} ${schedule.schedule_time}`);
    
    if (schedule.is_done == 1) {
        return 'completed';
    } else if (scheduleDateTime < now) {
        return 'missed';
    } else {
        return 'upcoming';
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
            case 0: dayName = 'sunday'; break;
            case 1: dayName = 'monday'; break;
            case 2: dayName = 'tuesday'; break;
            case 3: dayName = 'wednesday'; break;
            case 4: dayName = 'thursday'; break;
            case 5: dayName = 'friday'; break;
            case 6: dayName = 'saturday'; break;
            default: return;
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
    card.className = `schedule-card ${schedule.status}`;
    card.onclick = () => editSchedule(schedule.id);
    
    card.innerHTML = `
        <div class="schedule-time">${formatTime(schedule.schedule_time)}</div>
        <div class="schedule-type">${schedule.care_type}</div>
        <div class="schedule-pet">${schedule.pet_name}</div>
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

    // Sort by date and time
    filteredSchedules.sort((a, b) => {
        const dateTimeA = new Date(`${a.date} ${a.schedule_time}`);
        const dateTimeB = new Date(`${b.date} ${b.schedule_time}`);
        return dateTimeA - dateTimeB;
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
                    ${schedule.status === 'upcoming' ? 
                        `<button class="btn-small btn-complete" onclick="completeSchedule(${schedule.id})">Selesai</button>` : ''
                    }
                    <button class="btn-small btn-edit" onclick="editSchedule(${schedule.id})">Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteSchedule(${schedule.id})">Hapus</button>
                </div>
            </div>
            <div class="schedule-item-pet">🐾 ${schedule.pet_name}</div>
            ${schedule.description ? `<div class="schedule-item-notes">${schedule.description}</div>` : ''}
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
        document.getElementById('start_date').value = formatDate(today);
    }
    
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
            document.getElementById('pet_id').value = schedule.pet_id;
            document.getElementById('care_type').value = schedule.care_type;
            document.getElementById('schedule_time').value = schedule.schedule_time;
            document.getElementById('start_date').value = schedule.date;
            document.getElementById('description').value = schedule.description || '';
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
        console.error('Error deleting schedule:',error);
        showNotification('Terjadi kesalahan saat menghapus jadwal', 'error');
    }
}

async function completeSchedule(scheduleId) {
    try {
        const response = await fetch(getAbsoluteUrl('api/complete-schedule.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ schedule_id: scheduleId })
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
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatTime(timeString) {
    if (timeString.includes(' ')) {
        const timePart = timeString.split(' ')[1];
        return timePart.substring(0, 5);
    } else {
        return timeString.substring(0, 5);
    }
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        backgroundColor: type === 'success' ? '#10b981' : 
                        type === 'error' ? '#ef4444' : '#3b82f6',
        color: 'white',
        padding: '12px 20px',
        borderRadius: '8px',
        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
        zIndex: '10000',
        opacity: '0',
        transform: 'translateX(100%)',
        transition: 'all 0.3s ease',
        maxWidth: '300px',
        wordWrap: 'break-word'
    });
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
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