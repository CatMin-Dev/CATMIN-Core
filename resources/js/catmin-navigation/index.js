function setCollapsedState(collapsed) {
  document.body.classList.toggle('catmin-nav-collapsed', collapsed);
  document.cookie = `catmin_nav_collapsed=${collapsed ? '1' : '0'}; path=/; max-age=31536000; SameSite=Lax`;
}

function initMasterPanels() {
  document.querySelectorAll('[data-master-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const id = button.getAttribute('data-master-id');
      const panel = document.querySelector(`[data-master-panel="${id}"]`);
      if (!panel) return;
      const open = panel.classList.toggle('is-open');
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
}

function initFlyouts() {
  document.querySelectorAll('[data-master-id]').forEach((master) => {
    master.addEventListener('mouseenter', () => {
      if (!document.body.classList.contains('catmin-nav-collapsed')) return;
      const id = master.getAttribute('data-master-id');
      const flyout = master.querySelector(`[data-flyout-for="${id}"]`);
      if (!flyout) return;
      flyout.setAttribute('aria-hidden', 'false');
      flyout.classList.add('is-open');
    });

    master.addEventListener('mouseleave', () => {
      const id = master.getAttribute('data-master-id');
      const flyout = master.querySelector(`[data-flyout-for="${id}"]`);
      if (!flyout) return;
      flyout.setAttribute('aria-hidden', 'true');
      flyout.classList.remove('is-open');
    });
  });
}

function initCollapsedClickBehavior() {
  document.querySelectorAll('.catmin-nav-flyout a').forEach((link) => {
    link.addEventListener('click', () => {
      setCollapsedState(false);
    });
  });
}

function initSidebarToggle() {
  const toggle = document.querySelector('[data-nav-toggle]');
  if (!toggle) return;

  toggle.addEventListener('click', () => {
    setCollapsedState(!document.body.classList.contains('catmin-nav-collapsed'));
  });
}

function hydrateStateFromCookie() {
  const collapsed = document.cookie.includes('catmin_nav_collapsed=1');
  setCollapsedState(collapsed);
}

document.addEventListener('DOMContentLoaded', () => {
  hydrateStateFromCookie();
  initSidebarToggle();
  initMasterPanels();
  initFlyouts();
  initCollapsedClickBehavior();
});
