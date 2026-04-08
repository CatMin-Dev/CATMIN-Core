(function () {
    'use strict';

    var root = document.documentElement;
    var toggle = document.querySelector('[data-cat-theme-toggle]');
    var themes = ['corporate', 'dark', 'light'];
    var KEY = 'catmin.odin.theme';

    function currentTheme() {
        var active = root.getAttribute('data-bs-theme');
        return themes.indexOf(active || '') >= 0 ? active : 'corporate';
    }

    function nextTheme(theme) {
        var index = themes.indexOf(theme);
        return themes[(index + 1) % themes.length];
    }

    function setTheme(theme) {
        if (window.odinColor && typeof window.odinColor.setTheme === 'function') {
            window.odinColor.setTheme(theme);
        } else {
            root.setAttribute('data-bs-theme', theme);
            try {
                localStorage.setItem(KEY, theme);
            } catch (e) {
                /* ignore */
            }
        }
    }

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        setTheme(nextTheme(currentTheme()));
    });
}());
