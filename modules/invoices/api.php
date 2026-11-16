<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'receptionist', 'accountant'], json: true);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pid = (int) ($_GET['patient_id'] ?? 0);
    json_response(['success' => true, 'data' => InvoiceModel::pendingScopes($pid)]);
}
require_csrf();
$in = $_POST ?: json_input();
$ok = fn(string $m, array $x = []) => json_response(['success' => true, 'message' => $m] + $x);
$fail = fn(string $m, int $c = 422) => json_response(['success' => false, 'message' => $m], $c);
try {
    switch ((string) ($in['action'] ?? '')) {
        case 'generate':
            $adm = (int) ($in['admission_id'] ?? 0) ?: null;
            [$id, $e] = InvoiceModel::generate((int) ($in['patient_id'] ?? 0), $adm, (float) ($in['discount'] ?? 0), (float) ($in['insurance'] ?? 0), $user);
            $e ? $fail($e) : $ok('Invoice generated.', ['id' => $id]);
        case 'payment':
            ($e = InvoiceModel::addPayment((int) ($in['id'] ?? 0), (float) ($in['amount'] ?? 0), (string) ($in['method'] ?? ''), trim((string) ($in['reference'] ?? '')) ?: null, $user)) ? $fail($e) : $ok('Payment recorded.');
        case 'cancel':
            ($e = InvoiceModel::cancel((int) ($in['id'] ?? 0), $user)) ? $fail($e) : $ok('Invoice cancelled. Charges released for re-billing.');
    }
} catch (Throwable $e) {
    error_log('[invoice] ' . $e->getMessage());
    $fail(APP_DEBUG ? $e->getMessage() : 'Server error. Please try again.', 500);
}
$fail('Unknown action.', 400);
