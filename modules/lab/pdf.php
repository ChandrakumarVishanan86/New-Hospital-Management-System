<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/PdfBase.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'lab', 'receptionist', 'patient']);
$o = LabModel::find((int) ($_GET['id'] ?? 0));
if (!$o || !LabModel::canAccess($user, $o) || $o['status'] !== 'Completed') { http_response_code(404); exit('Report not available.'); }
$items = LabModel::items((int) $o['id']);
$pdf = new HmsPdf('P', 'mm', 'A4'); $pdf->docTitle = 'Lab Report ' . $o['order_no'];
$pdf->AliasNbPages(); $pdf->SetMargins(14, 14, 14); $pdf->SetAutoPageBreak(true, 22); $pdf->AddPage();
$pdf->patientBox(['patient_name' => $o['patient_name'], 'uhid' => $o['uhid'], 'age' => $o['age'], 'gender' => $o['gender'], 'date' => fdate($o['completed_at'])]);
$pdf->SetFont('Helvetica', '', 9); $pdf->Cell(0, 6, pdf_text('Ordered by: ' . ($o['doctor_name'] ?: '-') . '   |   Ordered: ' . fdatetime($o['ordered_at']) . '   |   Reported: ' . fdatetime($o['completed_at'])), 0, 1); $pdf->Ln(2);
$pdf->tableHead([['Test', 62, 'L'], ['Result', 34, 'C'], ['Reference range', 48, 'C'], ['Flag', 38, 'C']]);
foreach ($items as $i) {
    $ref = ($i['ref_min'] !== null || $i['ref_max'] !== null) ? (float) $i['ref_min'] . ' - ' . (float) $i['ref_max'] . ' ' . $i['unit'] : (string) ($i['ref_text'] ?? '-');
    $abn = in_array($i['result_flag'], ['High', 'Low', 'Abnormal'], true);
    $pdf->Cell(62, 8, pdf_text(mb_strimwidth($i['name'], 0, 36, '..')), 1);
    $pdf->SetFont('Helvetica', 'B', 9); if ($abn) { $pdf->SetTextColor(185, 28, 28); }
    $pdf->Cell(34, 8, pdf_text($i['result_value'] . ' ' . $i['unit']), 1, 0, 'C'); $pdf->SetFont('Helvetica', '', 9); $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(48, 8, pdf_text($ref), 1, 0, 'C');
    if ($abn) { $pdf->SetTextColor(185, 28, 28); } $pdf->Cell(38, 8, pdf_text((string) $i['result_flag']), 1, 1, 'C'); $pdf->SetTextColor(30, 41, 59);
}
$pdf->Ln(4); $pdf->SetFont('Helvetica', 'I', 8.5); $pdf->MultiCell(0, 5, 'Abnormal values are marked in red. Please correlate clinically; results should be interpreted by a qualified doctor.');
$pdf->SetTitle('Lab Report ' . $o['order_no']); $pdf->SetAuthor(HOSPITAL_NAME);
$pdf->Output('I', $o['order_no'] . '.pdf');
