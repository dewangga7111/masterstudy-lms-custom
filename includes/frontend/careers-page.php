<?php
/**
 * Careers page (frontend portal).
 * Rendered via [careers_page] shortcode — listing with category filters.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'careers_page', 'mslc_render_careers_page' );

function mslc_render_careers_page( $atts ) {
	global $wpdb;

	$kategoris  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}karir_kategori ORDER BY nama ASC" );
	$active_cat = isset( $_GET['kategori'] ) ? absint( $_GET['kategori'] ) : 0;

	$where = $active_cat ? $wpdb->prepare( "WHERE k.kategori_id = %d", $active_cat ) : '';

	$karirs = $wpdb->get_results(
		"SELECT k.*, kk.nama AS kategori_nama
		 FROM {$wpdb->prefix}karir k
		 LEFT JOIN {$wpdb->prefix}karir_kategori kk ON k.kategori_id = kk.id
		 $where
		 ORDER BY k.nama ASC"
	);

	ob_start();
	?>
	<style>
	.mslc-careers-wrap *,
	.mslc-careers-wrap *::before,
	.mslc-careers-wrap *::after { box-sizing: border-box; }
	.mslc-careers-wrap { font-family: inherit; max-width: 100%; padding: 0 0 60px; }
	.mslc-careers-hero { padding: 40px 0 20px; }
	.mslc-careers-hero h1 { font-size: 2.5rem; font-weight: 700; margin: 0 0 10px; color: #1d2939; }
	.mslc-careers-hero p { font-size: 1rem; color: #667085; margin: 0; line-height: 1.6; }

	.mslc-careers-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 28px 0 36px; padding: 0; list-style: none; }
	.mslc-filter-btn {
		display: inline-flex; align-items: center;
		padding: 11px 20px; border-radius: 45px; border: none;
		background: #f2f6fb; font-size: 14px; font-weight: 500;
		cursor: pointer; text-decoration: none; color: #205ec8;
		transition: background .2s, color .2s; line-height: 1;
	}
	.mslc-filter-btn:hover { background: #205ec8; color: #fff; text-decoration: none; }
	.mslc-filter-btn.active { background: #205ec8; color: #fff; }

	/* Career rows (horizontal cards) */
	.mslc-careers-list { display: flex; flex-direction: column; gap: 24px; }
	.mslc-career-row {
		background: #fff; border: 1px solid #e4e7ec; border-radius: 12px;
		overflow: hidden; display: grid; grid-template-columns: 240px 320px 1fr; gap: 0;
		transition: box-shadow .25s; align-items: stretch;
	}
	.mslc-career-row:hover { box-shadow: 0 8px 28px rgba(32,94,200,0.10); }
	@media (max-width: 1100px) {
		.mslc-career-row { grid-template-columns: 200px 1fr; }
		.mslc-career-row .mslc-course-rail-wrap { grid-column: 1 / -1; padding: 0 28px 28px; }
	}
	@media (max-width: 700px) {
		.mslc-career-row { grid-template-columns: 1fr; }
		.mslc-career-image { height: 200px; }
		.mslc-career-row .mslc-course-rail-wrap { padding: 0 22px 22px; }
	}

	/* Left column: career image (full height) */
	.mslc-career-image {
		position: relative; width: 100%; height: 100%;
		background: linear-gradient(135deg, #205ec8 0%, #1d83ff 100%);
		display: flex; align-items: center; justify-content: center;
		color: #fff; font-weight: 700; font-size: 3rem; overflow: hidden;
	}
	.mslc-career-image img {
		position: absolute; inset: 0;
		width: 100%; height: 100%; object-fit: cover; display: block;
	}

	/* Middle column: career info */
	.mslc-career-info { display: flex; flex-direction: column; gap: 10px; padding: 28px; }
	.mslc-career-cat { font-size: 0.72rem; font-weight: 600; color: #205ec8; text-transform: uppercase; letter-spacing: .6px; }
	.mslc-career-title { font-size: 1.4rem; font-weight: 700; color: #1d2939; margin: 6px 0 4px; line-height: 1.3; }
	.mslc-career-title a { color: inherit; text-decoration: none; transition: color .2s; }
	.mslc-career-title a:hover { color: #205ec8; text-decoration: none; }
	.mslc-career-desc { font-size: 0.9rem; color: #475467; line-height: 1.55; margin: 0;
		display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
	}
	.mslc-career-meta { font-size: 0.85rem; color: #667085; margin-top: auto; padding-top: 12px; }
	.mslc-career-meta strong { color: #1d2939; }
	.mslc-career-cta {
		display: inline-flex; align-items: center; justify-content: center;
		padding: 11px 22px; background: #205ec8; color: #fff !important;
		border-radius: 6px; font-size: 0.9rem; font-weight: 600;
		text-decoration: none; transition: background .2s; align-self: flex-start;
		margin-top: 8px;
	}
	.mslc-career-cta:hover { background: #1a4ea3; text-decoration: none; }

	/* Right column: course preview rail */
	.mslc-course-rail-wrap { position: relative; min-width: 0; padding: 28px; display: flex; align-items: center; }
	.mslc-course-rail {
		display: flex; gap: 16px; overflow-x: auto;
		scroll-behavior: smooth; padding-bottom: 4px; width: 100%;
		scrollbar-width: thin; scrollbar-color: #d0d5dd transparent;
	}
	.mslc-course-rail::-webkit-scrollbar { height: 6px; }
	.mslc-course-rail::-webkit-scrollbar-thumb { background: #d0d5dd; border-radius: 4px; }

	/* Chevron buttons */
	.mslc-rail-btn,
	.mslc-rail-btn:hover,
	.mslc-rail-btn:focus,
	.mslc-rail-btn:active {
		appearance: none; -webkit-appearance: none;
		position: absolute; top: 50%; transform: translateY(-50%);
		width: 38px; height: 38px; border-radius: 50% !important;
		background: #fff !important; background-image: none !important;
		border: 1px solid #e4e7ec !important;
		box-shadow: 0 2px 10px rgba(0,0,0,0.10);
		cursor: pointer; z-index: 3;
		display: flex; align-items: center; justify-content: center;
		color: #205ec8 !important; padding: 0;
		outline: none; text-decoration: none;
		transition: opacity .2s, background .15s, transform .15s, border-color .15s;
	}
	.mslc-rail-btn { opacity: 0; pointer-events: none; }
	.mslc-rail-btn:hover { background: #f2f6fb !important; border-color: #205ec8 !important; transform: translateY(-50%) scale(1.05); }
	.mslc-rail-btn svg { width: 18px; height: 18px; stroke: #205ec8; }
	.mslc-rail-btn-left  { left: 12px; }
	.mslc-rail-btn-right { right: 12px; }
	.mslc-course-rail-wrap.has-overflow-left  .mslc-rail-btn-left,
	.mslc-course-rail-wrap.has-overflow-right .mslc-rail-btn-right { opacity: 1; pointer-events: auto; }

	/* Shadow fade */
	.mslc-rail-fade {
		position: absolute; top: 28px; bottom: 28px;
		width: 60px; pointer-events: none; z-index: 2;
		opacity: 0; transition: opacity .25s;
	}
	.mslc-rail-fade-left  { left: 0;  background: linear-gradient(to right, #fff 20%, transparent); }
	.mslc-rail-fade-right { right: 0; background: linear-gradient(to left,  #fff 20%, transparent); }
	.mslc-course-rail-wrap.has-overflow-left  .mslc-rail-fade-left  { opacity: 1; }
	.mslc-course-rail-wrap.has-overflow-right .mslc-rail-fade-right { opacity: 1; }
	.mslc-course-mini {
		flex: 0 0 180px; background: #f9fafb; border-radius: 10px;
		overflow: hidden; display: flex; flex-direction: column;
		text-decoration: none; color: inherit;
		border: 1px solid #f2f4f7; transition: transform .2s, box-shadow .2s;
	}
	.mslc-course-mini:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.08); text-decoration: none; }
	.mslc-course-mini-img {
		width: 100%; height: 110px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
		display: flex; align-items: center; justify-content: center; overflow: hidden;
	}
	.mslc-course-mini-img img { width: 100%; height: 100%; object-fit: cover; }
	.mslc-course-mini-icon { font-size: 2rem; }
	.mslc-course-mini-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
	.mslc-course-mini-title { font-size: 0.85rem; font-weight: 600; color: #1d2939; line-height: 1.35;
		display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0;
	}

	.mslc-no-courses-mini {
		display: flex; align-items: center; justify-content: center;
		min-height: 130px; color: #98a2b3; font-size: 0.9rem;
		background: #f9fafb; border-radius: 10px; padding: 20px; width: 100%;
	}
	.mslc-no-careers { text-align: center; padding: 80px 20px; color: #667085; font-size: 1rem; background: #f9fafb; border-radius: 8px; border: 1px dashed #e4e7ec; }
	</style>

	<div class="mslc-careers-wrap">
		<div class="mslc-careers-hero">
			<h1>Careers</h1>
			<p>Start, switch, or grow your career with guidance from Masterstudy.</p>
		</div>

		<div class="mslc-careers-filters">
			<?php
			$all_url    = remove_query_arg('kategori');
			$all_active = $active_cat === 0 ? 'active' : '';
			echo '<a href="' . esc_url($all_url) . '" class="mslc-filter-btn ' . $all_active . '">All</a>';
			foreach ( $kategoris as $kat ) :
				$url       = add_query_arg('kategori', $kat->id);
				$is_active = $active_cat === (int)$kat->id ? 'active' : '';
			?>
				<a href="<?php echo esc_url($url); ?>" class="mslc-filter-btn <?php echo $is_active; ?>"><?php echo esc_html($kat->nama); ?></a>
			<?php endforeach; ?>
		</div>

		<?php if ( empty($karirs) ) : ?>
			<div class="mslc-no-careers"><p>No careers available yet. Add them via the <strong>Careers</strong> menu in WP Admin.</p></div>
		<?php else : ?>
			<div class="mslc-careers-list">
				<?php
				$detail_base = apply_filters( 'mslc_career_detail_url', home_url( '/career-detail/' ) );
				foreach ( $karirs as $karir ) :
					$courses = get_posts( [
						'post_type'      => 'stm-courses',
						'post_status'    => 'publish',
						'posts_per_page' => 5,
						'meta_query'     => [ [ 'key' => 'karir_id', 'value' => $karir->id, 'compare' => '=' ] ],
						'orderby'        => 'title',
						'order'          => 'ASC',
					] );
					$total_courses = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'karir_id' AND meta_value = %d", $karir->id
					) );
					$initial    = strtoupper( mb_substr( $karir->nama, 0, 1 ) );
					$detail_url = add_query_arg( 'karir_id', $karir->id, $detail_base );
				?>
				<article class="mslc-career-row">
					<div class="mslc-career-image">
						<?php if ( $karir->gambar ) {
							echo wp_get_attachment_image( $karir->gambar, 'large', false, ['alt'=>esc_attr($karir->nama)] );
						} else {
							echo esc_html( $initial );
						} ?>
					</div>

					<div class="mslc-career-info">
						<?php if ( $karir->kategori_nama ) : ?>
							<div class="mslc-career-cat"><?php echo esc_html( $karir->kategori_nama ); ?></div>
						<?php endif; ?>

						<h2 class="mslc-career-title">
							<a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $karir->nama ); ?></a>
						</h2>

						<?php if ( $karir->deskripsi ) : ?>
							<p class="mslc-career-desc"><?php echo esc_html( wp_strip_all_tags( $karir->deskripsi ) ); ?></p>
						<?php endif; ?>

						<div class="mslc-career-meta">
							<strong><?php echo $total_courses; ?></strong> course<?php echo $total_courses !== 1 ? 's' : ''; ?> available
						</div>
					</div>

					<div class="mslc-course-rail-wrap">
						<?php if ( empty( $courses ) ) : ?>
							<div class="mslc-no-courses-mini">No courses yet for this career.</div>
						<?php else : ?>
							<button class="mslc-rail-btn mslc-rail-btn-left" type="button" aria-label="Scroll left">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
							</button>
							<div class="mslc-rail-fade mslc-rail-fade-left"></div>
							<div class="mslc-course-rail">
								<?php foreach ( $courses as $i => $course ) :
									$thumb = get_the_post_thumbnail_url( $course->ID, 'medium' );
								?>
								<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" class="mslc-course-mini">
									<div class="mslc-course-mini-img">
										<?php if ( $thumb ) : ?>
											<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $course->post_title ); ?>">
										<?php else : ?>
											<span class="mslc-course-mini-icon">&#128218;</span>
										<?php endif; ?>
									</div>
									<div class="mslc-course-mini-body">
										<h4 class="mslc-course-mini-title"><?php echo esc_html( $course->post_title ); ?></h4>
									</div>
								</a>
								<?php endforeach; ?>
							</div>
							<div class="mslc-rail-fade mslc-rail-fade-right"></div>
							<button class="mslc-rail-btn mslc-rail-btn-right" type="button" aria-label="Scroll right">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
							</button>
						<?php endif; ?>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<script>
	(function(){
		var wraps = document.querySelectorAll('.mslc-course-rail-wrap');
		wraps.forEach(function(wrap){
			var rail = wrap.querySelector('.mslc-course-rail');
			if (!rail) return;
			var btnLeft  = wrap.querySelector('.mslc-rail-btn-left');
			var btnRight = wrap.querySelector('.mslc-rail-btn-right');

			function update(){
				var canLeft  = rail.scrollLeft > 5;
				var canRight = rail.scrollLeft < (rail.scrollWidth - rail.clientWidth - 5);
				wrap.classList.toggle('has-overflow-left',  canLeft);
				wrap.classList.toggle('has-overflow-right', canRight);
			}

			function scroll(dir){
				var step = rail.clientWidth * 0.7;
				rail.scrollBy({ left: dir * step, behavior: 'smooth' });
			}

			if (btnLeft)  btnLeft.addEventListener('click',  function(){ scroll(-1); });
			if (btnRight) btnRight.addEventListener('click', function(){ scroll(1);  });
			rail.addEventListener('scroll', update, { passive: true });
			window.addEventListener('resize', update);
			update();
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}
