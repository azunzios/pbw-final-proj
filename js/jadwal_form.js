// js/jadwal_form.js
document.addEventListener('DOMContentLoaded', function() {
    const isRoutineCheckbox = document.getElementById('is_routine');
    const nonRoutineFields = document.getElementById('non-routine-fields');
    const routineOptionsDiv = document.getElementById('routine-options');
    const routineTypeSelect = document.getElementById('routine_type');
    const routineDetailsDiv = document.getElementById('routine-details');

    isRoutineCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        routineOptionsDiv.style.display = isChecked ? 'block' : 'none';
        nonRoutineFields.style.display = isChecked ? 'none' : 'block';

        // Atur required fields berdasarkan pilihan
        document.querySelector('input[name="schedule_date"]').required = !isChecked;
        document.querySelector('input[name="schedule_time"]').required = !isChecked;

        // Hapus required dari semua input di routineOptionsDiv
        Array.from(routineOptionsDiv.querySelectorAll('input, select')).forEach(function(input) {
            input.required = false;
        });

        if(isChecked) {
            updateRoutineDetails();
        }
    });

    routineTypeSelect.addEventListener('change', updateRoutineDetails);

    function updateRoutineDetails() {
        const type = routineTypeSelect.value;
        let html = '<div class="form-group"><label for="time">Jam:</label><input type="time" name="routine_time" required></div>';

        if (type === 'mingguan') {
            html += `<div class="form-group"><label for="day">Hari:</label><select name="day" class="form-control">
                        <option value="1">Senin</option><option value="2">Selasa</option>
                        <option value="3">Rabu</option><option value="4">Kamis</option>
                        <option value="5">Jumat</option><option value="6">Sabtu</option>
                        <option value="7">Minggu</option>
                     </select></div>`;
        } else if (type === 'bulanan') {
            html += '<div class="form-group"><label for="date">Tanggal (1-31):</label><input type="number" name="date" class="form-control" min="1" max="31" required></div>';
        }
        routineDetailsDiv.innerHTML = html;

        // Pastikan hanya input yang tampil yang required
        Array.from(routineDetailsDiv.querySelectorAll('input, select')).forEach(function(input) {
            input.required = true;
        });
    }
    
    // Inisialisasi awal
    nonRoutineFields.style.display = 'block';
    routineOptionsDiv.style.display = 'none';
    document.querySelector('input[name="schedule_date"]').required = true;
});