(function () {
    'use strict';

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

    document.querySelectorAll('[data-cat-toast]').forEach(function (toastEl) {
        var delay = parseInt(toastEl.getAttribute('data-cat-toast-delay') || '4200', 10);
        if (!Number.isFinite(delay) || delay < 1000) {
            delay = 4200;
        }

        var progressEl = toastEl.querySelector('[data-cat-toast-progress]');
        var animateProgress = function () {
            if (!progressEl) {
                return;
            }
            progressEl.style.transitionDuration = delay + 'ms';
            progressEl.style.width = '100%';
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    progressEl.style.width = '0%';
                });
            });
        };

        if (window.bootstrap && typeof window.bootstrap.Toast === 'function') {
            var toast = window.bootstrap.Toast.getOrCreateInstance(toastEl, {
                autohide: true,
                delay: delay
            });
            toastEl.addEventListener('shown.bs.toast', animateProgress);
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
            toast.show();
            return;
        }

        toastEl.classList.add('show');
        animateProgress();
        window.setTimeout(function () {
            toastEl.remove();
        }, delay);
    });

    document.querySelectorAll('[data-cat-sidebar-order]').forEach(function (listEl) {
        var input = document.querySelector('[data-cat-sidebar-order-input]');
        var dragging = null;

        var updateOrder = function () {
            if (!input) {
                return;
            }
            var keys = [];
            listEl.querySelectorAll('[data-cat-sidebar-item]').forEach(function (item) {
                keys.push(item.getAttribute('data-key') || '');
            });
            input.value = keys.filter(function (v) { return v !== ''; }).join(',');
        };

        listEl.querySelectorAll('[data-cat-sidebar-item]').forEach(function (item) {
            item.addEventListener('dragstart', function (event) {
                dragging = item;
                item.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.getAttribute('data-key') || '');
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('is-dragging');
                dragging = null;
                updateOrder();
            });
            item.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (!dragging || dragging === item) {
                    return;
                }
                var rect = item.getBoundingClientRect();
                var next = (event.clientY - rect.top) > rect.height / 2;
                listEl.insertBefore(dragging, next ? item.nextSibling : item);
            });
        });
    });
}());
