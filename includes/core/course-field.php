<?php
/**
 * Adds a "Career" select field to the course editor (STM metabox)
 * and exposes the value via the MasterStudy REST API.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'stm_wpcfto_boxes', 'mslc_add_course_karir_box' );
function mslc_add_course_karir_box( array $boxes ): array {
	$boxes['mslc_course_karir'] = [
		'post_type' => [ 'stm-courses' ],
		'label'     => __( 'Career Assignment', 'mslc' ),
	];
	return $boxes;
}

add_filter( 'stm_wpcfto_fields', 'mslc_add_course_karir_fields' );
function mslc_add_course_karir_fields( array $fields ): array {
	global $wpdb;
	$rows    = $wpdb->get_results( "SELECT id, nama FROM {$wpdb->prefix}karir ORDER BY nama ASC" );
	$options = [ '0' => '— Select Career —' ];
	foreach ( $rows as $row ) {
		$options[ (string) $row->id ] = $row->nama;
	}

	$fields['mslc_course_karir'] = [
		'section_karir' => [
			'name'   => __( 'Career', 'mslc' ),
			'fields' => [
				'karir_id' => [
					'type'    => 'select',
					'label'   => __( 'Career', 'mslc' ),
					'options' => $options,
				],
			],
		],
	];
	return $fields;
}

add_filter( 'masterstudy_lms_pro_course_serialize', function ( array $data ): array {
	$data['karir_id'] = (int) get_post_meta( $data['id'] ?? 0, 'karir_id', true );
	return $data;
} );
