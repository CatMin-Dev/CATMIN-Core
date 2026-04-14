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

  const cropCanvas = document.getElementById('cat-media-crop-canvas');
  const previewCanvas = document.getElementById('cat-media-crop-preview');
  const cropDataInput = document.getElementById('cat-media-crop-data');
  const cropForm = document.getElementById('cat-media-manual-crop-form');
  const presetSelect = document.getElementById('cat-media-crop-preset');

  if (!(cropCanvas instanceof HTMLCanvasElement) || !(previewCanvas instanceof HTMLCanvasElement) || !(cropDataInput instanceof HTMLInputElement) || !(cropForm instanceof HTMLFormElement) || !(presetSelect instanceof HTMLSelectElement)) {
    return;
  }

  const ctx = cropCanvas.getContext('2d');
  const pctx = previewCanvas.getContext('2d');
  if (!ctx || !pctx) return;

  const img = new Image();
  img.crossOrigin = 'anonymous';
  const imageUrl = String(cropCanvas.getAttribute('data-image-url') || '');
  if (!imageUrl) return;

  const state = {
    dragging: false,
    startX: 0,
    startY: 0,
    rect: { x: 0, y: 0, width: 1, height: 1 },
  };

  const draw = () => {
    if (!img.width || !img.height) return;
    ctx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
    ctx.drawImage(img, 0, 0, cropCanvas.width, cropCanvas.height);

    const r = state.rect;
    ctx.save();
    ctx.strokeStyle = '#00bcd4';
    ctx.lineWidth = 2;
    ctx.fillStyle = 'rgba(0,188,212,0.20)';
    ctx.fillRect(r.x, r.y, r.width, r.height);
    ctx.strokeRect(r.x, r.y, r.width, r.height);
    ctx.restore();
  };

  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

  const selectedPreset = () => {
    const opt = presetSelect.options[presetSelect.selectedIndex];
    if (!opt) return { w: 1, h: 1, ratioLocked: false };
    return {
      w: Math.max(1, parseInt(opt.getAttribute('data-width') || '1', 10)),
      h: Math.max(1, parseInt(opt.getAttribute('data-height') || '1', 10)),
      ratioLocked: String(opt.getAttribute('data-ratio-locked') || '0') === '1',
    };
  };

  const updatePreviewAndJson = () => {
    const sp = selectedPreset();
    const scaleX = img.width / cropCanvas.width;
    const scaleY = img.height / cropCanvas.height;
    const sourceX = Math.round(state.rect.x * scaleX);
    const sourceY = Math.round(state.rect.y * scaleY);
    const sourceW = Math.round(state.rect.width * scaleX);
    const sourceH = Math.round(state.rect.height * scaleY);

    previewCanvas.width = sp.w;
    previewCanvas.height = sp.h;
    pctx.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
    pctx.drawImage(img, sourceX, sourceY, sourceW, sourceH, 0, 0, previewCanvas.width, previewCanvas.height);

    cropDataInput.value = JSON.stringify({
      x: sourceX,
      y: sourceY,
      width: sourceW,
      height: sourceH,
      target_width: sp.w,
      target_height: sp.h,
      rotation: 0,
    });
  };

  const toCanvasPos = (event) => {
    const rect = cropCanvas.getBoundingClientRect();
    return {
      x: clamp(event.clientX - rect.left, 0, rect.width),
      y: clamp(event.clientY - rect.top, 0, rect.height),
    };
  };

  const syncSelection = (current) => {
    const sp = selectedPreset();
    let x = Math.min(state.startX, current.x);
    let y = Math.min(state.startY, current.y);
    let w = Math.abs(current.x - state.startX);
    let h = Math.abs(current.y - state.startY);

    if (sp.ratioLocked) {
      const ratio = sp.w / Math.max(1, sp.h);
      if (h === 0 || w / h > ratio) {
        h = w / ratio;
      } else {
        w = h * ratio;
      }

      if (current.x < state.startX) {
        x = state.startX - w;
      }
      if (current.y < state.startY) {
        y = state.startY - h;
      }
    }

    x = clamp(x, 0, cropCanvas.width - 1);
    y = clamp(y, 0, cropCanvas.height - 1);
    w = clamp(w, 1, cropCanvas.width - x);
    h = clamp(h, 1, cropCanvas.height - y);

    state.rect = {
      x: Math.round(x),
      y: Math.round(y),
      width: Math.round(w),
      height: Math.round(h),
    };

    draw();
    updatePreviewAndJson();
  };

  cropCanvas.addEventListener('mousedown', (event) => {
    const pos = toCanvasPos(event);
    state.dragging = true;
    state.startX = pos.x;
    state.startY = pos.y;
    state.rect = { x: Math.round(pos.x), y: Math.round(pos.y), width: 1, height: 1 };
    draw();
  });

  window.addEventListener('mousemove', (event) => {
    if (!state.dragging) return;
    syncSelection(toCanvasPos(event));
  });

  window.addEventListener('mouseup', () => {
    if (!state.dragging) return;
    state.dragging = false;
    updatePreviewAndJson();
  });

  presetSelect.addEventListener('change', updatePreviewAndJson);
  cropForm.addEventListener('submit', (event) => {
    if (!cropDataInput.value || cropDataInput.value === '{}') {
      event.preventDefault();
      window.alert('Select a crop area first.');
    }
  });

  img.onload = () => {
    const maxW = Math.min(900, Math.max(320, img.width));
    const ratio = img.height / Math.max(1, img.width);
    cropCanvas.width = maxW;
    cropCanvas.height = Math.max(240, Math.round(maxW * ratio));

    state.rect = {
      x: Math.round(cropCanvas.width * 0.15),
      y: Math.round(cropCanvas.height * 0.15),
      width: Math.round(cropCanvas.width * 0.7),
      height: Math.round(cropCanvas.height * 0.7),
    };
    draw();
    updatePreviewAndJson();
  };
  img.src = imageUrl;
})();
