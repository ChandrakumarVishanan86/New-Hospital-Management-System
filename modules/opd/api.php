<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor'], json: true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['success' => false, 'message' => 'Method not allowed.'], 405); }
require_csrf();
$in = $_POST ?: json_input();
$action = (string) ($in['action'] ?? '');
$cid = (int) ($in['id'] ?? 0);

if ($action === 'start') {
    [$id, $err] = ConsultationModel::start((int) ($in['appointment_id'] ?? 0), $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'id' => $id]);
}
if ($action === 'draft') {
    $err = ConsultationModel::saveDraft($cid, $in, $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Draft saved.']);
}
if ($action === 'finish') {
    [$res, $err] = ConsultationModel::finish($cid, $in, $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Consultation completed.'] + $res);
}
json_response(['success' => false, 'message' => 'Unknown action.'], 400);
