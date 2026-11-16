<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'receptionist', 'accountant', 'patient']);
$isPatient = $user['role'] === 'patient';
$rows = InvoiceModel::list($user);
$unbilled = $isPatient ? [] : InvoiceModel::unbilledPatients();
$pageTitle = $isPatient ? 'My Bills' : 'Billing'; $activeNav = 'invoices'; $libs = ['datatables']; $pageScripts = $isPatient ? [] : ['assets/js/invoices.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <?php if (!$isPatient): ?><ul class="nav nav-pills mb-3" id="tabs"><li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tInv">Invoices <span class="badge bg-secondary"><?= count($rows) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tUnb">Ready to bill <span class="badge bg-warning text-dark"><?= count($unbilled) ?></span></button></li></ul><?php endif; ?>
    <div class="tab-content"><div class="tab-pane fade show active" id="tInv">
        <div class="btn-group mb-3" id="chips"><?php foreach (['' => 'All', 'Unpaid' => 'Unpaid', 'Partial' => 'Partial', 'Paid' => 'Paid', 'Cancelled' => 'Cancelled'] as $k => $l): ?><button type="button" class="btn btn-sm btn-soft <?= $k === '' ? 'active' : '' ?>" data-f="<?= $k ?>"><?= $l ?></button><?php endforeach; ?></div>
        <div class="table-responsive"><table class="table table-hover align-middle w-100" id="tbl"><thead><tr><th>Invoice</th><th>Date</th><th>Patient</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><span class="plate"><?= e($r['invoice_no']) ?></span></td><td data-order="<?= e($r['issued_at']) ?>"><?= fdate($r['issued_at']) ?></td>
                <td><?= e($r['patient_name']) ?><div class="small text-muted"><?= e($r['uhid']) ?><?= $r['adm_no'] ? ' · ' . e($r['adm_no']) : '' ?></div></td>
                <td class="text-end" data-order="<?= $r['total'] ?>"><?= money($r['total']) ?></td><td class="text-end"><?= money($r['paid']) ?></td><td class="text-end fw-semibold" data-order="<?= $r['balance'] ?>"><?= $r['status'] === 'Cancelled' ? '–' : money($r['balance']) ?></td>
                <td><?= status_badge($r['overdue'] ? 'Overdue' : $r['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('invoices/' . (int) $r['id']) ?>">Open</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    </div>
    <?php if (!$isPatient): ?><div class="tab-pane fade" id="tUnb">
        <p class="text-muted small">Charges from consultations, lab, pharmacy and completed admissions. Admitted patients' bed charges appear here after discharge.</p>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Patient</th><th>Lines</th><th class="text-end">Amount</th><th>Last charge</th><th></th></tr></thead><tbody>
        <?php foreach ($unbilled as $u): ?><tr><td><?= e($u['full_name']) ?><div class="small text-muted"><?= e($u['uhid']) ?><?= $u['insurance_provider'] ? ' · ' . e($u['insurance_provider']) : '' ?></div></td><td><?= (int) $u['line_count'] ?></td><td class="text-end fw-semibold"><?= money($u['amount']) ?></td><td><?= fdatetime($u['last_charge']) ?></td>
            <td class="text-end"><button class="btn btn-sm btn-gradient" data-bill="<?= (int) $u['id'] ?>" data-name="<?= e($u['full_name']) ?>" data-ins="<?= $u['insurance_provider'] ? 1 : 0 ?>">Create invoice</button></td></tr><?php endforeach; ?>
        <?php if (!$unbilled): ?><tr><td colspan="5" class="text-center text-muted py-4">Nothing waiting to be billed.</td></tr><?php endif; ?></tbody></table></div>
    </div><?php endif; ?></div>
</div>
<?php if (!$isPatient): ?>
<div class="modal fade" id="billModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="billForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="billTitle">Create invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Bill</label><select class="form-select" id="billScope"></select></div>
        <div class="row g-2"><div class="col-6"><label class="form-label">Discount (<?= e(CURRENCY) ?>)</label><input type="number" class="form-control" id="billDisc" min="0" step="0.01" value="0"></div>
            <div class="col-6"><label class="form-label">Insurance covers (<?= e(CURRENCY) ?>)</label><input type="number" class="form-control" id="billIns" min="0" step="0.01" value="0"></div></div>
        <div class="form-text" id="billHint"></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient">Generate</button></div>
</form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
var dt = $('#tbl').DataTable({ order: [[1, 'desc']], pageLength: 10, language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No invoices.' }, columnDefs: [{ targets: -1, orderable: false }] });
$('#chips').on('click', 'button', function () { $('#chips button').removeClass('active'); $(this).addClass('active'); dt.column(6).search(this.dataset.f ? this.dataset.f : '', false, false).draw(); });
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
