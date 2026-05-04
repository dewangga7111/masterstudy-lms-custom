<?php
/**
 * Plugin Name: MasterStudy LMS Custom
 * Description: Kustomisasi tambahan untuk MasterStudy LMS — aman dari update plugin utama.
 * Version: 1.0.0
 * Author: Custom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// ACTIVATION: Buat tabel custom
// ============================================================

register_activation_hook( __FILE__, 'mslc_create_tables' );
function mslc_create_tables() {
	global $wpdb;
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}karir_kategori (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		nama varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) $charset;" );

	dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}karir (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		nama varchar(255) NOT NULL,
		deskripsi longtext,
		gambar bigint(20) DEFAULT 0,
		kategori_id bigint(20) DEFAULT 0,
		PRIMARY KEY (id)
	) $charset;" );

	dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}karir_course (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		karir_id bigint(20) NOT NULL,
		course_id bigint(20) NOT NULL,
		urutan int(11) DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY karir_course (karir_id, course_id)
	) $charset;" );
}

// ============================================================
// ADMIN MENU
// ============================================================

add_action( 'admin_menu', 'mslc_admin_menu' );
function mslc_admin_menu() {
	add_menu_page(
		'Karir',
		'Karir',
		'manage_options',
		'mslc-karir',
		'mslc_page_karir_list',
		'dashicons-welcome-learn-more',
		30
	);
	add_submenu_page( 'mslc-karir', 'Daftar Karir',    'Daftar Karir',    'manage_options', 'mslc-karir',          'mslc_page_karir_list' );
	add_submenu_page( 'mslc-karir', 'Tambah Karir',    'Tambah Karir',    'manage_options', 'mslc-karir-add',      'mslc_page_karir_form' );
	add_submenu_page( 'mslc-karir', 'Kategori Karir',  'Kategori Karir',  'manage_options', 'mslc-karir-kategori', 'mslc_page_kategori'   );
}

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================

add_action( 'admin_init', 'mslc_handle_forms' );
function mslc_handle_forms() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$page = $_GET['page'] ?? '';

	// --- Simpan karir ---
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
			$id = $wpdb->insert_id;
		}

		// Simpan relasi course
		$wpdb->delete( "{$wpdb->prefix}karir_course", [ 'karir_id' => $id ] );
		$courses = array_filter( array_map( 'absint', (array) ( $_POST['courses'] ?? [] ) ) );
		foreach ( array_values( $courses ) as $urutan => $course_id ) {
			$wpdb->insert( "{$wpdb->prefix}karir_course", [
				'karir_id'  => $id,
				'course_id' => $course_id,
				'urutan'    => $urutan,
			] );
		}

		wp_redirect( admin_url( 'admin.php?page=mslc-karir&saved=1' ) );
		exit;
	}

	// --- Hapus karir ---
	if ( $page === 'mslc-karir' && ( $_GET['action'] ?? '' ) === 'delete' && isset( $_GET['id'] ) ) {
		$id = absint( $_GET['id'] );
		check_admin_referer( 'mslc_delete_karir_' . $id );
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}karir", [ 'id' => $id ] );
		$wpdb->delete( "{$wpdb->prefix}karir_course", [ 'karir_id' => $id ] );
		wp_redirect( admin_url( 'admin.php?page=mslc-karir&deleted=1' ) );
		exit;
	}

	// --- Simpan kategori ---
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

	// --- Hapus kategori ---
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
// ENQUEUE SCRIPTS (media picker)
// ============================================================

add_action( 'admin_enqueue_scripts', 'mslc_enqueue_scripts' );
function mslc_enqueue_scripts( $hook ) {
	$karir_pages = [ 'toplevel_page_mslc-karir', 'karir_page_mslc-karir-add' ];
	if ( in_array( $hook, $karir_pages, true ) ) {
		wp_enqueue_media();
	}
}

// ============================================================
// PAGE: DAFTAR KARIR
// ============================================================

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
		<h1 class="wp-heading-inline">Daftar Karir</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-add' ) ); ?>" class="page-title-action">+ Tambah Karir</a>

		<?php if ( isset( $_GET['saved'] ) )   : ?><div class="notice notice-success is-dismissible"><p>Karir berhasil disimpan.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Karir berhasil dihapus.</p></div><?php endif; ?>

		<table class="wp-list-table widefat fixed striped" style="margin-top:15px">
			<thead>
				<tr>
					<th>Nama Karir</th>
					<th>Kategori</th>
					<th width="120">Jumlah Course</th>
					<th width="180">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $karirs ) ) : ?>
					<tr><td colspan="4">Belum ada data karir.</td></tr>
				<?php else : foreach ( $karirs as $karir ) :
					$count      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}karir_course WHERE karir_id = %d", $karir->id ) );
					$detail_url = admin_url( 'admin.php?page=mslc-karir&detail=' . $karir->id );
					$edit_url   = admin_url( 'admin.php?page=mslc-karir-add&id=' . $karir->id );
					$delete_url = wp_nonce_url( admin_url( 'admin.php?page=mslc-karir&action=delete&id=' . $karir->id ), 'mslc_delete_karir_' . $karir->id );
				?>
				<tr>
					<td><strong><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $karir->nama ); ?></a></strong></td>
					<td><?php echo esc_html( $karir->kategori_nama ?: '—' ); ?></td>
					<td><?php echo $count; ?> course</td>
					<td>
						<a href="<?php echo esc_url( $detail_url ); ?>">Lihat</a> &nbsp;|&nbsp;
						<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> &nbsp;|&nbsp;
						<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Yakin hapus karir ini?')" style="color:#b32d2e">Hapus</a>
					</td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

// ============================================================
// PAGE: DETAIL KARIR (courses by level)
// ============================================================

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
		echo '<div class="wrap"><p>Karir tidak ditemukan.</p></div>';
		return;
	}

	$course_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT course_id FROM {$wpdb->prefix}karir_course WHERE karir_id = %d ORDER BY urutan ASC",
		$karir_id
	) );

	$level_labels = [
		'beginner'     => 'Beginner',
		'intermediate' => 'Intermediate',
		'advanced'     => 'Advanced',
		''             => 'Umum',
	];
	$level_order = [ 'beginner', 'intermediate', 'advanced', '' ];

	// Kelompokkan course by level
	$by_level = [];
	foreach ( $course_ids as $course_id ) {
		$level           = get_post_meta( (int) $course_id, 'level', true ) ?: '';
		$by_level[ $level ][] = get_post( (int) $course_id );
	}
	?>
	<div class="wrap">
		<h1>
			<?php echo esc_html( $karir->nama ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-add&id=' . $karir_id ) ); ?>" class="page-title-action">Edit</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir' ) ); ?>" class="page-title-action">← Kembali</a>
		</h1>

		<table class="form-table" style="max-width:600px">
			<tr><th>Kategori</th><td><?php echo esc_html( $karir->kategori_nama ?: '—' ); ?></td></tr>
			<?php if ( $karir->deskripsi ) : ?>
			<tr><th>Deskripsi</th><td><?php echo wp_kses_post( $karir->deskripsi ); ?></td></tr>
			<?php endif; ?>
			<?php if ( $karir->gambar ) : ?>
			<tr><th>Gambar</th><td><?php echo wp_get_attachment_image( $karir->gambar, 'medium' ); ?></td></tr>
			<?php endif; ?>
		</table>

		<hr>
		<h2>Course Berdasarkan Level</h2>

		<?php if ( empty( $course_ids ) ) : ?>
			<p>Belum ada course di karir ini. <a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-add&id=' . $karir_id ) ); ?>">Tambah course</a>.</p>
		<?php else : ?>
			<?php foreach ( $level_order as $level_key ) :
				if ( ! isset( $by_level[ $level_key ] ) ) continue;
				$label = $level_labels[ $level_key ] ?? ucfirst( $level_key );
			?>
			<h3 style="margin-top:25px"><?php echo esc_html( $label ); ?></h3>
			<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px">
				<thead>
					<tr>
						<th>Judul Course</th>
						<th width="120">Status</th>
						<th width="120">Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $by_level[ $level_key ] as $course ) :
						if ( ! $course ) continue;
					?>
					<tr>
						<td><?php echo esc_html( $course->post_title ); ?></td>
						<td><?php echo esc_html( ucfirst( $course->post_status ) ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $course->ID ) ); ?>" target="_blank">Edit Course</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

// ============================================================
// PAGE: FORM TAMBAH / EDIT KARIR
// ============================================================

function mslc_page_karir_form() {
	global $wpdb;

	$id    = absint( $_GET['id'] ?? 0 );
	$karir = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}karir WHERE id = %d", $id ) ) : null;

	$kategoris = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}karir_kategori ORDER BY nama ASC" );

	$selected_courses = $id
		? $wpdb->get_col( $wpdb->prepare( "SELECT course_id FROM {$wpdb->prefix}karir_course WHERE karir_id = %d ORDER BY urutan ASC", $id ) )
		: [];

	$all_courses = get_posts( [
		'post_type'      => 'stm-courses',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	wp_enqueue_media();
	?>
	<div class="wrap">
		<h1><?php echo $karir ? 'Edit Karir' : 'Tambah Karir'; ?></h1>

		<form method="post">
			<?php wp_nonce_field( 'mslc_karir_nonce' ); ?>
			<input type="hidden" name="karir_id" value="<?php echo $id; ?>">

			<table class="form-table">
				<tr>
					<th><label for="nama">Nama Karir <span style="color:red">*</span></label></th>
					<td><input type="text" id="nama" name="nama" class="regular-text" value="<?php echo esc_attr( $karir->nama ?? '' ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="kategori_id">Kategori</label></th>
					<td>
						<select name="kategori_id" id="kategori_id">
							<option value="0">— Pilih Kategori —</option>
							<?php foreach ( $kategoris as $kat ) : ?>
								<option value="<?php echo $kat->id; ?>" <?php selected( $karir->kategori_id ?? 0, $kat->id ); ?>>
									<?php echo esc_html( $kat->nama ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-kategori' ) ); ?>" target="_blank">Kelola Kategori ↗</a>
					</td>
				</tr>
				<tr>
					<th><label for="deskripsi">Deskripsi</label></th>
					<td><textarea id="deskripsi" name="deskripsi" rows="5" class="large-text"><?php echo esc_textarea( $karir->deskripsi ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label>Gambar</label></th>
					<td>
						<div id="mslc-img-preview" style="margin-bottom:8px">
							<?php if ( ! empty( $karir->gambar ) ) echo wp_get_attachment_image( $karir->gambar, 'thumbnail' ); ?>
						</div>
						<input type="hidden" name="gambar" id="mslc-gambar-id" value="<?php echo absint( $karir->gambar ?? 0 ); ?>">
						<button type="button" class="button" id="mslc-btn-pilih">Pilih Gambar</button>
						<button type="button" class="button" id="mslc-btn-hapus">Hapus</button>
					</td>
				</tr>
				<tr>
					<th><label for="mslc-courses">Course</label></th>
					<td>
						<select name="courses[]" id="mslc-courses" multiple size="12" style="width:100%;max-width:500px">
							<?php foreach ( $all_courses as $course ) : ?>
								<option value="<?php echo $course->ID; ?>" <?php echo in_array( $course->ID, $selected_courses ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $course->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">Tahan <kbd>Ctrl</kbd> / <kbd>Cmd</kbd> untuk pilih beberapa course.</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="mslc_save_karir" class="button button-primary">Simpan Karir</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir' ) ); ?>" class="button">Batal</a>
			</p>
		</form>
	</div>

	<script>
	jQuery( function( $ ) {
		var frame;

		$( '#mslc-btn-pilih' ).on( 'click', function( e ) {
			e.preventDefault();
			if ( frame ) { frame.open(); return; }
			frame = wp.media( { title: 'Pilih Gambar', button: { text: 'Gunakan Gambar' }, multiple: false } );
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

// ============================================================
// PAGE: KATEGORI KARIR
// ============================================================

function mslc_page_kategori() {
	global $wpdb;

	$edit_id  = absint( $_GET['edit'] ?? 0 );
	$edit_row = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}karir_kategori WHERE id = %d", $edit_id ) ) : null;
	$kategoris = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}karir_kategori ORDER BY nama ASC" );
	?>
	<div class="wrap">
		<h1>Kategori Karir</h1>

		<?php if ( isset( $_GET['saved'] ) )   : ?><div class="notice notice-success is-dismissible"><p>Kategori berhasil disimpan.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Kategori berhasil dihapus.</p></div><?php endif; ?>

		<div style="display:flex;gap:40px;margin-top:20px;align-items:flex-start">

			<div style="flex:1">
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th>Nama Kategori</th><th width="150">Aksi</th></tr></thead>
					<tbody>
						<?php if ( empty( $kategoris ) ) : ?>
							<tr><td colspan="2">Belum ada kategori.</td></tr>
						<?php else : foreach ( $kategoris as $kat ) :
							$edit_url   = admin_url( 'admin.php?page=mslc-karir-kategori&edit=' . $kat->id );
							$delete_url = wp_nonce_url( admin_url( 'admin.php?page=mslc-karir-kategori&action=delete&id=' . $kat->id ), 'mslc_delete_kategori_' . $kat->id );
						?>
						<tr>
							<td><?php echo esc_html( $kat->nama ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> &nbsp;|&nbsp;
								<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Yakin hapus kategori ini?')" style="color:#b32d2e">Hapus</a>
							</td>
						</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>

			<div style="flex:0 0 280px;background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px">
				<h2 style="margin-top:0"><?php echo $edit_row ? 'Edit Kategori' : 'Tambah Kategori'; ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'mslc_kategori_nonce' ); ?>
					<input type="hidden" name="kategori_id" value="<?php echo $edit_id; ?>">
					<table class="form-table">
						<tr>
							<th><label for="nama">Nama</label></th>
							<td><input type="text" name="nama" id="nama" class="regular-text" value="<?php echo esc_attr( $edit_row->nama ?? '' ); ?>" required></td>
						</tr>
					</table>
					<p class="submit" style="margin-top:10px">
						<button type="submit" name="mslc_save_kategori" class="button button-primary">Simpan</button>
						<?php if ( $edit_row ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=mslc-karir-kategori' ) ); ?>" class="button">Batal</a>
						<?php endif; ?>
					</p>
				</form>
			</div>

		</div>
	</div>
	<?php
}
