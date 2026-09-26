/**
 * StockSense - Auth Pages JavaScript
 */
(function () {
    'use strict';

    // ---- Demo credential autofill ----
    // Usage: <div class="demo-cred-chip" data-email="manager@stocksense.in" data-password="Demo@123">
    document.addEventListener('click', function (e) {
        var chip = e.target.closest('.demo-cred-chip');
        if (!chip) return;

        var emailField = document.querySelector('#loginEmail, #email');
        var passField = document.querySelector('#loginPassword, #password');
        if (emailField) {
            emailField.value = chip.getAttribute('data-email');
            emailField.classList.add('is-valid');
        }
        if (passField) {
            passField.value = chip.getAttribute('data-password');
            passField.classList.add('is-valid');
        }
        window.ssToast && window.ssToast('success', 'Demo credentials filled — click Sign In');
    });

    // ---- Password visibility toggle ----
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.toggle-password');
        if (!toggle) return;
        var targetId = toggle.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.innerHTML = isPassword ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
    });

    // ---- Password strength meter ----
    var pwInput = document.getElementById('registerPassword');
    if (pwInput) {
        var bar = document.querySelector('.password-strength-bar');
        var label = document.getElementById('strengthLabel');
        pwInput.addEventListener('input', function () {
            var val = pwInput.value;
            var score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            var levels = [
                { pct: 0, color: '#E3E8F0', text: '' },
                { pct: 25, color: '#DC2626', text: 'Weak' },
                { pct: 50, color: '#D97706', text: 'Fair' },
                { pct: 75, color: '#0891B2', text: 'Good' },
                { pct: 100, color: '#16A34A', text: 'Strong' },
            ];
            var level = levels[score];
            if (bar) { bar.style.width = level.pct + '%'; bar.style.background = level.color; }
            if (label) { label.textContent = level.text; label.style.color = level.color; }
        });
    }

    // ---- Role selection cards (register form) ----
    document.querySelectorAll('.role-select-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.role-select-card').forEach(function (c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            var input = document.getElementById('role_id');
            if (input) input.value = card.getAttribute('data-role-id');
        });
    });

    // ---- OTP input auto-advance ----
    var otpInputs = document.querySelectorAll('.otp-input-group input');
    if (otpInputs.length) {
        otpInputs.forEach(function (input, idx) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
                if (input.value && otpInputs[idx + 1]) otpInputs[idx + 1].focus();
                syncOtpHidden();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !input.value && otpInputs[idx - 1]) {
                    otpInputs[idx - 1].focus();
                }
            });
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                var text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                text.split('').forEach(function (ch, i) {
                    if (otpInputs[i]) otpInputs[i].value = ch;
                });
                syncOtpHidden();
                otpInputs[Math.min(text.length, otpInputs.length - 1)].focus();
            });
        });
    }
    function syncOtpHidden() {
        var hidden = document.getElementById('otp_code');
        if (!hidden) return;
        var val = '';
        otpInputs.forEach(function (inp) { val += inp.value; });
        hidden.value = val;
    }

    // ---- Client-side form validation feedback (Bootstrap) ----
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ---- Confirm password match check ----
    var confirmField = document.getElementById('confirmPassword');
    if (confirmField && pwInput) {
        confirmField.addEventListener('input', function () {
            if (confirmField.value && confirmField.value !== pwInput.value) {
                confirmField.setCustomValidity('Passwords do not match');
            } else {
                confirmField.setCustomValidity('');
            }
        });
    }
})();
