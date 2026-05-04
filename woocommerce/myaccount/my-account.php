<?php
/**
 * VAXX · Template override: WooCommerce My Account
 *
 * Logado: renderiza o layout aprovado de [[vaxx-componentes#15 minha conta]]
 * (HTML copiado verbatim de preview/minha-conta.html, com dados dinâmicos
 * injetados onde marcado).
 *
 * Deslogado: delega ao form-login.php (override separado).
 *
 * Ref: vaxx-qualidade-testes.md · task de parity da página Minha Conta.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wc_get_template( 'myaccount/form-login.php' );
	return;
}

$current_user = wp_get_current_user();
$first_name   = $current_user->first_name ?: $current_user->display_name;
$initials     = strtoupper( mb_substr( $current_user->first_name ?: $current_user->user_login, 0, 1 ) . mb_substr( $current_user->last_name ?: $current_user->user_login[1] ?? '', 0, 1 ) );
$order_count  = wc_get_customer_order_count( $current_user->ID );
$member_since = date_i18n( 'm/Y', strtotime( $current_user->user_registered ) );

// Contagem de leads de aluguel do usuário (match por e-mail)
$alug_count = 0;
if ( $current_user->user_email ) {
	$alug_query = new WP_Query( array(
		'post_type'      => 'rent_lead',
		'post_status'    => array( 'publish', 'pending', 'private' ),
		'posts_per_page' => 1,
		'no_found_rows'  => false,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => 'email', 'value' => $current_user->user_email, 'compare' => '=' ) ),
	) );
	$alug_count = (int) $alug_query->found_posts;
	wp_reset_postdata();
}
?>
<main class="page-mc">

  <!-- Breadcrumb canônico -->
  <nav class="bc" aria-label="Breadcrumb">
    <div class="bc__inner">
      <a href="/">Início</a>
      <span class="sep" aria-hidden="true">›</span>
      <span class="is-current" aria-current="page">Minha conta</span>
    </div>
  </nav>

  <!-- Hero de identificação -->
  <section class="mc-hero" aria-label="Identificação do usuário">
    <div class="mc-hero__bg" aria-hidden="true"></div>
    <div class="mc-hero__inner">
      <div class="mc-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
      <div class="mc-hero__body">
        <div class="mc-hero__greeting">
          <span class="mc-hero__eyebrow">MINHA CONTA</span>
          <h1 class="mc-hero__title">Olá, <span class="lime"><?php echo esc_html( $first_name ); ?>!</span></h1>
        </div>
        <div class="mc-hero__stats">
          <span><strong><?php echo (int) $order_count; ?></strong> pedido<?php echo $order_count === 1 ? "" : "s"; ?></span>
          <span><strong><?php echo (int) $alug_count; ?></strong> aluguel<?php echo $alug_count === 1 ? "" : "s"; ?></span>
          <span>Membro desde <strong><?php echo esc_html( $member_since ); ?></strong></span>
        </div>
      </div>
      <a href="<?php echo esc_url( wc_logout_url( wc_get_page_permalink( "myaccount" ) ) ); ?>" class="mc-hero__logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Sair
      </a>
    </div>
  </section>

  <!-- Layout sidebar + content -->
  <div class="mc-layout">

    <!-- Sidebar (desktop) -->
    <aside class="mc-sidebar" aria-label="Navegação da conta">
      <nav class="mc-sidebar__list">
        <button type="button" class="mc-nav-item is-active" data-view="pedidos">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
            <line x1="12" y1="22.08" x2="12" y2="12"/>
          </svg>
          Pedidos
          <?php if ( $order_count > 0 ) : ?><span class="mc-nav-item__badge"><?php echo (int) $order_count; ?></span><?php endif; ?>
        </button>
        <button type="button" class="mc-nav-item" data-view="alugueis">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          Aluguéis
          <?php if ( $alug_count > 0 ) : ?><span class="mc-nav-item__badge"><?php echo (int) $alug_count; ?></span><?php endif; ?>
        </button>
        <button type="button" class="mc-nav-item" data-view="favoritos">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
          Favoritos
        </button>
        <button type="button" class="mc-nav-item" data-view="enderecos">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
          Endereços
        </button>
        <button type="button" class="mc-nav-item" data-view="dados">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          Meus dados
        </button>
        <button type="button" class="mc-nav-item" data-view="seguranca">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Segurança
        </button>
        <button type="button" class="mc-nav-item" data-view="notificacoes">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          Notificações
        </button>
      </nav>
    </aside>

    <!-- Tabs mobile -->
    <div class="mc-tabs" role="tablist" aria-label="Navegação da conta">
      <button type="button" class="mc-tab is-active" data-view="pedidos" role="tab" aria-selected="true">
        Pedidos <?php if ( $order_count > 0 ) : ?><span class="mc-tab__badge"><?php echo (int) $order_count; ?></span><?php endif; ?>
      </button>
      <button type="button" class="mc-tab" data-view="alugueis" role="tab" aria-selected="false">
        Aluguéis <?php if ( $alug_count > 0 ) : ?><span class="mc-tab__badge"><?php echo (int) $alug_count; ?></span><?php endif; ?>
      </button>
      <button type="button" class="mc-tab" data-view="favoritos" role="tab" aria-selected="false">Favoritos</button>
      <button type="button" class="mc-tab" data-view="enderecos" role="tab" aria-selected="false">Endereços</button>
      <button type="button" class="mc-tab" data-view="dados" role="tab" aria-selected="false">Meus dados</button>
      <button type="button" class="mc-tab" data-view="seguranca" role="tab" aria-selected="false">Segurança</button>
      <button type="button" class="mc-tab" data-view="notificacoes" role="tab" aria-selected="false">Notificações</button>
    </div>

    <!-- Conteúdo principal -->
    <div class="mc-content">

      <!-- VIEW: PEDIDOS -->
      <?php vaxx_mc_render_pedidos( $current_user->ID ); ?>

      <?php vaxx_mc_render_alugueis( $current_user->ID ); ?>


      <!-- VIEW: FAVORITOS -->
      <section class="mc-view" data-view="favoritos" aria-label="Meus favoritos">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Favoritos</h2>
            <p class="mc-view__desc">Produtos que você salvou pra depois.</p>
          </div>
        </div>

        <div class="mc-favorites">
          <div class="mc-fav-card">
            <button type="button" class="mc-fav-remove" aria-label="Remover dos favoritos">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <a href="#" class="prod-card">
              <div class="prod-card__media">
                <span class="prod-card__line-badge">Articulados</span>
                <span class="prod-card__regulagem">1,55–1,95</span>
                <img src="https://images.unsplash.com/photo-1591741535018-d042766c62eb?auto=format&fit=crop&w=900&q=75" onerror="this.onerror=null;this.src='https://picsum.photos/seed/fav-desenv/900/675?grayscale'" alt="Desenvolvimento Articulado">
              </div>
              <div class="prod-card__body">
                <h3 class="prod-card__title">Desenvolvimento Articulado</h3>
                <div class="prod-card__tags">
                  <span class="prod-card__tag">Ombros</span>
                  <span class="prod-card__tag">Deltoides</span>
                </div>
                <div class="prod-card__cta">
                  <span class="prod-card__cta-text">Ver produto</span>
                  <span class="prod-card__cta-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                </div>
              </div>
            </a>
          </div>

          <div class="mc-fav-card">
            <button type="button" class="mc-fav-remove" aria-label="Remover dos favoritos">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <a href="#" class="prod-card">
              <div class="prod-card__media">
                <span class="prod-card__line-badge">Articulados</span>
                <span class="prod-card__regulagem">1,55–1,95</span>
                <img src="https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=900&q=75" onerror="this.onerror=null;this.src='https://picsum.photos/seed/fav-peck/900/675?grayscale'" alt="Peck Deck Articulado">
              </div>
              <div class="prod-card__body">
                <h3 class="prod-card__title">Peck Deck Articulado</h3>
                <div class="prod-card__tags">
                  <span class="prod-card__tag">Peito</span>
                  <span class="prod-card__tag">Ombro</span>
                </div>
                <div class="prod-card__cta">
                  <span class="prod-card__cta-text">Ver produto</span>
                  <span class="prod-card__cta-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                </div>
              </div>
            </a>
          </div>

          <div class="mc-fav-card">
            <button type="button" class="mc-fav-remove" aria-label="Remover dos favoritos">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <a href="#" class="prod-card">
              <div class="prod-card__media">
                <span class="prod-card__line-badge">Articulados</span>
                <span class="prod-card__regulagem">1,55–1,95</span>
                <img src="https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=900&q=75" onerror="this.onerror=null;this.src='https://picsum.photos/seed/fav-remada/900/675?grayscale'" alt="Remada Baixa Articulada">
              </div>
              <div class="prod-card__body">
                <h3 class="prod-card__title">Remada Baixa Articulada</h3>
                <div class="prod-card__tags">
                  <span class="prod-card__tag">Costas</span>
                  <span class="prod-card__tag">Trapézio</span>
                </div>
                <div class="prod-card__cta">
                  <span class="prod-card__cta-text">Ver produto</span>
                  <span class="prod-card__cta-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                </div>
              </div>
            </a>
          </div>
        </div>
      </section>

      <!-- VIEW: ENDEREÇOS -->
      <section class="mc-view" data-view="enderecos" aria-label="Meus endereços">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Endereços</h2>
            <p class="mc-view__desc">Gerencie os endereços onde recebe os equipamentos.</p>
          </div>
          <button type="button" class="mc-view__action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo endereço
          </button>
        </div>

        <div class="mc-addresses">
          <article class="mc-address is-default">
            <div class="mc-address__head">
              <span class="mc-address__label">Casa</span>
              <span class="mc-address__default-badge">Padrão</span>
            </div>
            <p class="mc-address__body">
              <strong>Rafael Miguel</strong><br>
              Rua Walter Marquardt, 1234 — Apto 302<br>
              Distrito Industrial · Jaraguá do Sul/SC<br>
              CEP 89254-430
            </p>
            <div class="mc-address__actions">
              <button type="button" class="mc-address__action">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
              </button>
              <button type="button" class="mc-address__action mc-address__action--danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                Remover
              </button>
            </div>
          </article>

          <article class="mc-address">
            <div class="mc-address__head">
              <span class="mc-address__label">Academia</span>
            </div>
            <p class="mc-address__body">
              <strong>Ruch Studios Fitness</strong><br>
              Av. Getúlio Vargas, 567 — Sala 1<br>
              Centro · Joinville/SC<br>
              CEP 89201-000
            </p>
            <div class="mc-address__actions">
              <button type="button" class="mc-address__action">Definir padrão</button>
              <button type="button" class="mc-address__action">Editar</button>
              <button type="button" class="mc-address__action mc-address__action--danger">Remover</button>
            </div>
          </article>
        </div>
      </section>

      <!-- VIEW: MEUS DADOS -->
      <section class="mc-view" data-view="dados" aria-label="Meus dados pessoais">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Meus dados</h2>
            <p class="mc-view__desc">Mantenha suas informações atualizadas.</p>
          </div>
        </div>

        <div class="mc-data">
          <div class="mc-data__grid">
            <div class="field">
              <label for="mc-name">Nome completo</label>
              <input type="text" id="mc-name" value="Rafael Miguel Oliveira">
            </div>
            <div class="field">
              <label for="mc-email">E-mail</label>
              <input type="email" id="mc-email" value="rafael@ruch.com.br">
            </div>
            <div class="field">
              <label for="mc-cpf">CPF</label>
              <input type="text" id="mc-cpf" value="000.000.000-00">
            </div>
            <div class="field">
              <label for="mc-phone">Telefone</label>
              <input type="tel" id="mc-phone" value="(47) 99999-9999">
            </div>
            <div class="field">
              <label for="mc-birth">Data de nascimento</label>
              <input type="date" id="mc-birth" value="1990-05-15">
            </div>
            <div class="field">
              <label for="mc-gender">Gênero (opcional)</label>
              <select id="mc-gender">
                <option value="">Prefiro não informar</option>
                <option value="m" selected>Masculino</option>
                <option value="f">Feminino</option>
                <option value="nb">Não-binário</option>
                <option value="o">Outro</option>
              </select>
            </div>
          </div>

          <div class="mc-data__actions">
            <button type="button" class="mc-data__save">Salvar alterações</button>
          </div>
        </div>
      </section>

      <!-- VIEW: SEGURANÇA -->
      <section class="mc-view" data-view="seguranca" aria-label="Segurança da conta">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Segurança</h2>
            <p class="mc-view__desc">Senha, verificação em 2 etapas e cartões tokenizados.</p>
          </div>
        </div>

        <div class="mc-sec">

          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Alterar senha
            </h3>
            <p class="mc-sec-block__desc">Última alteração há 2 meses. Recomendamos trocar a cada 6 meses.</p>

            <div class="mc-data__grid mc-data__grid--pw">

              <div class="field field--icon">
                <label for="mc-pw-current">Senha atual</label>
                <input type="password" id="mc-pw-current" placeholder="Digite sua senha atual" autocomplete="current-password">
                <span class="field__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <button type="button" class="field__toggle" data-toggle-for="mc-pw-current" aria-label="Mostrar senha">
                  <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>

              <div class="field field--icon">
                <label for="mc-pw-new">Nova senha</label>
                <input type="password" id="mc-pw-new" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                <span class="field__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <button type="button" class="field__toggle" data-toggle-for="mc-pw-new" aria-label="Mostrar senha">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <div class="pw-strength" id="pwStrength" data-level="0" aria-live="polite">
                  <div class="pw-strength__bars" aria-hidden="true">
                    <span class="pw-strength__bar"></span>
                    <span class="pw-strength__bar"></span>
                    <span class="pw-strength__bar"></span>
                    <span class="pw-strength__bar"></span>
                  </div>
                  <div class="pw-strength__text">
                    <span>Use letras, números e símbolos</span>
                    <span class="pw-strength__label" id="pwStrengthLabel">—</span>
                  </div>
                </div>
              </div>

              <div class="field field--icon">
                <label for="mc-pw-confirm">Confirmar nova senha</label>
                <input type="password" id="mc-pw-confirm" placeholder="Repita a nova senha" autocomplete="new-password">
                <span class="field__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <button type="button" class="field__toggle" data-toggle-for="mc-pw-confirm" aria-label="Mostrar senha">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <div class="pw-match" id="pwMatch" aria-live="polite">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>As senhas coincidem</span>
                </div>
              </div>

            </div>

            <div class="mc-data__actions">
              <button type="button" class="mc-data__save">Atualizar senha</button>
            </div>
          </div>

          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Verificação em 2 etapas
            </h3>
            <p class="mc-sec-block__desc">Segurança extra: código via SMS sempre que fizer login em novo dispositivo.</p>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>2FA por SMS</strong>
                <small>Código no número (47) 99999-9999</small>
              </div>
              <label class="mc-toggle">
                <input type="checkbox" id="mc-2fa">
                <span class="mc-toggle__track" aria-hidden="true"></span>
              </label>
            </div>
          </div>

          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              Cartões salvos
            </h3>
            <p class="mc-sec-block__desc">Tokenizados com criptografia PCI-DSS · nunca armazenamos o número completo.</p>
            <div class="mc-card">
              <div class="mc-card__brand">VISA</div>
              <div class="mc-card__info">
                <span class="mc-card__number">•••• •••• •••• 4242</span>
                <span class="mc-card__meta">Rafael M. Oliveira · Vence 08/2028</span>
              </div>
              <button type="button" class="mc-card__remove" aria-label="Remover cartão">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

        </div>
      </section>

      <!-- VIEW: NOTIFICAÇÕES -->
      <section class="mc-view" data-view="notificacoes" aria-label="Preferências de notificação">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Notificações</h2>
            <p class="mc-view__desc">Escolha o que quer receber por e-mail e WhatsApp.</p>
          </div>
        </div>

        <div class="mc-sec">
          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              Por e-mail
            </h3>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Status do pedido</strong>
                <small>Confirmação, produção, envio, entrega</small>
              </div>
              <label class="mc-toggle"><input type="checkbox" checked><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Nota fiscal</strong>
                <small>NFe assim que o pedido for emitido</small>
              </div>
              <label class="mc-toggle"><input type="checkbox" checked><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Ofertas e novidades</strong>
                <small>Lançamentos, promoções, cupons</small>
              </div>
              <label class="mc-toggle"><input type="checkbox"><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
          </div>

          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              Por WhatsApp
            </h3>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Atualizações de entrega</strong>
                <small>Rastreio em tempo real até chegar</small>
              </div>
              <label class="mc-toggle"><input type="checkbox" checked><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Comunicação do time comercial</strong>
                <small>Resposta de aluguel, B2B, orçamento</small>
              </div>
              <label class="mc-toggle"><input type="checkbox" checked><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>Ofertas relâmpago</strong>
                <small>Descontos de última hora</small>
              </div>
              <label class="mc-toggle"><input type="checkbox"><span class="mc-toggle__track" aria-hidden="true"></span></label>
            </div>
          </div>
        </div>
      </section>

    </div>

  </div>

</main>
