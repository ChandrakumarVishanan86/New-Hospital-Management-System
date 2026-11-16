<?php
declare(strict_types=1);
function stat_tile(string $label, float|int $n, string $icon, string $cls, int $dec = 0, string $prefix = ''): void
{ ?>
<div class="col-sm-6 col-xl-3"><div class="stat-card <?= $cls ?> hover-lift"><div class="stat-icon"><i class="bi <?= $icon ?>"></i></div><div><div class="stat-num" data-count="<?= $n ?>" data-decimals="<?= $dec ?>"<?= $prefix ? ' data-prefix="' . e($prefix) . ' "' : '' ?>>0</div><div class="stat-label"><?= e($label) ?></div></div></div></div>
<?php }
function queue_list(array $q, bool $link = true): void
{
    foreach ($q as $a): ?>
    <div class="queue-item"><span class="token"><?= (int) $a['token_no'] ?></span>
        <div class="flex-fill"><div class="fw-semibold"><?= e($a['patient_name']) ?> <small class="text-muted"><?= e($a['uhid']) ?></small></div>
            <div class="small text-muted"><?= e($a['doctor_name']) ?> · <?= e($a['department']) ?> · <?= e(substr((string) $a['appt_time'], 0, 5)) ?></div></div>
        <?= status_badge($a['status']) ?></div>
    <?php endforeach;
    if (!$q): ?><div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-3 d-block mb-1"></i>No appointments today.</div><?php endif;
}
function dash_head(string $title, string $sub): void
{ ?><div class="mb-4"><h3 class="fw-bold mb-1"><?= e($title) ?></h3><div class="text-muted"><?= e($sub) ?></div></div><?php }
