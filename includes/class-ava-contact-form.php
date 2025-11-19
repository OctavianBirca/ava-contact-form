<?php
/**
 * Core class for AVA Contact Form.
 *
 * @package AvaContactForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple contact form handler.
 */
class Ava_Contact_Form {

	/**
	 * Singleton instance.
	 *
	 * @var Ava_Contact_Form|null
	 */
	protected static $instance = null;

	/**
	 * Return singleton instance.
	 *
	 * @return Ava_Contact_Form
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire hooks.
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'ava_contact_form', array( $this, 'render_shortcode' ) );
		add_action( 'admin_post_ava_contact_form_submit', array( $this, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_ava_contact_form_submit', array( $this, 'handle_submission' ) );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ava-contact-form', false, dirname( plugin_basename( AVA_CONTACT_FORM_PATH . 'ava-contact-form.php' ) ) . '/languages' );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'ava-contact-form',
			AVA_CONTACT_FORM_URL . 'assets/css/form.css',
			array(),
			AVA_CONTACT_FORM_VERSION
		);

		wp_register_script(
			'ava-contact-form',
			AVA_CONTACT_FORM_URL . 'assets/js/form.js',
			array(),
			AVA_CONTACT_FORM_VERSION,
			true
		);

		wp_localize_script(
			'ava-contact-form',
			'avaContactForm',
			array(
				'i18n' => array(
					'required' => __( 'Veuillez remplir tous les champs obligatoires.', 'ava-contact-form' ),
					'sending'  => __( 'Envoi en cours...', 'ava-contact-form' ),
				),
			)
		);
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		wp_enqueue_style( 'ava-contact-form' );
		wp_enqueue_script( 'ava-contact-form' );

		$atts = shortcode_atts(
			array(
				'title'       => __( 'Contactez-nous', 'ava-contact-form' ),
				'description' => __( 'Laissez-nous un message et nous vous repondrons rapidement.', 'ava-contact-form' ),
				'redirect'    => '',
			),
			$atts,
			'ava_contact_form'
		);

		$status = isset( $_GET['ava_contact_status'] ) ? sanitize_key( wp_unslash( $_GET['ava_contact_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$template = AVA_CONTACT_FORM_PATH . 'templates/form.php';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		ob_start();
		$title       = $atts['title'];
		$description = $atts['description'];
		$redirect    = $atts['redirect'];
		include $template;

		return (string) ob_get_clean();
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	public function handle_submission() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Methode invalide.', 'ava-contact-form' ) );
		}

		check_admin_referer( 'ava_contact_form_submit', 'ava_contact_form_nonce' );

		$name    = isset( $_POST['ava_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ava_contact_name'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email   = isset( $_POST['ava_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['ava_contact_email'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$phone   = isset( $_POST['ava_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ava_contact_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$message = isset( $_POST['ava_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ava_contact_message'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect= isset( $_POST['ava_contact_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['ava_contact_redirect'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$referer = isset( $_POST['ava_contact_referer'] ) ? esc_url_raw( wp_unslash( $_POST['ava_contact_referer'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = 'success';

		if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
			$result = 'invalid';
		} else {
			$to       = get_option( 'admin_email' );
			$subject  = sprintf( __( 'Nouveau message sur le site %s', 'ava-contact-form' ), get_bloginfo( 'name' ) );
			$body     = sprintf(
				"Message recu via le formulaire de contact :\n\nNom : %s\nE-mail : %s\nTelephone : %s\n\nMessage :\n%s\n",
				$name,
				$email,
				$phone,
				$message
			);
			$headers  = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
			$sent     = wp_mail( $to, $subject, $body, $headers );

			if ( ! $sent ) {
				$result = 'error';
			}
		}

		$target = $redirect ? $redirect : ( $referer ? $referer : wp_get_referer() );
		$target = $target ? $target : home_url( '/' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'ava_contact_status' => $result,
				),
				$target
			)
		);
		exit;
	}
}

