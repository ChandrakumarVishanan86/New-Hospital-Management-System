<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'receptionist', 'accountant', 'patient']);
$inv = InvoiceModel::find((int) ($_GET['id'] ?? 0));
if (!$inv || !InvoiceModel::canAccess($user, $inv)) { http_response_code(404); exit('Invoice not found.'); }
$items = InvoiceModel::items((int) $inv['id']); $pays = InvoiceModel::payments((int) $inv['id']);
$staff = $user['role'] !== 'patient';
$canPay = $staff && in_array($inv['status'], ['Unpaid', 'Partial'], true);
$canCancel = in_array($user['role'], ['admin', 'accountant'], true) && $inv['status'] !== 'Cancelled' && (float) $inv['paid'] <= 0;
$pageTitle = $inv['invoice_no']; $activeNav = 'invoices'; $libs = []; $pageScripts = $staff ? ['assets/js/invoice_view.js'] : [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4"><div class="col-xl-8"><div class="card-panel">
    <div class="d-flex flex-wrap gap-3 mb-3"><div><h4 class="fw-bold mb-1"><?= e($inv['invoice_no']) ?> <?= status_badge($inv['overdue'] ? 'Overdue' : $inv['status']) ?></h4>
        <div class="text-muted"><?= e($inv['patient_name']) ?> · <span class="plate"><?= e($inv['uhid']) ?></span><?= $inv['adm_no'] ? ' · ' . e($inv['adm_no']) : '' ?></div>
        <div class="small text-muted">Issued <?= fdate($inv['issued_at']) ?> · Due <?= fdate($inv['due_date']) ?></div></div>
        <div class="ms-auto no-print"><a class="btn btn-soft" target="_blank" href="<?= url('invoices/' . (int) $inv['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a></div></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Description</th><th>Type</th><th class="text-center">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead><tbody>
    <?php foreach ($items as $i): ?><tr><td><?= e($i['description']) ?></td><td><span class="badge badge-soft-secondary"><?= e($i['source']) ?></span></td><td class="text-center"><?= rtrim(rtrim((string) $i['qty'], '0'), '.') ?></td><td class="text-end"><?= money($i['unit_price']) ?></td><td class="text-end"><?= money($i['amount']) ?></td></tr><?php endforeach; ?></tbody>
    <tfoot class="small"><tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end"><?= money($inv['subtotal']) ?></td></tr>
        <?php if ((float) $inv['discount'] > 0): ?><tr><td colspan="4" class="text-end">Discount</td><td class="text-end">− <?= money($inv['discount']) ?></td></tr><?php endif; ?>
        <?php if ((float) $inv['insurance_covered'] > 0): ?><tr><td colspan="4" class="text-end">Insurance (<?= e($inv['insurance_provider']) ?>)</td><td class="text-end">− <?= money($inv['insurance_covered']) ?></td></tr><?php endif; ?>
        <tr class="fw-bold fs-6"><td colspan="4" class="text-end">Total</td><td class="text-end"><?= money($inv['total']) ?></td></tr><tr><td colspan="4" class="text-end">Paid</td><td class="text-end"><?= money($inv['paid']) ?></td></tr>
        <tr class="fw-bold"><td colspan="4" class="text-end">Balance</td><td class="text-end"><?= $inv['status'] === 'Cancelled' ? '–' : money($inv['balance']) ?></td></tr></tfoot></table></div>
</div></div>
<div class="col-xl-4">
    <div class="card-panel mb-4"><div class="d-flex align-items-center mb-2"><h6 class="fw-bold mb-0">Payments</h6>
        <?php if ($canPay): ?><button class="btn btn-sm btn-gradient ms-auto" id="btnPay"><i class="bi bi-plus-lg"></i> Record</button><?php endif; ?></div>
        <ul class="list-unstyled mini-list mb-0"><?php foreach ($pays as $p): ?><li><div><strong><?= money($p['amount']) ?></strong> <span class="badge badge-soft-secondary"><?= e($p['method']) ?></span><div class="small text-muted"><?= fdatetime($p['paid_at']) ?><?= $p['reference'] ? ' · ' . e($p['reference']) : '' ?></div></div></li><?php endforeach; ?>
        <?php if (!$pays): ?><li class="text-muted">No payments yet.</li><?php endif; ?></ul></div>
    <?php if ($canCancel): ?><button class="btn btn-soft-danger w-100 no-print" id="btnCancelInv">Cancel invoice</button><?php endif; ?>
</div></div>
<?php if ($canPay): ?>
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="payForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Record payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="mb-3"><label class="form-label">Amount (<?= e(CURRENCY) ?>)</label><input type="number" class="form-control" id="payAmt" min="0.01" step="0.01" value="<?= e((string) $inv['balance']) ?>"><div class="form-text">Balance: <?= money($inv['balance']) ?></div></div>
        <div class="mb-3"><label class="form-label">Method</label><select class="form-select" id="payMethod"><option>Cash</option><option>Card</option><option>Online</option></select></div>
        <div><label class="form-label">Reference</label><input class="form-control" id="payRef" maxlength="60"></div></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient">Save payment</button></div>
</form></div></div>
<?php endif; ?>
<?php ob_start(); ?><script>window.INV = { id: <?= (int) $inv['id'] ?> };</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
