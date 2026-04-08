(function () {
    'use strict';

    var body = document.body;
    var toggle = document.querySelector('[data-cat-sidebar-toggle]');
    var overlay = document.getElementById('catGlobalOverlay');
    var compactKey = 'catmin.admin.sidebar.compact';
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-cat-nav-group]'));

    if (!toggle || !body) {
        return;
    }

    function isMobile() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function setOverlay(open) {
        if (!overlay) {
            return;
        }
        overlay.hidden = !open;
    }

    function setCompact(compact) {
        body.classList.toggle('cat-sidebar-compact', compact);
        body.classList.toggle('cat-sidebar-expanded', !compact);
    }

    function closeMobileSidebar() {
        body.classList.remove('cat-sidebar-open');
        setOverlay(false);
    }

    function closeCompactPanels() {
        groups.forEach(function (item) {
            item.classList.remove('is-compact-open');
        });
    }

    function toggleSidebar() {
        if (isMobile()) {
            var opened = !body.classList.contains('cat-sidebar-open');
            body.classList.toggle('cat-sidebar-open', opened);
            setOverlay(opened);
            return;
        }

        var compact = !body.classList.contains('cat-sidebar-compact');
        setCompact(compact);

        try {
            localStorage.setItem(compactKey, compact ? '1' : '0');
        } catch (e) {
            /* ignore */
        }
    }

    function openGroup(group, expanded) {
        group.classList.toggle('is-open', expanded);
        group.classList.toggle('is-compact-open', expanded);
        var trigger = group.querySelector('[data-cat-nav-trigger]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        if (expanded && body.classList.contains('cat-sidebar-compact') && !isMobile()) {
            animateCompactPanel(group);
        }
    }

    function animateCompactPanel(group) {
        var panel = group.querySelector('.cat-nav-compact-panel');
        if (!panel) {
            return;
        }

        panel.classList.remove('cat-compact-anim-in');
        panel.offsetWidth;
        panel.classList.add('cat-compact-anim-in');
    }

    groups.forEach(function (group) {
        var trigger = group.querySelector('[data-cat-nav-trigger]');
        var links = Array.prototype.slice.call(group.querySelectorAll('.cat-subnav-link'));
        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', function () {
            var isOpen = group.classList.contains('is-open');

            if (body.classList.contains('cat-sidebar-compact') && !isMobile()) {
                var isCompactOpen = group.classList.contains('is-compact-open');
                groups.forEach(function (item) {
                    if (item !== group) {
                        item.classList.remove('is-compact-open');
                    }
                });
                group.classList.toggle('is-compact-open', !isCompactOpen);
                return;
            }

            groups.forEach(function (item) {
                if (item !== group) {
                    openGroup(item, false);
                }
            });
            openGroup(group, !isOpen);
        });

        links.forEach(function (link) {
            link.addEventListener('click', function () {
                closeCompactPanels();
                closeMobileSidebar();
            });
        });
    });

    toggle.addEventListener('click', toggleSidebar);

    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeMobileSidebar();
        }
    });

    try {
        setCompact(localStorage.getItem(compactKey) === '1');
    } catch (e) {
        /* ignore */
    }

    closeCompactPanels();
}());
