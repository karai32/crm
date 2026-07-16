(function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        var input = document.getElementById(button.dataset.passwordToggle);
        if (!input) { return; }

        button.addEventListener('click', function () {
            var showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
            button.setAttribute('aria-label', showPassword
                ? (button.dataset.hideLabel || 'Hide password')
                : (button.dataset.showLabel || 'Show password'));
        });
    });
}());
