<?php
namespace Ava_Contact_Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Submissions {
	const POST_TYPE = 'ava_cf_message';
	const META_FORM_LABEL = 'ava_cf_form_label';
	const UNLABELED_KEY = '__none';

	/**
	 * Register CPT and admin hooks.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = [
			'name'               => _x( 'Messages', 'post type general name', 'ava-contact-form' ),
			'singular_name'      => _x( 'Message', 'post type singular name', 'ava-contact-form' ),
			'menu_name'          => _x( 'Messages', 'admin menu', 'ava-contact-form' ),
			'name_admin_bar'     => _x( 'Message', 'add new on admin bar', 'ava-contact-form' ),
			'add_new'            => __( 'Ajouter un message', 'ava-contact-form' ),
			'add_new_item'       => __( 'Ajouter un nouveau message', 'ava-contact-form' ),
			'new_item'           => __( 'Nouveau message', 'ava-contact-form' ),
			'edit_item'          => __( 'Modifier le message', 'ava-contact-form' ),
			'view_item'          => __( 'Voir le message', 'ava-contact-form' ),
			'all_items'          => __( 'Tous les messages', 'ava-contact-form' ),
			'search_items'       => __( 'Rechercher des messages', 'ava-contact-form' ),
			'not_found'          => __( 'Aucun message trouve.', 'ava-contact-form' ),
			'not_found_in_trash' => __( 'Aucun message trouve dans la corbeille.', 'ava-contact-form' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'supports'        => [ 'title' ],
				'capability_type' => 'post',
				'capabilities'    => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'    => true,
			]
		);

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'set_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'views_edit-' . self::POST_TYPE, [ $this, 'register_views' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_list_query' ] );
		add_filter( 'post_row_actions', [ $this, 'filter_row_actions' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'admin_menu', [ $this, 'remove_default_meta_boxes' ] );
	}

	public function set_columns( $columns ) {
		return [
			'cb'         => $columns['cb'],
			'title'      => __( 'Demandeur', 'ava-contact-form' ),
			'form_label' => __( 'Formulaire', 'ava-contact-form' ),
			'email'      => __( 'Email', 'ava-contact-form' ),
			'summary'    => __( 'Details', 'ava-contact-form' ),
			'date'       => $columns['date'],
		];
	}

	public function render_column( $column, $post_id ) {
		$payload        = get_post_meta( $post_id, 'ava_cf_payload', true );
		$payload        = is_array( $payload ) ? $payload : [];
		$display_values = isset( $payload['display'] ) ? (array) $payload['display'] : [];
		$raw_values     = isset( $payload['values'] ) ? (array) $payload['values'] : [];
		$meta           = isset( $payload['meta'] ) ? (array) $payload['meta'] : [];

		switch ( $column ) {
			case 'form_label':
				$label = get_post_meta( $post_id, self::META_FORM_LABEL, true );
				if ( ! $label && ! empty( $meta['form_label'] ) ) {
					$label = $meta['form_label'];
				} elseif ( ! $label && ! empty( $meta['page_title'] ) ) {
					$label = $meta['page_title'];
				}
				echo esc_html( $label ? $label : '--' );
				break;

			case 'email':
				$email = get_post_meta( $post_id, 'ava_cf_email', true );
				if ( $email ) {
					printf( '<a href="%1$s">%2$s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				} else {
					echo esc_html( $email );
				}
				break;

			case 'summary':
				if ( empty( $payload['fields'] ) ) {
					echo '--';
					break;
				}
				$items = [];
				foreach ( $payload['fields'] as $field ) {
					$name  = isset( $field['name'] ) ? $field['name'] : '';
					$label = isset( $field['label'] ) ? $field['label'] : '';
					if ( ! $name ) {
						continue;
					}
					$value = '';
					if ( isset( $display_values[ $name ] ) && '' !== trim( (string) $display_values[ $name ] ) ) {
						$value = $display_values[ $name ];
					} elseif ( isset( $raw_values[ $name ] ) ) {
						$value = $raw_values[ $name ];
						if ( is_array( $value ) ) {
							$value = implode(
								', ',
								array_map(
									'trim',
									array_filter(
										array_map( 'strval', $value )
									)
								)
							);
						}
					}
					if ( '' === trim( (string) $value ) ) {
						continue;
					}
					$items[] = sprintf(
						'%1$s: %2$s',
						esc_html( $label ? $label : ucfirst( $name ) ),
						esc_html( wp_trim_words( $value, 12, '...' ) )
					);
					if ( count( $items ) >= 3 ) {
						break;
					}
				}
				echo $items ? implode( ' | ', $items ) : '--';
				break;
		}
	}

	public function register_views( $views ) {
		$current = isset( $_GET['ava_cf_form_label'] ) ? sanitize_text_field( wp_unslash( $_GET['ava_cf_form_label'] ) ) : '';
		$base    = add_query_arg( 'post_type', self::POST_TYPE, admin_url( 'edit.php' ) );

		$new_views = [];

		$total = $this->get_total_count();
		$new_views['all'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( remove_query_arg( 'ava_cf_form_label', $base ) ),
			'' === $current ? ' class="current"' : '',
			esc_html__( 'Tous', 'ava-contact-form' ),
			(int) $total
		);

		foreach ( $this->get_form_label_counts() as $label => $count ) {
			$link_label = self::UNLABELED_KEY === $label ? __( 'Non etiquete', 'ava-contact-form' ) : $label;
			$encoded    = rawurlencode( $label );
			$view_key   = sanitize_title( self::UNLABELED_KEY === $label ? 'non-etiquete' : $label );
			$new_views[ $view_key ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
				esc_url( add_query_arg( 'ava_cf_form_label', $encoded, $base ) ),
				$label === $current ? ' class="current"' : '',
				esc_html( $link_label ),
				(int) $count
			);
		}

		return $new_views + $views;
	}

	public function filter_list_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( isset( $_GET['ava_cf_form_label'] ) && '' !== $_GET['ava_cf_form_label'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_label = sanitize_text_field( wp_unslash( $_GET['ava_cf_form_label'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( self::UNLABELED_KEY === $form_label ) {
				$query->set(
					'meta_query',
					[
						[
							'key'     => self::META_FORM_LABEL,
							'compare' => 'NOT EXISTS',
						],
					]
				);
			} else {
				$query->set(
					'meta_query',
					[
						[
							'key'   => self::META_FORM_LABEL,
							'value' => $form_label,
						],
					]
				);
			}
		}
	}

	public function filter_row_actions( $actions, $post ) {
		if ( self::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	public function add_meta_boxes() {
		add_meta_box(
			'ava_cf_submission_details',
			__( 'Details du message', 'ava-contact-form' ),
			[ $this, 'render_details_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function remove_default_meta_boxes() {
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
	}

	public function render_details_meta_box( $post ) {
		$payload = get_post_meta( $post->ID, 'ava_cf_payload', true );

		if ( ! is_array( $payload ) ) {
			echo '<p>' . esc_html__( 'Aucun detail n\'est disponible pour cet envoi.', 'ava-contact-form' ) . '</p>';
			return;
		}

		$fields         = isset( $payload['fields'] ) ? (array) $payload['fields'] : [];
		$values         = isset( $payload['values'] ) ? (array) $payload['values'] : [];
		$display_values = isset( $payload['display'] ) ? (array) $payload['display'] : [];
		$meta           = isset( $payload['meta'] ) ? (array) $payload['meta'] : [];

		echo '<table class="widefat striped">';
		$current_step = null;

		foreach ( $fields as $field ) {
			$name  = isset( $field['name'] ) ? $field['name'] : '';
			$label = isset( $field['label'] ) ? $field['label'] : '';
			$step  = isset( $field['step'] ) ? $field['step'] : '';
			$value = $name && isset( $display_values[ $name ] ) ? $display_values[ $name ] : ( $name && isset( $values[ $name ] ) ? $values[ $name ] : '' );

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'trim', array_filter( array_map( 'strval', $value ) ) ) );
			}

			if ( $step && $current_step !== $step ) {
				printf( '<tr><th colspan="2" style="background:#f6f7f7;font-weight:600;">%s</th></tr>', esc_html( $step ) );
				$current_step = $step;
			}

			printf(
				'<tr><th style="width:25%%">%s</th><td><pre style="white-space:pre-wrap;font-family:inherit;margin:0;">%s</pre></td></tr>',
				esc_html( $label ? $label : ucfirst( $name ) ),
				esc_html( (string) $value )
			);
		}

		echo '</table>';

		$meta_labels = [
			'form_id'    => __( 'ID du formulaire', 'ava-contact-form' ),
			'form_label' => __( 'Formulaire', 'ava-contact-form' ),
			'page_title' => __( 'Page', 'ava-contact-form' ),
			'page_url'   => __( 'URL de la page', 'ava-contact-form' ),
			'referer'    => __( 'Referent', 'ava-contact-form' ),
			'ip'         => __( 'Adresse IP', 'ava-contact-form' ),
			'timestamp'  => __( 'Date d\'envoi', 'ava-contact-form' ),
			'source'     => __( 'Source', 'ava-contact-form' ),
			'form_mode'  => __( 'Type de formulaire', 'ava-contact-form' ),
			'form_steps' => __( 'Etapes', 'ava-contact-form' ),
		];

		$meta_rows = [];

		foreach ( $meta_labels as $key => $label ) {
			if ( empty( $meta[ $key ] ) ) {
				continue;
			}

			$value = $meta[ $key ];

			if ( 'form_mode' === $key ) {
				$value = 'multi' === strtolower( (string) $value ) ? __( 'Formulaire multi etapes', 'ava-contact-form' ) : __( 'Une seule etape', 'ava-contact-form' );
			} elseif ( 'timestamp' === $key ) {
				$value = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $value ) );
			}

			if ( in_array( $key, [ 'page_url', 'referer' ], true ) ) {
				$url = esc_url( $value );
				if ( $url ) {
					$meta_rows[] = sprintf( '<tr><th>%1$s</th><td><a href="%2$s" target="_blank" rel="nofollow noopener">%2$s</a></td></tr>', esc_html( $label ), $url );
				}
				continue;
			}

			$meta_rows[] = sprintf( '<tr><th>%1$s</th><td>%2$s</td></tr>', esc_html( $label ), esc_html( $value ) );
		}

		if ( $meta_rows ) {
			echo '<h3>' . esc_html__( 'Metadonnees', 'ava-contact-form' ) . '</h3>';
			echo '<table class="widefat striped">' . implode( '', $meta_rows ) . '</table>';
		}
	}

	/**
	 * Persist submission data.
	 *
	 * @param array $submission Submission payload.
	 * @return void
	 */
	public function store_submission( $submission ) {
		$fields  = isset( $submission['fields'] ) ? (array) $submission['fields'] : [];
		$values  = isset( $submission['values'] ) ? (array) $submission['values'] : [];
		$display = isset( $submission['display_values'] ) ? (array) $submission['display_values'] : [];
		$meta    = isset( $submission['meta'] ) ? (array) $submission['meta'] : [];

		if ( empty( $fields ) ) {
			return;
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $this->generate_title( $fields, $values, $meta, $display ),
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			'ava_cf_payload',
			[
				'fields'  => $fields,
				'values'  => $values,
				'display' => $display,
				'meta'    => $meta,
			]
		);

		$form_label = isset( $meta['form_label'] ) ? sanitize_text_field( $meta['form_label'] ) : '';
		if ( $form_label ) {
			update_post_meta( $post_id, self::META_FORM_LABEL, $form_label );
		} else {
			delete_post_meta( $post_id, self::META_FORM_LABEL );
		}

		$email = $this->extract_email( $fields, $values );
		if ( $email ) {
			update_post_meta( $post_id, 'ava_cf_email', $email );
		}
	}

