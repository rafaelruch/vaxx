<?php
/**
 * VAXX · Shortcodes do shell (header, footer, mini-cart)
 *
 * Renderizam o HTML real aprovado do preview, com dados dinâmicos
 * vindos do Customizer (telefone, e-mail, endereço, etc.).
 *
 * Cliente edita os textos em Aparência → Personalizar → VAXX · Configurações
 * e o menu principal em Aparência → Menus (location: primary).
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper: limpa quebras de linha e indentação do output de shortcode
 * pra evitar que wpautop insira <br/> e <p> indesejados.
 */
function vaxx_clean_shortcode_output( $html ) {
	// Remove HTML comments (disparam wpautop em alguns casos)
	$html = preg_replace( '/<!--(.|\s)*?-->/', '', $html );
	// Remove whitespace entre tags
	$html = preg_replace( '/>\s+</', '><', $html );
	// Remove newlines/tabs
	$html = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
	return trim( $html );
}

/**
 * Registra localizações de menu do tema.
 */
function vaxx_register_menus() {
	register_nav_menus( array(
		'primary'         => __( 'Menu principal (header desktop)', 'vaxx' ),
		'footer-produtos' => __( 'Rodapé · Produtos', 'vaxx' ),
		'footer-empresa'  => __( 'Rodapé · Empresa', 'vaxx' ),
		'footer-suporte'  => __( 'Rodapé · Atendimento', 'vaxx' ),
	) );
}
add_action( 'after_setup_theme', 'vaxx_register_menus' );

/**
 * Retorna HTML do logo VAXX (SVG inline do tema).
 */
function vaxx_logo_html() {
	$custom = get_theme_mod( 'custom_logo' );
	if ( $custom ) {
		$logo_img = wp_get_attachment_image_src( $custom, 'full' );
		if ( $logo_img ) {
			return sprintf(
				'<img src="%s" alt="%s">',
				esc_url( $logo_img[0] ),
				esc_attr( get_bloginfo( 'name' ) )
			);
		}
	}
	// Fallback: SVG default do tema
	return sprintf(
		'<img src="%s" alt="VAXX">',
		esc_url( VAXX_THEME_URI . '/assets/svg/logo-vaxx.svg' )
	);
}

/**
 * Shortcode [vaxx_header] — header completo aprovado do preview
 * Uso: template-part header.html inclui este shortcode
 */
