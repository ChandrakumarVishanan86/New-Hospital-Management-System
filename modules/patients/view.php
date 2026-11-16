<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(Auth::STAFF);
$role = $user['role'];
$p = PatientModel::find((int) ($_GET['id'] ?? 0));
if (!$p) { http_response_code(404); exit('Patient not found.'); }
$pid = (int) $p['id'];
$clin = in_array($role, ['admin', 'doctor', 'nurse'], true);
$can = [
    'visits' => $clin, 'appts' => in_array($role, ['admin', 'doctor', 'nurse', 'receptionist'], true),
    'labs' => $clin || $role === 'lab', 'rx' => $clin || $role === 'pharmacist',
    'adm' => $clin || $role === 'receptionist', 'bills' => in_array($role, ['admin', 'receptionist', 'accountant'], true),
];
$q = static function (string $sql) use ($pid): array { $s = db()->prepare($sql); $s->execute([':p' => $pid]); return $s->fetchAll(); };
$visits = $can['visits'] ? ConsultationModel::history($pid, 20) : [];
$appts = $can['appts'] ? $q("SELECT a.*, u.full_name doctor_name, dp.name department FROM appointments a JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id JOIN departments dp ON dp.id = d.department_id WHERE a.patient_id = :p ORDER BY a.appt_date DESC, a.appt_time DESC LIMIT 30") : [];
$labs = $can['labs'] ? LabModel::forPatient($pid) : [];
$rxs = $can['rx'] ? $q("SELECT r.id, r.rx_no, r.status, r.created_at, u.full_name doctor_name, (SELECT GROUP_CONCAT(m.name SEPARATOR ', ') FROM prescription_items i JOIN medicines m ON m.id = i.medicine_id WHERE i.prescription_id = r.id) medicines FROM prescriptions r JOIN doctors d ON d.id = r.doctor_id JOIN users u ON u.id = d.user_id WHERE r.patient_id = :p ORDER BY r.id DESC LIMIT 30") : [];
$adms = $can['adm'] ? AdmissionModel::forPatient($pid) : [];
$bills = $can['bills'] ? $q("SELECT id, invoice_no, issued_at, total, paid, status FROM invoices WHERE patient_id = :p ORDER BY id DESC") : [];
$age = age_from($p['dob']);
$tabs = [];
foreach (['visits' => 'Visits', 'appts' => 'Appointments', 'labs' => 'Lab reports', 'rx' => 'Prescriptions', 'adm' => 'Admissions', 'bills' => 'Billing'] as $k => $l) { if ($can[$k]) { $tabs[$k] = $l; } }
$pageTitle = $p['full_name']; $activeNav = 'patients'; $libs = []; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
$empty = static fn(string $m) => '<div class="text-center text-muted py-4">' . $m . '</div>';
?>
<div class="card-panel mb-4">
    <div class="d-flex flex-wrap align-items-start gap-3">
        <span class="avatar avatar-lg bg-primary"><?= e(mb_strtoupper(mb_substr($p['full_name'], 0, 1))) ?></span>
        <div><h4 class="fw-bold mb-1"><?= e($p['full_name']) ?></h4>
            <div class="text-muted"><span class="plate"><?= e($p['uhid']) ?></span> · <?= e($p['gender']) ?>, <?= (int) $age ?> yrs<?= $p['blood_group'] ? ' · ' . e($p['blood_group']) : '' ?> · <i class="bi bi-telephone"></i> <?= e($p['phone']) ?></div>
            <?php if ($p['allergies']): ?><div class="allergy-alert mt-2 d-inline-block"><i class="bi bi-exclamation-triangle-fill me-1"></i> Allergies: <?= e($p['allergies']) ?></div><?php endif; ?></div>
        <div class="ms-auto d-flex gap-2 no-print"><?php if (in_array($role, ['admin', 'receptionist'], true)): ?><a class="btn btn-gradient" href="<?= url('appointments?patient=' . $pid) ?>"><i class="bi bi-calendar-plus me-1"></i> Book appointment</a><?php endif; ?></div>
    </div>
    <hr><div class="row g-3 small">
        <div class="col-6 col-md-3"><div class="text-muted">Date of birth</div><strong><?= fdate($p['dob']) ?></strong></div>
        <div class="col-6 col-md-3"><div class="text-muted">Insurance</div><strong><?= $p['insurance_provider'] ? e($p['insurance_provider']) . ' · ' . e((string) $p['insurance_no']) : 'Self-pay' ?></strong></div>
        <div class="col-6 col-md-3"><div class="text-muted">Guardian</div><strong><?= e((string) ($p['guardian_name'] ?? '–')) ?> <?= e((string) ($p['guardian_phone'] ?? '')) ?></strong></div>
        <div class="col-6 col-md-3"><div class="text-muted">Address</div><strong><?= e((string) ($p['address'] ?? '–')) ?></strong></div>
        <?php if (!empty($p['chronic_conditions'])): ?><div class="col-12"><div class="text-muted">Chronic conditions</div><strong><?= e($p['chronic_conditions']) ?></strong></div><?php endif; ?>
    </div>
