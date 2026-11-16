/* invoices.js - generate invoice from unbilled charges */
(function () {
    'use strict';
    var el = document.getElementById('billModal'); if (!el) return;
    var m = new bootstrap.Modal(el), pid = 0, scope = document.getElementById('billScope'), esc = SMS.esc;
    document.querySelectorAll('[data-bill]').forEach(function (b) { b.addEventListener('click', function () {
        pid = b.dataset.bill; document.getElementById('billTitle').textContent = 'Invoice — ' + b.dataset.name;
        document.getElementById('billDisc').value = 0; document.getElementById('billIns').value = 0;
        document.getElementById('billIns').disabled = b.dataset.ins !== '1'; document.getElementById('billHint').textContent = b.dataset.ins === '1' ? '' : 'No insurance on file for this patient.';
        scope.innerHTML = '<option>Loading…</option>';
        SMS.api(APP.base + '/invoices/api?patient_id=' + pid).then(function (r) {
            scope.innerHTML = (r.data || []).map(function (s) { return '<option value="' + (s.admission_id || '') + '">' + esc(s.label) + ' — ' + s.line_count + ' line(s), ' + SMS.money(s.amount) + '</option>'; }).join('') || '<option value="">Nothing to bill</option>'; });
        m.show(); }); });
    document.getElementById('billForm').addEventListener('submit', function (e) { e.preventDefault();
        SMS.api(APP.base + '/invoices/api', { method: 'POST', body: { action: 'generate', patient_id: pid, admission_id: scope.value, discount: document.getElementById('billDisc').value || 0, insurance: document.getElementById('billIns').value || 0 } })
            .then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.href = APP.base + '/invoices/' + r.id; }, 500); }); });
})();
