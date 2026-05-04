<?php
/**
 * VAXX · My Account render helpers
 *
 * Renderers dinâmicos para as views de Pedidos e Aluguéis.
 * Consumidos pelo override woocommerce/myaccount/my-account.php.
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render da view "Pedidos" com dados reais do WooCommerce.
 */
function vaxx_mc_render_pedidos( $user_id ) {
	$orders = wc_get_orders( array(
		'customer_id' => (int) $user_id,
		'limit'       => -1,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'status'      => array_keys( wc_get_order_statuses() ),
	) );

	// Buckets por status pra alimentar os chips de filtro
	$buckets = array(
		'todos'       => 0,
		'em_andamento'=> 0,
		'entregues'   => 0,
		'cancelados'  => 0,
	);
	$in_progress_statuses = array( 'pending', 'processing', 'on-hold' );
	$delivered_statuses   = array( 'completed' );
	$cancelled_statuses   = array( 'cancelled', 'refunded', 'failed' );

	foreach ( $orders as $o ) {
		$st = $o->get_status();
		$buckets['todos']++;
		if ( in_array( $st, $in_progress_statuses, true ) ) $buckets['em_andamento']++;
		elseif ( in_array( $st, $delivered_statuses, true ) ) $buckets['entregues']++;
		elseif ( in_array( $st, $cancelled_statuses, true ) ) $buckets['cancelados']++;
	}
	?>
	<section class="mc-view is-active" data-view="pedidos" aria-label="Meus pedidos">
		<div class="mc-view__header">
			<div>
				<h2 class="mc-view__title"><?php esc_html_e( 'Meus pedidos', 'vaxx' ); ?></h2>
				<p class="mc-view__desc"><?php esc_html_e( 'Acompanhe todos os seus pedidos em um só lugar.', 'vaxx' ); ?></p>
			</div>
		</div>

		<div class="mc-filters" role="group" aria-label="<?php esc_attr_e( 'Filtrar pedidos', 'vaxx' ); ?>">
			<button type="button" class="mc-filter-chip is-active" data-filter="todos">
				<?php esc_html_e( 'Todos', 'vaxx' ); ?> <span class="mc-filter-chip__count"><?php echo (int) $buckets['todos']; ?></span>
			</button>
			<button type="button" class="mc-filter-chip" data-filter="em_andamento">
				<?php esc_html_e( 'Em andamento', 'vaxx' ); ?> <span class="mc-filter-chip__count"><?php echo (int) $buckets['em_andamento']; ?></span>
			</button>
			<button type="button" class="mc-filter-chip" data-filter="entregues">
				<?php esc_html_e( 'Entregues', 'vaxx' ); ?> <span class="mc-filter-chip__count"><?php echo (int) $buckets['entregues']; ?></span>
			</button>
			<button type="button" class="mc-filter-chip" data-filter="cancelados">
				<?php esc_html_e( 'Cancelados', 'vaxx' ); ?> <span class="mc-filter-chip__count"><?php echo (int) $buckets['cancelados']; ?></span>
			</button>
		</div>

		<?php if ( empty( $orders ) ) : ?>

			<div class="mc-empty">
				<div class="mc-empty__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
						<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
						<line x1="12" y1="22.08" x2="12" y2="12"/>
					</svg>
				</div>
				<h3><?php esc_html_e( 'Você ainda não tem pedidos', 'vaxx' ); ?></h3>
				<p><?php esc_html_e( 'Explore o catálogo e comece com um item para sua academia ou casa.', 'vaxx' ); ?></p>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ?: home_url( '/shop/' ) ); ?>" class="mc-empty__cta"><?php esc_html_e( 'Ver produtos', 'vaxx' ); ?></a>
			</div>

		<?php else : ?>

			<div class="mc-orders">
				<?php foreach ( $orders as $i => $order ) :
					$oid       = $order->get_id();
					$number    = $order->get_order_number();
					$date      = wc_format_datetime( $order->get_date_created(), 'd M Y' );
					$status    = $order->get_status();
					$status_label = wc_get_order_status_name( $status );
					$total     = $order->get_formatted_order_total();
					$items     = $order->get_items();
					$item_count = count( $items );

					$is_in_progress = in_array( $status, $in_progress_statuses, true );
					$is_delivered   = in_array( $status, $delivered_statuses, true );
					$is_cancelled   = in_array( $status, $cancelled_statuses, true );

					$status_class = $is_in_progress ? 'mc-order__status--production'
						: ( $is_delivered ? 'mc-order__status--delivered'
							: ( $is_cancelled ? 'mc-order__status--cancelled' : '' ) );

					$expanded = $i === 0 && $is_in_progress ? 'is-expanded' : '';
					$aria_expanded = $expanded ? 'true' : 'false';

					// Título resumo: primeiros 2 nomes de produto ou nome único + " · N itens"
					$names = array();
					foreach ( $items as $item ) $names[] = $item->get_name();
					$title_text = $item_count > 2 ? implode( ' + ', array_slice( $names, 0, 2 ) ) . ' · ' . $item_count . ' itens'
						: implode( ' + ', $names );
				?>
					<article class="mc-order <?php echo esc_attr( $expanded ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
						<button type="button" class="mc-order__head" aria-expanded="<?php echo esc_attr( $aria_expanded ); ?>">
							<div class="mc-order__meta">
								<span class="mc-order__id">#VXX-<?php echo esc_html( $number ); ?></span>
								<span class="mc-order__date"><?php echo esc_html( $date ); ?></span>
							</div>
							<span class="mc-order__title"><?php echo esc_html( $title_text ); ?></span>
							<span class="mc-order__status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
							<span class="mc-order__total"><?php echo wp_kses_post( $total ); ?></span>
							<span class="mc-order__toggle" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
							</span>
						</button>
						<div class="mc-order__body">
							<div class="mc-order__body-inner">
								<div class="mc-order__items">
									<?php foreach ( $items as $item ) :
										$product = $item->get_product();
										$thumb = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
										$line_total = wc_price( $order->get_line_total( $item, true ) );
									?>
										<div class="mc-order__item">
											<?php if ( $thumb ) : ?>
												<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $item->get_name() ); ?>">
											<?php endif; ?>
											<div>
												<strong><?php echo esc_html( $item->get_name() ); ?></strong>
												<span><?php echo (int) $item->get_quantity(); ?>× <?php echo wp_kses_post( wc_price( $order->get_item_subtotal( $item, true ) ) ); ?></span>
											</div>
											<span class="mc-order__line-total"><?php echo wp_kses_post( $line_total ); ?></span>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="mc-order__actions">
									<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="mc-action-btn mc-action-btn--primary">
										<?php esc_html_e( 'Ver detalhes', 'vaxx' ); ?>
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
									</a>
									<?php $invoice = $order->get_checkout_order_received_url(); ?>
									<a href="<?php echo esc_url( $invoice ); ?>" class="mc-action-btn">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
										<?php esc_html_e( 'Comprovante', 'vaxx' ); ?>
									</a>
									<a href="<?php echo esc_url( vaxx_wa_link( 'Oi! Preciso de ajuda com o pedido #VXX-' . $number ) ); ?>" target="_blank" rel="noopener" class="mc-action-btn">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
										<?php esc_html_e( 'Falar com comercial', 'vaxx' ); ?>
									</a>
								</div>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

		<?php endif; ?>
	</section>
	<?php
}

