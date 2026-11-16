<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::guard(['admin', 'pharmacist', 'doctor'], json: true);
    $a = $_GET['action'] ?? 'list';
    if ($a === 'batches') { json_response(['success' => true, 'data' => MedicineModel::batches((int) ($_GET['id'] ?? 0))]); }
    if ($a === 'options') { json_response(['success' => true, 'data' => MedicineModel::options()]); }
}
CrudController::handle(MedicineModel::class, ['read' => ['admin', 'pharmacist', 'doctor'], 'write' => ['admin', 'pharmacist'], 'label' => 'Medicine']);
