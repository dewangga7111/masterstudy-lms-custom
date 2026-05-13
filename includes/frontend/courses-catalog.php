<?php
/**
 * Open edX course catalog frontend.
 * Rendered via [openedx_catalog] shortcode.
 *
 * Attributes:
 *   org="KUniv"   → filter by org (optional)
 *   per_page="12" → courses per page (default 12)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'openedx_catalog', 'mslc_render_openedx_catalog' );

function mslc_render_openedx_catalog( $atts ) {
	$atts = shortcode_atts( [
		'org'      => '',
		'per_page' => 12,
	], $atts );

	$client = new MSLC_OpenEdX_Client();

	if ( ! $client->is_configured() ) {
		return '<p style="padding:40px 0;color:#667085;">Open edX is not configured yet.</p>';
	}

	$current_page = isset( $_GET['edx_page'] ) ? max( 1, (int) $_GET['edx_page'] ) : 1;
	$org_filter   = isset( $_GET['edx_org'] ) ? sanitize_text_field( $_GET['edx_org'] ) : sanitize_text_field( $atts['org'] );

	$result = $client->get_courses( $current_page, (int) $atts['per_page'] );

	if ( is_wp_error( $result ) ) {
		return '<p style="padding:40px 0;color:#d92d20;">Failed to load courses: ' . esc_html( $result->get_error_message() ) . '</p>';
	}

	$all_courses = $result['courses'];
	$count       = $result['count'];
	$num_pages   = $result['num_pages'];

	// Collect unique orgs for filter
	$orgs = array_unique( array_filter( array_column( $all_courses, 'org' ) ) );
	sort( $orgs );

	// Apply org filter client-side (single page) or server-side via query arg
	$courses = $org_filter
		? array_values( array_filter( $all_courses, fn( $c ) => $c['org'] === $org_filter ) )
		: $all_courses;

	ob_start();
	?>
	<style>
	.mslc-edx-wrap *,
	.mslc-edx-wrap *::before,
	.mslc-edx-wrap *::after { box-sizing: border-box; }
	.mslc-edx-wrap { font-family: inherit; padding: 0 0 60px; }

	/* Org filter pills */
	.mslc-edx-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 32px; padding: 0; list-style: none; }
	.mslc-edx-filter-btn {
		display: inline-flex; align-items: center;
		padding: 11px 20px; border-radius: 45px; border: none;
		background: #f2f6fb; font-size: 14px; font-weight: 500;
		cursor: pointer; text-decoration: none; color: #205ec8;
		transition: background .2s, color .2s; line-height: 1;
	}
	.mslc-edx-filter-btn:hover { background: #205ec8; color: #fff; text-decoration: none; }
	.mslc-edx-filter-btn.active { background: #205ec8; color: #fff; }

	/* Course grid */
	.mslc-edx-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
		gap: 24px;
	}

	/* Course card */
	.mslc-edx-card {
		border: 1px solid #e4e7ec;
		border-radius: 12px;
		overflow: hidden;
		background: #fff;
		text-decoration: none;
		display: flex; flex-direction: column;
		transition: box-shadow .2s, transform .2s;
	}
	.mslc-edx-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-2px); text-decoration: none; }

	.mslc-edx-card__image {
		width: 100%; aspect-ratio: 16/9; overflow: hidden;
		background: linear-gradient(135deg, #205ec8 0%, #1d83ff 100%);
		position: relative;
	}
	.mslc-edx-card__image img { width: 100%; height: 100%; object-fit: cover; display: block; }
	.mslc-edx-card__image-fallback {
		width: 100%; height: 100%;
		display: flex; align-items: center; justify-content: center;
		color: #fff; font-size: 2.5rem; font-weight: 700;
	}

	.mslc-edx-card__body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
	.mslc-edx-card__org { font-size: 0.75rem; font-weight: 600; color: #205ec8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
	.mslc-edx-card__title { font-size: 1rem; font-weight: 700; color: #1d2939; margin: 0 0 10px; line-height: 1.4; }
	.mslc-edx-card__desc { font-size: 0.875rem; color: #475467; line-height: 1.5; margin: 0 0 16px; flex: 1; }
	.mslc-edx-card__footer { display: flex; align-items: center; justify-content: space-between; margin-top: auto; }
	.mslc-edx-card__start { font-size: 0.8rem; color: #667085; }
	.mslc-edx-card__cta {
		display: inline-flex; align-items: center;
		padding: 8px 16px; background: #205ec8; color: #fff;
		border-radius: 20px; font-size: 0.8rem; font-weight: 600;
		text-decoration: none; transition: background .2s;
	}
	.mslc-edx-card__cta:hover { background: #1a4fa8; color: #fff; text-decoration: none; }

	/* Empty state */
	.mslc-edx-empty { text-align: center; padding: 60px 20px; color: #667085; background: #f9fafb; border: 1px dashed #e4e7ec; border-radius: 10px; }

	/* Pagination */
	.mslc-edx-pagination { display: flex; flex-wrap: wrap; gap: 8px; margin: 40px 0 0; justify-content: center; }
	.mslc-edx-pagination a, .mslc-edx-pagination span {
		padding: 9px 16px; border: 1px solid #e4e7ec; border-radius: 6px;
		text-decoration: none; color: #205ec8; background: #fff; font-size: 14px;
		transition: background .2s, color .2s;
	}
	.mslc-edx-pagination a:hover { background: #205ec8; color: #fff; border-color: #205ec8; text-decoration: none; }
	.mslc-edx-pagination span.current { background: #205ec8; color: #fff; border-color: #205ec8; }
	</style>

	<div class="mslc-edx-wrap">

		<?php if ( ! empty( $orgs ) ) : ?>
			<div class="mslc-edx-filters">
				<a href="<?php echo esc_url( remove_query_arg( 'edx_org' ) ); ?>"
				   class="mslc-edx-filter-btn <?php echo ! $org_filter ? 'active' : ''; ?>">All</a>
				<?php foreach ( $orgs as $org ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'edx_org', $org ) ); ?>"
					   class="mslc-edx-filter-btn <?php echo $org_filter === $org ? 'active' : ''; ?>">
						<?php echo esc_html( $org ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( empty( $courses ) ) : ?>
			<div class="mslc-edx-empty">
				<p>No courses found<?php echo $org_filter ? ' for this organization.' : '.'; ?></p>
			</div>
		<?php else : ?>
			<div class="mslc-edx-grid">
				<?php foreach ( $courses as $course ) : ?>
					<a href="<?php echo esc_url( $course['course_url'] ); ?>" class="mslc-edx-card" target="_blank" rel="noopener">
						<div class="mslc-edx-card__image">
							<?php if ( $course['thumbnail'] ) : ?>
								<img src="<?php echo esc_url( $course['thumbnail'] ); ?>" alt="<?php echo esc_attr( $course['name'] ); ?>">
							<?php else : ?>
								<div class="mslc-edx-card__image-fallback">
									<?php echo esc_html( strtoupper( mb_substr( $course['org'], 0, 2 ) ) ); ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="mslc-edx-card__body">
							<?php if ( $course['org'] ) : ?>
								<span class="mslc-edx-card__org"><?php echo esc_html( $course['org'] ); ?></span>
							<?php endif; ?>
							<h3 class="mslc-edx-card__title"><?php echo esc_html( $course['name'] ); ?></h3>
							<?php if ( $course['short_description'] ) : ?>
								<p class="mslc-edx-card__desc"><?php echo esc_html( wp_trim_words( $course['short_description'], 20 ) ); ?></p>
							<?php endif; ?>
							<div class="mslc-edx-card__footer">
								<span class="mslc-edx-card__start">
									<?php echo $course['start_display'] ? 'Starts ' . esc_html( $course['start_display'] ) : ''; ?>
								</span>
								<span class="mslc-edx-card__cta">View Course →</span>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if ( $num_pages > 1 && ! $org_filter ) : ?>
				<div class="mslc-edx-pagination">
					<?php if ( $current_page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'edx_page', $current_page - 1 ) ); ?>">&laquo; Previous</a>
					<?php endif; ?>

					<?php for ( $i = 1; $i <= $num_pages; $i++ ) : ?>
						<?php if ( $i === $current_page ) : ?>
							<span class="current"><?php echo $i; ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( 'edx_page', $i ) ); ?>"><?php echo $i; ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $current_page < $num_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'edx_page', $current_page + 1 ) ); ?>">Next &raquo;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}
