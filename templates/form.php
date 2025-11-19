<?php
/**
 * Front template for AVA Contact Form.
 *
 * @package AvaContactForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action      = esc_url( admin_url( 'admin-post.php' ) );
$nonce_field = wp_nonce_field( 'ava_contact_form_submit', 'ava_contact_form_nonce', true, false );
$referer     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

?>
<div class="ava-contact-form">
	<?php if ( $title ) : ?>
		<h2 class="ava-contact-form__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( $description ) : ?>
		<p class="ava-contact-form__description"><?php echo esc_html( $description ); ?></p>
	<?php endif; ?>

	<?php if ( 'success' === $status ) : ?>
		<div class="ava-contact-form__notice ava-contact-form__notice--success">
			<?php esc_html_e( 'Merci ! Votre message a ete envoye.', 'ava-contact-form' ); ?>
		</div>
	<?php elseif ( 'error' === $status ) : ?>
		<div class="ava-contact-form__notice ava-contact-form__notice--error">
			<?php esc_html_e( 'Une erreur s\'est produite lors de l\'envoi. Veuillez reessayer.', 'ava-contact-form' ); ?>
		</div>
	<?php elseif ( 'invalid' === $status ) : ?>
		<div class="ava-contact-form__notice ava-contact-form__notice--warning">
			<?php esc_html_e( 'Merci de verifier les champs obligatoires.', 'ava-contact-form' ); ?>
		</div>
	<?php endif; ?>

	<form class="ava-contact-form__form" method="post" action="<?php echo $action; ?>" novalidate>
		<div class="ava-contact-form__field">
			<label for="ava-contact-name"><?php esc_html_e( 'Nom complet', 'ava-contact-form' ); ?> *</label>
			<input type="text" id="ava-contact-name" name="ava_contact_name" required />
		</div>

		<div class="ava-contact-form__field">
			<label for="ava-contact-email"><?php esc_html_e( 'Adresse e-mail', 'ava-contact-form' ); ?> *</label>
			<input type="email" id="ava-contact-email" name="ava_contact_email" required />
		</div>

		<div class="ava-contact-form__field">
			<label for="ava-contact-phone"><?php esc_html_e( 'Telephone', 'ava-contact-form' ); ?></label>
			<input type="tel" id="ava-contact-phone" name="ava_contact_phone" />
		</div>

		<div class="ava-contact-form__field">
			<label for="ava-contact-message"><?php esc_html_e( 'Votre message', 'ava-contact-form' ); ?> *</label>
			<textarea id="ava-contact-message" name="ava_contact_message" rows="5" required></textarea>
		</div>

		<div class="ava-contact-form__actions">
			<button type="submit" class="ava-contact-form__submit">
				<?php esc_html_e( 'Envoyer le message', 'ava-contact-form' ); ?>
			</button>
		</div>

		<input type="hidden" name="action" value="ava_contact_form_submit" />
		<input type="hidden" name="ava_contact_redirect" value="<?php echo esc_attr( $redirect ); ?>" />
		<input type="hidden" name="ava_contact_referer" value="<?php echo esc_attr( $referer ); ?>" />
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</form>
</div>

