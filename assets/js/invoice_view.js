(function () {
    'use strict';
    var id = window.INV.id, API = APP.base + '/invoices/api';
    function post(body) { body.id = id; return SMS.api(API, { method: 'POST', body: body }).then(function (r) { SMS.toast(r.message, r.success ? 'success' : 'danger'); if (r.success) setTimeout(function () { location.reload(); }, 600); }); }
    var b = document.getElementById('btnPay');
    if (b) { var m = new bootstrap.Modal(document.getElementById('payModal')); b.addEventListener('click', function () { m.show(); });
        document.getElementById('payForm').addEventListener('submit', function (e) { e.preventDefault(); post({ action: 'payment', amount: document.getElementById('payAmt').value, method: document.getElementById('payMethod').value, reference: document.getElementById('payRef').value }); }); }
    if ((b = document.getElementById('btnCancelInv'))) b.addEventListener('click', function () { SMS.confirmBox('Cancel this invoice? Its charges return to the unbilled list.', 'Cancel invoice').then(function (ok) { if (ok) post({ action: 'cancel' }); }); });
})();
