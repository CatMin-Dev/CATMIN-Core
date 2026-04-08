(function () {
    'use strict';

    function enhanceStatCards() {
        document.querySelectorAll('.cat-stat-card').forEach(function (card) {
            card.addEventListener('mouseenter', function () {
                card.style.transform = 'translateY(-2px)';
                card.style.transition = 'transform 160ms ease';
            });
            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
            });
        });
    }

    function revealSections() {
        document.querySelectorAll('.cat-page-content > section').forEach(function (section, index) {
            section.style.opacity = '0';
            section.style.transform = 'translateY(6px)';
            window.setTimeout(function () {
                section.style.transition = 'opacity 180ms ease, transform 180ms ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, Math.min(index * 40, 220));
        });
    }

    function init() {
        enhanceStatCards();
        revealSections();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
