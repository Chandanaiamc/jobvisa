/**
 * Progressive enhancement for public jobs board.
 * Uses /api/v1/jobs for filter/pagination updates; SSR remains the baseline.
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

  function apiBase() {
    var board = document.querySelector('[data-jobs-board]');
    if (board && board.getAttribute('data-api-url')) {
      return board.getAttribute('data-api-url');
    }
    var base = document.documentElement.getAttribute('data-app-base') || '';
    return base.replace(/\/$/, '') + '/api/v1/jobs';
  }

  function detailBase() {
    var board = document.querySelector('[data-jobs-board]');
    return (board && board.getAttribute('data-detail-base')) || '/jobs';
  }

  function formParams(form) {
    var data = new FormData(form);
    var params = new URLSearchParams();
    data.forEach(function (value, key) {
      if (value !== null && String(value).trim() !== '') {
        params.set(key, String(value));
      }
    });
    return params;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderJobs(jobs, listEl) {
    if (!jobs || jobs.length === 0) {
      listEl.innerHTML = '<p class="jobs-empty" data-jobs-empty>No published jobs match these filters.</p>';
      return;
    }
    var base = detailBase().replace(/\/$/, '');
    listEl.innerHTML = jobs.map(function (job) {
      var href = base + '/' + encodeURIComponent(job.id);
      var meta = escapeHtml(job.country_name || '—');
      if (job.job_type_name) {
        meta += ' <span>· ' + escapeHtml(job.job_type_name) + '</span>';
      }
      if (job.visa_sponsorship) {
        meta += ' <span class="job-card__badge">Visa sponsorship</span>';
      }
      var summary = job.summary
        ? '<p class="job-card__summary">' + escapeHtml(job.summary) + '</p>'
        : '';
      return (
        '<article class="job-card">' +
        '<h2 class="job-card__title"><a href="' + href + '">' + escapeHtml(job.title || '') + '</a></h2>' +
        '<p class="job-card__meta">' + meta + '</p>' +
        summary +
        '<a class="job-card__link" href="' + href + '">View details</a>' +
        '</article>'
      );
    }).join('');
  }

  function renderPagination(pagination, params, nav) {
    var page = pagination.page || 1;
    var totalPages = pagination.total_pages || 1;
    nav.setAttribute('data-page', String(page));
    nav.setAttribute('data-total-pages', String(totalPages));
    if (totalPages <= 1) {
      nav.innerHTML = '';
      return;
    }
    var html = '';
    if (page > 1) {
      html += '<button type="button" class="jobs-btn jobs-btn--ghost" data-page-link="' + (page - 1) + '">Previous</button>';
    }
    html += '<span class="jobs-pagination__label">Page ' + page + ' of ' + totalPages + '</span>';
    if (page < totalPages) {
      html += '<button type="button" class="jobs-btn jobs-btn--ghost" data-page-link="' + (page + 1) + '">Next</button>';
    }
    nav.innerHTML = html;
  }

  function setStatus(el, text, kind) {
    if (!el) {
      return;
    }
    el.textContent = text;
    el.className = 'jobs-status' + (kind ? ' is-' + kind : '');
  }

  function fetchJobs(params, ui) {
    setStatus(ui.status, 'Updating results…', 'loading');
    var url = apiBase() + '?' + params.toString();
    var request = window.JobVisaApi && window.JobVisaApi.get
      ? window.JobVisaApi.get('/api/v1/jobs?' + params.toString(), { retryOnAuth: false })
      : fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
          .then(function (res) {
            return res.json().then(function (json) {
              return { ok: res.ok, status: res.status, json: json };
            });
          });

    return request.then(function (result) {
      if (!result.ok || !result.json || !result.json.success) {
        setStatus(ui.status, 'Unable to load jobs. Showing last results.', 'error');
        return;
      }
      var data = result.json.data || {};
      var meta = result.json.meta || {};
      var jobs = data.jobs || [];
      var pagination = meta.pagination || { page: 1, per_page: 12, total: jobs.length, total_pages: 1 };
      renderJobs(jobs, ui.list);
      renderPagination(pagination, params, ui.pagination);
      var total = pagination.total || 0;
      var label = total === 1 ? '1 published role' : total + ' published roles';
      if (params.get('q') || params.get('country_id') || params.get('job_type_id')) {
        label += ' matching your filters';
      }
      setStatus(ui.status, label, '');

      var qs = params.toString();
      var nextUrl = (document.documentElement.getAttribute('data-app-base') || '').replace(/\/$/, '') + '/jobs' + (qs ? '?' + qs : '');
      if (window.history && window.history.replaceState) {
        window.history.replaceState({}, '', nextUrl);
      }
    }).catch(function () {
      setStatus(ui.status, 'Network error while loading jobs.', 'error');
    });
  }

  ready(function () {
    var board = document.querySelector('[data-jobs-board]');
    var form = document.querySelector('[data-jobs-filters]');
    if (!board || !form) {
      return;
    }

    var ui = {
      list: document.querySelector('[data-jobs-list]'),
      status: document.querySelector('[data-jobs-status]'),
      pagination: document.querySelector('[data-jobs-pagination]')
    };
    if (!ui.list || !ui.pagination) {
      return;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var params = formParams(form);
      params.set('page', '1');
      fetchJobs(params, ui);
    });

    ui.pagination.addEventListener('click', function (event) {
      var target = event.target;
      if (!target || !target.getAttribute) {
        return;
      }
      var page = target.getAttribute('data-page-link');
      if (!page) {
        return;
      }
      event.preventDefault();
      var params = formParams(form);
      params.set('page', page);
      fetchJobs(params, ui);
    });
  });
})();
