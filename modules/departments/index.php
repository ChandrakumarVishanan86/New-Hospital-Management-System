<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin']);
$pageTitle = 'Departments'; $activeNav = 'departments'; $libs = ['datatables']; $pageScripts = ['assets/js/crud.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex align-items-center mb-3"><div><h5 class="mb-0 fw-bold">Departments</h5><small class="text-muted">Clinical departments doctors belong to.</small></div>
        <button class="btn btn-gradient ms-auto" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Department</button></div>
    <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100"><thead><tr><th>Name</th><th>Description</th><th class="text-center">Doctors</th><th>Status</th><th class="text-end">Actions</th></tr></thead></table></div>
</div>
<?php ob_start(); ?>
<script>
new Crud({ table: '#tbl', api: APP.base + '/departments/api', label: 'Department', canWrite: true,
    columns: [{ data: 'name', render: function (d) { return '<span class="fw-semibold">' + SMS.esc(d) + '</span>'; } }, { data: 'description', render: function (d) { return d ? SMS.esc(d) : '–'; } },
        { data: 'doctor_count', className: 'text-center' }, { data: 'is_active', render: function (d) { return parseInt(d, 10) ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>'; } }],
    fields: [{ name: 'name', label: 'Name', required: true, max: 80, col: 12, unique: true }, { name: 'description', label: 'Description', max: 255, col: 12 }, { name: 'is_active', label: 'Active', type: 'checkbox', col: 12, default: true }] });
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
