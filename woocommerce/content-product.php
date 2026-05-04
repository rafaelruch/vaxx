<?php
/**
 * VAXX · Card de produto no loop (grid).
 * Renderiza .prod-card matching preview linha-articulados.html.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// product_line (linha): primeiro termo
$lines = wp_get_post_terms( $product->get_id(), 'product_line', array( 'fields' => 'names' ) );
$line  = ! empty( $lines ) ? $lines[0] : 'VAXX';

// muscle_group (grupos) — usado como data-grupo + tags visuais
$muscles_raw   = wp_get_post_terms( $product->get_id(), 'muscle_group' );
$muscle_slugs  = array(); // todos os grupos (pra filtro multi-match)
$tags          = array();
if ( ! is_wp_error( $muscles_raw ) && ! empty( $muscles_raw ) ) {
	foreach ( $muscles_raw as $m ) {
		$muscle_slugs[] = $m->slug;
		$tags[]         = $m->name;
	}
}
$muscle_data = implode( ' ', $muscle_slugs ); // space-separated para data-grupo

// regulagem real (ACF/meta)
$has_reg = (bool) get_post_meta( $product->get_id(), 'vaxx_regulagem_real', true );
$reg_val = get_post_meta( $product->get_id(), 'vaxx_regulagem', true ) ?: '1,55–1,95';

// Imagem destacada
$thumb = get_the_post_thumbnail_url( $product->get_id(), 'vaxx-prod-card' );
if ( ! $thumb && function_exists( 'wc_placeholder_img_src' ) ) {
	$thumb = wc_placeholder_img_src( 'vaxx-prod-card' );
}
?>
<a href="<?php echo esc_url( $product->get_permalink() ); ?>"
   class="prod-card"
   data-grupo="<?php echo esc_attr( $muscle_data ); ?>"
   data-regulagem="<?php echo $has_reg ? 'true' : 'false'; ?>">
	<div class="prod-card__media">
		<span class="prod-card__line-badge"><?php echo esc_html( $line ); ?></span>
		<?php if ( $has_reg ) : ?>
			<span class="prod-card__regulagem"><?php echo esc_html( $reg_val ); ?></span>
		<?php endif; ?>
		<img src="<?php echo esc_url( $thumb ); ?>"
			 alt="<?php echo esc_attr( $product->get_name() ); ?>"
			 loading="lazy">
	</div>
	<div class="prod-card__body">
		<h3 class="prod-card__title"><?php echo esc_html( $product->get_name() ); ?></h3>
		<?php if ( ! empty( $tags ) ) : ?>
			<div class="prod-card__tags">
				<?php foreach ( array_slice( $tags, 0, 3 ) as $tag ) : ?>
					<span class="prod-card__tag"><?php echo esc_html( $tag ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="prod-card__cta">
			<span class="prod-card__cta-text">Ver produto</span>
			<span class="prod-card__cta-arrow">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
			</span>
		</div>
	</div>
</a>
