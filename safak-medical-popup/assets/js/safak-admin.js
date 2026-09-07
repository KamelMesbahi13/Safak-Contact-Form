/**
 * safak-admin.js
 * Safak Medical – Admin Settings Page JavaScript
 * Handles AJAX add/remove for departments and doctors.
 * Supports two modes: WP Sync and Manual (per-language).
 */
(function () {
    'use strict';

    var cfg = window.SafakAdmin || {};

    // Local cache for manual data (updated on AJAX responses)
    var manualDepartments = cfg.manualDepartments || { ar: [], fr: [], en: [] };
    var manualDoctors     = cfg.manualDoctors     || { ar: [], fr: [], en: [] };

    // ── Utility ─────────────────────────────────────────────────────────────

    function post(action, data, callback) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', cfg.nonce);
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, data[key]);
            }
        }
        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.success) {
                    callback(null, json.data);
                } else {
                    callback(json.data ? json.data.message : 'Error');
                }
            })
            .catch(function (err) { callback(err.message || 'Network error'); });
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showNotice(msg, isError) {
        var notice = document.getElementById('safak-admin-notice');
        if (!notice) return;
        notice.textContent = msg;
        notice.className = 'safak-admin-notice ' + (isError ? 'safak-admin-notice--error' : 'safak-admin-notice--success');
        notice.style.display = 'block';
        setTimeout(function () {
            notice.style.display = 'none';
        }, 5000);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  WP SYNC MODE – Departments & Doctors
    // ══════════════════════════════════════════════════════════════════════════

    function renderDepartments(departments) {
        var list = document.getElementById('safak-dept-list');
        if (!list) return;

        if (!departments || departments.length === 0) {
            list.innerHTML = '<p class="safak-admin-empty">No departments added yet.</p>';
            updateDoctorDeptSelect(departments);
            return;
        }

        list.innerHTML = departments.map(function (dept) {
            return '<div class="safak-admin-list-item" data-name="' + escHtml(dept) + '">' +
                '<span class="safak-admin-list-item__name">' + escHtml(dept) + '</span>' +
                '<button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-dept" data-name="' + escHtml(dept) + '">✕</button>' +
                '</div>';
        }).join('');

        updateDoctorDeptSelect(departments);
    }

    function updateDoctorDeptSelect(departments) {
        var select = document.getElementById('safak-doctor-dept-select');
        if (!select) return;
        var currentVal = select.value;
        select.innerHTML = '<option value="">— Select Department —</option>';
        if (departments) {
            departments.forEach(function (dept) {
                var opt = document.createElement('option');
                opt.value = dept;
                opt.textContent = dept;
                if (dept === currentVal) opt.selected = true;
                select.appendChild(opt);
            });
        }
    }

    function renderDoctors(doctors) {
        var list = document.getElementById('safak-doctor-list');
        if (!list) return;

        if (!doctors || doctors.length === 0) {
            list.innerHTML = '<p class="safak-admin-empty">No doctors added yet.</p>';
            return;
        }

        list.innerHTML = doctors.map(function (doc, idx) {
            return '<div class="safak-admin-list-item" data-index="' + idx + '">' +
                '<span class="safak-admin-list-item__name">' + escHtml(doc.name) + '</span>' +
                '<span class="safak-admin-list-item__badge">' + escHtml(doc.department) + '</span>' +
                '<button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-doctor" data-index="' + idx + '">✕</button>' +
                '</div>';
        }).join('');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MANUAL MODE – Per-Language Departments & Doctors
    // ══════════════════════════════════════════════════════════════════════════

    var langPlaceholders = {
        ar: '— اختر القسم —',
        fr: '— Sélectionner —',
        en: '— Select Department —'
    };

    function renderManualDepartments(lang, departments) {
        var list = document.querySelector('.safak-manual-dept-list[data-lang="' + lang + '"]');
        if (!list) return;

        if (!departments || departments.length === 0) {
            list.innerHTML = '<p class="safak-admin-empty">No departments added yet.</p>';
            updateManualDoctorDeptSelect(lang, departments);
            return;
        }

        list.innerHTML = departments.map(function (dept) {
            return '<div class="safak-admin-list-item" data-name="' + escHtml(dept) + '">' +
                '<span class="safak-admin-list-item__name">' + escHtml(dept) + '</span>' +
                '<button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-manual-dept" data-lang="' + lang + '" data-name="' + escHtml(dept) + '">✕</button>' +
                '</div>';
        }).join('');

        updateManualDoctorDeptSelect(lang, departments);
    }

    function updateManualDoctorDeptSelect(lang, departments) {
        var select = document.querySelector('.safak-manual-doctor-dept-select[data-lang="' + lang + '"]');
        if (!select) return;
        var currentVal = select.value;
        select.innerHTML = '<option value="">' + escHtml(langPlaceholders[lang] || langPlaceholders.en) + '</option>';
        if (departments) {
            departments.forEach(function (dept) {
                var opt = document.createElement('option');
                opt.value = dept;
                opt.textContent = dept;
                if (dept === currentVal) opt.selected = true;
                select.appendChild(opt);
            });
        }
    }

    function renderManualDoctors(lang, doctors) {
        var list = document.querySelector('.safak-manual-doctor-list[data-lang="' + lang + '"]');
        if (!list) return;

        if (!doctors || doctors.length === 0) {
            list.innerHTML = '<p class="safak-admin-empty">No doctors added yet.</p>';
            return;
        }

        list.innerHTML = doctors.map(function (doc, idx) {
            return '<div class="safak-admin-list-item" data-index="' + idx + '">' +
                '<span class="safak-admin-list-item__name">' + escHtml(doc.name) + '</span>' +
                '<span class="safak-admin-list-item__badge">' + escHtml(doc.department) + '</span>' +
                '<button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-manual-doctor" data-lang="' + lang + '" data-index="' + idx + '">✕</button>' +
                '</div>';
        }).join('');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MODE TOGGLE
    // ══════════════════════════════════════════════════════════════════════════

    function switchMode(mode) {
        var wpPanels = document.getElementById('safak-wp-sync-panels');
        var manualPanels = document.getElementById('safak-manual-panels');

        if (mode === 'wp_sync') {
            if (wpPanels) wpPanels.style.display = '';
            if (manualPanels) manualPanels.style.display = 'none';
        } else {
            if (wpPanels) wpPanels.style.display = 'none';
            if (manualPanels) manualPanels.style.display = '';
        }

        // Update active style on radio labels
        document.querySelectorAll('.safak-mode-option').forEach(function (el) {
            var radio = el.querySelector('input[type="radio"]');
            if (radio && radio.value === mode) {
                el.classList.add('safak-mode-option--active');
            } else {
                el.classList.remove('safak-mode-option--active');
            }
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LANGUAGE TABS
    // ══════════════════════════════════════════════════════════════════════════

    function switchLangTab(lang) {
        // Update tab active state
        document.querySelectorAll('.safak-lang-tab').forEach(function (tab) {
            if (tab.dataset.lang === lang) {
                tab.classList.add('safak-lang-tab--active');
            } else {
                tab.classList.remove('safak-lang-tab--active');
            }
        });

        // Show/hide panels
        document.querySelectorAll('.safak-lang-panel').forEach(function (panel) {
            if (panel.dataset.lang === lang) {
                panel.classList.add('safak-lang-panel--active');
                panel.style.display = '';
            } else {
                panel.classList.remove('safak-lang-panel--active');
                panel.style.display = 'none';
            }
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  EVENT DELEGATION
    // ══════════════════════════════════════════════════════════════════════════

    document.addEventListener('click', function (e) {

        // ── Mode Toggle ─────────────────────────────────────────────────
        var modeOption = e.target.closest('.safak-mode-option');
        if (modeOption) {
            var radio = modeOption.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                var newMode = radio.value;
                switchMode(newMode);
                post('safak_save_data_mode', { mode: newMode }, function (err) {
                    if (err) {
                        showNotice(err, true);
                    } else {
                        showNotice('Data source updated to: ' + (newMode === 'wp_sync' ? 'Sync from WordPress' : 'Manual Data'), false);
                    }
                });
            }
            return;
        }

        // ── Language Tabs ───────────────────────────────────────────────
        var langTab = e.target.closest('.safak-lang-tab');
        if (langTab) {
            switchLangTab(langTab.dataset.lang);
            return;
        }

        // ── WP Sync: Sync button ────────────────────────────────────────
        var syncBtn = e.target.closest('#safak-sync-wp-btn');
        if (syncBtn) {
            var origHtml = syncBtn.innerHTML;
            syncBtn.disabled = true;
            syncBtn.innerHTML = '<span>Syncing with WordPress...</span>';
            post('safak_sync_wp_data', {}, function (err, data) {
                syncBtn.disabled = false;
                syncBtn.innerHTML = origHtml;
                if (err) {
                    showNotice(err, true);
                    return;
                }
                renderDepartments(data.departments);
                renderDoctors(data.doctors);
                showNotice(data.message || 'Successfully synchronized with WordPress!', false);
            });
            return;
        }

        // ── WP Sync: Add Department ─────────────────────────────────────
        if (e.target.id === 'safak-add-dept-btn' || e.target.closest('#safak-add-dept-btn')) {
            var input = document.getElementById('safak-dept-input');
            var name = (input.value || '').trim();
            if (!name) { input.focus(); return; }
            post('safak_add_department', { department_name: name }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                input.value = '';
                renderDepartments(data.departments);
            });
        }

        // ── WP Sync: Remove Department ──────────────────────────────────
        var removeDepBtn = e.target.closest('.safak-remove-dept');
        if (removeDepBtn && !removeDepBtn.classList.contains('safak-remove-manual-dept')) {
            var deptName = removeDepBtn.dataset.name;
            if (!confirm('Remove department "' + deptName + '"? All doctors in this department will also be removed.')) return;
            post('safak_remove_department', { department_name: deptName }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                renderDepartments(data.departments);
                renderDoctors(data.doctors);
            });
        }

        // ── WP Sync: Add Doctor ─────────────────────────────────────────
        if (e.target.id === 'safak-add-doctor-btn' || e.target.closest('#safak-add-doctor-btn')) {
            var nameInput = document.getElementById('safak-doctor-input');
            var deptSelect = document.getElementById('safak-doctor-dept-select');
            var docName = (nameInput.value || '').trim();
            var docDept = deptSelect.value;
            if (!docName) { nameInput.focus(); return; }
            if (!docDept) { deptSelect.focus(); return; }
            post('safak_add_doctor', { doctor_name: docName, doctor_department: docDept }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                nameInput.value = '';
                renderDoctors(data.doctors);
            });
        }

        // ── WP Sync: Remove Doctor ──────────────────────────────────────
        var removeDocBtn = e.target.closest('.safak-remove-doctor');
        if (removeDocBtn && !removeDocBtn.classList.contains('safak-remove-manual-doctor')) {
            var idx = removeDocBtn.dataset.index;
            if (!confirm('Remove this doctor?')) return;
            post('safak_remove_doctor', { doctor_index: idx }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                renderDoctors(data.doctors);
            });
        }

        // ══════════════════════════════════════════════════════════════════
        //  MANUAL MODE ACTIONS
        // ══════════════════════════════════════════════════════════════════

        // ── Manual: Add Department ──────────────────────────────────────
        var addManualDeptBtn = e.target.closest('.safak-add-manual-dept-btn');
        if (addManualDeptBtn) {
            var lang = addManualDeptBtn.dataset.lang;
            var inputEl = document.querySelector('.safak-manual-dept-input[data-lang="' + lang + '"]');
            var dName = (inputEl.value || '').trim();
            if (!dName) { inputEl.focus(); return; }
            post('safak_add_manual_department', { lang: lang, department_name: dName }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                inputEl.value = '';
                manualDepartments = data.departments;
                renderManualDepartments(lang, manualDepartments[lang] || []);
            });
        }

        // ── Manual: Remove Department ───────────────────────────────────
        var removeManualDeptBtn = e.target.closest('.safak-remove-manual-dept');
        if (removeManualDeptBtn) {
            var rmLang = removeManualDeptBtn.dataset.lang;
            var rmName = removeManualDeptBtn.dataset.name;
            if (!confirm('Remove department "' + rmName + '"? All doctors in this department will also be removed.')) return;
            post('safak_remove_manual_department', { lang: rmLang, department_name: rmName }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                manualDepartments = data.departments;
                manualDoctors     = data.doctors;
                renderManualDepartments(rmLang, manualDepartments[rmLang] || []);
                renderManualDoctors(rmLang, manualDoctors[rmLang] || []);
            });
        }

        // ── Manual: Add Doctor ──────────────────────────────────────────
        var addManualDocBtn = e.target.closest('.safak-add-manual-doctor-btn');
        if (addManualDocBtn) {
            var docLang = addManualDocBtn.dataset.lang;
            var docNameInput = document.querySelector('.safak-manual-doctor-input[data-lang="' + docLang + '"]');
            var docDeptSelect = document.querySelector('.safak-manual-doctor-dept-select[data-lang="' + docLang + '"]');
            var mDocName = (docNameInput.value || '').trim();
            var mDocDept = docDeptSelect.value;
            if (!mDocName) { docNameInput.focus(); return; }
            if (!mDocDept) { docDeptSelect.focus(); return; }
            post('safak_add_manual_doctor', { lang: docLang, doctor_name: mDocName, doctor_department: mDocDept }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                docNameInput.value = '';
                manualDoctors = data.doctors;
                renderManualDoctors(docLang, manualDoctors[docLang] || []);
            });
        }

        // ── Manual: Remove Doctor ───────────────────────────────────────
        var removeManualDocBtn = e.target.closest('.safak-remove-manual-doctor');
        if (removeManualDocBtn) {
            var rdLang = removeManualDocBtn.dataset.lang;
            var rdIdx  = removeManualDocBtn.dataset.index;
            if (!confirm('Remove this doctor?')) return;
            post('safak_remove_manual_doctor', { lang: rdLang, doctor_index: rdIdx }, function (err, data) {
                if (err) { showNotice(err, true); return; }
                manualDoctors = data.doctors;
                renderManualDoctors(rdLang, manualDoctors[rdLang] || []);
            });
        }
    });

    // ── Enter key handling (WP Sync inputs) ─────────────────────────────────

    var deptInput = document.getElementById('safak-dept-input');
    if (deptInput) {
        deptInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('safak-add-dept-btn').click();
            }
        });
    }

    var docInput = document.getElementById('safak-doctor-input');
    if (docInput) {
        docInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('safak-add-doctor-btn').click();
            }
        });
    }

    // ── Enter key handling (Manual inputs) ───────────────────────────────────

    document.querySelectorAll('.safak-manual-dept-input').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var lang = input.dataset.lang;
                var btn = document.querySelector('.safak-add-manual-dept-btn[data-lang="' + lang + '"]');
                if (btn) btn.click();
            }
        });
    });

    document.querySelectorAll('.safak-manual-doctor-input').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var lang = input.dataset.lang;
                var btn = document.querySelector('.safak-add-manual-doctor-btn[data-lang="' + lang + '"]');
                if (btn) btn.click();
            }
        });
    });

})();
