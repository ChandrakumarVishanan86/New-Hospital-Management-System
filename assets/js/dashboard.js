/* dashboard.js - charts for the staff / mechanic dashboards */
(function () {
    'use strict';
    if (!window.Chart || !window.DASH) return;
    var D = window.DASH, P = SMS.palette;
    function el(id) { return document.getElementById(id); }
    var grid = { color: 'rgba(100,116,139,.12)' };

    if (el('chartRevenue') && D.revenue) new Chart(el('chartRevenue'), {
        type: 'bar',
        data: { labels: D.revenue.map(function (r) { return r.label; }), datasets: [{ data: D.revenue.map(function (r) { return +r.value; }), backgroundColor: P[0], borderRadius: 8, maxBarThickness: 42 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return SMS.money(c.parsed.y); } } } }, scales: { y: { beginAtZero: true, grid: grid }, x: { grid: { display: false } } } }
    });
    if (el('chartPipeline') && D.pipeline) new Chart(el('chartPipeline'), {
        type: 'doughnut',
        data: { labels: D.pipeline.map(function (r) { return r.label; }), datasets: [{ data: D.pipeline.map(function (r) { return +r.value; }), backgroundColor: P, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }] },
        options: { maintainAspectRatio: false, cutout: '64%', plugins: { legend: { position: 'bottom' } } }
    });
    if (el('chartServices') && D.services) new Chart(el('chartServices'), {
        type: 'bar',
        data: { labels: D.services.map(function (r) { return r.label; }), datasets: [{ data: D.services.map(function (r) { return +r.value; }), backgroundColor: P[1], borderRadius: 8, maxBarThickness: 22 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return SMS.money(c.parsed.x); } } } }, scales: { x: { beginAtZero: true, grid: grid }, y: { grid: { display: false } } } }
    });
    if (el('chartWeekly') && D.weekly) new Chart(el('chartWeekly'), {
        type: 'line',
        data: { labels: D.weekly.map(function (r) { return r.label; }), datasets: [{ data: D.weekly.map(function (r) { return +r.value; }), borderColor: P[0], backgroundColor: 'rgba(13,148,136,.15)', fill: true, tension: .35, pointRadius: 4 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid }, x: { grid: { display: false } } } }
    });
    if (el('chartDepts') && D.depts) new Chart(el('chartDepts'), {
        type: 'doughnut',
        data: { labels: D.depts.map(function (r) { return r.label; }), datasets: [{ data: D.depts.map(function (r) { return +r.value; }), backgroundColor: P, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
    });
})();
