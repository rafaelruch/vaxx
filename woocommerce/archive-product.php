<?php
/**
 * VAXX · Archive de produtos (Shop + taxonomia product_line/muscle_group)
 *
 * Baseado no preview linha-articulados.html. O hero se adapta:
 * — na taxonomia product_line, mostra nome + descrição do termo
 * — na /shop/ (catálogo todo), mostra hero genérico
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// Hero — adapta conforme contexto
$is_tax     = is_tax( 'product_line' );
$term       = $is_tax ? get_queried_object() : null;
$hero_eye   = $is_tax ? 'LINHA · ' . strtoupper( $term->name ) : 'CATÁLOGO COMPLETO';
// Split em duas partes: branco e lime. Preview = "Máquinas que se ajustam a você."
$hero_title_main  = $is_tax ? $term->name : 'Todos os equipamentos';
$hero_title_lime  = $is_tax ? '' : 'VAXX.';
$hero_lede  = $is_tax
	? ( $term->description ?: 'Regulagem real de 1,55 m a 1,95 m. Projeto nacional, fabricado em Jaraguá do Sul.' )
	: 'Linha completa de equipamentos para academia, fabricada em Jaraguá do Sul por quem treina todo dia.';

// Conta de produtos na query atual
$total = wc_get_loop_prop( 'total' );

// Taxonomias ativas pra filtros — ordem canonica.
// Só entra grupo que tem produto: chip de grupo vazio filtra pra lista em
// branco e passa a impressão de catálogo quebrado. Quando a fábrica cadastrar
// um produto do grupo, o chip volta sozinho.
$muscle_order = array( 'peito', 'costas', 'ombros', 'pernas', 'gluteos', 'bracos', 'core' );
$muscles      = array();
foreach ( $muscle_order as $slug ) {
	$t = get_term_by( 'slug', $slug, 'muscle_group' );
	if ( $t && $t->count > 0 ) { $muscles[] = $t; }
}
?>

<main class="page-linha">

	<!-- ───── HERO ───── -->
	<section class="hero-linha" aria-label="<?php echo esc_attr( trim( $hero_title_main . ' ' . $hero_title_lime ) ); ?>">
		<div class="hero-linha__bg" aria-hidden="true"></div>
		<div class="hero-linha__content">
			<span class="hero-linha__eyebrow"><?php echo esc_html( $hero_eye ); ?></span>
			<h1 class="hero-linha__title">
				<?php echo esc_html( $hero_title_main ); ?>
				<?php if ( $hero_title_lime ) : ?>
					<span class="lime"><?php echo esc_html( $hero_title_lime ); ?></span>
				<?php endif; ?>
			</h1>
			<p class="hero-linha__lede"><?php echo esc_html( $hero_lede ); ?></p>
		</div>
	</section>

	<!-- ───── BREADCRUMB ───── -->
	<nav class="bc" aria-label="Breadcrumb">
		<div class="bc__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
			<span class="sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Produtos</a>
			<?php if ( $is_tax ) : ?>
				<span class="sep" aria-hidden="true">›</span>
				<span class="is-current" aria-current="page"><?php echo esc_html( $term->name ); ?></span>
			<?php endif; ?>
		</div>
	</nav>

	<!-- ───── TOOLBAR ───── -->
	<div class="toolbar" id="toolbar">
		<div class="toolbar__inner">
			<span class="toolbar__count"><strong id="toolbarCount"><?php echo intval( $total ); ?></strong> produtos encontrados</span>

			<button type="button" class="toolbar__mobile-btn" id="filtersOpenBtn" aria-haspopup="dialog" aria-controls="filtersSheet">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/>
					<line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/>
					<line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/>
					<line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/>
					<line x1="17" y1="16" x2="23" y2="16"/>
				</svg>
				Filtrar + Ordenar
			</button>

			<div class="toolbar__chips" role="group" aria-label="Filtrar por grupo muscular">
				<button type="button" class="toolbar__chip is-active" data-filter="all">Todos</button>
				<?php foreach ( $muscles as $m ) : ?>
					<button type="button" class="toolbar__chip" data-filter="<?php echo esc_attr( $m->slug ); ?>"><?php echo esc_html( $m->name ); ?></button>
				<?php endforeach; ?>
			</div>

			<label class="toolbar__toggle">
				<input type="checkbox" id="toggleRegulagem">
				<span class="toolbar__toggle-track" aria-hidden="true"></span>
				<span class="toolbar__toggle-label">Só regulagem 1,55–1,95</span>
			</label>

			<?php
			$orderby_current = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
			$sort_options = array(
				'menu_order' => 'Relev\u00e2ncia',
				'popularity' => 'Mais vendidos',
				'price'      => 'Menor pre\u00e7o',
				'price-desc' => 'Maior pre\u00e7o',
				'date'       => 'Lan\u00e7amento',
			);
			?>
			<form class="toolbar__sort" method="get" action="">
				<label for="orderby" class="toolbar__sort-label">Ordenar</label>
				<select id="orderby" name="orderby" class="toolbar__sort-select" onchange="this.form.submit()" aria-label="Ordenar produtos">
					<option value="menu_order"<?php selected( $orderby_current, 'menu_order' ); ?>>Relev&acirc;ncia</option>
					<option value="popularity"<?php selected( $orderby_current, 'popularity' ); ?>>Mais vendidos</option>
					<option value="price"<?php selected( $orderby_current, 'price' ); ?>>Menor pre&ccedil;o</option>
					<option value="price-desc"<?php selected( $orderby_current, 'price-desc' ); ?>>Maior pre&ccedil;o</option>
					<option value="date"<?php selected( $orderby_current, 'date' ); ?>>Lan&ccedil;amento</option>
				</select>
				<?php // Preserva outros GET params
				foreach ( $_GET as $k => $v ) :
					if ( $k === 'orderby' || $k === 'submit' ) continue;
					if ( is_scalar( $v ) ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( wp_unslash( $v ) ); ?>">
					<?php endif; endforeach; ?>
			</form>
		</div>
	</div>

	<!-- ───── GRID DE PRODUTOS ───── -->
	<section class="catalogo" aria-label="Produtos">
		<div class="catalogo__inner">

			<?php if ( woocommerce_product_loop() ) : ?>
				<div class="catalogo__grid" id="catalogoGrid">
					<?php
					if ( wc_get_loop_prop( 'is_shortcode' ) ) {
						$columns = absint( wc_get_loop_prop( 'columns' ) );
					} else {
						$columns = wc_get_default_products_per_row();
					}

					while ( have_posts() ) :
						the_post();
						wc_get_template_part( 'content', 'product' );
					endwhile;
					?>
				</div>

				<div class="pagination" role="navigation" aria-label="Paginação">
					<?php woocommerce_pagination(); ?>
				</div>
			<?php else : ?>
				<div class="catalogo__empty">
					<h3>Nenhum produto encontrado</h3>
					<p>Tente remover algum filtro ou voltar ao catálogo.</p>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<!-- ───── SEO · 3 COLS ───── -->
	<section class="seo" aria-label="Por que VAXX">
		<div class="seo__bg"></div>
		<div class="seo__inner">
			<div class="seo__header">
				<span class="seo__eyebrow">Por que VAXX</span>
				<h2 class="seo__title">Engenharia, regulagem<br>e garantia de fábrica.</h2>
			</div>
			<div class="seo__cols">
				<div class="seo__col">
					<div class="seo__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
					</div>
					<h3>Engenharia de fábrica</h3>
					<p>Estrutura em aço SAE 1020, solda robotizada, pintura eletrostática preta fosca. Cada peça inspecionada antes da montagem final em Jaraguá do Sul.</p>
				</div>
				<div class="seo__col">
					<div class="seo__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="12" x2="20" y2="12"/><line x1="6" y1="8" x2="6" y2="16"/><line x1="10" y1="6" x2="10" y2="18"/><line x1="14" y1="8" x2="14" y2="16"/><line x1="18" y1="10" x2="18" y2="14"/></svg>
					</div>
					<h3>Regulagem real 1,55–1,95</h3>
					<p>Assento, encosto e apoios com múltiplas posições numeradas. <strong>Serve da sua mãe ao seu primo de 1,92.</strong></p>
					<a href="<?php echo esc_url( home_url( '/quem-somos/' ) ); ?>">Conheça a fábrica</a>
				</div>
				<div class="seo__col">
					<div class="seo__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
					</div>
					<h3>Garantia e instalação</h3>
					<p><strong>10 anos</strong> garantia estrutural · <strong>2 anos</strong> estofados · <strong>1 ano</strong> componentes móveis.</p>
					<a href="<?php echo esc_url( home_url( '/garantia/' ) ); ?>">Ver termos de garantia</a>
				</div>
			</div>
		</div>
	</section>

</main>

<?php
get_footer( 'shop' );
