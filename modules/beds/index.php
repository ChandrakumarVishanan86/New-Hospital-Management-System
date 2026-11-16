<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist']);
$isAdmin = $user['role'] === 'admin';
$canAdmit = in_array($user['role'], ['admin', 'doctor', 'nurse', 'receptionist'], true);
$board = AdmissionModel::board();
$free = AdmissionModel::freeBeds();
$doctors = DoctorModel::options();
$tot = $occ = $maint = 0;
foreach ($board as $w) { foreach ($w['beds'] as $b) { $tot++; $occ += $b['status'] === 'Occupied'; $maint += $b['status'] === 'Maintenance'; } }
$pageTitle = 'Wards & Beds'; $activeNav = 'beds'; $libs = $isAdmin ? ['datatables'] : []; $pageScripts = $isAdmin ? ['assets/js/crud.js', 'assets/js/admit.js'] : ['assets/js/admit.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-teal hover-lift"><div class="stat-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div><div><div class="stat-num" data-count="<?= $tot ?>">0</div><div class="stat-label">Total beds</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-red hover-lift"><div class="stat-icon"><i class="bi bi-person-fill-lock"></i></div><div><div class="stat-num" data-count="<?= $occ ?>">0</div><div class="stat-label">Occupied</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-green hover-lift"><div class="stat-icon"><i class="bi bi-check2-square"></i></div><div><div class="stat-num" data-count="<?= count($free) ?>">0</div><div class="stat-label">Available</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-blue hover-lift"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-num" data-count="<?= $tot - $maint ? round(100 * $occ / ($tot - $maint)) : 0 ?>">0</div><div class="stat-label">Occupancy %</div></div></div></div>
</div>

<ul class="nav nav-pills mb-3 gap-1" id="bedTabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabBoard">Bed board</button></li>
    <?php if ($isAdmin): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabManage">Manage wards &amp; beds</button></li><?php endif; ?>
    <?php if ($canAdmit): ?><li class="ms-auto"><button class="btn btn-gradient" id="btnAdmit"><i class="bi bi-hospital me-1"></i> Admit Patient</button></li><?php endif; ?>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tabBoard">
    <?php foreach ($board as $w): $o = count(array_filter($w['beds'], static fn($b) => $b['status'] === 'Occupied')); ?>
    <div class="card-panel mb-3">
        <div class="d-flex align-items-center mb-3"><div><h6 class="fw-bold mb-0"><?= e($w['name']) ?> <span class="badge badge-soft-primary ms-1"><?= e($w['ward_type']) ?></span></h6><small class="text-muted"><?= money($w['daily_rate']) ?> / day</small></div>
            <span class="ms-auto small text-muted"><?= $o ?> / <?= count($w['beds']) ?> occupied</span></div>
        <div class="bed-grid">
        <?php foreach ($w['beds'] as $b): $tag = $b['status'] === 'Occupied' ? 'a' : 'button'; ?>
            <<?= $tag ?> class="bed bed-<?= e($b['status']) ?>" <?= $tag === 'a' ? 'href="' . url('admissions/' . (int) $b['admission_id']) . '"' : 'type="button" data-bed="' . (int) $b['id'] . '" ' . ($b['status'] !== 'Available' || !$canAdmit ? 'disabled' : '') ?>>
                <span class="bed-dot"></span><div class="bed-no"><i class="bi bi-hospital-fill me-1 opacity-50"></i><?= e($b['bed_no']) ?></div>
                <?php if ($b['status'] === 'Occupied'): ?><div class="bed-who"><strong class="text-dark"><?= e($b['patient_name']) ?></strong><br><?= e($b['uhid']) ?> · <?= e($b['doctor_name']) ?></div>
                <?php else: ?><div class="bed-who"><?= $b['status'] === 'Available' ? 'Available' . ($canAdmit ? ' — click to admit' : '') : 'Under maintenance' ?></div><?php endif; ?>
            </<?= $tag ?>>
        <?php endforeach; ?>
        <?php if (!$w['beds']): ?><span class="text-muted">No beds in this ward.</span><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<div class="tab-pane fade" id="tabManage">
    <div class="card-panel mb-3">
        <div class="d-flex align-items-center mb-3"><h6 class="fw-bold mb-0">Wards</h6><button class="btn btn-gradient btn-sm ms-auto" id="btnAddWard"><i class="bi bi-plus-lg me-1"></i> Add Ward</button></div>
        <div class="table-responsive"><table id="tblWards" class="table table-hover align-middle w-100"><thead><tr><th>Ward</th><th>Type</th><th class="text-end">Daily rate</th><th class="text-center">Beds</th><th class="text-center">Occupied</th><th>Status</th><th class="text-end">Actions</th></tr></thead></table></div>
    </div>
    <div class="card-panel">
        <h6 class="fw-bold mb-3">Beds</h6>
        <?php foreach ($board as $w): ?>
        <div class="mb-3"><div class="fw-semibold mb-2"><?= e($w['name']) ?></div><div class="d-flex flex-wrap gap-2">
            <?php foreach ($w['beds'] as $b): ?>
            <div class="btn-group btn-group-sm"><span class="btn btn-outline-secondary disabled"><?= e($b['bed_no']) ?> · <?= e($b['status']) ?></span>
                <?php if ($b['status'] !== 'Occupied'): ?>
                <button class="btn btn-outline-warning" data-bedact="bed_status" data-id="<?= (int) $b['id'] ?>" data-status="<?= $b['status'] === 'Available' ? 'Maintenance' : 'Available' ?>" title="Toggle maintenance"><i class="bi bi-tools"></i></button>
                <button class="btn btn-outline-danger" data-bedact="delete_bed" data-id="<?= (int) $b['id'] ?>" title="Delete bed"><i class="bi bi-trash3"></i></button><?php endif; ?></div>
            <?php endforeach; ?>
            <button class="btn btn-sm btn-soft" data-addbed="<?= (int) $w['id'] ?>" data-ward="<?= e($w['name']) ?>"><i class="bi bi-plus"></i> Bed</button>
        </div></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../admissions/_admit_modal.php'; ?>
<?php if ($isAdmin): ?>
<div class="modal fade" id="bedModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><form class="modal-content" id="bedForm" novalidate>
    <div class="modal-header border-0 pb-0"><h6 class="modal-title fw-bold" id="bedTitle">Add bed</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Bed number</label><input class="form-control text-uppercase" id="bedNo" maxlength="10" placeholder="A-07" required><div class="invalid-feedback">Letters, digits and dashes (max 10).</div></div>
    <div class="modal-footer border-0"><button class="btn btn-gradient w-100">Add bed</button></div></form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>window.ADMIT = { free: <?= json_encode($free) ?>, doctors: <?= json_encode($doctors) ?>, role: <?= json_encode($user['role']) ?>, myDoctor: <?= json_encode($user['doctor_id'] ?? null) ?> };</script>
<?php $pageConfig = ob_get_clean(); ob_start(); ?>
<script>
<?php if ($isAdmin): ?>
var wards = new Crud({ table: '#tblWards', api: APP.base + '/beds/wards-api', label: 'Ward', canWrite: true, pageLength: 10,
    columns: [{ data: 'name', render: function (d) { return '<span class="fw-semibold">' + SMS.esc(d) + '</span>'; } }, { data: 'ward_type' }, { data: 'daily_rate', className: 'text-end', render: function (d, t) { return t === 'display' ? SMS.money(d) : d; } },
        { data: 'beds', className: 'text-center' }, { data: 'occupied', className: 'text-center' }, { data: 'is_active', render: function (d) { return parseInt(d, 10) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>'; } }],
    addButton: 'btnAddWard', onSaved: function () { setTimeout(function () { location.reload(); }, 600); },
    fields: [{ name: 'name', label: 'Ward name', required: true, max: 60, col: 6, unique: true }, { name: 'ward_type', label: 'Type', type: 'select', required: true, col: 3, options: ['General', 'Private', 'ICU', 'Maternity', 'Pediatric', 'Emergency'].map(function (x) { return [x, x]; }) },
        { name: 'daily_rate', label: 'Daily rate', type: 'number', required: true, min: 0, step: '0.01', col: 3 }, { name: 'is_active', label: 'Active', type: 'checkbox', col: 12, default: true }] });
var bedModal = new bootstrap.Modal(document.getElementById('bedModal')), bedWard = 0;
document.querySelectorAll('[data-addbed]').forEach(function (b) { b.addEventListener('click', function () { bedWard = b.dataset.addbed; document.getElementById('bedTitle').textContent = 'Add bed to ' + b.dataset.ward; document.getElementById('bedNo').value = ''; document.getElementById('bedNo').classList.remove('is-invalid'); bedModal.show(); }); });
document.getElementById('bedForm').addEventListener('submit', function (e) { e.preventDefault(); var inp = document.getElementById('bedNo');
    if (!/^[A-Za-z0-9\-]{1,10}$/.test(inp.value.trim())) { inp.classList.add('is-invalid'); return; }
    SMS.api(APP.base + '/beds/wards-api', { method: 'POST', body: { action: 'add_bed', ward_id: bedWard, bed_no: inp.value.trim() } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.reload(); }, 500); }); });
document.querySelectorAll('[data-bedact]').forEach(function (b) { b.addEventListener('click', function () {
    var body = { action: b.dataset.bedact, bed_id: b.dataset.id, status: b.dataset.status };
    var go = function () { SMS.api(APP.base + '/beds/wards-api', { method: 'POST', body: body }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.reload(); }, 500); }); };
    if (b.dataset.bedact === 'delete_bed') SMS.confirmBox('Delete this bed?', 'Delete').then(function (ok) { if (ok) go(); }); else go(); }); });
<?php endif; ?>
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
