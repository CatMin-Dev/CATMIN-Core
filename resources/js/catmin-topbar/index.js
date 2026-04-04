function initCommandSurface() {
  const input = document.querySelector('[data-topbar-command-input]');
  const list = document.querySelector('[data-topbar-command-results]');
  if (!input || !list) return;

  const allItems = Array.from(list.querySelectorAll('[data-command-item]'));

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    allItems.forEach((item) => {
      const text = (item.textContent || '').toLowerCase();
      item.classList.toggle('d-none', q !== '' && !text.includes(q));
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initCommandSurface();
});
