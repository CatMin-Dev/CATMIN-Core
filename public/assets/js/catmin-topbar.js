(function () {
    'use strict';

    var normalizeText = function (value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    };

    var initTopbarSearch = function () {
        var forms = document.querySelectorAll('[data-cat-search-form]');
        forms.forEach(function (form) {
            var input = form.querySelector('[data-cat-search-input]');
            var suggest = form.querySelector('[data-cat-search-suggest]');
            if (!input || !suggest) {
                return;
            }

            var items = [];
            try {
                var raw = form.getAttribute('data-cat-search-items') || '[]';
                var parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    items = parsed.filter(function (item) {
                        return item && typeof item === 'object' && typeof item.url === 'string' && typeof item.label === 'string';
                    });
                }
            } catch (error) {
                items = [];
            }

            var activeIndex = -1;
            var visible = [];

            var hideSuggest = function () {
                suggest.hidden = true;
                suggest.innerHTML = '';
                activeIndex = -1;
                visible = [];
            };

            var openSuggest = function () {
                if (visible.length === 0) {
                    hideSuggest();
                    return;
                }
                suggest.hidden = false;
            };

            var selectIndex = function (index) {
                var rows = suggest.querySelectorAll('[data-cat-search-item]');
                rows.forEach(function (row, rowIndex) {
                    var active = rowIndex === index;
                    row.classList.toggle('is-active', active);
                    if (active) {
                        row.setAttribute('aria-selected', 'true');
                    } else {
                        row.removeAttribute('aria-selected');
                    }
                });
                activeIndex = index;
            };

            var navigateTo = function (item) {
                if (!item || typeof item.url !== 'string' || item.url.trim() === '') {
                    return;
                }
                window.location.assign(item.url);
            };

            var render = function (query) {
                var q = normalizeText(query);
                if (q.length < 1) {
                    hideSuggest();
                    return;
                }

                visible = items.filter(function (item) {
                    var haystack = [
                        item.label || '',
                        item.description || '',
                        item.keywords || ''
                    ].join(' ');
                    return normalizeText(haystack).indexOf(q) !== -1;
                }).slice(0, 8);

                if (visible.length === 0) {
                    suggest.innerHTML = '<div class="cat-search-empty">Aucun resultat</div>';
                    suggest.hidden = false;
                    activeIndex = -1;
                    return;
                }

                var html = visible.map(function (item, index) {
                    var label = (item.label || '').toString();
                    var description = (item.description || '').toString();
                    return ''
                        + '<button type="button" class="cat-search-item" data-cat-search-item data-index="' + index + '">'
                        + '<span class="cat-search-item-label">' + label.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>'
                        + '<span class="cat-search-item-meta">' + description.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>'
                        + '</button>';
                }).join('');

                suggest.innerHTML = html;
                openSuggest();
                selectIndex(0);
            };

            input.addEventListener('input', function () {
                render(input.value || '');
            });

            input.addEventListener('focus', function () {
                if ((input.value || '').trim() !== '') {
                    render(input.value || '');
                }
            });

            input.addEventListener('keydown', function (event) {
                if (suggest.hidden) {
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (visible.length === 0) {
                        return;
                    }
                    var next = activeIndex < 0 ? 0 : (activeIndex + 1) % visible.length;
                    selectIndex(next);
                    return;
                }
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (visible.length === 0) {
                        return;
                    }
                    var prev = activeIndex <= 0 ? visible.length - 1 : activeIndex - 1;
                    selectIndex(prev);
                    return;
                }
                if (event.key === 'Escape') {
                    event.preventDefault();
                    hideSuggest();
                    return;
                }
                if (event.key === 'Enter' && activeIndex >= 0 && visible[activeIndex]) {
                    event.preventDefault();
                    navigateTo(visible[activeIndex]);
                }
            });

            suggest.addEventListener('mousedown', function (event) {
                var button = event.target.closest('[data-cat-search-item]');
                if (!button) {
                    return;
                }
                event.preventDefault();
                var index = parseInt(button.getAttribute('data-index') || '-1', 10);
                if (Number.isFinite(index) && visible[index]) {
                    navigateTo(visible[index]);
                }
            });

            form.addEventListener('submit', function (event) {
                var q = (input.value || '').trim();
                if (q === '') {
                    event.preventDefault();
                    hideSuggest();
                    return;
                }
                if (activeIndex >= 0 && visible[activeIndex]) {
                    event.preventDefault();
                    navigateTo(visible[activeIndex]);
                    return;
                }
                if (visible[0]) {
                    event.preventDefault();
                    navigateTo(visible[0]);
                }
            });

            document.addEventListener('click', function (event) {
                if (!form.contains(event.target)) {
                    hideSuggest();
                }
            });
        });
    };

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

    initTopbarSearch();
}());
