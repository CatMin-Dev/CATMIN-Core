(function () {
    'use strict';

    function getMessage(node) {
        if (!node || !node.getAttribute) {
            return '';
        }
        return (node.getAttribute('data-cat-confirm') || '').trim();
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var message = getMessage(form);
        if (message === '') {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('click', function (event) {
        var node = event.target;
        if (!(node instanceof Element)) {
            return;
        }

        var trigger = node.closest('[data-cat-confirm]');
        if (!trigger) {
            return;
        }

        if (trigger instanceof HTMLFormElement) {
            return;
        }

        var message = getMessage(trigger);
        if (message === '') {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);
}());
