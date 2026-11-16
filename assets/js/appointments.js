/* appointments.js - booking form (slots) + appointment list */
(function () {
    'use strict';
    var A = window.APPT, esc = SMS.esc;
    var API = APP.base + '/appointments/api';
    var filter = 'today';

    /* ---------------- list ---------------- */
    var table = $('#tbl').DataTable({
        ajax: { url: API + '?action=list', dataSrc: 'data' }, order: [[1, 'asc']], pageLength: 10,
        language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No appointments.' },
        columns: [
            { data: 'token_no', render: function (d, t, r) { return t === 'display' ? '<span class="token">' + d + '</span>' : d; } },
            { data: null, render: function (r, t) { if (t !== 'display') return r.appt_date + ' ' + r.appt_time; return '<div class="fw-semibold">' + SMS.fdate(r.appt_date) + '</div><small class="text-muted">' + r.appt_time.slice(0, 5) + ' · ' + esc(r.appt_no) + '</small>'; } },
            { data: 'patient_name', render: function (d, t, r) { return A.role === 'patient' ? '<span class="fw-semibold">' + esc(d) + '</span>' : '<a class="text-decoration-none fw-semibold" href="' + APP.base + '/patients/' + r.patient_id + '">' + esc(d) + '</a><div class="small text-muted">' + esc(r.uhid) + (r.reason ? ' · ' + esc(r.reason) : '') + '</div>'; } },
            { data: 'doctor_name', render: function (d, t, r) { return esc(d) + '<div class="small text-muted">' + esc(r.department) + (r.room_no ? ' · Room ' + esc(r.room_no) : '') + '</div>'; } },
            { data: 'status', render: function (d, t) { return t === 'display' ? SMS.badge(d) : d; } },
            { data: null, orderable: false, searchable: false, className: 'text-end text-nowrap', render: function (r) {
                var h = '', isToday = r.appt_date === A.today, s = r.status;
                if (A.role === 'doctor' || A.role === 'admin') { if (['Scheduled', 'Checked-in', 'In Consultation', 'Completed'].indexOf(s) >= 0 && (isToday || s === 'Completed' || r.consultation_id)) h += '<a class="btn btn-sm btn-gradient" href="' + APP.base + '/opd/consult/' + r.id + '"><i class="bi bi-stethoscope"></i> ' + (s === 'Completed' ? 'View' : 'Consult') + '</a> '; }
                if (['nurse', 'doctor', 'admin'].indexOf(A.role) >= 0 && isToday && ['Scheduled', 'Checked-in'].indexOf(s) >= 0) h += '<button class="btn btn-sm btn-soft" data-vitals="' + r.id + '" title="Vitals"><i class="bi bi-heart-pulse"></i></button> ';
                if (['admin', 'receptionist', 'nurse'].indexOf(A.role) >= 0 && s === 'Scheduled' && isToday) h += '<button class="btn btn-sm btn-soft-success" data-st="Checked-in" data-id="' + r.id + '"><i class="bi bi-box-arrow-in-right"></i> Check-in</button> ';
                if (['admin', 'receptionist', 'nurse', 'doctor'].indexOf(A.role) >= 0 && ['Scheduled', 'Checked-in'].indexOf(s) >= 0 && isToday) h += '<button class="btn btn-sm btn-soft-warning" data-st="No-show" data-id="' + r.id + '" title="Mark no-show"><i class="bi bi-person-x"></i></button> ';
                if (['Scheduled', 'Checked-in'].indexOf(s) >= 0 && A.role !== 'doctor' && A.role !== 'nurse') h += '<button class="btn btn-sm btn-soft-danger" data-st="Cancelled" data-id="' + r.id + '" title="Cancel"><i class="bi bi-x-lg"></i></button>';
                return h; } }
        ]
    });
    $.fn.dataTable.ext.search.push(function (s, d, i) {
        if (s.nTable.id !== 'tbl' || filter === 'all') return true; var r = table.row(i).data(); if (!r) return true;
        if (filter === 'today') return r.appt_date === A.today; if (filter === 'upcoming') return r.appt_date > A.today; return r.appt_date < A.today; });
    document.querySelectorAll('#apptFilters .chip').forEach(function (c) { c.addEventListener('click', function () {
        document.querySelectorAll('#apptFilters .chip').forEach(function (x) { x.classList.remove('active'); }); c.classList.add('active'); filter = c.dataset.f;
        table.order(filter === 'past' || filter === 'all' ? [[1, 'desc']] : [[1, 'asc']]); table.draw(); }); });

    $('#tbl').on('click', '[data-st]', function () {
        var id = this.dataset.id, st = this.dataset.st;
        var go = function () { SMS.api(API, { method: 'POST', body: { action: 'status', id: id, status: st } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); table.ajax.reload(null, false); }); };
        if (st === 'Cancelled' || st === 'No-show') SMS.confirmBox('Mark this appointment as ' + st + '?', st).then(function (ok) { if (ok) go(); }); else go();
    });
    var vm = document.getElementById('vitalsModal');
    if (vm) {
        var vmodal = new bootstrap.Modal(vm), vf = document.getElementById('vitalsForm');
        $('#tbl').on('click', '[data-vitals]', function () { vf.reset(); vf.elements.id.value = this.dataset.vitals; vmodal.show(); });
        vf.addEventListener('submit', function (e) { e.preventDefault(); var o = { action: 'vitals' }; new FormData(vf).forEach(function (v, k) { o[k] = v; });
            SMS.api(API, { method: 'POST', body: o }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) { vmodal.hide(); table.ajax.reload(null, false); } }); });
    }
    if (!A.canBook) return;

    /* ---------------- booking ---------------- */
    var form = document.getElementById('bookForm'), docSel = document.getElementById('doctor'), deptSel = document.getElementById('dept'),
        slotsEl = document.getElementById('slots'), timeInp = document.getElementById('apptTime'), info = document.getElementById('docInfo');
    function fillDoctors() {
        var d = deptSel.value, cur = docSel.value;
        docSel.innerHTML = '<option value="">— Select —</option>' + A.doctors.filter(function (x) { return !d || String(x.department_id) === d; }).map(function (x) { return '<option value="' + x.id + '">' + esc(x.full_name) + ' · ' + esc(x.specialization) + '</option>'; }).join('');
        docSel.value = cur; if (docSel.value !== cur) { docSel.value = ''; } showInfo();
    }
    function showInfo() {
        var d = A.doctors.filter(function (x) { return String(x.id) === docSel.value; })[0];
        info.innerHTML = d ? '<i class="bi bi-info-circle me-1"></i>' + esc(d.available_days.split(',').join(', ')) + ' · ' + d.start_time.slice(0, 5) + '–' + d.end_time.slice(0, 5) + ' · Fee ' + SMS.money(d.consultation_fee) + (d.room_no ? ' · Room ' + esc(d.room_no) : '') : '';
    }
    var fp = flatpickr('#apptDate', { dateFormat: 'Y-m-d', minDate: 'today', maxDate: new Date().fp_incr(A.maxDays), disableMobile: true, onChange: loadSlots,
        disable: [function (date) { var d = A.doctors.filter(function (x) { return String(x.id) === docSel.value; })[0]; if (!d) return false; return d.available_days.split(',').indexOf(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][date.getDay()]) < 0; }] });
    function loadSlots() {
        timeInp.value = ''; var d = docSel.value, dt = document.getElementById('apptDate').value;
        if (!d || !dt) { slotsEl.innerHTML = '<span class="text-muted small">Select a doctor and a date to see free slots.</span>'; return; }
        slotsEl.innerHTML = '<span class="text-muted small">Loading…</span>';
        SMS.api(API + '?action=slots&doctor_id=' + d + '&date=' + dt).then(function (r) {
            if (!r.data.length) { slotsEl.innerHTML = '<span class="text-muted small">No slots available on this day.</span>'; return; }
            slotsEl.innerHTML = r.data.map(function (s) { return '<button type="button" class="slot' + (s.free ? '' : ' full') + '" ' + (s.free ? '' : 'disabled') + ' data-t="' + s.time + '">' + s.time + '<small>' + (s.free ? 'free' : 'booked') + '</small></button>'; }).join('');
        });
    }
    deptSel.addEventListener('change', function () { fillDoctors(); fp.redraw(); loadSlots(); });
    docSel.addEventListener('change', function () { showInfo(); fp.redraw(); loadSlots(); });
    slotsEl.addEventListener('click', function (e) { var b = e.target.closest('.slot'); if (!b || b.disabled) return; slotsEl.querySelectorAll('.slot').forEach(function (x) { x.classList.remove('active'); }); b.classList.add('active'); timeInp.value = b.dataset.t; });
    fillDoctors();

    var ps = document.getElementById('pSearch');
    if (ps) {
        var pl = document.getElementById('pList'), pid = document.getElementById('pId'), pk = document.getElementById('pPicked');
        var run = SMS.debounce(function () { var q = ps.value.trim(); if (q.length < 2) { pl.style.display = 'none'; return; }
            SMS.api(APP.base + '/patients/api?action=search&q=' + encodeURIComponent(q)).then(function (r) {
                pl.innerHTML = (r.data || []).map(function (p) { return '<button type="button" class="list-group-item list-group-item-action" data-id="' + p.id + '" data-name="' + esc(p.full_name) + '" data-uhid="' + esc(p.uhid) + '"><strong>' + esc(p.full_name) + '</strong> <small class="text-muted">' + esc(p.uhid) + ' · ' + esc(p.phone) + '</small></button>'; }).join('') || '<div class="list-group-item text-muted">No patients found.</div>'; pl.style.display = 'block'; }); }, 250);
        ps.addEventListener('input', function () { pid.value = ''; pk.textContent = ''; run(); });
        pl.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (!b) return; pid.value = b.dataset.id; ps.value = b.dataset.name; pk.textContent = b.dataset.uhid; pl.style.display = 'none'; });
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault(); form.querySelectorAll('.is-invalid').forEach(function (x) { x.classList.remove('is-invalid'); });
        var o = { action: 'book' }; new FormData(form).forEach(function (v, k) { o[k] = v; });
        var bad = function (k, m) { var el = form.elements[k]; if (el && el.type !== 'hidden') el.classList.add('is-invalid'); var fb = form.querySelector('[data-for="' + k + '"]'); if (fb) { fb.textContent = m; fb.style.display = 'block'; } };
        var ok = true;
        if (ps && !o.patient_id) { bad('patient_id', 'Choose a patient from the list.'); ok = false; }
        if (!o.doctor_id) { bad('doctor_id', 'Choose a doctor.'); ok = false; }
        if (!o.appt_date) { bad('appt_date', 'Choose a date.'); ok = false; }
        if (!o.appt_time) { bad('appt_time', 'Pick a time slot.'); ok = false; }
        if (!ok) { SMS.toast('Please complete the booking details.', 'warning'); return; }
        SMS.api(API, { method: 'POST', body: o }).then(function (r) {
            if (r.success) { SMS.toast(r.message, 'success'); form.reset(); fp.clear(); if (pid) { pid.value = ''; pk.textContent = ''; } slotsEl.innerHTML = ''; filter = 'upcoming'; document.querySelectorAll('#apptFilters .chip').forEach(function (x) { x.classList.toggle('active', x.dataset.f === 'upcoming'); }); table.ajax.reload(); return; }
            if (r.errors) Object.keys(r.errors).forEach(function (k) { bad(k, r.errors[k]); }); SMS.toast(r.message, 'danger'); loadSlots(); });
    });
})();
