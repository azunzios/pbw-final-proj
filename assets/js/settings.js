// Global variables
let activeTab = 'profile';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupTabNavigation();
    setupFormSubmissions();
});

// Tab Navigation
function setupTabNavigation() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.dataset.tab;
            setActiveTab(tab);
        });
    });
}

function setActiveTab(tab) {
    // Update active state for tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
    
    // Show the corresponding panel
    document.querySelectorAll('.settings-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    document.getElementById(`${tab}-panel`).classList.add('active');
    
    // Update active tab
    activeTab = tab;
}

// Form Submissions
function setupFormSubmissions() {
    // Profile Form
    document.getElementById('profile-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        await handleProfileUpdate(this);
    });
    
    // Password Form
    document.getElementById('password-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        await handlePasswordUpdate(this);
    });
    
    // Preferences Form
    document.getElementById('preferences-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        await handlePreferencesUpdate(this);
    });
}

// Profile Update Handler
async function handleProfileUpdate(form) {
    try {
        const formData = new FormData();
        
        // Get form values
        const fullname = form.elements['fullname'].value.trim();
        const email = form.elements['email'].value.trim();
        
        if (!fullname) {
            showMessage('profile', 'error', 'Nama lengkap wajib diisi');
            return;
        }
        
        // Add form data
        formData.append('fullname', fullname);
        formData.append('email', email);
        
        const response = await fetch('api/update-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('profile', 'success', 'Profil berhasil diperbarui!');
            
            // Reload after a short delay to show updated name in sidebar
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage('profile', 'error', data.message || 'Gagal memperbarui profil');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showMessage('profile', 'error', 'Terjadi kesalahan saat memperbarui profil');
    }
}

// Password Update Handler
async function handlePasswordUpdate(form) {
    try {
        // Validate passwords match
        const newPassword = form.elements['new_password'].value;
        const confirmPassword = form.elements['confirm_password'].value;
        
        if (newPassword !== confirmPassword) {
            showMessage('security', 'error', 'Password baru dan konfirmasi tidak cocok');
            return;
        }
        
        const formData = new FormData(form);
        
        const response = await fetch('api/update-password.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('security', 'success', 'Password berhasil diperbarui!');
            form.reset();
        } else {
            showMessage('security', 'error', data.message || 'Gagal memperbarui password');
        }
    } catch (error) {
        console.error('Error updating password:', error);
        showMessage('security', 'error', 'Terjadi kesalahan saat memperbarui password');
    }
}

// Preferences Update Handler
async function handlePreferencesUpdate(form) {
    try {
        const formData = new FormData(form);
        
        // Get timezone value
        const timezone = form.elements['timezone'].value;
        formData.set('timezone', timezone);
        
        const response = await fetch('api/update-preferences.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('preferences', 'success', 'Zona waktu berhasil diperbarui!');
            
            // Force reload to apply timezone changes
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage('preferences', 'error', data.message || 'Gagal memperbarui zona waktu');
        }
    } catch (error) {
        console.error('Error updating preferences:', error);
        showMessage('preferences', 'error', 'Terjadi kesalahan saat memperbarui zona waktu');
    }
}

// Helper Functions
function showMessage(tab, type, message) {
    // Remove any existing messages
    const existingMessage = document.querySelector(`#${tab}-panel .message`);
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `message ${type}-message`;
    messageEl.textContent = message;
    
    // Insert at the top of the form
    const form = document.querySelector(`#${tab}-panel .settings-form`);
    form.insertBefore(messageEl, form.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageEl.remove();
    }, 5000);
}

// Show notification for successful updates
function showNotification(message, type = 'success') {
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
