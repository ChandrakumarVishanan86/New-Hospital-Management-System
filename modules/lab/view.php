<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'lab', 'receptionist', 'patient']);
$o = LabModel::find((int) ($_GET['id'] ?? 0));
if (!$o || !LabModel::canAccess($user, $o)) { http_response_code(404); exit('Lab order not found.'); }
$items = LabModel::items((int) $o['id']);
$open = in_array($o['status'], ['Ordered', 'Sample Collected'], true);
$canEnter = $open && in_array($user['role'], ['admin', 'lab'], true);
$canCollect = $o['status'] === 'Ordered' && in_array($user['role'], ['admin', 'lab', 'nurse'], true);
$canCancel = $open && in_array($user['role'], ['admin', 'lab', 'doctor'], true);
$pageTitle = $o['order_no']; $activeNav = 'lab'; $libs = []; $pageScripts = ['assets/js/lab_view.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
function ref_label(array $i): string { if ($i['ref_min'] !== null || $i['ref_max'] !== null) { return rtrim(rtrim((string) $i['ref_min'], '0'), '.') . ' – ' . rtrim(rtrim((string) $i['ref_max'], '0'), '.') . ' ' . $i['unit']; } return (string) ($i['ref_text'] ?? '–'); }
?>
<div class="card-panel mb-4">
    <div class="d-flex flex-wrap align-items-start gap-3">
        <div><h4 class="fw-bold mb-1"><?= e($o['order_no']) ?> <?= status_badge($o['status']) ?> <?= $o['priority'] === 'Urgent' ? '<span class="badge badge-soft-danger">Urgent</span>' : '' ?></h4>
            <div class="text-muted"><?= e($o['patient_name']) ?> · <span class="plate"><?= e($o['uhid']) ?></span> · <?= e($o['gender']) ?>, <?= (int) $o['age'] ?> yrs<?= $o['doctor_name'] ? ' · Ordered by ' . e($o['doctor_name']) : '' ?> · <?= fdatetime($o['ordered_at']) ?></div>
            <?php if ($o['notes']): ?><div class="small mt-1"><i class="bi bi-chat-left-text"></i> <?= e($o['notes']) ?></div><?php endif; ?></div>
        <div class="ms-auto d-flex flex-wrap gap-2 no-print">
            <?php if ($o['status'] === 'Completed'): ?><a class="btn btn-soft" target="_blank" href="<?= url('lab/' . (int) $o['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf me-1"></i> Report PDF</a><?php endif; ?>
            <?php if ($canCollect): ?><button class="btn btn-soft" id="btnCollect"><i class="bi bi-eyedropper me-1"></i> Sample collected</button><?php endif; ?>
            <?php if ($canCancel): ?><button class="btn btn-soft-danger" id="btnCancel">Cancel order</button><?php endif; ?>
        </div>
    </div>
</div>
<div class="card-panel">
    <h6 class="fw-bold mb-3"><?= $canEnter ? 'Enter results' : 'Results' ?></h6>
    <div class="table-responsive"><table class="table align-middle" id="resTbl"><thead><tr><th>Test</th><th>Sample</th><th style="width:170px">Result</th><th>Reference</th><th>Flag</th><th>Remarks</th></tr></thead><tbody>
    <?php foreach ($items as $i): ?>
        <tr data-id="<?= (int) $i['id'] ?>"><td><strong><?= e($i['name']) ?></strong><div class="small text-muted"><?= e($i['category']) ?></div></td><td class="small"><?= e($i['sample_type']) ?></td>
        <?php if ($canEnter): ?>
            <td><div class="input-group input-group-sm"><input class="form-control" data-v value="<?= e((string) $i['result_value']) ?>" maxlength="60"><span class="input-group-text"><?= e($i['unit'] ?: '') ?></span></div></td>
            <td class="small"><?= e(ref_label($i)) ?></td><td><?= $i['result_flag'] ? '<span class="badge ' . ($i['result_flag'] === 'Normal' ? 'badge-soft-success' : 'badge-soft-danger') . '">' . e($i['result_flag']) . '</span>' : '–' ?></td>
            <td><input class="form-control form-control-sm" data-r value="<?= e((string) $i['remarks']) ?>" maxlength="120"></td>
        <?php else: ?>
            <td class="fw-bold <?= in_array($i['result_flag'], ['High', 'Low', 'Abnormal'], true) ? 'text-danger' : '' ?>"><?= $i['result_value'] !== null ? e($i['result_value']) . ' ' . e($i['unit'] ?: '') : '<span class="text-muted">Pending</span>' ?></td>
            <td class="small"><?= e(ref_label($i)) ?></td><td><?= $i['result_flag'] ? '<span class="badge ' . ($i['result_flag'] === 'Normal' ? 'badge-soft-success' : 'badge-soft-danger') . '">' . e($i['result_flag']) . '</span>' : '–' ?></td><td class="small"><?= e((string) $i['remarks']) ?></td>
        <?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php if ($canEnter): ?><div class="d-flex gap-2 justify-content-end no-print"><button class="btn btn-soft" id="btnSave">Save draft</button><button class="btn btn-gradient" id="btnComplete"><i class="bi bi-check2-circle me-1"></i> Complete &amp; release report</button></div><?php endif; ?>
</div>
<?php ob_start(); ?><script>window.LABV = { id: <?= (int) $o['id'] ?> };</script><?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
