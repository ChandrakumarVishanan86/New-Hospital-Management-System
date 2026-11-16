<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);
$a = AdmissionModel::find((int) ($_GET['id'] ?? 0));
if (!$a || !AdmissionModel::canAccess($user, $a)) { http_response_code(404); exit('Admission not found.'); }
$isPatient = $user['role'] === 'patient';
$admitted = $a['status'] === 'Admitted';
$notes = AdmissionModel::notes((int) $a['id']);
$labs = AdmissionModel::labOrders((int) $a['id']);
$charges = $isPatient ? [] : AdmissionModel::charges((int) $a['id']);
$canNote = $admitted && in_array($user['role'], ['admin', 'doctor', 'nurse'], true);
$canDischarge = $admitted && ($user['role'] === 'admin' || ($user['role'] === 'doctor' && (int) $user['doctor_id'] === (int) $a['doctor_id']));
$canOrder = $admitted && in_array($user['role'], ['admin', 'doctor'], true);
$freeBeds = $canNote ? AdmissionModel::freeBeds() : [];
$tests = $canOrder ? LabTestModel::options() : [];
$accrued = $a['days'] * (float) $a['daily_rate'];
$pageTitle = $a['adm_no']; $activeNav = 'admissions'; $libs = []; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel mb-4">
    <div class="d-flex flex-wrap align-items-start gap-3">
        <span class="avatar avatar-lg bg-primary"><?= e(mb_strtoupper(mb_substr($a['patient_name'], 0, 1))) ?></span>
        <div><h4 class="fw-bold mb-1"><?= e($a['patient_name']) ?> <?= status_badge($a['status']) ?></h4>
            <div class="text-muted"><span class="plate"><?= e($a['uhid']) ?></span> · <?= e($a['gender']) ?>, <?= (int) $a['age'] ?> yrs<?= $a['blood_group'] ? ' · ' . e($a['blood_group']) : '' ?> · <?= e($a['adm_no']) ?></div>
            <?php if ($a['allergies']): ?><div class="allergy-alert mt-2 d-inline-block"><i class="bi bi-exclamation-triangle-fill me-1"></i> Allergies: <?= e($a['allergies']) ?></div><?php endif; ?></div>
        <div class="ms-auto d-flex flex-wrap gap-2 no-print">
            <?php if (!$isPatient): ?><a class="btn btn-soft" href="<?= url('patients/' . (int) $a['patient_id']) ?>"><i class="bi bi-folder2-open me-1"></i> Patient record</a><?php endif; ?>
            <?php if (!$admitted): ?><a class="btn btn-soft" target="_blank" href="<?= url('admissions/' . (int) $a['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf me-1"></i> Discharge summary</a><?php endif; ?>
            <?php if ($canNote): ?><button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="bi bi-arrow-left-right me-1"></i> Transfer</button><?php endif; ?>
            <?php if ($canOrder): ?><button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#labModal"><i class="bi bi-eyedropper me-1"></i> Order tests</button><?php endif; ?>
            <?php if ($canDischarge): ?><button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#dischargeModal"><i class="bi bi-box-arrow-right me-1"></i> Discharge</button><?php endif; ?>
        </div>
    </div>
    <hr>
    <div class="row g-3 small">
        <div class="col-6 col-md-3"><div class="text-muted">Ward / Bed</div><strong><?= e($a['ward']) ?> · <?= e($a['bed_no']) ?></strong></div>
        <div class="col-6 col-md-3"><div class="text-muted">Admitted</div><strong><?= fdatetime($a['admitted_at']) ?></strong> <span class="text-muted">(<?= (int) $a['days'] ?> day<?= $a['days'] > 1 ? 's' : '' ?>)</span></div>
        <div class="col-6 col-md-3"><div class="text-muted">Admitting doctor</div><strong><?= e($a['doctor_name']) ?></strong></div>
        <div class="col-6 col-md-3"><div class="text-muted"><?= $admitted ? 'Bed charges so far' : 'Discharged' ?></div><strong><?= $admitted ? money($accrued) : fdatetime($a['discharged_at']) ?></strong></div>
        <div class="col-12 col-md-6"><div class="text-muted">Reason</div><strong><?= e($a['reason']) ?></strong></div>
        <div class="col-12 col-md-6"><div class="text-muted">Diagnosis</div><strong><?= e($a['diagnosis'] ?: '–') ?></strong></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-panel mb-4">
            <div class="d-flex align-items-center mb-3"><h6 class="fw-bold mb-0">Nursing &amp; progress notes</h6></div>
            <?php if ($canNote): ?>
            <form id="noteForm" class="row g-2 mb-3 no-print" novalidate>
                <div class="col-12"><textarea class="form-control" name="note" rows="2" maxlength="500" placeholder="Write a note…" required></textarea></div>
                <div class="col-4 col-md-3"><input class="form-control" name="bp" placeholder="BP 120/80" maxlength="7"></div>
                <div class="col-4 col-md-3"><input class="form-control" name="pulse" type="number" min="20" max="260" placeholder="Pulse"></div>
                <div class="col-4 col-md-3"><input class="form-control" name="temp" type="number" step="0.1" min="30" max="45" placeholder="Temp °C"></div>
                <div class="col-12 col-md-3 d-grid"><button class="btn btn-gradient"><i class="bi bi-plus-lg me-1"></i> Add note</button></div>
            </form>
            <?php endif; ?>
            <div class="timeline">
            <?php foreach ($notes as $n): ?>
                <div class="tl-item"><div class="tl-dot"></div><div class="tl-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center"><strong><?= e($n['full_name']) ?></strong><span class="badge role-<?= e($n['role']) ?>"><?= e($n['role']) ?></span><small class="text-muted ms-auto"><?= fdatetime($n['created_at']) ?></small></div>
                    <div><?= nl2br(e($n['note'])) ?></div>
                    <?php if ($n['bp'] || $n['pulse'] || $n['temp']): ?><div class="small text-muted mt-1"><?= $n['bp'] ? 'BP ' . e($n['bp']) . ' · ' : '' ?><?= $n['pulse'] ? 'Pulse ' . (int) $n['pulse'] . ' · ' : '' ?><?= $n['temp'] ? 'Temp ' . e((string) $n['temp']) . '°C' : '' ?></div><?php endif; ?>
                </div></div>
            <?php endforeach; ?>
            <?php if (!$notes): ?><div class="text-muted">No notes yet.</div><?php endif; ?>
            </div>
        </div>
        <?php if ($a['discharge_summary']): ?><div class="card-panel"><h6 class="fw-bold mb-2">Discharge summary</h6><p class="mb-0"><?= nl2br(e($a['discharge_summary'])) ?></p></div><?php endif; ?>
    </div>
    <div class="col-xl-5">
        <div class="card-panel mb-4"><h6 class="fw-bold mb-3">Lab orders</h6>
            <ul class="list-unstyled mini-list mb-0">
            <?php foreach ($labs as $l): ?><li><div><a class="fw-semibold" href="<?= url('lab/' . (int) $l['id']) ?>"><?= e($l['order_no']) ?></a><div class="small text-muted"><?= e(mb_strimwidth((string) $l['tests'], 0, 60, '…')) ?></div></div><span class="ms-auto"><?= status_badge($l['status']) ?></span></li><?php endforeach; ?>
            <?php if (!$labs): ?><li class="text-muted">No tests ordered.</li><?php endif; ?></ul></div>
        <?php if (!$isPatient): ?>
        <div class="card-panel"><h6 class="fw-bold mb-3">Charges</h6>
            <ul class="list-unstyled mini-list mb-0">
            <?php foreach ($charges as $c): ?><li><div><?= e($c['description']) ?><div class="small text-muted"><?= e($c['source']) ?><?= $c['invoice_no'] ? ' · billed ' . e($c['invoice_no']) : ' · unbilled' ?></div></div><strong class="ms-auto"><?= money($c['amount']) ?></strong></li><?php endforeach; ?>
            <?php if ($admitted): ?><li class="text-muted small"><i class="bi bi-info-circle me-1"></i>Bed charges are added on transfer/discharge (currently <?= money($accrued) ?> accrued).</li><?php endif; ?>
            <?php if (!$charges && !$admitted): ?><li class="text-muted">No charges.</li><?php endif; ?></ul></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canNote): ?>
