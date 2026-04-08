(function () {
    'use strict';

    var root = document.documentElement;
    var themeOrder = ['light', 'dark', 'corporate'];

    var normalizeTheme = function (theme) {
        return (theme || '').toString().trim().toLowerCase();
    };

    var refreshThemeUi = function (theme) {
        var normalized = normalizeTheme(theme);
        if (themeOrder.indexOf(normalized) === -1) {
            normalized = 'corporate';
        }

        var activeButton = null;
        document.querySelectorAll('.js-theme-set').forEach(function (button) {
            var buttonTheme = normalizeTheme(button.getAttribute('data-theme'));
            var isActive = buttonTheme === normalized;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-current', isActive ? 'true' : 'false');
            if (isActive) {
                activeButton = button;
            }
        });

        var label = '';
        if (activeButton) {
            label = (activeButton.getAttribute('data-theme-label') || '').trim();
        }
        if (!label) {
            label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
        }

        document.querySelectorAll('.js-theme-label').forEach(function (node) {
            node.textContent = label;
        });
    };

    var setTheme = function (theme) {
        var normalized = normalizeTheme(theme);
        if (!normalized || themeOrder.indexOf(normalized) === -1) {
            return;
        }
        root.setAttribute('data-bs-theme', normalized);
        refreshThemeUi(normalized);
        try {
            localStorage.setItem('catmin.theme', normalized);
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
            var current = normalizeTheme(root.getAttribute('data-bs-theme')) || 'corporate';
            var index = themeOrder.indexOf(current);
            setTheme(themeOrder[(index + 1) % themeOrder.length]);
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
    if (savedTheme && themeOrder.indexOf(normalizeTheme(savedTheme)) !== -1) {
        setTheme(savedTheme);
    } else {
        refreshThemeUi(root.getAttribute('data-bs-theme') || 'corporate');
    }
}());
