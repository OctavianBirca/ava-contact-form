<?php
namespace Ava_Contact_Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {
	const OPTION_RECIPIENT = 'ava_cf_recipient_email';
	const OPTION_MAILER    = 'ava_cf_mailer';
	const OPTION_SMTP      = 'ava_cf_smtp_settings';

	/**
	 * Register admin menus.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_menu_page(
			__( 'Formulaire de contact AVA', 'ava-contact-form' ),
			__( 'Formulaire de contact', 'ava-contact-form' ),
			'manage_options',
			'ava-contact-form',
			[ $this, 'render_settings_page' ],
			'dashicons-email-alt',
			58
		);

		add_submenu_page(
			'ava-contact-form',
			__( 'Paramètres du formulaire de contact', 'ava-contact-form' ),
			__( 'Réglages', 'ava-contact-form' ),
			'manage_options',
			'ava-contact-form',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'ava-contact-form',
			__( 'Tous les messages', 'ava-contact-form' ),
			__( 'Tous les messages', 'ava-contact-form' ),
			'manage_options',
			'edit.php?post_type=' . Submissions::POST_TYPE
		);

		add_submenu_page(
			'ava-contact-form',
			__( 'Checklist Devis', 'ava-contact-form' ),
			__( 'Checklist Devis', 'ava-contact-form' ),
			'manage_options',
			'ava-contact-form-checklist',
			[ $this, 'render_devis_page' ]
		);
	}

	/**
	 * Register plugin options.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ava_contact_form_settings',
			self::OPTION_RECIPIENT,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_recipient' ],
				'default'           => '',
			]
		);

		add_settings_section(
			'ava_contact_form_email_section',
			__( 'Destinataires des messages', 'ava-contact-form' ),
			function () {
				echo '<p>' . esc_html__( 'Saisissez une ou plusieurs adresses e-mail (séparées par des virgules) qui recevront les notifications du formulaire.', 'ava-contact-form' ) . '</p>';
			},
			'ava-contact-form'
		);

		add_settings_field(
			self::OPTION_RECIPIENT,
			__( 'Adresse e-mail destinataire', 'ava-contact-form' ),
			[ $this, 'render_recipient_field' ],
			'ava-contact-form',
			'ava_contact_form_email_section'
		);

		register_setting(
			'ava_contact_form_settings',
			self::OPTION_MAILER,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_mailer' ],
				'default'           => 'php',
			]
		);

		register_setting(
			'ava_contact_form_settings',
			self::OPTION_SMTP,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_smtp_settings' ],
				'default'           => [],
			]
		);

		add_settings_section(
			'ava_contact_form_mailer_section',
			__( 'Méthode d\'envoi', 'ava-contact-form' ),
			function () {
				echo '<p>' . esc_html__( 'Choisissez le mode d\'envoi des e-mails et configurez le serveur SMTP si nécessaire.', 'ava-contact-form' ) . '</p>';
			},
			'ava-contact-form'
		);

		add_settings_field(
			self::OPTION_MAILER,
			__( 'Type d\'envoi', 'ava-contact-form' ),
			[ $this, 'render_mailer_field' ],
			'ava-contact-form',
			'ava_contact_form_mailer_section'
		);

		add_settings_field(
			self::OPTION_SMTP,
			__( 'Paramètres SMTP', 'ava-contact-form' ),
			[ $this, 'render_smtp_fields' ],
			'ava-contact-form',
			'ava_contact_form_mailer_section'
		);
	}

	/**
	 * Render admin settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Formulaire de contact AVA', 'ava-contact-form' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ava_contact_form_settings' );
				do_settings_sections( 'ava-contact-form' );
				submit_button( __( 'Enregistrer les modifications', 'ava-contact-form' ) );
				?>
				<button type="button" class="button button-secondary" id="ava-cf-test-email">
					<?php esc_html_e( 'Envoyer un e-mail de test', 'ava-contact-form' ); ?>
				</button>
				<p class="description" id="ava-cf-test-email-result" aria-live="polite"></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Print recipient input field.
	 *
	 * @return void
	 */
	public function render_recipient_field() {
		$value = get_option( self::OPTION_RECIPIENT, '' );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_RECIPIENT ); ?>"
			id="<?php echo esc_attr( self::OPTION_RECIPIENT ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="contact@example.com, sales@example.com"
		/>
		<p class="description"><?php esc_html_e( 'Les e-mails seront envoyés via la fonction PHP mail().', 'ava-contact-form' ); ?></p>
		<?php
	}

	/**
	 * Sanitize recipient option.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_recipient( $value ) {
		$emails = array_filter(
			array_map(
				'trim',
				preg_split( '/[,;\s]+/', (string) $value )
			)
		);

		$valid_emails = array_filter(
			$emails,
			function ( $email ) {
				return is_email( $email );
			}
		);

		return implode( ', ', $valid_emails );
	}

	/**
	 * Retrieve stored recipient list.
	 *
	 * @return string
	 */
	public function get_recipient_address() {
		return get_option( self::OPTION_RECIPIENT, '' );
	}

	/**
	 * Get mailer configuration array.
	 *
	 * @return array
	 */
	public function get_mailer_settings() {
		$mailer = get_option( self::OPTION_MAILER, 'php' );
		$mailer = in_array( $mailer, [ 'php', 'smtp' ], true ) ? $mailer : 'php';

		$raw_smtp = get_option( self::OPTION_SMTP, [] );
		$raw_smtp = is_array( $raw_smtp ) ? $raw_smtp : [];

		$smtp = [
			'host'       => isset( $raw_smtp['host'] ) ? sanitize_text_field( $raw_smtp['host'] ) : '',
			'port'       => isset( $raw_smtp['port'] ) ? absint( $raw_smtp['port'] ) : 0,
			'encryption' => isset( $raw_smtp['encryption'] ) ? sanitize_text_field( $raw_smtp['encryption'] ) : 'none',
			'username'   => isset( $raw_smtp['username'] ) ? sanitize_text_field( $raw_smtp['username'] ) : '',
			'password'   => isset( $raw_smtp['password'] ) ? $this->decrypt_value( $raw_smtp['password'] ) : '',
		];

		if ( ! in_array( $smtp['encryption'], [ 'none', 'ssl', 'tls' ], true ) ) {
			$smtp['encryption'] = 'none';
		}

		return [
			'mailer' => $mailer,
			'smtp'   => $smtp,
		];
	}

	/**
	 * Render select field for mailer type.
	 *
	 * @return void
	 */
	public function render_mailer_field() {
		$current = get_option( self::OPTION_MAILER, 'php' );
		?>
		<select name="<?php echo esc_attr( self::OPTION_MAILER ); ?>" id="<?php echo esc_attr( self::OPTION_MAILER ); ?>">
			<option value="php" <?php selected( $current, 'php' ); ?>><?php esc_html_e( 'PHP mail() implicite', 'ava-contact-form' ); ?></option>
			<option value="smtp" <?php selected( $current, 'smtp' ); ?>><?php esc_html_e( 'Serveur SMTP personnalisé', 'ava-contact-form' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Pour une délivrabilité fiable, SMTP est recommandé.', 'ava-contact-form' ); ?></p>
		<?php
	}

	/**
	 * Render SMTP fields.
	 *
	 * @return void
	 */
	public function render_smtp_fields() {
		$values     = get_option( self::OPTION_SMTP, [] );
		$host       = isset( $values['host'] ) ? $values['host'] : '';
		$port       = isset( $values['port'] ) ? (int) $values['port'] : 587;
		$encryption = isset( $values['encryption'] ) ? $values['encryption'] : 'tls';
		$username   = isset( $values['username'] ) ? $values['username'] : '';
		$password   = isset( $values['password'] ) && $values['password'] ? '********' : '';
		?>
		<fieldset id="ava-cf-smtp-settings">
			<label for="ava-cf-smtp-host"><?php esc_html_e( 'Host SMTP', 'ava-contact-form' ); ?></label>
			<input type="text" id="ava-cf-smtp-host" name="<?php echo esc_attr( self::OPTION_SMTP ); ?>[host]" value="<?php echo esc_attr( $host ); ?>" class="regular-text" />

			<p>
				<label for="ava-cf-smtp-port"><?php esc_html_e( 'Port', 'ava-contact-form' ); ?></label>
				<input type="number" id="ava-cf-smtp-port" name="<?php echo esc_attr( self::OPTION_SMTP ); ?>[port]" value="<?php echo esc_attr( $port ); ?>" class="small-text" min="1" />
			</p>

			<p>
				<label for="ava-cf-smtp-encryption"><?php esc_html_e( 'Chiffrement', 'ava-contact-form' ); ?></label>
				<select id="ava-cf-smtp-encryption" name="<?php echo esc_attr( self::OPTION_SMTP ); ?>[encryption]">
					<option value="none" <?php selected( $encryption, 'none' ); ?>><?php esc_html_e( 'Aucun', 'ava-contact-form' ); ?></option>
					<option value="ssl" <?php selected( $encryption, 'ssl' ); ?>>SSL</option>
					<option value="tls" <?php selected( $encryption, 'tls' ); ?>>TLS</option>
				</select>
			</p>

			<p>
				<label for="ava-cf-smtp-username"><?php esc_html_e( 'Utilisateur', 'ava-contact-form' ); ?></label>
				<input type="text" id="ava-cf-smtp-username" name="<?php echo esc_attr( self::OPTION_SMTP ); ?>[username]" value="<?php echo esc_attr( $username ); ?>" class="regular-text" autocomplete="off" />
			</p>

			<p>
				<label for="ava-cf-smtp-password"><?php esc_html_e( 'Mot de passe', 'ava-contact-form' ); ?></label>
				<input type="password" id="ava-cf-smtp-password" name="<?php echo esc_attr( self::OPTION_SMTP ); ?>[password]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr( $password ); ?>" />
				<span class="description"><?php esc_html_e( 'Laissez vide pour conserver le mot de passe existant. Le mot de passe est stocké de manière chiffrée.', 'ava-contact-form' ); ?></span>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize mailer option.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_mailer( $value ) {
		return in_array( $value, [ 'php', 'smtp' ], true ) ? $value : 'php';
	}

	/**
	 * Sanitize SMTP option array.
	 *
	 * @param array $values Raw values.
	 * @return array
	 */
	public function sanitize_smtp_settings( $values ) {
		$values = is_array( $values ) ? $values : [];

		$host       = isset( $values['host'] ) ? sanitize_text_field( $values['host'] ) : '';
		$port       = isset( $values['port'] ) ? absint( $values['port'] ) : 0;
		$encryption = isset( $values['encryption'] ) ? sanitize_text_field( $values['encryption'] ) : 'none';
		$username   = isset( $values['username'] ) ? sanitize_text_field( $values['username'] ) : '';
		$password   = isset( $values['password'] ) ? (string) $values['password'] : '';

		if ( ! in_array( $encryption, [ 'none', 'ssl', 'tls' ], true ) ) {
			$encryption = 'none';
		}

		$stored = get_option( self::OPTION_SMTP, [] );
		$stored = is_array( $stored ) ? $stored : [];

		if ( '' === $password && isset( $stored['password'] ) ) {
			$password = $stored['password'];
		} else {
			$password = $password ? $this->encrypt_value( $password ) : '';
		}

		return [
			'host'       => $host,
			'port'       => $port,
			'encryption' => $encryption,
			'username'   => $username,
			'password'   => $password,
		];
	}

	/**
	 * Encrypt confidential value.
	 *
	 * @param string $value Plain password.
	 * @return string
	 */
	private function encrypt_value( $value ) {
		$value = (string) $value;

		if ( '' === $value ) {
			return '';
		}

		if ( function_exists( 'openssl_encrypt' ) ) {
			$encrypted = openssl_encrypt(
				$value,
				'AES-256-CBC',
				$this->get_crypto_key(),
				0,
				$this->get_crypto_iv()
			);

			if ( false !== $encrypted ) {
				return 'enc:' . base64_encode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscate_base64_encode
			}
		}

		return 'plain:' . base64_encode( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscate_base64_encode
	}

	/**
	 * Decrypt stored value.
	 *
	 * @param string $value Stored string.
	 * @return string
	 */
	private function decrypt_value( $value ) {
		$value = (string) $value;

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, 'enc:' ) ) {
			$encoded = substr( $value, 4 );
			$decoded = base64_decode( $encoded, true );

			if ( false === $decoded ) {
				return '';
			}

			if ( function_exists( 'openssl_decrypt' ) ) {
				$decrypted = openssl_decrypt(
					$decoded,
					'AES-256-CBC',
					$this->get_crypto_key(),
					0,
					$this->get_crypto_iv()
				);

				return false !== $decrypted ? (string) $decrypted : '';
			}

			return '';
		}

		if ( 0 === strpos( $value, 'plain:' ) ) {
			$encoded = substr( $value, 6 );
			$decoded = base64_decode( $encoded, true );

			return false !== $decoded ? (string) $decoded : '';
		}

		return '';
	}

	/**
	 * Derive encryption key.
	 *
	 * @return string
	 */
	private function get_crypto_key() {
		return hash( 'sha256', wp_salt( 'auth' ), true );
	}

	/**
	 * Derive encryption IV.
	 *
	 * @return string
	 */
	private function get_crypto_iv() {
		return substr( hash( 'sha256', 'ava_cf_iv', true ), 0, 16 );
	}

	/**
	 * Render checklist helper page.
	 *
	 * @return void
	 */
	public function render_devis_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$questions = [
			__( 'Quelles adresses de notification sont nécessaires au départ ? Sont-elles segmentées par région ?', 'ava-contact-form' ),
			__( 'Le client souhaite-t-il envoyer un e-mail de confirmation au demandeur ? Si oui, quel texte utiliser ?', 'ava-contact-form' ),
			__( 'Avons-nous besoin d’intégrations supplémentaires (CRM, export JSON, API) ?', 'ava-contact-form' ),
			__( 'Quelles images ou ressources visuelles doivent être importées pour le widget Elementor ?', 'ava-contact-form' ),
			__( 'Existe-t-il des validations supplémentaires (services autorisés, seuils budgétaires, etc.) ?', 'ava-contact-form' ),
			__( 'Quels journaux doivent être conservés et pendant combien de temps ?', 'ava-contact-form' ),
			__( 'Quel est le plan d’action si l’envoi des e-mails échoue ?', 'ava-contact-form' ),
		];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Checklist Devis AVA', 'ava-contact-form' ); ?></h1>
			<p><?php esc_html_e( 'Utilisez les questions ci-dessous lorsque vous discutez des demandes de devis avec les clients.', 'ava-contact-form' ); ?></p>
			<ol>
				<?php foreach ( $questions as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ol>
			<p>
				<?php
				printf(
					'%s <code>%s</code>',
					esc_html__( 'La documentation complète est disponible dans le fichier', 'ava-contact-form' ),
					esc_html( 'AVA_Devis_V3_prompt.md' )
				);
				?>
			</p>
		</div>
		<?php
	}
}
