/* VAXX · Global JS · placeholder pra Fase 2 (header scroll, mini-cart, etc.) */
(function () {
	'use strict';
	// Placeholder. Scripts específicos virão nas fases posteriores.
})();
/* ═══════════════════════════════════════════════════════════════
   SCROLL BEHAVIOR — header vira sólido após 40px
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const header = document.getElementById('header');
  if (!header) return;
  let ticking = false;
  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(() => {
        if (window.scrollY > 40) header.classList.add('scrolled');
        else header.classList.remove('scrolled');
        ticking = false;
      });
      ticking = true;
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* ═══════════════════════════════════════════════════════════════
   MINI-CART · abrir/fechar + stepper + remove + subtotal dinâmico
   ═══════════════════════════════════════════════════════════════ */
(function() {
  const overlay = document.getElementById('cartOverlay');
  const drawer = document.getElementById('cartDrawer');
  const openBtn = document.getElementById('cartOpenBtn');
  const closeBtn = document.getElementById('cartCloseBtn');
  const list = document.getElementById('cartList');
  const subtotalEl = document.getElementById('cartSubtotal');
  const headerCount = document.getElementById('cartHeaderCount');
  const cartBadge = document.getElementById('cartBadge');
  if (!drawer || !openBtn) return;

  const priceFmt = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

  function getItems() {
    return Array.from(list.querySelectorAll('.cart-item:not(.is-removing)'));
  }

  function updateTotals() {
    const items = getItems();
    let subtotal = 0;
    let totalQty = 0;
    items.forEach(item => {
      const qty = parseInt(item.querySelector('.cart-qty__value').textContent, 10) || 0;
      const price = parseInt(item.dataset.price, 10) || 0;
      subtotal += qty * price;
      totalQty += qty;
    });
    subtotalEl.textContent = 'R$ ' + priceFmt.format(subtotal);
    headerCount.textContent = totalQty;
    if (cartBadge) cartBadge.textContent = totalQty;

    // Estado vazio
    if (items.length === 0) {
      drawer.classList.add('is-empty');
      if (cartBadge) cartBadge.style.display = 'none';
    } else {
      drawer.classList.remove('is-empty');
      if (cartBadge) cartBadge.style.display = '';
    }
  }

  // Abrir drawer
  function openCart() {
    overlay.classList.add('is-open');
    drawer.classList.add('is-open');
    document.body.classList.add('has-cart-open');
    overlay.setAttribute('aria-hidden', 'false');
    drawer.setAttribute('aria-hidden', 'false');
    openBtn.setAttribute('aria-expanded', 'true');
    setTimeout(() => drawer.focus(), 300);
  }

  // Fechar
  function closeCart() {
    overlay.classList.remove('is-open');
    drawer.classList.remove('is-open');
    document.body.classList.remove('has-cart-open');
    overlay.setAttribute('aria-hidden', 'true');
    drawer.setAttribute('aria-hidden', 'true');
    openBtn.setAttribute('aria-expanded', 'false');
    openBtn.focus();
  }

  openBtn.addEventListener('click', openCart);
  closeBtn.addEventListener('click', closeCart);
  overlay.addEventListener('click', closeCart);

  // ESC fecha
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) closeCart();
  });

  /* Swipe down pra fechar (experiência nativa mobile)
     Só ativa em viewport < 768px (quando é bottom sheet, não drawer lateral) */
  let touchStartY = null;
  let touchCurrentY = null;
  let dragging = false;

  function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }

  drawer.addEventListener('touchstart', (e) => {
    if (!isMobile() || !drawer.classList.contains('is-open')) return;
    // Só aceita swipe do topo do drawer (primeiros 80px — área do handle + header)
    // ou quando o body já está com scrollTop = 0
    const body = drawer.querySelector('.cart-body');
    const touchInHeader = e.target.closest('.cart-header') !== null;
    const bodyAtTop = body.scrollTop <= 0;
    if (!touchInHeader && !bodyAtTop) return;

    touchStartY = e.touches[0].clientY;
    touchCurrentY = touchStartY;
    dragging = true;
    drawer.style.transition = 'none';
  }, { passive: true });

  drawer.addEventListener('touchmove', (e) => {
    if (!dragging || touchStartY === null) return;
    touchCurrentY = e.touches[0].clientY;
    const delta = touchCurrentY - touchStartY;
    if (delta > 0) {
      drawer.style.transform = `translateY(${delta}px)`;
    }
  }, { passive: true });

  drawer.addEventListener('touchend', () => {
    if (!dragging || touchStartY === null) return;
    const delta = touchCurrentY - touchStartY;
    drawer.style.transition = '';
    drawer.style.transform = '';

    // Se arrastou mais de 120px pra baixo OU velocidade rápida → fecha
    if (delta > 120) closeCart();

    touchStartY = null;
    touchCurrentY = null;
    dragging = false;
  });

  // Endpoint AJAX do WooCommerce: POST /?wc-ajax=<action>
  // Aceita 'remove_from_cart' e 'update_order_review'. Aqui usamos
  // admin-ajax custom actions registradas em inc/woocommerce.php (vaxx_ajax_remove / vaxx_ajax_set_qty)
  // pra garantir que a sessão WC seja atualizada e a badge fique sincronizada.
  const WC_AJAX = (window.wc_cart_fragments_params && window.wc_cart_fragments_params.ajax_url)
    || '/wp-admin/admin-ajax.php';

  async function wcCartUpdate(action, params) {
    const body = new URLSearchParams({ action, ...params }).toString();
    const res = await fetch(WC_AJAX, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body,
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(action + ' HTTP ' + res.status);
    return res.json().catch(() => ({}));
  }

  function updateHeaderBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = '';
    } else {
      badge.style.display = 'none';
    }
  }

  // Stepper de quantidade (event delegation) — com persistência no WC
  list.addEventListener('click', async (e) => {
    const btn = e.target.closest('.cart-qty__btn');
    if (!btn) return;
    const item = btn.closest('.cart-item');
    const valueEl = item.querySelector('.cart-qty__value');
    const decBtn = item.querySelector('[data-action="decrease"]');
    const cartKey = item.dataset.cartKey;
    let qty = parseInt(valueEl.textContent, 10) || 1;

    if (btn.dataset.action === 'increase') qty += 1;
    else if (btn.dataset.action === 'decrease' && qty > 1) qty -= 1;
    else return;

    // Otimista: atualiza UI antes
    valueEl.textContent = qty;
    decBtn.disabled = qty <= 1;
    btn.disabled = true;
    updateTotals();

    try {
      const res = await wcCartUpdate('vaxx_cart_set_qty', { cart_key: cartKey, qty });
      const count = res?.data?.count ?? res?.count;
      if (typeof count === 'number') updateHeaderBadge(count);
    } catch (err) {
      console.error('vaxx cart qty update failed', err);
    } finally {
      btn.disabled = false;
    }
  });

  // Remover item — atualiza DOM + WC session
  list.addEventListener('click', async (e) => {
    const btn = e.target.closest('.cart-item__remove');
    if (!btn) return;
    const item = btn.closest('.cart-item');
    const cartKey = btn.dataset.cartKey || item.dataset.cartKey;
    btn.disabled = true;
    item.classList.add('is-removing');
    updateTotals();

    try {
      const res = await wcCartUpdate('vaxx_cart_remove', { cart_key: cartKey });
      const count = res?.data?.count ?? res?.count;
      if (typeof count === 'number') updateHeaderBadge(count);
    } catch (err) {
      console.error('vaxx cart remove failed', err);
    }

    setTimeout(() => {
      item.remove();
      updateTotals();
    }, 320);
  });

  // Init: ajustar disabled dos botões de decrease + totais iniciais
  getItems().forEach(item => {
    const decBtn = item.querySelector('[data-action="decrease"]');
    const qty = parseInt(item.querySelector('.cart-qty__value').textContent, 10) || 1;
    decBtn.disabled = qty <= 1;
  });
  updateTotals();
})();

