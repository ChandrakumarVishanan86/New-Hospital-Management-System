<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin', 'accountant']);
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="invoices_' . $from . '_' . $to . '.csv"');
$o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
fputcsv($o, ['Invoice', 'Date', 'UHID', 'Patient', 'Type', 'Subtotal', 'Discount', 'Insurance', 'Total', 'Paid', 'Balance', 'Status']);
foreach (ReportModel::invoiceRows($from, $to) as $r) {
    // neutralise spreadsheet formula injection
    foreach ($r as $k => $v) { if (is_string($v) && $v !== '' && strpos('=+-@', $v[0]) !== false) { $r[$k] = "'" . $v; } }
    fputcsv($o, array_values($r));
}
fclose($o); Audit::log('export', 'report', null, "Invoices $from to $to");
