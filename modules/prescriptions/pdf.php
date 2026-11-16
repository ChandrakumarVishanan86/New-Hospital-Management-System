<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/PdfBase.php';
$user = Auth::guard(['admin', 'doctor', 'pharmacist', 'patient']);
$rx = PharmacyModel::find((int) ($_GET['id'] ?? 0));
if (!$rx || !PharmacyModel::canAccess($user, $rx)) { http_response_code(404); exit('Prescription not found.'); }
$items = PharmacyModel::items((int) $rx['id']);

$pdf = new HmsPdf('P', 'mm', 'A4'); $pdf->docTitle = 'Prescription ' . $rx['rx_no'];
$pdf->AliasNbPages(); $pdf->SetMargins(14, 14, 14); $pdf->SetAutoPageBreak(true, 22); $pdf->AddPage();
$pdf->patientBox(['patient_name' => $rx['patient_name'], 'uhid' => $rx['uhid'], 'age' => $rx['age'], 'gender' => $rx['gender'], 'date' => fdate($rx['visit_at'])]);
if ($rx['allergies']) { $pdf->SetFont('Helvetica', 'B', 9); $pdf->SetTextColor(185, 28, 28); $pdf->Cell(0, 6, pdf_text('ALLERGIES: ' . $rx['allergies']), 0, 1); $pdf->SetTextColor(30, 41, 59); }
if ($rx['diagnosis']) { $pdf->SetFont('Helvetica', 'B', 9); $pdf->Cell(24, 6, 'Diagnosis:'); $pdf->SetFont('Helvetica', '', 9); $pdf->MultiCell(0, 6, pdf_text($rx['diagnosis'])); $pdf->Ln(2); }
$pdf->heading('Rx');
$cols = [['#', 8, 'C'], ['Medicine', 62, 'L'], ['Dose', 22, 'C'], ['Frequency', 24, 'C'], ['Days', 14, 'C'], ['Qty', 14, 'C'], ['Instructions', 38, 'L']];
$pdf->tableHead($cols);
foreach ($items as $n => $i) {
    $pdf->Cell(8, 8, (string) ($n + 1), 1, 0, 'C'); $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(62, 8, pdf_text(mb_strimwidth($i['name'] . ' ' . $i['strength'], 0, 38, '..')), 1); $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(22, 8, pdf_text($i['dosage']), 1, 0, 'C'); $pdf->Cell(24, 8, pdf_text($i['frequency']), 1, 0, 'C'); $pdf->Cell(14, 8, (string) $i['duration_days'], 1, 0, 'C');
    $pdf->Cell(14, 8, (string) $i['qty'], 1, 0, 'C'); $pdf->Cell(38, 8, pdf_text(mb_strimwidth((string) $i['instructions'], 0, 24, '..')), 1, 1);
}
if ($rx['advice']) { $pdf->Ln(4); $pdf->heading('Advice'); $pdf->SetFont('Helvetica', '', 9.5); $pdf->MultiCell(0, 5.5, pdf_text($rx['advice'])); }
$pdf->SetY(-48); $pdf->SetDrawColor(148, 163, 184); $pdf->Line(135, $pdf->GetY(), 196, $pdf->GetY());
$pdf->SetX(135); $pdf->SetFont('Helvetica', 'B', 10); $pdf->Cell(61, 6, pdf_text($rx['doctor_name']), 0, 1, 'C');
$pdf->SetX(135); $pdf->SetFont('Helvetica', '', 8.5); $pdf->Cell(61, 5, pdf_text($rx['specialization']), 0, 1, 'C');
$pdf->SetTitle('Prescription ' . $rx['rx_no']); $pdf->SetAuthor(HOSPITAL_NAME);
$pdf->Output('I', $rx['rx_no'] . '.pdf');
