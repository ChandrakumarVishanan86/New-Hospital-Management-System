<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
CrudController::handle(LabTestModel::class, ['read' => ['admin','lab','doctor'], 'write' => ['admin','lab'], 'label' => 'Lab test']);
