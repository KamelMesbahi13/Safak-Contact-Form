/**
 * safak-admin.js
 * Safak Medical – Admin Settings Page JavaScript
 * Handles AJAX add/remove for departments and doctors.
 */
(function () {
    'use strict';

    var cfg = window.SafakAdmin || {};

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

    // ── Departments ─────────────────────────────────────────────────────────

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

    // ── Event Delegation ────────────────────────────────────────────────────

    document.addEventListener('click', function (e) {

        // Sync with WordPress
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

        // Add Department
        if (e.target.id === 'safak-add-dept-btn' || e.target.closest('#safak-add-dept-btn')) {
            var input = document.getElementById('safak-dept-input');
            var name = (input.value || '').trim();
            if (!name) { input.focus(); return; }
            post('safak_add_department', { department_name: name }, function (err, data) {
                if (err) { alert(err); return; }
                input.value = '';
                renderDepartments(data.departments);
            });
        }

        // Remove Department
        var removeDepBtn = e.target.closest('.safak-remove-dept');
        if (removeDepBtn) {
            var deptName = removeDepBtn.dataset.name;
            if (!confirm('Remove department "' + deptName + '"? All doctors in this department will also be removed.')) return;
            post('safak_remove_department', { department_name: deptName }, function (err, data) {
                if (err) { alert(err); return; }
                renderDepartments(data.departments);
                renderDoctors(data.doctors);
            });
        }

        // Add Doctor
        if (e.target.id === 'safak-add-doctor-btn' || e.target.closest('#safak-add-doctor-btn')) {
            var nameInput = document.getElementById('safak-doctor-input');
            var deptSelect = document.getElementById('safak-doctor-dept-select');
            var docName = (nameInput.value || '').trim();
            var docDept = deptSelect.value;
            if (!docName) { nameInput.focus(); return; }
            if (!docDept) { deptSelect.focus(); return; }
            post('safak_add_doctor', { doctor_name: docName, doctor_department: docDept }, function (err, data) {
                if (err) { alert(err); return; }
                nameInput.value = '';
                renderDoctors(data.doctors);
            });
        }

        // Remove Doctor
        var removeDocBtn = e.target.closest('.safak-remove-doctor');
        if (removeDocBtn) {
            var idx = removeDocBtn.dataset.index;
            if (!confirm('Remove this doctor?')) return;
            post('safak_remove_doctor', { doctor_index: idx }, function (err, data) {
                if (err) { alert(err); return; }
                renderDoctors(data.doctors);
            });
        }
    });

    // Allow pressing Enter in department input to add
    var deptInput = document.getElementById('safak-dept-input');
    if (deptInput) {
        deptInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('safak-add-dept-btn').click();
            }
        });
    }

    // Allow pressing Enter in doctor name input to add
    var docInput = document.getElementById('safak-doctor-input');
    if (docInput) {
        docInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('safak-add-doctor-btn').click();
            }
        });
    }

})();
