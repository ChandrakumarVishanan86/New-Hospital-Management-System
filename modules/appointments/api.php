<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient'], json: true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $a = $_GET['action'] ?? 'list';
    if ($a === 'slots') { json_response(['success' => true, 'data' => AppointmentModel::slots((int) ($_GET['doctor_id'] ?? 0), (string) ($_GET['date'] ?? ''))]); }
    json_response(['success' => true, 'data' => AppointmentModel::list($user, $_GET)]);
}
require_csrf();
$in = $_POST ?: json_input();
$action = (string) ($in['action'] ?? '');
if ($action === 'book') {
    if (!in_array($user['role'], ['admin', 'receptionist', 'patient'], true)) { json_response(['success' => false, 'message' => 'You cannot book appointments.'], 403); }
    [$id, $errors] = AppointmentModel::book($in, $user);
    if ($errors) { json_response(['success' => false, 'message' => reset($errors), 'errors' => $errors], 422); }
    json_response(['success' => true, 'message' => 'Appointment booked.', 'id' => $id]);
}
if ($action === 'status') {
    $err = AppointmentModel::setStatus((int) ($in['id'] ?? 0), (string) ($in['status'] ?? ''), $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Appointment updated.']);
}
if ($action === 'vitals') {
    $err = ConsultationModel::saveVitals((int) ($in['id'] ?? 0), $in, $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Vitals saved.']);
}
json_response(['success' => false, 'message' => 'Unknown action.'], 400);
