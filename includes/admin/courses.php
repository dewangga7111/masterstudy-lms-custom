<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function mslc_page_openedx_courses() {
	$client = new MSLC_OpenEdX_Client();

	if ( ! $client->is_configured() ) {
		echo '<div class="wrap"><h1>Open edX — Courses</h1>';
		echo '<div class="notice notice-warning"><p>Open edX base URL is not configured. <a href="' . esc_url( admin_url( 'admin.php?page=mslc-openedx-settings' ) ) . '">Go to Settings →</a></p></div>';
		echo '</div>';
		return;
	}

	$current_page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$result       = $client->get_courses( $current_page, 20 );

	if ( is_wp_error( $result ) ) {
		echo '<div class="wrap"><h1>Open edX — Courses</h1>';
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		echo '</div>';
		return;
	}

	$courses   = $result['courses'];
	$count     = $result['count'];
	$num_pages = $result['num_pages'];
	?>
	<div class="wrap">
		<h1>Open edX — Courses <span style="font-size:13px;font-weight:400;color:#646970;">(<?php echo $count; ?> total)</span></h1>

		<style>
		.mslc-edx-table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: 6px; overflow: hidden; }
		.mslc-edx-table th { background: #f6f7f7; color: #1d2327; font-weight: 600; text-align: left; padding: 12px 16px; border-bottom: 1px solid #e0e0e0; }
		.mslc-edx-table td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #3c434a; }
		.mslc-edx-table tr:last-child td { border-bottom: none; }
		.mslc-edx-table tr:hover td { background: #f9f9f9; }
		.mslc-edx-thumb { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; background: #e4e7ec; display: block; }
		.mslc-edx-thumb-placeholder { width: 80px; height: 50px; border-radius: 4px; background: linear-gradient(135deg, #205ec8, #1d83ff); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 13px; }
		.mslc-edx-org { display: inline-block; padding: 2px 8px; background: #f0f4ff; color: #205ec8; border-radius: 12px; font-size: 12px; font-weight: 600; }
		.mslc-edx-pagination { margin: 20px 0; display: flex; align-items: center; gap: 8px; }
		.mslc-edx-pagination a, .mslc-edx-pagination span { padding: 6px 14px; border: 1px solid #e0e0e0; border-radius: 4px; text-decoration: none; color: #205ec8; background: #fff; font-size: 13px; }
		.mslc-edx-pagination span.current { background: #205ec8; color: #fff; border-color: #205ec8; }
		</style>

		<?php if ( empty( $courses ) ) : ?>
			<p>No courses found.</p>
		<?php else : ?>
			<table class="mslc-edx-table">
				<thead>
					<tr>
						<th style="width:96px;">Thumbnail</th>
						<th>Course Name</th>
						<th>Org</th>
						<th>Number</th>
						<th>Start</th>
						<th>Course ID</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $courses as $course ) : ?>
						<tr>
							<td>
								<?php if ( $course['thumbnail'] ) : ?>
									<img src="<?php echo esc_url( $course['thumbnail'] ); ?>" class="mslc-edx-thumb" alt="">
								<?php else : ?>
									<div class="mslc-edx-thumb-placeholder"><?php echo esc_html( strtoupper( mb_substr( $course['org'], 0, 2 ) ) ); ?></div>
								<?php endif; ?>
							</td>
							<td>
								<strong>
									<a href="<?php echo esc_url( $course['course_url'] ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html( $course['name'] ); ?>
									</a>
								</strong>
								<?php if ( $course['short_description'] ) : ?>
									<p style="margin:4px 0 0;color:#646970;font-size:12px;"><?php echo esc_html( wp_trim_words( $course['short_description'], 15 ) ); ?></p>
								<?php endif; ?>
							</td>
							<td><span class="mslc-edx-org"><?php echo esc_html( $course['org'] ); ?></span></td>
							<td><?php echo esc_html( $course['number'] ); ?></td>
							<td><?php echo esc_html( $course['start_display'] ?: '—' ); ?></td>
							<td><code style="font-size:11px;"><?php echo esc_html( $course['id'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $num_pages > 1 ) : ?>
				<div class="mslc-edx-pagination">
					<?php if ( $current_page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">&laquo; Previous</a>
					<?php endif; ?>

					<?php for ( $i = 1; $i <= $num_pages; $i++ ) : ?>
						<?php if ( $i === $current_page ) : ?>
							<span class="current"><?php echo $i; ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo $i; ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $current_page < $num_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>">Next &raquo;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
