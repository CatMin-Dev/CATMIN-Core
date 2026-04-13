(() => {
  'use strict';

  const form = document.getElementById('media-link-sync-form');
  if (!form) return;

  const featured = document.getElementById('featured-media-id');
  const gallery = document.getElementById('gallery-media-ids');
  const social = document.getElementById('social-media-id');
  const cards = Array.from(document.querySelectorAll('[data-media-id]'));

  if (!featured || !gallery || !social || cards.length === 0) return;

  const parseList = (raw) => String(raw || '')
    .split(/[\s,]+/)
    .map((x) => parseInt(x, 10))
    .filter((x) => Number.isInteger(x) && x > 0);

  const syncCardStates = () => {
    const featuredId = parseInt(featured.value || '0', 10);
    const socialId = parseInt(social.value || '0', 10);
    const galleryIds = new Set(parseList(gallery.value));

    cards.forEach((card) => {
      const id = parseInt(card.getAttribute('data-media-id') || '0', 10);
      card.classList.remove('border-primary', 'border-success', 'border-info');
      card.classList.add('border');

      if (id === featuredId) {
        card.classList.add('border-primary');
      } else if (id === socialId) {
        card.classList.add('border-info');
      } else if (galleryIds.has(id)) {
        card.classList.add('border-success');
      }
    });
  };

  cards.forEach((card) => {
    card.style.cursor = 'pointer';
    card.title = 'Click: featured | Shift+Click: social | Alt+Click: add/remove gallery';
    card.addEventListener('click', (event) => {
      const id = parseInt(card.getAttribute('data-media-id') || '0', 10);
      if (id <= 0) return;

      if (event.shiftKey) {
        social.value = String(id);
      } else if (event.altKey) {
        const values = parseList(gallery.value);
        const set = new Set(values);
        if (set.has(id)) {
          set.delete(id);
        } else {
          set.add(id);
        }
        gallery.value = Array.from(set).join(',');
      } else {
        featured.value = String(id);
      }

      syncCardStates();
    });
  });

  const filterButtons = Array.from(document.querySelectorAll('.cat-media-filter-btn[data-media-filter]'));
  const applyFilter = (filter) => {
    const wanted = String(filter || 'all');
    cards.forEach((card) => {
      const type = String(card.getAttribute('data-media-type') || '').toLowerCase();
      const show = wanted === 'all' || wanted === type;
      card.style.display = show ? '' : 'none';
    });
    filterButtons.forEach((btn) => {
      const current = String(btn.getAttribute('data-media-filter') || 'all');
      if (current === wanted) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  };
  filterButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      applyFilter(String(btn.getAttribute('data-media-filter') || 'all'));
    });
  });

  featured.addEventListener('input', syncCardStates);
  social.addEventListener('input', syncCardStates);
  gallery.addEventListener('input', syncCardStates);
  syncCardStates();
  applyFilter('all');
})();