function vaxx_shortcode_header() {
	ob_start();
	$topbar_ativo = get_theme_mod( 'vaxx_topbar_ativo', 'on' );
	$wa_link      = vaxx_wa_link( 'Oi! Vim pelo site da VAXX' );
	$cta_curto    = vaxx_get_option( 'cta_pill_curto', 'Fale conosco' );
	$cta_longo    = vaxx_get_option( 'cta_pill_longo', 'Fale com quem fabricou' );
	$topbar_1     = vaxx_get_option( 'topbar_text_1', 'FEITO POR QUEM TREINA' );
	$topbar_2     = vaxx_get_option( 'topbar_text_2', 'PRA QUEM TREINA' );
	?>
	<header class="header" id="header">

		<?php if ( $topbar_ativo === 'on' ) : ?>
		<div class="topbar">
			<div class="topbar__marquee">
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<span><?php echo esc_html( $topbar_1 ); ?></span><span class="sep">·</span>
				<span><?php echo esc_html( $topbar_2 ); ?></span><span class="sep">·</span>
				<?php endfor; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="header__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="header__logo" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — ir para home">
				<?php echo vaxx_logo_html(); ?>
			</a>

			<nav class="header__nav" role="navigation" aria-label="Principal">
				<?php
				// Linhas para o mega menu — ordem canonica (Articulados primeiro)
				$linhas_ordem = array( 'articulados', 'bateria-de-pesos', 'linha-cardio', 'acessorios' );
				$linhas = array();
				foreach ( $linhas_ordem as $slug_linha ) {
					$t = get_term_by( 'slug', $slug_linha, 'product_line' );
					if ( $t ) { $linhas[] = $t; }
				}
				// Grupos para chips — ordem canonica
				$grupos_ordem = array( 'peito', 'costas', 'ombros', 'pernas', 'gluteos', 'bracos', 'core' );
				$grupos = array();
				foreach ( $grupos_ordem as $slug_g ) {
					$t = get_term_by( 'slug', $slug_g, 'muscle_group' );
					if ( $t ) { $grupos[] = $t; }
				}
				// Imagem por linha — pega thumb do primeiro produto cadastrado naquela linha.
				// Cacheado por 1h pra evitar query repetida em cada render do header.
				$vaxx_get_linha_img = function( $term ) {
					$cache_key = 'vaxx_linha_thumb_' . $term->term_id;
					$cached    = get_transient( $cache_key );
					if ( $cached !== false ) return $cached;
					$q = new WP_Query( array(
						'post_type'      => 'product',
						'posts_per_page' => 1,
						'post_status'    => 'publish',
						'tax_query'      => array( array(
							'taxonomy' => 'product_line',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						) ),
						'meta_query'     => array( array(
							'key'   => '_thumbnail_id',
							'compare' => 'EXISTS',
						) ),
						'fields'         => 'ids',
					) );
					$img_url = '';
					if ( $q->have_posts() ) {
						$img_url = get_the_post_thumbnail_url( $q->posts[0], 'vaxx-prod-card' );
					}
					if ( ! $img_url && function_exists( 'wc_placeholder_img_src' ) ) {
						$img_url = wc_placeholder_img_src( 'vaxx-prod-card' );
					}
					set_transient( $cache_key, $img_url, HOUR_IN_SECONDS );
					return $img_url;
				};
				?>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/quem-somos/' ) ); ?>">Quem Somos</a></li>
					<li class="has-megamenu">
						<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>" class="has-dropdown">Produtos<span class="chevron">▼</span></a>
						<div class="megamenu" role="menu">
							<div class="megamenu__grid">
								<div class="megamenu__col">
									<h4>Linhas</h4>
									<div class="megamenu__cards">
										<?php foreach ( $linhas as $linha ) :
											$count = $linha->count;
											$img   = $vaxx_get_linha_img( $linha );
										?>
											<a href="<?php echo esc_url( get_term_link( $linha ) ); ?>" class="megamenu__card">
												<?php if ( $img ) : ?>
												<div class="megamenu__card-img">
													<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $linha->name ); ?>" loading="lazy">
												</div>
												<?php endif; ?>
												<h5><?php echo esc_html( $linha->name ); ?></h5>
												<span><?php echo intval( $count ); ?>+ equipamentos</span>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
								<div class="megamenu__col">
									<h4>Grupo Muscular</h4>
									<div class="megamenu__chips">
										<?php foreach ( $grupos as $grupo ) : ?>
											<a href="<?php echo esc_url( get_term_link( $grupo ) ); ?>" class="megamenu__chip"><?php echo esc_html( $grupo->name ); ?></a>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
							<?php if ( $wa_link ) : ?>
							<div class="megamenu__footer">
								<a href="<?php echo esc_url( $wa_link ); ?>" class="megamenu__footer-cta" target="_blank" rel="noopener">Fale com quem fabricou</a>
							</div>
							<?php endif; ?>
						</div>
					</li>
					<li><a href="<?php echo esc_url( home_url( '/para-academias/' ) ); ?>">Para Academias</a></li>
					<li><a href="<?php echo esc_url( home_url( '/revendedores/' ) ); ?>">Revendedores</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contato/' ) ); ?>">Contato</a></li>
				</ul>
				<?php
				?>
			</nav>

			<div class="header__actions">
				<button type="button" class="header__icon-btn" id="searchOpenBtn" aria-label="Buscar produtos" aria-expanded="false" aria-controls="searchOverlay">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"/>
						<line x1="21" y1="21" x2="16.65" y2="16.65"/>
					</svg>
				</button>

				<?php
				$account_url = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/minha-conta/' );
				$is_logged   = is_user_logged_in();
				$account_label = $is_logged ? esc_attr__( 'Minha conta', 'vaxx' ) : esc_attr__( 'Entrar ou criar conta', 'vaxx' );
				?>
				<a href="<?php echo esc_url( $account_url ); ?>" class="header__icon-btn header__account" aria-label="<?php echo $account_label; ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
						<circle cx="12" cy="7" r="4"/>
					</svg>
				</a>

				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<?php $count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0; ?>
				<button type="button" class="header__icon-btn" id="cartOpenBtn" aria-label="Abrir carrinho" aria-expanded="false" aria-controls="cartDrawer">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="9" cy="21" r="1"/>
						<circle cx="20" cy="21" r="1"/>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
					</svg>
					<span class="header__cart-badge" id="cartBadge"<?php echo $count === 0 ? ' style="display:none"' : ''; ?>><?php echo (int) $count; ?></span>
				</button>
				<?php endif; ?>

				<?php if ( $wa_link ) : ?>
				<a href="<?php echo esc_url( $wa_link ); ?>" class="header__cta" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
					<span class="pulse"></span>
					<span class="header__cta-short"><?php echo esc_html( $cta_curto ); ?></span>
					<span class="header__cta-full"><?php echo esc_html( $cta_longo ); ?></span>
				</a>
				<?php endif; ?>

				<button class="header__menu-toggle" aria-label="Abrir menu mobile">
					<span></span><span></span><span></span>
				</button>
			</div>
		</div>

		<div class="search-overlay" id="searchOverlay" role="dialog" aria-modal="true" aria-label="Buscar produtos" hidden>
			<button type="button" class="search-overlay__close" aria-label="Fechar busca">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
				</svg>
			</button>
			<form role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="search-overlay__form">
				<label for="searchOverlayInput" class="screen-reader-text"><?php esc_html_e( 'Buscar produtos', 'vaxx' ); ?></label>
				<input type="search" id="searchOverlayInput" name="s" placeholder="<?php esc_attr_e( 'Buscar equipamento…', 'vaxx' ); ?>" autocomplete="off">
				<input type="hidden" name="post_type" value="product">
				<button type="submit" class="search-overlay__submit" aria-label="Buscar">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
					</svg>
				</button>
			</form>
			<p class="search-overlay__hint">
				<?php esc_html_e( 'Dica: experimente', 'vaxx' ); ?>
				<em>supino</em>, <em>leg press</em>, <em>articulados</em>.
			</p>
		</div>
	</header>
	<?php
	return vaxx_clean_shortcode_output( ob_get_clean() );
}
add_shortcode( 'vaxx_header', 'vaxx_shortcode_header' );

