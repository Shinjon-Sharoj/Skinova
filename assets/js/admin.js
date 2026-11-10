/**
 * Dashboard helpers (admin + consultant).
 *  - table filter: <input data-table-filter="#tableId">
 *  - select-all checkboxes: <input type="checkbox" data-check-all="#tableId">
 *  - auto-submit filters: <select data-autosubmit>
 */
(function () {
    'use strict';

    document.addEventListener('input', function (ev) {
        var input = ev.target.closest('[data-table-filter]');
        if (!input) { return; }
        var table = document.querySelector(input.getAttribute('data-table-filter'));
        if (!table) { return; }
        var q = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            tr.classList.toggle('d-none', q !== '' && tr.textContent.toLowerCase().indexOf(q) === -1);
        });
    });

    document.addEventListener('change', function (ev) {
        var t = ev.target;
        if (t.matches && t.matches('[data-check-all]')) {
            var table = document.querySelector(t.getAttribute('data-check-all'));
            if (table) {
                table.querySelectorAll('tbody input[type="checkbox"]').forEach(function (c) { c.checked = t.checked; });
            }
        }
        if (t.matches && t.matches('select[data-autosubmit]') && t.form) {
            t.form.submit();
        }
    });
})();