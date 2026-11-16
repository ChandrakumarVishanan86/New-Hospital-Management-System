<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::reception();
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = []; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Front desk', date('l, d F Y'));
?>
<div class="row g-4 mb-4"><?php stat_tile('Appointments today', count($d['queue']), 'bi-calendar-check', 'stat-indigo'); stat_tile('New patients today', $d['new_today'], 'bi-person-plus', 'stat-green'); stat_tile('Beds available', $d['free_beds'], 'bi-door-open', 'stat-cyan'); stat_tile('Ready to bill', $d['unbilled'], 'bi-receipt', 'stat-amber'); ?></div>
<div class="row g-4"><div class="col-xl-7"><div class="card-panel"><div class="d-flex mb-2"><h6 class="fw-bold mb-0">Today's queue</h6><a class="ms-auto small" href="<?= url('appointments') ?>">Manage →</a></div><?php queue_list($d['queue']); ?></div></div>
<div class="col-xl-5"><div class="card-panel"><h6 class="fw-bold">Doctors on duty</h6><ul class="list-unstyled mini-list mb-0"><?php foreach (array_slice($d['doctors'], 0, 8) as $x): ?><li><div><strong><?= e($x['full_name']) ?></strong><div class="small text-muted"><?= e($x['department']) ?> · Room <?= e((string) $x['room_no']) ?></div></div><span class="ms-auto small"><?= money($x['consultation_fee']) ?></span></li><?php endforeach; ?></ul></div></div></div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
