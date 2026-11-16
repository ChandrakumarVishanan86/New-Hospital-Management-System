<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'pharmacist'], json: true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rx = PharmacyModel::find((int) ($_GET['id'] ?? 0));
    if (!$rx) { json_response(['success' => false, 'message' => 'Prescription not found.'], 404); }
    json_response(['success' => true, 'data' => $rx + ['items' => PharmacyModel::items((int) $rx['id'])]]);
}
require_csrf();
$in = $_POST ?: json_input();
$action = (string) ($in['action'] ?? '');
if ($action === 'dispense') {
    [$total, $err] = PharmacyModel::dispense((int) ($in['id'] ?? 0), (array) ($in['lines'] ?? []), $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Dispensed. Charge of ' . money($total) . ' added to the patient\'s bill.', 'total' => $total]);
}
if ($action === 'cancel') {
    $err = PharmacyModel::cancelRx((int) ($in['id'] ?? 0), $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Prescription cancelled.']);
}
if ($action === 'sell') {
    [$total, $err] = PharmacyModel::sell((int) ($in['patient_id'] ?? 0), (array) ($in['lines'] ?? []), $user);
    $err ? json_response(['success' => false, 'message' => $err], 422) : json_response(['success' => true, 'message' => 'Sale recorded: ' . money($total) . ' added to the patient\'s bill.', 'total' => $total]);
}
json_response(['success' => false, 'message' => 'Unknown action.'], 400);
