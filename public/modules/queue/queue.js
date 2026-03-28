document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.queue-stat-card');
  cards.forEach((card, index) => {
    card.style.transitionDelay = `${index * 40}ms`;
    card.classList.add('queue-ready');
  });
});
