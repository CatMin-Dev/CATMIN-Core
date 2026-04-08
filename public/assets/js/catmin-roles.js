(function () {
    'use strict';

    function syncAll(master, cells) {
        var total = cells.length;
        var checked = cells.filter(function (cell) { return cell.checked; }).length;

        master.checked = total > 0 && checked === total;
        master.indeterminate = checked > 0 && checked < total;
    }

    function syncRows(rowToggles, cells) {
        rowToggles.forEach(function (rowToggle) {
            var moduleName = rowToggle.value;
            var rowCells = cells.filter(function (cell) {
                return cell.getAttribute('data-module') === moduleName;
            });
            var total = rowCells.length;
            var checked = rowCells.filter(function (cell) { return cell.checked; }).length;

            rowToggle.checked = total > 0 && checked === total;
            rowToggle.indeterminate = checked > 0 && checked < total;
        });
    }

    function initMatrix() {
        var master = document.querySelector('[data-matrix-all]');
        var cells = Array.prototype.slice.call(document.querySelectorAll('[data-matrix-cell]'));
        var rowToggles = Array.prototype.slice.call(document.querySelectorAll('[data-matrix-row]'));
        if (!master || cells.length === 0) {
            return;
        }

        master.addEventListener('change', function () {
            cells.forEach(function (cell) {
                cell.checked = master.checked;
            });
            syncAll(master, cells);
            syncRows(rowToggles, cells);
        });

        rowToggles.forEach(function (rowToggle) {
            rowToggle.addEventListener('change', function () {
                var moduleName = rowToggle.value;
                var rowCells = cells.filter(function (cell) { return cell.getAttribute('data-module') === moduleName; });
                rowCells.forEach(function (cell) {
                    cell.checked = rowToggle.checked;
                });
                syncAll(master, cells);
                syncRows(rowToggles, cells);
            });
        });

        cells.forEach(function (cell) {
            cell.addEventListener('change', function () {
                syncAll(master, cells);
                syncRows(rowToggles, cells);
            });
        });

        syncAll(master, cells);
        syncRows(rowToggles, cells);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMatrix);
    } else {
        initMatrix();
    }
}());
