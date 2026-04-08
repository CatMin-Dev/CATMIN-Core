(function () {
    'use strict';

    var root = document.documentElement;
    var STORAGE_KEY = 'catmin.odin.theme';
    var UTILS_STYLE_ID = 'odin-color-runtime-utilities';
    var STEPS = [50, 100, 200, 300, 400, 500, 550, 600, 700, 800, 900, 950];

    var PRESETS = {
        light: {
            mode: 'light',
            semantic: {
                bodyBg: 'var(--palette-stone-50)',
                bodyColor: 'var(--palette-stone-900)',
                emphasis: 'var(--palette-stone-900)',
                secondary: 'var(--palette-stone-600)',
                tertiaryBg: 'var(--palette-stone-100)',
                border: 'var(--palette-stone-300)',
                link: 'var(--palette-rose-500)',
                linkHover: 'var(--palette-rose-600)'
            }
        },
        dark: {
            mode: 'dark',
            semantic: {
                bodyBg: 'var(--palette-stone-900)',
                bodyColor: 'var(--palette-stone-100)',
                emphasis: 'var(--palette-stone-50)',
                secondary: 'var(--palette-stone-300)',
                tertiaryBg: 'var(--palette-stone-800)',
                border: 'var(--palette-stone-700)',
                link: 'var(--palette-rose-300)',
                linkHover: 'var(--palette-rose-200)'
            }
        },
        corporate: {
            mode: 'light',
            semantic: {
                bodyBg: 'var(--palette-stone-50)',
                bodyColor: 'var(--palette-stone-900)',
                emphasis: 'var(--palette-stone-900)',
                secondary: 'var(--palette-stone-600)',
                tertiaryBg: 'var(--palette-stone-100)',
                border: 'var(--palette-stone-300)',
                link: 'var(--palette-rose-500)',
                linkHover: 'var(--palette-rose-600)'
            }
        }
    };

    var FIXED_THEME_PALETTES = {
        corporate: {
            rose: {50: '#fff0f3', 100: '#ffd4da', 200: '#ffa8b9', 300: '#ff7a96', 400: '#f65279', 500: '#e23561', 600: '#c2234d', 700: '#9a1b3d', 800: '#70142d', 900: '#4a0d1f'},
            stone: {50: '#fafaf9', 100: '#f5f5f4', 200: '#e7e5e4', 300: '#d6d3d1', 400: '#a8a29e', 500: '#78716c', 600: '#57534e', 700: '#44403c', 800: '#292524', 900: '#1c1917'}
        },
        light: {
            rose: {50: '#fff0f3', 100: '#ffd4da', 200: '#ffa8b9', 300: '#ff7a96', 400: '#f65279', 500: '#e23561', 600: '#c2234d', 700: '#9a1b3d', 800: '#70142d', 900: '#4a0d1f'},
            stone: {50: '#fafaf9', 100: '#f5f5f4', 200: '#e7e5e4', 300: '#d6d3d1', 400: '#a8a29e', 500: '#78716c'}
        },
        dark: {
            rose: {50: '#fff0f3', 100: '#ffd4da', 200: '#ffa8b9', 300: '#ff7a96', 400: '#f65279', 500: '#e23561', 600: '#c2234d', 700: '#9a1b3d', 800: '#70142d', 900: '#4a0d1f'},
            stone: {500: '#78716c', 600: '#57534e', 700: '#44403c', 800: '#292524', 900: '#1c1917'}
        }
    };

    function stepExpression(family, step) {
        if (step === 550) {
            return 'color-mix(in oklch, var(--palette-' + family + '-500) 92%, black)';
        }

        if (step === 950) {
            return 'color-mix(in oklch, var(--palette-' + family + '-500) 32%, black)';
        }

        return 'var(--palette-' + family + '-500)';
    }

    function applyRuntimeExtendedPalette() {
        ['rose', 'stone'].forEach(function (family) {
            root.style.setProperty('--palette-' + family + '-550', stepExpression(family, 550));
            root.style.setProperty('--palette-' + family + '-950', stepExpression(family, 950));
        });
    }

    function applyFixedThemePalette(theme) {
        var fixed = FIXED_THEME_PALETTES[theme];
        if (!fixed) {
            return;
        }

        Object.keys(fixed).forEach(function (family) {
            var scale = fixed[family];
            Object.keys(scale).forEach(function (step) {
                root.style.setProperty('--palette-' + family + '-' + step, scale[step]);
            });
        });
    }

    function ensureUtilities() {
        var style = document.getElementById(UTILS_STYLE_ID);
        if (!style) {
            style = document.createElement('style');
            style.id = UTILS_STYLE_ID;
            document.head.appendChild(style);
        }

        var css = [];
        ['rose', 'stone'].forEach(function (family) {
            STEPS.forEach(function (step) {
                var varName = 'var(--palette-' + family + '-' + step + ')';
                css.push('.bg-' + family + '-' + step + '{background-color:' + varName + '!important}');
                css.push('.text-' + family + '-' + step + '{color:' + varName + '!important}');
                css.push('.border-' + family + '-' + step + '{border-color:' + varName + '!important}');
            });
        });

        style.textContent = css.join('\n');
    }

    function applyPreset(name, persist) {
        var presetName = PRESETS[name] ? name : 'corporate';
        var preset = PRESETS[presetName];

        root.setAttribute('data-bs-theme', presetName);
        root.style.setProperty('color-scheme', preset.mode);

        applyFixedThemePalette(presetName);
        applyRuntimeExtendedPalette();
        ensureUtilities();

        if (persist !== false) {
            try {
                localStorage.setItem(STORAGE_KEY, presetName);
            } catch (e) {
                /* ignore storage errors */
            }
        }
    }

    function initialTheme() {
        var fromAttr = root.getAttribute('data-bs-theme');
        if (fromAttr && PRESETS[fromAttr]) {
            return fromAttr;
        }

        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved && PRESETS[saved]) {
                return saved;
            }
        } catch (e) {
            /* ignore storage errors */
        }

        return 'corporate';
    }

    window.odinColor = {
        presets: Object.keys(PRESETS),
        setTheme: function (name) { applyPreset(name, true); },
        getTheme: function () { return root.getAttribute('data-bs-theme') || 'corporate'; },
        setBaseColor: function () {
            /* intentionally disabled: palette source is fixed by project policy */
        },
        regenerate: function () {
            applyPreset(this.getTheme(), false);
        }
    };

    applyPreset(initialTheme(), false);
}());
