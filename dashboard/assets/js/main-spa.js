/**
 * main-spa.js — Catmin SPA Router
 * Handles client-side navigation by loading content files dynamically.
 * Components (aside, topnav, footer) are embedded in index.html for proper init.
 */

import './main-minimal.js';

// Page-to-module mapping — lazy loaded only when needed
const PAGE_MODULES = {
  calendar:       () => import('./main-calendar.js'),
  tables_dynamic: () => import('./main-tables.js'),
  inbox:          () => import('./main-inbox.js'),
  form_advanced:  () => import('./main-form-basic.js'),
  form_upload:    () => import('./main-upload.js'),
};

// Reinit function map (called on repeated navigation once module is cached)
const REINIT_FNS = {
  calendar:       () => window.initCalendarPage?.(),
  tables_dynamic: () => window.initTablesPage?.(),
  inbox:          () => window.initInboxPage?.(),
  form_advanced:  () => window.initFormAdvancedPage?.(),
  form_upload:    () => window.initUploadPage?.(),
};

const MODULE_READY = {
  calendar:       () => typeof window.initCalendarPage === 'function',
  tables_dynamic: () => typeof window.initTablesPage === 'function',
  inbox:          () => typeof window.initInboxPage === 'function',
  form_advanced:  () => typeof window.initFormAdvancedPage === 'function',
  form_upload:    () => typeof window.initUploadPage === 'function',
};

const DEFAULT_PAGE = 'dashboard';

/** Re-execute <script> tags injected via innerHTML */
function executeScripts(container) {
  container.querySelectorAll('script').forEach(function (oldScript) {
    const newScript = document.createElement('script');
    Array.from(oldScript.attributes).forEach(function (attr) {
      // Skip importmap and external module src scripts (shell handles those)
      if (attr.name === 'type' && attr.value === 'importmap') return;
      if (attr.name === 'src' && (attr.value.includes('browser-globals-shim') || attr.value.includes('main-'))) return;
      newScript.setAttribute(attr.name, attr.value);
    });
    newScript.textContent = oldScript.textContent;
    oldScript.parentNode.replaceChild(newScript, oldScript);
  });
}

/** Get the current page name from URL hash */
function getCurrentPage() {
  const hash = location.hash.slice(1);
  if (hash) {
    return hash;
  }

  const main = document.getElementById('page-content');
  const initialPage = main?.getAttribute('data-initial-page') || DEFAULT_PAGE;
  return initialPage;
}

/** Update active menu item in aside */
function setActiveMenu(page) {
  document.querySelectorAll('#sidebar-menu a').forEach(function (a) {
    const href = a.getAttribute('href') || '';
    const linkPage = href.startsWith('#') ? href.slice(1) : null;
    if (linkPage) {
      const li = a.closest('li');
      if (li) {
        li.classList.toggle('active', linkPage === page);
      }
    }
  });

  // Open parent menu sections containing the active item
  document.querySelectorAll('#sidebar-menu li.active').forEach(function (li) {
    const parentMenu = li.closest('ul.child_menu');
    if (parentMenu) {
      const parentLi = parentMenu.closest('li');
      if (parentLi) {
        parentLi.classList.add('active');
        parentMenu.style.display = 'block';
      }
    }
  });
}

/** Load a page into the content area */
async function loadPage(page) {
  const main = document.getElementById('page-content');
  if (!main) return;

  // Show loading state
  main.innerHTML = '<div class="content-loading d-flex align-items-center justify-content-center py-5">'
    + '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>'
    + '</div>';

  // Update URL hash without triggering hashchange again
  if (location.hash !== '#' + page) {
    history.replaceState(null, '', '#' + page);
  }

  // Update body class for page-specific styles
  document.body.className = 'nav-md page-' + page;

  // Update active menu item
  setActiveMenu(page);

  // Fetch content
  try {
    const res = await fetch('content/' + page + '.html');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const html = await res.text();
    main.innerHTML = html;
    executeScripts(main);
  } catch (err) {
    main.innerHTML = '<div class="container py-5"><div class="alert alert-warning">'
      + '<i class="fas fa-exclamation-triangle me-2"></i>'
      + 'Page <strong>' + page + '</strong> not found.</div></div>';
    return;
  }

  // Load or reinit page-specific module
  if (PAGE_MODULES[page]) {
    const isReady = MODULE_READY[page]?.() || false;

    if (!isReady) {
      try {
        await PAGE_MODULES[page]();
      } catch (e) {
        console.warn('Could not load module for page:', page, e);
      }
    }

    REINIT_FNS[page]?.();
  }
}

// ── Bootstrap the SPA ──────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  // Load initial page
  loadPage(getCurrentPage());

  // Handle hash navigation
  window.addEventListener('hashchange', function () {
    loadPage(getCurrentPage());
  });

  // Intercept clicks on hash links in the sidebar
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href^="#"]');
    if (link && !link.closest('.dropdown-menu')) {
      const page = link.getAttribute('href').slice(1);
      if (page && page !== location.hash.slice(1)) {
        e.preventDefault();
        loadPage(page);
      }
    }
  });
});
