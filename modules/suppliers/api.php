<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
CrudController::handle(SupplierModel::class, ['read' => ['admin','pharmacist'], 'write' => ['admin','pharmacist'], 'label' => 'Supplier']);
