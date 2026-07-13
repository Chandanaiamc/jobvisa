(() => {
  const country = document.querySelector('[data-city-filter]');
  const city = document.querySelector('#current_city_id');
  if (country && city) {
    const options = Array.from(city.querySelectorAll('option'));

    const filter = () => {
      const selected = country.value;
      options.forEach((opt) => {
        if (!opt.value) {
          opt.hidden = false;
          return;
        }
        const match = !selected || opt.getAttribute('data-country') === selected;
        opt.hidden = !match;
        if (!match && opt.selected) {
          city.value = '';
        }
      });
    };

    country.addEventListener('change', filter);
    filter();
  }
})();

(() => {
  const panel = document.querySelector('#professional-panel');
  const form = document.querySelector('#professional-form');
  if (!panel || !form || panel.getAttribute('data-can-edit') !== '1') {
    return;
  }

  const url = panel.getAttribute('data-autosave-url');
  if (!url) {
    return;
  }

  const statusEl = document.querySelector('#autosave-status');
  const scoreEl = document.querySelector('#resume-completion-score');
  const barEl = document.querySelector('#resume-completion-bar');
  let timer = null;
  let inFlight = false;
  let pending = false;

  const setStatus = (text) => {
    if (statusEl) {
      statusEl.textContent = text;
    }
  };

  const csrfInput = () => form.querySelector('input[name="_token"]');

  const save = async () => {
    if (inFlight) {
      pending = true;
      return;
    }
    inFlight = true;
    pending = false;
    setStatus('Saving…');

    try {
      const body = new FormData(form);
      const response = await fetch(url, {
        method: 'POST',
        body,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      const data = await response.json().catch(() => ({}));
      const token = csrfInput();
      if (data.csrf_token && token) {
        token.value = data.csrf_token;
      }

      if (!response.ok || !data.success) {
        setStatus(data.message || 'Autosave paused — fix highlighted fields.');
        return;
      }

      if (typeof data.completion === 'number') {
        if (scoreEl) {
          scoreEl.textContent = data.completion + '%';
        }
        if (barEl) {
          barEl.style.width = data.completion + '%';
          barEl.parentElement?.setAttribute('aria-valuenow', String(data.completion));
        }
      }
      setStatus(data.message || 'Draft autosaved.');
    } catch (err) {
      setStatus('Autosave unavailable. Use Save when ready.');
    } finally {
      inFlight = false;
      if (pending) {
        schedule();
      }
    }
  };

  const schedule = () => {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(save, 900);
  };

  form.addEventListener('input', schedule);
  form.addEventListener('change', schedule);
})();

(() => {
  const current = document.querySelector('#is_current');
  const end = document.querySelector('#end_date');
  if (!current || !end) {
    return;
  }
  const sync = () => {
    end.disabled = current.checked;
    end.required = !current.checked;
    if (current.checked) {
      end.value = '';
    }
  };
  current.addEventListener('change', sync);
  sync();
})();

(() => {
  const noExpire = document.querySelector('#does_not_expire');
  const expiry = document.querySelector('#expiry_date');
  if (!noExpire || !expiry) {
    return;
  }
  const sync = () => {
    expiry.disabled = noExpire.checked;
    expiry.required = !noExpire.checked;
    if (noExpire.checked) {
      expiry.value = '';
    }
  };
  noExpire.addEventListener('change', sync);
  sync();
})();

(() => {
  const panel = document.querySelector('#skills-panel');
  const search = document.querySelector('#skill_search');
  const hidden = document.querySelector('#skill_id');
  const list = document.querySelector('#skill-suggestions');
  if (!panel || !search || !hidden || !list || search.readOnly) {
    return;
  }

  const url = panel.getAttribute('data-search-url');
  if (!url) {
    return;
  }

  let timer = null;
  let active = -1;
  let results = [];

  const hide = () => {
    list.hidden = true;
    list.innerHTML = '';
    active = -1;
  };

  const pick = (item) => {
    hidden.value = String(item.id);
    search.value = item.name;
    hide();
  };

  const render = () => {
    list.innerHTML = '';
    if (results.length === 0) {
      hide();
      return;
    }
    results.forEach((item, index) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.textContent = item.name;
      if (index === active) {
        li.classList.add('is-active');
      }
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        pick(item);
      });
      list.appendChild(li);
    });
    list.hidden = false;
  };

  const fetchResults = async (q) => {
    try {
      const response = await fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      results = Array.isArray(data.results) ? data.results : [];
      active = results.length ? 0 : -1;
      render();
    } catch (err) {
      hide();
    }
  };

  search.addEventListener('input', () => {
    hidden.value = '';
    const q = search.value.trim();
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fetchResults(q), 220);
  });

  search.addEventListener('keydown', (e) => {
    if (list.hidden || results.length === 0) {
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      active = (active + 1) % results.length;
      render();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      active = (active - 1 + results.length) % results.length;
      render();
    } else if (e.key === 'Enter' && active >= 0) {
      e.preventDefault();
      pick(results[active]);
    } else if (e.key === 'Escape') {
      hide();
    }
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target)) {
      hide();
    }
  });
})();

