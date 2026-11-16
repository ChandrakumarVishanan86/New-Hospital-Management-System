<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'accountant']);
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
if ($from > $to) { [$from, $to] = [$to, $from]; }
$r = ReportModel::summary($from, $to);
$v = $r['visits'][0] ?? ['total' => 0, 'done' => 0, 'cancelled' => 0, 'noshow' => 0]; $ad = $r['admissions'][0] ?? [];
$pageTitle = 'Reports'; $activeNav = 'reports'; $libs = ['chart', 'flatpickr']; $pageScripts = ['assets/js/reports.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<form class="card-panel mb-4 d-flex flex-wrap align-items-end gap-3" method="get">
    <div><label class="form-label small mb-1">From</label><input class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
    <div><label class="form-label small mb-1">To</label><input class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-gradient">Apply</button>
    <a class="btn btn-soft ms-auto" href="<?= url('reports/export?from=' . $from . '&to=' . $to) ?>"><i class="bi bi-download me-1"></i> Export invoices CSV</a>
</form>
<div class="row g-4 mb-4">
<?php foreach ([['Invoiced', money($r['invoices']['invoiced']), 'bi-receipt', 'stat-blue'], ['Collected', money($r['collected']), 'bi-cash-coin', 'stat-green'], ['Outstanding', money($r['invoices']['outstanding']), 'bi-hourglass-split', 'stat-orange'], ['Insurance claims', money($r['invoices']['insurance']), 'bi-shield-check', 'stat-purple'],
    ['Appointments', (string) (int) $v['total'] . ' (' . (int) $v['done'] . ' done)', 'bi-calendar-check', 'stat-blue'], ['New patients', (string) $r['new_patients'], 'bi-person-plus', 'stat-green'], ['Admissions', (string) (int) ($ad['total'] ?? 0), 'bi-hospital', 'stat-orange'], ['Stock value', money($r['stock_value']), 'bi-capsule', 'stat-purple']] as [$l, $val, $ic, $c]): ?>
    <div class="col-sm-6 col-xl-3"><div class="stat-card <?= $c ?> hover-lift"><div class="stat-icon"><i class="bi <?= $ic ?>"></i></div><div><div class="fs-5 fw-bold"><?= e($val) ?></div><div class="stat-label"><?= $l ?></div></div></div></div>
<?php endforeach; ?></div>
<div class="row g-4 mb-4">
    <div class="col-xl-8"><div class="card-panel"><h6 class="fw-bold">Daily collections</h6><div style="height:260px"><canvas id="chartDaily"></canvas></div></div></div>
    <div class="col-xl-4"><div class="card-panel"><h6 class="fw-bold">Payment methods</h6><div style="height:260px"><canvas id="chartMethods"></canvas></div></div></div>
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Revenue by source</h6><div style="height:260px"><canvas id="chartSources"></canvas></div></div></div>
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Visits by department</h6><div style="height:260px"><canvas id="chartDepts"></canvas></div></div></div>
</div>
<div class="row g-4">
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Doctor performance</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Doctor</th><th>Department</th><th class="text-end">Visits</th><th class="text-end">Fees</th></tr></thead><tbody>
        <?php foreach ($r['doctors'] as $d): ?><tr><td><?= e($d['full_name']) ?></td><td><?= e($d['department']) ?></td><td class="text-end"><?= (int) $d['visits'] ?></td><td class="text-end"><?= money($d['fees']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Top diagnoses</h6><div class="table-responsive"><table class="table table-sm"><tbody>
        <?php foreach ($r['diagnoses'] as $d): ?><tr><td><?= e($d['label']) ?></td><td class="text-end"><?= (int) $d['value'] ?></td></tr><?php endforeach; ?><?php if (!$r['diagnoses']): ?><tr><td class="text-muted">No data.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Laboratory</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Test</th><th class="text-end">Orders</th><th class="text-end">Abnormal</th><th class="text-end">Revenue</th></tr></thead><tbody>
        <?php foreach ($r['lab_tests'] as $d): ?><tr><td><?= e($d['name']) ?></td><td class="text-end"><?= (int) $d['n'] ?></td><td class="text-end"><?= (int) $d['abnormal'] ?></td><td class="text-end"><?= money($d['revenue']) ?></td></tr><?php endforeach; ?><?php if (!$r['lab_tests']): ?><tr><td colspan="4" class="text-muted">No data.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Pharmacy sales</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Medicine</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead><tbody>
        <?php foreach ($r['pharmacy'] as $d): ?><tr><td><?= e($d['name']) ?></td><td class="text-end"><?= (int) $d['qty'] ?></td><td class="text-end"><?= money($d['revenue']) ?></td></tr><?php endforeach; ?><?php if (!$r['pharmacy']): ?><tr><td colspan="3" class="text-muted">No data.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php ob_start(); ?><script>window.REPORT = <?= json_encode(['daily' => array_map(fn($d) => ['label' => $d['d'], 'value' => $d['value']], $r['daily']), 'methods' => $r['methods'], 'sources' => $r['by_source'], 'depts' => $r['departments']]) ?>;</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
