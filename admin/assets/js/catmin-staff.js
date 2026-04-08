(function () {
    'use strict';

    function initBulk() {
        var master = document.querySelector('[data-bulk-master]');
        var bulkBar = document.querySelector('[data-bulk-bar]');
        if (!master || !bulkBar) {
            return;
        }

        var countNode = bulkBar.querySelector('[data-bulk-count]');

        function selectedItems() {
            return Array.prototype.slice.call(document.querySelectorAll('[data-bulk-item]:checked'));
        }

        function syncBar() {
            var selected = selectedItems();
            if (countNode) {
                countNode.textContent = selected.length + ' selection';
            }
            bulkBar.classList.toggle('d-none', selected.length === 0);

            var hiddenContainer = bulkBar.querySelector('[data-bulk-hidden]') || document.createElement('div');
            hiddenContainer.setAttribute('data-bulk-hidden', '1');
            hiddenContainer.innerHTML = '';
            selected.forEach(function (input) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'ids[]';
                hidden.value = input.value;
                hiddenContainer.appendChild(hidden);
            });
            bulkBar.appendChild(hiddenContainer);
        }

        master.addEventListener('change', function () {
            document.querySelectorAll('[data-bulk-item]:not(:disabled)').forEach(function (checkbox) {
                checkbox.checked = master.checked;
            });
            syncBar();
        });

        document.querySelectorAll('[data-bulk-item]').forEach(function (checkbox) {
            checkbox.addEventListener('change', syncBar);
        });

        syncBar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBulk);
    } else {
        initBulk();
    }
}());