(() => {
  const panel = document.querySelector('#languages-panel');
  const search = document.querySelector('#language_search');
  const hidden = document.querySelector('#language_id');
  const list = document.querySelector('#language-suggestions');
  if (!panel || !search || !hidden || !list || search.readOnly) {
    return;
  }

  const url = panel.getAttribute('data-search-url');
  if (!url) {
    return;
  }

  let timer = null;
  let active = -1;
  let results = [];

  const hide = () => {
    list.hidden = true;
    list.innerHTML = '';
    active = -1;
  };

  const pick = (item) => {
    hidden.value = String(item.id);
    search.value = item.name;
    hide();
  };

  const render = () => {
    list.innerHTML = '';
    if (results.length === 0) {
      hide();
      return;
    }
    results.forEach((item, index) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.textContent = item.name + (item.code ? ' (' + item.code + ')' : '');
      if (index === active) {
        li.classList.add('is-active');
      }
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        pick(item);
      });
      list.appendChild(li);
    });
    list.hidden = false;
  };

  const fetchResults = async (q) => {
    try {
      const response = await fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      results = Array.isArray(data.results) ? data.results : [];
      active = results.length ? 0 : -1;
      render();
    } catch (err) {
      hide();
    }
  };

  search.addEventListener('input', () => {
    hidden.value = '';
    const q = search.value.trim();
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fetchResults(q), 220);
  });

  search.addEventListener('keydown', (e) => {
    if (list.hidden || results.length === 0) {
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      active = (active + 1) % results.length;
      render();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      active = (active - 1 + results.length) % results.length;
      render();
    } else if (e.key === 'Enter' && active >= 0) {
      e.preventDefault();
      pick(results[active]);
    } else if (e.key === 'Escape') {
      hide();
    }
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target)) {
      hide();
    }
  });
})();

(() => {
  const current = document.querySelector('#currently_working');
  const form = document.querySelector('#project-form');
  const end = form ? form.querySelector('#end_date') : null;
  if (!current || !end) {
    return;
  }
  const sync = () => {
    end.disabled = current.checked;
    if (current.checked) {
      end.value = '';
    }
  };
  current.addEventListener('change', sync);
  sync();
})();

(() => {
  const input = document.querySelector('#project-form #technologies');
  const preview = document.querySelector('#tech-tags');
  if (!input || !preview) {
    return;
  }
  const escape = (value) => value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
  const render = () => {
    const parts = input.value.split(/[,;|]+/).map((s) => s.trim()).filter(Boolean);
    preview.innerHTML = parts.map((t) => `<span class="tech-tag">${escape(t)}</span>`).join('');
  };
  input.addEventListener('input', render);
  render();
})();

(() => {
  const list = document.querySelector('#project-list');
  const form = document.querySelector('#project-dnd-form');
  const orderBox = document.querySelector('#project-dnd-order');
  if (!list || !form || !orderBox || list.getAttribute('data-can-drag') !== '1') {
    return;
  }

  let dragEl = null;

  list.querySelectorAll('.project-card').forEach((card) => {
    card.addEventListener('dragstart', (e) => {
      dragEl = card;
      card.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', card.getAttribute('data-id') || '');
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('is-dragging');
      list.querySelectorAll('.is-drag-over').forEach((el) => el.classList.remove('is-drag-over'));
      dragEl = null;
    });
    card.addEventListener('dragover', (e) => {
      e.preventDefault();
      if (!dragEl || dragEl === card) {
        return;
      }
      card.classList.add('is-drag-over');
      const rect = card.getBoundingClientRect();
      const before = e.clientY < rect.top + rect.height / 2;
      list.insertBefore(dragEl, before ? card : card.nextSibling);
    });
    card.addEventListener('dragleave', () => card.classList.remove('is-drag-over'));
    card.addEventListener('drop', (e) => {
      e.preventDefault();
      card.classList.remove('is-drag-over');
      orderBox.innerHTML = '';
      list.querySelectorAll('.project-card').forEach((item) => {
        const id = item.getAttribute('data-id');
        if (!id) {
          return;
        }
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'order[]';
        hidden.value = id;
        orderBox.appendChild(hidden);
      });
      form.submit();
    });
  });
})();