/* ═══════════════════════════════════════════════════════════════
   FOOTER — Voltar ao topo
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const backTop = document.getElementById('footerBackTop');
  if (!backTop) return;
  backTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();

/* CPF/CEP mask + valida\u00e7\u00e3o agora sao do plugin Brazilian Market on WooCommerce. */

/* ═══════════════════════════════════════════════════════════════
   Move WC notices (server-rendered) para dentro do .page-checkout,
   logo apos a breadcrumb — evita aparecer acima do header.
   ═══════════════════════════════════════════════════════════════ */
(function () {
  function relocateNotices() {
    const bc = document.querySelector('.page-checkout > .bc, .page-cart > .bc');
    if (!bc) return;
    // Acha TODOS os notice wrappers com conteudo, onde estiverem
    const wrappers = document.querySelectorAll('.woocommerce-notices-wrapper');
    wrappers.forEach(wrap => {
      // Ignora se ja esta dentro do main .page-checkout
      if (bc.parentNode.contains(wrap)) return;
      // Ignora se vazio
      if (!wrap.children.length || !wrap.textContent.trim()) return;
      // Move pra imediatamente apos a .bc
      const insertAfter = bc.nextElementSibling;
      bc.parentNode.insertBefore(wrap, insertAfter);
    });
  }
  document.addEventListener('DOMContentLoaded', relocateNotices);
  if (document.readyState !== 'loading') relocateNotices();
})();

