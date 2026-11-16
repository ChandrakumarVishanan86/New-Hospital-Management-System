<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'pharmacist', 'patient']);
$rows = PharmacyModel::listFor($user);
$pageTitle = $user['role'] === 'patient' ? 'My Prescriptions' : 'Prescriptions'; $activeNav = 'prescriptions'; $libs = ['datatables']; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <h5 class="fw-bold mb-3"><?= e($pageTitle) ?></h5>
    <div class="table-responsive"><table class="table table-hover align-middle w-100" id="tbl"><thead><tr><th>Rx</th><th>Date</th><th>Patient</th><th>Doctor</th><th>Medicines</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><span class="plate"><?= e($r['rx_no']) ?></span></td><td data-order="<?= e($r['created_at']) ?>"><?= fdate($r['created_at']) ?></td>
            <td><?= e($r['patient_name']) ?><div class="small text-muted"><?= e($r['uhid']) ?></div></td><td><?= e($r['doctor_name']) ?></td>
            <td class="small"><?= e(mb_strimwidth((string) $r['medicines'], 0, 70, '…')) ?></td><td><?= status_badge($r['status']) ?></td>
            <td class="text-end text-nowrap"><a class="btn btn-sm btn-soft" target="_blank" href="<?= url('prescriptions/' . (int) $r['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <?php if (in_array($user['role'], ['admin', 'pharmacist'], true) && in_array($r['status'], ['Pending', 'Partial'], true)): ?><a class="btn btn-sm btn-gradient" href="<?= url('pharmacy?rx=' . (int) $r['id']) ?>">Dispense</a><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php ob_start(); ?>
<script>$('#tbl').DataTable({ order: [[1, 'desc']], pageLength: 10, language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No prescriptions.' }, columnDefs: [{ targets: -1, orderable: false }] });</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
