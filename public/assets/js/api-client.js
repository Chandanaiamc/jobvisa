/**
 * JobVisa same-origin API client (credentials + CSRF).
 * Access/refresh live in httpOnly cookies — never read or log token values.
 */
(function (global) {
  'use strict';

  var REFRESHING = null;

  function csrfFromDocument() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) {
      return meta.getAttribute('content');
    }
    var input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
  }

  function rotateCsrf(token) {
    if (!token) {
      return;
    }
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
      meta.setAttribute('content', token);
    }
    document.querySelectorAll('input[name="_token"]').forEach(function (el) {
      el.value = token;
    });
  }

  function appBase() {
    var base = document.documentElement.getAttribute('data-app-base');
    if (base) {
      return base.replace(/\/$/, '');
    }
    // Fallback: /jobvisa/public from a known script path or empty.
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      var src = scripts[i].src || '';
      var idx = src.indexOf('/public/assets/');
      if (idx !== -1) {
        return src.substring(0, idx + '/public'.length);
      }
    }
    return '';
  }

  function url(path) {
    var p = path.charAt(0) === '/' ? path : '/' + path;
    return appBase() + p;
  }

  /**
   * @param {string} method
   * @param {string} path
   * @param {object|null} body
   * @param {{retryOnAuth?: boolean}} options
   */
  function request(method, path, body, options) {
    options = options || {};
    var retryOnAuth = options.retryOnAuth !== false;
    var headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfFromDocument()
    };
    var init = {
      method: method,
      headers: headers,
      credentials: 'same-origin'
    };

    if (body !== null && body !== undefined && method !== 'GET' && method !== 'HEAD') {
      headers['Content-Type'] = 'application/json';
      // Never include access_token / refresh_token in client bodies — cookies carry them.
      init.body = JSON.stringify(body);
    }

    return fetch(url(path), init).then(function (response) {
      return response.json().catch(function () {
        return { success: false, error: { code: 'invalid_json', message: 'Invalid response.' } };
      }).then(function (json) {
        if (json && json.csrf_token) {
          rotateCsrf(json.csrf_token);
        }
        return { status: response.status, ok: response.ok, json: json };
      });
    }).then(function (result) {
      var code = result.json && result.json.error && result.json.error.code;
      var isAuthFail =
        result.status === 401 &&
        (code === 'unauthorized' || code === 'token_expired' || code === 'token_revoked');

      if (!isAuthFail || !retryOnAuth || path.indexOf('/auth/api/refresh') !== -1) {
        return result;
      }

      return refreshOnce().then(function (refreshed) {
        if (!refreshed) {
          return result;
        }
        return request(method, path, body, { retryOnAuth: false });
      });
    });
  }

  function refreshOnce() {
    if (REFRESHING) {
      return REFRESHING;
    }
    REFRESHING = request('POST', '/auth/api/refresh', {}, { retryOnAuth: false })
      .then(function (result) {
        REFRESHING = null;
        return !!(result.ok && result.json && result.json.success);
      })
      .catch(function () {
        REFRESHING = null;
        return false;
      });
    return REFRESHING;
  }

  global.JobVisaApi = {
    request: request,
    get: function (path, options) {
      return request('GET', path, null, options);
    },
    post: function (path, body, options) {
      return request('POST', path, body || {}, options);
    },
    login: function (payload) {
      return request('POST', '/auth/api/login', payload, { retryOnAuth: false });
    },
    me: function () {
      return request('GET', '/auth/api/me', null, { retryOnAuth: true });
    },
    refresh: function () {
      return refreshOnce();
    },
    logout: function () {
      return request('POST', '/auth/api/logout', {}, { retryOnAuth: false });
    },
    url: url,
    csrf: csrfFromDocument
  };
})(window);