	private function generate_title( $fields, $values, $meta, $display = [] ) {
		$name = '';

		foreach ( $fields as $field ) {
			if ( 'text' !== $field['type'] ) {
				continue;
			}

			$key = isset( $field['name'] ) ? $field['name'] : '';
			if ( $key && ! empty( $values[ $key ] ) ) {
				$name = trim( (string) $values[ $key ] );
				if ( $name ) {
					break;
				}
			}

			if ( $key && ! empty( $display[ $key ] ) ) {
				$name = trim( (string) $display[ $key ] );
				if ( $name ) {
					break;
				}
			}
		}

		if ( ! $name ) {
			$email = $this->extract_email( $fields, $values );
			$name  = $email ? $email : __( 'Message du formulaire', 'ava-contact-form' );
		}

		$timestamp = isset( $meta['timestamp'] ) ? strtotime( (string) $meta['timestamp'] ) : false;
		$date      = $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		$context = array_filter(
			[
				! empty( $meta['form_label'] ) ? $meta['form_label'] : '',
				! empty( $meta['page_title'] ) ? $meta['page_title'] : '',
			]
		);

		if ( $context ) {
			return sprintf( '%1$s - %2$s (%3$s)', $name, implode( ' / ', $context ), $date );
		}

		return sprintf( '%1$s - %2$s', $name, $date );
	}