/* ═══════════════════════════════════════════════════════════════
   CHECKOUT · Step accordion (step 1 completo → colapsa, expande step 2)
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const block1 = document.querySelector('.co-block[data-block="1"]');
  const block2 = document.querySelector('.co-block[data-block="2"]');
  if (!block1 || !block2) return;

  // Lista de fields obrigatorios do step 1
  const requiredIds = [
    'billing_first_name', 'billing_last_name', 'billing_email',
    'billing_cpf', 'billing_phone', 'billing_postcode',
    'billing_address_1', 'billing_number', 'billing_neighborhood',
    'billing_city', 'billing_state',
  ];

  function allValid() {
    for (const id of requiredIds) {
      const el = document.getElementById(id);
      if (!el) continue;
      if (!el.value || !el.value.trim()) return false;
      // CPF precisa passar na validacao
      if (id === 'billing_cpf') {
        const s = el.value.replace(/\D/g, '');
        if (s.length !== 11 || /^(\d)\1{10}$/.test(s)) return false;
      }
      if (id === 'billing_postcode' && el.value.replace(/\D/g, '').length !== 8) return false;
      if (id === 'billing_email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) return false;
    }
    return true;
  }

  function collapseStep1() {
    if (block1.classList.contains('is-collapsed')) return;
    block1.classList.add('is-collapsed');
    block2.classList.add('is-active');
    // Scroll suave pro step 2
    setTimeout(() => {
      block2.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 200);
  }

  function expandStep1() {
    block1.classList.remove('is-collapsed');
  }

  // Listeners — valida a cada mudança e colapsa quando tudo ok
  requiredIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('blur', () => {
      if (allValid()) collapseStep1();
    });
    // state select2 dispara 'change' no elemento raw
    if (id === 'billing_state' && window.jQuery) {
      window.jQuery(el).on('change', () => {
        if (allValid()) collapseStep1();
      });
    }
  });

  // Click no header do step 1 (quando colapsado) reabre
  const header1 = block1.querySelector('.co-block__header');
  if (header1) {
    header1.style.cursor = 'pointer';
    header1.addEventListener('click', () => {
      if (block1.classList.contains('is-collapsed')) expandStep1();
    });
  }
})();

/* ViaCEP autofill agora \u00e9 do plugin Brazilian Market on WooCommerce. */

