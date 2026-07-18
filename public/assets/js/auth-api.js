/**
 * Progressive enhancement for the login form → /auth/api/login.
 * Falls back to classic form POST when JS is unavailable.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    // Password visibility (existing behaviour)
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

    var form = document.querySelector('[data-api-auth-login]');
    if (!form || !window.JobVisaApi) {
      return;
    }

    var statusEl = document.querySelector('[data-api-auth-status]');
    var submitBtn = form.querySelector('[type="submit"]');

    function setStatus(message, kind) {
      if (!statusEl) {
        return;
      }
      statusEl.hidden = !message;
      statusEl.textContent = message || '';
      statusEl.className = 'auth-api-status' + (kind ? ' auth-api-status--' + kind : '');
      statusEl.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    }

    function clearFieldErrors() {
      form.querySelectorAll('.field-error[data-api-error]').forEach(function (el) {
        el.remove();
      });
      form.querySelectorAll('.form-field.is-invalid').forEach(function (el) {
        el.classList.remove('is-invalid');
      });
      form.querySelectorAll('[aria-invalid="true"]').forEach(function (el) {
        el.setAttribute('aria-invalid', 'false');
      });
    }

    function showFieldErrors(details) {
      if (!details || typeof details !== 'object') {
        return;
      }
      Object.keys(details).forEach(function (field) {
        var messages = details[field];
        var msg = Array.isArray(messages) ? messages[0] : String(messages);
        var input = form.querySelector('[name="' + field + '"]');
        if (!input) {
          return;
        }
        var wrap = input.closest('.form-field');
        if (wrap) {
          wrap.classList.add('is-invalid');
        }
        input.setAttribute('aria-invalid', 'true');
        var p = document.createElement('p');
        p.className = 'field-error';
        p.setAttribute('data-api-error', '1');
        p.id = field + '-api-error';
        p.textContent = msg;
        input.setAttribute('aria-describedby', p.id);
        if (wrap) {
          wrap.appendChild(p);
        }
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      clearFieldErrors();
      setStatus('Signing in…', 'loading');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy', 'true');
      }

      var email = (form.querySelector('[name="email"]') || {}).value || '';
      var password = (form.querySelector('[name="password"]') || {}).value || '';
      var remember = !!(form.querySelector('[name="remember"]') || {}).checked;

      window.JobVisaApi.login({
        email: email,
        password: password,
        remember: remember,
        device_name: 'Browser',
        platform: 'web'
      }).then(function (result) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.removeAttribute('aria-busy');
        }

        if (!result.ok || !result.json || !result.json.success) {
          var err = (result.json && result.json.error) || {};
          var message = err.message || 'Sign in failed.';
          if (err.code === 'validation_error') {
            showFieldErrors(err.details || {});
            setStatus(message, 'error');
            return;
          }
          if (err.code === 'account_locked') {
            setStatus(message, 'error');
            return;
          }
          setStatus(message === 'Unauthenticated.' ? 'Invalid email or password.' : message, 'error');
          return;
        }

        var data = result.json.data || {};
        setStatus('Signed in. Redirecting…', 'success');

        if (data.email_verified === false) {
          window.location.href = window.JobVisaApi.url('/email/verify');
          return;
        }

        var path = (data.redirect && data.redirect.path) ? data.redirect.path : '/';
        window.location.href = window.JobVisaApi.url(path);
      }).catch(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.removeAttribute('aria-busy');
        }
        setStatus('Network error. Please try again.', 'error');
      });
    });
  });
})();
