(function () {
    'use strict';

    var root = document.documentElement;

    var setTheme = function (theme) {
        if (!theme || ['light', 'dark', 'corporate'].indexOf(theme) === -1) {
            return;
        }
        root.setAttribute('data-bs-theme', theme);
        try {
            localStorage.setItem('catmin.theme', theme);
        } catch (error) {
            void error;
        }
    };

    document.querySelectorAll('.js-theme-set').forEach(function (button) {
        button.addEventListener('click', function () {
            setTheme(button.getAttribute('data-theme') || 'corporate');
        });
    });

    document.querySelectorAll('.js-theme-cycle').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var current = root.getAttribute('data-bs-theme') || 'corporate';
            var order = ['light', 'dark', 'corporate'];
            var index = order.indexOf(current);
            setTheme(order[(index + 1) % order.length]);
        });
    });

    var fullscreenBtn = document.querySelector('[data-cat-fullscreen-toggle]');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenEnabled) {
                return;
            }

            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {
                    return null;
                });
                return;
            }

            document.exitFullscreen().catch(function () {
                return null;
            });
        });
    }

    var savedTheme = null;
    try {
        savedTheme = localStorage.getItem('catmin.theme');
    } catch (error) {
        savedTheme = null;
    }
    if (savedTheme && ['light', 'dark', 'corporate'].indexOf(savedTheme) !== -1) {
        setTheme(savedTheme);
    }
}());
