/* consult.js - OPD consultation screen */
(function () {
    'use strict';
    var C = window.CONSULT, esc = SMS.esc, form = document.getElementById('consultForm');
    var FREQ = { OD: 1, BD: 2, TDS: 3, QDS: 4, Mane: 1, Nocte: 1, PRN: 3, Stat: 1 };
    function bmi() { var w = parseFloat(form.weight.value), h = parseFloat(form.height.value), el = document.getElementById('bmi'); if (w > 0 && h > 0) { var b = w / Math.pow(h / 100, 2); el.innerHTML = 'BMI <strong>' + b.toFixed(1) + '</strong> · ' + (b < 18.5 ? 'underweight' : b < 25 ? 'normal' : b < 30 ? 'overweight' : 'obese'); } else el.textContent = ''; }
    form.weight.addEventListener('input', bmi); form.height.addEventListener('input', bmi); bmi();
    if (C.final) return;
    flatpickr('#fu', { dateFormat: 'Y-m-d', minDate: new Date().fp_incr(1), disableMobile: true });

    var list = document.getElementById('rxList'), empty = document.getElementById('rxEmpty'), n = 0;
    var allergens = (C.allergies || '').toLowerCase().split(/[,;\/]+/).map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 2; });
    function allergic(m) { var hay = (m.name + ' ' + (m.generic_name || '') + ' ' + (m.code || '')).toLowerCase(); return allergens.filter(function (a) { return hay.indexOf(a) >= 0; }); }
    function medOptions() { return '<option value="">— Select medicine —</option>' + C.meds.map(function (m) { return '<option value="' + m.id + '">' + esc(m.name + ' ' + (m.strength || '') + ' (' + m.form + ')') + '</option>'; }).join(''); }
    function addRow() {
        var id = ++n, row = document.createElement('div'); row.className = 'rx-row mb-2'; row.dataset.row = id;
        row.innerHTML = '<div class="d-flex gap-2 mb-2"><select class="form-select form-select-sm" data-f="medicine_id">' + medOptions() + '</select><button type="button" class="btn btn-sm btn-soft-danger" data-rm><i class="bi bi-x-lg"></i></button></div>' +
            '<div class="row g-2"><div class="col-4"><input class="form-control form-control-sm" data-f="dosage" placeholder="Dose (1 tab)" maxlength="40"></div>' +
            '<div class="col-4"><select class="form-select form-select-sm" data-f="frequency">' + Object.keys(FREQ).map(function (f) { return '<option>' + f + '</option>'; }).join('') + '</select></div>' +
            '<div class="col-2"><input class="form-control form-control-sm" data-f="duration_days" type="number" min="1" max="365" placeholder="Days" value="5"></div>' +
            '<div class="col-2"><input class="form-control form-control-sm" data-f="qty" type="number" min="1" placeholder="Qty"></div>' +
            '<div class="col-12"><input class="form-control form-control-sm" data-f="instructions" placeholder="Instructions (after food…)" maxlength="150"></div></div><div class="small text-danger mt-1 d-none" data-warn></div>';
        list.appendChild(row); empty.style.display = 'none';
    }
    function calc(row) {
        var m = C.meds.filter(function (x) { return String(x.id) === row.querySelector('[data-f=medicine_id]').value; })[0]; if (!m) return;
        var days = parseInt(row.querySelector('[data-f=duration_days]').value, 10) || 0, f = row.querySelector('[data-f=frequency]').value;
        var solid = ['Tablet', 'Capsule'].indexOf(m.form) >= 0, qty = row.querySelector('[data-f=qty]');
        if (!qty.dataset.manual) qty.value = solid ? (FREQ[f] * days || '') : 1;
        var w = row.querySelector('[data-warn]'), hit = allergic(m);
        if (hit.length) { w.textContent = '⚠ Patient is allergic to ' + hit.join(', ') + ' — check before prescribing.'; w.classList.remove('d-none'); } else w.classList.add('d-none');
    }
    document.getElementById('addRx').addEventListener('click', addRow);
    list.addEventListener('input', function (e) { var row = e.target.closest('[data-row]'); if (!row) return; if (e.target.dataset.f === 'qty') e.target.dataset.manual = '1'; calc(row); });
    list.addEventListener('change', function (e) { var row = e.target.closest('[data-row]'); if (row) calc(row); });
    list.addEventListener('click', function (e) { var b = e.target.closest('[data-rm]'); if (!b) return; b.closest('[data-row]').remove(); if (!list.children.length) empty.style.display = ''; });

    var tf = document.getElementById('testFilter');
    tf.addEventListener('input', function () { var q = tf.value.toLowerCase(); document.querySelectorAll('.test-row').forEach(function (r) { r.style.display = r.dataset.n.indexOf(q) >= 0 ? '' : 'none'; }); });

    function collect(action) {
        var o = { action: action, id: C.id, rx: [], tests: [] };
        ['bp_sys', 'bp_dia', 'pulse', 'temp', 'spo2', 'weight', 'height', 'symptoms', 'diagnosis', 'advice', 'follow_up_date'].forEach(function (k) { o[k] = form[k].value; });
        list.querySelectorAll('[data-row]').forEach(function (row) { var r = {}; row.querySelectorAll('[data-f]').forEach(function (i) { r[i.dataset.f] = i.value; }); o.rx.push(r); });
        form.querySelectorAll('[name="tests[]"]:checked').forEach(function (c) { o.tests.push(c.value); });
        return o;
    }
    function send(o, ok) { SMS.api(APP.base + '/opd/api', { method: 'POST', body: o }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) ok(r); }); }
    document.getElementById('btnDraft').addEventListener('click', function () { send(collect('draft'), function () {}); });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var o = collect('finish');
        if (!o.diagnosis.trim()) { form.diagnosis.classList.add('is-invalid'); SMS.toast('Enter a diagnosis.', 'warning'); form.diagnosis.focus(); return; }
        form.diagnosis.classList.remove('is-invalid');
        var warn = list.querySelectorAll('[data-warn]:not(.d-none)').length;
        SMS.confirmBox((warn ? '⚠ One or more medicines match the patient\'s recorded allergies. ' : '') + 'Complete this consultation? It cannot be edited afterwards.', 'Complete').then(function (ok) { if (!ok) return;
            send(o, function () { setTimeout(function () { location.reload(); }, 600); }); });
    });
})();
