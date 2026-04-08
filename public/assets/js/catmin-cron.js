(function () {
    var form = document.querySelector('[data-cron-builder-form]');
    if (!form) {
        return;
    }

    var frequency = form.querySelector('[data-cron-frequency]');
    var interval = form.querySelector('[data-cron-interval]');
    var hourlyMinute = form.querySelector('[data-cron-hourly-minute]');
    var time = form.querySelector('[data-cron-time]');
    var weekday = form.querySelector('[data-cron-weekday]');
    var monthday = form.querySelector('[data-cron-monthday]');
    var expression = form.querySelector('[data-cron-expression]');
    var human = form.querySelector('[data-cron-human]');
    var modeNodes = form.querySelectorAll('[data-mode]');

    if (!frequency || !expression || !human) {
        return;
    }

    function pad2(v) {
        var n = parseInt(String(v || '0'), 10);
        if (Number.isNaN(n)) {
            n = 0;
        }
        n = Math.max(0, n);
        return String(n).padStart(2, '0');
    }

    function parseTime(input) {
        var raw = String(input || '02:00');
        var parts = raw.split(':');
        var h = parseInt(parts[0] || '2', 10);
        var m = parseInt(parts[1] || '0', 10);
        if (Number.isNaN(h)) {
            h = 2;
        }
        if (Number.isNaN(m)) {
            m = 0;
        }
        h = Math.max(0, Math.min(23, h));
        m = Math.max(0, Math.min(59, m));
        return { h: h, m: m };
    }

    function toHuman(expr) {
        var parts = String(expr || '').trim().split(/\s+/);
        if (parts.length !== 5) {
            return 'Format cron invalide';
        }
        var min = parts[0];
        var hour = parts[1];
        var dom = parts[2];
        var mon = parts[3];
        var dow = parts[4];

        if (/^\*\/\d+$/.test(min) && hour === '*' && dom === '*' && mon === '*' && dow === '*') {
            return 'Toutes les ' + min.replace('*/', '') + ' minute(s)';
        }
        if (/^\d+$/.test(min) && hour === '*' && dom === '*' && mon === '*' && dow === '*') {
            return 'Chaque heure à ' + pad2(min) + ' minute(s)';
        }
        if (/^\d+$/.test(min) && /^\d+$/.test(hour) && dom === '*' && mon === '*' && dow === '*') {
            return 'Tous les jours à ' + pad2(hour) + ':' + pad2(min);
        }
        if (/^\d+$/.test(min) && /^\d+$/.test(hour) && dom === '*' && mon === '*' && /^\d+$/.test(dow)) {
            var days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            var idx = parseInt(dow, 10);
            if (Number.isNaN(idx) || idx < 0 || idx > 6) {
                idx = 0;
            }
            return 'Chaque ' + days[idx] + ' à ' + pad2(hour) + ':' + pad2(min);
        }
        if (/^\d+$/.test(min) && /^\d+$/.test(hour) && /^\d+$/.test(dom) && mon === '*' && dow === '*') {
            return 'Le jour ' + dom + ' de chaque mois à ' + pad2(hour) + ':' + pad2(min);
        }

        return 'Expression avancée';
    }

    function toggleModeUI(mode) {
        modeNodes.forEach(function (node) {
            var modes = String(node.getAttribute('data-mode') || '').split(/\s+/);
            var visible = modes.indexOf(mode) !== -1;
            node.classList.toggle('d-none', !visible);
        });
    }

    function buildExpression() {
        var mode = String(frequency.value || 'daily');
        toggleModeUI(mode);

        if (mode === 'custom') {
            expression.readOnly = false;
            human.textContent = toHuman(expression.value);
            return;
        }

        expression.readOnly = true;
        var expr = '0 2 * * *';

        if (mode === 'interval') {
            var step = parseInt((interval && interval.value) || '5', 10);
            if (Number.isNaN(step) || step < 1) {
                step = 5;
            }
            expr = '*/' + step + ' * * * *';
        } else if (mode === 'hourly') {
            var hmin = parseInt((hourlyMinute && hourlyMinute.value) || '0', 10);
            if (Number.isNaN(hmin) || hmin < 0 || hmin > 59) {
                hmin = 0;
            }
            expr = String(hmin) + ' * * * *';
        } else if (mode === 'daily') {
            var dailyTime = parseTime(time && time.value);
            expr = String(dailyTime.m) + ' ' + String(dailyTime.h) + ' * * *';
        } else if (mode === 'weekly') {
            var weeklyTime = parseTime(time && time.value);
            var dow = parseInt((weekday && weekday.value) || '1', 10);
            if (Number.isNaN(dow) || dow < 0 || dow > 6) {
                dow = 1;
            }
            expr = String(weeklyTime.m) + ' ' + String(weeklyTime.h) + ' * * ' + String(dow);
        } else if (mode === 'monthly') {
            var monthlyTime = parseTime(time && time.value);
            var d = parseInt((monthday && monthday.value) || '1', 10);
            if (Number.isNaN(d) || d < 1 || d > 31) {
                d = 1;
            }
            expr = String(monthlyTime.m) + ' ' + String(monthlyTime.h) + ' ' + String(d) + ' * *';
        }

        expression.value = expr;
        human.textContent = toHuman(expr);
    }

    frequency.addEventListener('change', buildExpression);
    if (interval) {
        interval.addEventListener('change', buildExpression);
    }
    if (hourlyMinute) {
        hourlyMinute.addEventListener('input', buildExpression);
    }
    if (time) {
        time.addEventListener('input', buildExpression);
    }
    if (weekday) {
        weekday.addEventListener('change', buildExpression);
    }
    if (monthday) {
        monthday.addEventListener('change', buildExpression);
    }
    expression.addEventListener('input', function () {
        if (String(frequency.value) === 'custom') {
            human.textContent = toHuman(expression.value);
        }
    });

    buildExpression();
})();
