// Sidebar Toggle Functions
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    hamburger.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');
    
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    hamburger.classList.remove('active');
}

// Fungsi untuk logout
function logout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        window.location.href = 'auth/logout.php';
    }
}

// Fungsi untuk login
function handleLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Tampilkan loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Memproses...';
    submitBtn.disabled = true;
    
    fetch('auth/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Fungsi untuk register
function handleRegister(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validasi password match
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password !== confirmPassword) {
        showAlert('Konfirmasi kata sandi tidak cocok', 'danger');
        return;
    }
    
    // Tampilkan loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Mendaftar...';
    submitBtn.disabled = true;
    
    fetch('auth/register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                showLogin();
                form.reset();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Fungsi untuk switching forms
function showLogin() {
    hideAllForms();
    document.getElementById('loginForm').classList.add('active');
}

function showRegister() {
    hideAllForms();
    document.getElementById('registerForm').classList.add('active');
}

function hideAllForms() {
    document.querySelectorAll('.login-form').forEach(form => {
        form.classList.remove('active');
    });
}

// Fungsi untuk validasi password match
function checkPasswordMatch() {
    const password = document.getElementById('reg_password');
    const confirmPassword = document.getElementById('reg_confirm_password');
    const message = document.getElementById('passwordMatchMessage');
    
    if (password && confirmPassword && message) {
        if (confirmPassword.value !== '' && password.value !== confirmPassword.value) {
            message.style.display = 'block';
            confirmPassword.style.borderColor = '#e74c3c';
        } else {
            message.style.display = 'none';
            confirmPassword.style.borderColor = '';
        }
    }
}

// Fungsi untuk menampilkan alert
function showAlert(message, type = 'info') {
    // Hapus alert sebelumnya
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Buat alert baru
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Masukkan ke form yang aktif
    const activeForm = document.querySelector('.login-form.active');
    if (activeForm) {
        activeForm.insertBefore(alert, activeForm.firstChild);
        
        // Auto remove setelah 5 detik
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Fungsi untuk toggle sidebar mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    hamburger.classList.toggle('active');
}

// Fungsi untuk close sidebar mobile
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');
    
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    hamburger.classList.remove('active');
}

// Fungsi untuk mendapatkan sapaan berdasarkan waktu
function getGreeting() {
    const hour = new Date().getHours();
    
    if (hour >= 5 && hour < 12) {
        return 'Selamat Pagi';
    } else if (hour >= 12 && hour < 15) {
        return 'Selamat Siang';
    } else if (hour >= 15 && hour < 18) {
        return 'Selamat Sore';
    } else {
        return 'Selamat Malam';
    }
}

// Fungsi untuk update greeting
function updateGreeting() {
    const greetingElement = document.querySelector('.greeting-text');
    if (greetingElement) {
        greetingElement.textContent = getGreeting();
    }
}

// Fungsi untuk format tanggal Indonesia
function formatDateIndonesia(date) {
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    const dayName = days[date.getDay()];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    
    return `${dayName}, ${day} ${month} ${year}`;
}

// Fungsi untuk logout
function logout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        fetch('auth/logout.php', {
            method: 'POST'
        })
        .then(() => {
            window.location.href = 'index.php';
        });
    }
}

// Initialize saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Update greeting saja
    updateGreeting();
    
    // Update greeting setiap menit
    setInterval(updateGreeting, 60000);
    
    // Event listener untuk overlay sidebar
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
});
