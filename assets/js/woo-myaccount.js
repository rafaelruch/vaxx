/**
 * VAXX · Minha Conta — tabs + password strength + address toggle
 * Extraido do preview/minha-conta.html aprovado.
 */
(function () {
  'use strict';

/* ═══════════════════════════════════════════════════════════════
   MINHA CONTA · tabs + hash sync + expand orders
   ═══════════════════════════════════════════════════════════════ */

/* ───── Troca de view (sidebar desktop + tabs mobile) ───── */
(function () {
  const navItems = document.querySelectorAll('.mc-nav-item');
  const tabs = document.querySelectorAll('.mc-tab');
  const views = document.querySelectorAll('.mc-view');

  function switchView(viewName) {
    if (!viewName) viewName = 'pedidos';

    navItems.forEach(n => n.classList.toggle('is-active', n.dataset.view === viewName));
    tabs.forEach(t => {
      const active = t.dataset.view === viewName;
      t.classList.toggle('is-active', active);
      t.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    views.forEach(v => v.classList.toggle('is-active', v.dataset.view === viewName));

    // Sincroniza URL hash
    if (location.hash !== '#' + viewName) {
      history.replaceState(null, '', '#' + viewName);
    }

    // Scroll to top do conteúdo em mobile
    if (window.innerWidth < 1024) {
      window.scrollTo({ top: document.querySelector('.mc-tabs').offsetTop - 64, behavior: 'smooth' });
    }
  }

  navItems.forEach(n => n.addEventListener('click', () => switchView(n.dataset.view)));
  tabs.forEach(t => t.addEventListener('click', () => switchView(t.dataset.view)));

  // Deep link via hash
  const hashView = location.hash.replace('#', '');
  if (hashView && document.querySelector(`.mc-view[data-view="${hashView}"]`)) {
    switchView(hashView);
  }

  // Sincroniza se hash mudar externamente
  window.addEventListener('hashchange', () => {
    const h = location.hash.replace('#', '');
    if (h) switchView(h);
  });
})();

/* ───── Expandir/colapsar pedidos ───── */
(function () {
  document.querySelectorAll('.mc-order__head').forEach(head => {
    head.addEventListener('click', () => {
      const order = head.closest('.mc-order');
      const expanded = order.classList.toggle('is-expanded');
      head.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  });
})();

/* ───── Filter chips (pedidos) ───── */
(function () {
  const chips = document.querySelectorAll('.mc-filter-chip');
  const orders = document.querySelectorAll('.mc-order');
  if (!chips.length) return;

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      const filter = chip.textContent.toLowerCase();
      orders.forEach(o => {
        const status = o.querySelector('.mc-order__status')?.textContent.toLowerCase() || '';
        let show = true;
        if (filter.startsWith('em andamento')) show = ['aguardando','em produção','em trânsito'].some(s => status.includes(s));
        else if (filter.startsWith('entregues')) show = status.includes('entregue');
        else if (filter.startsWith('cancelados')) show = status.includes('cancelado');
        o.style.display = show ? '' : 'none';
      });
    });
  });
})();

/* ───── Remove favorito (stub visual) ───── */
(function () {
  document.querySelectorAll('.mc-fav-remove').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();
      const card = btn.closest('.mc-fav-card');
      card.style.transition = 'opacity 260ms, transform 260ms';
      card.style.opacity = '0';
      card.style.transform = 'scale(0.92)';
      setTimeout(() => card.remove(), 280);
    });
  });
})();

/* ───── Sair · stub ───── */
(function () {
  document.querySelector('.mc-hero__logout')?.addEventListener('click', () => {
    if (confirm('Deseja realmente sair da conta?')) {
      window.location.href = '/';
    }
  });
})();

/* ═══════════════════════════════════════════════════════════════
   SENHA · toggle mostrar/ocultar + força + confirmação
   ═══════════════════════════════════════════════════════════════ */
(function () {
  // Toggle mostrar/ocultar qualquer input type=password
  document.querySelectorAll('.field__toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.toggleFor;
      const input = document.getElementById(targetId);
      if (!input) return;
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.classList.toggle('is-active', isHidden);
      btn.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
      // Troca o ícone (eye / eye-off)
      btn.innerHTML = isHidden
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });
  });

  // Força da senha
  const newPw = document.getElementById('mc-pw-new');
  const confirmPw = document.getElementById('mc-pw-confirm');
  const strength = document.getElementById('pwStrength');
  const strengthLabel = document.getElementById('pwStrengthLabel');
  const match = document.getElementById('pwMatch');

  function calcStrength(pw) {
    if (!pw) return 0;
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw) && pw.length >= 10) score++;
    return score;
  }

  const labels = ['', 'Fraca', 'Média', 'Boa', 'Forte'];

  function updateStrength() {
    if (!strength || !newPw) return;
    const level = calcStrength(newPw.value);
    strength.dataset.level = level;
    if (strengthLabel) strengthLabel.textContent = labels[level] || '—';
  }

  function updateMatch() {
    if (!match || !newPw || !confirmPw) return;
    const a = newPw.value;
    const b = confirmPw.value;
    if (!b) {
      match.classList.remove('is-visible', 'is-ok', 'is-fail');
      return;
    }
    const ok = a && a === b;
    match.classList.add('is-visible');
    match.classList.toggle('is-ok', ok);
    match.classList.toggle('is-fail', !ok);
    match.querySelector('span').textContent = ok ? 'As senhas coincidem' : 'As senhas não coincidem';
    match.querySelector('svg').innerHTML = ok
      ? '<polyline points="20 6 9 17 4 12"/>'
      : '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
  }

  newPw?.addEventListener('input', () => { updateStrength(); updateMatch(); });
  confirmPw?.addEventListener('input', updateMatch);
})();

})();
