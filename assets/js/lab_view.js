(function () {
    'use strict';
    var id = window.LABV.id, API = APP.base + '/lab/api';
    function send(body, goBack) { body.id = id; return SMS.api(API, { method: 'POST', body: body }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.reload(); }, 600); return r; }); }
    function collect() { var res = {}; document.querySelectorAll('#resTbl tbody tr').forEach(function (tr) { var v = tr.querySelector('[data-v]'); if (v) res[tr.dataset.id] = { value: v.value, remarks: tr.querySelector('[data-r]').value }; }); return res; }
    var b;
    if ((b = document.getElementById('btnSave'))) b.addEventListener('click', function () { send({ action: 'save', results: collect() }); });
    if ((b = document.getElementById('btnComplete'))) b.addEventListener('click', function () { SMS.confirmBox('Release the report? Results can no longer be edited.', 'Complete report').then(function (ok) { if (ok) send({ action: 'complete', results: collect() }); }); });
    if ((b = document.getElementById('btnCollect'))) b.addEventListener('click', function () { send({ action: 'collect' }); });
    if ((b = document.getElementById('btnCancel'))) b.addEventListener('click', function () { SMS.confirmBox('Cancel this lab order? Unbilled charges are removed.', 'Cancel order').then(function (ok) { if (ok) send({ action: 'cancel' }); }); });
})();
