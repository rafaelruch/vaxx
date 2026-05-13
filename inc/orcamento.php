<?php
/**
 * VAXX · Fluxo de Orçamento
 *
 * O carrinho NÃO leva para pagamento — leva para /orcamento/ onde o cliente
 * preenche dados (PF/PJ, contato, endereço com ViaCEP) e envia. Submit cria:
 *   1. Customer WP/WC (ou usa existente pelo e-mail)
 *   2. WC_Order com status custom `wc-orcamento` (sem pagamento)
 *   3. Esvazia o carrinho
 *   4. Notifica comercial por e-mail + gera deep link wa.me do cliente
 *   5. Envia confirmação ao cliente
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const VAXX_ORCAMENTO_SLUG = 'orcamento';

/**
 * Registra o status custom `wc-orcamento`.
 */
add_action( 'init', function() {
	register_post_status( 'wc-orcamento', array(
		'label'                     => 'Orçamento',
		'public'                    => true,
		'show_in_admin_status_list' => true,
		'show_in_admin_all_list'    => true,
		'exclude_from_search'       => false,
		'label_count'               => _n_noop(
			'Orçamento <span class="count">(%s)</span>',
			'Orçamentos <span class="count">(%s)</span>',
			'vaxx'
		),
	) );
} );

/**
 * Adiciona "Orçamento" na lista de status do WC (admin + customer-facing).
 */
add_filter( 'wc_order_statuses', function( $statuses ) {
	$new = array();
	foreach ( $statuses as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'wc-pending' ) {
			$new['wc-orcamento'] = 'Orçamento';
		}
	}
	if ( ! isset( $new['wc-orcamento'] ) ) {
		$new['wc-orcamento'] = 'Orçamento';
	}
	return $new;
} );

/**
 * Inclui `wc-orcamento` na lista de orders visíveis em /minha-conta/pedidos/
 * (defesa — o template já usa array_keys(wc_get_order_statuses()) mas isso
 * garante caso outro lugar do core filtre statuses).
 */
add_filter( 'woocommerce_my_account_my_orders_query', function( $args ) {
	$args['status'] = array_keys( wc_get_order_statuses() );
	return $args;
} );

/**
 * Cria a página /orcamento/ se ainda não existe. Guard por transient pra
 * evitar lookup em todo request. Roda também em after_switch_theme.
 */
function vaxx_ensure_orcamento_page() {
	$page = get_page_by_path( VAXX_ORCAMENTO_SLUG );
	if ( $page ) return $page->ID;
	$id = wp_insert_post( array(
		'post_title'   => 'Solicitar orçamento',
		'post_name'    => VAXX_ORCAMENTO_SLUG,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '<!-- wp:shortcode -->[vaxx_orcamento]<!-- /wp:shortcode -->',
	) );
	return is_wp_error( $id ) ? 0 : (int) $id;
}
add_action( 'after_switch_theme', 'vaxx_ensure_orcamento_page' );
add_action( 'init', function() {
	if ( wp_doing_ajax() || wp_doing_cron() ) return;
	if ( get_transient( 'vaxx_orc_page_ok' ) ) return;
	vaxx_ensure_orcamento_page();
	set_transient( 'vaxx_orc_page_ok', 1, DAY_IN_SECONDS );
}, 20 );

/**
 * Helper: URL absoluta da página de orçamento.
 */
function vaxx_orcamento_url() {
	$page = get_page_by_path( VAXX_ORCAMENTO_SLUG );
	return $page ? get_permalink( $page->ID ) : home_url( '/' . VAXX_ORCAMENTO_SLUG . '/' );
}

/**
 * Redireciona /finalizar-compra/ → /orcamento/ se o carrinho tem itens.
 * (Endpoints order-received e order-pay continuam funcionando.)
 */
