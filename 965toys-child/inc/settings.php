<?php
/**
 * Theme options.
 *
 * Registered through the Settings API with show_in_rest so they can be read and
 * written from /wp-json/wp/v2/settings, and surfaced in the Customizer so the
 * store owner can change them without touching code or the REST API.
 *
 * @package 965toys
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Registered options
 * ---------------------------------------------------------------------- */

add_action( 'init', 'toys965_register_settings' );

function toys965_register_settings() {

	register_setting(
		'options',
		'toys965_share_image',
		array(
			'type'         => 'integer',
			'description'  => 'Attachment ID of the 1200x630 Open Graph share card.',
			'default'      => 0,
			'show_in_rest' => true,
		)
	);

	register_setting(
		'options',
		'toys965_share_description',
		array(
			'type'              => 'string',
			'description'       => 'Homepage share description. Falls back to the site tagline.',
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);

	register_setting(
		'options',
		'toys965_design',
		array(
			'type'         => 'boolean',
			'description'  => 'Master switch for the TOY BOX design layer.',
			'default'      => false,
			'show_in_rest' => true,
		)
	);
}

/* -------------------------------------------------------------------------
 * Customizer
 * ---------------------------------------------------------------------- */

add_action( 'customize_register', 'toys965_customizer' );

function toys965_customizer( $wp_customize ) {

	$wp_customize->add_section( 'toys965', array(
		'title'       => __( '965toys — Toy Box', '965toys' ),
		'priority'    => 20,
		'description' => __( 'Sharing card and design-layer controls.', '965toys' ),
	) );

	// --- share image ---
	$wp_customize->add_setting( 'toys965_share_image', array(
		'type'              => 'option',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'toys965_share_image', array(
		'label'       => __( 'Share card (Open Graph)', '965toys' ),
		'description' => __( 'Shown when a link is shared on WhatsApp, Instagram or Facebook. Use 1200x630 and keep it under 300 KB.', '965toys' ),
		'section'     => 'toys965',
		'mime_type'   => 'image',
	) ) );

	// --- share description ---
	$wp_customize->add_setting( 'toys965_share_description', array(
		'type'              => 'option',
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );

	$wp_customize->add_control( 'toys965_share_description', array(
		'label'       => __( 'Homepage share text', '965toys' ),
		'description' => __( 'One or two sentences. Without this the homepage description is scraped from page content.', '965toys' ),
		'section'     => 'toys965',
		'type'        => 'textarea',
	) );

	// --- design layer ---
	$wp_customize->add_setting( 'toys965_design', array(
		'type'              => 'option',
		'default'           => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );

	$wp_customize->add_control( 'toys965_design', array(
		'label'       => __( 'Enable the TOY BOX design layer', '965toys' ),
		'description' => __( 'Switches on the new typography, colour system, components and motion. Preview it first with ?toybox=1 on any URL while logged in as an administrator.', '965toys' ),
		'section'     => 'toys965',
		'type'        => 'checkbox',
	) );
}

/**
 * The Customizer writes theme mods for media controls in some setups, so read
 * the option first and fall back to the theme mod. Keeps both entry points
 * working without the two disagreeing.
 */
add_filter( 'option_toys965_share_image', 'toys965_share_image_fallback' );

function toys965_share_image_fallback( $value ) {

	if ( $value ) {
		return $value;
	}

	return get_theme_mod( 'toys965_share_image', 0 );
}