/**
 * Render da view "Aluguéis" buscando CPT rent_lead pelo e-mail do usuário.
 */
function vaxx_mc_render_alugueis( $user_id ) {
	$user  = get_userdata( $user_id );
	$email = $user ? $user->user_email : '';

	$leads = array();
	if ( $email ) {
		$leads = get_posts( array(
			'post_type'      => 'rent_lead',
			'post_status'    => array( 'publish', 'pending', 'private' ),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => 'email', 'value' => $email, 'compare' => '=' ),
			),
			'orderby' => 'date',
			'order'   => 'DESC',
		) );
	}
	?>
	<section class="mc-view" data-view="alugueis" aria-label="Meus aluguéis">
		<div class="mc-view__header">
			<div>
				<h2 class="mc-view__title"><?php esc_html_e( 'Solicitações de aluguel', 'vaxx' ); ?></h2>
				<p class="mc-view__desc"><?php esc_html_e( 'Leads enviados via PDP · o time comercial responde em até 1 dia útil.', 'vaxx' ); ?></p>
			</div>
		</div>

		<?php if ( empty( $leads ) ) : ?>

			<div class="mc-empty">
				<div class="mc-empty__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="3" y="4" width="18" height="16" rx="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
				</div>
				<h3><?php esc_html_e( 'Nenhuma solicitação de aluguel', 'vaxx' ); ?></h3>
				<p><?php esc_html_e( 'Solicite aluguel na página de um produto para começar. O comercial entra em contato em 1 dia útil.', 'vaxx' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="mc-empty__cta"><?php esc_html_e( 'Ver produtos', 'vaxx' ); ?></a>
			</div>

		<?php else : ?>

			<div class="mc-orders">
				<?php foreach ( $leads as $lead ) :
					$produto   = get_post_meta( $lead->ID, 'produto', true );
					$status    = get_post_meta( $lead->ID, 'status_lead', true ) ?: 'Aguardando contato';
					$sent_date = date_i18n( 'd/m/Y', strtotime( $lead->post_date ) );
					$status_key = sanitize_title( $status );
				?>
					<div class="mc-rental">
						<div class="mc-rental__thumb">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
						</div>
						<div class="mc-rental__body">
							<span class="mc-rental__product"><?php echo esc_html( $produto ?: $lead->post_title ); ?></span>
							<span class="mc-rental__meta"><?php printf( esc_html__( 'Lead enviado em %s', 'vaxx' ), '<strong>' . esc_html( $sent_date ) . '</strong>' ); ?></span>
						</div>
						<div class="mc-rental__status">
							<span class="mc-rental__status-badge mc-rental__status--<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status ); ?></span>
							<a href="<?php echo esc_url( vaxx_wa_link( 'Oi! Sobre meu lead de aluguel do ' . $produto ) ); ?>" target="_blank" rel="noopener" class="mc-action-btn">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
								<?php esc_html_e( 'Continuar', 'vaxx' ); ?>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

		<?php endif; ?>
	</section>
	<?php
}
