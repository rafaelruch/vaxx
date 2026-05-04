/**
 * VAXX · Header search overlay
 * - Click na lupa abre overlay fullscreen com input
 * - ESC, × ou click fora do form fecha
 * - Foco vai para o input quando abre; retorna ao gatilho quando fecha
 */
(function () {
  'use strict';

  var trigger = document.getElementById('searchOpenBtn');
  var overlay = document.getElementById('searchOverlay');
  if (!trigger || !overlay) return;

  var closeBtn = overlay.querySelector('.search-overlay__close');
  var form     = overlay.querySelector('.search-overlay__form');
  var input    = overlay.querySelector('input[type="search"]');

  function open() {
    overlay.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    document.body.classList.add('has-search-open');
    // foco no input após o layout settle
    window.requestAnimationFrame(function () {
      if (input) input.focus();
    });
  }

  function close() {
    overlay.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('has-search-open');
    trigger.focus();
  }

  trigger.addEventListener('click', function (e) {
    e.preventDefault();
    if (overlay.hidden) open(); else close();
  });

  if (closeBtn) closeBtn.addEventListener('click', close);

  // ESC fecha
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) close();
  });

  // Click fora do form fecha (mas não submete)
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) close();
  });

  // Submit: deixa browser seguir action (/?s=…&post_type=product).
  // Blocka submit vazio.
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!input || !input.value.trim()) {
        e.preventDefault();
        input && input.focus();
      }
    });
  }
})();
