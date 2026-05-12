<?php
/**
 * Career detail page (frontend portal).
 * Rendered via [career_detail] shortcode — shows career info + courses filtered by level.
 *
 * URL params:
 *   ?karir_id=X      → which career to show
 *   ?level=beginner  → optional level filter
 *
 * To customize the back-link URL, filter `mslc_careers_url`.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'career_detail', 'mslc_render_career_detail' );

function mslc_render_career_detail( $atts ) {
	global $wpdb;

	$karir_id = isset( $_GET['karir_id'] ) ? absint( $_GET['karir_id'] ) : 0;
	$level    = isset( $_GET['level'] )    ? sanitize_key( $_GET['level'] ) : '';

	if ( ! $karir_id ) {
		return '<p style="padding:40px 0;color:#667085;">No career selected.</p>';
	}

	$karir = $wpdb->get_row( $wpdb->prepare(
		"SELECT k.*, kk.nama AS kategori_nama
		 FROM {$wpdb->prefix}karir k
		 LEFT JOIN {$wpdb->prefix}karir_kategori kk ON k.kategori_id = kk.id
		 WHERE k.id = %d",
		$karir_id
	) );

	if ( ! $karir ) {
		return '<p style="padding:40px 0;color:#667085;">Career not found.</p>';
	}

	$total_courses = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'karir_id' AND meta_value = %d",
		$karir_id
	) );

	$meta_query = [ [ 'key' => 'karir_id', 'value' => $karir_id, 'compare' => '=' ] ];
	if ( $level ) {
		$meta_query[] = [ 'key' => 'level', 'value' => $level, 'compare' => '=' ];
	}

	$courses = get_posts( [
		'post_type'      => 'stm-courses',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => $meta_query,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$level_options = [
		''             => 'All',
		'beginner'     => 'Beginner',
		'intermediate' => 'Intermediate',
		'advanced'     => 'Advanced',
	];

	$careers_url = apply_filters( 'mslc_careers_url', home_url( '/careers/' ) );

	ob_start();
	?>
	<style>
	.mslc-detail-wrap *,
	.mslc-detail-wrap *::before,
	.mslc-detail-wrap *::after { box-sizing: border-box; }
	.mslc-detail-wrap { font-family: inherit; padding: 0 0 60px; }

	.mslc-back-link { display: inline-flex; align-items: center; gap: 6px; color: #667085; text-decoration: none; font-size: 0.9rem; padding: 24px 0 0; }
	.mslc-back-link:hover { color: #205ec8; text-decoration: none; }

	/* Hero */
	.mslc-detail-hero { display: grid; grid-template-columns: 1fr 360px; gap: 40px; align-items: center; padding: 30px 0 50px; }
	.mslc-detail-hero h1 { font-size: 2.5rem; font-weight: 700; color: #1d2939; margin: 0 0 14px; line-height: 1.2; }
	.mslc-detail-cat { font-size: 0.78rem; font-weight: 600; color: #205ec8; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px; display: block; }
	.mslc-detail-desc { font-size: 1rem; color: #475467; line-height: 1.6; margin: 0 0 18px; }
	.mslc-detail-stats { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border: 1.5px solid #205ec8; border-radius: 50px; font-size: 0.95rem; color: #205ec8; font-weight: 600; }
	.mslc-detail-stats strong { font-size: 1.05rem; }
	.mslc-detail-image { width: 100%; aspect-ratio: 1; overflow: hidden; }
	.mslc-detail-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
	.mslc-detail-image-fallback {
		width: 100%; height: 100%;
		background: linear-gradient(135deg, #205ec8 0%, #1d83ff 100%);
		display: flex; align-items: center; justify-content: center;
		color: #fff; font-size: 5rem; font-weight: 700;
	}
	@media (max-width: 900px) {
		.mslc-detail-hero { grid-template-columns: 1fr; gap: 24px; padding: 20px 0 40px; }
		.mslc-detail-hero h1 { font-size: 2rem; }
		.mslc-detail-image { aspect-ratio: 16/9; }
	}

	/* Divider — full viewport width */
	.mslc-detail-divider {
		border: 0; border-top: 1px solid #e4e7ec;
		width: 100vw; position: relative;
		left: 50%; right: 50%;
		margin-left: -50vw; margin-right: -50vw;
		margin-top: 0; margin-bottom: 36px;
	}

	/* Section heading */
	.mslc-detail-section-title { font-size: 1.65rem; font-weight: 700; color: #1d2939; margin: 0 0 16px; }

	/* Level filter (same as category filter on listing page) */
	.mslc-level-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 32px; padding: 0; list-style: none; }
	.mslc-filter-btn {
		display: inline-flex; align-items: center;
		padding: 11px 20px; border-radius: 45px; border: none;
		background: #f2f6fb; font-size: 14px; font-weight: 500;
		cursor: pointer; text-decoration: none; color: #205ec8;
		transition: background .2s, color .2s; line-height: 1;
	}
	.mslc-filter-btn:hover { background: #205ec8; color: #fff; text-decoration: none; }
	.mslc-filter-btn.active { background: #205ec8; color: #fff; }

	/* Course grid (uses MasterStudy course-card) */
	.mslc-detail-courses { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
	.mslc-detail-courses .masterstudy-course-card { width: 100%; max-width: 100%; }
	.mslc-detail-courses .masterstudy-course-card__image { width: 100% !important; height: auto !important; aspect-ratio: 16/9; object-fit: cover; }

	.mslc-detail-empty { text-align: center; padding: 60px 20px; color: #667085; background: #f9fafb; border: 1px dashed #e4e7ec; border-radius: 10px; }
	</style>

	<div class="mslc-detail-wrap">

		<a href="<?php echo esc_url( $careers_url ); ?>" class="mslc-back-link">&larr; Back to Careers</a>

		<div class="mslc-detail-hero">
			<div class="mslc-detail-hero-content">
				<?php if ( $karir->kategori_nama ) : ?>
					<span class="mslc-detail-cat"><?php echo esc_html( $karir->kategori_nama ); ?></span>
				<?php endif; ?>
				<h1><?php echo esc_html( $karir->nama ); ?></h1>
				<?php if ( $karir->deskripsi ) : ?>
					<p class="mslc-detail-desc"><?php echo wp_kses_post( $karir->deskripsi ); ?></p>
				<?php endif; ?>
				<div class="mslc-detail-stats">
					<strong><?php echo $total_courses; ?></strong>
					<span>course<?php echo $total_courses !== 1 ? 's' : ''; ?> available</span>
				</div>
			</div>
			<div class="mslc-detail-image">
				<?php if ( $karir->gambar ) {
					echo wp_get_attachment_image( $karir->gambar, 'large', false, ['alt'=>esc_attr($karir->nama)] );
				} else { ?>
					<div class="mslc-detail-image-fallback"><?php echo esc_html( strtoupper( mb_substr( $karir->nama, 0, 2 ) ) ); ?></div>
				<?php } ?>
			</div>
		</div>

		<hr class="mslc-detail-divider">

		<h2 class="mslc-detail-section-title">Recommended Courses</h2>

		<div class="mslc-level-filters">
			<?php foreach ( $level_options as $value => $label ) :
				$url    = $value
					? add_query_arg( [ 'karir_id' => $karir_id, 'level' => $value ] )
					: remove_query_arg( 'level' );
				$active = $level === $value ? 'active' : '';
			?>
				<a href="<?php echo esc_url( $url ); ?>" class="mslc-filter-btn <?php echo $active; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<?php if ( empty( $courses ) ) : ?>
			<div class="mslc-detail-empty">
				<p>No courses found<?php echo $level ? ' for this level.' : '.'; ?></p>
			</div>
		<?php else : ?>
			<div class="mslc-detail-courses">
				<?php
				$course_ids = wp_list_pluck( $courses, 'ID' );
				if ( class_exists( 'STM_LMS_Courses' ) && class_exists( 'STM_LMS_Templates' ) ) {
					$course_data = STM_LMS_Courses::get_courses_metas( $course_ids );
					foreach ( $course_data as $cd ) {
						STM_LMS_Templates::show_lms_template(
							'components/course/card/default',
							[
								'course'   => $cd,
								'public'   => true,
								'reviews'  => true,
								'wishlist' => false,
							]
						);
					}
				} else {
					foreach ( $courses as $course ) {
						printf(
							'<a href="%s" style="display:block;padding:16px;border:1px solid #e4e7ec;border-radius:8px;text-decoration:none;color:#1d2939">%s</a>',
							esc_url( get_permalink( $course->ID ) ),
							esc_html( $course->post_title )
						);
					}
				}
				?>
			</div>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}
