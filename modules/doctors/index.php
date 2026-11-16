<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin']);
$pageTitle = 'Doctors'; $activeNav = 'doctors'; $libs = ['datatables']; $pageScripts = ['assets/js/crud.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
$depts = array_map(static fn($d) => [$d['id'], $d['name']], DepartmentModel::options());
?>
<div class="card-panel">
    <div class="d-flex align-items-center mb-3"><div><h5 class="mb-0 fw-bold">Doctors</h5><small class="text-muted">Profile, consultation fee and weekly schedule (drives appointment slots).</small></div>
        <button class="btn btn-gradient ms-auto" id="btnAdd"><i class="bi bi-person-plus-fill me-1"></i> Add Doctor</button></div>
    <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100"><thead><tr><th>Doctor</th><th>Department</th><th>Schedule</th><th class="text-end">Fee</th><th>Room</th><th>Status</th><th class="text-end">Actions</th></tr></thead></table></div>
</div>
<?php ob_start(); ?>
<script>
var DAYS = <?= json_encode(DoctorModel::DAYS) ?>;
var crud = new Crud({ table: '#tbl', api: APP.base + '/doctors/api', label: 'Doctor', canWrite: true, canDelete: false, modalSize: 'modal-lg', rowName: function (r) { return r.full_name; },
    columns: [
        { data: 'full_name', render: function (d, t, r) { return '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-sm">' + SMS.esc(d.replace('Dr. ', '').split(' ').map(function (p) { return p[0]; }).slice(0, 2).join('')) + '</span><div class="lh-sm"><div class="fw-semibold">' + SMS.esc(d) + '</div><small class="text-muted">' + SMS.esc(r.specialization) + '</small></div></div>'; } },
        { data: 'department' },
        { data: null, render: function (r) { return '<div class="small">' + SMS.esc(r.available_days.split(',').join(' · ')) + '</div><small class="text-muted">' + r.start_time.slice(0, 5) + ' – ' + r.end_time.slice(0, 5) + ' · ' + r.slot_minutes + ' min slots</small>'; } },
        { data: 'consultation_fee', className: 'text-end', render: function (d, t) { return t === 'display' ? SMS.money(d) : d; } },
        { data: 'room_no', render: function (d) { return d ? SMS.esc(d) : '–'; } },
        { data: 'is_active', render: function (d) { return parseInt(d, 10) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>'; } }],
    actions: function (r) { return '<button class="btn btn-icon btn-soft-warning" title="' + (parseInt(r.is_active, 10) ? 'Deactivate' : 'Activate') + '" data-act="toggle"><i class="bi bi-power"></i></button>'; },
    onAction: function (act, row, c) { if (act !== 'toggle') return false;
        SMS.confirmBox((parseInt(row.is_active, 10) ? 'Deactivate ' : 'Activate ') + row.full_name + '?', 'Deactivated doctors cannot sign in or take new bookings.').then(function (ok) { if (!ok) return;
            var fd = new FormData(); fd.append('action', 'toggle'); fd.append('id', row.id); fd.append('csrf_token', APP.csrf);
            SMS.api(APP.base + '/doctors/api', { method: 'POST', body: fd }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); c.reload(); }); }); return true; },
    fields: [
        { name: 'full_name', label: 'Full name', required: true, max: 120, col: 6, placeholder: 'Dr. Name' }, { name: 'username', label: 'Username', required: true, max: 50, col: 3, unique: true, pattern: '^[A-Za-z0-9._\\-]{3,50}$', patternMsg: '3-50 chars: a-z, 0-9 . _ -' },
        { name: 'password', label: 'Password', type: 'password', col: 3, max: 72, help: 'Min 8. Blank = keep.' },
        { name: 'email', label: 'Email', type: 'email', max: 120, col: 6, unique: true }, { name: 'phone', label: 'Phone', type: 'tel', max: 20, col: 6, pattern: '^[0-9+\\-\\s]{9,20}$', patternMsg: 'Enter a valid phone number.' },
        { name: 'department_id', label: 'Department', type: 'select', required: true, col: 4, options: <?= json_encode($depts) ?> },
        { name: 'specialization', label: 'Specialization', required: true, max: 100, col: 4 }, { name: 'qualification', label: 'Qualification', max: 150, col: 4 },
        { name: 'consultation_fee', label: 'Consultation fee', type: 'number', required: true, min: 0, step: '0.01', col: 4 }, { name: 'room_no', label: 'Room', max: 20, col: 2 },
        { name: 'start_time', label: 'From', type: 'time', required: true, col: 2 }, { name: 'end_time', label: 'To', type: 'time', required: true, col: 2 },
        { name: 'slot_minutes', label: 'Slot (min)', type: 'number', required: true, min: 10, step: '5', col: 2, default: 20 }
    ],
    onOpen: function (row, c) {
        if (!c.dayBox) { var h = document.createElement('div'); h.className = 'col-12'; h.innerHTML = '<label class="form-label">Working days <span class="text-danger">*</span></label><div class="d-flex flex-wrap gap-2" id="dayBox">' +
            DAYS.map(function (d) { return '<input type="checkbox" class="btn-check" id="day_' + d + '" value="' + d + '"><label class="btn btn-sm btn-outline-primary" for="day_' + d + '">' + d + '</label>'; }).join('') + '</div><div class="invalid-feedback d-block" data-for="available_days"></div>';
            c.form.querySelector('.row').appendChild(h); c.dayBox = h; }
        var set = row ? row.available_days.split(',') : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        DAYS.forEach(function (d) { document.getElementById('day_' + d).checked = set.indexOf(d) >= 0; });
        c.form.elements.password.placeholder = row ? 'Unchanged' : '';
    }
});
// make the Crud submit include the day checkboxes
var _fd = FormData.prototype.append;
document.addEventListener('submit', function (e) { var f = e.target; if (!f.closest || !f.closest('.modal')) return; f.querySelectorAll('input[name=available_days]').forEach(function (x) { x.remove(); });
    DAYS.forEach(function (d) { var cb = document.getElementById('day_' + d); if (cb && cb.checked) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'available_days[]'; h.value = d; f.appendChild(h); } }); }, true);
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
