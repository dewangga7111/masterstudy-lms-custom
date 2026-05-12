<?php
/**
 * Admin page: All Careers list + Career Detail (courses by level).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function mslc_page_karir_list() {
	if ( isset( $_GET['detail'] ) ) {
		mslc_page_karir_detail( absint( $_GET['detail'] ) );
		return;
	}

	global $wpdb;
	$karirs = $wpdb->get_results(
		"SELECT k.*, kk.nama AS kategori_nama
		 FROM {$wpdb->prefix}karir k
		 LEFT JOIN {$wpdb->prefix}karir_kategori kk ON k.kategori_id = kk.id
		 ORDER BY k.id DESC"
	);
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">All Careers</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-add' ) ); ?>" class="page-title-action">+ Add Career</a>

		<?php if ( isset( $_GET['saved'] ) )   : ?><div class="notice notice-success is-dismissible"><p>Career saved successfully.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Career deleted.</p></div><?php endif; ?>

		<table class="wp-list-table widefat fixed striped" style="margin-top:15px">
			<thead>
				<tr>
					<th>Career Name</th>
					<th>Category</th>
					<th width="130">Courses</th>
					<th width="180">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $karirs ) ) : ?>
					<tr><td colspan="4">No careers found.</td></tr>
				<?php else : foreach ( $karirs as $karir ) :
					$count = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'karir_id' AND meta_value = %d",
						$karir->id
					) );
					$detail_url = admin_url( 'admin.php?page=mslc-karir&detail=' . $karir->id );
					$edit_url   = admin_url( 'admin.php?page=mslc-karir-add&id=' . $karir->id );
					$delete_url = wp_nonce_url( admin_url( 'admin.php?page=mslc-karir&action=delete&id=' . $karir->id ), 'mslc_delete_karir_' . $karir->id );
				?>
				<tr>
					<td><strong><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $karir->nama ); ?></a></strong></td>
					<td><?php echo esc_html( $karir->kategori_nama ?: '—' ); ?></td>
					<td><?php echo $count; ?> course<?php echo $count !== 1 ? 's' : ''; ?></td>
					<td>
						<a href="<?php echo esc_url( $detail_url ); ?>">View</a> &nbsp;|&nbsp;
						<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> &nbsp;|&nbsp;
						<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Delete this career?')" style="color:#b32d2e">Delete</a>
					</td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function mslc_page_karir_detail( $karir_id ) {
	global $wpdb;

	$karir = $wpdb->get_row( $wpdb->prepare(
		"SELECT k.*, kk.nama AS kategori_nama
		 FROM {$wpdb->prefix}karir k
		 LEFT JOIN {$wpdb->prefix}karir_kategori kk ON k.kategori_id = kk.id
		 WHERE k.id = %d",
		$karir_id
	) );

	if ( ! $karir ) {
		echo '<div class="wrap"><p>Career not found.</p></div>';
		return;
	}

	$courses = get_posts( [
		'post_type'      => 'stm-courses',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_query'     => [ [ 'key' => 'karir_id', 'value' => $karir_id, 'compare' => '=' ] ],
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$level_labels = [
		'beginner'     => 'Beginner',
		'intermediate' => 'Intermediate',
		'advanced'     => 'Advanced',
		''             => 'General',
	];
	$level_order = [ 'beginner', 'intermediate', 'advanced', '' ];

	$by_level = [];
	foreach ( $courses as $course ) {
		$level             = get_post_meta( $course->ID, 'level', true ) ?: '';
		$by_level[ $level ][] = $course;
	}
	?>
	<div class="wrap">
		<h1>
			<?php echo esc_html( $karir->nama ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-add&id=' . $karir_id ) ); ?>" class="page-title-action">Edit</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir' ) ); ?>" class="page-title-action">← Back</a>
		</h1>

		<table class="form-table" style="max-width:600px">
			<tr><th>Category</th><td><?php echo esc_html( $karir->kategori_nama ?: '—' ); ?></td></tr>
			<?php if ( $karir->deskripsi ) : ?>
			<tr><th>Description</th><td><?php echo wp_kses_post( $karir->deskripsi ); ?></td></tr>
			<?php endif; ?>
			<?php if ( $karir->gambar ) : ?>
			<tr><th>Image</th><td><?php echo wp_get_attachment_image( $karir->gambar, 'medium' ); ?></td></tr>
			<?php endif; ?>
		</table>

		<hr>
		<h2>Courses by Level</h2>

		<?php if ( empty( $courses ) ) : ?>
			<p>No courses assigned to this career yet. Assign courses from the <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=stm-courses' ) ); ?>">course editor</a>.</p>
		<?php else : ?>
			<?php foreach ( $level_order as $level_key ) :
				if ( ! isset( $by_level[ $level_key ] ) ) continue;
				$label = $level_labels[ $level_key ];
			?>
			<h3 style="margin-top:25px"><?php echo esc_html( $label ); ?></h3>
			<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px">
				<thead>
					<tr>
						<th>Course Title</th>
						<th width="120">Status</th>
						<th width="140">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $by_level[ $level_key ] as $course ) : ?>
					<tr>
						<td><?php echo esc_html( $course->post_title ); ?></td>
						<td><?php echo esc_html( ucfirst( $course->post_status ) ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $course->ID ) ); ?>" target="_blank">Edit Course ↗</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}
