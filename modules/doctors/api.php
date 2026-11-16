<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin', 'receptionist', 'nurse', 'doctor', 'patient'], json: true);
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'options') { json_response(['success' => true, 'data' => DoctorModel::options(isset($_GET['department_id']) ? (int) $_GET['department_id'] : null)]); }
    if ($user['role'] !== 'admin') { json_response(['success' => false, 'message' => 'Not allowed.'], 403); }
    if ($action === 'get') {
        $d = DoctorModel::find((int) ($_GET['id'] ?? 0));
        $d ? json_response(['success' => true, 'data' => $d]) : json_response(['success' => false, 'message' => 'Doctor not found.'], 404);
    }
    if ($action === 'check') {
        $f = $_GET['field'] ?? ''; $v = trim((string) ($_GET['value'] ?? '')); $id = (int) ($_GET['exclude_id'] ?? 0);
        if (!in_array($f, ['username', 'email'], true) || $v === '') { json_response(['success' => true, 'exists' => false]); }
        $uid = $id ? (int) (DoctorModel::find($id)['user_id'] ?? 0) : 0;
        $q = db()->prepare("SELECT 1 FROM users WHERE $f = :v AND id <> :i"); $q->execute([':v' => strtolower($v), ':i' => $uid]);
        json_response(['success' => true, 'exists' => (bool) $q->fetchColumn()]);
    }
    json_response(['success' => true, 'data' => DoctorModel::all()]);
}

if ($user['role'] !== 'admin') { json_response(['success' => false, 'message' => 'Only administrators can manage doctors.'], 403); }
if (!csrf_verify()) { json_response(['success' => false, 'message' => 'Session expired. Reload the page.'], 403); }
$in = $_POST ?: json_input();
$action = $in['action'] ?? 'save';
if ($action === 'save') {
    $id = (int) ($in['id'] ?? 0);
    if ($id && !DoctorModel::find($id)) { json_response(['success' => false, 'message' => 'Doctor not found.'], 404); }
    [$d, $errors] = DoctorModel::validate($in, $id);
    if ($errors) { json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422); }
    $id = DoctorModel::save($d, $id, (string) ($in['password'] ?? ''));
    json_response(['success' => true, 'message' => 'Doctor saved.', 'id' => $id]);
}
if ($action === 'toggle') {
    $d = DoctorModel::find((int) ($in['id'] ?? 0));
    if (!$d) { json_response(['success' => false, 'message' => 'Doctor not found.'], 404); }
    $err = DoctorModel::setActive((int) $d['id'], !$d['is_active']);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => $d['is_active'] ? 'Doctor deactivated.' : 'Doctor activated.']);
}
json_response(['success' => false, 'message' => 'Unknown action.'], 400);
