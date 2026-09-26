/**
 * StockSense Pro - Core Client-Side Interactions (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Hide Preloader smoothly
    const preloader = document.getElementById('page-preloader');
    if (preloader) {
        setTimeout(() => {
            preloader.classList.add('fade-out');
            setTimeout(() => preloader.remove(), 400);
        }, 150);
    }

    // 2. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Close sidebar on click outside on mobile
    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // 3. Generic Client-side Table Filter & Search
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const targetTableSelector = input.getAttribute('data-table-search');
        const targetTable = document.querySelector(targetTableSelector);

        if (targetTable) {
            input.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = targetTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const rowText = row.innerText.toLowerCase();
                    if (rowText.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });

    // 4. Status Filter Buttons
    const filterButtons = document.querySelectorAll('[data-filter-status]');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const status = this.getAttribute('data-filter-status').toLowerCase();
            const targetTable = document.querySelector(this.getAttribute('data-filter-target') || 'table');
            
            // Toggle active class on buttons
            document.querySelectorAll('[data-filter-status]').forEach(b => b.classList.remove('active', 'btn-brand', 'text-white'));
            this.classList.add('active', 'btn-brand', 'text-white');

            if (!targetTable) return;
            const rows = targetTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 5. Check for flash alerts to display via SweetAlert2
    if (typeof Swal !== 'undefined') {
        const flashSuccess = document.body.getAttribute('data-flash-success');
        const flashError = document.body.getAttribute('data-flash-error');

        if (flashSuccess) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: flashSuccess,
                confirmButtonColor: '#00A09D',
                timer: 3500,
                timerProgressBar: true
            });
        }

        if (flashError) {
            Swal.fire({
                icon: 'error',
                title: 'Attention',
                text: flashError,
                confirmButtonColor: '#714B67'
            });
        }
    }
});

/**
 * 1-Click Demo Credential Auto-Filler
 */
function fillDemoCredentials(email, password, roleLabel) {
    const emailField = document.getElementById('email') || document.querySelector('input[type="email"]');
    const passField = document.getElementById('password') || document.querySelector('input[type="password"]');

    if (emailField && passField) {
        emailField.value = email;
        passField.value = password;

        // Visual flash effect on inputs
        emailField.classList.add('is-valid');
        passField.classList.add('is-valid');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: roleLabel + ' Demo Loaded!',
                html: `Populated <b>${email}</b><br><small class="text-muted">Click "Sign In" to enter the system.</small>`,
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }
    }
}

/**
 * SweetAlert Confirm Dialog for Form Submissions
 */
function confirmAction(e, message = "Are you sure you want to proceed with this operation?") {
    e.preventDefault();
    const form = e.target.closest('form');
    if (!form) return;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirm Operation',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00A09D',
            cancelButtonColor: '#6C757D',
            confirmButtonText: 'Yes, Confirm!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    } else {
        if (confirm(message)) {
            form.submit();
        }
    }
}
