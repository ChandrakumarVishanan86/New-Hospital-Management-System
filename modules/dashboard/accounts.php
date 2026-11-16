<?php
declare(strict_types=1);
require_once __DIR__ . '/_parts.php';
$d = DashboardModel::accounts();
$pageTitle = 'Dashboard'; $activeNav = 'dashboard'; $libs = ['chart']; $pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
dash_head('Accounts', date('l, d F Y'));
?>
<div class="row g-4 mb-4"><?php stat_tile('Collected today · ' . CURRENCY, $d['collected_today'], 'bi-cash-coin', 'stat-green'); stat_tile('Collected this month · ' . CURRENCY, $d['collected_mtd'], 'bi-calendar-month', 'stat-cyan'); stat_tile('Outstanding · ' . CURRENCY, $d['outstanding'], 'bi-hourglass-split', 'stat-amber'); stat_tile('Overdue · ' . CURRENCY, $d['overdue'], 'bi-exclamation-octagon', 'stat-red'); ?></div>
<div class="row g-4 mb-4"><div class="col-xl-8"><div class="card-panel"><h6 class="fw-bold">Collections — last 14 days</h6><div style="height:250px"><canvas id="chartRevenue"></canvas></div></div></div>
<div class="col-xl-4"><div class="card-panel"><h6 class="fw-bold">Payment methods (30 d)</h6><div style="height:250px"><canvas id="chartPipeline"></canvas></div></div></div></div>
<div class="row g-4"><div class="col-xl-6"><div class="card-panel"><div class="d-flex mb-2"><h6 class="fw-bold mb-0">Ready to bill</h6><a class="ms-auto small" href="<?= url('invoices') ?>">Billing →</a></div><ul class="list-unstyled mini-list mb-0"><?php foreach (array_slice($d['unbilled'], 0, 6) as $u): ?><li><div><strong><?= e($u['full_name']) ?></strong><div class="small text-muted"><?= (int) $u['line_count'] ?> charge(s)</div></div><span class="ms-auto fw-semibold"><?= money($u['amount']) ?></span></li><?php endforeach; ?><?php if (!$d['unbilled']): ?><li class="text-muted">Nothing to bill.</li><?php endif; ?></ul></div></div>
<div class="col-xl-6"><div class="card-panel"><h6 class="fw-bold">Recent invoices</h6><ul class="list-unstyled mini-list mb-0"><?php foreach ($d['recent'] as $i): ?><li><div><a href="<?= url('invoices/' . (int) $i['id']) ?>"><?= e($i['invoice_no']) ?></a><div class="small text-muted"><?= e($i['patient_name']) ?></div></div><span class="ms-auto text-end"><?= money($i['total']) ?><div><?= status_badge($i['status']) ?></div></span></li><?php endforeach; ?></ul></div></div></div>
<?php ob_start(); ?><script>window.DASH = <?= json_encode(['revenue' => array_map(fn($r) => ['label' => $r['label'], 'value' => $r['value']], $d['chart']), 'pipeline' => $d['methods']]) ?>;</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