(() => {
  const panel = document.querySelector('#achievements-panel');
  const input = document.querySelector('#achievement_q');
  const list = document.querySelector('#achievement-live-results');
  if (!panel || !input || !list) {
    return;
  }

  const url = panel.getAttribute('data-search-url');
  if (!url) {
    return;
  }

  let timer = null;

  const hide = () => {
    list.hidden = true;
    list.innerHTML = '';
  };

  const render = (results) => {
    list.innerHTML = '';
    if (!Array.isArray(results) || results.length === 0) {
      hide();
      return;
    }
    results.forEach((item) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      const bits = [item.title];
      if (item.issuer) {
        bits.push(item.issuer);
      }
      if (item.project_title) {
        bits.push(item.project_title);
      }
      li.textContent = bits.join(' · ');
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        window.location.href = url.replace(/\/search$/, '') + '/' + item.id + '/edit';
      });
      list.appendChild(li);
    });
    list.hidden = false;
  };

  const fetchResults = async (q) => {
    if (q.trim().length < 1) {
      hide();
      return;
    }
    try {
      const response = await fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      render(Array.isArray(data.results) ? data.results : []);
    } catch (err) {
      hide();
    }
  };

  input.addEventListener('input', () => {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fetchResults(input.value), 220);
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target)) {
      hide();
    }
  });
})();

(() => {
  const panel = document.querySelector('#achievements-panel');
  const input = document.querySelector('#achievement_q');
  const list = document.querySelector('#achievement-live-results');
  if (!panel || !input || !list) {
    return;
  }

  const url = panel.getAttribute('data-search-url');
  if (!url) {
    return;
  }

  let timer = null;

  const hide = () => {
    list.hidden = true;
    list.innerHTML = '';
  };

  const render = (results) => {
    list.innerHTML = '';
    if (!Array.isArray(results) || results.length === 0) {
      hide();
      return;
    }
    results.forEach((item) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      const bits = [item.title];
      if (item.issuer) {
        bits.push(item.issuer);
      }
      if (item.project_title) {
        bits.push(item.project_title);
      }
      li.textContent = bits.join(' · ');
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        window.location.href = url.replace(/\/search$/, '') + '/' + item.id + '/edit';
      });
      list.appendChild(li);
    });
    list.hidden = false;
  };

  const fetchResults = async (q) => {
    if (q.trim().length < 1) {
      hide();
      return;
    }
    try {
      const response = await fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      render(Array.isArray(data.results) ? data.results : []);
    } catch (err) {
      hide();
    }
  };

  input.addEventListener('input', () => {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fetchResults(input.value), 220);
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target)) {
      hide();
    }
  });
})();

(() => {
  const country = document.querySelector('[data-achievement-country]');
  const city = document.querySelector('[data-achievement-city]');
  if (!country || !city) {
    return;
  }

  const citiesUrl = country.getAttribute('data-cities-url') || '';

  const fillCities = async (countryId, selectedId) => {
    const keepSelected = selectedId || '';
    city.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Optional';
    city.appendChild(placeholder);
    if (!countryId || !citiesUrl) {
      return;
    }
    try {
      const response = await fetch(citiesUrl + '?country_id=' + encodeURIComponent(countryId), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      const results = Array.isArray(data.results) ? data.results : [];
      results.forEach((item) => {
        const opt = document.createElement('option');
        opt.value = String(item.id);
        opt.textContent = item.name;
        opt.setAttribute('data-country', String(item.country_id));
        if (String(item.id) === String(keepSelected)) {
          opt.selected = true;
        }
        city.appendChild(opt);
      });
    } catch (err) {
      // Keep empty city list on failure.
    }
  };

  country.addEventListener('change', () => {
    fillCities(country.value, '');
  });
})();

(() => {
  const panel = document.querySelector('#publications-panel');
  const input = document.querySelector('#publication_q');
  const list = document.querySelector('#publication-live-results');
  if (!panel || !input || !list) {
    return;
  }

  const url = panel.getAttribute('data-search-url');
  if (!url) {
    return;
  }

  let timer = null;

  const hide = () => {
    list.hidden = true;
    list.innerHTML = '';
  };

  const render = (results) => {
    list.innerHTML = '';
    if (!Array.isArray(results) || results.length === 0) {
      hide();
      return;
    }
    results.forEach((item) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      const bits = [item.title];
      if (item.publisher) {
        bits.push(item.publisher);
      }
      if (item.publication_year) {
        bits.push(String(item.publication_year));
      }
      li.textContent = bits.join(' · ');
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        window.location.href = url.replace(/\/search$/, '') + '/' + item.id + '/edit';
      });
      list.appendChild(li);
    });
    list.hidden = false;
  };

  const fetchResults = async (q) => {
    if (q.trim().length < 1) {
      hide();
      return;
    }
    try {
      const response = await fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      render(Array.isArray(data.results) ? data.results : []);
    } catch (err) {
      hide();
    }
  };

  input.addEventListener('input', () => {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fetchResults(input.value), 220);
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target)) {
      hide();
    }
  });
})();

