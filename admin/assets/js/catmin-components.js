(function () {
    'use strict';

    document.querySelectorAll('[data-cat-search-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    });

    document.querySelectorAll('[data-cat-alert-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.closest('.alert');
            if (!target) {
                return;
            }
            target.classList.remove('show');
            window.setTimeout(function () {
                target.remove();
            }, 160);
        });
    });

    document.querySelectorAll('[data-cat-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-cat-toggle-password');
            if (!targetId) {
                return;
            }
            var input = document.getElementById(targetId);
            if (!input) {
                return;
            }
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
}());
