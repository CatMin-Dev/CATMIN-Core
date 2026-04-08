document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.js-install-submit');
    if (!form) return;
    var stepInput = form.querySelector('input[name="_step"]');
    var isProfileStep = stepInput && stepInput.value === 'profile';
    var profilePhaseInput = form.querySelector('.js-profile-phase');
    var selectPanel = form.querySelector('.js-profile-select-panel');
    var customPanel = form.querySelector('.js-profile-custom-panel');
    var backTemplateBtn = form.querySelector('.js-profile-back');
    var templateRadios = form.querySelectorAll('input[name="profile"]');
    var dbDriverRadios = form.querySelectorAll('.js-db-driver-radio');
    var dbSqliteFields = form.querySelectorAll('.js-db-sqlite-fields');
    var dbServerFields = form.querySelectorAll('.js-db-server-fields');
    var dbSqliteInput = form.querySelector('input[name="sqlite_path"]');
    var dbPortInput = form.querySelector('input[name="port"]');
    var dbDatabaseInput = form.querySelector('input[name="database"]');
    var dbTestBtn = form.querySelector('.js-db-test-btn');
    var dbTestResult = form.querySelector('.js-db-test-result');
    var csrfInput = form.querySelector('input[name="_csrf"]');
    var adminModeRadios = form.querySelectorAll('.js-admin-mode-radio');
    var adminManualField = form.querySelector('.js-admin-manual-field');
    var adminAutoField = form.querySelector('.js-admin-auto-field');
    var adminManualInput = form.querySelector('input[name="admin_path"]');
    var adminAutoInput = form.querySelector('.js-admin-auto-input');
    var adminRegenBtn = form.querySelector('.js-admin-regenerate');
    var operatorTypeSelect = form.querySelector('select[name="operator_type"]');
    var operatorNameInput = form.querySelector('.js-operator-name');
    var whitelistToggle = form.querySelector('.js-whitelist-toggle');
    var whitelistWrap = form.querySelector('.js-whitelist-wrap');
    var whitelistText = form.querySelector('.js-whitelist-text');
    var whitelistInstallerIp = form.querySelector('.js-whitelist-installer-ip');

    var updateCustomPanel = function () {
        if (!isProfileStep || !profilePhaseInput) {
            return;
        }

        var selected = form.querySelector('input[name="profile"]:checked');
        var isCustom = selected && selected.value === 'custom';
        var showModules = isCustom && profilePhaseInput.value === 'modules';
        if (customPanel) {
            customPanel.classList.toggle('d-none', !showModules);
        }
        if (selectPanel) {
            selectPanel.classList.toggle('d-none', showModules);
        }
    };

    if (isProfileStep) {
        templateRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                var selected = form.querySelector('input[name="profile"]:checked');
                if (!selected || selected.value !== 'custom') {
                    if (profilePhaseInput) {
                        profilePhaseInput.value = 'select';
                    }
                }
                updateCustomPanel();
            });
        });

        if (backTemplateBtn) {
            backTemplateBtn.addEventListener('click', function (event) {
                if (backTemplateBtn.tagName === 'A') {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (profilePhaseInput) {
                    profilePhaseInput.value = 'select';
                }
                updateCustomPanel();
            });
        }

        updateCustomPanel();
    }

    var getSelectedDbDriver = function () {
        var selectedDriver = 'sqlite';
        dbDriverRadios.forEach(function (radio) {
            if (radio.checked) {
                selectedDriver = radio.value;
            }
        });
        return selectedDriver;
    };

    var updateDatabaseFields = function () {
        if (!dbDriverRadios || dbDriverRadios.length === 0) {
            return;
        }

        var selectedDriver = getSelectedDbDriver();

        var defaults = {
            sqlite: '',
            mysql: '3306',
            mariadb: '3306',
            pgsql: '5432',
            sqlsrv: '1433'
        };

        var isSqlite = selectedDriver === 'sqlite';
        dbSqliteFields.forEach(function (field) {
            field.classList.toggle('d-none', !isSqlite);
        });
        dbServerFields.forEach(function (field) {
            field.classList.toggle('d-none', isSqlite);
        });

        if (dbSqliteInput) {
            dbSqliteInput.required = isSqlite;
        }

        if (dbDatabaseInput) {
            dbDatabaseInput.required = !isSqlite;
            if (!isSqlite && dbDatabaseInput.value.trim() === '') {
                dbDatabaseInput.value = 'catmin';
            }
        }

        if (dbPortInput && (dbPortInput.value.trim() === '' || dbPortInput.dataset.autofill === '1')) {
            dbPortInput.value = defaults[selectedDriver] || '';
            dbPortInput.dataset.autofill = '1';
        }
    };

    if (dbDriverRadios && dbDriverRadios.length > 0) {
        dbDriverRadios.forEach(function (radio) {
            radio.addEventListener('change', updateDatabaseFields);
        });
        if (dbPortInput) {
            dbPortInput.addEventListener('input', function () {
                dbPortInput.dataset.autofill = '0';
            });
        }
        updateDatabaseFields();
    }

    if (dbTestBtn && csrfInput) {
        dbTestBtn.addEventListener('click', function () {
            var formData = new FormData(form);
            fetch('/install/db-test', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            }).then(function (result) {
                if (
                    csrfInput
                    && result.payload
                    && typeof result.payload.csrf === 'string'
                    && result.payload.csrf.length > 0
                ) {
                    csrfInput.value = result.payload.csrf;
                }

                if (!dbTestResult) {
                    return;
                }

                dbTestResult.classList.remove('d-none', 'alert-success', 'alert-danger');
                dbTestResult.classList.add('alert', result.ok ? 'alert-success' : 'alert-danger');
                dbTestResult.textContent = result.payload && result.payload.message
                    ? result.payload.message
                    : (result.ok ? 'Connexion DB validée.' : 'Échec test DB.');
            }).catch(function () {
                if (!dbTestResult) {
                    return;
                }

                dbTestResult.classList.remove('d-none', 'alert-success');
                dbTestResult.classList.add('alert', 'alert-danger');
                dbTestResult.textContent = 'Impossible de tester la connexion DB.';
            });
        });
    }

    var randomAdminPath = function () {
        var seed = Math.random().toString(16).slice(2, 10);
        return 'admin-' + seed;
    };

    var updateAdminMode = function () {
        if (!adminModeRadios || adminModeRadios.length === 0) {
            return;
        }

        var mode = 'manual';
        adminModeRadios.forEach(function (radio) {
            if (radio.checked) {
                mode = radio.value;
            }
        });

        var isAuto = mode === 'auto';
        if (adminManualField) {
            adminManualField.classList.toggle('d-none', isAuto);
        }
        if (adminAutoField) {
            adminAutoField.classList.toggle('d-none', !isAuto);
        }

        if (adminManualInput) {
            adminManualInput.required = !isAuto;
        }
        if (adminAutoInput) {
            if (isAuto && adminAutoInput.value.trim() === '') {
                adminAutoInput.value = randomAdminPath();
            }
        }
    };

    if (adminModeRadios && adminModeRadios.length > 0) {
        adminModeRadios.forEach(function (radio) {
            radio.addEventListener('change', updateAdminMode);
        });
        if (adminRegenBtn && adminAutoInput) {
            adminRegenBtn.addEventListener('click', function () {
                adminAutoInput.value = randomAdminPath();
            });
        }
        updateAdminMode();
    }

    var updateOperatorFields = function () {
        if (!operatorTypeSelect || !operatorNameInput) {
            return;
        }

        var isParticulier = operatorTypeSelect.value === 'particulier';
        operatorNameInput.disabled = isParticulier;
        operatorNameInput.required = !isParticulier;
        if (isParticulier) {
            operatorNameInput.value = '';
            operatorNameInput.placeholder = 'Non requis pour Particulier';
        } else {
            operatorNameInput.placeholder = 'Nom nominatif / raison sociale';
        }
    };

    if (operatorTypeSelect && operatorNameInput) {
        operatorTypeSelect.addEventListener('change', updateOperatorFields);
        updateOperatorFields();
    }

    var splitWhitelist = function (value) {
        return value.split(/[\s,]+/).map(function (item) {
            return item.trim();
        }).filter(function (item) {
            return item.length > 0;
        });
    };

    var updateWhitelistFields = function () {
        if (!whitelistToggle || !whitelistWrap || !whitelistText) {
            return;
        }

        var enabled = whitelistToggle.checked;
        whitelistWrap.classList.toggle('d-none', !enabled);
        if (!enabled) {
            whitelistText.required = false;
            return;
        }

        whitelistText.required = true;
        var installerIp = whitelistInstallerIp ? whitelistInstallerIp.value.trim() : '';
        if (installerIp === '') {
            return;
        }

        var list = splitWhitelist(whitelistText.value);
        if (list.indexOf(installerIp) === -1) {
            list.unshift(installerIp);
            whitelistText.value = list.join('\n');
        }
    };

    if (whitelistToggle && whitelistWrap && whitelistText) {
        whitelistToggle.addEventListener('change', updateWhitelistFields);
        updateWhitelistFields();
    }

    form.addEventListener('submit', function (event) {
        if (isProfileStep && profilePhaseInput) {
            var selectedProfile = form.querySelector('input[name="profile"]:checked');
            var isCustomProfile = selectedProfile && selectedProfile.value === 'custom';
            if (isCustomProfile && profilePhaseInput.value !== 'modules') {
                event.preventDefault();
                profilePhaseInput.value = 'modules';
                updateCustomPanel();
                return;
            }
        }

        if (form.dataset.submitting === '1') {
            return;
        }

        form.dataset.submitting = '1';
        event.preventDefault();

        var frame = document.querySelector('.install-frame');
        if (frame) {
            frame.classList.add('is-submitting');
        }

        var button = form.querySelector('.js-continue-btn');
        if (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        }

        window.setTimeout(function () {
            form.submit();
        }, 180);
    });
});
