<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin', 'lab']);
$pageTitle = 'Lab Test Catalogue'; $activeNav = 'lab_tests'; $libs = ['datatables']; $pageScripts = ['assets/js/crud.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex align-items-center mb-3"><div><h5 class="mb-0 fw-bold">Laboratory Test Catalogue</h5><small class="text-muted">Prices and reference ranges used to flag results automatically.</small></div>
        <button class="btn btn-gradient ms-auto" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Test</button></div>
    <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100"><thead><tr><th>Code</th><th>Test</th><th>Category</th><th>Sample</th><th class="text-end">Price</th><th>Reference</th><th>Status</th><th class="text-end">Actions</th></tr></thead></table></div>
</div>
<?php ob_start(); ?>
<script>
new Crud({ table: '#tbl', api: APP.base + '/lab-tests/api', label: 'Lab test', canWrite: true, modalSize: 'modal-lg',
    columns: [{ data: 'code', render: function (d) { return '<span class="plate">' + SMS.esc(d) + '</span>'; } }, { data: 'name', render: function (d) { return '<span class="fw-semibold">' + SMS.esc(d) + '</span>'; } },
        { data: 'category', render: function (d) { return d ? SMS.esc(d) : '–'; } }, { data: 'sample_type', render: function (d) { return d ? SMS.esc(d) : '–'; } },
        { data: 'price', className: 'text-end', render: function (d, t) { return t === 'display' ? SMS.money(d) : d; } },
        { data: null, render: function (r) { if (r.ref_min !== null || r.ref_max !== null) return SMS.esc((r.ref_min ?? '') + ' – ' + (r.ref_max ?? '') + ' ' + (r.unit || '')); return r.ref_text ? SMS.esc(r.ref_text) : '–'; } },
        { data: 'is_active', render: function (d) { return parseInt(d, 10) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>'; } }],
    fields: [{ name: 'code', label: 'Code', required: true, max: 20, col: 4, upper: true, unique: true, pattern: '^[A-Za-z0-9\\-]{2,20}$', patternMsg: 'Letters, digits and dashes only.' },
        { name: 'name', label: 'Test name', required: true, max: 100, col: 8 }, { name: 'category', label: 'Category', max: 50, col: 4 }, { name: 'sample_type', label: 'Sample type', max: 30, col: 4 },
        { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: '0.01', col: 4 },
        { name: 'unit', label: 'Unit', max: 20, col: 3 }, { name: 'ref_min', label: 'Reference min', type: 'number', step: '0.01', col: 3 }, { name: 'ref_max', label: 'Reference max', type: 'number', step: '0.01', col: 3 },
        { name: 'ref_text', label: 'Reference text (for non-numeric)', max: 80, col: 3, placeholder: 'Negative' },
        { name: 'is_active', label: 'Active', type: 'checkbox', col: 12, default: true }] });
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