</div>
<div class="card-panel">
    <ul class="nav nav-pills mb-3"><?php $i = 0; foreach ($tabs as $k => $l): ?><li class="nav-item"><button class="nav-link <?= $i++ === 0 ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#t-<?= $k ?>"><?= $l ?></button></li><?php endforeach; ?></ul>
    <div class="tab-content">
    <?php $i = 0; foreach ($tabs as $k => $l): ?><div class="tab-pane fade <?= $i++ === 0 ? 'show active' : '' ?>" id="t-<?= $k ?>">
    <?php if ($k === 'visits'): ?><div class="timeline"><?php foreach ($visits as $v): ?><div class="tl-item"><span class="tl-dot"></span><div class="fw-semibold"><?= e((string) $v['diagnosis']) ?></div><div class="small text-muted"><?= fdate($v['visit_at']) ?> · <?= e($v['doctor_name']) ?> · <?= e($v['department']) ?><?= $v['symptoms'] ? '<br>' . e($v['symptoms']) : '' ?><?= $v['rx_id'] ? ' · <a target="_blank" href="' . url('prescriptions/' . (int) $v['rx_id'] . '/pdf') . '">Prescription</a>' : '' ?></div></div><?php endforeach; ?></div><?= $visits ? '' : $empty('No consultations yet.') ?>
    <?php elseif ($k === 'appts'): ?><table class="table align-middle"><thead><tr><th>Date</th><th>Doctor</th><th>Reason</th><th>Token</th><th>Status</th></tr></thead><tbody><?php foreach ($appts as $a): ?><tr><td><?= fdate($a['appt_date']) ?> <?= e(substr((string) $a['appt_time'], 0, 5)) ?></td><td><?= e($a['doctor_name']) ?><div class="small text-muted"><?= e($a['department']) ?></div></td><td class="small"><?= e((string) $a['reason']) ?></td><td><?= (int) $a['token_no'] ?></td><td><?= status_badge($a['status']) ?></td></tr><?php endforeach; ?></tbody></table><?= $appts ? '' : $empty('No appointments.') ?>
    <?php elseif ($k === 'labs'): ?><table class="table align-middle"><thead><tr><th>Order</th><th>Date</th><th>Tests</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($labs as $o): ?><tr><td><span class="plate"><?= e($o['order_no']) ?></span></td><td><?= fdate($o['ordered_at']) ?></td><td class="small"><?= e(mb_strimwidth((string) $o['tests'], 0, 60, '…')) ?></td><td><?= status_badge($o['status']) ?><?= (int) $o['abnormal'] > 0 ? ' <span class="badge badge-soft-danger">' . (int) $o['abnormal'] . ' abnormal</span>' : '' ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('lab/' . (int) $o['id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table><?= $labs ? '' : $empty('No lab orders.') ?>
    <?php elseif ($k === 'rx'): ?><table class="table align-middle"><thead><tr><th>Rx</th><th>Date</th><th>Doctor</th><th>Medicines</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($rxs as $r): ?><tr><td><span class="plate"><?= e($r['rx_no']) ?></span></td><td><?= fdate($r['created_at']) ?></td><td><?= e($r['doctor_name']) ?></td><td class="small"><?= e(mb_strimwidth((string) $r['medicines'], 0, 60, '…')) ?></td><td><?= status_badge($r['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" target="_blank" href="<?= url('prescriptions/' . (int) $r['id'] . '/pdf') ?>">PDF</a></td></tr><?php endforeach; ?></tbody></table><?= $rxs ? '' : $empty('No prescriptions.') ?>
    <?php elseif ($k === 'adm'): ?><table class="table align-middle"><thead><tr><th>Admission</th><th>Admitted</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($adms as $a): ?><tr><td><span class="plate"><?= e($a['adm_no']) ?></span></td><td><?= fdate($a['admitted_at']) ?></td><td class="small"><?= e((string) $a['reason']) ?></td><td><?= status_badge($a['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('admissions/' . (int) $a['id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table><?= $adms ? '' : $empty('No admissions.') ?>
    <?php else: ?><table class="table align-middle"><thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($bills as $b): ?><tr><td><span class="plate"><?= e($b['invoice_no']) ?></span></td><td><?= fdate($b['issued_at']) ?></td><td class="text-end"><?= money($b['total']) ?></td><td class="text-end"><?= money($b['paid']) ?></td><td><?= status_badge($b['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('invoices/' . (int) $b['id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table><?= $bills ? '' : $empty('No invoices.') ?>
    <?php endif; ?></div><?php endforeach; ?>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
