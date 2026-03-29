document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.queue-stat-card');
  cards.forEach((card, index) => {
    card.style.transitionDelay = `${index * 40}ms`;
    card.classList.add('queue-ready');
  });

  const checkAll = document.querySelector('[data-queue-check-all]');
  const rowChecks = document.querySelectorAll('[data-queue-check-row]');

  if (checkAll && rowChecks.length > 0) {
    checkAll.addEventListener('change', () => {
      rowChecks.forEach((input) => {
        input.checked = checkAll.checked;
      });
    });

    rowChecks.forEach((input) => {
      input.addEventListener('change', () => {
        const checkedCount = Array.from(rowChecks).filter((node) => node.checked).length;
        checkAll.checked = checkedCount === rowChecks.length;
        checkAll.indeterminate = checkedCount > 0 && checkedCount < rowChecks.length;
      });
    });
  }
});
