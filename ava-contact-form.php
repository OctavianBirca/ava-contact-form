<?php
/**
 * Plugin Name:       AVA Contact Form
 * Description:       Formulaire de contact universel pour Elementor avec gestion des demandes (Devis).
 * Version:           1.1.1
 * Author:            AVA
 * Text Domain:       ava-contact-form
 * Domain Path:       /languages
 *
 * @package AvaContactForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AVA_CF_VERSION', '1.1.1' );
define( 'AVA_CF_PATH', plugin_dir_path( __FILE__ ) );
define( 'AVA_CF_URL', plugin_dir_url( __FILE__ ) );

require_once AVA_CF_PATH . 'includes/class-ava-cf-plugin.php';

register_activation_hook( __FILE__, [ \Ava_Contact_Form\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Ava_Contact_Form\Plugin::class, 'deactivate' ] );

/**
 * Retrieve plugin singleton instance.
 *
 * @return \Ava_Contact_Form\Plugin
 */
function ava_contact_form() {
	return \Ava_Contact_Form\Plugin::instance();
}

ava_contact_form();
