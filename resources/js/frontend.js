/*
 * CATMIN — Public Frontend JS
 * ===========================
 * Bootstrap is loaded from CDN in the layout <head>. This file adds
 * only site-specific vanilla behaviour — no admin code, no ES modules
 * requiring a bundler.
 */

/* ── Navbar collapse on outside click (Bootstrap handles toggle,
      this closes on overlay click for mobile) ──────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    /* Auto-dismiss flash alerts */
    document.querySelectorAll('.cf-flash.alert-dismissible[data-autohide]').forEach(function (el) {
        var delay = parseInt(el.dataset.autohide || '5000', 10);
        setTimeout(function () {
            var bsAlert = window.bootstrap && window.bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) { bsAlert.close(); }
        }, delay);
    });

    /* Smooth scroll for on-page anchor links */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* Back-to-top button (optional — rendered only if element present) */
    var backBtn = document.getElementById('cf-back-to-top');
    if (backBtn) {
        window.addEventListener('scroll', function () {
            backBtn.classList.toggle('d-none', window.scrollY < 300);
        });
        backBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

});
