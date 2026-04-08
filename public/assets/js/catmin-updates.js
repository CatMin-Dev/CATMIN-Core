document.querySelectorAll('[data-cat-update-refresh]').forEach((btn) => {
  btn.addEventListener('click', () => {
    btn.disabled = true;
  });
});

