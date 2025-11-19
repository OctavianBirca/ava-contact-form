<?php
namespace Ava_Contact_Form;

use Elementor\Widgets_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Submissions handler.
	 *
	 * @var Submissions
	 */
	public $submissions;

	/**
	 * Admin handler.
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Instantiate singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run setup.
	 */
	private function __construct() {
		$this->load_dependencies();

		$this->submissions = new Submissions();
		$this->admin       = new Admin();

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this->submissions, 'register_post_type' ] );
		add_action( 'admin_menu', [ $this->admin, 'register_settings_page' ], 9 );
		add_action( 'admin_init', [ $this->admin, 'register_settings' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_ava_cf_test_email', [ $this, 'handle_test_email' ] );

		add_action( 'wp_ajax_ava_contact_form_submit', [ $this, 'handle_form_submission' ] );
		add_action( 'wp_ajax_nopriv_ava_contact_form_submit', [ $this, 'handle_form_submission' ] );

		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );
		add_action( 'elementor/init', [ $this, 'maybe_register_category' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Include PHP files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once AVA_CF_PATH . 'includes/class-ava-cf-submissions.php';
		require_once AVA_CF_PATH . 'includes/class-ava-cf-admin.php';
	}

	/**
	 * Load localisation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ava-contact-form', false, dirname( plugin_basename( AVA_CF_PATH . 'ava-contact-form.php' ) ) . '/languages' );
	}

	/**
	 * Ensure Elementor category exists.
	 *
	 * @return void
	 */
	public function maybe_register_category() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		\Elementor\Plugin::instance()->elements_manager->add_category(
			'ava-widgets',
			[
				'title' => __( 'Widgets AVA', 'ava-contact-form' ),
				'icon'  => 'fa fa-plug',
			],
			1
		);
	}

	/**
	 * Register Elementor widget.
	 *
	 * @param Widgets_Manager $widgets_manager Elementor manager.
	 * @return void
	 */
	public function register_elementor_widget( $widgets_manager ) {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once AVA_CF_PATH . 'includes/class-ava-cf-widget.php';

		$widgets_manager->register( new Widget() );
	}

	/**
	 * Enqueue admin-only assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$allowed_hooks = [
			'toplevel_page_ava-contact-form',
			'ava-contact-form_page_ava-contact-form',
		];

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_script(
			'ava-contact-form-admin',
			AVA_CF_URL . 'assets/js/admin.js',
			[],
			AVA_CF_VERSION,
			true
		);

		wp_localize_script(
			'ava-contact-form-admin',
			'avaCfAdmin',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ava_cf_test_email' ),
				'messages'  => [
					'sending' => __( 'Envoi de l\'e-mail de test en cours...', 'ava-contact-form' ),
					'success' => __( 'L\'e-mail de test a ete envoye avec succes.', 'ava-contact-form' ),
					'error'   => __( 'L\'envoi de l\'e-mail de test a echoue.', 'ava-contact-form' ),
				],
			]
		);
	}

	/**
	 * Front-end assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_register_style(
			'ava-contact-form',
			AVA_CF_URL . 'assets/css/contact-form.css',
			[],
			AVA_CF_VERSION
		);

		wp_register_script(
			'ava-contact-form',
			AVA_CF_URL . 'assets/js/contact-form.js',
			[],
			AVA_CF_VERSION,
			true
		);

		wp_localize_script(
			'ava-contact-form',
			'avaContactForm',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Handle AJAX test email request.
	 *
	 * @return void
	 */
	public function handle_test_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Vous n\'avez pas l\'autorisation necessaire.', 'ava-contact-form' ),
				],
				403
			);
		}

		check_ajax_referer( 'ava_cf_test_email', 'nonce' );

		$recipient = $this->admin->get_recipient_address();

		if ( empty( $recipient ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Configurez l\'adresse du destinataire avant de tester l\'envoi.', 'ava-contact-form' ),
				],
				400
			);
		}

		$result = $this->send_test_email( $recipient );

		if ( ! $result ) {
			wp_send_json_error(
				[
					'message' => __( 'L\'envoi de l\'e-mail de test a echoue. Verifiez la configuration SMTP.', 'ava-contact-form' ),
				],
				500
			);
		}

		wp_send_json_success();
	}

	/**
	 * Send test email to confirm mailer configuration.
	 *
	 * @param string $recipient Recipient list.
	 * @return bool
	 */
	private function send_test_email( $recipient ) {
		$data = [
			'fields' => [
				[
					'name'  => 'message',
					'label' => __( 'Message', 'ava-contact-form' ),
					'value' => __( 'Ceci est un e-mail de test envoye depuis la page de reglages AVA Contact Form.', 'ava-contact-form' ),
				],
				[
					'name'  => 'date',
					'label' => __( 'Date du test', 'ava-contact-form' ),
					'value' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				],
			],
		];

		return $this->send_email(
			$recipient,
			[
				'subject' => sprintf(
					/* translators: %s: site name */
					__( 'Test SMTP - %s', 'ava-contact-form' ),
					wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
				),
				'fields' => $data['fields'],
				'meta'   => [
					'source'    => __( 'Test lance depuis la page de reglages', 'ava-contact-form' ),
					'timestamp' => current_time( 'mysql' ),
				],
			]
		);
	}

	/**
	 * Handle AJAX submission.
	 *
	 * @return void
	 */
	public function handle_form_submission() {
		check_ajax_referer( 'ava_contact_form_submit', 'nonce' );

		error_log( 'AVA CF: Submission started' );


		$payload = isset( $_POST['ava_cf_fields'] ) ? wp_unslash( $_POST['ava_cf_fields'] ) : '';
		$form_schema = $this->parse_fields_payload( $payload );

		error_log( 'AVA CF: Payload parsed. Fields count: ' . ( isset( $form_schema['fields'] ) ? count( $form_schema['fields'] ) : 0 ) );


		if ( empty( $form_schema['fields'] ) ) {
			wp_send_json_error(
				[
					'message' => __( 'La configuration du formulaire est invalide.', 'ava-contact-form' ),
				],
				400
			);
		}

		$fields     = $form_schema['fields'];
		$mode       = isset( $form_schema['mode'] ) ? $form_schema['mode'] : 'single';
		$steps      = isset( $form_schema['steps'] ) && is_array( $form_schema['steps'] ) ? $form_schema['steps'] : [];
		$form_label = isset( $form_schema['form_label'] ) ? $form_schema['form_label'] : '';

		if ( ! $form_label && isset( $_POST['ava_cf_form_label'] ) ) {
			$form_label = sanitize_text_field( wp_unslash( $_POST['ava_cf_form_label'] ) );
		}

		$values = [];

		foreach ( $fields as $field ) {
			$name      = $field['name'];
			$raw_value = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';

			$values[ $name ] = $this->sanitize_field_value( $field, $raw_value );
		}

		$errors = $this->validate_submission( $fields, $values );

		if ( ! empty( $errors ) ) {
			error_log( 'AVA CF: Validation errors: ' . print_r( $errors, true ) );
			wp_send_json_error(
				[
					'message' => implode( ' ', $errors ),
				],
				400
			);
		}

		$recipient = $this->admin->get_recipient_address();

		if ( empty( $recipient ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Configurez une adresse de destination avant d\'utiliser le formulaire.', 'ava-contact-form' ),
				],
				400
			);
		}

		$meta = [
			'form_id'    => isset( $_POST['ava_cf_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ava_cf_form_id'] ) ) : '',
			'page_title' => isset( $_POST['ava_cf_page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ava_cf_page_title'] ) ) : '',
			'page_url'   => isset( $_POST['ava_cf_page_url'] ) ? esc_url_raw( wp_unslash( $_POST['ava_cf_page_url'] ) ) : '',
			'referer'    => wp_get_referer(),
			'ip'         => $this->get_client_ip(),
			'timestamp'  => current_time( 'mysql' ),
			'form_mode'  => $mode,
			'form_steps' => $this->format_steps_for_meta( $steps ),
			'form_label' => $form_label,
		];

		$display_values = [];

		foreach ( $fields as $field ) {
			$name = $field['name'];
			$display_values[ $name ] = $this->format_field_value_for_output(
				$field,
				isset( $values[ $name ] ) ? $values[ $name ] : ''
			);
		}

		$email_fields = array_map(
			function ( $field ) use ( $display_values ) {
				$name  = $field['name'];
				$label = $field['label'] ? $field['label'] : ucfirst( $name );

				return [
					'label'    => $label,
					'value'    => isset( $display_values[ $name ] ) ? $display_values[ $name ] : '',
					'type'     => $field['type'],
					'name'     => $name,
					'multiple' => ! empty( $field['multiple'] ),
					'step'     => isset( $field['step'] ) ? $field['step'] : '',
				];
			},
			$fields
		);

		$subject_parts = array_filter(
			[
				$form_label ? sprintf( '[%s]', $form_label ) : '',
				__( 'Nouveau message envoye depuis le formulaire', 'ava-contact-form' ),
				$meta['page_title'],
			]
		);

		$subject = implode( ' - ', $subject_parts );

		error_log( 'AVA CF: Attempting to send email to: ' . $recipient );

		$email_sent = $this->send_email(
			$recipient,
			[
				'subject'  => $subject,
				'fields'   => $email_fields,
				'meta'     => $meta,
				'reply_to' => $this->extract_reply_to( $fields, $values ),
			]
		);

		if ( ! $email_sent ) {
			error_log( 'AVA CF: Email sending FAILED' );
			wp_send_json_error(
				[
					'message' => __( 'L\'envoi du message a echoue. Veuillez reessayer plus tard.', 'ava-contact-form' ),
				],
				500
			);
		}

		$this->submissions->store_submission(
			[
				'fields' => $fields,
				'values' => $values,
				'display_values' => $display_values,
				'meta'   => $meta,
			]
		);

		wp_send_json_success();
	}

	/**
	 * Validate submission data.
	 *
	 * @param array $fields Field definitions.
	 * @param array $values Submitted values.
	 * @return array
	 */
	private function validate_submission( $fields, $values ) {
		$errors = [];

		foreach ( $fields as $field ) {
			$name           = $field['name'];
			$label          = $field['label'] ? $field['label'] : ucfirst( $name );
			$value          = isset( $values[ $name ] ) ? $values[ $name ] : ( ! empty( $field['multiple'] ) ? [] : '' );
			$type           = $field['type'];
			$required       = (bool) $field['required'];
			$multiple       = ! empty( $field['multiple'] ) || in_array( $type, [ 'checkbox', 'multiselect' ], true );
			$options        = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
			$allowed_values = array_filter(
				array_map(
					'strval',
					wp_list_pluck( $options, 'value' )
				)
			);

			if ( $multiple ) {
				$value = is_array( $value ) ? $value : ( '' !== (string) $value ? [ $value ] : [] );
				$has_value = ! empty( array_filter( array_map( 'strlen', array_map( 'strval', $value ) ) ) );

				if ( $required && ! $has_value ) {
					$errors[] = sprintf(
						/* translators: %s: field label */
				__( 'Le champ %s est obligatoire.', 'ava-contact-form' ),
						$label
					);
					continue;
				}

				if ( $has_value && ! empty( $allowed_values ) ) {
					$diff = array_diff( array_map( 'strval', $value ), $allowed_values );
					if ( ! empty( $diff ) ) {
						$errors[] = sprintf(
							/* translators: %s: field label */
							__( 'Les options selectionnees pour %s ne sont pas valides.', 'ava-contact-form' ),
							$label
						);
					}
				}

				continue;
			}

			$string_value = is_array( $value ) ? '' : trim( (string) $value );

			if ( $required && '' === $string_value ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( 'Le champ %s est obligatoire.', 'ava-contact-form' ),
					$label
				);
				continue;
			}

			if ( '' === $string_value ) {
				continue;
			}

			switch ( $type ) {
				case 'email':
					if ( ! is_email( $string_value ) ) {
						$errors[] = sprintf(
							/* translators: %s: field label */
							__( 'Veuillez saisir une adresse e-mail valide pour le champ %s.', 'ava-contact-form' ),
							$label
						);
					}
					break;
				case 'url':
					if ( ! filter_var( $string_value, FILTER_VALIDATE_URL ) ) {
						$errors[] = sprintf(
							/* translators: %s: field label */
							__( 'Veuillez saisir une URL valide pour le champ %s.', 'ava-contact-form' ),
							$label
						);
					}
					break;
				case 'number':
					$normalized = str_replace( ',', '.', $string_value );
					if ( ! is_numeric( $normalized ) ) {
						$errors[] = sprintf(
							/* translators: %s: field label */
							__( 'Veuillez saisir une valeur numerique valide pour le champ %s.', 'ava-contact-form' ),
							$label
						);
					}
					break;
				case 'select':
				case 'radio':
					if ( ! empty( $allowed_values ) && ! in_array( $string_value, $allowed_values, true ) ) {
						$errors[] = sprintf(
							/* translators: %s: field label */
							__( 'L\'option selectionnee pour %s n\'est pas valide.', 'ava-contact-form' ),
							$label
						);
					}
					break;
				default:
					break;
			}
		}

		return $errors;
	}

	/**
	 * Send email using configured method.
	 *
	 * @param string $recipient Recipient list.
	 * @param array  $message   Message payload.
	 * @return bool
	 */
	private function send_email( $recipient, $message ) {
		$settings = $this->admin->get_mailer_settings();
		
		error_log( 'AVA CF: Mailer settings: ' . print_r( $settings, true ) );

		$subject = isset( $message['subject'] ) && $message['subject']
			? $message['subject']
			: sprintf(
				/* translators: %s: site name */
				__( 'Nouveau message sur le site %s', 'ava-contact-form' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			);

		$fields = isset( $message['fields'] ) && is_array( $message['fields'] ) ? $message['fields'] : [];
		$meta   = isset( $message['meta'] ) && is_array( $message['meta'] ) ? $message['meta'] : [];

		$body = isset( $message['body'] ) && $message['body']
			? $message['body']
			: $this->build_email_body( $fields, $meta );

		$reply_to = isset( $message['reply_to'] ) && is_array( $message['reply_to'] ) ? $message['reply_to'] : [];

		if ( 'smtp' === $settings['mailer'] ) {
			error_log( 'AVA CF: Sending via SMTP' );
			return $this->send_via_smtp( $recipient, $subject, $body, $reply_to, $settings['smtp'] );
		}

		error_log( 'AVA CF: Sending via WP Mail (Native)' );

		$headers   = [];
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		$from = $this->get_from_address();

		if ( $from['email'] ) {
			$headers[] = sprintf( 'From: %s <%s>', $from['name'], $from['email'] );
		}

		if ( isset( $reply_to['email'] ) && is_email( $reply_to['email'] ) ) {
			$name = isset( $reply_to['name'] ) && $reply_to['name'] ? $reply_to['name'] : $reply_to['email'];
			$headers[] = sprintf( 'Reply-To: %s <%s>', $name, $reply_to['email'] );
		}

		add_filter( 'wp_mail_content_type', [ $this, 'force_email_html' ] );
		
		// Log wp_mail failure details
		add_action( 'wp_mail_failed', function( $error ) {
			error_log( 'AVA CF: wp_mail_failed: ' . print_r( $error, true ) );
		} );

		$result = wp_mail( $recipient, $subject, $body, $headers );
		
		error_log( 'AVA CF: wp_mail result: ' . ( $result ? 'SUCCESS' : 'FAILURE' ) );
		
		remove_filter( 'wp_mail_content_type', [ $this, 'force_email_html' ] );

		return $result;
	}

	/**
	 * Ensure wp_mail sends HTML.
	 *
	 * @return string
	 */
	public function force_email_html() {
		return 'text/html';
	}

	/**
	 * Convert step definition into human readable meta string.
	 *
	 * @param array $steps Steps schema.
	 * @return string
	 */
	private function format_steps_for_meta( $steps ) {
		if ( empty( $steps ) || ! is_array( $steps ) ) {
			return '';
		}

		$titles = array_filter(
			array_map(
				function ( $step ) {
					if ( ! is_array( $step ) ) {
						return '';
					}

					return isset( $step['title'] ) ? sanitize_text_field( (string) $step['title'] ) : '';
				},
				$steps
			)
		);

		if ( empty( $titles ) ) {
			return '';
		}

		return implode( ' / ', array_unique( $titles ) );
	}

	/**
	 * Parse encoded fields payload.
	 *
	 * @param string $payload Base64 encoded JSON.
	 * @return array
	 */
	private function parse_fields_payload( $payload ) {
		$payload = (string) $payload;

		if ( '' === $payload ) {
			return [
				'mode'   => 'single',
				'fields' => [],
				'steps'  => [],
			];
		}

		$decoded = base64_decode( $payload, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscate_base64_decode

		if ( false === $decoded ) {
			return [
				'mode'   => 'single',
				'fields' => [],
				'steps'  => [],
			];
		}

		$data = json_decode( $decoded, true );

		if ( ! is_array( $data ) ) {
			return [
				'mode'   => 'single',
				'fields' => [],
				'steps'  => [],
			];
		}

		$mode = 'single';
		$form_label = '';

		if ( isset( $data['mode'] ) ) {
			$mode = 'multi' === $data['mode'] ? 'multi' : 'single';
		}

		$raw_steps = [];
		if ( isset( $data['steps'] ) && is_array( $data['steps'] ) ) {
			$raw_steps = $data['steps'];
		}

		$raw_fields = [];
		if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
			$raw_fields = $data['fields'];
		} elseif ( isset( $data[0] ) && is_array( $data[0] ) ) {
			// Backward compatibility with legacy payload.
			$raw_fields = $data;
		}

		if ( isset( $data['form_label'] ) ) {
			$form_label = sanitize_text_field( $data['form_label'] );
		}

		$steps = [];
		$step_titles_map = [];

		foreach ( $raw_steps as $index => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}

			$step_index = isset( $step['index'] ) ? (int) $step['index'] : $index;
			$title      = isset( $step['title'] ) ? sanitize_text_field( $step['title'] ) : '';

			if ( '' === $title ) {
				/* translators: %d: step number */
				$title = sprintf( __( 'Etape %d', 'ava-contact-form' ), $step_index + 1 );
			}

			$description = isset( $step['description'] ) ? sanitize_textarea_field( $step['description'] ) : '';

			$steps[] = [
				'index'       => $step_index,
				'title'       => $title,
				'description' => $description,
			];

			$step_titles_map[ $step_index ] = $title;
		}

		$allowed_types = [ 'text', 'email', 'tel', 'number', 'textarea', 'select', 'multiselect', 'checkbox', 'radio', 'date', 'time', 'datetime', 'url' ];
		$fields        = [];
		$used_names    = [];

		foreach ( $raw_fields as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( isset( $field['name'] ) ) {
				$raw_name     = $field['name'];
				$raw_label    = isset( $field['label'] ) ? $field['label'] : '';
				$raw_type     = isset( $field['type'] ) ? $field['type'] : 'text';
				$placeholder  = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				$required     = ! empty( $field['required'] );
				$step_index   = isset( $field['step_index'] ) ? $field['step_index'] : null;
				$step_title   = isset( $field['step'] ) ? $field['step'] : '';
			} else {
				$raw_name     = isset( $field['field_name'] ) ? $field['field_name'] : '';
				$raw_label    = isset( $field['field_label'] ) ? $field['field_label'] : '';
				$raw_type     = isset( $field['field_type'] ) ? $field['field_type'] : 'text';
				$placeholder  = isset( $field['field_placeholder'] ) ? $field['field_placeholder'] : '';
				$required     = isset( $field['field_required'] ) && ( 'yes' === $field['field_required'] || true === $field['field_required'] );
				$step_index   = null;
				$step_title   = '';
			}

			$name = strtolower( preg_replace( '/[^a-z0-9_]/', '_', (string) $raw_name ) );
			$name = $name ? $name : 'field_' . ( $index + 1 );

			$base_name = $name;
			$counter   = 1;

			while ( in_array( $name, $used_names, true ) ) {
				$name = $base_name . '_' . $counter;
				$counter++;
			}

			$used_names[] = $name;

			$type = strtolower( (string) $raw_type );

			if ( ! in_array( $type, $allowed_types, true ) ) {
				$type = 'text';
			}

			if ( null !== $step_index && '' === $step_title && isset( $step_titles_map[ (int) $step_index ] ) ) {
				$step_title = $step_titles_map[ (int) $step_index ];
			}

			$options = [];
			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				foreach ( $field['options'] as $option ) {
					if ( ! is_array( $option ) ) {
						continue;
					}
					$opt_value = isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '';
					$opt_label = isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : $opt_value;
					$opt_image = '';
					$opt_width = 0;

					if ( isset( $option['image'] ) ) {
						$opt_image = $this->extract_option_image_url( $option['image'] );
					} elseif ( isset( $option['option_image'] ) ) {
						$opt_image = $this->extract_option_image_url( $option['option_image'] );
					}

					if ( isset( $option['width'] ) ) {
						$opt_width = (int) $option['width'];
					} elseif ( isset( $option['option_width'] ) ) {
						$opt_width = (int) $option['option_width'];
					}

					if ( '' === $opt_value && '' === $opt_label ) {
						continue;
					}

					$options[] = [
						'value' => $opt_value ? $opt_value : sanitize_text_field( uniqid( 'opt_', true ) ),
						'label' => $opt_label ? $opt_label : $opt_value,
						'image' => $opt_image,
						'width' => $this->sanitize_option_width( $opt_width ),
					];
				}
			} elseif ( isset( $field['field_options_list'] ) && is_array( $field['field_options_list'] ) ) {
				$options = $this->parse_options_from_list( $field['field_options_list'] );
			} elseif ( isset( $field['field_options'] ) ) {
				$options = $this->parse_options_from_string( $field['field_options'] );
			}

			$multiple = isset( $field['multiple'] )
				? (bool) $field['multiple']
				: in_array( $type, [ 'checkbox', 'multiselect' ], true );

			if ( isset( $field['width'] ) ) {
				$width = (int) $field['width'];
			} elseif ( isset( $field['field_width'] ) ) {
				$width = (int) $field['field_width'];
			} else {
				$width = 4;
			}

			$width = max( 1, min( 4, $width ) );

			$fields[] = [
				'name'        => $name,
				'label'       => $raw_label ? sanitize_text_field( $raw_label ) : ucfirst( $name ),
				'type'        => $type,
				'placeholder' => sanitize_text_field( $placeholder ),
				'required'    => (bool) $required,
				'options'     => $options,
				'multiple'    => $multiple,
				'width'       => $width,
				'step'        => $step_title ? sanitize_text_field( $step_title ) : '',
				'step_index'  => null !== $step_index ? (int) $step_index : null,
			];
		}

		return [
			'mode'       => $mode,
			'fields'     => $fields,
			'steps'      => $steps,
			'form_label' => $form_label,
		];
	}

	/**
	 * Parse repeater-based options payload.
	 *
	 * @param array $raw_list Raw repeater data.
	 * @return array
	 */
	private function parse_options_from_list( $raw_list ) {
		if ( empty( $raw_list ) || ! is_array( $raw_list ) ) {
			return [];
		}

		$options = [];

		foreach ( $raw_list as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = isset( $item['option_label'] ) ? sanitize_text_field( $item['option_label'] ) : '';
			$value = isset( $item['option_value'] ) ? sanitize_text_field( $item['option_value'] ) : '';
			$image = '';
			$width = isset( $item['option_width'] ) ? (int) $item['option_width'] : 0;

			if ( '' === $value && '' !== $label ) {
				$value = sanitize_title( $label );
			}

			if ( '' === $label && '' !== $value ) {
				$label = $value;
			}

			if ( isset( $item['option_image'] ) ) {
				$image = $this->extract_option_image_url( $item['option_image'] );
			}

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$options[] = [
				'value' => $value ? $value : sanitize_text_field( uniqid( 'opt_', true ) ),
				'label' => $label ? $label : $value,
				'image' => $image,
				'width' => $this->sanitize_option_width( $width ),
			];
		}

		return $options;
	}

	/**
	 * Parse legacy textarea options payload.
	 *
	 * @param string $raw_options Raw option string.
	 * @return array
	 */
	private function parse_options_from_string( $raw_options ) {
		if ( empty( $raw_options ) ) {
			return [];
		}

		$lines = array_filter(
			array_map(
				'trim',
				preg_split( '/\r\n|\r|\n/', (string) $raw_options )
			)
		);

		$options = [];

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );

			$value = isset( $parts[0] ) ? $parts[0] : '';
			$label = isset( $parts[1] ) ? $parts[1] : '';
			$image = isset( $parts[2] ) ? $parts[2] : '';
			$width = isset( $parts[3] ) ? (int) $parts[3] : 0;

			if ( '' === $label && '' !== $value ) {
				$label = $value;
			}

			if ( '' === $value && '' !== $label ) {
				$value = sanitize_title( $label );
			}

			if ( '' === $value && '' === $label ) {
				continue;
			}

			$options[] = [
				'value' => sanitize_text_field( $value ? $value : uniqid( 'opt_', true ) ),
				'label' => sanitize_text_field( $label ? $label : $value ),
				'image' => $image ? esc_url_raw( $image ) : '',
				'width' => $this->sanitize_option_width( $width ),
			];
		}

		return $options;
	}

	/**
	 * Extract a usable image URL from Elementor media data.
	 *
	 * @param mixed $source Media data or URL.
	 * @return string
	 */
	private function extract_option_image_url( $source ) {
		if ( empty( $source ) ) {
			return '';
		}

		if ( is_array( $source ) ) {
			if ( isset( $source['id'] ) && $source['id'] ) {
				$attachment_url = wp_get_attachment_image_url( (int) $source['id'], 'large' );
				if ( $attachment_url ) {
					return esc_url_raw( $attachment_url );
				}
			}

			if ( isset( $source['url'] ) && $source['url'] ) {
				return esc_url_raw( $source['url'] );
			}
		}

		if ( is_string( $source ) ) {
			return esc_url_raw( $source );
		}

		return '';
	}

	/**
	 * Normalize option width span.
	 *
	 * @param int|string $width Width value.
	 * @return int
	 */
	private function sanitize_option_width( $width ) {
		$width = (int) $width;

		if ( $width < 1 || $width > 4 ) {
			return 0;
		}

		return $width;
	}

	/**
	 * Sanitize incoming value per field configuration.
	 *
	 * @param array $field     Field configuration.
	 * @param mixed $raw_value Raw submitted value.
	 * @return mixed
	 */
	private function sanitize_field_value( $field, $raw_value ) {
		$type          = isset( $field['type'] ) ? $field['type'] : 'text';
		$multiple      = ! empty( $field['multiple'] );
		$options       = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
		$allowed_values = array_filter(
			array_map(
				'strval',
				wp_list_pluck( $options, 'value' )
			)
		);

		if ( $multiple || in_array( $type, [ 'checkbox', 'multiselect' ], true ) ) {
			$raw_list = is_array( $raw_value ) ? $raw_value : ( '' !== (string) $raw_value ? [ $raw_value ] : [] );

			$sanitized = array_values(
				array_filter(
					array_map(
						'sanitize_text_field',
						array_map( 'strval', (array) $raw_list )
					),
					function ( $item ) use ( $allowed_values ) {
						return '' !== $item && ( empty( $allowed_values ) || in_array( $item, $allowed_values, true ) );
					}
				)
			);

			return $sanitized;
		}

		if ( is_array( $raw_value ) ) {
			$raw_value = reset( $raw_value );
		}

		$value = (string) $raw_value;

		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'number':
				$clean = preg_replace( '/[^0-9\.\,\-]/', '', $value );
				return trim( (string) $clean );
			case 'url':
				return esc_url_raw( $value );
			case 'select':
			case 'radio':
				$sanitized = sanitize_text_field( $value );
				if ( ! empty( $allowed_values ) && ! in_array( $sanitized, $allowed_values, true ) ) {
					return '';
				}
				return $sanitized;
			case 'date':
			case 'time':
			case 'datetime':
				return sanitize_text_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Convert stored value into human readable string for output.
	 *
	 * @param array      $field Field configuration.
	 * @param mixed      $value Sanitized value.
	 * @return string
	 */
	private function format_field_value_for_output( $field, $value ) {
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];

		$options_map = [];
		foreach ( $options as $option ) {
			if ( isset( $option['value'], $option['label'] ) ) {
				$options_map[ (string) $option['value'] ] = $option['label'];
			}
		}

		if ( is_array( $value ) ) {
			$labels = array_filter(
				array_map(
					function ( $item ) use ( $options_map ) {
						$item = (string) $item;
						if ( '' === $item ) {
							return '';
						}

						return isset( $options_map[ $item ] ) ? $options_map[ $item ] : $item;
					},
					$value
				)
			);

			if ( empty( $labels ) ) {
				return '';
			}

			if ( in_array( $type, [ 'checkbox', 'radio' ], true ) && empty( $options_map ) ) {
				return __( 'Oui', 'ava-contact-form' );
			}

			return implode( ', ', $labels );
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( isset( $options_map[ $value ] ) ) {
			return $options_map[ $value ];
		}

		if ( in_array( $type, [ 'checkbox', 'radio' ], true ) && empty( $options_map ) ) {
			return __( 'Oui', 'ava-contact-form' );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Extract reply-to information from submission.
	 *
	 * @param array $fields Field definitions.
	 * @param array $values Submitted values.
	 * @return array
	 */
	private function extract_reply_to( $fields, $values ) {
		$email = '';

		foreach ( $fields as $field ) {
			if ( 'email' !== $field['type'] ) {
				continue;
			}

			$name = $field['name'];
			$val  = isset( $values[ $name ] ) ? $values[ $name ] : '';

			if ( is_email( $val ) ) {
				$email = $val;
				break;
			}
		}

		if ( ! $email ) {
			return [];
		}

		$reply_name = '';

		foreach ( $fields as $field ) {
			if ( 'text' !== $field['type'] ) {
				continue;
			}

			$name = $field['name'];

			if ( ! empty( $values[ $name ] ) ) {
				$reply_name = $values[ $name ];
				break;
			}
		}

		if ( ! $reply_name ) {
			$reply_name = $email;
		}

		return [
			'email' => $email,
			'name'  => $reply_name,
		];
	}

	/**
	 * Build HTML email body.
	 *
	 * @param array $fields Field/value pairs.
	 * @param array $meta   Extra metadata.
	 * @return string
	 */
	private function build_email_body( $fields, $meta ) {
		$rows = '';
		$current_step = null;

		foreach ( $fields as $field ) {
			$label = isset( $field['label'] ) ? $field['label'] : '';
			$value = isset( $field['value'] ) ? $field['value'] : '';
			$step  = isset( $field['step'] ) ? $field['step'] : '';

			if ( '' === $label && '' === $value ) {
				continue;
			}

			if ( '' === trim( (string) $value ) ) {
				continue;
			}

			if ( $step && $step !== $current_step ) {
				$rows .= sprintf(
					'<tr><th colspan="2" style="text-align:left;padding:10px;border:1px solid #e1e1e1;background-color:#f5f7fa;font-size:13px;">%s</th></tr>',
					esc_html( $step )
				);
				$current_step = $step;
			} elseif ( ! $step ) {
				$current_step = null;
			}

			$rows .= sprintf(
				'<tr><th style="text-align:left;padding:8px;border:1px solid #e1e1e1;width:30%%;">%1$s</th><td style="padding:8px;border:1px solid #e1e1e1;">%2$s</td></tr>',
				esc_html( $label ),
				nl2br( esc_html( $value ) )
			);
		}

		if ( '' === $rows ) {
			$rows = sprintf(
				'<tr><td colspan="2" style="padding:8px;border:1px solid #e1e1e1;">%s</td></tr>',
				esc_html__( 'Aucun detail n\'a ete fourni.', 'ava-contact-form' )
			);
		}

$body  = '<h2 style="font-family:Arial,Helvetica,sans-serif;">' . esc_html__( 'Details du formulaire', 'ava-contact-form' ) . '</h2>';
		$body .= '<table style="width:100%;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:14px;margin-bottom:20px;">' . $rows . '</table>';

		$meta_rows   = '';
		$meta_labels = [
			'form_id'    => __( 'ID du formulaire', 'ava-contact-form' ),
			'page_title' => __( 'Page', 'ava-contact-form' ),
			'page_url'   => __( 'URL de la page', 'ava-contact-form' ),
			'referer'    => __( 'Referent', 'ava-contact-form' ),
			'ip'         => __( 'Adresse IP', 'ava-contact-form' ),
			'timestamp'  => __( 'Date d\'envoi', 'ava-contact-form' ),
			'source'     => __( 'Source', 'ava-contact-form' ),
			'form_mode'  => __( 'Type de formulaire', 'ava-contact-form' ),
			'form_steps' => __( 'Etapes', 'ava-contact-form' ),
		];

		foreach ( $meta_labels as $key => $label ) {
			if ( empty( $meta[ $key ] ) ) {
				continue;
			}

			$value = $meta[ $key ];

			if ( 'form_mode' === $key ) {
				$value = 'multi' === strtolower( (string) $value )
					? __( 'Multi-etapes', 'ava-contact-form' )
					: __( 'Une seule etape', 'ava-contact-form' );
			}

			if ( 'timestamp' === $key ) {
				$value = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $value ) );
			}

			if ( in_array( $key, [ 'page_url', 'referer' ], true ) ) {
				$url = esc_url( $value );

				if ( $url ) {
					$value = sprintf( '<a href="%1$s">%1$s</a>', $url );
				}
			} else {
				$value = esc_html( $value );
			}

			$meta_rows .= sprintf(
				'<tr><th style="text-align:left;padding:6px;border:1px solid #e1e1e1;width:30%%;">%1$s</th><td style="padding:6px;border:1px solid #e1e1e1;">%2$s</td></tr>',
				esc_html( $label ),
				$value
			);
		}

		if ( $meta_rows ) {
		$body .= '<h3 style="font-family:Arial,Helvetica,sans-serif;">' . esc_html__( 'Metadonnees du formulaire', 'ava-contact-form' ) . '</h3>';
			$body .= '<table style="width:100%;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;">' . $meta_rows . '</table>';
		}

		return $body;
	}

	/**
	 * Send email via SMTP.
	 *
	 * @param string $recipient Recipient string.
	 * @param string $subject   Subject.
	 * @param string $body      HTML body.
	 * @param array  $reply_to  Reply-to info.
	 * @param array  $smtp      SMTP settings.
	 * @return bool
	 */
	private function send_via_smtp( $recipient, $subject, $body, $reply_to, $smtp ) {
		$recipients = $this->parse_recipient_list( $recipient );

		if ( empty( $recipients ) ) {
			return false;
		}

		if ( empty( $smtp['host'] ) ) {
			return false;
		}

		if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$mailer = new \PHPMailer\PHPMailer\PHPMailer( true );

		try {
			$mailer->CharSet = 'UTF-8';
			$mailer->isSMTP();
			$mailer->Host = $smtp['host'];
			$mailer->Port = ! empty( $smtp['port'] ) ? (int) $smtp['port'] : 587;
			$mailer->SMTPAuth = ! empty( $smtp['username'] ) || ! empty( $smtp['password'] );

			if ( ! empty( $smtp['username'] ) ) {
				$mailer->Username = $smtp['username'];
			}

			if ( ! empty( $smtp['password'] ) ) {
				$mailer->Password = $smtp['password'];
			}

			if ( 'ssl' === $smtp['encryption'] ) {
				$mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
			} elseif ( 'tls' === $smtp['encryption'] ) {
				$mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			} else {
				$mailer->SMTPSecure = '';
			}

			$from = $this->get_from_address();

			if ( ! $from['email'] ) {
				return false;
			}

			$mailer->setFrom( $from['email'], $from['name'] );

			foreach ( $recipients as $email ) {
				$mailer->addAddress( $email );
			}

			if ( isset( $reply_to['email'] ) && is_email( $reply_to['email'] ) ) {
				$name = isset( $reply_to['name'] ) && $reply_to['name'] ? $reply_to['name'] : $reply_to['email'];
				$mailer->addReplyTo( $reply_to['email'], $name );
			}

			$mailer->isHTML( true );
			$mailer->Subject = $subject;
			$mailer->Body    = $body;

			return $mailer->send();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Build from address based on site domain.
	 *
	 * @return array
	 */
	private function get_from_address() {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		$domain = $domain ? preg_replace( '/^www\./', '', (string) $domain ) : '';

		$email = $domain ? 'no-reply@' . $domain : '';
		$email = sanitize_email( $email );

		if ( ! $email ) {
			$email = sanitize_email( get_option( 'admin_email' ) );
		}

		return [
			'email' => $email,
			'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		];
	}

	/**
	 * Parse recipient string into array.
	 *
	 * @param string $recipient Recipient list.
	 * @return array
	 */
	private function parse_recipient_list( $recipient ) {
		$parts = preg_split( '/[,;\r\n]+/', (string) $recipient );
		$parts = is_array( $parts ) ? $parts : [];

		$emails = array_filter(
			array_map(
				function ( $email ) {
					$email = sanitize_email( trim( (string) $email ) );
					return $email ? $email : null;
				},
				$parts
			)
		);

		return array_unique( $emails );
	}

	/**
	 * Attempt to detect client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$ip_list = explode( ',', (string) $_SERVER[ $key ] );

			foreach ( $ip_list as $ip ) {
				$ip = trim( $ip );

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Plugin activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		$plugin = self::instance();
		$plugin->submissions->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation cleanup.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}





