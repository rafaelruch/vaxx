<?php
/**
 * VAXX · Template override: Login + Register forms
 *
 * Mostra as duas colunas (Entrar | Cadastre-se) lado a lado em desktop,
 * empilhadas em mobile, com visual VAXX (carvão + lima).
 *
 * Depende de: woocommerce_enable_myaccount_registration = yes.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );
?>

<main class="page-mc-login">
	<nav class="bc" aria-label="Breadcrumb">
		<div class="bc__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Início', 'vaxx' ); ?></a>
			<span class="sep" aria-hidden="true">›</span>
			<span class="is-current" aria-current="page"><?php esc_html_e( 'Minha conta', 'vaxx' ); ?></span>
		</div>
	</nav>

	<section class="mc-login-hero" aria-label="Identificação do usuário">
		<div class="mc-login-hero__inner">
			<span class="mc-hero__eyebrow"><?php esc_html_e( 'MINHA CONTA', 'vaxx' ); ?></span>
			<h1 class="mc-hero__title">
				<?php esc_html_e( 'Entre', 'vaxx' ); ?> <span class="lime"><?php esc_html_e( 'ou crie sua conta', 'vaxx' ); ?></span>
			</h1>
			<p class="mc-login-hero__desc"><?php esc_html_e( 'Acompanhe seus pedidos, guarde favoritos e acelere o próximo checkout.', 'vaxx' ); ?></p>
		</div>
	</section>

	<div class="mc-login-grid">

		<!-- ENTRAR -->
		<div class="mc-login-card" id="customer_login">
			<h2 class="mc-login-card__title"><?php esc_html_e( 'Entrar', 'vaxx' ); ?></h2>
			<p class="mc-login-card__desc"><?php esc_html_e( 'Já tem conta? Use seu e-mail e senha.', 'vaxx' ); ?></p>

			<form class="woocommerce-form woocommerce-form-login login mc-form" method="post">
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<label for="username"><?php esc_html_e( 'E-mail', 'vaxx' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />

				<label for="password"><?php esc_html_e( 'Senha', 'vaxx' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required />

				<?php do_action( 'woocommerce_login_form' ); ?>

				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme mc-form__remember">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span><?php esc_html_e( 'Lembrar de mim', 'vaxx' ); ?></span>
				</label>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit mc-btn mc-btn--lime" name="login" value="<?php esc_attr_e( 'Entrar', 'vaxx' ); ?>"><?php esc_html_e( 'Entrar', 'vaxx' ); ?></button>

				<p class="mc-form__forgot">
					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Esqueceu a senha?', 'vaxx' ); ?></a>
				</p>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>

		<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

		<!-- CADASTRE-SE -->
		<div class="mc-login-card mc-login-card--alt" id="customer_register">
			<h2 class="mc-login-card__title"><?php esc_html_e( 'Cadastre-se', 'vaxx' ); ?></h2>
			<p class="mc-login-card__desc"><?php esc_html_e( 'Primeira vez por aqui? Criar conta leva 30 segundos.', 'vaxx' ); ?></p>

			<form method="post" class="woocommerce-form woocommerce-form-register register mc-form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<label for="reg_username"><?php esc_html_e( 'Nome de usuário', 'vaxx' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
				<?php endif; ?>

				<label for="reg_email"><?php esc_html_e( 'E-mail', 'vaxx' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required />

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<label for="reg_password"><?php esc_html_e( 'Senha', 'vaxx' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required />
				<?php else : ?>
					<p class="mc-form__hint"><?php esc_html_e( 'Uma senha será gerada e enviada para o seu e-mail.', 'vaxx' ); ?></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit mc-btn mc-btn--outline" name="register" value="<?php esc_attr_e( 'Criar conta', 'vaxx' ); ?>"><?php esc_html_e( 'Criar conta', 'vaxx' ); ?></button>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>

		<?php endif; ?>

	</div>
</main>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
