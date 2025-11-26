<?php
/**
 * Template for displaying single Review posts
 *
 * Post type: review
 */

get_header();
?>

<main id="site-content" class="review-single">

	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();

			// ---- ACF フィールド取得 ----
			$target_name  = function_exists( 'get_field' ) ? get_field( 'target_name' ) : '';
			$catch_copy   = function_exists( 'get_field' ) ? get_field( 'catch_copy' ) : '';
			$rating       = function_exists( 'get_field' ) ? get_field( 'rating' ) : '';
			$official_url = function_exists( 'get_field' ) ? get_field( 'official_url' ) : '';
			$source_url   = function_exists( 'get_field' ) ? get_field( 'source_url' ) : '';

			// ---- 閲覧統計 ----
			$view_stats = null;
			if ( function_exists( 'get_review_view_stats' ) ) {
				$view_stats = get_review_view_stats( get_the_ID() );
			}
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'review-entry' ); ?>>

				<header class="review-header">
					<nav class="review-breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">トップ</a> &gt;
						<a href="<?php echo esc_url( get_post_type_archive_link( 'review' ) ); ?>">口コミ・レビュー一覧</a> &gt;
						<span><?php the_title(); ?></span>
					</nav>

					<h1 class="review-title">
						<?php echo esc_html( $target_name ? $target_name : get_the_title() ); ?>
					</h1>

					<?php if ( $catch_copy ) : ?>
						<p class="review-catch-copy"><?php echo esc_html( $catch_copy ); ?></p>
					<?php endif; ?>

					<div class="review-meta">
						<span class="review-date">公開日：<?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?></span>
						<span class="review-updated">最終更新日：<?php echo esc_html( get_the_modified_date( 'Y-m-d' ) ); ?></span>
					</div>

					<?php if ( $rating !== '' && $rating !== null ) : ?>
						<p class="review-rating">
							<strong>評価：</strong><?php echo esc_html( $rating ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $official_url || $source_url ) : ?>
						<div class="review-links">
							<?php if ( $official_url ) : ?>
								<p class="review-official-url">
									<a href="<?php echo esc_url( $official_url ); ?>" target="_blank" rel="noopener nofollow sponsored">
										公式サイトはこちら
									</a>
								</p>
							<?php endif; ?>

							<?php if ( $source_url ) : ?>
								<p class="review-source-url">
									出典：
									<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">
										元ページを表示
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				</header>

				<section class="review-content section-block">
					<?php the_content(); ?>
				</section>

				<footer class="review-footer section-block">

					<?php if ( is_array( $view_stats ) ) : ?>
						<section class="review-views">
							<h2 class="section-title">このページの閲覧データ</h2>
							<ul class="review-view-stats">
								<?php if ( isset( $view_stats['total_views'] ) ) : ?>
									<li>総閲覧数：<?php echo esc_html( (int) $view_stats['total_views'] ); ?> 回</li>
								<?php endif; ?>

								<?php if ( isset( $view_stats['unique_ip_views'] ) ) : ?>
									<li>ユニークIP数：<?php echo esc_html( (int) $view_stats['unique_ip_views'] ); ?> IP</li>
								<?php endif; ?>

								<?php if ( isset( $view_stats['current_ip_views'] ) ) : ?>
									<li>あなたのIPからの閲覧回数：
										<?php echo esc_html( (int) $view_stats['current_ip_views'] ); ?> 回
									</li>
								<?php endif; ?>

								<?php if ( ! empty( $view_stats['last_viewed_at'] ) ) : ?>
									<li>最後に閲覧された日時：
										<?php echo esc_html( $view_stats['last_viewed_at'] ); ?>
									</li>
								<?php endif; ?>
							</ul>
						</section>
					<?php endif; ?>

				</footer>

			</article>

		<?php
		endwhile;
	endif;
	?>

</main>

<?php
get_footer();
