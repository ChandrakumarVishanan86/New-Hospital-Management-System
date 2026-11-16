<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);
$isPatient = $user['role'] === 'patient';
$rows = AdmissionModel::list($user);
$canAdmit = in_array($user['role'], ['admin', 'doctor', 'nurse', 'receptionist'], true);
$free = $canAdmit ? AdmissionModel::freeBeds() : [];
$pageTitle = $isPatient ? 'My Admissions' : 'Admissions (IPD)'; $activeNav = 'admissions'; $libs = ['datatables']; $pageScripts = ['assets/js/admit.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div><h5 class="mb-0 fw-bold"><?= $isPatient ? 'My admissions' : 'In-patient admissions' ?></h5><small class="text-muted"><?= count(array_filter($rows, static fn($r) => $r['status'] === 'Admitted')) ?> currently admitted</small></div>
        <?php if ($canAdmit): ?><button class="btn btn-gradient ms-auto" id="btnAdmit"><i class="bi bi-hospital me-1"></i> Admit Patient</button><?php endif; ?>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle w-100" id="tbl">
        <thead><tr><th>Admission</th><th>Patient</th><th>Ward / Bed</th><th>Doctor</th><th>Admitted</th><th class="text-center">Days</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><a class="fw-semibold" href="<?= url('admissions/' . (int) $r['id']) ?>"><?= e($r['adm_no']) ?></a><div class="small text-muted"><?= e(mb_strimwidth((string) $r['reason'], 0, 40, '…')) ?></div></td>
                <td><?= e($r['patient_name']) ?><div class="small text-muted"><?= e($r['uhid']) ?></div></td>
                <td><?= e($r['ward']) ?> <span class="plate"><?= e($r['bed_no']) ?></span></td><td><?= e($r['doctor_name']) ?></td>
                <td data-order="<?= e($r['admitted_at']) ?>"><?= fdate($r['admitted_at']) ?></td><td class="text-center"><?= (int) $r['days'] ?></td>
                <td><?= status_badge($r['status']) ?></td><td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('admissions/' . (int) $r['id']) ?>">Open</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
</div>
<?php if ($canAdmit) { require __DIR__ . '/_admit_modal.php'; } ?>
<?php ob_start(); ?>
<script>window.ADMIT = { free: <?= json_encode($free) ?>, doctors: <?= json_encode($canAdmit ? DoctorModel::options() : []) ?>, role: <?= json_encode($user['role']) ?>, myDoctor: <?= json_encode($user['doctor_id'] ?? null) ?> };</script>
<?php $pageConfig = ob_get_clean(); ob_start(); ?>
<script>$('#tbl').DataTable({ order: [[6, 'asc'], [4, 'desc']], pageLength: 10, language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No admissions.' }, columnDefs: [{ targets: -1, orderable: false }] });</script>
<?php $pageInline = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
