<?php
/**
 * VAXX · Theme Options (Customizer)
 * Todas as strings globais editáveis pelo cliente via Aparência → Personalizar
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Defaults canônicos — usados tanto no Customizer quanto no frontend
 * (porque defaults de add_setting só aplicam no contexto do Customizer).
 */
function vaxx_option_defaults() {
	return array(
		// Contato — vazios por default. O cliente preenche em Aparência → Personalizar.
		// Sem fallback fake pra evitar exibir telefone/e-mail/WhatsApp inválido em produção.
		'whatsapp_numero'  => '',
		'whatsapp_display' => '',
		'telefone'         => '',
		'email_comercial'  => '',
		'email_suporte'    => '',
		'email_dpo'        => '',
		'cta_pill_curto'   => 'Fale conosco',
		'cta_pill_longo'   => 'Fale com quem fabricou',
		// Empresa — strings da marca podem vir como default (são fixas, não placeholder).
		// Dados que mudam por loja/CNPJ ficam vazios.
		'razao_social'     => 'Grupo Delva — Indústria Metálica Ltda.',
		'nome_fantasia'    => 'VAXX',
		'cnpj'             => '',
		'desde_ano'        => '2008',
		'endereco_rua'     => '',
		'endereco_bairro'  => '',
		'endereco_cidade'  => 'Jaraguá do Sul / SC',
		'endereco_cep'     => '',
		'slogan'           => 'Feito por quem treina. Pra quem treina.',
		'tagline_footer'   => 'Grupo Delva · Desde 2008',
		'desc_footer'      => 'Linha completa de equipamentos para academia, fabricada em Jaraguá do Sul por quem treina todo dia. Direto da fábrica, sem intermediário.',
		// Redes sociais — vazios por default. Ícones só aparecem se URL preenchida.
		'social_instagram' => '',
		'social_youtube'   => '',
		'social_facebook'  => '',
		'social_linkedin'  => '',
		// Topbar
		'topbar_text_1'    => 'FEITO POR QUEM TREINA',
		'topbar_text_2'    => 'PRA QUEM TREINA',
		'topbar_ativo'     => 'on',
	);
}

/**
 * Registra painéis/seções/campos do Customizer.
 */
