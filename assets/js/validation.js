const registrationForm = document.getElementById("registrationForm");
const passwordInput = document.querySelector('input[name="password"]');
const strengthBar = document.getElementById('strength-bar');
const strengthText = document.getElementById('strength-text');

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        let strength = 0;

        // Validation Rules
        const hasUpperCase = /[A-Z]/.test(val);
        const hasLowerCase = /[a-z]/.test(val);
        const hasNumbers = /\d/.test(val);
        const hasSymbols = /[^A-Za-z0-9]/.test(val);
        const hasMinimumLength = val.length >= 8;

        if (hasMinimumLength) strength += 25;
        if (hasUpperCase && hasLowerCase) strength += 25;
        if (hasNumbers) strength += 25;
        if (hasSymbols) strength += 25;

        strengthBar.style.width = strength + '%';

        if (strength <= 25) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.innerHTML = 'Weak (Needs numbers/symbols)';
        } else if (strength <= 50) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.innerHTML = 'Fair (Add uppercase/symbols)';
        } else if (strength <= 75) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.innerHTML = 'Good (Almost there)';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.innerHTML = 'Strong';
        }
    });
}

// Updated Form Submission Validation
if (registrationForm) {
    registrationForm.onsubmit = function(event) {
        const val = passwordInput.value;
        const captchaResponse = grecaptcha.getResponse();

        // Strict Password Requirements Check
        const strongRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\\$%\\^&\\*])(?=.{8,})");

        if (!strongRegex.test(val)) {
            alert("Password must contain at least 8 characters, including uppercase, lowercase, a number, and a symbol.");
            event.preventDefault();
            return false;
        }

        if (captchaResponse.length == 0) { 
            alert("Please verify that you are not a robot.");
            event.preventDefault(); 
            return false;
        }
        return true;
    };
}