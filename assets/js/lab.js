/* lab.js - place a lab order */
(function () {
    'use strict';
    var btn = document.getElementById('btnOrder'); if (!btn) return;
    var m = new bootstrap.Modal(document.getElementById('orderModal')), esc = SMS.esc;
    var s = document.getElementById('oSearch'), l = document.getElementById('oList');
    btn.addEventListener('click', function () { document.getElementById('orderForm').reset(); document.getElementById('oPid').value = ''; document.getElementById('oPicked').textContent = ''; m.show(); });
    var run = SMS.debounce(function () { var q = s.value.trim(); if (q.length < 2) { l.style.display = 'none'; return; }
        SMS.api(APP.base + '/patients/api?action=search&q=' + encodeURIComponent(q)).then(function (r) { l.innerHTML = (r.data || []).map(function (p) { return '<button type="button" class="list-group-item list-group-item-action" data-id="' + p.id + '" data-name="' + esc(p.full_name) + '" data-uhid="' + esc(p.uhid) + '"><strong>' + esc(p.full_name) + '</strong> <small class="text-muted">' + esc(p.uhid) + '</small></button>'; }).join('') || '<div class="list-group-item text-muted">No patients found.</div>'; l.style.display = 'block'; }); }, 250);
    s.addEventListener('input', function () { document.getElementById('oPid').value = ''; run(); });
    l.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (!b) return; document.getElementById('oPid').value = b.dataset.id; s.value = b.dataset.name; document.getElementById('oPicked').textContent = b.dataset.uhid; l.style.display = 'none'; });
    document.getElementById('orderForm').addEventListener('submit', function (e) { e.preventDefault();
        var pid = document.getElementById('oPid').value; if (!pid) { SMS.toast('Choose a registered patient.', 'warning'); return; }
        var tests = [].map.call(document.querySelectorAll('#oTests input:checked'), function (c) { return c.value; });
        if (!tests.length) { SMS.toast('Select at least one test.', 'warning'); return; }
        SMS.api(APP.base + '/lab/api', { method: 'POST', body: { action: 'order', patient_id: pid, tests: tests, priority: document.getElementById('oPrio').value, notes: document.getElementById('oNotes').value } })
            .then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.href = APP.base + '/lab/' + r.order_id; }, 600); });
    });
})();
