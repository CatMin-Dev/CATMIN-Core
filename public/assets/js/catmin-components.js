(function () {
    'use strict';

    function i18nPassword() {
        var lang = ((document.documentElement && document.documentElement.lang) || 'fr').toLowerCase();
        var fr = lang.indexOf('fr') === 0;
        return {
            minLength: fr ? 'Minimum 12 caracteres' : 'Minimum 12 characters',
            uppercase: fr ? 'Au moins 1 majuscule' : 'At least 1 uppercase',
            digit: fr ? 'Au moins 1 chiffre' : 'At least 1 digit',
            special: fr ? 'Au moins 1 caractere special' : 'At least 1 special character',
            score: fr ? 'Robustesse' : 'Strength',
            weak: fr ? 'Faible' : 'Weak',
            medium: fr ? 'Moyenne' : 'Medium',
            strong: fr ? 'Forte' : 'Strong',
            blocked: fr ? 'Mot de passe trop faible (< 50%).' : 'Password too weak (< 50%).',
            mismatch: fr ? 'La confirmation ne correspond pas.' : 'Confirmation does not match.',
            matchOk: fr ? 'Confirmation identique.' : 'Confirmation matches.',
            showPasswords: fr ? 'Voir les mots de passe' : 'Show passwords',
            hidePasswords: fr ? 'Masquer les mots de passe' : 'Hide passwords'
        };
    }

    function passwordScore(value) {
        var pwd = (value || '').toString();
        var score = 0;
        var checks = {
            minLength: pwd.length >= 12,
            uppercase: /[A-Z]/.test(pwd),
            digit: /\d/.test(pwd),
            special: /[^A-Za-z0-9]/.test(pwd)
        };

        if (checks.minLength) {
            score += 40;
        } else if (pwd.length > 0) {
            score += Math.min(40, Math.floor((pwd.length / 12) * 40));
        }
        if (checks.uppercase) score += 20;
        if (checks.digit) score += 20;
        if (checks.special) score += 20;

        return {
            score: Math.max(0, Math.min(100, score)),
            checks: checks
        };
    }

    function ensurePasswordHelp(input) {
        var next = input.nextElementSibling;
        if (next && next.classList && next.classList.contains('cat-password-policy-help')) {
            return next;
        }

        var help = document.createElement('div');
        help.className = 'cat-password-policy-help form-text small';
        help.innerHTML = '<div class="cat-password-policy-rules" data-cat-password-rules></div>';
        input.insertAdjacentElement('afterend', help);
        return help;
    }

    function findPasswordPolicyFields() {
        return Array.prototype.slice.call(document.querySelectorAll('input[type="password"]')).filter(function (input) {
            if (input.disabled || input.readOnly) {
                return false;
            }

            var autocomplete = (input.getAttribute('autocomplete') || '').toLowerCase();
            var name = (input.getAttribute('name') || '').toLowerCase();
            if (autocomplete === 'current-password') {
                return false;
            }
            if (name.indexOf('confirm') !== -1 || name.indexOf('confirmation') !== -1) {
                return false;
            }

            if (autocomplete === 'new-password') {
                return true;
            }

            var minlength = parseInt(input.getAttribute('minlength') || '0', 10);
            if (Number.isFinite(minlength) && minlength >= 12) {
                return true;
            }

            return false;
        });
    }

    function initPasswordPolicy() {
        var labels = i18nPassword();
        var primaryFields = findPasswordPolicyFields();
        if (primaryFields.length === 0) {
            return;
        }

        var allForms = new Set();

        var computeTone = function (score) {
            if (score < 50) return 'weak';
            if (score < 75) return 'medium';
            return 'strong';
        };

        var getPairKey = function (primaryInput, confirmInput) {
            return (primaryInput.id || primaryInput.name || 'password') + '::' + (confirmInput.id || confirmInput.name || 'confirm');
        };

        var setInputTone = function (input, tone) {
            input.classList.remove('cat-password-weak', 'cat-password-medium', 'cat-password-strong');
            input.classList.add('cat-password-' + tone);
        };

        var ensurePrimaryUi = function (input) {
            if (!input || input.dataset.catPasswordPrimaryUi === '1') {
                return;
            }

            var group = input.closest('.input-group');
            if (!group) {
                group = document.createElement('div');
                group.className = 'input-group cat-password-group';
                input.parentNode.insertBefore(group, input);
                group.appendChild(input);
            } else if (!group.classList.contains('cat-password-group')) {
                group.classList.add('cat-password-group');
            }

            var score = group.querySelector('[data-cat-password-inline-score]');
            if (!score) {
                score = document.createElement('input');
                score.type = 'text';
                score.readOnly = true;
                score.tabIndex = -1;
                score.className = 'form-control cat-password-inline-score';
                score.setAttribute('data-cat-password-inline-score', '1');
                score.value = '0%';
                score.setAttribute('aria-label', labels.score);
                group.appendChild(score);
            }

            var legacyScore = group.querySelector('span.cat-password-inline-score');
            if (legacyScore) {
                legacyScore.remove();
            }

            var eye = group.querySelector('[data-cat-password-eye]');
            if (!eye) {
                eye = document.createElement('button');
                eye.type = 'button';
                eye.className = 'btn btn-outline-secondary cat-password-eye';
                eye.setAttribute('data-cat-password-eye', '1');
                eye.setAttribute('aria-label', labels.showPasswords);
                eye.innerHTML = '<i class="bi bi-eye"></i>';
                eye.addEventListener('click', function () {
                    var reveal = input.type === 'password';
                    input.type = reveal ? 'text' : 'password';
                    eye.setAttribute('aria-label', reveal ? labels.hidePasswords : labels.showPasswords);
                    eye.innerHTML = reveal ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                });
                group.appendChild(eye);
            }

            input.dataset.catPasswordPrimaryUi = '1';
        };

        var ensureCompareNote = function (primaryInput, confirmInput) {
            var pairKey = getPairKey(primaryInput, confirmInput);
            var parent = confirmInput.parentElement || confirmInput.form || document.body;
            var note = parent.querySelector('[data-cat-password-compare-note="' + pairKey + '"]');
            if (!note) {
                note = document.createElement('div');
                note.className = 'small mt-1 cat-password-compare-note';
                note.setAttribute('data-cat-password-compare-note', pairKey);
                confirmInput.insertAdjacentElement('afterend', note);
            }
            return note;
        };

        var renderRules = function (input, result) {
            var lines = [];
            lines.push((result.checks.minLength ? '✓ ' : '✗ ') + labels.minLength);
            lines.push((result.checks.uppercase ? '✓ ' : '✗ ') + labels.uppercase);
            lines.push((result.checks.digit ? '✓ ' : '✗ ') + labels.digit);
            lines.push((result.checks.special ? '✓ ' : '✗ ') + labels.special);

            var form = input.form || document;
            var panel = form.querySelector('[data-cat-password-rules-panel]');
            if (panel) {
                panel.innerHTML = lines.map(function (line) {
                    var ok = line.indexOf('✓') === 0;
                    return '<li class="' + (ok ? 'text-success' : 'text-danger') + '">' + line + '</li>';
                }).join('');
                return;
            }

            var help = ensurePasswordHelp(input);
            var rulesText = help.querySelector('[data-cat-password-rules]');
            if (rulesText) {
                rulesText.innerHTML = lines.join('<br>');
            }
        };

        var updateCompareState = function (primaryInput, confirmInput) {
            if (!primaryInput || !confirmInput) {
                return true;
            }

            var note = ensureCompareNote(primaryInput, confirmInput);
            primaryInput.classList.remove('cat-password-match', 'cat-password-mismatch');
            confirmInput.classList.remove('cat-password-match', 'cat-password-mismatch');

            if ((confirmInput.value || '') === '') {
                confirmInput.setCustomValidity('');
                note.textContent = '';
                note.className = 'small mt-1 cat-password-compare-note';
                return true;
            }

            var matches = (confirmInput.value || '') === (primaryInput.value || '');
            confirmInput.setCustomValidity(matches ? '' : labels.mismatch);
            note.textContent = matches ? labels.matchOk : labels.mismatch;
            note.className = 'small mt-1 cat-password-compare-note ' + (matches ? 'text-success' : 'text-danger');
            primaryInput.classList.add(matches ? 'cat-password-match' : 'cat-password-mismatch');
            confirmInput.classList.add(matches ? 'cat-password-match' : 'cat-password-mismatch');
            return matches;
        };

        var updatePrimary = function (input) {
            ensurePrimaryUi(input);
            var result = passwordScore(input.value || '');
            var tone = computeTone(result.score);
            var toneLabel = tone === 'weak' ? labels.weak : (tone === 'medium' ? labels.medium : labels.strong);
            var group = input.closest('.cat-password-group');
            var scoreNode = group ? group.querySelector('[data-cat-password-inline-score]') : null;

            setInputTone(input, tone);
            if (scoreNode) {
                scoreNode.value = result.score + '%';
                scoreNode.classList.remove('is-weak', 'is-medium', 'is-strong');
                scoreNode.classList.add(tone === 'weak' ? 'is-weak' : (tone === 'medium' ? 'is-medium' : 'is-strong'));
                scoreNode.setAttribute('title', labels.score + ': ' + result.score + '% (' + toneLabel + ')');
            }
            renderRules(input, result);

            var confirmSelector = 'input[name="password_confirm"], input[name="password_confirmation"], input[name="confirm_password"], input[name="new_password_confirmation"], input#password_confirm';
            var form = input.form || document;
            var confirmInput = form.querySelector(confirmSelector);
            var primaryName = (input.name || '').toLowerCase();
            var isLikelyPrimary = primaryName === 'password' || primaryName === 'new_password' || primaryName === 'admin_password' || primaryName === '';
            if (confirmInput && !confirmInput.readOnly && !confirmInput.disabled && isLikelyPrimary) {
                updateCompareState(input, confirmInput);
                var listenerKey = 'data-cat-compare-bound-' + getPairKey(input, confirmInput).replace(/[^a-z0-9_-]+/gi, '-');
                if (!confirmInput.hasAttribute(listenerKey)) {
                    var compareUpdater = function () { updateCompareState(input, confirmInput); };
                    confirmInput.addEventListener('input', compareUpdater);
                    confirmInput.addEventListener('change', compareUpdater);
                    confirmInput.setAttribute(listenerKey, '1');
                }
            }

            input.setCustomValidity(result.score >= 50 ? '' : labels.blocked);
            return result.score;
        };

        primaryFields.forEach(function (input) {
            allForms.add(input.form || document);
            input.addEventListener('input', function () { updatePrimary(input); });
            input.addEventListener('keyup', function () { updatePrimary(input); });
            input.addEventListener('paste', function () {
                window.setTimeout(function () { updatePrimary(input); }, 0);
            });
            input.addEventListener('change', function () { updatePrimary(input); });
            input.addEventListener('focus', function () {
                if (input._catPwdLiveTimer) {
                    return;
                }
                var last = input.value || '';
                input._catPwdLiveTimer = window.setInterval(function () {
                    var now = input.value || '';
                    if (now !== last) {
                        last = now;
                        updatePrimary(input);
                    }
                }, 120);
            });
            input.addEventListener('blur', function () {
                if (input._catPwdLiveTimer) {
                    window.clearInterval(input._catPwdLiveTimer);
                    input._catPwdLiveTimer = null;
                }
            });
            updatePrimary(input);
        });

        allForms.forEach(function (form) {
            if (!form || typeof form.addEventListener !== 'function') {
                return;
            }
            form.addEventListener('submit', function (event) {
                var ok = true;
                primaryFields.forEach(function (input) {
                    if ((input.form || document) !== form) {
                        return;
                    }
                    if (updatePrimary(input) < 50) {
                        ok = false;
                    }
                });
                if (!ok) {
                    event.preventDefault();
                    var firstInvalid = form.querySelector('.cat-password-weak, input:invalid');
                    if (firstInvalid && typeof firstInvalid.reportValidity === 'function') {
                        firstInvalid.reportValidity();
                    }
                }
            });
        });
    }

    function slugify(value) {
        var text = (value || '').toString().trim().toLowerCase();
        if (text === '') {
            return '';
        }

        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        text = text
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');

        return text;
    }

    function initAutoSlug() {
        var pairs = [];

        document.querySelectorAll('[data-cat-slug-target]').forEach(function (target) {
            var selector = target.getAttribute('data-cat-slug-target');
            if (!selector) {
                return;
            }
            var source = document.querySelector(selector);
            if (!source) {
                return;
            }
            pairs.push({ source: source, target: target });
        });

        if (pairs.length === 0) {
            document.querySelectorAll('input[name="slug"]').forEach(function (target) {
                if (target.type === 'hidden' || target.readOnly || target.disabled) {
                    return;
                }
                var form = target.form || document;
                var source = form.querySelector('input[name="name"], input[name="title"], input[name="label"]');
                if (!source || source === target) {
                    return;
                }
                pairs.push({ source: source, target: target });
            });
        }

        pairs.forEach(function (pair) {
            var source = pair.source;
            var target = pair.target;
            var syncing = true;

            var sync = function () {
                if (!syncing || target.readOnly || target.disabled) {
                    return;
                }
                target.value = slugify(source.value);
            };

            source.addEventListener('input', sync);
            source.addEventListener('change', sync);

            target.addEventListener('input', function () {
                var sourceSlug = slugify(source.value);
                var targetSlug = slugify(target.value);
                syncing = targetSlug === '' || targetSlug === sourceSlug;
            });

            if ((target.value || '').trim() === '') {
                sync();
            } else {
                syncing = slugify(target.value) === slugify(source.value);
            }
        });
    }

    document.querySelectorAll('[data-cat-alert-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.closest('.alert');
            if (!target) {
                return;
            }
            target.classList.remove('show');
            window.setTimeout(function () {
                target.remove();
            }, 160);
        });
    });

    document.querySelectorAll('[data-cat-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-cat-toggle-password');
            if (!targetId) {
                return;
            }
            var input = document.getElementById(targetId);
            if (!input) {
                return;
            }
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    (function initTooltips() {
        if (typeof window.bootstrap === 'undefined' || !window.bootstrap || !window.bootstrap.Tooltip) {
            return;
        }

        var nodes = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        nodes.forEach(function (node) {
            if (!node.hasAttribute('data-cat-tooltip-ready')) {
                new window.bootstrap.Tooltip(node);
                node.setAttribute('data-cat-tooltip-ready', '1');
            }
        });
    }());

    (function initFormTriggers() {
        var submitForm = function (form) {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            var submitter = form.querySelector('[data-cat-submitter]');
            if (typeof form.requestSubmit === 'function') {
                if (submitter) {
                    form.requestSubmit(submitter);
                    return;
                }
                form.requestSubmit();
                return;
            }

            var tempSubmit = document.createElement('button');
            tempSubmit.type = 'submit';
            tempSubmit.hidden = true;
            form.appendChild(tempSubmit);
            tempSubmit.click();
            tempSubmit.remove();
        };

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            var directButton = target.closest('[data-cat-submit-form]');
            if (directButton instanceof HTMLElement) {
                var formId = (directButton.getAttribute('data-cat-submit-form') || '').trim();
                if (formId !== '') {
                    var directForm = document.getElementById(formId);
                    if (directForm instanceof HTMLFormElement) {
                        submitForm(directForm);
                    }
                }
                return;
            }

            var selectButton = target.closest('[data-cat-submit-form-from-select]');
            if (!(selectButton instanceof HTMLElement)) {
                return;
            }

            var selectId = (selectButton.getAttribute('data-cat-submit-form-from-select') || '').trim();
            if (selectId === '') {
                return;
            }

            var select = document.getElementById(selectId);
            if (!(select instanceof HTMLSelectElement)) {
                return;
            }

            var selectedFormId = (select.value || '').trim();
            if (selectedFormId === '') {
                return;
            }

            var selectedForm = document.getElementById(selectedFormId);
            if (selectedForm instanceof HTMLFormElement) {
                submitForm(selectedForm);
            }
        });
    }());

    document.querySelectorAll('[data-cat-toast]').forEach(function (toastEl) {
        var delay = parseInt(toastEl.getAttribute('data-cat-toast-delay') || '4200', 10);
        if (!Number.isFinite(delay) || delay < 1000) {
            delay = 4200;
        }

        var progressEl = toastEl.querySelector('[data-cat-toast-progress]');
        var animateProgress = function () {
            if (!progressEl) {
                return;
            }
            progressEl.style.transitionDuration = delay + 'ms';
            progressEl.style.width = '100%';
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    progressEl.style.width = '0%';
                });
            });
        };

        if (window.bootstrap && typeof window.bootstrap.Toast === 'function') {
            var toast = window.bootstrap.Toast.getOrCreateInstance(toastEl, {
                autohide: true,
                delay: delay
            });
            toastEl.addEventListener('shown.bs.toast', animateProgress);
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
            toast.show();
            return;
        }

        toastEl.classList.add('show');
        animateProgress();
        window.setTimeout(function () {
            toastEl.remove();
        }, delay);
    });

    document.querySelectorAll('[data-cat-sidebar-order]').forEach(function (listEl) {
        var orderInput = document.querySelector('[data-cat-sidebar-order-input]');
        var dragging = null;

        var updateOrder = function () {
            if (!orderInput) { return; }
            var keys = [];
            listEl.querySelectorAll('[data-cat-sidebar-item]').forEach(function (item) {
                keys.push(item.getAttribute('data-key') || '');
            });
            orderInput.value = keys.filter(function (v) { return v !== ''; }).join(',');
        };

        // After drag, reassign IDs by collecting current values, sorting them,
        // and redistributing them in DOM order so the visual order matches numeric order.
        var reassignIds = function () {
            var items = listEl.querySelectorAll('[data-cat-sidebar-item]');
            var ids = [];
            items.forEach(function (item) {
                var inp = item.querySelector('[data-cat-sidebar-id-input]');
                if (inp) { ids.push(parseInt(inp.value, 10) || 100); }
            });
            ids.sort(function (a, b) { return a - b; });
            items.forEach(function (item, i) {
                var inp = item.querySelector('[data-cat-sidebar-id-input]');
                if (inp) { inp.value = ids[i] !== undefined ? ids[i] : (i + 1) * 100; }
            });
        };

        listEl.querySelectorAll('[data-cat-sidebar-item]').forEach(function (item) {
            item.addEventListener('dragstart', function (event) {
                dragging = item;
                item.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.getAttribute('data-key') || '');
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('is-dragging');
                dragging = null;
                reassignIds();
                updateOrder();
            });
            item.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (!dragging || dragging === item) { return; }
                var rect = item.getBoundingClientRect();
                var next = (event.clientY - rect.top) > rect.height / 2;
                listEl.insertBefore(dragging, next ? item.nextSibling : item);
            });
        });
    });

    (function initSidebarEntryOrderByGroup() {
        var globalInput = document.querySelector('[data-cat-sidebar-item-order-input]');
        var groupedLists = document.querySelectorAll('[data-cat-sidebar-item-order-group]');
        if (!globalInput || groupedLists.length === 0) {
            return;
        }

        var updateGlobalOrder = function () {
            var keys = [];
            groupedLists.forEach(function (listEl) {
                listEl.querySelectorAll('[data-cat-sidebar-entry-item]').forEach(function (item) {
                    keys.push(item.getAttribute('data-key') || '');
                });
            });
            globalInput.value = keys.filter(function (v) { return v !== ''; }).join(',');
        };

        groupedLists.forEach(function (listEl) {
            var dragging = null;
            listEl.querySelectorAll('[data-cat-sidebar-entry-item]').forEach(function (item) {
                item.addEventListener('dragstart', function (event) {
                    dragging = item;
                    item.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.getAttribute('data-key') || '');
                });
                item.addEventListener('dragend', function () {
                    item.classList.remove('is-dragging');
                    dragging = null;
                    updateGlobalOrder();
                });
                item.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    if (!dragging || dragging === item) {
                        return;
                    }
                    var rect = item.getBoundingClientRect();
                    var next = (event.clientY - rect.top) > rect.height / 2;
                    listEl.insertBefore(dragging, next ? item.nextSibling : item);
                });
            });
        });

        updateGlobalOrder();
    }());

    // Auto-save: debounced fetch submit for forms with data-cat-autosave
    (function initSettingsAutoSave() {
        document.querySelectorAll('form[data-cat-autosave]').forEach(function (form) {
            var timer = null;
            var indicator = document.createElement('span');
            indicator.className = 'text-success small ms-3 align-self-center';
            indicator.style.cssText = 'opacity:0;transition:opacity 0.4s;';
            indicator.textContent = '\u2713 Sauvegard\u00e9';
            var btnRow = form.querySelector('.d-flex.gap-2');
            if (btnRow) { btnRow.appendChild(indicator); }

            var doSave = function () {
                // Ensure drag-generated hidden inputs reflect current state
                var orderInput = form.querySelector('[data-cat-sidebar-order-input]');
                var itemOrderInput = form.querySelector('[data-cat-sidebar-item-order-input]');
                var data = new FormData(form);
                // disabled inputs (e.g. dashboard) are excluded from FormData — add them back
                form.querySelectorAll('[disabled][name]').forEach(function (el) {
                    data.set(el.name, el.value);
                });
                fetch(form.action, {
                    method: 'POST',
                    body: data,
                    redirect: 'follow',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) {
                    if (response.status < 400) {
                        indicator.style.opacity = '1';
                        setTimeout(function () { indicator.style.opacity = '0'; }, 2000);
                    }
                }).catch(function () {});
            };

            var schedule = function () {
                clearTimeout(timer);
                timer = setTimeout(doSave, 1200);
            };

            form.addEventListener('change', schedule);
            // Only trigger input for text/number fields to avoid spamming on every keystroke
            form.addEventListener('input', function (e) {
                var tag = (e.target.tagName || '').toLowerCase();
                var type = (e.target.type || '').toLowerCase();
                if (tag === 'input' && (type === 'text' || type === 'number' || type === 'email' || type === 'url' || type === '')) {
                    schedule();
                }
            });
        });
    }());

    initAutoSlug();
    initPasswordPolicy();
}());
