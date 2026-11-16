<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'pharmacist']);
$d = DashboardModel::pharmacy();
$meds = MedicineModel::options();
$pageTitle = 'Pharmacy Counter'; $activeNav = 'pharmacy'; $libs = []; $pageScripts = ['assets/js/pharmacy.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-orange hover-lift"><div class="stat-icon"><i class="bi bi-file-earmark-medical"></i></div><div><div class="stat-num" data-count="<?= count($d['queue']) ?>">0</div><div class="stat-label">Prescriptions waiting</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-green hover-lift"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-num" data-count="<?= $d['dispensed_today'] ?>" data-decimals="0">0</div><div class="stat-label">Dispensed today · <?= e(CURRENCY) ?></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-red hover-lift"><div class="stat-icon"><i class="bi bi-exclamation-diamond"></i></div><div><div class="stat-num" data-count="<?= count($d['low']) ?>">0</div><div class="stat-label">Low-stock medicines</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card stat-blue hover-lift"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-num" data-count="<?= count($d['expiring']) ?>">0</div><div class="stat-label">Batches expiring ≤ <?= LOW_STOCK_WARN_DAYS ?>d</div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-xl-8"><div class="card-panel">
        <div class="d-flex align-items-center mb-3"><h5 class="fw-bold mb-0">Dispensing queue</h5><button class="btn btn-soft ms-auto" id="btnSale"><i class="bi bi-bag-plus me-1"></i> Counter sale</button></div>
        <?php foreach ($d['queue'] as $q): ?>
        <div class="queue-item">
            <span class="token" style="background:linear-gradient(135deg,#16a34a,#0d9488)"><i class="bi bi-capsule"></i></span>
            <div class="flex-fill"><div class="fw-semibold"><?= e($q['patient_name']) ?> <small class="text-muted"><?= e($q['uhid']) ?></small><?php if ($q['allergies']): ?> <span class="badge badge-soft-danger"><i class="bi bi-exclamation-triangle-fill"></i> Allergy</span><?php endif; ?></div>
                <div class="small text-muted"><?= e($q['rx_no']) ?> · <?= e($q['doctor_name']) ?> · <?= (int) $q['open_lines'] ?> of <?= (int) $q['line_count'] ?> line(s) to dispense · <?= fdatetime($q['created_at']) ?></div></div>
            <?= status_badge($q['status']) ?>
            <button class="btn btn-sm btn-gradient" data-rx="<?= (int) $q['id'] ?>"><i class="bi bi-box-arrow-up-right me-1"></i> Dispense</button>
        </div>
        <?php endforeach; ?>
        <?php if (!$d['queue']): ?><div class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2"></i>All prescriptions are dispensed.</div><?php endif; ?>
    </div></div>
    <div class="col-xl-4">
        <div class="card-panel mb-4"><div class="d-flex align-items-center mb-2"><h6 class="fw-bold mb-0">Low stock</h6><a class="ms-auto small" href="<?= url('medicines') ?>">Medicines →</a></div>
            <ul class="list-unstyled mini-list mb-0"><?php foreach ($d['low'] as $m): ?><li><div><strong><?= e($m['name']) ?></strong> <small class="text-muted"><?= e($m['strength']) ?></small></div><span class="ms-auto badge <?= (int) $m['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int) $m['stock'] ?> / <?= (int) $m['reorder_level'] ?></span></li><?php endforeach; ?>
            <?php if (!$d['low']): ?><li class="text-muted">All medicines are stocked.</li><?php endif; ?></ul></div>
        <div class="card-panel"><h6 class="fw-bold mb-2">Expiring soon</h6>
            <ul class="list-unstyled mini-list mb-0"><?php foreach (array_slice($d['expiring'], 0, 6) as $b): $n = (int) $b['days_left']; ?><li><div><strong><?= e($b['name']) ?></strong><div class="small text-muted"><?= e($b['batch_no']) ?> · <?= (int) $b['qty'] ?> units</div></div><span class="ms-auto due-pill <?= $n < 0 ? 'due-over' : 'due-soon' ?>"><?= $n < 0 ? 'expired' : $n . 'd' ?></span></li><?php endforeach; ?>
            <?php if (!$d['expiring']): ?><li class="text-muted">Nothing expiring soon.</li><?php endif; ?></ul></div>
    </div>
</div>

<div class="modal fade" id="rxModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="rxTitle">Dispense</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="rxBody"></div>
    <div class="modal-footer border-0"><button class="btn btn-soft-danger me-auto" id="rxCancel">Cancel prescription</button><button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-gradient" id="rxGo"><i class="bi bi-check2-circle me-1"></i> Dispense selected</button></div>
</div></div></div>

<div class="modal fade" id="saleModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="saleForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Counter sale (over-the-counter)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3 position-relative"><label class="form-label">Registered patient <span class="text-danger">*</span></label><input class="form-control" id="saleSearch" placeholder="Search name, UHID, phone…" autocomplete="off"><input type="hidden" id="salePid">
            <div class="list-group position-absolute w-100 shadow" id="saleList" style="z-index:5;display:none"></div><div class="form-text" id="salePicked"></div></div>
        <div id="saleLines"></div><button type="button" class="btn btn-sm btn-soft" id="saleAdd"><i class="bi bi-plus-lg"></i> Add medicine</button>
    </div>
    <div class="modal-footer border-0"><div class="me-auto fw-bold" id="saleTotal"></div><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient">Record sale</button></div>
</form></div></div>
<?php ob_start(); ?>
<script>window.PHARM = { meds: <?= json_encode($meds) ?>, openRx: <?= (int) ($_GET['rx'] ?? 0) ?> };</script>
<?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