function vaxx_customize_register( $wp_customize ) {

	// ───── PAINEL PRINCIPAL ─────
	$wp_customize->add_panel( 'vaxx_panel', array(
		'title'       => __( 'VAXX · Configurações da marca', 'vaxx' ),
		'description' => __( 'Todas as informações globais do site: telefone, e-mail, endereço, redes sociais e textos que aparecem em múltiplas páginas.', 'vaxx' ),
		'priority'    => 10,
	) );

	// ───── SEÇÃO: CONTATO ─────
	$wp_customize->add_section( 'vaxx_contato', array(
		'title' => __( 'Contato e canais', 'vaxx' ),
		'panel' => 'vaxx_panel',
	) );

	$contact_fields = array(
		'whatsapp_numero'  => array( 'label' => 'Número WhatsApp (formato E.164, sem sinais)', 'default' => '', 'type' => 'text' ),
		'whatsapp_display' => array( 'label' => 'WhatsApp formatado (exibição)',                'default' => '', 'type' => 'text' ),
		'telefone'         => array( 'label' => 'Telefone fixo (com DDD)',                      'default' => '',  'type' => 'text' ),
		'email_comercial'  => array( 'label' => 'E-mail comercial',                             'default' => '', 'type' => 'email' ),
		'email_suporte'    => array( 'label' => 'E-mail suporte',                               'default' => '', 'type' => 'email' ),
		'email_dpo'        => array( 'label' => 'E-mail DPO (LGPD)',                            'default' => '', 'type' => 'email' ),
		'cta_pill_curto'   => array( 'label' => 'Texto CTA pequeno (header mobile)',            'default' => 'Fale conosco',   'type' => 'text' ),
		'cta_pill_longo'   => array( 'label' => 'Texto CTA grande (header desktop)',            'default' => 'Fale com quem fabricou', 'type' => 'text' ),
	);

	foreach ( $contact_fields as $slug => $opts ) {
		$wp_customize->add_setting( 'vaxx_' . $slug, array(
			'default'           => $opts['default'],
			'sanitize_callback' => $opts['type'] === 'email' ? 'sanitize_email' : 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'vaxx_' . $slug, array(
			'label'    => $opts['label'],
			'section'  => 'vaxx_contato',
			'type'     => $opts['type'],
		) );
	}

	// ───── SEÇÃO: ENDEREÇO / EMPRESA ─────
	$wp_customize->add_section( 'vaxx_empresa', array(
		'title' => __( 'Empresa e endereço', 'vaxx' ),
		'panel' => 'vaxx_panel',
	) );

	$empresa_fields = array(
		'razao_social'   => array( 'label' => 'Razão social',        'default' => 'Grupo Delva — Indústria Metálica Ltda.', 'type' => 'text' ),
		'nome_fantasia'  => array( 'label' => 'Nome fantasia',       'default' => 'VAXX', 'type' => 'text' ),
		'cnpj'           => array( 'label' => 'CNPJ',                'default' => '', 'type' => 'text' ),
		'desde_ano'      => array( 'label' => 'Ano de fundação',     'default' => '2008', 'type' => 'text' ),
		'endereco_rua'   => array( 'label' => 'Rua + número',        'default' => '', 'type' => 'text' ),
		'endereco_bairro'=> array( 'label' => 'Bairro',              'default' => '', 'type' => 'text' ),
		'endereco_cidade'=> array( 'label' => 'Cidade / UF',         'default' => 'Jaraguá do Sul / SC', 'type' => 'text' ),
		'endereco_cep'   => array( 'label' => 'CEP',                 'default' => '', 'type' => 'text' ),
		'slogan'         => array( 'label' => 'Slogan',              'default' => 'Feito por quem treina. Pra quem treina.', 'type' => 'text' ),
		'tagline_footer' => array( 'label' => 'Tagline rodapé',      'default' => 'Grupo Delva · Desde 2008', 'type' => 'text' ),
		'desc_footer'    => array( 'label' => 'Descrição rodapé',    'default' => 'Linha completa de equipamentos para academia, fabricada em Jaraguá do Sul por quem treina todo dia. Direto da fábrica, sem intermediário.', 'type' => 'textarea' ),
	);

	foreach ( $empresa_fields as $slug => $opts ) {
		$wp_customize->add_setting( 'vaxx_' . $slug, array(
			'default'           => $opts['default'],
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'vaxx_' . $slug, array(
			'label'   => $opts['label'],
			'section' => 'vaxx_empresa',
			'type'    => $opts['type'],
		) );
	}

	// ───── SEÇÃO: REDES SOCIAIS ─────
	$wp_customize->add_section( 'vaxx_social', array(
		'title' => __( 'Redes sociais', 'vaxx' ),
		'panel' => 'vaxx_panel',
	) );

	$social_fields = array(
		'social_instagram' => array( 'label' => 'Instagram (URL completa)', 'default' => '' ),
		'social_youtube'   => array( 'label' => 'YouTube (URL completa)',   'default' => '' ),
		'social_facebook'  => array( 'label' => 'Facebook (URL completa)',  'default' => '' ),
		'social_linkedin'  => array( 'label' => 'LinkedIn (URL completa)',  'default' => '' ),
	);

	foreach ( $social_fields as $slug => $opts ) {
		$wp_customize->add_setting( 'vaxx_' . $slug, array(
			'default'           => $opts['default'],
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'vaxx_' . $slug, array(
			'label'   => $opts['label'],
			'section' => 'vaxx_social',
			'type'    => 'url',
		) );
	}

	// ───── SEÇÃO: TOPBAR (marquee) ─────
	$wp_customize->add_section( 'vaxx_topbar', array(
		'title' => __( 'Topbar (marquee)', 'vaxx' ),
		'panel' => 'vaxx_panel',
	) );

	$wp_customize->add_setting( 'vaxx_topbar_text_1', array(
		'default' => 'FEITO POR QUEM TREINA',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'vaxx_topbar_text_1', array(
		'label' => 'Frase 1',
		'section' => 'vaxx_topbar',
	) );

	$wp_customize->add_setting( 'vaxx_topbar_text_2', array(
		'default' => 'PRA QUEM TREINA',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'vaxx_topbar_text_2', array(
		'label' => 'Frase 2',
		'section' => 'vaxx_topbar',
	) );

	$wp_customize->add_setting( 'vaxx_topbar_ativo', array(
		'default' => 'on',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'vaxx_topbar_ativo', array(
		'label' => 'Ativar topbar?',
		'section' => 'vaxx_topbar',
		'type' => 'checkbox',
	) );
}
add_action( 'customize_register', 'vaxx_customize_register' );

/**
 * Helper: retorna link WhatsApp completo com texto pré-preenchido opcional.
 */
function vaxx_wa_link( $text = 'Oi! Vim pelo site da VAXX' ) {
	$num = vaxx_get_option( 'whatsapp_numero', '' );
	if ( ! $num ) return '';
	return 'https://wa.me/' . $num . '?text=' . rawurlencode( $text );
}

/**
 * Helper: link mailto.
 */
function vaxx_email_link( $key = 'comercial' ) {
	$email = vaxx_get_option( 'email_' . $key, '' );
	return $email ? 'mailto:' . $email : '';
}
