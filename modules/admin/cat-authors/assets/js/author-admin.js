(function () {
  'use strict';

  function slugify(value) {
    return String(value || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9-]+/g, '-')
      .replace(/-{2,}/g, '-')
      .replace(/^-|-$/g, '');
  }

  function bindSlug(form) {
    var first = form.querySelector('[data-author-first-name]');
    var last = form.querySelector('[data-author-last-name]');
    var display = form.querySelector('[data-author-display-name]');
    var slug = form.querySelector('[data-author-slug]');

    if (!display || !slug) {
      return;
    }

    function displayLocked() {
      return display.dataset.locked === '1';
    }

    function slugLocked() {
      return slug.dataset.locked === '1';
    }

    function syncDisplay() {
      if (displayLocked()) {
        return;
      }
      var value = [first ? first.value : '', last ? last.value : '']
        .join(' ')
        .trim()
        .replace(/\s{2,}/g, ' ');
      display.value = value;
    }

    function syncSlug() {
      if (slugLocked()) {
        return;
      }
      slug.value = slugify(display.value);
    }

    if ((display.value || '').trim() !== '') {
      display.dataset.locked = '1';
    }
    if ((slug.value || '').trim() !== '') {
      slug.dataset.locked = '1';
    }

    if (first) {
      first.addEventListener('input', function () {
        syncDisplay();
        syncSlug();
      });
    }

    if (last) {
      last.addEventListener('input', function () {
        syncDisplay();
        syncSlug();
      });
    }

    display.addEventListener('input', function () {
      display.dataset.locked = display.value.trim() === '' ? '0' : '1';
      syncSlug();
    });

    slug.addEventListener('input', function () {
      slug.dataset.locked = slug.value.trim() === '' ? '0' : '1';
    });

    if ((slug.value || '').trim() === '' && (display.value || '').trim() !== '') {
      syncSlug();
    }
  }

  function bindSocialStates(form) {
    var activeLabel = form.getAttribute('data-social-active-label') || 'Actif';
    var inactiveLabel = form.getAttribute('data-social-inactive-label') || 'Inactif';

    function paintRow(input) {
      var row = input.closest('[data-author-social-row]');
      if (!row) {
        return;
      }
      var state = row.querySelector('[data-author-social-state]');
      if (!state) {
        return;
      }

      var on = input.value.trim() !== '';
      state.classList.remove('text-bg-success', 'text-bg-danger');
      state.classList.add(on ? 'text-bg-success' : 'text-bg-danger');
      state.textContent = on ? activeLabel : inactiveLabel;

      input.classList.toggle('is-valid', on);
      input.classList.remove('is-invalid');
    }

    form.querySelectorAll('[data-author-social-url]').forEach(function (input) {
      paintRow(input);
      input.addEventListener('input', function () {
        paintRow(input);
      });
    });
  }

  document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelector('[data-author-slug]')) {
      return;
    }
    bindSlug(form);
    bindSocialStates(form);
  });
})();
