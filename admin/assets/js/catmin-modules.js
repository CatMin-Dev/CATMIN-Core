(function () {
    'use strict';

    function init() {
        document.querySelectorAll('form[action$="/modules/toggle"]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var button = form.querySelector('button[type="submit"]');
                if (!button) {
                    return;
                }
                button.disabled = true;
                button.classList.add('disabled');
                button.textContent = 'Traitement...';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