(() => {
  const country = document.querySelector('[data-publication-country]');
  const city = document.querySelector('[data-publication-city]');
  if (!country || !city) {
    return;
  }

  const citiesUrl = country.getAttribute('data-cities-url') || '';

  const fillCities = async (countryId, selectedId) => {
    const keepSelected = selectedId || '';
    city.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Optional';
    city.appendChild(placeholder);
    if (!countryId || !citiesUrl) {
      return;
    }
    try {
      const response = await fetch(citiesUrl + '?country_id=' + encodeURIComponent(countryId), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      const results = Array.isArray(data.results) ? data.results : [];
      results.forEach((item) => {
        const opt = document.createElement('option');
        opt.value = String(item.id);
        opt.textContent = item.name;
        opt.setAttribute('data-country', String(item.country_id));
        if (String(item.id) === String(keepSelected)) {
          opt.selected = true;
        }
        city.appendChild(opt);
      });
    } catch (err) {
      // Keep empty city list on failure.
    }
  };

  country.addEventListener('change', () => {
    fillCities(country.value, '');
  });
})();

(() => {
  const bindLiveSearch = (panelSel, inputSel, listSel, bitKeys) => {
    const panel = document.querySelector(panelSel);
    const input = document.querySelector(inputSel);
    const list = document.querySelector(listSel);
    if (!panel || !input || !list) {
      return;
    }
    const url = panel.getAttribute('data-search-url');
    if (!url) {
      return;
    }
    let timer = null;
    const hide = () => {
      list.hidden = true;
      list.innerHTML = '';
    };
    const render = (results) => {
      list.innerHTML = '';
      if (!Array.isArray(results) || results.length === 0) {
        hide();
        return;
      }
      results.forEach((item) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        const bits = [item.title || item.name];
        bitKeys.forEach((key) => {
          if (item[key]) {
            bits.push(String(item[key]));
          }
        });
        li.textContent = bits.join(' · ');
        li.addEventListener('mousedown', (e) => {
          e.preventDefault();
          window.location.href = url.replace(/\/search$/, '') + '/' + item.id + '/edit';
        });
        list.appendChild(li);
      });
      list.hidden = false;
    };
    const fetchResults = async (q) => {
      if (q.trim().length < 1) {
        hide();
        return;
      }
      try {
        const response = await fetch(url + '?q=' + encodeURIComponent(q), {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        const data = await response.json();
        render(Array.isArray(data.results) ? data.results : []);
      } catch (err) {
        hide();
      }
    };
    input.addEventListener('input', () => {
      if (timer) {
        clearTimeout(timer);
      }
      timer = setTimeout(() => fetchResults(input.value), 220);
    });
    document.addEventListener('click', (e) => {
      if (!panel.contains(e.target)) {
        hide();
      }
    });
  };

  bindLiveSearch('#portfolio-panel', '#portfolio_q', '#portfolio-live-results', ['category', 'project_title']);
  bindLiveSearch('#references-panel', '#reference_q', '#reference-live-results', ['company', 'designation']);
})();

(() => {
  const bindCountryCity = (countrySel, citySel) => {
    const country = document.querySelector(countrySel);
    const city = document.querySelector(citySel);
    if (!country || !city) {
      return;
    }
    const citiesUrl = country.getAttribute('data-cities-url') || '';
    const fillCities = async (countryId, selectedId) => {
      const keepSelected = selectedId || '';
      city.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'Optional';
      city.appendChild(placeholder);
      if (!countryId || !citiesUrl) {
        return;
      }
      try {
        const response = await fetch(citiesUrl + '?country_id=' + encodeURIComponent(countryId), {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        const data = await response.json();
        const results = Array.isArray(data.results) ? data.results : [];
        results.forEach((item) => {
          const opt = document.createElement('option');
          opt.value = String(item.id);
          opt.textContent = item.name;
          opt.setAttribute('data-country', String(item.country_id));
          if (String(item.id) === String(keepSelected)) {
            opt.selected = true;
          }
          city.appendChild(opt);
        });
      } catch (err) {
        // Keep empty city list on failure.
      }
    };
    country.addEventListener('change', () => {
      fillCities(country.value, '');
    });
  };

  bindCountryCity('[data-portfolio-country]', '[data-portfolio-city]');
  bindCountryCity('[data-reference-country]', '[data-reference-city]');
})();