add_action( 'template_redirect', function() {
	if ( ! function_exists( 'is_checkout' ) ) return;
	if ( ! is_checkout() ) return;
	if ( is_wc_endpoint_url() ) return; // order-received, order-pay
	if ( ! WC()->cart || WC()->cart->is_empty() ) return;
	wp_safe_redirect( vaxx_orcamento_url() );
	exit;
}, 5 );

/**
 * Shortcode [vaxx_orcamento] — form + resumo do carrinho.
 * Se houver ?orcamento=<id> na URL, renderiza tela de confirmação.
 */
function vaxx_shortcode_orcamento() {
	if ( ! class_exists( 'WooCommerce' ) ) return '';

	// Tela de confirmação pós-submit
	if ( isset( $_GET['orcamento'] ) ) {
		$order_id = absint( $_GET['orcamento'] );
		$order = $order_id ? wc_get_order( $order_id ) : null;
		if ( $order && current_user_can( 'read_post', $order_id ) ) {
			return vaxx_render_orcamento_thanks( $order );
		}
	}

	$cart = WC()->cart;
	$items = $cart ? $cart->get_cart() : array();

	if ( empty( $items ) ) {
		ob_start(); ?>
		<div class="vx-orc vx-orc--empty">
			<div class="vx-orc__inner">
				<div class="vx-orc-empty">
					<div class="vx-orc-empty__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
					</div>
					<h2>Seu carrinho está vazio</h2>
					<p>Adicione equipamentos ao carrinho antes de solicitar um orçamento.</p>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="vx-orc-empty__cta">Ver catálogo</a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// Pré-preenche se logado
	$pref = vaxx_orcamento_prefill();

	$err = isset( $_GET['orc_err'] ) ? sanitize_key( $_GET['orc_err'] ) : '';

	ob_start();
	?>
	<div class="vx-orc">
		<div class="vx-orc__inner">

			<header class="vx-orc__hero">
				<span class="vx-orc__eyebrow">Etapa 3 · Final</span>
				<h1 class="vx-orc__title">Solicitar orçamento</h1>
				<p class="vx-orc__lead">Preencha seus dados pra finalizar. Nosso time entra em contato em até 1 dia útil com proposta completa, frete e prazos.</p>
			</header>

			<?php if ( $err ) : ?>
			<div class="vx-orc__notice vx-orc__notice--error" role="alert">
				<?php echo esc_html( vaxx_orcamento_err_msg( $err ) ); ?>
			</div>
			<?php endif; ?>

			<div class="vx-orc__layout">

				<form class="vx-orc__form" id="vxOrcForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
					<input type="hidden" name="action" value="vaxx_submit_orcamento">
					<?php wp_nonce_field( 'vaxx_orcamento', 'vaxx_orc_nonce' ); ?>

					<fieldset class="vx-orc__fieldset">
						<legend class="vx-orc__legend">Tipo de pessoa</legend>
						<div class="vx-orc__radio-group" role="radiogroup">
							<label class="vx-orc__radio">
								<input type="radio" name="tipo_pessoa" value="pf" <?php checked( $pref['tipo_pessoa'], 'pf' ); ?>>
								<span class="vx-orc__radio-box">
									<span class="vx-orc__radio-title">Pessoa Física</span>
									<span class="vx-orc__radio-desc">Compra individual · home gym</span>
								</span>
							</label>
							<label class="vx-orc__radio">
								<input type="radio" name="tipo_pessoa" value="pj" <?php checked( $pref['tipo_pessoa'], 'pj' ); ?>>
								<span class="vx-orc__radio-box">
									<span class="vx-orc__radio-title">Pessoa Jurídica</span>
									<span class="vx-orc__radio-desc">Academia · condomínio · empresa</span>
								</span>
							</label>
						</div>
					</fieldset>

					<fieldset class="vx-orc__fieldset" data-pf<?php echo $pref['tipo_pessoa'] === 'pj' ? ' hidden' : ''; ?>>
						<legend class="vx-orc__legend">Identificação</legend>
						<div class="vx-orc__grid">
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcNome">Nome completo *</label>
								<input type="text" id="vxOrcNome" name="nome" value="<?php echo esc_attr( $pref['nome'] ); ?>" autocomplete="name" required>
							</div>
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcCpf">CPF *</label>
								<input type="text" id="vxOrcCpf" name="cpf" value="<?php echo esc_attr( $pref['cpf'] ); ?>" data-mask="cpf" inputmode="numeric" placeholder="000.000.000-00">
							</div>
						</div>
					</fieldset>

					<fieldset class="vx-orc__fieldset" data-pj<?php echo $pref['tipo_pessoa'] !== 'pj' ? ' hidden' : ''; ?>>
						<legend class="vx-orc__legend">Identificação</legend>
						<div class="vx-orc__grid">
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcRazao">Razão Social *</label>
								<input type="text" id="vxOrcRazao" name="razao_social" value="<?php echo esc_attr( $pref['razao_social'] ); ?>" autocomplete="organization">
							</div>
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcCnpj">CNPJ *</label>
								<input type="text" id="vxOrcCnpj" name="cnpj" value="<?php echo esc_attr( $pref['cnpj'] ); ?>" data-mask="cnpj" inputmode="numeric" placeholder="00.000.000/0000-00">
							</div>
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcResp">Nome do responsável *</label>
								<input type="text" id="vxOrcResp" name="responsavel" value="<?php echo esc_attr( $pref['nome'] ); ?>" autocomplete="name">
							</div>
						</div>
					</fieldset>

					<fieldset class="vx-orc__fieldset">
						<legend class="vx-orc__legend">Contato</legend>
						<div class="vx-orc__grid">
							<div class="vx-orc__field">
								<label for="vxOrcEmail">E-mail *</label>
								<input type="email" id="vxOrcEmail" name="email" value="<?php echo esc_attr( $pref['email'] ); ?>" autocomplete="email" required placeholder="seu@email.com">
							</div>
							<div class="vx-orc__field">
								<label for="vxOrcTel">Telefone / WhatsApp *</label>
								<input type="tel" id="vxOrcTel" name="telefone" value="<?php echo esc_attr( $pref['telefone'] ); ?>" data-mask="phone" autocomplete="tel" required placeholder="(00) 00000-0000">
							</div>
						</div>
					</fieldset>

					<fieldset class="vx-orc__fieldset">
						<legend class="vx-orc__legend">Endereço de entrega</legend>
						<div class="vx-orc__grid">
							<div class="vx-orc__field">
								<label for="vxOrcCep">CEP *</label>
								<input type="text" id="vxOrcCep" name="cep" value="<?php echo esc_attr( $pref['cep'] ); ?>" data-mask="cep" inputmode="numeric" placeholder="00000-000" autocomplete="postal-code" required>
								<span class="vx-orc__hint" id="vxOrcCepHint" aria-live="polite"></span>
							</div>
							<div class="vx-orc__field vx-orc__field--full">
								<label for="vxOrcRua">Rua / Logradouro *</label>
								<input type="text" id="vxOrcRua" name="rua" value="<?php echo esc_attr( $pref['rua'] ); ?>" autocomplete="address-line1" required>
							</div>
							<div class="vx-orc__field">
								<label for="vxOrcNum">Número *</label>
								<input type="text" id="vxOrcNum" name="numero" value="<?php echo esc_attr( $pref['numero'] ); ?>" required>
							</div>
							<div class="vx-orc__field">
								<label for="vxOrcComp">Complemento</label>
								<input type="text" id="vxOrcComp" name="complemento" value="<?php echo esc_attr( $pref['complemento'] ); ?>" autocomplete="address-line2" placeholder="Apto, bloco, sala…">
							</div>
							<div class="vx-orc__field">
								<label for="vxOrcBairro">Bairro *</label>
								<input type="text" id="vxOrcBairro" name="bairro" value="<?php echo esc_attr( $pref['bairro'] ); ?>" required>
							</div>
							<div class="vx-orc__field">
								<label for="vxOrcCidade">Cidade *</label>
								<input type="text" id="vxOrcCidade" name="cidade" value="<?php echo esc_attr( $pref['cidade'] ); ?>" autocomplete="address-level2" required>
							</div>
							<div class="vx-orc__field vx-orc__field--uf">
								<label for="vxOrcUf">UF *</label>
								<input type="text" id="vxOrcUf" name="uf" value="<?php echo esc_attr( $pref['uf'] ); ?>" maxlength="2" autocomplete="address-level1" required>
							</div>
						</div>
					</fieldset>

					<div class="vx-orc__submit-row">
						<button type="submit" class="vx-orc__submit" id="vxOrcSubmit">
							<span class="vx-orc__submit-label">Fazer orçamento</span>
							<svg class="vx-orc__submit-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
						</button>
						<p class="vx-orc__legal">Sem compromisso. Resposta em até 1 dia útil. Ao enviar você concorda com nossos <a href="<?php echo esc_url( home_url( '/termos-de-uso/' ) ); ?>">Termos</a> e <a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>">Política de Privacidade</a>.</p>
					</div>
				</form>

				<aside class="vx-orc__summary" aria-label="Resumo do orçamento">
					<div class="vx-orc__summary-inner">
						<h2 class="vx-orc__summary-title">Seu orçamento</h2>
						<ul class="vx-orc__items">
							<?php foreach ( $items as $item ) :
								$product = $item['data']; if ( ! $product ) continue;
								$thumb = get_the_post_thumbnail_url( $product->get_id(), 'vaxx-prod-thumb' );
								if ( ! $thumb && function_exists( 'wc_placeholder_img_src' ) ) {
									$thumb = wc_placeholder_img_src( 'vaxx-prod-thumb' );
								}
							?>
							<li class="vx-orc__item">
								<div class="vx-orc__item-media"><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>"></div>
								<div class="vx-orc__item-body">
									<strong class="vx-orc__item-name"><?php echo esc_html( $product->get_name() ); ?></strong>
									<span class="vx-orc__item-qty"><?php echo (int) $item['quantity']; ?>×</span>
								</div>
								<span class="vx-orc__item-price"><?php echo wp_kses_post( wc_price( $product->get_price() * $item['quantity'] ) ); ?></span>
							</li>
							<?php endforeach; ?>
						</ul>
						<div class="vx-orc__total">
							<span class="vx-orc__total-label">Subtotal estimado</span>
							<strong class="vx-orc__total-value"><?php echo wp_kses_post( wc_price( $cart->get_subtotal() ) ); ?></strong>
						</div>
						<p class="vx-orc__note">Valor final pode variar conforme frete, montagem e condições negociadas direto com a fábrica.</p>
					</div>
				</aside>

			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'vaxx_orcamento', 'vaxx_shortcode_orcamento' );

/**
 * Tela de confirmação pós-submit (renderizada quando ?orcamento=<id>).
 */
function vaxx_render_orcamento_thanks( $order ) {
	$nome = $order->get_billing_first_name();
	$wa   = vaxx_get_option( 'whatsapp_numero', '' );
	$wa_link = $wa ? 'https://wa.me/' . preg_replace( '/\D/', '', $wa ) . '?text=' . rawurlencode( "Olá! Sou {$nome}, acabei de solicitar o orçamento #{$order->get_id()}." ) : '';
	ob_start();
	?>
	<div class="vx-orc vx-orc--thanks">
		<div class="vx-orc__inner">
			<div class="vx-orc-thanks">
				<div class="vx-orc-thanks__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
				</div>
				<h1 class="vx-orc-thanks__title">Orçamento enviado!</h1>
				<p class="vx-orc-thanks__lead">Recebemos seu pedido <strong>#<?php echo (int) $order->get_id(); ?></strong>, <?php echo esc_html( $nome ); ?>. Nosso time entra em contato em até <strong>1 dia útil</strong> com proposta completa.</p>

				<div class="vx-orc-thanks__panel">
					<div class="vx-orc-thanks__row">
						<span>Pedido</span>
						<strong>#<?php echo (int) $order->get_id(); ?></strong>
					</div>
					<div class="vx-orc-thanks__row">
						<span>Status</span>
						<strong><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></strong>
					</div>
					<div class="vx-orc-thanks__row">
						<span>Itens</span>
						<strong><?php echo (int) $order->get_item_count(); ?></strong>
					</div>
					<div class="vx-orc-thanks__row">
						<span>Subtotal estimado</span>
						<strong><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></strong>
					</div>
				</div>

				<div class="vx-orc-thanks__cta-row">
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="vx-orc-thanks__cta vx-orc-thanks__cta--primary">Ver meus pedidos</a>
					<?php if ( $wa_link ) : ?>
					<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener" class="vx-orc-thanks__cta vx-orc-thanks__cta--wa">Adiantar pelo WhatsApp</a>
					<?php endif; ?>
				</div>

				<p class="vx-orc-thanks__hint">Enviamos uma cópia do orçamento para <strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>.</p>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Pré-preenche o form se cliente está logado.
 */
function vaxx_orcamento_prefill() {
	$defaults = array(
		'tipo_pessoa'  => 'pf',
		'nome'         => '',
		'cpf'          => '',
		'razao_social' => '',
		'cnpj'         => '',
		'email'        => '',
		'telefone'     => '',
		'cep'          => '',
		'rua'          => '',
		'numero'       => '',
		'complemento'  => '',
		'bairro'       => '',
		'cidade'       => '',
		'uf'           => '',
	);
	if ( ! is_user_logged_in() ) return $defaults;

	$uid = get_current_user_id();
	$user = wp_get_current_user();
	$customer = class_exists( 'WC_Customer' ) ? new WC_Customer( $uid ) : null;

	$persontype = get_user_meta( $uid, 'billing_persontype', true );
	$defaults['tipo_pessoa']  = ( $persontype === '2' ) ? 'pj' : 'pf';
	$defaults['nome']         = trim( ( $user->first_name ?: '' ) . ' ' . ( $user->last_name ?: '' ) ) ?: $user->display_name;
	$defaults['cpf']          = get_user_meta( $uid, 'billing_cpf', true );
	$defaults['razao_social'] = $customer ? $customer->get_billing_company() : '';
	$defaults['cnpj']         = get_user_meta( $uid, 'billing_cnpj', true );
	$defaults['email']        = $user->user_email;
	$defaults['telefone']     = $customer ? $customer->get_billing_phone() : '';
	$defaults['cep']          = $customer ? $customer->get_billing_postcode() : '';
	$defaults['rua']          = $customer ? $customer->get_billing_address_1() : '';
	$defaults['numero']       = get_user_meta( $uid, 'billing_number', true );
	$defaults['complemento']  = $customer ? $customer->get_billing_address_2() : '';
	$defaults['bairro']       = get_user_meta( $uid, 'billing_neighborhood', true );
	$defaults['cidade']       = $customer ? $customer->get_billing_city() : '';
	$defaults['uf']           = $customer ? $customer->get_billing_state() : '';
	return $defaults;
}

/**
 * Mensagens de erro para query param `?orc_err=...`.
 */
function vaxx_orcamento_err_msg( $key ) {
	$map = array(
		'missing' => 'Preencha todos os campos obrigatórios e tente novamente.',
		'email'   => 'O e-mail informado é inválido.',
		'user'    => 'Não foi possível criar sua conta. Verifique se você já tem cadastro com este e-mail.',
		'order'   => 'Não conseguimos registrar o orçamento. Tente novamente em instantes.',
		'nonce'   => 'Sessão expirada. Volte ao formulário e envie de novo.',
	);
	return $map[ $key ] ?? 'Erro no envio. Tente novamente.';
}

/**
 * Handler do submit do form.
 */
function vaxx_handle_orcamento_submit() {
	if ( ! isset( $_POST['vaxx_orc_nonce'] ) || ! wp_verify_nonce( $_POST['vaxx_orc_nonce'], 'vaxx_orcamento' ) ) {
		wp_safe_redirect( add_query_arg( 'orc_err', 'nonce', vaxx_orcamento_url() ) );
		exit;
	}
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	$tipo  = sanitize_text_field( wp_unslash( $_POST['tipo_pessoa'] ?? 'pf' ) );
	$is_pj = ( $tipo === 'pj' );

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$tel   = sanitize_text_field( wp_unslash( $_POST['telefone'] ?? '' ) );

	if ( $is_pj ) {
		$nome  = sanitize_text_field( wp_unslash( $_POST['responsavel'] ?? '' ) );
		$razao = sanitize_text_field( wp_unslash( $_POST['razao_social'] ?? '' ) );
		$cnpj  = sanitize_text_field( wp_unslash( $_POST['cnpj'] ?? '' ) );
		$cpf   = '';
	} else {
		$nome  = sanitize_text_field( wp_unslash( $_POST['nome'] ?? '' ) );
		$razao = '';
		$cnpj  = '';
		$cpf   = sanitize_text_field( wp_unslash( $_POST['cpf'] ?? '' ) );
	}

	$cep    = sanitize_text_field( wp_unslash( $_POST['cep'] ?? '' ) );
	$rua    = sanitize_text_field( wp_unslash( $_POST['rua'] ?? '' ) );
	$num    = sanitize_text_field( wp_unslash( $_POST['numero'] ?? '' ) );
	$comp   = sanitize_text_field( wp_unslash( $_POST['complemento'] ?? '' ) );
	$bairro = sanitize_text_field( wp_unslash( $_POST['bairro'] ?? '' ) );
	$cidade = sanitize_text_field( wp_unslash( $_POST['cidade'] ?? '' ) );
	$uf     = strtoupper( substr( sanitize_text_field( wp_unslash( $_POST['uf'] ?? '' ) ), 0, 2 ) );

	if ( ! $email || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'orc_err', 'email', vaxx_orcamento_url() ) );
		exit;
	}
	if ( ! $nome || ! $tel || ! $cep || ! $rua || ! $num || ! $bairro || ! $cidade || ! $uf ) {
		wp_safe_redirect( add_query_arg( 'orc_err', 'missing', vaxx_orcamento_url() ) );
		exit;
	}

	// 1. Acha ou cria customer
	$user_id = email_exists( $email );
	if ( ! $user_id ) {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		// Garante uniqueness do login
		if ( username_exists( $username ) ) {
			$username .= '_' . wp_rand( 100, 999 );
		}
		$password = wp_generate_password( 16 );
		$first_parts = preg_split( '/\s+/', $nome, 2 );
		$user_id = wp_insert_user( array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'first_name' => $first_parts[0] ?? '',
			'last_name'  => $first_parts[1] ?? '',
			'role'       => 'customer',
		) );
		if ( is_wp_error( $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'orc_err', 'user', vaxx_orcamento_url() ) );
			exit;
		}
		// Dispara e-mail de credenciais do WP
		wp_new_user_notification( $user_id, null, 'user' );
	}

	// 2. Atualiza meta/billing do customer
	$first_parts = preg_split( '/\s+/', $nome, 2 );
	update_user_meta( $user_id, 'first_name', $first_parts[0] ?? '' );
	update_user_meta( $user_id, 'last_name', $first_parts[1] ?? '' );
	$meta_pairs = array(
		'billing_first_name' => $first_parts[0] ?? '',
		'billing_last_name'  => $first_parts[1] ?? '',
		'billing_email'      => $email,
		'billing_phone'      => $tel,
		'billing_address_1'  => $rua,
		'billing_number'     => $num,
		'billing_address_2'  => $comp,
		'billing_neighborhood' => $bairro,
		'billing_city'       => $cidade,
		'billing_state'      => $uf,
		'billing_postcode'   => $cep,
		'billing_country'    => 'BR',
		'billing_persontype' => $is_pj ? '2' : '1',
	);
	foreach ( $meta_pairs as $k => $v ) update_user_meta( $user_id, $k, $v );
	if ( $is_pj ) {
		update_user_meta( $user_id, 'billing_cnpj', $cnpj );
		update_user_meta( $user_id, 'billing_company', $razao );
	} else {
		update_user_meta( $user_id, 'billing_cpf', $cpf );
	}

	// 3. Loga o customer pra ele acessar /minha-conta/
	if ( ! is_user_logged_in() ) {
		wc_set_customer_auth_cookie( $user_id );
	}

	// 4. Cria a order
	$order = wc_create_order( array( 'customer_id' => $user_id ) );
	if ( is_wp_error( $order ) ) {
		wp_safe_redirect( add_query_arg( 'orc_err', 'order', vaxx_orcamento_url() ) );
		exit;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$order->add_product( $cart_item['data'], $cart_item['quantity'] );
	}

	$billing = array(
		'first_name' => $first_parts[0] ?? '',
		'last_name'  => $first_parts[1] ?? '',
		'company'    => $is_pj ? $razao : '',
		'address_1'  => $rua,
		'address_2'  => $comp,
		'city'       => $cidade,
		'state'      => $uf,
		'postcode'   => $cep,
		'country'    => 'BR',
		'email'      => $email,
		'phone'      => $tel,
	);
	$order->set_address( $billing, 'billing' );
	$order->set_address( $billing, 'shipping' );

	$order->update_meta_data( '_billing_number', $num );
	$order->update_meta_data( '_billing_neighborhood', $bairro );
	$order->update_meta_data( '_billing_persontype', $is_pj ? '2' : '1' );
	if ( $is_pj ) {
		$order->update_meta_data( '_billing_cnpj', $cnpj );
	} else {
		$order->update_meta_data( '_billing_cpf', $cpf );
	}
	$order->update_meta_data( '_vaxx_orcamento', '1' );

	$order->calculate_totals();
	$order->set_status( 'orcamento', 'Pedido de orçamento criado via formulário no site.' );
	$order->save();

	// 5. Esvazia carrinho
	WC()->cart->empty_cart();

	// 6. Notifica responsável + cliente
	vaxx_notify_orcamento( $order, $is_pj, array(
		'nome'    => $nome,
		'razao'   => $razao,
		'cnpj'    => $cnpj,
		'cpf'     => $cpf,
		'email'   => $email,
		'tel'     => $tel,
		'cep'     => $cep,
		'rua'     => $rua,
		'numero'  => $num,
		'comp'    => $comp,
		'bairro'  => $bairro,
		'cidade'  => $cidade,
		'uf'      => $uf,
	) );

	// 7. Redirect pra confirmação
	wp_safe_redirect( add_query_arg( 'orcamento', $order->get_id(), vaxx_orcamento_url() ) );
	exit;
}
add_action( 'admin_post_nopriv_vaxx_submit_orcamento', 'vaxx_handle_orcamento_submit' );
add_action( 'admin_post_vaxx_submit_orcamento',        'vaxx_handle_orcamento_submit' );

