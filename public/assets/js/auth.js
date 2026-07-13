document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-toggle-password');
            var input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            var showing = input.getAttribute('type') === 'text';
            input.setAttribute('type', showing ? 'password' : 'text');
            button.textContent = showing ? 'Show' : 'Hide';
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    });
});
