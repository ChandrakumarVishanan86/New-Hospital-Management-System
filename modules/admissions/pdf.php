<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/PdfBase.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);
$a = AdmissionModel::find((int) ($_GET['id'] ?? 0));
if (!$a || !AdmissionModel::canAccess($user, $a) || $a['status'] !== 'Discharged') { http_response_code(404); exit('Discharge summary not available.'); }
$pdf = new HmsPdf('P', 'mm', 'A4'); $pdf->docTitle = 'Discharge Summary';
$pdf->AliasNbPages(); $pdf->SetMargins(14, 14, 14); $pdf->SetAutoPageBreak(true, 22); $pdf->AddPage();
$pdf->patientBox(['patient_name' => $a['patient_name'], 'uhid' => $a['uhid'], 'age' => $a['age'], 'gender' => $a['gender'], 'date' => fdate($a['discharged_at'])]);
$rows = [['Admission no', $a['adm_no']], ['Admitted', fdatetime($a['admitted_at'])], ['Discharged', fdatetime($a['discharged_at'])], ['Stay', $a['days'] . ' day(s)'], ['Ward / Bed', $a['ward'] . ' / ' . $a['bed_no']], ['Consultant', $a['doctor_name']]];
foreach ($rows as [$l, $v]) { $pdf->SetFont('Helvetica', '', 9); $pdf->SetTextColor(100, 116, 139); $pdf->Cell(34, 6.5, $l); $pdf->SetFont('Helvetica', 'B', 9.5); $pdf->SetTextColor(30, 41, 59); $pdf->Cell(0, 6.5, pdf_text((string) $v), 0, 1); }
$pdf->Ln(3);
foreach ([['Reason for admission', $a['reason']], ['Diagnosis', $a['diagnosis']], ['Discharge summary & advice', $a['discharge_summary']]] as [$h, $t]) {
    if (!$t) continue; $pdf->heading($h); $pdf->SetFont('Helvetica', '', 9.5); $pdf->MultiCell(0, 5.5, pdf_text((string) $t)); $pdf->Ln(2);
}
$pdf->SetTitle('Discharge ' . $a['adm_no']); $pdf->SetAuthor(HOSPITAL_NAME);
$pdf->Output('I', $a['adm_no'] . '-discharge.pdf');
