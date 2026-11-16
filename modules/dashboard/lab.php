<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::lab();
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = ['chart']; $pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Laboratory', date('l, d F Y'));
?>
<div class="row g-4 mb-4"><?php stat_tile('Awaiting sample', count($d['pending']), 'bi-eyedropper', 'stat-amber'); stat_tile('In processing', count($d['collected']), 'bi-activity', 'stat-cyan'); stat_tile('Reports today', $d['done_today'], 'bi-check2-circle', 'stat-green'); stat_tile('Urgent pending', $d['urgent'], 'bi-exclamation-triangle', 'stat-red'); ?></div>
<div class="row g-4"><div class="col-xl-8"><div class="card-panel"><h6 class="fw-bold">Work queue</h6>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Patient</th><th>Tests</th><th>Status</th><th></th></tr></thead><tbody><?php foreach (array_merge($d['collected'], $d['pending']) as $o): ?><tr><td><span class="plate"><?= e($o['order_no']) ?></span> <?= $o['priority'] === 'Urgent' ? '<span class="badge badge-soft-danger">Urgent</span>' : '' ?></td><td><?= e($o['patient_name']) ?></td><td class="small"><?= e(mb_strimwidth((string) $o['tests'], 0, 50, '…')) ?></td><td><?= status_badge($o['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-gradient" href="<?= url('lab/' . (int) $o['id']) ?>">Process</a></td></tr><?php endforeach; ?><?php if (!$d['pending'] && !$d['collected']): ?><tr><td colspan="5" class="text-center text-muted py-4">No pending work.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="col-xl-4"><div class="card-panel mb-4"><h6 class="fw-bold">Tests by category (30 d)</h6><div style="height:220px"><canvas id="chartPipeline"></canvas></div></div>
    <div class="card-panel"><h6 class="fw-bold">Recent abnormal results</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['abnormal'] as $a): ?><li><div><strong><?= e($a['test']) ?></strong><div class="small text-muted"><?= e($a['patient_name']) ?></div></div><span class="ms-auto badge badge-soft-danger"><?= e($a['result_value']) ?> <?= e($a['result_flag']) ?></span></li><?php endforeach; ?><?php if (!$d['abnormal']): ?><li class="text-muted">None.</li><?php endif; ?></ul></div></div></div>
<?php ob_start(); ?><script>window.DASH = <?= json_encode(['pipeline' => $d['by_cat']]) ?>;</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
