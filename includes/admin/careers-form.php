<?php
/**
 * Admin page: Add / Edit Career form.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function mslc_page_karir_form() {
	global $wpdb;

	$id    = absint( $_GET['id'] ?? 0 );
	$karir = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}karir WHERE id = %d", $id ) ) : null;

	$kategoris = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}karir_kategori ORDER BY nama ASC" );

	wp_enqueue_media();
	?>
	<div class="wrap">
		<h1><?php echo $karir ? 'Edit Career' : 'Add Career'; ?></h1>

		<form method="post">
			<?php wp_nonce_field( 'mslc_karir_nonce' ); ?>
			<input type="hidden" name="karir_id" value="<?php echo $id; ?>">

			<table class="form-table">
				<tr>
					<th><label for="nama">Career Name <span style="color:red">*</span></label></th>
					<td><input type="text" id="nama" name="nama" class="regular-text" value="<?php echo esc_attr( $karir->nama ?? '' ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="kategori_id">Category</label></th>
					<td>
						<select name="kategori_id" id="kategori_id">
							<option value="0">— Select Category —</option>
							<?php foreach ( $kategoris as $kat ) : ?>
								<option value="<?php echo $kat->id; ?>" <?php selected( $karir->kategori_id ?? 0, $kat->id ); ?>>
									<?php echo esc_html( $kat->nama ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-kategori' ) ); ?>" target="_blank">Manage Categories ↗</a>
					</td>
				</tr>
				<tr>
					<th><label for="deskripsi">Description</label></th>
					<td><textarea id="deskripsi" name="deskripsi" rows="5" class="large-text"><?php echo esc_textarea( $karir->deskripsi ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label>Image</label></th>
					<td>
						<div id="mslc-img-preview" style="margin-bottom:8px">
							<?php if ( ! empty( $karir->gambar ) ) echo wp_get_attachment_image( $karir->gambar, 'thumbnail' ); ?>
						</div>
						<input type="hidden" name="gambar" id="mslc-gambar-id" value="<?php echo absint( $karir->gambar ?? 0 ); ?>">
						<button type="button" class="button" id="mslc-btn-pilih">Select Image</button>
						<button type="button" class="button" id="mslc-btn-hapus">Remove</button>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="mslc_save_karir" class="button button-primary">Save Career</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir' ) ); ?>" class="button">Cancel</a>
			</p>
		</form>
	</div>

	<script>
	jQuery( function( $ ) {
		var frame;
		$( '#mslc-btn-pilih' ).on( 'click', function( e ) {
			e.preventDefault();
			if ( frame ) { frame.open(); return; }
			frame = wp.media( { title: 'Select Image', button: { text: 'Use Image' }, multiple: false } );
			frame.on( 'select', function() {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$( '#mslc-gambar-id' ).val( att.id );
				var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
				$( '#mslc-img-preview' ).html( '<img src="' + src + '" style="max-width:150px;height:auto">' );
			} );
			frame.open();
		} );
		$( '#mslc-btn-hapus' ).on( 'click', function() {
			$( '#mslc-gambar-id' ).val( 0 );
			$( '#mslc-img-preview' ).html( '' );
		} );
	} );
	</script>
	<?php
}
