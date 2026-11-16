<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::doctor((int) $user['doctor_id']);
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = ['chart']; $pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Good day, ' . $user['full_name'], date('l, d F Y'));
$waiting = count(array_filter($d['queue'], fn($a) => $a['status'] !== 'Completed'));
?>
<div class="row g-4 mb-4"><?php stat_tile('Waiting today', $waiting, 'bi-hourglass-split', 'stat-amber'); stat_tile('Seen today', $d['seen_today'], 'bi-check2-circle', 'stat-green'); stat_tile('Seen this month', $d['seen_month'], 'bi-calendar-month', 'stat-cyan'); stat_tile('My inpatients', count($d['inpatients']), 'bi-hospital', 'stat-indigo'); ?></div>
<div class="row g-4">
    <div class="col-xl-6"><div class="card-panel mb-4"><div class="d-flex mb-2"><h6 class="fw-bold mb-0">My queue</h6><a class="ms-auto small" href="<?= url('opd') ?>">Open OPD →</a></div><?php queue_list($d['queue']); ?></div>
        <div class="card-panel"><h6 class="fw-bold">Patients seen — 14 days</h6><div style="height:200px"><canvas id="chartWeekly"></canvas></div></div></div>
    <div class="col-xl-6"><div class="card-panel mb-4"><h6 class="fw-bold">Admitted under me</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['inpatients'] as $a): ?><li><div><a href="<?= url('admissions/' . (int) $a['id']) ?>"><?= e($a['patient_name']) ?></a><div class="small text-muted"><?= e($a['ward']) ?> · <?= e($a['bed_no']) ?> · <?= e($a['reason']) ?></div></div></li><?php endforeach; ?><?php if (!$d['inpatients']): ?><li class="text-muted">None.</li><?php endif; ?></ul></div>
        <div class="card-panel mb-4"><h6 class="fw-bold">Recent lab reports</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['labs'] as $l): ?><li><div><a href="<?= url('lab/' . (int) $l['id']) ?>"><?= e($l['order_no']) ?></a> · <?= e($l['patient_name']) ?></div><?php if ((int) $l['abnormal'] > 0): ?><span class="ms-auto badge badge-soft-danger"><?= (int) $l['abnormal'] ?> abnormal</span><?php endif; ?></li><?php endforeach; ?><?php if (!$d['labs']): ?><li class="text-muted">No recent reports.</li><?php endif; ?></ul></div>
        <div class="card-panel"><h6 class="fw-bold">Upcoming appointments</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['upcoming'] as $a): ?><li><div><?= e($a['patient_name']) ?><div class="small text-muted"><?= e((string) $a['reason']) ?></div></div><span class="ms-auto small"><?= fdate($a['appt_date']) ?> <?= e(substr((string) $a['appt_time'], 0, 5)) ?></span></li><?php endforeach; ?><?php if (!$d['upcoming']): ?><li class="text-muted">Nothing booked.</li><?php endif; ?></ul></div></div>
</div>
<?php ob_start(); ?><script>window.DASH = <?= json_encode(['weekly' => array_map(fn($r) => ['label' => $r['label'], 'value' => $r['value']], $d['weekly'])]) ?>;</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