/**
 * Shortcode [vaxx_footer] — footer completo
 */
function vaxx_shortcode_footer() {
	ob_start();
	$wa_link   = vaxx_wa_link();
	$razao     = vaxx_get_option( 'razao_social', 'Grupo Delva — Indústria Metálica Ltda.' );
	$fantasia  = vaxx_get_option( 'nome_fantasia', 'VAXX' );
	$cnpj      = vaxx_get_option( 'cnpj', '' );
	$ano_desde = vaxx_get_option( 'desde_ano', '2008' );
	$rua       = vaxx_get_option( 'endereco_rua', '' );
	$bairro    = vaxx_get_option( 'endereco_bairro', '' );
	$cidade    = vaxx_get_option( 'endereco_cidade', 'Jaraguá do Sul / SC' );
	$cep       = vaxx_get_option( 'endereco_cep', '' );
	$tagline   = vaxx_get_option( 'tagline_footer', 'Grupo Delva · Desde ' . $ano_desde );
	$desc      = vaxx_get_option( 'desc_footer', 'Linha completa de equipamentos para academia, fabricada em Jaraguá do Sul por quem treina todo dia. Direto da fábrica, sem intermediário.' );

	$ig = vaxx_get_option( 'social_instagram', '' );
	$yt = vaxx_get_option( 'social_youtube', '' );
	$fb = vaxx_get_option( 'social_facebook', '' );
	$in = vaxx_get_option( 'social_linkedin', '' );

	// Compõe linha de endereço só com os campos preenchidos (evita "—" solto, "·" sem CEP, etc.)
	$endereco_linha1 = trim( $rua . ( $rua && $bairro ? ' — ' : '' ) . $bairro );
	$endereco_linha2 = trim( $cidade . ( $cidade && $cep ? ' · ' : '' ) . $cep );
	$tem_endereco    = $endereco_linha1 || $endereco_linha2;
	$tem_social      = $ig || $yt || $fb || $in;
	$tem_wa          = (bool) $wa_link;
	?>
	<footer class="footer" id="secFooter" role="contentinfo" aria-label="Rodapé">

		<div class="footer__main">
			<!-- Coluna 1: Brand -->
			<div class="footer__col">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer__brand-logo" aria-label="<?php echo esc_attr( $fantasia ); ?> — Início">
					<?php echo vaxx_logo_html(); ?>
				</a>
				<span class="footer__brand-tagline"><?php echo esc_html( $tagline ); ?></span>
				<p class="footer__brand-desc"><?php echo esc_html( $desc ); ?></p>

				<?php if ( $tem_endereco ) : ?>
				<div class="footer__address">
					<span class="footer__address-label">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
						Fábrica
					</span>
					<p class="footer__address-text">
						<strong><?php echo esc_html( $razao ); ?></strong><?php echo $endereco_linha1 || $endereco_linha2 ? '<br>' : ''; ?>
						<?php if ( $endereco_linha1 ) : ?><?php echo esc_html( $endereco_linha1 ); ?><?php echo $endereco_linha2 ? '<br>' : ''; ?><?php endif; ?>
						<?php if ( $endereco_linha2 ) : ?><?php echo esc_html( $endereco_linha2 ); ?><?php endif; ?>
					</p>
				</div>
				<?php endif; ?>

				<?php if ( $tem_social ) : ?>
				<div class="footer__social">
					<?php if ( $ig ) : ?>
					<a href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener" aria-label="Instagram">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $yt ) : ?>
					<a href="<?php echo esc_url( $yt ); ?>" target="_blank" rel="noopener" aria-label="YouTube">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $fb ) : ?>
					<a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $in ) : ?>
					<a href="<?php echo esc_url( $in ); ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
					</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Colunas 2-4: menus WP -->
			<div class="footer__col">
				<h3 class="footer__col-title">Produtos</h3>
				<?php if ( has_nav_menu( 'footer-produtos' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-produtos', 'container' => false, 'menu_class' => 'footer__links', 'fallback_cb' => false ) );
				} else { echo '<p style="font-size:12px;color:rgba(255,255,255,0.4);">Configure em Menus → Rodapé Produtos</p>'; } ?>
			</div>

			<div class="footer__col">
				<h3 class="footer__col-title">Empresa</h3>
				<?php if ( has_nav_menu( 'footer-empresa' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-empresa', 'container' => false, 'menu_class' => 'footer__links', 'fallback_cb' => false ) );
				} else { echo '<p style="font-size:12px;color:rgba(255,255,255,0.4);">Configure em Menus → Rodapé Empresa</p>'; } ?>
			</div>

			<div class="footer__col">
				<h3 class="footer__col-title">Atendimento</h3>
				<?php if ( has_nav_menu( 'footer-suporte' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer-suporte', 'container' => false, 'menu_class' => 'footer__links', 'fallback_cb' => false ) );
				} else { echo '<p style="font-size:12px;color:rgba(255,255,255,0.4);">Configure em Menus → Rodapé Suporte</p>'; } ?>

				<?php if ( $tem_wa ) : ?>
				<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener" class="footer__whatsapp">
					<span class="pulse-dot"></span>
					<div class="footer__whatsapp-text">
						<small>WhatsApp · Online</small>
						<span><?php echo esc_html( vaxx_get_option( 'cta_pill_longo', 'Fale com quem fabricou' ) ); ?></span>
					</div>
				</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Bottom bar -->
		<div class="footer__bottom">
			<div class="footer__copy">
				<span class="footer__copy-line"><strong><?php echo esc_html( $fantasia ); ?></strong> · Marca fitness do <?php echo esc_html( explode( ' — ', $razao )[0] ); ?></span>
				<span class="footer__copy-line"><?php if ( $cnpj ) : ?>CNPJ <?php echo esc_html( $cnpj ); ?> · <?php endif; ?>© <?php echo esc_html( $ano_desde . '–' . date( 'Y' ) ); ?> · Todos os direitos reservados</span>
			</div>
			<div class="footer__legal">
				<a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>">Privacidade</a>
				<span class="footer__legal-sep" aria-hidden="true"></span>
				<a href="<?php echo esc_url( home_url( '/termos-de-uso/' ) ); ?>">Termos</a>
				<span class="footer__legal-sep" aria-hidden="true"></span>
				<a href="<?php echo esc_url( home_url( '/trocas-e-devolucoes/' ) ); ?>">Trocas</a>
			</div>
			<button type="button" class="footer__back-top" id="footerBackTop" aria-label="Voltar ao topo">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
				Voltar ao topo
			</button>
		</div>
	</footer>
	<?php
	return vaxx_clean_shortcode_output( ob_get_clean() );
}
add_shortcode( 'vaxx_footer', 'vaxx_shortcode_footer' );

/**
 * Shortcode [vaxx_mini_cart] — drawer/bottom-sheet integrado com WooCommerce
 */
function vaxx_shortcode_mini_cart() {
	if ( ! class_exists( 'WooCommerce' ) ) return '';
	ob_start();
	$items = is_object( WC()->cart ) ? WC()->cart->get_cart() : array();
	$count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	$subtotal_raw = is_object( WC()->cart ) ? WC()->cart->get_subtotal() : 0;
	?>
	<div class="cart-overlay" id="cartOverlay" aria-hidden="true"></div>

	<aside class="cart-drawer<?php echo $count === 0 ? ' is-empty' : ''; ?>" id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartTitle" aria-hidden="true" tabindex="-1">

		<header class="cart-header">
			<div class="cart-header__title">
				<h3 id="cartTitle">Carrinho</h3>
				<span class="cart-header__count" id="cartHeaderCount"><?php echo (int) $count; ?></span>
			</div>
			<button type="button" class="cart-close" id="cartCloseBtn" aria-label="Fechar carrinho">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
			</button>
		</header>

		<div class="cart-body">
			<div class="cart-body__empty">
				<div class="cart-body__empty-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
						<line x1="3" y1="6" x2="21" y2="6"/>
						<path d="M16 10a4 4 0 0 1-8 0"/>
					</svg>
				</div>
				<h4 class="cart-body__empty-title">Seu carrinho está vazio</h4>
				<p class="cart-body__empty-desc">Encontre equipamentos forjados em ferro para academia profissional ou doméstica.</p>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="cart-body__empty-cta">
					Ver catálogo
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</a>
			</div>

			<div class="cart-body__list" id="cartList">
				<?php foreach ( $items as $cart_key => $item ) :
					$product = $item['data'];
					if ( ! $product ) continue;
					$id    = $product->get_id();
					$price = (int) $product->get_price();
					$qty   = (int) $item['quantity'];
					$name  = $product->get_name();
					$img   = get_the_post_thumbnail_url( $id, 'vaxx-prod-thumb' );
					if ( ! $img ) $img = wc_placeholder_img_src( 'vaxx-prod-thumb' );
				?>
				<article class="cart-item" data-id="<?php echo esc_attr( $id ); ?>" data-price="<?php echo esc_attr( $price ); ?>" data-cart-key="<?php echo esc_attr( $cart_key ); ?>">
					<div class="cart-item__media">
						<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?>">
					</div>
					<div class="cart-item__body">
						<h4 class="cart-item__name"><?php echo esc_html( $name ); ?></h4>
						<div class="cart-item__footer">
							<div class="cart-qty" role="group" aria-label="Quantidade">
								<button type="button" class="cart-qty__btn" data-action="decrease" aria-label="Diminuir">−</button>
								<span class="cart-qty__value" aria-live="polite"><?php echo (int) $qty; ?></span>
								<button type="button" class="cart-qty__btn" data-action="increase" aria-label="Aumentar">+</button>
							</div>
							<span class="cart-item__price">R$ <?php echo esc_html( number_format( $price, 0, ',', '.' ) ); ?></span>
						</div>
					</div>
					<button type="button" class="cart-item__remove" aria-label="Remover <?php echo esc_attr( $name ); ?>" data-cart-key="<?php echo esc_attr( $cart_key ); ?>" data-product-id="<?php echo esc_attr( $id ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="3 6 5 6 21 6"/>
							<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
						</svg>
					</button>
				</article>
				<?php endforeach; ?>
			</div>

			<div class="cart-info">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
				<span>Frete calculado no checkout. <strong>Entrega em todo o Brasil</strong> direto da fábrica em Jaraguá do Sul.</span>
			</div>
		</div>

		<footer class="cart-footer">
			<div class="cart-subtotal">
				<span class="cart-subtotal__label">Subtotal</span>
				<span class="cart-subtotal__value" id="cartSubtotal">R$ <?php echo esc_html( number_format( (int) $subtotal_raw, 0, ',', '.' ) ); ?></span>
			</div>
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="cart-checkout-btn">
				Finalizar compra
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</a>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-view-full">Ver carrinho completo</a>
		</footer>

	</aside>
	<?php
	return vaxx_clean_shortcode_output( ob_get_clean() );
}
add_shortcode( 'vaxx_mini_cart', 'vaxx_shortcode_mini_cart' );

/**
 * Render do shell via hooks (bypass de wpautop/block pipeline).
 * Header no wp_body_open, footer no wp_footer.
 * O template-part block apenas marca a posição conceitual.
 */
function vaxx_render_header_hook() {
	// Só renderiza uma vez por página (caso seja chamado múltiplas vezes)
	static $rendered = false;
	if ( $rendered ) return;
	$rendered = true;
	echo vaxx_shortcode_header();
}
add_action( 'wp_body_open', 'vaxx_render_header_hook', 5 );

function vaxx_render_footer_hook() {
	static $rendered = false;
	if ( $rendered ) return;
	$rendered = true;
	echo vaxx_shortcode_footer();
	echo vaxx_shortcode_mini_cart();
}
add_action( 'wp_footer', 'vaxx_render_footer_hook', 5 );
