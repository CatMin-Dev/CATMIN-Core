(function () {
    'use strict';

    function initTooltips() {
        if (typeof window.bootstrap === 'undefined' || !window.bootstrap || !window.bootstrap.Tooltip) {
            return;
        }

        var nodes = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        nodes.forEach(function (node) {
            if (!node.hasAttribute('data-cat-tooltip-ready')) {
                new window.bootstrap.Tooltip(node);
                node.setAttribute('data-cat-tooltip-ready', '1');
            }
        });
    }

    function submitForm(form) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var submitter = form.querySelector('[data-cat-submitter]');
        if (typeof form.requestSubmit === 'function') {
            if (submitter) {
                form.requestSubmit(submitter);
                return;
            }
            form.requestSubmit();
            return;
        }

        var tempSubmit = document.createElement('button');
        tempSubmit.type = 'submit';
        tempSubmit.hidden = true;
        form.appendChild(tempSubmit);
        tempSubmit.click();
        tempSubmit.remove();
    }

    function syncRestorePayload(container, form) {
        if (!(container instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        var modeField = container.querySelector('[data-cat-restore-mode]');
        var dryField = container.querySelector('[data-cat-restore-dry-run]');
        var modeValue = form.querySelector('[data-cat-restore-mode-value]');
        var dryValue = form.querySelector('[data-cat-restore-dry-value]');

        if (modeField && modeValue) {
            modeValue.value = (modeField.value || 'db_only').toString();
        }

        if (dryField && dryValue) {
            dryValue.value = dryField.checked ? '1' : '';
        }
    }

    function initActionButtons() {
        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            var actionButton = target.closest('[data-cat-submit-action]');
            if (!(actionButton instanceof HTMLElement)) {
                return;
            }

            var action = (actionButton.getAttribute('data-cat-submit-action') || '').trim();
            if (action === 'create') {
                var createForm = actionButton.closest('form');
                if (createForm instanceof HTMLFormElement) {
                    actionButton.disabled = true;
                    var loadingText = actionButton.getAttribute('data-loading-text');
                    if (loadingText) {
                        actionButton.dataset.originalText = actionButton.textContent || '';
                        actionButton.textContent = loadingText;
                    }
                    submitForm(createForm);
                }
                return;
            }

            var container = actionButton.closest('[data-cat-maintenance-actions]');
            if (!(container instanceof HTMLElement)) {
                return;
            }
            var restoreFormId = (container.getAttribute('data-cat-restore-form') || '').trim();
            var deleteFormId = (container.getAttribute('data-cat-delete-form') || '').trim();

            if (action === 'restore' && restoreFormId !== '') {
                var restoreForm = document.getElementById(restoreFormId);
                if (restoreForm instanceof HTMLFormElement) {
                    syncRestorePayload(container, restoreForm);
                    submitForm(restoreForm);
                }
                return;
            }

            if (action === 'delete' && deleteFormId !== '') {
                var deleteForm = document.getElementById(deleteFormId);
                if (deleteForm instanceof HTMLFormElement) {
                    submitForm(deleteForm);
                }
            }
        });
    }

    function initSubmitLoading() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            var buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(function (button) {
                button.disabled = true;
                var loadingText = button.getAttribute('data-loading-text');
                if (loadingText) {
                    button.dataset.originalText = button.textContent || '';
                    button.textContent = loadingText;
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTooltips();
        initActionButtons();
        initSubmitLoading();
    });
})();
