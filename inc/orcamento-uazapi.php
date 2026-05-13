<?php
/**
 * VAXX · Integração Uazapi (WhatsApp não-oficial)
 *
 * Listener do hook `vaxx_orcamento_enviado` que dispara mensagem WhatsApp
 * para o número do responsável quando um orçamento é submetido.
 *
 * Defaults seguem a Uazapi v2 (`POST {base}/send/text` com header `token`
 * e body `{ number, text }`). Use os filtros abaixo se sua instância usa
 * outra rota / outro nome de campo:
 *   - vaxx_uazapi_endpoint  (string URL completa)
 *   - vaxx_uazapi_headers   (array de headers)
 *   - vaxx_uazapi_body      (array do body)
 *   - vaxx_uazapi_message   (string mensagem final, formatada)
 *
 * Configuração via Aparência → Personalizar → VAXX · Integração WhatsApp.
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra seção do Customizer com URL base, token, status on/off
 * e número do responsável (override do whatsapp_numero da marca).
 */
add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'vaxx_uazapi', array(
		'title'       => __( 'VAXX · Integração WhatsApp (Uazapi)', 'vaxx' ),
		'panel'       => 'vaxx_panel',
		'description' => 'Envio automático de mensagens via Uazapi quando um orçamento é solicitado. Deixe o status como "Desligado" para usar apenas o e-mail.',
		'priority'    => 30,
	) );

	$fields = array(
		'uazapi_status'   => array( 'label' => 'Status da integração', 'type' => 'select', 'default' => 'off', 'choices' => array( 'off' => 'Desligado', 'on' => 'Ligado' ) ),
		'uazapi_url'      => array( 'label' => 'URL base da instância (ex.: https://minhainstancia.uazapi.com)', 'type' => 'url', 'default' => '' ),
		'uazapi_token'    => array( 'label' => 'Token de autenticação', 'type' => 'text', 'default' => '' ),
		'uazapi_destino'  => array( 'label' => 'Número do responsável (E.164, ex.: 5547999999999) — vazio usa o WhatsApp da marca', 'type' => 'text', 'default' => '5547999402864' ),
	);

	foreach ( $fields as $slug => $opts ) {
		$wp_customize->add_setting( 'vaxx_' . $slug, array(
			'default'           => $opts['default'],
			'sanitize_callback' => $opts['type'] === 'url' ? 'esc_url_raw' : 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$ctl = array( 'label' => $opts['label'], 'section' => 'vaxx_uazapi', 'type' => $opts['type'] );
		if ( isset( $opts['choices'] ) ) $ctl['choices'] = $opts['choices'];
		$wp_customize->add_control( 'vaxx_' . $slug, $ctl );
	}
}, 20 );

/**
 * Envia texto via Uazapi. Retorna true/false. Não lança exception.
 */
function vaxx_uazapi_send_text( $number, $text ) {
	$status = get_theme_mod( 'vaxx_uazapi_status', 'off' );
	if ( $status !== 'on' ) {
		error_log( '[VAXX][Uazapi] Status=desligado — skip envio.' );
		return false;
	}

	$base  = trim( (string) get_theme_mod( 'vaxx_uazapi_url', '' ), '/' );
	$token = trim( (string) get_theme_mod( 'vaxx_uazapi_token', '' ) );
	if ( ! $base || ! $token ) {
		error_log( '[VAXX][Uazapi] URL ou token vazios — skip.' );
		return false;
	}

	$number_clean = preg_replace( '/\D/', '', (string) $number );
	if ( ! $number_clean ) {
		error_log( '[VAXX][Uazapi] Número destinatário vazio — skip.' );
		return false;
	}
	// Garante prefixo 55 (Brasil) se número vier sem
	if ( strlen( $number_clean ) <= 11 && strpos( $number_clean, '55' ) !== 0 ) {
		$number_clean = '55' . $number_clean;
	}

	$endpoint = apply_filters( 'vaxx_uazapi_endpoint', $base . '/send/text', $base );
	$headers  = apply_filters( 'vaxx_uazapi_headers', array(
		'token'        => $token,
		'Content-Type' => 'application/json',
	), $token );
	$body     = apply_filters( 'vaxx_uazapi_body', array(
		'number' => $number_clean,
		'text'   => $text,
	), $number_clean, $text );

	$started = microtime( true );
	error_log( sprintf( '[VAXX][Uazapi] →POST %s · destino=%s · %d chars', $endpoint, $number_clean, strlen( $text ) ) );

	$resp = wp_remote_post( $endpoint, array(
		'headers' => $headers,
		'body'    => wp_json_encode( $body ),
		'timeout' => 15,
	) );

	$elapsed_ms = (int) ( ( microtime( true ) - $started ) * 1000 );

	if ( is_wp_error( $resp ) ) {
		error_log( sprintf( '[VAXX][Uazapi] ✗ Falha HTTP em %dms: %s', $elapsed_ms, $resp->get_error_message() ) );
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $resp );
	$resp_body = wp_remote_retrieve_body( $resp );
	if ( $code < 200 || $code >= 300 ) {
		error_log( sprintf( '[VAXX][Uazapi] ✗ Resposta %d em %dms · body: %s', $code, $elapsed_ms, $resp_body ) );
		return false;
	}

	// Sucesso — loga short preview da resposta (Uazapi normalmente devolve JSON com id/status)
	$preview = is_string( $resp_body ) ? substr( $resp_body, 0, 200 ) : '';
	error_log( sprintf( '[VAXX][Uazapi] ✓ OK %d em %dms · destino=%s · body: %s', $code, $elapsed_ms, $number_clean, $preview ) );
	return true;
}

