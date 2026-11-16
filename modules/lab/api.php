<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'lab', 'receptionist'], json: true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['success' => false, 'message' => 'Method not allowed.'], 405); }
require_csrf();
$in = $_POST ?: json_input();
$id = (int) ($in['id'] ?? 0);
$ok = fn(string $m, array $x = []) => json_response(['success' => true, 'message' => $m] + $x);
$fail = fn(string $m, int $c = 422) => json_response(['success' => false, 'message' => $m], $c);
try {
    switch ((string) ($in['action'] ?? '')) {
        case 'order':
            if (!in_array($user['role'], ['admin', 'doctor'], true)) { $fail('Only doctors can order tests.', 403); }
            $did = $user['role'] === 'doctor' ? (int) $user['doctor_id'] : null;
            [$oid, $e] = LabModel::createOrder((int) ($in['patient_id'] ?? 0), $did, (array) ($in['tests'] ?? []), (string) ($in['priority'] ?? 'Routine'), trim((string) ($in['notes'] ?? '')) ?: null, null, null, $user);
            $e ? $fail($e) : $ok('Lab order placed.', ['order_id' => $oid]);
        case 'collect':
            ($e = LabModel::collect($id, $user)) ? $fail($e) : $ok('Sample marked as collected.');
        case 'save':
        case 'complete':
            ($e = LabModel::saveResults($id, (array) ($in['results'] ?? []), $in['action'] === 'complete', $user)) ? $fail($e) : $ok($in['action'] === 'complete' ? 'Report completed.' : 'Results saved.');
        case 'cancel':
            ($e = LabModel::cancel($id, $user)) ? $fail($e) : $ok('Lab order cancelled.');
    }
} catch (Throwable $e) {
    error_log('[lab] ' . $e->getMessage());
    $fail(APP_DEBUG ? $e->getMessage() : 'Server error. Please try again.', 500);
}
$fail('Unknown action.', 400);
