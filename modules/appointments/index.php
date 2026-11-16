<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);
$role = $user['role'];
$canBook = in_array($role, ['admin', 'receptionist', 'patient'], true);
$doctors = DoctorModel::options();
$depts = DepartmentModel::options();
$pre = null;
if ($canBook && $role !== 'patient' && isset($_GET['patient'])) { $pre = PatientModel::find((int) $_GET['patient']); }
$pageTitle = $role === 'patient' ? 'Book Appointment' : 'Appointments'; $activeNav = 'appointments'; $libs = ['datatables', 'flatpickr']; $pageScripts = ['assets/js/appointments.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4">
<?php if ($canBook): ?>
    <div class="col-xl-4">
        <div class="card-panel">
            <h5 class="fw-bold mb-3"><i class="bi bi-calendar-plus text-primary me-2"></i>Book an appointment</h5>
            <form id="bookForm" novalidate>
                <?php if ($role !== 'patient'): ?>
                <div class="mb-3 position-relative"><label class="form-label">Patient <span class="text-danger">*</span></label>
                    <input class="form-control" id="pSearch" placeholder="Search name, UHID, phone…" autocomplete="off" value="<?= e($pre['full_name'] ?? '') ?>">
                    <input type="hidden" name="patient_id" id="pId" value="<?= (int) ($pre['id'] ?? 0) ?: '' ?>">
                    <div class="list-group position-absolute w-100 shadow" id="pList" style="z-index:5;display:none"></div>
                    <div class="invalid-feedback" data-for="patient_id"></div><div class="form-text" id="pPicked"><?= $pre ? e($pre['uhid']) : '' ?></div></div>
                <?php endif; ?>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Department</label><select class="form-select" id="dept"><option value="">All</option><?php foreach ($depts as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">Doctor <span class="text-danger">*</span></label><select class="form-select" name="doctor_id" id="doctor" required><option value="">— Select —</option></select><div class="invalid-feedback" data-for="doctor_id"></div></div>
                </div>
                <div id="docInfo" class="small text-muted mb-3"></div>
                <div class="mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input class="form-control" name="appt_date" id="apptDate" placeholder="Choose a date" required><div class="invalid-feedback" data-for="appt_date"></div></div>
                <div class="mb-3"><label class="form-label">Time slot <span class="text-danger">*</span></label>
                    <div id="slots" class="slot-grid"><span class="text-muted small">Select a doctor and a date to see free slots.</span></div>
                    <input type="hidden" name="appt_time" id="apptTime"><div class="invalid-feedback" data-for="appt_time"></div></div>
                <div class="mb-3"><label class="form-label">Reason for visit</label><input class="form-control" name="reason" maxlength="255" placeholder="Fever, check-up…"></div>
                <button class="btn btn-gradient w-100"><i class="bi bi-check2-circle me-1"></i> Confirm booking</button>
            </form>
        </div>
    </div>
<?php endif; ?>
    <div class="<?= $canBook ? 'col-xl-8' : 'col-12' ?>">
        <div class="card-panel">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <h5 class="fw-bold mb-0"><?= $role === 'patient' ? 'My appointments' : 'Appointments' ?></h5>
                <div class="chip-filters ms-md-auto" id="apptFilters"><button class="chip active" data-f="today">Today</button><button class="chip" data-f="upcoming">Upcoming</button><button class="chip" data-f="past">Past</button><button class="chip" data-f="all">All</button></div>
            </div>
            <div class="table-responsive"><table id="tbl" class="table table-hover align-middle w-100"><thead><tr><th>Token</th><th>Date &amp; time</th><th>Patient</th><th>Doctor</th><th>Status</th><th class="text-end">Actions</th></tr></thead></table></div>
        </div>
    </div>
</div>

<?php if (in_array($role, ['nurse', 'doctor', 'admin'], true)): ?>
<div class="modal fade" id="vitalsModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="vitalsForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="vitalsTitle">Record vitals</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><input type="hidden" name="id"><div class="row g-2">
        <div class="col-6"><label class="form-label small">BP systolic</label><input class="form-control" type="number" name="bp_sys" min="50" max="280"></div><div class="col-6"><label class="form-label small">BP diastolic</label><input class="form-control" type="number" name="bp_dia" min="30" max="180"></div>
        <div class="col-4"><label class="form-label small">Pulse</label><input class="form-control" type="number" name="pulse" min="20" max="260"></div><div class="col-4"><label class="form-label small">Temp °C</label><input class="form-control" type="number" step="0.1" name="temp" min="30" max="45"></div><div class="col-4"><label class="form-label small">SpO₂ %</label><input class="form-control" type="number" name="spo2" min="40" max="100"></div>
        <div class="col-6"><label class="form-label small">Weight kg</label><input class="form-control" type="number" step="0.1" name="weight" min="1" max="400"></div><div class="col-6"><label class="form-label small">Height cm</label><input class="form-control" type="number" step="0.1" name="height" min="30" max="250"></div></div></div>
    <div class="modal-footer border-0"><button class="btn btn-gradient w-100">Save vitals</button></div></form></div></div>
<?php endif; ?>
<?php ob_start(); ?>
<script>window.APPT = { role: <?= json_encode($role) ?>, doctors: <?= json_encode($doctors) ?>, maxDays: <?= BOOKING_DAYS_AHEAD ?>, today: <?= json_encode(date('Y-m-d')) ?>, canBook: <?= $canBook ? 'true' : 'false' ?> };</script>
<?php $pageConfig = ob_get_clean(); require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
