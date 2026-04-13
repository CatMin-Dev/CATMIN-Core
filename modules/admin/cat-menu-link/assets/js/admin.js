(() => {
  'use strict';

  const list = document.getElementById('menu-items-sortable');
  const output = document.getElementById('menu-order-json');
  const deleteForm = document.getElementById('menu-delete-form');
  const deleteId = document.getElementById('menu-delete-id');
  if (!list || !output) return;

  let dragged = null;

  const serialize = () => {
    const rows = Array.from(list.querySelectorAll('[data-item-id]')).map((el, idx) => ({
      id: parseInt(el.getAttribute('data-item-id') || '0', 10),
      parent_item_id: parseInt(el.getAttribute('data-parent-id') || '0', 10),
      sort_order: idx + 1,
    })).filter((r) => r.id > 0);

    output.value = JSON.stringify(rows);
  };

  list.querySelectorAll('[data-item-id]').forEach((item) => {
    item.addEventListener('dragstart', () => {
      dragged = item;
      item.classList.add('opacity-50');
    });

    item.addEventListener('dragend', () => {
      item.classList.remove('opacity-50');
      dragged = null;
      serialize();
    });

    item.addEventListener('dragover', (event) => {
      event.preventDefault();
    });

    item.addEventListener('drop', (event) => {
      event.preventDefault();
      if (!dragged || dragged === item) return;
      const rect = item.getBoundingClientRect();
      const before = event.clientY < rect.top + rect.height / 2;
      if (before) {
        list.insertBefore(dragged, item);
      } else {
        list.insertBefore(dragged, item.nextSibling);
      }
      serialize();
    });
  });

  if (deleteForm && deleteId) {
    const confirmText = String(deleteForm.getAttribute('data-confirm') || 'Confirm deletion?');
    document.querySelectorAll('.cat-menu-delete-btn[data-delete-id]').forEach((button) => {
      button.addEventListener('click', () => {
        const id = parseInt(button.getAttribute('data-delete-id') || '0', 10);
        if (id <= 0) return;
        if (!window.confirm(confirmText)) return;
        deleteId.value = String(id);
        deleteForm.submit();
      });
    });
  }

  serialize();
})();