/* ═══════════════════════════════════════════════════════════════
   ARCHIVE · filtros por grupo muscular (chips) + regulagem toggle
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const grid = document.getElementById('catalogoGrid');
  if (!grid) return;
  const chips = document.querySelectorAll('.toolbar__chip');
  const regToggle = document.getElementById('toggleRegulagem');
  const countEl = document.getElementById('toolbarCount');
  const cards = Array.from(grid.querySelectorAll('.prod-card'));

  let filterMuscle = 'all';
  let filterRegulagem = false;

  function applyFilters() {
    let visible = 0;
    cards.forEach(card => {
      const grupos = (card.dataset.grupo || '').toLowerCase().split(/\s+/).filter(Boolean);
      const hasReg = card.dataset.regulagem === 'true';
      const matchMuscle = filterMuscle === 'all' || grupos.includes(filterMuscle);
      const matchReg = !filterRegulagem || hasReg;
      const show = matchMuscle && matchReg;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (countEl) countEl.textContent = visible;

    // Mostra/oculta empty state
    const empty = document.getElementById('catalogoEmpty');
    if (empty) empty.style.display = visible === 0 ? '' : 'none';
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      filterMuscle = chip.dataset.filter || 'all';
      applyFilters();
    });
  });

  if (regToggle) {
    regToggle.addEventListener('change', () => {
      filterRegulagem = regToggle.checked;
      applyFilters();
    });
  }
})();

/* ═══════════════════════════════════════════════════════════════
   PDP · RENT MODAL (abrir/fechar + submit AJAX)
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const overlay = document.getElementById('rentModalOverlay');
  const modal = document.getElementById('rentModal');
  const openBtn = document.getElementById('openRentBtn');
  const closeBtn = document.getElementById('closeRentBtn');
  const form = document.getElementById('rentForm');
  if (!modal || !openBtn) return;

  function openModal() {
    overlay.classList.add('is-open');
    modal.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    modal.setAttribute('aria-hidden', 'false');
    openBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('has-modal-open');
    setTimeout(() => { const inp = modal.querySelector('input, select, textarea'); if (inp) inp.focus(); }, 280);
  }
  function closeModal() {
    overlay.classList.remove('is-open');
    modal.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    modal.setAttribute('aria-hidden', 'true');
    openBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('has-modal-open');
    openBtn.focus();
  }

  openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (overlay) overlay.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  // Submit AJAX
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('.rent-modal__submit');
      if (!submitBtn) return;
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Enviando…';

      try {
        const fd = new FormData(form);
        fd.append('action', 'vaxx_submit_rent_lead');
        const res = await fetch('/wp-admin/admin-ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json().catch(() => ({ success: false }));

        if (data.success) {
          form.innerHTML = '<div class="rent-modal__success">'
            + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            + '<h4>Solicitação enviada!</h4>'
            + '<p>Nosso comercial entra em contato em até 24h úteis. Obrigado!</p>'
            + '<button type="button" class="rent-modal__success-close" id="rentSuccessClose">Fechar</button>'
            + '</div>';
          const closeOkBtn = document.getElementById('rentSuccessClose');
          if (closeOkBtn) closeOkBtn.addEventListener('click', closeModal);
        } else {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
          alert(data.data && data.data.message ? data.data.message : 'Erro ao enviar. Tente novamente.');
        }
      } catch (err) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        alert('Erro de conexão. Tente novamente.');
      }
    });
  }
})();

/* ═══════════════════════════════════════════════════════════════
   PDP · ADD-TO-CART AJAX + TOAST + badge animation
   ═══════════════════════════════════════════════════════════════ */
