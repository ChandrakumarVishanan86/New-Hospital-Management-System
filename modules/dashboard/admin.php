<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::admin();
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = ['chart']; $pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Welcome back, ' . $user['full_name'] . ' 👋', date('l, d F Y') . ' · hospital overview');
?>
<div class="row g-4 mb-4">
    <?php stat_tile('Active patients', $d['patients'], 'bi-people', 'stat-indigo'); stat_tile('Appointments today (' . $d['appts_done'] . ' done)', $d['appts_today'], 'bi-calendar-check', 'stat-cyan'); stat_tile('Inpatients (' . $d['occupancy'] . '% occupancy)', $d['admitted'], 'bi-hospital', 'stat-amber'); stat_tile('Collected today · ' . CURRENCY, $d['revenue_today'], 'bi-cash-coin', 'stat-green'); ?>
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-8"><div class="card-panel"><h6 class="fw-bold">Collections — last 14 days</h6><div style="height:260px"><canvas id="chartRevenue"></canvas></div></div></div>
    <div class="col-xl-4"><div class="card-panel"><h6 class="fw-bold">Visits by department (30 d)</h6><div style="height:260px"><canvas id="chartDepts"></canvas></div></div></div>
</div>
<div class="row g-4">
    <div class="col-xl-5"><div class="card-panel"><div class="d-flex mb-2"><h6 class="fw-bold mb-0">Today's OPD queue</h6><a class="ms-auto small" href="<?= url('appointments') ?>">All →</a></div><?php queue_list(array_slice($d['queue'], 0, 6)); ?></div></div>
    <div class="col-xl-4"><div class="card-panel mb-4"><h6 class="fw-bold">Low stock</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['low_meds'] as $m): ?><li><strong><?= e($m['name']) ?></strong><span class="ms-auto badge <?= (int) $m['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int) $m['stock'] ?></span></li><?php endforeach; ?><?php if (!$d['low_meds']): ?><li class="text-muted">All stocked.</li><?php endif; ?></ul></div>
        <div class="card-panel"><h6 class="fw-bold">Expiring batches</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['expiring'] as $b): ?><li><div><strong><?= e($b['name']) ?></strong> <small class="text-muted"><?= e($b['batch_no']) ?></small></div><span class="ms-auto due-pill <?= (int) $b['days_left'] < 0 ? 'due-over' : 'due-soon' ?>"><?= (int) $b['days_left'] < 0 ? 'expired' : (int) $b['days_left'] . 'd' ?></span></li><?php endforeach; ?><?php if (!$d['expiring']): ?><li class="text-muted">None.</li><?php endif; ?></ul></div></div>
    <div class="col-xl-3"><div class="card-panel mb-4"><h6 class="fw-bold">Finance</h6><div class="small text-muted">Month to date</div><div class="fs-5 fw-bold"><?= money($d['revenue_mtd']) ?></div><div class="small text-muted mt-2">Outstanding</div><div class="fs-5 fw-bold text-danger"><?= money($d['outstanding']) ?></div></div>
        <div class="card-panel"><h6 class="fw-bold">Current inpatients</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['recent_adm'] as $a): ?><li><div><a href="<?= url('admissions/' . (int) $a['id']) ?>"><?= e($a['patient_name']) ?></a><div class="small text-muted"><?= e($a['ward']) ?> · <?= e($a['bed_no']) ?></div></div></li><?php endforeach; ?><?php if (!$d['recent_adm']): ?><li class="text-muted">No inpatients.</li><?php endif; ?></ul></div></div>
</div>
<?php ob_start(); ?><script>window.DASH = <?= json_encode(['revenue' => array_map(fn($r) => ['label' => $r['label'], 'value' => $r['value']], $d['revenue_chart']), 'depts' => $d['dept_chart']]) ?>;</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
