(function () {
    'use strict';

    function togglePassword(button) {
        var target = button.getAttribute('data-password-toggle');
        if (!target) {
            return;
        }

        var input = document.querySelector(target);
        if (!input) {
            return;
        }

        var isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        button.textContent = isPassword ? 'Masquer' : 'Voir';
    }

    function updateStrength(meter) {
        var sourceSelector = meter.getAttribute('data-password-source');
        if (!sourceSelector) {
            return;
        }

        var source = document.querySelector(sourceSelector);
        var bar = meter.querySelector('[data-password-meter-bar]');
        var label = meter.querySelector('[data-password-meter-label]');
        if (!source || !bar || !label) {
            return;
        }

        var value = source.value || '';
        var score = 0;

        if (value.length >= 8) score += 25;
        if (value.length >= 12) score += 25;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 20;
        if (/\d/.test(value)) score += 15;
        if (/[^A-Za-z0-9]/.test(value)) score += 15;

        if (score > 100) {
            score = 100;
        }

        var tone = 'danger';
        var text = 'Force: faible';
        if (score >= 75) {
            tone = 'success';
            text = 'Force: forte';
        } else if (score >= 50) {
            tone = 'warning';
            text = 'Force: moyenne';
        }

        bar.style.width = String(score) + '%';
        bar.className = 'progress-bar bg-' + tone;
        label.textContent = text;
    }

    function startCountdown(node) {
        var remaining = Number.parseInt(node.getAttribute('data-lock-countdown') || '0', 10);
        if (!Number.isFinite(remaining) || remaining <= 0) {
            return;
        }

        function render() {
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            node.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }

        render();

        var timer = window.setInterval(function () {
            remaining -= 1;
            render();
            if (remaining <= 0) {
                window.clearInterval(timer);
            }
        }, 1000);
    }

    function init() {
        var autofocus = document.querySelector('[data-auth-autofocus]');
        if (autofocus && typeof autofocus.focus === 'function') {
            autofocus.focus();
        }

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                togglePassword(button);
            });
        });

        document.querySelectorAll('[data-password-meter]').forEach(function (meter) {
            var sourceSelector = meter.getAttribute('data-password-source');
            var source = sourceSelector ? document.querySelector(sourceSelector) : null;
            if (!source) {
                return;
            }
            source.addEventListener('input', function () {
                updateStrength(meter);
            });
            updateStrength(meter);
        });

        document.querySelectorAll('[data-lock-countdown]').forEach(function (node) {
            startCountdown(node);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