(function () {
  const form = document.querySelector('.pdp-actions__form');
  if (!form) return;
  const btn = form.querySelector('.pdp-actions__primary');
  const productId = form.querySelector('input[name="add-to-cart"]')?.value;
  if (!btn || !productId) return;

  function showToast(msg, variant = 'success') {
    // Injeta notice inline abaixo da breadcrumb (padrao VAXX)
    let notice = document.getElementById('vaxxNotice');
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'vaxxNotice';
      notice.className = 'vaxx-notice';
      // Insere logo depois da .bc
      const bc = document.querySelector('.bc');
      if (bc && bc.parentNode) {
        bc.parentNode.insertBefore(notice, bc.nextSibling);
      } else {
        // Fallback: topo do main
        const main = document.querySelector('main');
        main?.prepend(notice);
      }
    }
    notice.className = 'vaxx-notice is-' + variant;
    notice.innerHTML =
      '<div class="vaxx-notice__inner">' +
        '<span class="vaxx-notice__icon">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' +
        '</span>' +
        '<span class="vaxx-notice__msg">' + msg + '</span>' +
        '<a class="vaxx-notice__cta" href="/carrinho/">Ver orçamento ' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>' +
        '</a>' +
        '<button type="button" class="vaxx-notice__close" aria-label="Fechar">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
        '</button>' +
      '</div>';
    requestAnimationFrame(() => notice.classList.add('is-visible'));
    notice.querySelector('.vaxx-notice__close')?.addEventListener('click', () => {
      notice.classList.remove('is-visible');
      setTimeout(() => notice.remove(), 280);
    });
    clearTimeout(notice._timer);
    notice._timer = setTimeout(() => {
      notice.classList.remove('is-visible');
      setTimeout(() => notice.remove?.(), 280);
    }, 5000);
  }

  function bumpCartIcon() {
    const cartBtn = document.getElementById('cartOpenBtn');
    if (cartBtn) {
      cartBtn.classList.remove('is-bumped');
      void cartBtn.offsetWidth;
      cartBtn.classList.add('is-bumped');
    }
  }

  // Busca contagem real do carrinho no server e atualiza badge
  async function syncCartCount() {
    try {
      const r = await fetch('/wp-admin/admin-ajax.php?action=vaxx_cart_count', { credentials: 'same-origin' });
      const data = await r.json();
      const badge = document.getElementById('cartBadge');
      const headerCount = document.getElementById('cartHeaderCount');
      const count = parseInt(data.count, 10) || 0;
      if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? '' : 'none';
      }
      if (headerCount) headerCount.textContent = count;
    } catch (err) { /* silent */ }
  }

  // Re-renderiza o drawer inteiro com os items atuais do carrinho.
  // Chamado apos add-to-cart pra refletir o novo item sem reload.
  // Exposto em window pra outros scripts chamarem (PDP add-to-cart).
  window.vaxxRefreshCart = async function refreshCartDrawer(openAfter = false) {
    try {
      const r = await fetch('/wp-admin/admin-ajax.php?action=vaxx_cart_html', { credentials: 'same-origin' });
      const data = await r.json();
      if (!data.html) return;

      // O shortcode devolve overlay + drawer. Parseamos e substituimos os 2 nodes.
      const parser = new DOMParser();
      const doc = parser.parseFromString(data.html, 'text/html');
      const newOverlay = doc.getElementById('cartOverlay');
      const newDrawer = doc.getElementById('cartDrawer');
      const currentOverlay = document.getElementById('cartOverlay');
      const currentDrawer = document.getElementById('cartDrawer');

      if (newDrawer && currentDrawer) {
        // Preserva estado is-open (se estava aberto, mantem)
        const wasOpen = currentDrawer.classList.contains('is-open');
        currentDrawer.outerHTML = newDrawer.outerHTML;
        if (newOverlay && currentOverlay) currentOverlay.outerHTML = newOverlay.outerHTML;

        // Re-inicializa handlers do novo drawer/overlay
        initCartDrawer();
        if (wasOpen || openAfter) {
          // Re-abre drawer
          const d = document.getElementById('cartDrawer');
          const o = document.getElementById('cartOverlay');
          d?.classList.add('is-open');
          o?.classList.add('is-open');
          document.body.classList.add('has-cart-open');
          d?.setAttribute('aria-hidden', 'false');
          o?.setAttribute('aria-hidden', 'false');
        }
      }

      // Atualiza badge no header
      const badge = document.getElementById('cartBadge');
      const count = parseInt(data.count, 10) || 0;
      if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? '' : 'none';
      }
    } catch (err) { console.error('refresh cart drawer:', err); }
  };

  // Extrai a inicializacao do drawer pra poder re-chamar apos AJAX
  function initCartDrawer() {
    const overlay = document.getElementById('cartOverlay');
    const drawer = document.getElementById('cartDrawer');
    const openBtn = document.getElementById('cartOpenBtn');
    const closeBtn = document.getElementById('cartCloseBtn');
    if (!drawer || !overlay) return;

    function open() {
      overlay.classList.add('is-open');
      drawer.classList.add('is-open');
      document.body.classList.add('has-cart-open');
      overlay.setAttribute('aria-hidden', 'false');
      drawer.setAttribute('aria-hidden', 'false');
      openBtn?.setAttribute('aria-expanded', 'true');
    }
    function close() {
      overlay.classList.remove('is-open');
      drawer.classList.remove('is-open');
      document.body.classList.remove('has-cart-open');
      overlay.setAttribute('aria-hidden', 'true');
      drawer.setAttribute('aria-hidden', 'true');
      openBtn?.setAttribute('aria-expanded', 'false');
    }
    // Remove listeners antigos clonando (hack simples)
    if (openBtn && !openBtn.dataset.vaxxInit) {
      openBtn.addEventListener('click', open);
      openBtn.dataset.vaxxInit = '1';
    }
    closeBtn?.addEventListener('click', close);
    overlay.addEventListener('click', close);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.classList.add('is-loading');
    btn.innerHTML = 'Adicionando…';

    try {
      const fd = new FormData(form);
      // Woo espera POST pro mesmo URL; com X-Requested-With redireciona e retorna 200
      const res = await fetch(window.location.href, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (res.ok || res.redirected) {
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Incluído';
        btn.classList.remove('is-loading');
        btn.classList.add('is-success');
        showToast('Produto incluído no orçamento');
        bumpCartIcon();
        // Re-renderiza drawer inteiro (items atualizados) + abre pra mostrar o item
        if (window.vaxxRefreshCart) {
          await window.vaxxRefreshCart(true);
        } else {
          // Fallback: atualiza so a contagem
          syncCartCount?.();
        }

        setTimeout(() => {
          btn.disabled = false;
          btn.classList.remove('is-success');
          btn.innerHTML = originalText;
        }, 2800);
      } else {
        throw new Error('Falha');
      }
    } catch (err) {
      btn.disabled = false;
      btn.classList.remove('is-loading');
      btn.innerHTML = originalText;
      showToast('Erro ao adicionar. Tente novamente.', 'error');
    }
  });
})();


/* ═══════════════════════════════════════════════════════════════
   Mobile menu drawer · toggle/close (click / ESC / overlay / link)
   ═══════════════════════════════════════════════════════════════ */
(function () {
  var toggle  = document.getElementById('mobileMenuToggle');
  var drawer  = document.getElementById('mobileMenu');
  var overlay = document.getElementById('mobileMenuOverlay');
  var closeBtn = document.getElementById('mobileMenuClose');
  if (!toggle || !drawer || !overlay) return;

  function open() {
    drawer.classList.add('is-open');
    overlay.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    overlay.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('has-mobile-menu-open');
    // Foco no botão de fechar pra acessibilidade
    setTimeout(function () { closeBtn && closeBtn.focus(); }, 50);
  }

  function close() {
    drawer.classList.remove('is-open');
    overlay.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    overlay.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('has-mobile-menu-open');
    toggle.focus();
  }

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    drawer.classList.contains('is-open') ? close() : open();
  });

  closeBtn && closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
      close();
    }
  });

  // Fecha ao clicar em qualquer link interno do drawer
  drawer.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { setTimeout(close, 80); });
  });
})();
