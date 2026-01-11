<?php
/**
 * The site's entry point.
 *
 * Loads the relevant template part,
 * the loop is executed (when needed) by the relevant template part.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$is_elementor_theme_exist = function_exists( 'elementor_theme_do_location' );
if ( is_singular() ) {
if ( ! $is_elementor_theme_exist || ! elementor_theme_do_location( 'single' ) ) {