/**
 * Envia notificações: comercial + cliente.
 */
function vaxx_notify_orcamento( $order, $is_pj, $d ) {
	$email_resp = vaxx_get_option( 'email_comercial', get_option( 'admin_email' ) );
	$order_id   = $order->get_id();
	$admin_url  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );

	// Resumo de itens
	$items_text = '';
	$items_html = '';
	foreach ( $order->get_items() as $item ) {
		$line = sprintf( '%d × %s', $item->get_quantity(), $item->get_name() );
		$items_text .= "  - {$line}\n";
		$items_html .= '<li>' . esc_html( $line ) . '</li>';
	}

	// wa.me com número do cliente
	$tel_clean = preg_replace( '/\D/', '', $d['tel'] );
	if ( $tel_clean && strlen( $tel_clean ) >= 10 ) {
		if ( strpos( $tel_clean, '55' ) !== 0 ) $tel_clean = '55' . $tel_clean;
		$wa_msg  = "Olá " . explode( ' ', $d['nome'] )[0] . "! Sou da VAXX e recebi seu pedido de orçamento #{$order_id}. Vou te ajudar a fechar essa proposta. Como posso colaborar?";
		$wa_link = 'https://wa.me/' . $tel_clean . '?text=' . rawurlencode( $wa_msg );
	} else {
		$wa_link = '';
	}

	$total = wp_strip_all_tags( wc_price( $order->get_total() ) );

	// ─── E-mail pro comercial ─────────────────────────────
	$subject_resp = "[VAXX] Novo orçamento #{$order_id} — {$d['nome']}";
	$body_resp  = "Novo pedido de orçamento recebido pelo site.\n\n";
	$body_resp .= "PEDIDO: #{$order_id}\n";
	$body_resp .= "TIPO: " . ( $is_pj ? 'Pessoa Jurídica' : 'Pessoa Física' ) . "\n";
	$body_resp .= "NOME: {$d['nome']}\n";
	if ( $is_pj ) {
		$body_resp .= "RAZÃO SOCIAL: {$d['razao']}\n";
		$body_resp .= "CNPJ: {$d['cnpj']}\n";
	} else {
		$body_resp .= "CPF: {$d['cpf']}\n";
	}
	$body_resp .= "E-MAIL: {$d['email']}\n";
	$body_resp .= "TELEFONE: {$d['tel']}\n";
	$body_resp .= "ENDEREÇO: {$d['rua']}, {$d['numero']}";
	if ( $d['comp'] ) $body_resp .= " ({$d['comp']})";
	$body_resp .= " — {$d['bairro']} — {$d['cidade']}/{$d['uf']} — CEP {$d['cep']}\n\n";
	$body_resp .= "ITENS:\n{$items_text}\n";
	$body_resp .= "TOTAL ESTIMADO: {$total}\n\n";
	if ( $wa_link ) {
		$body_resp .= "▶ RESPONDER NO WHATSAPP:\n{$wa_link}\n\n";
	}
	$body_resp .= "▶ VER NO ADMIN:\n{$admin_url}\n";

	wp_mail( $email_resp, $subject_resp, $body_resp );

	// ─── E-mail pro cliente ───────────────────────────────
	$subject_cli = "[VAXX] Recebemos seu orçamento #{$order_id}";
	$body_cli  = "Olá {$d['nome']},\n\n";
	$body_cli .= "Recebemos seu pedido de orçamento #{$order_id}. Nosso time entra em contato em até 1 dia útil com a proposta completa.\n\n";
	$body_cli .= "ITENS:\n{$items_text}\n";
	$body_cli .= "Subtotal estimado: {$total}\n";
	$body_cli .= "(Valor final pode variar conforme frete, montagem e condições negociadas.)\n\n";
	$body_cli .= "Você pode acompanhar este pedido em:\n" . wc_get_account_endpoint_url( 'orders' ) . "\n\n";
	$body_cli .= "VAXX · " . vaxx_get_option( 'razao_social', 'Grupo Delva' ) . " · " . vaxx_get_option( 'endereco_cidade', 'Jaraguá do Sul / SC' );

	wp_mail( $d['email'], $subject_cli, $body_cli );

	/**
	 * Hook pra integrações externas (Z-API, Twilio, etc.) — recebe o order e
	 * o link wa.me já formatado. Plugins podem disparar a mensagem direto.
	 */
	do_action( 'vaxx_orcamento_enviado', $order, $wa_link, $d );
}

/**
 * Label canônico do status na visualização do pedido.
 */
add_filter( 'woocommerce_get_order_status_name', function( $name, $order = null ) {
	if ( $name === 'wc-orcamento' || $name === 'orcamento' ) return 'Orçamento';
	return $name;
}, 10, 2 );