	private function extract_email( $fields, $values ) {
		foreach ( $fields as $field ) {
			if ( 'email' !== $field['type'] ) {
				continue;
			}

			$key = isset( $field['name'] ) ? $field['name'] : '';
			if ( $key && ! empty( $values[ $key ] ) ) {
			  $email = sanitize_email( $values[ $key ] );
			  if ( $email ) {
				  return $email;
			  }
			}
		}

		return '';
	}

	private function get_form_label_counts() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS label, COUNT( * ) AS total
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status NOT IN ( 'trash', 'auto-draft' )
				GROUP BY pm.meta_value
				ORDER BY pm.meta_value ASC",
				self::META_FORM_LABEL,
				self::POST_TYPE
			)
		);

		$counts = [];

		if ( $results ) {
			foreach ( $results as $row ) {
				$label = trim( (string) $row->label );
				if ( '' === $label ) {
					$label = self::UNLABELED_KEY;
				}
				if ( ! isset( $counts[ $label ] ) ) {
					$counts[ $label ] = 0;
				}
				$counts[ $label ] += (int) $row->total;
			}
		}

		$unlabeled = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( * )
				FROM {$wpdb->posts} p
				WHERE p.post_type = %s
				AND p.post_status NOT IN ( 'trash', 'auto-draft' )
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} pm
					WHERE pm.post_id = p.ID AND pm.meta_key = %s
				)",
				self::POST_TYPE,
				self::META_FORM_LABEL
			)
		);

		if ( $unlabeled > 0 ) {
			if ( ! isset( $counts[ self::UNLABELED_KEY ] ) ) {
				$counts[ self::UNLABELED_KEY ] = 0;
			}
			$counts[ self::UNLABELED_KEY ] += $unlabeled;
		}

		return $counts;
	}

	private function get_total_count() {
		$counts = wp_count_posts( self::POST_TYPE );
		$total  = 0;

		foreach ( (array) $counts as $status => $count ) {
			if ( 'trash' === $status ) {
				continue;
			}
			$total += (int) $count;
		}

		return $total;
	}
}
