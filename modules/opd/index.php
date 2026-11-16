<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse']);
$role = $user['role'];
$queue = AppointmentModel::queue($role === 'doctor' ? (int) $user['doctor_id'] : null);
$waiting = count(array_filter($queue, static fn($a) => in_array($a['status'], ['Scheduled', 'Checked-in'], true)));
$pageTitle = 'OPD Consultations'; $activeNav = 'opd'; $libs = []; $pageScripts = [];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-sm-4"><div class="stat-card stat-teal hover-lift"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-num" data-count="<?= count($queue) ?>">0</div><div class="stat-label">Patients today</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card stat-orange hover-lift"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-num" data-count="<?= $waiting ?>">0</div><div class="stat-label">Waiting</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card stat-green hover-lift"><div class="stat-icon"><i class="bi bi-check2-all"></i></div><div><div class="stat-num" data-count="<?= count(array_filter($queue, static fn($a) => $a['status'] === 'Completed')) ?>">0</div><div class="stat-label">Seen</div></div></div></div>
</div>
<div class="card-panel">
    <div class="d-flex align-items-center mb-2"><div><h5 class="fw-bold mb-0">Today's queue · <?= fdate(date('Y-m-d')) ?></h5><small class="text-muted"><?= $role === 'doctor' ? 'Your patients, in token order.' : 'All doctors. Record vitals before the consultation.' ?></small></div>
        <a class="btn btn-soft ms-auto" href="<?= url('opd') ?>"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a></div>
    <?php foreach ($queue as $a): $closed = $a['status'] === 'Completed'; ?>
    <div class="queue-item">
        <span class="token"><?= (int) $a['token_no'] ?></span>
        <div class="flex-fill"><div class="fw-semibold"><?= e($a['patient_name']) ?> <small class="text-muted"><?= e($a['uhid']) ?> · <?= e($a['gender']) ?>, <?= (int) $a['patient_age'] ?>y</small></div>
            <div class="small text-muted"><?= e(substr((string) $a['appt_time'], 0, 5)) ?> · <?= e($a['doctor_name']) ?><?= $a['reason'] ? ' · ' . e($a['reason']) : '' ?></div></div>
        <?= status_badge($a['status']) ?>
        <div class="text-nowrap">
            <?php if (in_array($role, ['doctor', 'admin'], true)): ?><a class="btn btn-sm <?= $closed ? 'btn-soft' : 'btn-gradient' ?>" href="<?= url('opd/consult/' . (int) $a['id']) ?>"><i class="bi bi-stethoscope me-1"></i><?= $closed ? 'View' : ($a['status'] === 'In Consultation' ? 'Continue' : 'Consult') ?></a><?php endif; ?>
            <?php if (in_array($role, ['nurse', 'admin'], true) && !$closed): ?><a class="btn btn-sm btn-soft" href="<?= url('appointments') ?>" title="Record vitals from the appointments list"><i class="bi bi-heart-pulse"></i> Vitals</a><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$queue): ?><div class="text-center text-muted py-5"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>No appointments for today.</div><?php endif; ?>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
