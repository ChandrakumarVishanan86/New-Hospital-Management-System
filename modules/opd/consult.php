<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor']);
$appt = AppointmentModel::find((int) ($_GET['appt'] ?? 0));
if (!$appt || !AppointmentModel::canAccess($user, $appt)) { http_response_code(404); exit('Appointment not found.'); }
$cons = ConsultationModel::forAppointment((int) $appt['id']);
if (!$cons) {
    [$cid, $err] = ConsultationModel::start((int) $appt['id'], $user);
    if ($err) { flash('danger', $err); redirect('opd'); }
    $cons = ConsultationModel::find($cid);
}
$final = (bool) $cons['is_final'];
$rx = $final ? ConsultationModel::prescriptionFor((int) $cons['id']) : null;
$labs = LabModel::forPatient((int) $cons['patient_id']);
$labs = array_values(array_filter($labs, static fn($l) => (int) $l['consultation_id'] === (int) $cons['id']));
$history = array_values(array_filter(ConsultationModel::history((int) $cons['patient_id'], 6), static fn($h) => (int) $h['id'] !== (int) $cons['id']));
$meds = MedicineModel::options();
$tests = LabTestModel::options();
$pageTitle = 'Consultation'; $activeNav = 'opd'; $libs = ['flatpickr']; $pageScripts = ['assets/js/consult.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
$v = static fn(string $k) => e((string) ($cons[$k] ?? ''));
$dis = $final ? 'disabled' : '';
?>
<div class="card-panel mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="token fs-5" style="width:52px;height:52px"><?= (int) $appt['token_no'] ?></span>
        <div><h4 class="fw-bold mb-0"><?= e($cons['patient_name']) ?> <?= $final ? status_badge('Completed') : status_badge('In Consultation') ?></h4>
            <div class="text-muted"><span class="plate"><?= e($cons['uhid']) ?></span> · <?= e($cons['gender']) ?>, <?= (int) $cons['age'] ?> yrs<?= $cons['blood_group'] ? ' · ' . e($cons['blood_group']) : '' ?> · <?= e($appt['appt_no']) ?> · <?= fdate($appt['appt_date']) ?> <?= e(substr((string) $appt['appt_time'], 0, 5)) ?></div></div>
        <div class="ms-auto d-flex gap-2 no-print"><a class="btn btn-soft" href="<?= url('patients/' . (int) $cons['patient_id']) ?>" target="_blank"><i class="bi bi-folder2-open me-1"></i> Full record</a><a class="btn btn-light" href="<?= url('opd') ?>"><i class="bi bi-arrow-left me-1"></i> Queue</a></div>
    </div>
    <?php if ($cons['allergies'] || $cons['chronic_conditions'] || $appt['reason']): ?><hr class="my-3">
    <div class="d-flex flex-wrap gap-2">
        <?php if ($cons['allergies']): ?><div class="allergy-alert"><i class="bi bi-exclamation-triangle-fill me-1"></i> Allergies: <?= e($cons['allergies']) ?></div><?php endif; ?>
        <?php if ($cons['chronic_conditions']): ?><span class="badge badge-soft-warning fs-6 fw-semibold"><i class="bi bi-heart-pulse me-1"></i><?= e($cons['chronic_conditions']) ?></span><?php endif; ?>
        <?php if ($appt['reason']): ?><span class="badge badge-soft-info fs-6 fw-semibold"><i class="bi bi-chat-left-text me-1"></i> Reason: <?= e($appt['reason']) ?></span><?php endif; ?>
    </div><?php endif; ?>
</div>

<form id="consultForm" novalidate>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-panel mb-4"><h6 class="fw-bold mb-3"><i class="bi bi-heart-pulse text-danger me-2"></i>Vitals</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><label class="form-label small">BP systolic</label><input class="form-control" type="number" name="bp_sys" min="50" max="280" value="<?= $v('bp_sys') ?>" <?= $dis ?>></div>
                <div class="col-6 col-md-3"><label class="form-label small">BP diastolic</label><input class="form-control" type="number" name="bp_dia" min="30" max="180" value="<?= $v('bp_dia') ?>" <?= $dis ?>></div>
                <div class="col-4 col-md-2"><label class="form-label small">Pulse</label><input class="form-control" type="number" name="pulse" min="20" max="260" value="<?= $v('pulse') ?>" <?= $dis ?>></div>
                <div class="col-4 col-md-2"><label class="form-label small">Temp °C</label><input class="form-control" type="number" step="0.1" name="temp" min="30" max="45" value="<?= $v('temp') ?>" <?= $dis ?>></div>
                <div class="col-4 col-md-2"><label class="form-label small">SpO₂ %</label><input class="form-control" type="number" name="spo2" min="40" max="100" value="<?= $v('spo2') ?>" <?= $dis ?>></div>
                <div class="col-6 col-md-3"><label class="form-label small">Weight kg</label><input class="form-control" type="number" step="0.1" name="weight" min="1" max="400" value="<?= $v('weight') ?>" <?= $dis ?>></div>
                <div class="col-6 col-md-3"><label class="form-label small">Height cm</label><input class="form-control" type="number" step="0.1" name="height" min="30" max="250" value="<?= $v('height') ?>" <?= $dis ?>></div>
                <div class="col-12 col-md-6 d-flex align-items-end"><div class="small text-muted" id="bmi"></div></div>
            </div></div>
        <div class="card-panel mb-4"><h6 class="fw-bold mb-3"><i class="bi bi-journal-medical text-primary me-2"></i>Clinical notes</h6>
            <div class="mb-3"><label class="form-label">Symptoms / history</label><textarea class="form-control" name="symptoms" rows="3" <?= $dis ?>><?= $v('symptoms') ?></textarea></div>
            <div class="mb-3"><label class="form-label">Diagnosis <span class="text-danger">*</span></label><textarea class="form-control" name="diagnosis" rows="2" required <?= $dis ?>><?= $v('diagnosis') ?></textarea></div>
            <div class="row g-3"><div class="col-md-8"><label class="form-label">Advice to patient</label><textarea class="form-control" name="advice" rows="2" <?= $dis ?>><?= $v('advice') ?></textarea></div>
                <div class="col-md-4"><label class="form-label">Follow-up date</label><input class="form-control" name="follow_up_date" id="fu" value="<?= $v('follow_up_date') ?>" <?= $dis ?> placeholder="Optional"></div></div></div>
    </div>

    <div class="col-xl-5">
        <div class="card-panel mb-4">
            <div class="d-flex align-items-center mb-3"><h6 class="fw-bold mb-0"><i class="bi bi-capsule text-success me-2"></i>Prescription</h6>
                <?php if (!$final): ?><button type="button" class="btn btn-sm btn-soft ms-auto" id="addRx"><i class="bi bi-plus-lg"></i> Add medicine</button>
                <?php elseif ($rx): ?><a class="btn btn-sm btn-soft ms-auto" target="_blank" href="<?= url('prescriptions/' . (int) $rx['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a><?php endif; ?></div>
            <?php if ($final): ?>
                <?php foreach (($rx['items'] ?? []) as $i): ?><div class="rx-row mb-2"><strong><?= e($i['name'] . ' ' . $i['strength']) ?></strong> <span class="badge badge-soft-secondary"><?= e($i['form']) ?></span>
                    <div class="small text-muted"><?= e($i['dosage']) ?> · <?= e($i['frequency']) ?> · <?= (int) $i['duration_days'] ?> days · Qty <?= (int) $i['qty'] ?><?= $i['instructions'] ? ' · ' . e($i['instructions']) : '' ?></div></div><?php endforeach; ?>
                <?php if (!$rx): ?><div class="text-muted">No medicines prescribed.</div><?php else: ?><div class="mt-2"><?= status_badge($rx['status']) ?> <small class="text-muted"><?= e($rx['rx_no']) ?></small></div><?php endif; ?>
            <?php else: ?><div id="rxList"></div><div class="text-muted small" id="rxEmpty">No medicines added yet.</div><?php endif; ?>
        </div>
        <div class="card-panel mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-eyedropper text-purple me-2"></i>Laboratory tests</h6>
            <?php if ($final): ?>
                <?php foreach ($labs as $l): ?><div class="d-flex align-items-center gap-2 mb-1"><a class="fw-semibold" href="<?= url('lab/' . (int) $l['id']) ?>"><?= e($l['order_no']) ?></a><?= status_badge($l['status']) ?><small class="text-muted"><?= e(mb_strimwidth((string) $l['tests'], 0, 50, '…')) ?></small></div><?php endforeach; ?>
                <?php if (!$labs): ?><div class="text-muted">No tests ordered.</div><?php endif; ?>
            <?php else: ?>
                <input class="form-control form-control-sm mb-2" id="testFilter" placeholder="Filter tests…">
                <div style="max-height:220px;overflow:auto" id="testList">
                <?php foreach ($tests as $t): ?><label class="d-flex align-items-center gap-2 py-1 test-row" data-n="<?= e(strtolower($t['name'] . ' ' . $t['category'])) ?>"><input type="checkbox" class="form-check-input mt-0" name="tests[]" value="<?= (int) $t['id'] ?>"><span class="flex-fill small"><?= e($t['name']) ?> <span class="text-muted">· <?= e($t['category']) ?></span></span><span class="small fw-semibold"><?= money($t['price']) ?></span></label><?php endforeach; ?></div>
            <?php endif; ?>
        </div>
        <?php if ($history): ?><div class="card-panel"><h6 class="fw-bold mb-3">Previous visits</h6>
            <ul class="list-unstyled mini-list mb-0"><?php foreach ($history as $h): ?><li><div><div class="fw-semibold"><?= e($h['diagnosis']) ?></div><div class="small text-muted"><?= fdate($h['visit_at']) ?> · <?= e($h['doctor_name']) ?></div></div></li><?php endforeach; ?></ul></div><?php endif; ?>
    </div>
</div>
<?php if (!$final): ?>
<div class="card-panel mt-4 d-flex flex-wrap gap-2 align-items-center sticky-bottom no-print" style="bottom:12px">
    <div class="small text-muted"><i class="bi bi-info-circle me-1"></i>Completing adds the consultation fee to the patient's bill, sends the prescription to the pharmacy and the tests to the lab.</div>
    <button type="button" class="btn btn-soft ms-auto" id="btnDraft"><i class="bi bi-save me-1"></i> Save draft</button>
    <button type="submit" class="btn btn-gradient"><i class="bi bi-check2-circle me-1"></i> Complete consultation</button>
</div>
<?php endif; ?>
</form>
<?php ob_start(); ?>
<script>window.CONSULT = { id: <?= (int) $cons['id'] ?>, final: <?= $final ? 'true' : 'false' ?>, allergies: <?= json_encode((string) $cons['allergies']) ?>, meds: <?= json_encode($meds) ?>, queueUrl: <?= json_encode(url('opd')) ?> };</script>
<?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
