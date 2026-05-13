<?php
/**
 * Admin menu registration, form submission handlers, and asset enqueuing.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// MENU
// ============================================================

add_action( 'admin_menu', 'mslc_admin_menu' );
function mslc_admin_menu() {
	add_menu_page(
		'Careers',
		'Careers',
		'manage_options',
		'mslc-karir',
		'mslc_page_karir_list',
		'dashicons-welcome-learn-more',
		30
	);
	add_submenu_page( 'mslc-karir', 'All Careers',       'All Careers',       'manage_options', 'mslc-karir',          'mslc_page_karir_list' );
	add_submenu_page( 'mslc-karir', 'Add Career',        'Add Career',        'manage_options', 'mslc-karir-add',      'mslc_page_karir_form' );
	add_submenu_page( 'mslc-karir', 'Career Categories', 'Career Categories', 'manage_options', 'mslc-karir-kategori', 'mslc_page_kategori'   );
}

// ============================================================
// FORM HANDLERS
// ============================================================

add_action( 'admin_init', 'mslc_handle_forms' );
function mslc_handle_forms() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$page = $_GET['page'] ?? '';

	// Save career
	if ( isset( $_POST['mslc_save_karir'] ) && check_admin_referer( 'mslc_karir_nonce' ) ) {
		global $wpdb;
		$id   = absint( $_POST['karir_id'] ?? 0 );
		$data = [
			'nama'        => sanitize_text_field( $_POST['nama'] ),
			'deskripsi'   => wp_kses_post( $_POST['deskripsi'] ),
			'gambar'      => absint( $_POST['gambar'] ),
			'kategori_id' => absint( $_POST['kategori_id'] ),
		];

		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}karir", $data, [ 'id' => $id ] );
		} else {
			$wpdb->insert( "{$wpdb->prefix}karir", $data );
		}

		wp_redirect( admin_url( 'admin.php?page=mslc-karir&saved=1' ) );
		exit;
	}

	// Delete career
	if ( $page === 'mslc-karir' && ( $_GET['action'] ?? '' ) === 'delete' && isset( $_GET['id'] ) ) {
		$id = absint( $_GET['id'] );
		check_admin_referer( 'mslc_delete_karir_' . $id );
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}karir", [ 'id' => $id ] );
		wp_redirect( admin_url( 'admin.php?page=mslc-karir&deleted=1' ) );
		exit;
	}

	// Save category
	if ( isset( $_POST['mslc_save_kategori'] ) && check_admin_referer( 'mslc_kategori_nonce' ) ) {
		global $wpdb;
		$id   = absint( $_POST['kategori_id'] ?? 0 );
		$nama = sanitize_text_field( $_POST['nama'] );
		if ( $id ) {
			$wpdb->update( "{$wpdb->prefix}karir_kategori", [ 'nama' => $nama ], [ 'id' => $id ] );
		} else {
			$wpdb->insert( "{$wpdb->prefix}karir_kategori", [ 'nama' => $nama ] );
		}
		wp_redirect( admin_url( 'admin.php?page=mslc-karir-kategori&saved=1' ) );
		exit;
	}

	// Delete category
	if ( $page === 'mslc-karir-kategori' && ( $_GET['action'] ?? '' ) === 'delete' && isset( $_GET['id'] ) ) {
		$id = absint( $_GET['id'] );
		check_admin_referer( 'mslc_delete_kategori_' . $id );
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}karir_kategori", [ 'id' => $id ] );
		wp_redirect( admin_url( 'admin.php?page=mslc-karir-kategori&deleted=1' ) );
		exit;
	}
}

// ============================================================
// ENQUEUE (media picker)
// ============================================================

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, [ 'toplevel_page_mslc-karir', 'careers_page_mslc-karir-add' ], true ) ) {
		wp_enqueue_media();
	}
} );
