<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::nurse();
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = []; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Hello, ' . explode(' ', $user['full_name'])[0], date('l, d F Y') . ' · ward overview');
?>
<div class="row g-4 mb-4"><?php stat_tile('Inpatients', count($d['inpatients']), 'bi-hospital', 'stat-indigo'); stat_tile('Beds available', $d['free_beds'], 'bi-door-open', 'stat-green'); stat_tile('OPD waiting', count($d['queue']), 'bi-people', 'stat-amber'); stat_tile('Samples to collect', count($d['lab_pending']), 'bi-eyedropper', 'stat-cyan'); ?></div>
<div class="row g-4"><div class="col-xl-7"><div class="card-panel"><div class="d-flex mb-2"><h6 class="fw-bold mb-0">My ward patients</h6><a class="ms-auto small" href="<?= url('beds') ?>">Bed board →</a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Patient</th><th>Ward / Bed</th><th>Doctor</th><th></th></tr></thead><tbody><?php foreach ($d['inpatients'] as $a): ?><tr><td><?= e($a['patient_name']) ?><div class="small text-muted"><?= e($a['reason']) ?></div></td><td><?= e($a['ward']) ?> · <?= e($a['bed_no']) ?></td><td class="small"><?= e($a['doctor_name']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('admissions/' . (int) $a['id']) ?>">Notes</a></td></tr><?php endforeach; ?><?php if (!$d['inpatients']): ?><tr><td colspan="4" class="text-center text-muted py-4">No inpatients.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="col-xl-5"><div class="card-panel mb-4"><h6 class="fw-bold">OPD queue</h6><?php queue_list(array_slice($d['queue'], 0, 5)); ?></div>
    <div class="card-panel"><h6 class="fw-bold">Samples waiting</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['lab_pending'] as $l): ?><li><div><a href="<?= url('lab/' . (int) $l['id']) ?>"><?= e($l['order_no']) ?></a> · <?= e($l['patient_name']) ?></div><?php if ($l['priority'] === 'Urgent'): ?><span class="ms-auto badge badge-soft-danger">Urgent</span><?php endif; ?></li><?php endforeach; ?><?php if (!$d['lab_pending']): ?><li class="text-muted">None.</li><?php endif; ?></ul></div></div></div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
