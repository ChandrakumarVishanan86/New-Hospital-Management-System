/* pharmacy.js - dispense modal + counter sale */
(function () {
    'use strict';
    var P = window.PHARM, esc = SMS.esc, API = APP.base + '/pharmacy/api';
    var rxModal = new bootstrap.Modal(document.getElementById('rxModal')), body = document.getElementById('rxBody'), curRx = null;

    function openRx(id) {
        body.innerHTML = '<div class="text-center text-muted py-5">Loading…</div>'; rxModal.show();
        SMS.api(API + '?id=' + id).then(function (r) {
            if (!r.success) { body.innerHTML = '<div class="text-danger">' + esc(r.message) + '</div>'; return; }
            var x = r.data; curRx = x;
            document.getElementById('rxTitle').textContent = x.rx_no + ' — ' + x.patient_name;
            var h = '<div class="d-flex flex-wrap gap-3 mb-3 small text-muted"><span><i class="bi bi-person"></i> ' + esc(x.patient_name) + ' (' + esc(x.uhid) + ')</span><span><i class="bi bi-stethoscope"></i> ' + esc(x.doctor_name) + '</span><span><i class="bi bi-clipboard2-pulse"></i> ' + esc(x.diagnosis || '') + '</span></div>';
            if (x.allergies) h += '<div class="allergy-alert mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> Allergies: ' + esc(x.allergies) + '</div>';
            h += '<table class="table align-middle"><thead><tr><th>Medicine</th><th>Directions</th><th class="text-center">Prescribed</th><th class="text-center">Given</th><th class="text-center">In stock</th><th style="width:120px">Dispense now</th></tr></thead><tbody>';
            x.items.forEach(function (i) { var left = i.qty - i.dispensed_qty, can = Math.min(left, i.stock);
                h += '<tr><td><strong>' + esc(i.name) + '</strong> <small class="text-muted">' + esc(i.strength || '') + '</small><div class="small text-muted">' + esc(i.form) + ' · ' + SMS.money(i.unit_price) + '</div></td>' +
                    '<td class="small">' + esc(i.dosage) + ' · ' + esc(i.frequency) + ' · ' + i.duration_days + 'd' + (i.instructions ? '<br>' + esc(i.instructions) : '') + '</td><td class="text-center">' + i.qty + '</td><td class="text-center">' + i.dispensed_qty + '</td>' +
                    '<td class="text-center ' + (i.stock < left ? 'text-danger fw-bold' : '') + '">' + i.stock + '</td>' +
                    '<td>' + (left > 0 ? '<input type="number" class="form-control form-control-sm" data-item="' + i.id + '" min="0" max="' + Math.min(left, i.stock) + '" value="' + can + '" ' + (i.stock === 0 ? 'disabled' : '') + '>' : '<span class="badge badge-soft-success">Done</span>') + '</td></tr>'; });
            body.innerHTML = h + '</tbody></table><div class="small text-muted">Stock is taken first-expiry-first-out from non-expired batches. Dispensed items are added to the patient\'s bill automatically.</div>';
        });
    }
    document.querySelectorAll('[data-rx]').forEach(function (b) { b.addEventListener('click', function () { openRx(b.dataset.rx); }); });
    if (P.openRx) openRx(P.openRx);
    document.getElementById('rxGo').addEventListener('click', function () {
        var lines = {}; body.querySelectorAll('[data-item]').forEach(function (i) { var q = parseInt(i.value, 10) || 0; if (q > 0) lines[i.dataset.item] = q; });
        SMS.api(API, { method: 'POST', body: { action: 'dispense', id: curRx.id, lines: lines } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.href = APP.base + '/pharmacy'; }, 700); });
    });
    document.getElementById('rxCancel').addEventListener('click', function () {
        SMS.confirmBox('Cancel prescription ' + curRx.rx_no + '? Only prescriptions with nothing dispensed can be cancelled.', 'Cancel prescription').then(function (ok) { if (!ok) return;
            SMS.api(API, { method: 'POST', body: { action: 'cancel', id: curRx.id } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.href = APP.base + '/pharmacy'; }, 600); }); });
    });

    /* ---------- counter sale ---------- */
    var saleModal = new bootstrap.Modal(document.getElementById('saleModal')), lines = document.getElementById('saleLines'), sf = document.getElementById('saleForm');
    function row() { var d = document.createElement('div'); d.className = 'd-flex gap-2 mb-2';
        d.innerHTML = '<select class="form-select form-select-sm" data-m><option value="">— Medicine —</option>' + P.meds.map(function (m) { return '<option value="' + m.id + '" data-p="' + m.unit_price + '">' + esc(m.name + ' ' + (m.strength || '')) + ' — ' + SMS.money(m.unit_price) + '</option>'; }).join('') + '</select>' +
            '<input type="number" min="1" class="form-control form-control-sm" style="width:90px" data-q value="1"><button type="button" class="btn btn-sm btn-soft-danger" data-x><i class="bi bi-x"></i></button>'; lines.appendChild(d); }
    function total() { var t = 0; lines.querySelectorAll('.d-flex').forEach(function (r) { var o = r.querySelector('[data-m]').selectedOptions[0]; t += (parseFloat(o.dataset.p) || 0) * (parseInt(r.querySelector('[data-q]').value, 10) || 0); }); document.getElementById('saleTotal').textContent = 'Total: ' + SMS.money(t); }
    document.getElementById('btnSale').addEventListener('click', function () { sf.reset(); lines.innerHTML = ''; row(); document.getElementById('salePid').value = ''; document.getElementById('salePicked').textContent = ''; total(); saleModal.show(); });
    document.getElementById('saleAdd').addEventListener('click', row);
    lines.addEventListener('input', total); lines.addEventListener('change', total); lines.addEventListener('click', function (e) { var x = e.target.closest('[data-x]'); if (x) { x.parentNode.remove(); total(); } });
    var ss = document.getElementById('saleSearch'), sl = document.getElementById('saleList');
    var run = SMS.debounce(function () { var q = ss.value.trim(); if (q.length < 2) { sl.style.display = 'none'; return; }
        SMS.api(APP.base + '/patients/api?action=search&q=' + encodeURIComponent(q)).then(function (r) { sl.innerHTML = (r.data || []).map(function (p) { return '<button type="button" class="list-group-item list-group-item-action" data-id="' + p.id + '" data-name="' + esc(p.full_name) + '" data-uhid="' + esc(p.uhid) + '"><strong>' + esc(p.full_name) + '</strong> <small class="text-muted">' + esc(p.uhid) + '</small></button>'; }).join('') || '<div class="list-group-item text-muted">No patients found.</div>'; sl.style.display = 'block'; }); }, 250);
    ss.addEventListener('input', function () { document.getElementById('salePid').value = ''; run(); });
    sl.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (!b) return; document.getElementById('salePid').value = b.dataset.id; ss.value = b.dataset.name; document.getElementById('salePicked').textContent = b.dataset.uhid; sl.style.display = 'none'; });
    sf.addEventListener('submit', function (e) { e.preventDefault();
        var pid = document.getElementById('salePid').value; if (!pid) { SMS.toast('Choose a registered patient.', 'warning'); return; }
        var map = {}; lines.querySelectorAll('.d-flex').forEach(function (r) { var m = r.querySelector('[data-m]').value, q = parseInt(r.querySelector('[data-q]').value, 10) || 0; if (m && q > 0) map[m] = (map[m] || 0) + q; });
        if (!Object.keys(map).length) { SMS.toast('Add at least one medicine.', 'warning'); return; }
        SMS.api(API, { method: 'POST', body: { action: 'sell', patient_id: pid, lines: map } }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) { saleModal.hide(); setTimeout(function () { location.reload(); }, 700); } }); });
})();
