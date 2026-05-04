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

// Dados reais do customer Woo
$customer       = new WC_Customer( $current_user->ID );
$billing_phone  = $customer->get_billing_phone();
$billing_first  = $customer->get_billing_first_name();
$billing_last   = $customer->get_billing_last_name();
$user_full_name = trim( $billing_first . ' ' . $billing_last ) ?: $current_user->display_name;
$user_cpf       = get_user_meta( $current_user->ID, 'billing_cpf', true );
$user_birthdate = get_user_meta( $current_user->ID, 'billing_birthdate', true );
$user_gender    = get_user_meta( $current_user->ID, 'billing_gender', true );

// Endereços (billing + shipping) — montagem das linhas
$ba1 = $customer->get_billing_address_1();
$ba_num = get_user_meta( $current_user->ID, 'billing_number', true );
$ba_neigh = get_user_meta( $current_user->ID, 'billing_neighborhood', true );
$ba_city = $customer->get_billing_city();
$ba_state = $customer->get_billing_state();
$ba_pc = $customer->get_billing_postcode();
$has_billing = $ba1 || $ba_pc;

$sa1 = $customer->get_shipping_address_1();
$sa_num = get_user_meta( $current_user->ID, 'shipping_number', true );
$sa_neigh = get_user_meta( $current_user->ID, 'shipping_neighborhood', true );
$sa_city = $customer->get_shipping_city();
$sa_state = $customer->get_shipping_state();
$sa_pc = $customer->get_shipping_postcode();
$has_shipping = $sa1 || $sa_pc;
$shipping_differs_billing = $has_shipping && ( $sa1 !== $ba1 || $sa_pc !== $ba_pc );

