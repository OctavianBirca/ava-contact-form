<?php
namespace Ava_Contact_Form;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget extends Widget_Base {
	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ava_contact_form';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Formulaire de contact AVA', 'ava-contact-form' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'ava-widgets', 'general' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Options du formulaire', 'ava-contact-form' ),
			]
		);

		$this->add_control(
			'form_mode',
			[
				'label'   => __( 'Type de formulaire', 'ava-contact-form' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'single',
				'options' => [
					'single' => __( 'Une seule etape', 'ava-contact-form' ),
					'multi'  => __( 'Formulaire multi-etapes', 'ava-contact-form' ),
				],
			]
		);

		$this->add_control(
			'form_label',
			[
				'label'       => __( 'Nom du formulaire', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Formulaire de contact', 'ava-contact-form' ),
				'description' => __( 'Utilise pour identifier l\'entree dans l\'administration.', 'ava-contact-form' ),
				'label_block' => true,
			]
		);

		$field_repeater = $this->create_field_repeater();

		$this->add_control(
			'fields',
			[
				'label'       => __( 'Champs du formulaire', 'ava-contact-form' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $field_repeater->get_controls(),
				'default'     => [
					[
						'field_label'      => __( 'Nom', 'ava-contact-form' ),
						'field_name'       => 'nom',
						'field_type'       => 'text',
						'field_placeholder'=> __( 'Nom complet*', 'ava-contact-form' ),
						'field_required'   => 'yes',
					],
					[
						'field_label'      => __( 'Email', 'ava-contact-form' ),
						'field_name'       => 'email',
						'field_type'       => 'email',
						'field_placeholder'=> __( 'E-mail*', 'ava-contact-form' ),
						'field_required'   => 'yes',
					],
					[
						'field_label'      => __( 'Telephone', 'ava-contact-form' ),
						'field_name'       => 'telephone',
						'field_type'       => 'tel',
						'field_placeholder'=> __( 'Telephone', 'ava-contact-form' ),
						'field_required'   => '',
					],
					[
						'field_label'      => __( 'Message', 'ava-contact-form' ),
						'field_name'       => 'message',
						'field_type'       => 'textarea',
						'field_placeholder'=> __( 'Votre message*', 'ava-contact-form' ),
						'field_required'   => 'yes',
					],
				],
				'title_field' => '{{{ field_label }}}',
				'condition'   => [
					'form_mode' => 'single',
				],
			]
		);

		$step_field_repeater = $this->create_field_repeater();

		$step_repeater = new Repeater();

		$step_repeater->add_control(
			'step_title',
			[
				'label'       => __( 'Titre de l\'etape', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Informations de contact', 'ava-contact-form' ),
				'label_block' => true,
			]
		);

		$step_repeater->add_control(
			'step_description',
			[
				'label'       => __( 'Description de l\'etape', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'label_block' => true,
			]
		);

		$step_repeater->add_control(
			'step_fields',
			[
				'label'       => __( 'Champs de l\'etape', 'ava-contact-form' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $step_field_repeater->get_controls(),
				'prevent_empty' => false,
				'title_field' => '{{{ field_label }}}',
			]
		);

		$this->add_control(
			'steps',
			[
				'label'       => __( 'Etapes du formulaire', 'ava-contact-form' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $step_repeater->get_controls(),
				'title_field' => '{{{ step_title }}}',
				'default'     => [
					[
						'step_title'       => __( 'Informations de contact', 'ava-contact-form' ),
						'step_description' => '',
						'step_fields'      => [
							[
								'field_label'      => __( 'Nom', 'ava-contact-form' ),
								'field_name'       => 'nom',
								'field_type'       => 'text',
								'field_placeholder'=> __( 'Nom complet*', 'ava-contact-form' ),
								'field_required'   => 'yes',
							],
							[
								'field_label'      => __( 'Email', 'ava-contact-form' ),
								'field_name'       => 'email',
								'field_type'       => 'email',
								'field_placeholder'=> __( 'E-mail*', 'ava-contact-form' ),
								'field_required'   => 'yes',
							],
						],
					],
					[
						'step_title'       => __( 'Details du message', 'ava-contact-form' ),
						'step_description' => '',
						'step_fields'      => [
							[
								'field_label'      => __( 'Telephone', 'ava-contact-form' ),
								'field_name'       => 'telephone',
								'field_type'       => 'tel',
								'field_placeholder'=> __( 'Telephone', 'ava-contact-form' ),
								'field_required'   => '',
							],
							[
								'field_label'      => __( 'Message', 'ava-contact-form' ),
								'field_name'       => 'message',
								'field_type'       => 'textarea',
								'field_placeholder'=> __( 'Votre message*', 'ava-contact-form' ),
								'field_required'   => 'yes',
							],
						],
					],
				],
				'condition'   => [
					'form_mode' => 'multi',
				],
			]
		);

		$this->add_control(
			'success_message',
			[
				'label'       => __( 'Message de succes', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Merci pour votre message ! Nous revenons vers vous tres vite.', 'ava-contact-form' ),
				'placeholder' => __( 'Texte affiche lorsque l\'envoi est reussi.', 'ava-contact-form' ),
			]
		);

		$this->add_control(
			'error_message',
			[
				'label'       => __( 'Message d\'erreur', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Une erreur est survenue. Merci de reessayer.', 'ava-contact-form' ),
				'placeholder' => __( 'Texte affiche lorsque l\'envoi echoue.', 'ava-contact-form' ),
			]
		);

		$this->add_control(
			'button_label',
			[
				'label'       => __( 'Texte du bouton', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Envoyer', 'ava-contact-form' ),
				'placeholder' => __( 'Envoyer', 'ava-contact-form' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			[
				'label' => __( 'Bouton d\'envoi', 'ava-contact-form' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Couleur du texte', 'ava-contact-form' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ava-contact-form__submit' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Couleur de fond', 'ava-contact-form' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ava-contact-form__submit' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Espacement interne', 'ava-contact-form' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .ava-contact-form__submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => __( 'Rayon des bordures', 'ava-contact-form' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .ava-contact-form__submit' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'ava-contact-form' );
		wp_enqueue_script( 'ava-contact-form' );
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->enqueue_assets();

		$success_message = isset( $settings['success_message'] ) ? $settings['success_message'] : '';
		$error_message   = isset( $settings['error_message'] ) ? $settings['error_message'] : '';
		$button_label    = isset( $settings['button_label'] ) ? $settings['button_label'] : __( 'Envoyer', 'ava-contact-form' );
		$form_label      = isset( $settings['form_label'] ) ? sanitize_text_field( $settings['form_label'] ) : '';
		if ( ! $form_label ) {
			$form_label = __( 'Formulaire de contact', 'ava-contact-form' );
		}

		$form_id = 'ava-contact-form-' . $this->get_id();
		$mode    = isset( $settings['form_mode'] ) && 'multi' === $settings['form_mode'] ? 'multi' : 'single';

		if ( 'multi' === $mode ) {
			$steps_setting    = isset( $settings['steps'] ) && is_array( $settings['steps'] ) ? $settings['steps'] : [];
			$steps_structure  = $this->prepare_steps( $steps_setting );
			$structured_steps = $steps_structure['steps'];
			$prepared_fields  = $steps_structure['fields'];
		} else {
			$fields          = isset( $settings['fields'] ) && is_array( $settings['fields'] ) ? $settings['fields'] : [];
			$prepared_fields = $this->prepare_fields( $fields );
			$structured_steps = [];
		}

		if ( empty( $prepared_fields ) ) {
			return;
		}

		$payload = [
			'mode'        => $mode,
			'fields'      => $prepared_fields,
			'form_label'  => $form_label,
		];

		if ( 'multi' === $mode ) {
			$payload['steps'] = array_map(
				function ( $step ) {
					return [
						'title'       => $step['title'],
						'description' => $step['description'],
						'index'       => $step['index'],
					];
				},
				$structured_steps
			);
		}

		$encoded_payload = base64_encode( wp_json_encode( $payload ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscate_base64_encode
		$page_id         = get_the_ID();
		$page_title      = get_the_title( $page_id );
		$page_url        = get_permalink( $page_id );
		$total_steps     = 'multi' === $mode ? max( 1, count( $structured_steps ) ) : 1;
		$field_counter   = 0;
		?>
		<div
			class="ava-contact-form"
			id="<?php echo esc_attr( $form_id ); ?>"
			data-success="<?php echo esc_attr( $success_message ); ?>"
			data-error="<?php echo esc_attr( $error_message ); ?>"
			data-mode="<?php echo esc_attr( $mode ); ?>"
			data-total-steps="<?php echo esc_attr( $total_steps ); ?>"
		>
			<div class="ava-contact-form__feedback" aria-live="polite" role="status"></div>

			<?php if ( 'multi' === $mode && ! empty( $structured_steps ) ) : ?>
				<div class="ava-contact-form__progress" role="tablist" aria-label="<?php esc_attr_e( 'Etapes du formulaire', 'ava-contact-form' ); ?>">
					<?php foreach ( $structured_steps as $index => $step ) : ?>
						<div class="ava-contact-form__progress-item">
							<div class="ava-contact-form__step-indicator<?php echo 0 === $index ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( $index ); ?>" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
								<span class="ava-contact-form__step-number"><?php echo esc_html( $index + 1 ); ?></span>
							</div>
						</div>
						<?php if ( $index < ( count( $structured_steps ) - 1 ) ) : ?>
							<div class="ava-contact-form__progress-line" aria-hidden="true"></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form class="ava-contact-form__form">
				<?php wp_nonce_field( 'ava_contact_form_submit', 'nonce' ); ?>

				<?php if ( 'multi' === $mode && ! empty( $structured_steps ) ) : ?>
					<div class="ava-contact-form__steps-wrapper">
						<div class="ava-contact-form__steps-track">
							<?php foreach ( $structured_steps as $index => $step ) : ?>
								<div class="ava-contact-form__step<?php echo 0 === $index ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( $index ); ?>">
									<?php if ( $step['title'] ) : ?>
										<h2 class="ava-contact-form__step-heading"><?php echo esc_html( $step['title'] ); ?></h2>
									<?php endif; ?>

									<?php if ( $step['description'] ) : ?>
										<p class="ava-contact-form__step-subtitle"><?php echo esc_html( $step['description'] ); ?></p>
									<?php endif; ?>

									<?php foreach ( $step['fields'] as $field ) : ?>
										<?php echo $this->render_field_block( $form_id, $field, $field_counter ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endforeach; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ava-contact-form__actions">
						<button type="button" class="ava-contact-form__button ava-contact-form__button--ghost ava-contact-form__prev is-hidden" data-action="prev">
							<?php esc_html_e( 'Retour', 'ava-contact-form' ); ?>
						</button>
						<button type="button" class="ava-contact-form__button ava-contact-form__button--accent ava-contact-form__next" data-action="next">
							<?php esc_html_e( 'Suivant', 'ava-contact-form' ); ?>
						</button>
						<button type="submit" class="ava-contact-form__submit ava-contact-form__button is-hidden" data-action="submit">
							<?php echo esc_html( $button_label ); ?>
						</button>
					</div>
				<?php else : ?>
					<?php foreach ( $prepared_fields as $field ) : ?>
						<?php echo $this->render_field_block( $form_id, $field, $field_counter ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
					<button type="submit" class="ava-contact-form__submit ava-contact-form__button"><?php echo esc_html( $button_label ); ?></button>
				<?php endif; ?>

				<input type="hidden" name="ava_cf_fields" value="<?php echo esc_attr( $encoded_payload ); ?>" />
				<input type="hidden" name="ava_cf_form_label" value="<?php echo esc_attr( $form_label ); ?>" />
				<input type="hidden" name="ava_cf_form_id" value="<?php echo esc_attr( $form_id ); ?>" />
				<input type="hidden" name="ava_cf_page_title" value="<?php echo esc_attr( $page_title ); ?>" />
				<input type="hidden" name="ava_cf_page_url" value="<?php echo esc_url( $page_url ); ?>" />
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize and prepare fields for output.
	 *
	 * @param array $fields Raw fields from Elementor.
	 * @return array
	 */
	private function create_field_repeater() {
		$repeater = new Repeater();

		$repeater->add_control(
			'field_label',
			[
				'label'       => __( 'Libelle', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Nom', 'ava-contact-form' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'field_name',
			[
				'label'       => __( 'Cle du champ', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'nume',
				'description' => __( 'Utilisee pour l\'identification interne. Lettres minuscules et underscores uniquement.', 'ava-contact-form' ),
			]
		);

		$repeater->add_control(
			'field_type',
			[
				'label'   => __( 'Type de champ', 'ava-contact-form' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text'        => __( 'Texte simple', 'ava-contact-form' ),
					'email'       => __( 'Email', 'ava-contact-form' ),
					'tel'         => __( 'Telephone', 'ava-contact-form' ),
					'number'      => __( 'Nombre', 'ava-contact-form' ),
					'textarea'    => __( 'Zone de texte', 'ava-contact-form' ),
					'select'      => __( 'Liste deroulante', 'ava-contact-form' ),
					'multiselect' => __( 'Liste multiple', 'ava-contact-form' ),
					'checkbox'    => __( 'Groupe de cases a cocher', 'ava-contact-form' ),
					'radio'       => __( 'Bouton radio', 'ava-contact-form' ),
					'date'        => __( 'Date', 'ava-contact-form' ),
					'time'        => __( 'Heure', 'ava-contact-form' ),
					'datetime'    => __( 'Date & heure', 'ava-contact-form' ),
					'url'         => __( 'URL', 'ava-contact-form' ),
				],
			]
		);

		$repeater->add_control(
			'field_width',
			[
				'label'   => __( 'Largeur des colonnes', 'ava-contact-form' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [
					'1' => __( '33 % (1 / 3)', 'ava-contact-form' ),
					'2' => __( '50 % (1 / 2)', 'ava-contact-form' ),
					'3' => __( '66 % (2 / 3)', 'ava-contact-form' ),
					'4' => __( '100 %', 'ava-contact-form' ),
				],
			]
		);

		$repeater->add_control(
			'field_layout_width',
			[
				'label'   => __( 'Largeur du bloc', 'ava-contact-form' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'        => __( 'Automatique (responsive)', 'ava-contact-form' ),
					'full'        => __( '100 %', 'ava-contact-form' ),
					'two_thirds'  => __( '66 %', 'ava-contact-form' ),
					'half'        => __( '50 %', 'ava-contact-form' ),
					'third'       => __( '33 %', 'ava-contact-form' ),
				],
			]
		);

		$repeater->add_control(
			'field_placeholder',
			[
				'label'       => __( 'Texte indicatif', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'condition'   => [
					'field_type!' => [ 'checkbox', 'radio' ],
				],
			]
		);

		$option_repeater = new Repeater();

		$option_repeater->add_control(
			'option_label',
			[
				'label'       => __( 'Libelle de l\'option', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			]
		);

		$option_repeater->add_control(
			'option_value',
			[
				'label'       => __( 'Valeur de l\'option', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'description' => __( 'Si ce champ est vide, une valeur est generee automatiquement a partir du libelle.', 'ava-contact-form' ),
			]
		);

		$option_repeater->add_control(
			'option_image',
			[
				'label'       => __( 'Image / Icone', 'ava-contact-form' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$option_repeater->add_control(
			'option_width',
			[
				'label'   => __( 'Largeur de l\'option', 'ava-contact-form' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''  => __( 'Heriter du champ', 'ava-contact-form' ),
					'1' => __( '33 % (1 / 3)', 'ava-contact-form' ),
					'2' => __( '50 % (1 / 2)', 'ava-contact-form' ),
					'3' => __( '66 % (2 / 3)', 'ava-contact-form' ),
					'4' => __( '100 %', 'ava-contact-form' ),
				],
			]
		);

		$repeater->add_control(
			'field_options_list',
			[
				'label'       => __( 'Options (avance)', 'ava-contact-form' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $option_repeater->get_controls(),
				'title_field' => '{{{ option_label || option_value || "' . esc_js( __( 'Option', 'ava-contact-form' ) ) . '" }}}',
				'prevent_empty' => false,
				'description' => __( 'Configurez chaque option individuellement, y compris les images affichees sur les cartes.', 'ava-contact-form' ),
				'condition'   => [
					'field_type' => [ 'select', 'multiselect', 'checkbox', 'radio' ],
				],
			]
		);

		$repeater->add_control(
			'field_options',
			[
				'label'       => __( 'Options', 'ava-contact-form' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'description' => __( 'Une option par ligne. Format pris en charge : valeur|Libelle affiche|URL de l\'image|Largeur (1-4) – les deux derniers champs sont facultatifs.', 'ava-contact-form' ),
				'condition'   => [
					'field_type' => [ 'select', 'multiselect', 'checkbox', 'radio' ],
				],
			]
		);

		$repeater->add_control(
			'field_required',
			[
				'label'        => __( 'Obligatoire', 'ava-contact-form' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'ava-contact-form' ),
				'label_off'    => __( 'Non', 'ava-contact-form' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		return $repeater;
	}

	/**
	 * Prepare steps configuration for multi-step forms.
	 *
	 * @param array $steps Raw step definitions.
	 * @return array
	 */
	private function prepare_steps( $steps ) {
		$prepared_steps = [];
		$all_fields     = [];
		$used_names     = [];

		foreach ( $steps as $index => $step ) {
			$raw_fields = isset( $step['step_fields'] ) && is_array( $step['step_fields'] ) ? $step['step_fields'] : [];
			$title       = isset( $step['step_title'] ) ? sanitize_text_field( $step['step_title'] ) : '';
			$description = isset( $step['step_description'] ) ? sanitize_textarea_field( $step['step_description'] ) : '';

			if ( '' === $title ) {
				/* translators: %d: step number */
				$title = sprintf( __( 'Etape %d', 'ava-contact-form' ), $index + 1 );
			}

			$fields = $this->prepare_fields( $raw_fields, $used_names, $title, $index );

			$prepared_steps[] = [
				'title'       => $title,
				'description' => $description,
				'fields'      => $fields,
				'index'       => $index,
			];

			$all_fields = array_merge( $all_fields, $fields );
		}

		return [
			'steps'  => $prepared_steps,
			'fields' => $all_fields,
		];
	}

	/**
	 * Sanitize and prepare fields for output.
	 *
	 * @param array      $fields     Raw fields from Elementor.
	 * @param array|null $used_names Reference list of used keys.
	 * @param string     $step_title Step label (optional).
	 * @param int|null   $step_index Step index (optional).
	 * @return array
	 */
	private function prepare_fields( $fields, &$used_names = null, $step_title = '', $step_index = null ) {
		$allowed_types = [ 'text', 'email', 'tel', 'number', 'textarea', 'select', 'multiselect', 'checkbox', 'radio', 'date', 'time', 'datetime', 'url' ];
		$prepared      = [];

		if ( null === $used_names ) {
			$used_names = [];
		}

		foreach ( $fields as $index => $field ) {
			$label       = isset( $field['field_label'] ) ? sanitize_text_field( $field['field_label'] ) : '';
			$name        = isset( $field['field_name'] ) ? $field['field_name'] : '';
			$type        = isset( $field['field_type'] ) ? $field['field_type'] : 'text';
			$placeholder = isset( $field['field_placeholder'] ) ? $field['field_placeholder'] : '';
			$required    = isset( $field['field_required'] ) && 'yes' === $field['field_required'];
			$options_raw = isset( $field['field_options'] ) ? $field['field_options'] : '';
			$options_list_raw = isset( $field['field_options_list'] ) ? $field['field_options_list'] : [];
			$width       = isset( $field['field_width'] ) ? (int) $field['field_width'] : 4;
			$layout_choice = isset( $field['field_layout_width'] ) ? $field['field_layout_width'] : 'auto';

			$options  = $this->parse_field_options_repeater( $options_list_raw );

			if ( empty( $options ) ) {
				$options = $this->parse_field_options( $options_raw );
			}
			$multiple = in_array( $type, [ 'multiselect', 'checkbox' ], true );

			$width = max( 1, min( 4, $width ) );

			$name = preg_replace( '/[^a-z0-9_]/', '_', strtolower( $name ) );
			$name = $name ? $name : 'field_' . ( $index + 1 );

			$base_name = $name;
			$counter   = 1;

			while ( in_array( $name, $used_names, true ) ) {
				$name = $base_name . '_' . $counter;
				$counter++;
			}

			$used_names[] = $name;

			if ( ! in_array( $type, $allowed_types, true ) ) {
				$type = 'text';
			}

			$prepared[] = [
				'label'       => $label,
				'name'        => $name,
				'type'        => $type,
				'placeholder' => sanitize_text_field( $placeholder ),
				'required'    => $required,
				'options'     => $options,
				'multiple'    => $multiple,
				'width'       => $width,
				'layout_span' => $this->resolve_layout_span( $layout_choice, $width ),
				'step'        => $step_title,
				'step_index'  => null !== $step_index ? (int) $step_index : null,
			];
		}

		return $prepared;
	}

	/**
	 * Parse raw options list from Elementor control.
	 *
	 * @param string $raw_options Raw textarea input.
	 * @return array
	 */
	private function parse_field_options( $raw_options ) {
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
			$opt_width = isset( $parts[3] ) ? (int) $parts[3] : 0;

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
				'width' => $this->sanitize_width_value( $opt_width ),
			];
		}

		return $options;
	}

	/**
	 * Parse structured options repeater payload.
	 *
	 * @param array $raw_list Raw repeater data.
	 * @return array
	 */
	private function parse_field_options_repeater( $raw_list ) {
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
			$opt_width = isset( $item['option_width'] ) ? (int) $item['option_width'] : 0;

			if ( '' === $value && '' !== $label ) {
				$value = sanitize_title( $label );
			}

			if ( '' === $label && '' !== $value ) {
				$label = $value;
			}

			if ( isset( $item['option_image'] ) ) {
				$image_source = $item['option_image'];

				if ( is_array( $image_source ) ) {
					if ( isset( $image_source['id'] ) && $image_source['id'] ) {
						$attachment_url = wp_get_attachment_image_url( (int) $image_source['id'], 'large' );
						if ( $attachment_url ) {
							$image = esc_url_raw( $attachment_url );
						}
					}

					if ( ! $image && isset( $image_source['url'] ) ) {
						$image = esc_url_raw( $image_source['url'] );
					}
				} elseif ( is_string( $image_source ) ) {
					$image = esc_url_raw( $image_source );
				}
			}

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$options[] = [
				'value' => $value ? $value : sanitize_text_field( uniqid( 'opt_', true ) ),
				'label' => $label ? $label : $value,
				'image' => $image,
				'width' => $this->sanitize_width_value( $opt_width ),
			];
		}

		return $options;
	}

	/**
	 * Normalize width selector.
	 *
	 * @param int|string $width Width value.
	 * @return int
	 */
	private function sanitize_width_value( $width ) {
		$width = (int) $width;

		if ( $width < 1 || $width > 4 ) {
			return 0;
		}

		return $width;
	}

	/**
	 * Determine the layout span (out of 12) for a field container.
	 *
	 * @param string|int $layout_choice Selected layout value.
	 * @param int        $fallback_width Legacy width (1-4) for auto mode.
	 * @return int
	 */
	private function resolve_layout_span( $layout_choice, $fallback_width ) {
		if ( '' === $layout_choice || null === $layout_choice ) {
			$layout_choice = 'auto';
		}

		$map = [
			'full'        => 12,
			'two_thirds'  => 8,
			'half'        => 6,
			'third'       => 4,
		];

		if ( is_string( $layout_choice ) && isset( $map[ $layout_choice ] ) ) {
			return $map[ $layout_choice ];
		}

		if ( 'auto' === $layout_choice ) {
			return 0;
		}

		if ( is_numeric( $layout_choice ) ) {
			$numeric = (int) $layout_choice;
			if ( $numeric > 0 ) {
				return min( 12, max( 1, $numeric ) );
			}
		}

		$fallback = max( 1, min( 4, (int) $fallback_width ) );

		return $fallback * 3;
	}

	/**
	 * Render a single field block.
	 *
	 * @param string $form_id       Form identifier.
	 * @param array  $field         Field configuration.
	 * @param int    $field_counter Reference counter for unique IDs.
	 * @return string
	 */
	private function render_field_block( $form_id, $field, &$field_counter ) {
		$field_id = sprintf( '%s-%s-%d', $form_id, $field['name'], $field_counter );
		$field_counter++;

		$is_required = ! empty( $field['required'] );
		$label       = $field['label'] ? $field['label'] : ucfirst( $field['name'] );
		$placeholder = $field['placeholder'];
		$type        = $field['type'];
		$options     = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
		$multiple    = ! empty( $field['multiple'] );
		$input_name  = $field['name'] . ( $multiple ? '[]' : '' );

		$wrapper_classes = [ 'ava-contact-form__field' ];
		$layout_span     = isset( $field['layout_span'] ) ? (int) $field['layout_span'] : 0;

		$has_options = ! empty( $options );
		$is_single_toggle = in_array( $type, [ 'checkbox', 'radio' ], true ) && ! $has_options;

		if ( in_array( $type, [ 'checkbox', 'radio' ], true ) ) {
			$wrapper_classes[] = 'ava-contact-form__field--options';
			if ( $is_single_toggle ) {
				$wrapper_classes[] = 'ava-contact-form__field--single-toggle';
			}
		}

		$width = isset( $field['width'] ) ? (int) $field['width'] : 1;
		$width = max( 1, min( 4, $width ) );
		$wrapper_classes[] = 'ava-contact-form__field--span-' . $width;
		if ( $layout_span > 0 ) {
			$wrapper_classes[] = 'ava-contact-form__field--custom-span';
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			data-field-type="<?php echo esc_attr( $type ); ?>"
			data-required="<?php echo $is_required ? 'true' : 'false'; ?>"
			<?php if ( $layout_span > 0 ) : ?>
				style="--ava-cf-span: <?php echo esc_attr( $layout_span ); ?>;"
			<?php endif; ?>
		>
			<?php if ( in_array( $type, [ 'checkbox', 'radio' ], true ) ) : ?>
				<?php $group_label_id = $field_id . '-label'; ?>
				<?php if ( $is_single_toggle ) : ?>
					<div class="ava-contact-form__options ava-contact-form__options--single" role="group" <?php echo $label ? 'aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
						<label class="ava-contact-form__option ava-contact-form__option--span-<?php echo esc_attr( $width ); ?>" for="<?php echo esc_attr( $field_id ); ?>">
							<input
								type="checkbox"
								id="<?php echo esc_attr( $field_id ); ?>"
								name="<?php echo esc_attr( $input_name ); ?>"
								value="yes"
								<?php echo $is_required ? ' aria-required="true"' : ''; ?>
							/>
							<span class="ava-contact-form__option-body">
								<span class="ava-contact-form__option-text"><?php echo esc_html( $label ); ?></span>
							</span>
						</label>
					</div>
					<?php if ( $placeholder ) : ?>
						<p class="ava-contact-form__group-note"><?php echo esc_html( $placeholder ); ?></p>
					<?php endif; ?>
					<?php elseif ( empty( $options ) ) : ?>
						<p class="ava-contact-form__group-note"><?php esc_html_e( 'Configurez les options de ce champ depuis le panneau Elementor.', 'ava-contact-form' ); ?></p>
					<?php else : ?>
					<div class="ava-contact-form__options" role="group" <?php echo $label ? 'aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
						<?php foreach ( $options as $option_index => $option ) : ?>
							<?php
							$option_id    = sprintf( '%s-%d', $field_id, $option_index );
							$option_image = isset( $option['image'] ) ? $option['image'] : '';
							$option_width = isset( $option['width'] ) && $option['width'] ? (int) $option['width'] : $width;
							$option_width = max( 1, min( 4, $option_width ) );
							?>
							<label class="ava-contact-form__option ava-contact-form__option--span-<?php echo esc_attr( $option_width ); ?>" for="<?php echo esc_attr( $option_id ); ?>">
								<input
									type="<?php echo esc_attr( 'checkbox' === $type ? 'checkbox' : 'radio' ); ?>"
									id="<?php echo esc_attr( $option_id ); ?>"
									name="<?php echo esc_attr( $input_name ); ?>"
									value="<?php echo esc_attr( $option['value'] ); ?>"
									<?php echo ( 'radio' === $type && $is_required && 0 === $option_index ) ? ' required aria-required="true"' : ''; ?>
								/>
								<span class="ava-contact-form__option-body">
									<?php if ( $option_image ) : ?>
										<span class="ava-contact-form__option-media">
											<img src="<?php echo esc_url( $option_image ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" />
										</span>
									<?php endif; ?>
									<span class="ava-contact-form__option-text"><?php echo esc_html( $option['label'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php if ( $placeholder ) : ?>
						<p class="ava-contact-form__group-note"><?php echo esc_html( $placeholder ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			<?php else : ?>
				<label class="ava-contact-form__label" for="<?php echo esc_attr( $field_id ); ?>">
					<?php
					echo esc_html( $label );
					if ( $is_required ) {
						echo ' *';
					}
					?>
				</label>
				<?php if ( in_array( $type, [ 'select', 'multiselect' ], true ) && $placeholder ) : ?>
					<p class="ava-contact-form__hint"><?php echo esc_html( $placeholder ); ?></p>
				<?php endif; ?>
				<?php
				switch ( $type ) {
					case 'textarea':
						?>
						<textarea
							id="<?php echo esc_attr( $field_id ); ?>"
							name="<?php echo esc_attr( $input_name ); ?>"
							rows="5"
							<?php echo $is_required ? ' required aria-required="true"' : ''; ?>
							placeholder="<?php echo esc_attr( $placeholder ); ?>"
						></textarea>
						<?php
						break;
					case 'select':
					case 'multiselect':
						?>
						<select
							id="<?php echo esc_attr( $field_id ); ?>"
							name="<?php echo esc_attr( $input_name ); ?>"
							<?php echo 'multiselect' === $type ? ' multiple' : ''; ?>
							<?php echo ( $is_required && 'select' === $type ) ? ' required aria-required="true"' : ''; ?>
						>
							<?php if ( 'select' === $type ) : ?>
								<option value="" <?php echo $is_required ? 'disabled selected hidden' : 'selected'; ?>><?php echo esc_html( $placeholder ? $placeholder : __( 'Selectionnez une option', 'ava-contact-form' ) ); ?></option>
							<?php endif; ?>
							<?php foreach ( $options as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						break;
					default:
						$input_type = in_array( $type, [ 'date', 'time', 'datetime' ], true ) ? $type : $type;
						if ( 'datetime' === $type ) {
							$input_type = 'datetime-local';
						}
						if ( ! in_array( $input_type, [ 'text', 'email', 'tel', 'number', 'url', 'date', 'time', 'datetime-local' ], true ) ) {
							$input_type = 'text';
						}
						?>
						<input
							type="<?php echo esc_attr( $input_type ); ?>"
							id="<?php echo esc_attr( $field_id ); ?>"
							name="<?php echo esc_attr( $input_name ); ?>"
							<?php echo $is_required ? ' required aria-required="true"' : ''; ?>
							placeholder="<?php echo esc_attr( $placeholder ); ?>"
						/>
						<?php
						break;
				}
				?>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}


