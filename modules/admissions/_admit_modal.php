<?php /** Shared "Admit patient" modal (used by Wards & Beds and Admissions pages). */ ?>
<div class="modal fade" id="admitModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="admitForm" novalidate>
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold"><i class="bi bi-hospital text-primary me-2"></i>Admit patient</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12 position-relative"><label class="form-label">Patient <span class="text-danger">*</span></label>
            <input class="form-control" id="admPatientSearch" placeholder="Search by name, UHID, phone or NIC…" autocomplete="off">
            <input type="hidden" name="patient_id" id="admPatientId"><div class="list-group position-absolute w-100 shadow" id="admPatientList" style="z-index:5;display:none"></div>
            <div class="invalid-feedback" data-for="patient_id"></div><div class="form-text" id="admPatientPicked"></div></div>
        <div class="col-md-6"><label class="form-label">Admitting doctor <span class="text-danger">*</span></label><select class="form-select" name="doctor_id" id="admDoctor"></select><div class="invalid-feedback" data-for="doctor_id"></div></div>
        <div class="col-md-6"><label class="form-label">Bed <span class="text-danger">*</span></label><select class="form-select" name="bed_id" id="admBed"></select><div class="invalid-feedback" data-for="bed_id"></div></div>
        <div class="col-12"><label class="form-label">Reason for admission <span class="text-danger">*</span></label><input class="form-control" name="reason" maxlength="255"><div class="invalid-feedback" data-for="reason"></div></div>
        <div class="col-12"><label class="form-label">Provisional diagnosis</label><input class="form-control" name="diagnosis" maxlength="255"><div class="invalid-feedback" data-for="diagnosis"></div></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gradient"><i class="bi bi-check2-circle me-1"></i> Admit</button></div>
</form></div></div>