/**
 * Listener: monta a mensagem do orçamento e dispara via Uazapi.
 * Roda DEPOIS da notificação por e-mail (não bloqueia se falhar).
 */
add_action( 'vaxx_orcamento_enviado', function( $order, $wa_link, $d ) {
	if ( get_theme_mod( 'vaxx_uazapi_status', 'off' ) !== 'on' ) return;

	$destino = trim( (string) get_theme_mod( 'vaxx_uazapi_destino', '' ) );
	if ( ! $destino ) {
		$destino = vaxx_get_option( 'whatsapp_numero', '' );
	}
	if ( ! $destino ) {
		error_log( '[VAXX][Uazapi] Sem número de responsável configurado — skip.' );
		return;
	}

	$is_pj   = ( '2' === (string) $order->get_meta( '_billing_persontype' ) );
	$total   = wp_strip_all_tags( wc_price( $order->get_total() ) );
	$id      = $order->get_id();
	$primeiro_nome = explode( ' ', $d['nome'] )[0] ?? $d['nome'];

	// Lista de itens
	$items_lines = array();
	foreach ( $order->get_items() as $it ) {
		$items_lines[] = sprintf( '• %d× %s', $it->get_quantity(), $it->get_name() );
	}
	$items_text = implode( "\n", $items_lines );

	// Telefone do cliente formatado pra wa.me
	$tel_clean = preg_replace( '/\D/', '', $d['tel'] );
	if ( $tel_clean && strlen( $tel_clean ) <= 11 && strpos( $tel_clean, '55' ) !== 0 ) {
		$tel_clean = '55' . $tel_clean;
	}
	$wa_cliente_link = $tel_clean ? "https://wa.me/{$tel_clean}" : '';

	$admin_url = admin_url( 'post.php?post=' . $id . '&action=edit' );

	// WhatsApp markdown: *bold*, _italic_, ~strike~, ```code```
	$msg  = "*🔔 Novo orçamento #{$id}*\n\n";
	$msg .= "*Tipo:* " . ( $is_pj ? 'Pessoa Jurídica' : 'Pessoa Física' ) . "\n";
	$msg .= "*Nome:* {$d['nome']}\n";
	if ( $is_pj ) {
		$msg .= "*Razão Social:* {$d['razao']}\n";
		$msg .= "*CNPJ:* {$d['cnpj']}\n";
	} else {
		$msg .= "*CPF:* " . ( $d['cpf'] ?: '—' ) . "\n";
	}
	$msg .= "*E-mail:* {$d['email']}\n";
	$msg .= "*Telefone:* {$d['tel']}\n\n";

	$msg .= "*Endereço:*\n";
	$msg .= "{$d['rua']}, {$d['numero']}";
	if ( ! empty( $d['comp'] ) ) $msg .= " ({$d['comp']})";
	$msg .= "\n{$d['bairro']} — {$d['cidade']}/{$d['uf']}\n";
	$msg .= "CEP {$d['cep']}\n\n";

	$msg .= "*Itens:*\n{$items_text}\n\n";
	$msg .= "*Total estimado:* {$total}\n\n";
	if ( $wa_cliente_link ) {
		$msg .= "📞 Falar com cliente: {$wa_cliente_link}\n";
	}
	$msg .= "🔗 Admin: {$admin_url}";

	$msg = apply_filters( 'vaxx_uazapi_message', $msg, $order, $d );

	vaxx_uazapi_send_text( $destino, $msg );
}, 10, 3 );
