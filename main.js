/**
 * StockSense - Global JavaScript
 * Loading screen, sidebar toggle, flash message rendering, small UX utilities.
 */
(function () {
    'use strict';

    // ---- Loading screen ----
    window.addEventListener('load', function () {
        var loader = document.getElementById('ss-loader');
        if (loader) {
            setTimeout(function () { loader.classList.add('ss-hide'); }, 350);
        }
    });
    // Safety net: never let the loader get stuck longer than 2.5s
    setTimeout(function () {
        var loader = document.getElementById('ss-loader');
        if (loader) loader.classList.add('ss-hide');
    }, 2500);

    // ---- Sidebar toggle (desktop collapse / mobile drawer) ----
    var sidebar = document.querySelector('.ss-sidebar');
    var main = document.querySelector('.ss-main');
    var backdrop = document.querySelector('.ss-sidebar-backdrop');
    var toggleBtns = document.querySelectorAll('[data-toggle="sidebar"]');

    function isMobile() { return window.innerWidth < 992; }

    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!sidebar) return;
            if (isMobile()) {
                sidebar.classList.toggle('ss-mobile-open');
                if (backdrop) backdrop.classList.toggle('show');
            } else {
                sidebar.classList.toggle('ss-collapsed');
                if (main) main.classList.toggle('ss-collapsed');
                localStorage.setItem('ss_sidebar_collapsed', sidebar.classList.contains('ss-collapsed') ? '1' : '0');
            }
        });
    });

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('ss-mobile-open');
            backdrop.classList.remove('show');
        });
    }

    // Restore collapsed state on desktop
    if (!isMobile() && sidebar && localStorage.getItem('ss_sidebar_collapsed') === '1') {
        sidebar.classList.add('ss-collapsed');
        if (main) main.classList.add('ss-collapsed');
    }

    // ---- Flash message (rendered via data attributes on <body>) ----
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        var type = body.getAttribute('data-flash-type');
        var msg = body.getAttribute('data-flash-message');
        if (type && msg && window.Swal) {
            var iconMap = { success: 'success', danger: 'error', warning: 'warning', info: 'info' };
            Swal.fire({
                icon: iconMap[type] || 'info',
                title: msg,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4200,
                timerProgressBar: true,
            });
        }
    });

    // ---- Generic delete confirmation (SweetAlert2) ----
    // Usage: <a href="#" class="ss-confirm-delete" data-url="delete_process.php?id=5" data-name="Steel Rods">
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.ss-confirm-delete');
        if (!trigger) return;
        e.preventDefault();
        var url = trigger.getAttribute('data-url');
        var name = trigger.getAttribute('data-name') || 'this item';

        Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            confirmButtonColor: '#DC2626',
            cancelButtonText: 'Cancel',
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // ---- Generic status-change confirmation (validate/cancel operations) ----
    // Usage: <button class="ss-confirm-action" data-url="..." data-title="Validate Receipt?" data-text="..." data-icon="question" data-confirm-text="Validate">
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.ss-confirm-action');
        if (!trigger) return;
        e.preventDefault();

        Swal.fire({
            title: trigger.getAttribute('data-title') || 'Are you sure?',
            text: trigger.getAttribute('data-text') || '',
            icon: trigger.getAttribute('data-icon') || 'question',
            showCancelButton: true,
            confirmButtonText: trigger.getAttribute('data-confirm-text') || 'Confirm',
            confirmButtonColor: '#2F6FED',
            cancelButtonText: 'Cancel',
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = trigger.getAttribute('data-url');
            }
        });
    });

    // ---- Auto-dismiss server-rendered alerts ----
    document.querySelectorAll('.alert[data-autohide]').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.classList.remove('show');
            setTimeout(function () { alertEl.remove(); }, 300);
        }, 5000);
    });

    // ---- Bootstrap tooltips activation ----
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });
    });

    // Expose a small toast helper globally for other scripts
    window.ssToast = function (icon, message) {
        if (!window.Swal) return;
        Swal.fire({ icon: icon, title: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true });
    };
})();