<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="transferForm">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Transfer to another bed</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p class="text-muted small">The current bed segment is billed now at the <?= e($a['ward']) ?> rate; the new bed starts a new segment.</p>
        <select class="form-select" name="bed_id" required><option value="">— Select bed —</option><?php foreach ($freeBeds as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['ward'] . ' · ' . $b['bed_no'] . ' (' . money($b['daily_rate']) . '/day)') ?></option><?php endforeach; ?></select></div>
    <div class="modal-footer border-0"><button class="btn btn-gradient w-100">Transfer</button></div></form></div></div>
<?php endif; ?>
<?php if ($canDischarge): ?>
<div class="modal fade" id="dischargeModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" id="dischargeForm">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Discharge <?= e($a['patient_name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Discharge summary <span class="text-danger">*</span></label>
        <textarea class="form-control" name="summary" rows="6" required minlength="10" placeholder="Condition at discharge, treatment given, medication, follow-up advice…"></textarea></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient"><i class="bi bi-box-arrow-right me-1"></i> Confirm discharge</button></div></form></div></div>
<?php endif; ?>
<?php if ($canOrder): ?>
<div class="modal fade" id="labModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="labForm">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Order laboratory tests</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-2">
        <?php foreach ($tests as $t): ?><div class="col-md-6"><label class="d-flex align-items-center gap-2 rx-row"><input type="checkbox" class="form-check-input mt-0" name="tests[]" value="<?= (int) $t['id'] ?>"><span class="flex-fill"><?= e($t['name']) ?><small class="text-muted d-block"><?= e($t['category']) ?></small></span><strong class="small"><?= money($t['price']) ?></strong></label></div><?php endforeach; ?>
        </div>
        <div class="row g-2 mt-2"><div class="col-md-4"><select class="form-select" name="priority"><option>Routine</option><option>Urgent</option></select></div><div class="col-md-8"><input class="form-control" name="notes" maxlength="255" placeholder="Clinical notes (optional)"></div></div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient">Place order</button></div></form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
(function () {
    var ID = <?= (int) $a['id'] ?>;
    function post(body, ok) { body.id = ID; return SMS.api(APP.base + '/admissions/api', { method: 'POST', body: body }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(ok || function () { location.reload(); }, 500); }); }
    function form(id, build) { var f = document.getElementById(id); if (f) f.addEventListener('submit', function (e) { e.preventDefault(); build(f); }); }
    form('noteForm', function (f) { var o = { action: 'note' }; new FormData(f).forEach(function (v, k) { o[k] = v; }); post(o); });
    form('transferForm', function (f) { if (!f.bed_id.value) { SMS.toast('Choose a bed.', 'warning'); return; } post({ action: 'transfer', bed_id: f.bed_id.value }); });
    form('dischargeForm', function (f) { if (f.summary.value.trim().length < 10) { SMS.toast('Write a discharge summary.', 'warning'); return; } post({ action: 'discharge', summary: f.summary.value }); });
    form('labForm', function (f) { var t = Array.prototype.map.call(f.querySelectorAll('[name="tests[]"]:checked'), function (c) { return c.value; }); if (!t.length) { SMS.toast('Select at least one test.', 'warning'); return; } post({ action: 'order_lab', tests: t, priority: f.priority.value, notes: f.notes.value }); });
})();
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
