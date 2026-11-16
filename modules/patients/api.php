<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'search') {
    Auth::guard(Auth::STAFF, json: true);
    json_response(['success' => true, 'data' => PatientModel::search(trim((string) ($_GET['q'] ?? '')))]);
}
CrudController::handle(PatientModel::class, ['read' => array_merge(Auth::STAFF, ['patient']), 'write' => ['admin', 'receptionist', 'nurse'], 'label' => 'Patient']);
