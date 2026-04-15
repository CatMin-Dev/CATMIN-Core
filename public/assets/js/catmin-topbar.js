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
        console.log('[CATMIN SEARCH] Found ' + forms.length + ' search form(s)');
        
        forms.forEach(function (form) {
            var input = form.querySelector('[data-cat-search-input]');
            var suggest = form.querySelector('[data-cat-search-suggest]');
            if (!input || !suggest) {
                console.log('[CATMIN SEARCH] Missing input or suggest elements');
                return;
            }

            var endpoint = (form.getAttribute('data-cat-search-endpoint') || '').trim();
            var resultsUrl = (form.getAttribute('data-cat-search-results-url') || form.getAttribute('action') || '').trim();

            console.log('[CATMIN SEARCH] Endpoint: ' + endpoint);
            console.log('[CATMIN SEARCH] Results URL: ' + resultsUrl);

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
                console.log('[CATMIN SEARCH] Error parsing items: ' + error.message);
                items = [];
            }
            
            console.log('[CATMIN SEARCH] Loaded ' + items.length + ' local items');

            var activeIndex = -1;
            var visible = [];
            var requestToken = 0;
            var debounceTimer = null;

            var escapeHtml = function (value) {
                return (value || '')
                    .toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

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

            var navigateWithQuery = function (query) {
                var q = (query || '').toString().trim();
                if (q === '') {
                    return;
                }
                if (!resultsUrl) {
                    return;
                }

                var hasQuery = resultsUrl.indexOf('?') !== -1;
                var separator = hasQuery ? '&' : '?';
                window.location.assign(resultsUrl + separator + 'q=' + encodeURIComponent(q));
            };

            var renderVisible = function () {
                if (visible.length === 0) {
                    suggest.innerHTML = '<div class="cat-search-empty">Aucun resultat</div>';
                    suggest.hidden = false;
                    activeIndex = -1;
                    console.log('[CATMIN SEARCH] Showed empty state');
                    return;
                }

                var html = visible.map(function (item, index) {
                    var label = escapeHtml(item.label || '');
                    var description = escapeHtml(item.description || '');
                    var type = escapeHtml(item.type || 'page');
                    var answer = escapeHtml(item.answer || '');
                    var url = escapeHtml(item.url || '');
                    var inputs = Array.isArray(item.inputs) ? item.inputs : [];
                    var inputTags = inputs.slice(0, 4).map(function (name) {
                        return '<span class="cat-search-chip">' + escapeHtml(name) + '</span>';
                    }).join('');

                    return ''
                        + '<button type="button" class="cat-search-item" data-cat-search-item data-index="' + index + '">'
                        + '<span class="cat-search-item-top">'
                        + '<span class="cat-search-item-label">' + label + '</span>'
                        + '<span class="cat-search-item-type">' + type + '</span>'
                        + '</span>'
                        + '<span class="cat-search-item-meta">' + description + '</span>'
                        + (answer ? ('<span class="cat-search-item-answer">' + answer + '</span>') : '')
                        + (inputTags ? ('<span class="cat-search-item-chips">' + inputTags + '</span>') : '')
                        + '<span class="cat-search-item-url">' + url + '</span>'
                        + '</button>';
                }).join('');

                suggest.innerHTML = html;
                openSuggest();
                selectIndex(0);
                console.log('[CATMIN SEARCH] Rendered ' + visible.length + ' items');
            };

            var renderFromLocal = function (query) {
                var q = normalizeText(query);
                if (q.length < 1) {
                    hideSuggest();
                    return;
                }

                console.log('[CATMIN SEARCH] Rendering from local (query="' + q + '", items=' + items.length + ')');

                visible = items.filter(function (item) {
                    var haystack = [
                        item.label || '',
                        item.description || '',
                        item.keywords || '',
                        item.answer || '',
                        (Array.isArray(item.inputs) ? item.inputs.join(' ') : '')
                    ].join(' ');
                    return normalizeText(haystack).indexOf(q) !== -1;
                }).slice(0, 10);

                console.log('[CATMIN SEARCH] Local filter found ' + visible.length + ' matches');
                renderVisible();
            };

            var fetchRemote = function (query) {
                if (!endpoint) {
                    console.log('[CATMIN SEARCH] No endpoint, using local fallback');
                    renderFromLocal(query);
                    return;
                }

                requestToken += 1;
                var token = requestToken;
                var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(query);

                console.log('[CATMIN SEARCH] Fetching: ' + url);

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).then(function (response) {
                    console.log('[CATMIN SEARCH] Response status: ' + response.status);
                    if (!response.ok) {
                        throw new Error('Search endpoint failed: ' + response.status);
                    }
                    return response.json();
                }).then(function (payload) {
                    if (token !== requestToken) {
                        console.log('[CATMIN SEARCH] Ignoring stale request');
                        return;
                    }
                    console.log('[CATMIN SEARCH] Got payload:', payload);
                    if (!payload || !Array.isArray(payload.items)) {
                        console.log('[CATMIN SEARCH] Invalid payload, using local fallback');
                        renderFromLocal(query);
                        return;
                    }
                    visible = payload.items.filter(function (item) {
                        return item && typeof item === 'object' && typeof item.url === 'string' && typeof item.label === 'string';
                    }).slice(0, 10);
                    console.log('[CATMIN SEARCH] Got ' + visible.length + ' results from API');
                    renderVisible();
                }).catch(function (err) {
                    console.log('[CATMIN SEARCH] Fetch error: ' + err.message);
                    if (token !== requestToken) {
                        return;
                    }
                    renderFromLocal(query);
                });
            };

            var render = function (query) {
                var q = normalizeText(query);
                console.log('[CATMIN SEARCH] render() called with query: "' + query + '"');
                if (q.length < 1) {
                    console.log('[CATMIN SEARCH] Query empty, hiding suggestions');
                    hideSuggest();
                    return;
                }

                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                }
                debounceTimer = window.setTimeout(function () {
                    console.log('[CATMIN SEARCH] Debounce timer fired');
                    fetchRemote(query);
                }, 120);
            };

            input.addEventListener('input', function () {
                console.log('[CATMIN SEARCH] input event fired');
                render(input.value || '');
            });

            input.addEventListener('focus', function () {
                console.log('[CATMIN SEARCH] focus event fired');
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
                    return;
                }

                event.preventDefault();
                navigateWithQuery(q);
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
