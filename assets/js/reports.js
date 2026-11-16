/* reports.js - date pickers + charts */
(function () {
    'use strict';
    flatpickr('#from', { dateFormat: 'Y-m-d', maxDate: 'today', disableMobile: true });
    flatpickr('#to', { dateFormat: 'Y-m-d', maxDate: 'today', disableMobile: true });
    if (!window.Chart || !window.REPORT) return;
    var R = window.REPORT, P = SMS.palette;
    var money = function (c) { return SMS.money(c.parsed.y !== undefined ? c.parsed.y : c.parsed); };
    new Chart(document.getElementById('chartDaily'), { type: 'bar',
        data: { labels: R.daily.map(function (d) { return SMS.fdate(d.label).slice(0, 6); }), datasets: [{ label: 'Collected', data: R.daily.map(function (d) { return +d.value; }), backgroundColor: P[0], borderRadius: 8, maxBarThickness: 34 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: money } } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(100,116,139,.12)' } }, x: { grid: { display: false } } } } });
    function donut(id, rows, fmt) { new Chart(document.getElementById(id), { type: 'doughnut',
        data: { labels: rows.map(function (m) { return m.label; }), datasets: [{ data: rows.map(function (m) { return +m.value; }), backgroundColor: P, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }] },
        options: { maintainAspectRatio: false, cutout: '64%', plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (c) { return c.label + ': ' + fmt(c.parsed); } } } } } }); }
    donut('chartMethods', R.methods, SMS.money); donut('chartSources', R.sources, SMS.money); donut('chartDepts', R.depts, function (v) { return v + ' visits'; });
})();
