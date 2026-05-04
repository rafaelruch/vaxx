<?php
/**
 * VAXX · Single Product (PDP)
 *
 * Baseado no preview produto-supino-reto-articulado.html.
 * Renderiza hero split (galeria + info), regua de regulagem (se aplicavel),
 * especificacoes, depoimentos e modal de aluguel.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

global $product;

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer( 'shop' );
	return;
}

while ( have_posts() ) :
	the_post();
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) continue;

	// Taxonomias
	$lines_raw = wp_get_post_terms( $product->get_id(), 'product_line' );
	$line_name = ! empty( $lines_raw ) ? $lines_raw[0]->name : 'VAXX';
	$line_url  = ! empty( $lines_raw ) ? get_term_link( $lines_raw[0] ) : '';

	$muscles_raw = wp_get_post_terms( $product->get_id(), 'muscle_group' );
	$primary_muscle = ! empty( $muscles_raw ) ? $muscles_raw[0] : null;

	// ACF / meta
	$has_reg = (bool) get_post_meta( $product->get_id(), 'vaxx_regulagem_real', true );
	$reg_val = get_post_meta( $product->get_id(), 'vaxx_regulagem', true ) ?: '1,55 – 1,95 m';

	// Galeria
	$main_img_id = get_post_thumbnail_id( $product->get_id() );
	$gallery_ids = $product->get_gallery_image_ids();
	if ( $main_img_id && ! in_array( $main_img_id, $gallery_ids, true ) ) {
		array_unshift( $gallery_ids, $main_img_id );
	}
	if ( empty( $gallery_ids ) && $main_img_id ) {
		$gallery_ids = array( $main_img_id );
	}

	// Preco
	$price       = $product->get_price();
	$price_html  = $product->get_price_html();
	$installment = $price > 0 ? ( $price / 10 ) : 0;
?>

<main class="page-pdp">

	<!-- ───── BREADCRUMB ───── -->
	<nav class="bc" aria-label="Breadcrumb">
		<div class="bc__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
			<span class="sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Produtos</a>
			<?php if ( $line_url ) : ?>
				<span class="sep" aria-hidden="true">›</span>
				<a href="<?php echo esc_url( $line_url ); ?>"><?php echo esc_html( $line_name ); ?></a>
			<?php endif; ?>
			<span class="sep" aria-hidden="true">›</span>
			<span class="is-current" aria-current="page"><?php echo esc_html( $product->get_name() ); ?></span>
		</div>
	</nav>

	<!-- ───── HERO split 50/50 ───── -->
	<section class="pdp-hero" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
		<div class="pdp-hero__inner">

			<!-- Galeria -->
			<div class="gallery" id="gallery">
				<div class="gallery__main" id="galleryMain">
					<span class="gallery__line-badge"><?php echo esc_html( $line_name ); ?></span>
					<?php
					$main_src = $main_img_id
						? wp_get_attachment_image_url( $main_img_id, 'vaxx-prod-gallery' )
						: ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'vaxx-prod-gallery' ) : '' );
					?>
					<img id="galleryMainImg" src="<?php echo esc_url( $main_src ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
					<?php if ( count( $gallery_ids ) > 1 ) : ?>
						<button type="button" class="gallery__nav gallery__nav--prev" id="galleryPrev" aria-label="Foto anterior">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button type="button" class="gallery__nav gallery__nav--next" id="galleryNext" aria-label="Próxima foto">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
						</button>
						<span class="gallery__counter" id="galleryCounter">1 / <?php echo count( $gallery_ids ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( count( $gallery_ids ) > 1 ) : ?>
					<div class="gallery__thumbs" id="galleryThumbs" role="tablist">
						<?php foreach ( $gallery_ids as $i => $gid ) :
							$thumb = wp_get_attachment_image_url( $gid, 'vaxx-prod-thumb' );
							$large = wp_get_attachment_image_url( $gid, 'vaxx-prod-gallery' );
							if ( ! $thumb || ! $large ) continue;
						?>
							<button type="button" class="gallery__thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>" data-src="<?php echo esc_url( $large ); ?>">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="Foto <?php echo $i + 1; ?>">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Info lateral -->
			<div class="pdp-info">
				<span class="pdp-info__eyebrow">
					<?php echo esc_html( strtoupper( $line_name ) ); ?>
					<?php if ( $primary_muscle ) : ?>
						· <?php echo esc_html( strtoupper( $primary_muscle->name ) ); ?>
					<?php endif; ?>
				</span>
				<h1 class="pdp-info__title"><?php echo esc_html( $product->get_name() ); ?></h1>

				<?php if ( ! empty( $muscles_raw ) ) : ?>
					<div class="pdp-info__tags">
						<?php foreach ( $muscles_raw as $m ) : ?>
							<span class="pdp-info__tag"><?php echo esc_html( $m->name ); ?></span>
						<?php endforeach; ?>
						<span class="pdp-info__tag">Profissional</span>
					</div>
				<?php endif; ?>

				<?php if ( $has_reg ) : ?>
					<span class="pdp-badge">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<line x1="4" y1="12" x2="20" y2="12"/><line x1="6" y1="8" x2="6" y2="16"/><line x1="10" y1="6" x2="10" y2="18"/><line x1="14" y1="8" x2="14" y2="16"/><line x1="18" y1="10" x2="18" y2="14"/>
						</svg>
						Regulagem real <?php echo esc_html( $reg_val ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $price > 0 ) : ?>
					<div class="pdp-price">
						<span class="pdp-price__value"><small>R$</small><?php echo esc_html( number_format_i18n( $price, 0 ) ); ?></span>
						<span class="pdp-price__installments">À vista no Pix · ou <strong>10× de R$ <?php echo esc_html( number_format_i18n( $installment, 0 ) ); ?></strong> sem juros</span>
					</div>
				<?php endif; ?>

				<div class="pdp-actions">
					<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
						<form class="pdp-actions__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post">
							<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>">
							<input type="hidden" name="quantity" value="1">
							<button type="submit" class="pdp-actions__primary" id="addToCartBtn">
								Adicionar ao carrinho
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
									<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
									<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
								</svg>
							</button>
						</form>
					<?php endif; ?>
					<button type="button" class="pdp-actions__ghost pdp-actions__rent" id="openRentBtn" aria-haspopup="dialog" aria-controls="rentModal">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="3" y="4" width="18" height="16" rx="2"/>
							<line x1="16" y1="2" x2="16" y2="6"/>
							<line x1="8" y1="2" x2="8" y2="6"/>
							<line x1="3" y1="10" x2="21" y2="10"/>
						</svg>
						Solicitar aluguel
					</button>
				</div>

				<dl class="pdp-meta">
					<div class="pdp-meta__row">
						<div class="pdp-meta__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
							</svg>
						</div>
						<div class="pdp-meta__text">
							<strong>Frete grátis SC, PR, RS</strong>
							<small>Orçamento direto para outros estados</small>
						</div>
					</div>
					<div class="pdp-meta__row">
						<div class="pdp-meta__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>
							</svg>
						</div>
						<div class="pdp-meta__text">
							<strong>10 anos de garantia</strong>
							<small>Estrutura · 2 anos estofado</small>
						</div>
					</div>
					<div class="pdp-meta__row">
						<div class="pdp-meta__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
							</svg>
						</div>
						<div class="pdp-meta__text">
							<strong>Entrega em 30 dias</strong>
							<small>Produção sob encomenda</small>
						</div>
					</div>
					<div class="pdp-meta__row">
						<div class="pdp-meta__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
							</svg>
						</div>
						<div class="pdp-meta__text">
							<strong>Instalação inclusa</strong>
							<small>Equipe própria VAXX</small>
						</div>
					</div>
				</dl>
			</div>

		</div>
	</section>

	<?php if ( $has_reg ) : ?>
	<!-- ───── RÉGUA DE REGULAGEM · só quando tem regulagem real ───── -->
	<section class="ruler" aria-label="Régua de regulagem <?php echo esc_attr( $reg_val ); ?>">
		<div class="ruler__bg"></div>
		<div class="ruler__inner">
			<div class="ruler__header">
				<span class="ruler__eyebrow">AJUSTE PRA VOCÊ</span>
				<h2 class="ruler__title">Regulagem real <?php echo esc_html( $reg_val ); ?></h2>
				<p class="ruler__sub">Assento, encosto e apoios seguem a altura — sem adaptação improvisada.</p>
			</div>
			<div class="ruler__slider" id="rulerSlider">
				<div class="ruler__track" style="--fill: 50%;" id="rulerFill"></div>
				<div class="ruler__stops" aria-hidden="true">
					<span class="ruler__stop is-active" data-alt="1,55"><span class="ruler__stop-label">1,55 m</span></span>
					<span class="ruler__stop" data-alt="1,65"><span class="ruler__stop-label">1,65 m</span></span>
					<span class="ruler__stop" data-alt="1,75"><span class="ruler__stop-label">1,75 m</span></span>
					<span class="ruler__stop" data-alt="1,85"><span class="ruler__stop-label">1,85 m</span></span>
					<span class="ruler__stop" data-alt="1,95"><span class="ruler__stop-label">1,95 m</span></span>
				</div>
				<button type="button" class="ruler__handle" id="rulerHandle" aria-label="Ajustar altura" role="slider" aria-valuemin="1.55" aria-valuemax="1.95" aria-valuenow="1.75" style="left: 50%;"></button>
			</div>
			<div class="ruler__value">
				<span class="ruler__value-num" id="rulerValue">1,75 m</span>
				<span class="ruler__value-label">Altura selecionada</span>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- ───── DESCRIÇÃO / ESPECIFICAÇÕES ───── -->
	<section class="pdp-specs" aria-label="Especificações">
		<div class="pdp-specs__inner">
			<div class="pdp-specs__header">
				<span class="pdp-specs__eyebrow">ESPECIFICAÇÕES</span>
				<h2 class="pdp-specs__title">Engenharia de fábrica.</h2>
			</div>
			<div class="pdp-specs__content">
				<?php echo apply_filters( 'the_content', $product->get_description() ); ?>
			</div>
		</div>
	</section>

</main>

<!-- ───── MODAL DE ALUGUEL ───── -->
<div class="rent-modal-overlay" id="rentModalOverlay" aria-hidden="true"></div>
<aside class="rent-modal" id="rentModal" role="dialog" aria-modal="true" aria-labelledby="rentModalTitle" aria-hidden="true" tabindex="-1">
	<div class="rent-modal__header">
		<div>
			<span class="rent-modal__eyebrow">SOLICITAR ALUGUEL</span>
			<h3 class="rent-modal__title" id="rentModalTitle"><?php echo esc_html( $product->get_name() ); ?></h3>
		</div>
		<button type="button" class="rent-modal__close" id="closeRentBtn" aria-label="Fechar">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>
	<form class="rent-modal__form" id="rentForm" method="post">
		<p class="rent-modal__lede">Nosso comercial entra em contato em até 24h úteis com condições personalizadas de aluguel.</p>
		<label class="rent-field">
			<span class="rent-field__label">Nome completo *</span>
			<input type="text" name="rent_nome" required autocomplete="name">
		</label>
		<label class="rent-field">
			<span class="rent-field__label">Telefone / WhatsApp *</span>
			<input type="tel" name="rent_telefone" required autocomplete="tel">
		</label>
		<label class="rent-field">
			<span class="rent-field__label">E-mail *</span>
			<input type="email" name="rent_email" required autocomplete="email">
		</label>
		<label class="rent-field">
			<span class="rent-field__label">Tipo de local</span>
			<select name="rent_tipo">
				<option value="">Selecione…</option>
				<option value="academia">Academia</option>
				<option value="condominio">Condomínio</option>
				<option value="empresa">Empresa / Escritório</option>
				<option value="residencial">Residencial</option>
				<option value="outro">Outro</option>
			</select>
		</label>
		<label class="rent-field">
			<span class="rent-field__label">Mensagem (opcional)</span>
			<textarea name="rent_mensagem" rows="3" placeholder="Quantidade, prazo, detalhes do espaço…"></textarea>
		</label>
		<input type="hidden" name="rent_produto_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<input type="hidden" name="rent_produto_nome" value="<?php echo esc_attr( $product->get_name() ); ?>">
		<?php wp_nonce_field( 'vaxx_rent_lead', 'rent_nonce' ); ?>
		<button type="submit" class="rent-modal__submit">
			Enviar solicitação
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
		</button>
		<p class="rent-modal__disclaimer">Ao enviar, você concorda com nossa <a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>">Política de Privacidade</a>.</p>
	</form>
</aside>

<?php
endwhile;

get_footer( 'shop' );
