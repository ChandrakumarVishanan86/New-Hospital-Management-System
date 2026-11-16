<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/PdfBase.php';
$user = Auth::guard(['admin', 'receptionist', 'accountant', 'patient']);
$inv = InvoiceModel::find((int) ($_GET['id'] ?? 0));
if (!$inv || !InvoiceModel::canAccess($user, $inv)) { http_response_code(404); exit('Invoice not found.'); }
$items = InvoiceModel::items((int) $inv['id']); $pays = InvoiceModel::payments((int) $inv['id']);
$pdf = new HmsPdf('P', 'mm', 'A4'); $pdf->docTitle = 'Invoice ' . $inv['invoice_no'];
$pdf->AliasNbPages(); $pdf->SetMargins(14, 14, 14); $pdf->SetAutoPageBreak(true, 22); $pdf->AddPage();
$age = '';
$pdf->patientBox(['patient_name' => $inv['patient_name'], 'uhid' => $inv['uhid'], 'age' => '-', 'gender' => '-', 'date' => fdate($inv['issued_at'])]);
$pdf->SetFont('Helvetica', '', 9); $pdf->Cell(0, 6, pdf_text('Status: ' . $inv['status'] . '   |   Due: ' . fdate($inv['due_date']) . ($inv['adm_no'] ? '   |   Admission: ' . $inv['adm_no'] : '')), 0, 1); $pdf->Ln(1);
$pdf->tableHead([['Description', 90, 'L'], ['Qty', 18, 'C'], ['Rate', 34, 'R'], ['Amount', 40, 'R']]);
foreach ($items as $i) {
    $pdf->Cell(90, 7.5, pdf_text(mb_strimwidth($i['description'], 0, 56, '..')), 1); $pdf->Cell(18, 7.5, rtrim(rtrim((string) $i['qty'], '0'), '.'), 1, 0, 'C');
    $pdf->Cell(34, 7.5, number_format((float) $i['unit_price'], 2), 1, 0, 'R'); $pdf->Cell(40, 7.5, number_format((float) $i['amount'], 2), 1, 1, 'R');
}
$pdf->Ln(3);
$tot = [['Subtotal', $inv['subtotal']]];
if ((float) $inv['discount'] > 0) { $tot[] = ['Discount', -(float) $inv['discount']]; }
if ((float) $inv['insurance_covered'] > 0) { $tot[] = ['Insurance', -(float) $inv['insurance_covered']]; }
$tot[] = ['TOTAL', $inv['total']]; $tot[] = ['Paid', $inv['paid']]; $tot[] = ['BALANCE', $inv['status'] === 'Cancelled' ? 0 : $inv['balance']];
foreach ($tot as [$l, $v]) { $b = in_array($l, ['TOTAL', 'BALANCE'], true); $pdf->SetFont('Helvetica', $b ? 'B' : '', $b ? 10.5 : 9.5); $pdf->Cell(142, 6.5, $l, 0, 0, 'R'); $pdf->Cell(40, 6.5, CURRENCY . ' ' . number_format((float) $v, 2), 0, 1, 'R'); }
if ($pays) { $pdf->Ln(4); $pdf->heading('Payments received'); $pdf->SetFont('Helvetica', '', 9);
    foreach ($pays as $p) { $pdf->Cell(40, 6, fdate($p['paid_at']), 0); $pdf->Cell(30, 6, pdf_text($p['method']), 0); $pdf->Cell(40, 6, CURRENCY . ' ' . number_format((float) $p['amount'], 2), 0); $pdf->Cell(0, 6, pdf_text((string) $p['reference']), 0, 1); } }
$pdf->SetTitle('Invoice ' . $inv['invoice_no']); $pdf->SetAuthor(HOSPITAL_NAME);
$pdf->Output('I', $inv['invoice_no'] . '.pdf');
