<?php
/**
 * VAXX · Custom Post Types
 *  - rent_lead   (leads de aluguel via modal PDP · per memória project_rental_flow)
 *  - case_study  (cases de clientes · usado nas landings)
 *  - depoimento  (depoimentos da galeria)
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function vaxx_register_post_types() {

	// ───── RENT_LEAD — Leads de aluguel (não público) ─────
	register_post_type( 'rent_lead', array(
		'labels' => array(
			'name'               => __( 'Leads de Aluguel', 'vaxx' ),
			'singular_name'      => __( 'Lead de Aluguel', 'vaxx' ),
			'menu_name'          => __( 'Aluguéis', 'vaxx' ),
			'all_items'          => __( 'Todos os leads', 'vaxx' ),
			'edit_item'          => __( 'Editar lead', 'vaxx' ),
			'view_item'          => __( 'Ver lead', 'vaxx' ),
			'add_new'            => __( 'Novo lead', 'vaxx' ),
			'add_new_item'       => __( 'Adicionar novo lead', 'vaxx' ),
			'search_items'       => __( 'Buscar leads', 'vaxx' ),
			'not_found'          => __( 'Nenhum lead encontrado', 'vaxx' ),
		),
		'public'            => false,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_rest'      => false,
		'menu_position'     => 56,
		'menu_icon'         => 'dashicons-calendar-alt',
		'supports'          => array( 'title', 'custom-fields' ),
		'capability_type'   => 'post',
		'has_archive'       => false,
	) );

	// ───── CASE_STUDY — Cases de clientes (usado nas landings) ─────
	register_post_type( 'case_study', array(
		'labels' => array(
			'name'          => __( 'Cases', 'vaxx' ),
			'singular_name' => __( 'Case', 'vaxx' ),
			'menu_name'     => __( 'Cases', 'vaxx' ),
			'all_items'     => __( 'Todos os cases', 'vaxx' ),
			'add_new'       => __( 'Novo case', 'vaxx' ),
			'add_new_item'  => __( 'Novo case', 'vaxx' ),
			'edit_item'     => __( 'Editar case', 'vaxx' ),
			'view_item'     => __( 'Ver case', 'vaxx' ),
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_position' => 55,
		'menu_icon'     => 'dashicons-awards',
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'cases', 'with_front' => false ),
	) );

	// ───── DEPOIMENTO — Galeria de depoimentos ─────
	register_post_type( 'depoimento', array(
		'labels' => array(
			'name'          => __( 'Depoimentos', 'vaxx' ),
			'singular_name' => __( 'Depoimento', 'vaxx' ),
			'menu_name'     => __( 'Depoimentos', 'vaxx' ),
			'all_items'     => __( 'Todos os depoimentos', 'vaxx' ),
			'add_new'       => __( 'Novo depoimento', 'vaxx' ),
			'add_new_item'  => __( 'Novo depoimento', 'vaxx' ),
			'edit_item'     => __( 'Editar depoimento', 'vaxx' ),
		),
		'public'        => false,
		'show_ui'       => true,
		'show_in_rest'  => true,
		'menu_position' => 54,
		'menu_icon'     => 'dashicons-format-quote',
		'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'has_archive'   => false,
	) );
}
add_action( 'init', 'vaxx_register_post_types', 5 );

/**
 * Colunas extras no listing admin de rent_lead.
 */
function vaxx_rent_lead_admin_columns( $columns ) {
	$new = array();
	$new['cb'] = $columns['cb'];
	$new['title']    = __( 'Lead', 'vaxx' );
	$new['produto']  = __( 'Produto', 'vaxx' );
	$new['contato']  = __( 'Contato', 'vaxx' );
	$new['status']   = __( 'Status', 'vaxx' );
	$new['date']     = $columns['date'];
	return $new;
}
add_filter( 'manage_rent_lead_posts_columns', 'vaxx_rent_lead_admin_columns' );

function vaxx_rent_lead_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'produto':
			echo esc_html( get_post_meta( $post_id, 'produto', true ) );
			break;
		case 'contato':
			$email = get_post_meta( $post_id, 'email', true );
			$tel   = get_post_meta( $post_id, 'telefone', true );
			echo esc_html( $tel ) . '<br><small>' . esc_html( $email ) . '</small>';
			break;
		case 'status':
			$status = get_post_meta( $post_id, 'status_lead', true );
			if ( ! $status ) $status = 'Aguardando contato';
			echo esc_html( $status );
			break;
	}
}
add_action( 'manage_rent_lead_posts_custom_column', 'vaxx_rent_lead_column_content', 10, 2 );
