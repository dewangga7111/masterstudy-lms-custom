<?php
/**
 * Admin page: Career Categories (CRUD).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function mslc_page_kategori() {
	global $wpdb;

	$edit_id   = absint( $_GET['edit'] ?? 0 );
	$edit_row  = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}karir_kategori WHERE id = %d", $edit_id ) ) : null;
	$kategoris = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}karir_kategori ORDER BY nama ASC" );
	?>
	<div class="wrap">
		<h1>Career Categories</h1>

		<?php if ( isset( $_GET['saved'] ) )   : ?><div class="notice notice-success is-dismissible"><p>Category saved.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Category deleted.</p></div><?php endif; ?>

		<div style="display:flex;gap:40px;margin-top:20px;align-items:flex-start">

			<div style="flex:1">
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th>Category Name</th><th width="150">Actions</th></tr></thead>
					<tbody>
						<?php if ( empty( $kategoris ) ) : ?>
							<tr><td colspan="2">No categories found.</td></tr>
						<?php else : foreach ( $kategoris as $kat ) :
							$edit_url   = admin_url( 'admin.php?page=mslc-karir-kategori&edit=' . $kat->id );
							$delete_url = wp_nonce_url( admin_url( 'admin.php?page=mslc-karir-kategori&action=delete&id=' . $kat->id ), 'mslc_delete_kategori_' . $kat->id );
						?>
						<tr>
							<td><?php echo esc_html( $kat->nama ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> &nbsp;|&nbsp;
								<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Delete this category?')" style="color:#b32d2e">Delete</a>
							</td>
						</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>

			<div style="flex:0 0 280px;background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px">
				<h2 style="margin-top:0"><?php echo $edit_row ? 'Edit Category' : 'Add Category'; ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'mslc_kategori_nonce' ); ?>
					<input type="hidden" name="kategori_id" value="<?php echo $edit_id; ?>">
					<table class="form-table">
						<tr>
							<th><label for="nama">Name</label></th>
							<td><input type="text" name="nama" id="nama" class="regular-text" value="<?php echo esc_attr( $edit_row->nama ?? '' ); ?>" required></td>
						</tr>
					</table>
					<p class="submit" style="margin-top:10px">
						<button type="submit" name="mslc_save_kategori" class="button button-primary">Save</button>
						<?php if ( $edit_row ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-kategori' ) ); ?>" class="button">Cancel</a>
						<?php endif; ?>
					</p>
				</form>
			</div>

		</div>
	</div>
	<?php
}
