<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'pharmacist', 'doctor']);
$canWrite = in_array($user['role'], ['admin', 'pharmacist'], true);
$suppliers = array_map(static fn($s) => [$s['id'], $s['name']], db()->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll());
$expiring = $canWrite ? MedicineModel::expiring() : [];
$pageTitle = 'Medicines & Stock'; $activeNav = 'medicines'; $libs = ['datatables', 'flatpickr']; $pageScripts = ['assets/js/crud.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<?php if ($expiring): ?>
<div class="alert alert-warning d-flex align-items-start gap-2"><i class="bi bi-hourglass-split fs-5"></i>
    <div><strong><?= count($expiring) ?> batch(es) expired or expiring within <?= LOW_STOCK_WARN_DAYS ?> days:</strong>
        <?php foreach (array_slice($expiring, 0, 4) as $b): ?><span class="badge <?= (int) $b['days_left'] < 0 ? 'bg-danger' : 'bg-warning text-dark' ?> ms-1"><?= e($b['name']) ?> · <?= e($b['batch_no']) ?> · <?= (int) $b['days_left'] < 0 ? 'expired' : (int) $b['days_left'] . 'd' ?></span><?php endforeach; ?>
        <?php if (count($expiring) > 4): ?><span class="text-muted ms-1">+<?= count($expiring) - 4 ?> more (open a medicine to see its batches)</span><?php endif; ?></div></div>
<?php endif; ?>
<div class="card-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div><h5 class="mb-0 fw-bold">Medicines &amp; Stock</h5><small class="text-muted">Stock is held in batches and dispensed first-expiry-first-out.</small></div>
        <div class="chip-filters ms-md-auto"><button class="chip active" data-f="">All</button><button class="chip" data-f="low">Low stock</button><button class="chip" data-f="expiring">Expiring</button></div>
        <?php if ($canWrite): ?><button class="btn btn-gradient" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Medicine</button><?php endif; ?>
    </div>
    <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100"><thead><tr><th>Medicine</th><th>Code</th><th>Category</th><th class="text-end">Price</th><th>Stock</th><th>Next expiry</th><th class="text-end">Actions</th></tr></thead></table></div>
</div>

<div class="modal fade" id="batchModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="batchTitle">Batches</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Batch</th><th>Supplier</th><th>Expiry</th><th class="text-end">Qty</th><th class="text-end">Cost</th><th></th></tr></thead><tbody id="batchBody"></tbody></table></div></div>
</div></div></div>

<?php if ($canWrite): ?>
<div class="modal fade" id="recvModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="recvForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="recvTitle">Receive stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <input type="hidden" name="medicine_id">
        <div class="col-6"><label class="form-label">Batch number <span class="text-danger">*</span></label><input class="form-control text-uppercase" name="batch_no" maxlength="30" required><div class="invalid-feedback" data-for="batch_no"></div></div>
        <div class="col-6"><label class="form-label">Expiry date <span class="text-danger">*</span></label><input class="form-control" name="expiry_date" id="rExp" required><div class="invalid-feedback" data-for="expiry_date"></div></div>
        <div class="col-6"><label class="form-label">Quantity <span class="text-danger">*</span></label><input type="number" min="1" class="form-control" name="qty" required><div class="invalid-feedback" data-for="qty"></div></div>
        <div class="col-6"><label class="form-label">Cost price (per unit)</label><input type="number" min="0" step="0.01" class="form-control" name="cost_price" value="0"><div class="invalid-feedback" data-for="cost_price"></div></div>
        <div class="col-12"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id"><option value="">— None —</option><?php foreach ($suppliers as $s): ?><option value="<?= (int) $s[0] ?>"><?= e($s[1]) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient"><i class="bi bi-box-arrow-in-down me-1"></i> Receive</button></div>
</form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
var canWrite = <?= $canWrite ? 'true' : 'false' ?>, filter = '';
var crud = new Crud({ table: '#tbl', api: APP.base + '/medicines/api', label: 'Medicine', canWrite: canWrite, canDelete: <?= $user['role'] === 'admin' ? 'true' : 'false' ?>, modalSize: 'modal-lg',
    columns: [
        { data: 'name', render: function (d, t, r) { return '<div class="fw-semibold">' + SMS.esc(d) + ' <small class="text-muted">' + SMS.esc(r.strength || '') + '</small></div><small class="text-muted">' + SMS.esc(r.form) + (r.generic_name ? ' · ' + SMS.esc(r.generic_name) : '') + '</small>'; } },
        { data: 'code', render: function (d) { return '<span class="plate">' + SMS.esc(d) + '</span>'; } }, { data: 'category', render: function (d) { return d ? SMS.esc(d) : '–'; } },
        { data: 'unit_price', className: 'text-end', render: function (d, t) { return t === 'display' ? SMS.money(d) : d; } },
        { data: 'stock', render: function (d, t, r) { if (t !== 'display') return parseInt(d, 10);
            var low = parseInt(d, 10) <= parseInt(r.reorder_level, 10), pct = Math.min(100, Math.round(100 * d / Math.max(1, r.reorder_level * 3)));
            return '<div class="d-flex align-items-center gap-2"><strong>' + d + '</strong>' + (low ? '<span class="badge badge-soft-danger">Low</span>' : '') + (parseInt(r.expired_qty, 10) ? '<span class="badge badge-soft-warning" title="Expired stock on hand">' + r.expired_qty + ' expired</span>' : '') + '</div><div class="bar-track bar-sm"><div class="bar-fill ' + (low ? 'bar-bad' : 'bar-ok') + '" style="--w:' + pct + '%"></div></div>'; } },
        { data: 'next_expiry', render: function (d) { if (!d) return '–'; var days = Math.round((new Date(d) - new Date()) / 864e5); return SMS.fdate(d) + ' ' + (days <= <?= LOW_STOCK_WARN_DAYS ?> ? '<span class="due-pill due-soon">' + days + 'd</span>' : ''); } }
    ],
    actions: function (r) { return '<button class="btn btn-icon btn-soft" title="Batches" data-act="batches"><i class="bi bi-layers"></i></button>' + (canWrite ? ' <button class="btn btn-icon btn-soft-success" title="Receive stock" data-act="receive"><i class="bi bi-box-arrow-in-down"></i></button>' : ''); },
    onAction: function (act, row) { if (act === 'batches') { showBatches(row); return true; } if (act === 'receive') { openRecv(row); return true; } return false; },
    fields: [
        { name: 'name', label: 'Medicine name', required: true, max: 100, col: 6 }, { name: 'code', label: 'Code', required: true, max: 20, col: 3, upper: true, unique: true, pattern: '^[A-Za-z0-9\\-]{2,20}$', patternMsg: 'Letters, digits and dashes only.' },
        { name: 'form', label: 'Form', type: 'select', required: true, col: 3, options: ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Drops', 'Inhaler', 'Other'].map(function (x) { return [x, x]; }) },
        { name: 'generic_name', label: 'Generic name', max: 100, col: 4 }, { name: 'category', label: 'Category', max: 50, col: 4 }, { name: 'strength', label: 'Strength', max: 30, col: 4, placeholder: '500mg' },
        { name: 'unit_price', label: 'Unit price', type: 'number', required: true, min: 0.01, step: '0.01', col: 4 }, { name: 'reorder_level', label: 'Reorder level', type: 'number', required: true, min: 0, integer: true, col: 4, default: 50 },
        { name: 'is_active', label: 'Active', type: 'checkbox', col: 4, default: true }]
});
// filter chips (client-side on the loaded rows)
$.fn.dataTable.ext.search.push(function (s, d, i) { if (s.nTable.id !== 'tbl' || !filter) return true; var r = crud.table.row(i).data();
    if (filter === 'low') return parseInt(r.stock, 10) <= parseInt(r.reorder_level, 10);
    return r.next_expiry && (new Date(r.next_expiry) - new Date()) / 864e5 <= <?= LOW_STOCK_WARN_DAYS ?> || parseInt(r.expired_qty, 10) > 0; });
document.querySelectorAll('.chip-filters .chip').forEach(function (c) { c.addEventListener('click', function () { document.querySelectorAll('.chip-filters .chip').forEach(function (x) { x.classList.remove('active'); }); c.classList.add('active'); filter = c.dataset.f; crud.table.draw(); }); });

var batchModal = new bootstrap.Modal(document.getElementById('batchModal')), curMed = null;
function showBatches(m) { curMed = m; document.getElementById('batchTitle').textContent = m.name + ' ' + (m.strength || '') + ' — batches';
    var body = document.getElementById('batchBody'); body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>'; batchModal.show();
    SMS.api(APP.base + '/medicines/api?action=batches&id=' + m.id).then(function (r) {
        body.innerHTML = r.data.length ? r.data.map(function (b) { var exp = parseInt(b.expired, 10);
            return '<tr><td><span class="plate">' + SMS.esc(b.batch_no) + '</span></td><td>' + SMS.esc(b.supplier || '–') + '</td><td>' + SMS.fdate(b.expiry_date) + ' ' + (exp ? '<span class="due-pill due-over">expired</span>' : (b.days_left <= <?= LOW_STOCK_WARN_DAYS ?> ? '<span class="due-pill due-soon">' + b.days_left + 'd</span>' : '')) + '</td><td class="text-end fw-semibold">' + b.qty + '</td><td class="text-end">' + SMS.money(b.cost_price) + '</td><td class="text-end">' +
                (canWrite && exp && parseInt(b.qty, 10) > 0 ? '<button class="btn btn-sm btn-soft-danger" data-wo="' + b.id + '">Write off</button>' : '') + '</td></tr>'; }).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">No batches yet.</td></tr>'; }); }
document.getElementById('batchBody').addEventListener('click', function (e) { var b = e.target.closest('[data-wo]'); if (!b) return;
    SMS.confirmBox('Write off this expired batch? Its remaining quantity will be set to zero.', 'Write off').then(function (ok) { if (!ok) return;
        SMS.api(APP.base + '/medicines/api', { method: 'POST', body: { action: 'write_off', batch_id: b.dataset.wo } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) { showBatches(curMed); crud.reload(); } }); }); });
<?php if ($canWrite): ?>
var recvModal = new bootstrap.Modal(document.getElementById('recvModal')), recvForm = document.getElementById('recvForm');
flatpickr('#rExp', { dateFormat: 'Y-m-d', minDate: new Date().fp_incr(1), disableMobile: true });
function openRecv(m) { recvForm.reset(); recvForm.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); }); recvForm.elements.medicine_id.value = m.id; document.getElementById('recvTitle').textContent = 'Receive stock — ' + m.name + ' ' + (m.strength || ''); recvModal.show(); }
recvForm.addEventListener('submit', function (e) { e.preventDefault(); recvForm.querySelectorAll('.is-invalid').forEach(function (x) { x.classList.remove('is-invalid'); });
    var o = {}; new FormData(recvForm).forEach(function (v, k) { o[k] = v; }); o.action = 'receive';
    SMS.api(APP.base + '/medicines/api', { method: 'POST', body: o }).then(function (r) {
        if (r.success) { recvModal.hide(); SMS.toast(r.message, 'success'); crud.reload(); return; }
        if (r.errors) Object.keys(r.errors).forEach(function (k) { var el = recvForm.elements[k]; if (el) { el.classList.add('is-invalid'); var fb = recvForm.querySelector('[data-for="' + k + '"]'); if (fb) fb.textContent = r.errors[k]; } });
        SMS.toast(r.message, 'danger'); }); });
<?php endif; ?>
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
