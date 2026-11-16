<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
CrudController::handle(WardModel::class, ['read' => ['admin', 'doctor', 'nurse', 'receptionist'], 'write' => ['admin'], 'label' => 'Ward']);
