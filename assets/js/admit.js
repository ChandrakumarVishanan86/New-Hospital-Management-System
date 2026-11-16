/* admit.js - "Admit patient" modal shared by Wards & Beds and Admissions pages */
(function () {
    'use strict';
    var modalEl = document.getElementById('admitModal');
    if (!modalEl || !window.ADMIT) return;
    var modal = new bootstrap.Modal(modalEl), form = document.getElementById('admitForm'), A = window.ADMIT;
    var search = document.getElementById('admPatientSearch'), list = document.getElementById('admPatientList'), pid = document.getElementById('admPatientId'), picked = document.getElementById('admPatientPicked');

    function fill() {
        var d = document.getElementById('admDoctor');
        d.innerHTML = '<option value="">— Select doctor —</option>' + A.doctors.map(function (x) { return '<option value="' + x.id + '">' + SMS.esc(x.full_name) + ' · ' + SMS.esc(x.specialization) + '</option>'; }).join('');
        if (A.role === 'doctor' && A.myDoctor) { d.value = A.myDoctor; d.disabled = true; } else d.disabled = false;
        var b = document.getElementById('admBed'), ward = '';
        b.innerHTML = '<option value="">— Select bed —</option>' + A.free.map(function (x) {
            var g = ''; if (x.ward !== ward) { if (ward) g += '</optgroup>'; g += '<optgroup label="' + SMS.esc(x.ward) + ' (' + SMS.money(x.daily_rate) + '/day)">'; ward = x.ward; }
            return g + '<option value="' + x.id + '">' + SMS.esc(x.bed_no) + '</option>'; }).join('') + (ward ? '</optgroup>' : '');
    }
    function open(bed, patient) {
        form.reset(); form.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); }); fill();
        pid.value = ''; picked.textContent = ''; list.style.display = 'none';
        if (bed) document.getElementById('admBed').value = bed;
        if (patient) { pid.value = patient.id; search.value = patient.full_name; picked.textContent = patient.uhid; }
        modal.show();
    }
    window.openAdmit = open;
    var btn = document.getElementById('btnAdmit'); if (btn) btn.addEventListener('click', function () { open(null, window.ADMIT.patient || null); });
    document.querySelectorAll('[data-bed]').forEach(function (b) { b.addEventListener('click', function () { open(b.dataset.bed, null); }); });

    var run = SMS.debounce(function () {
        var q = search.value.trim(); if (q.length < 2) { list.style.display = 'none'; return; }
        SMS.api(APP.base + '/patients/api?action=search&q=' + encodeURIComponent(q)).then(function (r) {
            list.innerHTML = (r.data || []).map(function (p) { return '<button type="button" class="list-group-item list-group-item-action" data-id="' + p.id + '" data-name="' + SMS.esc(p.full_name) + '" data-uhid="' + SMS.esc(p.uhid) + '"><strong>' + SMS.esc(p.full_name) + '</strong> <small class="text-muted">' + SMS.esc(p.uhid) + ' · ' + SMS.esc(p.phone) + ' · ' + p.age + ' yrs</small></button>'; }).join('') || '<div class="list-group-item text-muted">No patients found.</div>';
            list.style.display = 'block'; });
    }, 250);
    search.addEventListener('input', function () { pid.value = ''; picked.textContent = ''; run(); });
    list.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (!b) return; pid.value = b.dataset.id; search.value = b.dataset.name; picked.textContent = b.dataset.uhid; list.style.display = 'none'; });

    form.addEventListener('submit', function (e) {
        e.preventDefault(); form.querySelectorAll('.is-invalid').forEach(function (x) { x.classList.remove('is-invalid'); });
        var o = { action: 'admit' }; new FormData(form).forEach(function (v, k) { o[k] = v; });
        if (A.role === 'doctor') o.doctor_id = A.myDoctor;
        SMS.api(APP.base + '/admissions/api', { method: 'POST', body: o }).then(function (r) {
            if (r.success) { SMS.toast(r.message, 'success'); location.href = APP.base + '/admissions/' + r.id; return; }
            if (r.errors) Object.keys(r.errors).forEach(function (k) { var el = form.elements[k]; if (el) el.classList.add('is-invalid'); var fb = form.querySelector('[data-for="' + k + '"]'); if (fb) { fb.textContent = r.errors[k]; fb.style.display = 'block'; } });
            SMS.toast(r.message || 'Please check the form.', 'danger'); });
    });
})();
