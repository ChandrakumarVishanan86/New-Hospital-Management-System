<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(Auth::STAFF);
$canWrite = in_array($user['role'], ['admin', 'receptionist', 'nurse'], true);
$pageTitle = 'Patients'; $activeNav = 'patients'; $libs = ['datatables']; $pageScripts = ['assets/js/crud.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div><h5 class="mb-0 fw-bold">Patient Register</h5><small class="text-muted">Search by name, UHID, phone or NIC.</small></div>
        <?php if ($canWrite): ?><button class="btn btn-gradient ms-auto" id="btnAdd"><i class="bi bi-person-plus-fill me-1"></i> Register Patient</button><?php endif; ?>
    </div>
    <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100">
        <thead><tr><th>Patient</th><th>UHID</th><th>Phone</th><th>Blood</th><th>Insurance</th><th class="text-center">Visits</th><th>Last visit</th><th class="text-end">Actions</th></tr></thead>
    </table></div>
</div>
<?php ob_start(); ?>
<script>
var crud = new Crud({
    table: '#tbl', api: APP.base + '/patients/api', label: 'Patient', canWrite: <?= $canWrite ? 'true' : 'false' ?>, canDelete: <?= $user['role'] === 'admin' ? 'true' : 'false' ?>, order: [[0, 'asc']], modalSize: 'modal-xl',
    rowName: function (r) { return r.full_name; },
    columns: [
        { data: 'full_name', render: function (d, t, r) {
            var ini = SMS.esc(d.split(' ').map(function (p) { return p[0]; }).slice(0, 2).join(''));
            var warn = r.allergies ? ' <span class="badge badge-soft-danger" title="Allergies: ' + SMS.esc(r.allergies) + '"><i class="bi bi-exclamation-triangle-fill"></i> Allergy</span>' : '';
            var adm = parseInt(r.admitted, 10) ? ' <span class="badge badge-soft-primary">Admitted</span>' : '';
            return '<a class="text-decoration-none text-reset" href="' + APP.base + '/patients/' + r.id + '"><div class="d-flex align-items-center gap-2"><span class="avatar avatar-sm">' + ini + '</span><div class="lh-sm"><div class="fw-semibold">' + SMS.esc(d) + warn + adm + '</div><small class="text-muted">' + SMS.esc(r.gender) + ', ' + r.age + ' yrs</small></div></div></a>'; } },
        { data: 'uhid', render: function (d) { return '<span class="plate">' + SMS.esc(d) + '</span>'; } },
        { data: 'phone', render: function (d) { return '<a class="text-decoration-none" href="https://wa.me/' + String(d).replace(/\D/g, '').replace(/^0/, '94') + '" target="_blank" rel="noopener"><i class="bi bi-whatsapp text-success me-1"></i>' + SMS.esc(d) + '</a>'; } },
        { data: 'blood_group', render: function (d) { return d ? '<span class="badge badge-soft-danger">' + SMS.esc(d) + '</span>' : '–'; } },
        { data: 'insurance_provider', render: function (d) { return d ? SMS.esc(d) : '<span class="text-muted">Self-pay</span>'; } },
        { data: 'visits', className: 'text-center' },
        { data: 'last_visit', render: function (d) { return SMS.fdate(d); } }
    ],
    actions: function (r) { return '<a class="btn btn-icon btn-soft" title="Open record" href="' + APP.base + '/patients/' + r.id + '"><i class="bi bi-folder2-open"></i></a> ' +
        '<a class="btn btn-icon btn-soft" title="Book appointment" href="' + APP.base + '/appointments?patient=' + r.id + '"><i class="bi bi-calendar-plus"></i></a>'; },
    fields: [
        { name: 'full_name', label: 'Full name', required: true, max: 120, col: 6, pattern: "^[A-Za-z\\u00C0-\\u024F][A-Za-z\\u00C0-\\u024F .'\\-]*$", patternMsg: 'Name may contain letters only.' },
        { name: 'gender', label: 'Gender', type: 'select', required: true, col: 3, options: [['Male', 'Male'], ['Female', 'Female'], ['Other', 'Other']] },
        { name: 'dob', label: 'Date of birth', type: 'date', required: true, col: 3, maxv: '<?= date('Y-m-d') ?>' },
        { name: 'phone', label: 'Phone', type: 'tel', required: true, max: 20, col: 4, unique: true, pattern: '^[0-9+\\-\\s]{9,20}$', patternMsg: 'Enter a valid phone number.', placeholder: '0771234567' },
        { name: 'email', label: 'Email', type: 'email', max: 120, col: 4 },
        { name: 'nic', label: 'NIC', max: 20, col: 4, upper: true, unique: true, pattern: '^([0-9]{9}[VvXx]|[0-9]{12})$', patternMsg: 'NIC: 9 digits + V/X, or 12 digits.' },
        { name: 'blood_group', label: 'Blood group', type: 'select', col: 3, options: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'].map(function (b) { return [b, b]; }) },
        { name: 'address', label: 'Address', max: 255, col: 9 },
        { name: 'guardian_name', label: 'Guardian / emergency contact', max: 120, col: 6 },
        { name: 'guardian_phone', label: 'Guardian phone', type: 'tel', max: 20, col: 6, pattern: '^[0-9+\\-\\s]{9,20}$', patternMsg: 'Enter a valid phone number.' },
        { name: 'allergies', label: 'Known allergies', max: 255, col: 6, placeholder: 'e.g. Penicillin, Aspirin' },
        { name: 'chronic_conditions', label: 'Chronic conditions', max: 255, col: 6, placeholder: 'e.g. Hypertension, Diabetes' },
        { name: 'insurance_provider', label: 'Insurance provider', max: 80, col: 6 },
        { name: 'insurance_no', label: 'Insurance number', max: 40, col: 6 },
        { name: 'create_login', label: 'Create patient portal login', type: 'checkbox', col: 12, createOnly: true, default: true, help: 'Username: p + phone digits · Password: <?= e(DEFAULT_PATIENT_PASSWORD) ?>' }
    ]
});
<?php if ($canWrite): ?>if (/[?&]new=1/.test(location.search)) { setTimeout(function () { crud.open(null); }, 300); }<?php endif; ?>
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
