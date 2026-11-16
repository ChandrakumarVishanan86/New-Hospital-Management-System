<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'lab', 'receptionist', 'patient']);
$isPatient = $user['role'] === 'patient';
$rows = LabModel::list($user);
$canOrder = in_array($user['role'], ['admin', 'doctor'], true);
$tests = $canOrder ? LabTestModel::options() : [];
$pageTitle = $isPatient ? 'My Lab Reports' : 'Laboratory'; $activeNav = 'lab'; $libs = ['datatables']; $pageScripts = $canOrder ? ['assets/js/lab.js'] : [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><h5 class="fw-bold mb-0"><?= e($pageTitle) ?></h5>
        <div class="btn-group ms-3" id="chips"><?php foreach (['' => 'All', 'Ordered' => 'Ordered', 'Sample Collected' => 'Collected', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'] as $k => $l): if ($isPatient && $k !== '' && $k !== 'Completed') continue; ?><button type="button" class="btn btn-sm btn-soft <?= $k === '' ? 'active' : '' ?>" data-f="<?= e($k) ?>"><?= e($l) ?></button><?php endforeach; ?></div>
        <?php if ($canOrder): ?><button class="btn btn-gradient ms-auto" id="btnOrder"><i class="bi bi-plus-lg me-1"></i> New lab order</button><?php endif; ?></div>
    <div class="table-responsive"><table class="table table-hover align-middle w-100" id="tbl"><thead><tr><th>Order</th><th>Date</th><th>Patient</th><th>Tests</th><th>Priority</th><th>Progress</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><span class="plate"><?= e($r['order_no']) ?></span></td><td data-order="<?= e($r['ordered_at']) ?>"><?= fdatetime($r['ordered_at']) ?></td>
            <td><?= e($r['patient_name']) ?><div class="small text-muted"><?= e($r['uhid']) ?></div></td><td class="small"><?= e(mb_strimwidth((string) $r['tests'], 0, 60, '…')) ?></td>
            <td><?= $r['priority'] === 'Urgent' ? '<span class="badge badge-soft-danger">Urgent</span>' : '<span class="badge badge-soft-secondary">Routine</span>' ?></td>
            <td class="small"><?= (int) $r['resulted'] ?>/<?= (int) $r['test_count'] ?><?php if ((int) $r['abnormal'] > 0): ?> <span class="badge badge-soft-danger"><?= (int) $r['abnormal'] ?> abnormal</span><?php endif; ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('lab/' . (int) $r['id']) ?>"><?= $user['role'] === 'lab' && in_array($r['status'], ['Ordered', 'Sample Collected'], true) ? 'Process' : 'Open' ?></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php if ($canOrder): ?>
<div class="modal fade" id="orderModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="orderForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">New lab order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3 position-relative"><label class="form-label">Patient <span class="text-danger">*</span></label><input class="form-control" id="oSearch" placeholder="Search name, UHID, phone…" autocomplete="off"><input type="hidden" id="oPid">
            <div class="list-group position-absolute w-100 shadow" id="oList" style="z-index:5;display:none"></div><div class="form-text" id="oPicked"></div></div>
        <div class="row g-2 mb-3"><div class="col-md-4"><label class="form-label">Priority</label><select class="form-select" id="oPrio"><option>Routine</option><option>Urgent</option></select></div>
            <div class="col-md-8"><label class="form-label">Clinical notes</label><input class="form-control" id="oNotes" maxlength="200"></div></div>
        <label class="form-label">Tests <span class="text-danger">*</span></label>
        <div class="row g-2" id="oTests"><?php foreach ($tests as $t): ?><div class="col-md-6"><label class="form-check border rounded p-2 ps-5 d-block"><input class="form-check-input" type="checkbox" value="<?= (int) $t['id'] ?>"><span class="fw-semibold"><?= e($t['name']) ?></span> <small class="text-muted"><?= e($t['category']) ?> · <?= money($t['price']) ?></small></label></div><?php endforeach; ?></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient">Place order</button></div>
</form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>
var dt = $('#tbl').DataTable({ order: [], pageLength: 10, language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No lab orders.' }, columnDefs: [{ targets: -1, orderable: false }] });
$('#chips').on('click', 'button', function () { $('#chips button').removeClass('active'); $(this).addClass('active'); dt.column(6).search(this.dataset.f ? '^' + this.dataset.f.replace(/ /g, '\\s') + '$' : '', true, false).draw(); });
</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