// Cartões salvos (tokens WC)
$payment_tokens = WC_Payment_Tokens::get_customer_tokens( $current_user->ID );

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

        <?php
        // Lista favoritos do usuário a partir do user meta `vaxx_favoritos` (array de product IDs).
        // Se o cliente integrar plugin de wishlist no futuro, basta apontar o filtro `vaxx_user_favorites_ids` pra fonte real.
        $fav_ids = apply_filters( 'vaxx_user_favorites_ids', (array) get_user_meta( $current_user->ID, 'vaxx_favoritos', true ), $current_user->ID );
        $fav_ids = array_filter( array_map( 'intval', $fav_ids ) );
        ?>

        <?php if ( empty( $fav_ids ) ) : ?>
          <div class="mc-empty">
            <div class="mc-empty__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </div>
            <h3 class="mc-empty__title">Nenhum favorito ainda</h3>
            <p class="mc-empty__desc">Marque produtos com o ícone de coração na página do produto pra encontrá-los aqui.</p>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="mc-empty__cta">Ver catálogo</a>
          </div>
        <?php else : ?>
          <div class="mc-favorites">
            <?php foreach ( $fav_ids as $fav_id ) :
              $fav_product = wc_get_product( $fav_id );
              if ( ! $fav_product || ! $fav_product->is_visible() ) continue;
              $fav_thumb = get_the_post_thumbnail_url( $fav_id, 'vaxx-prod-card' ) ?: ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'vaxx-prod-card' ) : '' );
              $fav_lines = wp_get_post_terms( $fav_id, 'product_line' );
              $fav_line  = $fav_lines && ! is_wp_error( $fav_lines ) ? $fav_lines[0]->name : '';
              $fav_groups = wp_get_post_terms( $fav_id, 'muscle_group' );
              $has_reg   = (bool) get_post_meta( $fav_id, 'vaxx_regulagem_real', true );
              $reg_val   = get_post_meta( $fav_id, 'vaxx_regulagem', true ) ?: '1,55–1,95';
            ?>
              <div class="mc-fav-card">
                <button type="button" class="mc-fav-remove" data-product-id="<?php echo (int) $fav_id; ?>" aria-label="Remover dos favoritos">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
                <a href="<?php echo esc_url( $fav_product->get_permalink() ); ?>" class="prod-card">
                  <div class="prod-card__media">
                    <?php if ( $fav_line ) : ?><span class="prod-card__line-badge"><?php echo esc_html( $fav_line ); ?></span><?php endif; ?>
                    <?php if ( $has_reg ) : ?><span class="prod-card__regulagem"><?php echo esc_html( $reg_val ); ?></span><?php endif; ?>
                    <?php if ( $fav_thumb ) : ?><img src="<?php echo esc_url( $fav_thumb ); ?>" alt="<?php echo esc_attr( $fav_product->get_name() ); ?>" loading="lazy"><?php endif; ?>
                  </div>
                  <div class="prod-card__body">
                    <h3 class="prod-card__title"><?php echo esc_html( $fav_product->get_name() ); ?></h3>
                    <?php if ( $fav_groups && ! is_wp_error( $fav_groups ) ) : ?>
                    <div class="prod-card__tags">
                      <?php foreach ( $fav_groups as $g ) : ?><span class="prod-card__tag"><?php echo esc_html( $g->name ); ?></span><?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="prod-card__cta">
                      <span class="prod-card__cta-text">Ver produto</span>
                      <span class="prod-card__cta-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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

        <?php if ( ! $has_billing && ! $has_shipping ) : ?>
          <div class="mc-empty">
            <div class="mc-empty__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <h3 class="mc-empty__title">Nenhum endereço cadastrado</h3>
            <p class="mc-empty__desc">Adicione um endereço pra agilizar o checkout dos seus pedidos.</p>
            <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="mc-empty__cta">Adicionar endereço</a>
          </div>
        <?php else : ?>
        <div class="mc-addresses">
          <?php if ( $has_billing ) :
            $billing_linha1 = trim( $ba1 . ( $ba1 && $ba_num ? ', ' : '' ) . $ba_num . ( ( $ba1 || $ba_num ) && $ba_neigh ? ' — ' : '' ) . $ba_neigh );
            $billing_linha2 = trim( $ba_city . ( $ba_city && $ba_state ? '/' . $ba_state : ( $ba_state ?: '' ) ) );
          ?>
          <article class="mc-address is-default">
            <div class="mc-address__head">
              <span class="mc-address__label">Cobrança</span>
              <span class="mc-address__default-badge">Padrão</span>
            </div>
            <p class="mc-address__body">
              <strong><?php echo esc_html( $user_full_name ); ?></strong><br>
              <?php if ( $billing_linha1 ) : ?><?php echo esc_html( $billing_linha1 ); ?><br><?php endif; ?>
              <?php if ( $billing_linha2 ) : ?><?php echo esc_html( $billing_linha2 ); ?><br><?php endif; ?>
              <?php if ( $ba_pc ) : ?>CEP <?php echo esc_html( $ba_pc ); ?><?php endif; ?>
            </p>
            <div class="mc-address__actions">
              <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="mc-address__action">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
              </a>
            </div>
          </article>
          <?php endif; ?>

          <?php if ( $shipping_differs_billing ) :
            $shipping_first = $customer->get_shipping_first_name();
            $shipping_last  = $customer->get_shipping_last_name();
            $shipping_name  = trim( $shipping_first . ' ' . $shipping_last ) ?: $user_full_name;
            $shipping_linha1 = trim( $sa1 . ( $sa1 && $sa_num ? ', ' : '' ) . $sa_num . ( ( $sa1 || $sa_num ) && $sa_neigh ? ' — ' : '' ) . $sa_neigh );
            $shipping_linha2 = trim( $sa_city . ( $sa_city && $sa_state ? '/' . $sa_state : ( $sa_state ?: '' ) ) );
          ?>
          <article class="mc-address">
            <div class="mc-address__head">
              <span class="mc-address__label">Entrega</span>
            </div>
            <p class="mc-address__body">
              <strong><?php echo esc_html( $shipping_name ); ?></strong><br>
              <?php if ( $shipping_linha1 ) : ?><?php echo esc_html( $shipping_linha1 ); ?><br><?php endif; ?>
              <?php if ( $shipping_linha2 ) : ?><?php echo esc_html( $shipping_linha2 ); ?><br><?php endif; ?>
              <?php if ( $sa_pc ) : ?>CEP <?php echo esc_html( $sa_pc ); ?><?php endif; ?>
            </p>
            <div class="mc-address__actions">
              <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'shipping', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="mc-address__action">Editar</a>
            </div>
          </article>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </section>

      <!-- VIEW: MEUS DADOS -->
      <section class="mc-view" data-view="dados" aria-label="Meus dados pessoais">
        <div class="mc-view__header">
          <div>
            <h2 class="mc-view__title">Meus dados</h2>
            <p class="mc-view__desc">Mantenha suas informações atualizadas.</p>
          </div>
        </div>

        <form method="post" class="mc-data" action="<?php echo esc_url( wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>">
          <div class="mc-data__grid">
            <div class="field">
              <label for="mc-name">Nome completo</label>
              <input type="text" id="mc-name" name="account_display_name" value="<?php echo esc_attr( $user_full_name ); ?>" placeholder="Como aparece nos pedidos">
            </div>
            <div class="field">
              <label for="mc-email">E-mail</label>
              <input type="email" id="mc-email" name="account_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" placeholder="seu@email.com">
            </div>
            <div class="field">
              <label for="mc-cpf">CPF</label>
              <input type="text" id="mc-cpf" name="billing_cpf" value="<?php echo esc_attr( $user_cpf ); ?>" placeholder="000.000.000-00">
            </div>
            <div class="field">
              <label for="mc-phone">Telefone</label>
              <input type="tel" id="mc-phone" name="billing_phone" value="<?php echo esc_attr( $billing_phone ); ?>" placeholder="(00) 00000-0000">
            </div>
            <div class="field">
              <label for="mc-birth">Data de nascimento</label>
              <input type="date" id="mc-birth" name="billing_birthdate" value="<?php echo esc_attr( $user_birthdate ); ?>">
            </div>
            <div class="field">
              <label for="mc-gender">Gênero (opcional)</label>
              <select id="mc-gender" name="billing_gender">
                <option value=""<?php selected( $user_gender, '' ); ?>>Prefiro não informar</option>
                <option value="m"<?php selected( $user_gender, 'm' ); ?>>Masculino</option>
                <option value="f"<?php selected( $user_gender, 'f' ); ?>>Feminino</option>
                <option value="nb"<?php selected( $user_gender, 'nb' ); ?>>Não-binário</option>
                <option value="o"<?php selected( $user_gender, 'o' ); ?>>Outro</option>
              </select>
            </div>
          </div>

          <div class="mc-data__actions">
            <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
            <input type="hidden" name="action" value="save_account_details">
            <button type="submit" class="mc-data__save">Salvar alterações</button>
          </div>
        </form>
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

          <?php if ( $billing_phone ) : ?>
          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Verificação em 2 etapas
            </h3>
            <p class="mc-sec-block__desc">Segurança extra: código via SMS sempre que fizer login em novo dispositivo.</p>
            <div class="mc-toggle-row">
              <div class="mc-toggle-row__text">
                <strong>2FA por SMS</strong>
                <small>Código no número <?php echo esc_html( $billing_phone ); ?></small>
              </div>
              <label class="mc-toggle">
                <input type="checkbox" id="mc-2fa"<?php checked( get_user_meta( $current_user->ID, 'vaxx_2fa_sms', true ), '1' ); ?>>
                <span class="mc-toggle__track" aria-hidden="true"></span>
              </label>
            </div>
          </div>
          <?php endif; ?>

          <div class="mc-sec-block">
            <h3 class="mc-sec-block__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              Cartões salvos
            </h3>
            <p class="mc-sec-block__desc">Tokenizados com criptografia PCI-DSS · nunca armazenamos o número completo.</p>

            <?php if ( empty( $payment_tokens ) ) : ?>
              <p class="mc-sec-block__empty">Você ainda não tem cartões salvos. Eles aparecem aqui após a primeira compra com cartão.</p>
            <?php else : ?>
              <?php foreach ( $payment_tokens as $token ) :
                if ( ! ( $token instanceof WC_Payment_Token_CC ) ) continue;
                $brand = strtoupper( $token->get_card_type() );
                $last4 = $token->get_last4();
                $exp_m = str_pad( $token->get_expiry_month(), 2, '0', STR_PAD_LEFT );
                $exp_y = $token->get_expiry_year();
              ?>
              <div class="mc-card">
                <div class="mc-card__brand"><?php echo esc_html( $brand ); ?></div>
                <div class="mc-card__info">
                  <span class="mc-card__number">•••• •••• •••• <?php echo esc_html( $last4 ); ?></span>
                  <span class="mc-card__meta">Vence <?php echo esc_html( $exp_m . '/' . $exp_y ); ?></span>
                </div>
                <a href="<?php echo esc_url( wc_get_endpoint_url( 'payment-methods', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="mc-card__remove" aria-label="Gerenciar cartão">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </a>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
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
