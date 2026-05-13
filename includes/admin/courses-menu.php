<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'mslc_register_openedx_menu' );

function mslc_register_openedx_menu() {
	add_menu_page(
		'Courses',
		'Courses',
		'manage_options',
		'mslc-openedx',
		'mslc_page_openedx_courses',
		'dashicons-welcome-learn-more',
		56
	);

	add_submenu_page(
		'mslc-openedx',
		'All Courses',
		'All Courses',
		'manage_options',
		'mslc-openedx',
		'mslc_page_openedx_courses'
	);


}
