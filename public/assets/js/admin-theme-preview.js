(function () {
    'use strict';

    var root = document.documentElement;

    function resolveCssValue(computed, value, depth) {
        var maxDepth = typeof depth === 'number' ? depth : 0;
        if (!value || maxDepth > 8) {
            return value || '';
        }

        var trimmed = value.trim();
        var match = /^var\((--[^,\s)]+)\)$/.exec(trimmed);
        if (!match) {
            return trimmed;
        }

        var nested = computed.getPropertyValue(match[1]).trim();
        if (!nested) {
            return trimmed;
        }

        return resolveCssValue(computed, nested, maxDepth + 1);
    }

    function refreshThemeDebug() {
        if (!root.getAttribute('data-bs-theme')) {
            root.setAttribute('data-bs-theme', 'corporate');
        }

        var computed = getComputedStyle(root);
        var keys = [
            'bs-primary', 'bs-secondary', 'bs-success', 'bs-warning', 'bs-danger', 'bs-info',
            'bs-light', 'bs-dark', 'bs-body-bg', 'bs-body-color', 'bs-border-color'
        ];

        var label = document.getElementById('activeThemeLabel');
        if (label) {
            label.textContent = root.getAttribute('data-bs-theme') || 'corporate';
        }

        keys.forEach(function (key) {
            var node = document.getElementById('v-' + key);
            if (!node) {
                return;
            }

            var value = computed.getPropertyValue('--' + key).trim();
            node.textContent = value || '(vide)';

            var resolvedNode = document.getElementById('r-' + key);
            if (resolvedNode) {
                resolvedNode.textContent = resolveCssValue(computed, value) || '(vide)';
            }
        });
    }

    document.querySelectorAll('[data-theme-set]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.odinColor && typeof window.odinColor.setTheme === 'function') {
                window.odinColor.setTheme(button.getAttribute('data-theme-set'));
            } else {
                root.setAttribute('data-bs-theme', button.getAttribute('data-theme-set'));
            }

            window.setTimeout(refreshThemeDebug, 0);
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshThemeDebug);
    } else {
        refreshThemeDebug();
    }
})();
