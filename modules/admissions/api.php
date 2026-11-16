<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient'], json: true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(['success' => true, 'data' => AdmissionModel::list($user, $_GET['status'] ?? null)]);
}
require_csrf();
$in = $_POST ?: json_input();
$action = (string) ($in['action'] ?? '');
if ($user['role'] === 'patient') { json_response(['success' => false, 'message' => 'Not allowed.'], 403); }
$id = (int) ($in['id'] ?? 0);
$ok = static fn(string $m, array $x = []) => json_response(['success' => true, 'message' => $m] + $x);
$fail = static fn(string $m, int $c = 422) => json_response(['success' => false, 'message' => $m], $c);

switch ($action) {
    case 'admit':
        [$newId, $errors] = AdmissionModel::admit($in, $user);
        if ($errors) { json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422); }
        $ok('Patient admitted.', ['id' => $newId]);
    case 'note':
        ($e = AdmissionModel::addNote($id, $in, $user)) ? $fail($e) : $ok('Note added.');
    case 'transfer':
        ($e = AdmissionModel::transfer($id, (int) ($in['bed_id'] ?? 0), $user)) ? $fail($e) : $ok('Patient transferred.');
    case 'discharge':
        ($e = AdmissionModel::discharge($id, (string) ($in['summary'] ?? ''), $user)) ? $fail($e) : $ok('Patient discharged. Charges are ready to be billed.');
    case 'order_lab':
        if (!in_array($user['role'], ['admin', 'doctor'], true)) { $fail('Only doctors can order tests.', 403); }
        $a = AdmissionModel::find($id);
        if (!$a || $a['status'] !== 'Admitted') { $fail('Admission not found or already discharged.'); }
        [$oid, $e] = LabModel::createOrder((int) $a['patient_id'], (int) $a['doctor_id'], (array) ($in['tests'] ?? []), (string) ($in['priority'] ?? 'Routine'), trim((string) ($in['notes'] ?? '')) ?: null, null, $id, $user);
        $e ? $fail($e) : $ok('Lab order placed.', ['order_id' => $oid]);
}
$fail('Unknown action.', 400);
