<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wsp_admin_menu(): void {
	add_menu_page( 'Social Publisher', 'Social Publisher', 'manage_options', 'wsp-accounts', 'wsp_accounts_page', 'dashicons-share', 26 );
	add_submenu_page( 'wsp-accounts', 'Hesaplar', 'Accounts', 'manage_options', 'wsp-accounts', 'wsp_accounts_page' );
	add_submenu_page( 'wsp-accounts', 'Otomatik paylaşım', 'WP Auto Post', 'manage_options', 'wsp-auto-post', 'wsp_placeholder_page' );
	add_submenu_page( 'wsp-accounts', 'Yazı planları', 'WP Post Schedules', 'manage_options', 'wsp-schedules', 'wsp_schedules_page' );
	add_submenu_page( 'wsp-accounts', 'Şimdi paylaş', 'Share Now', 'manage_options', 'wsp-share-now', 'wsp_share_now_page' );
	add_submenu_page( 'wsp-accounts', 'Takvim', 'Calendar', 'manage_options', 'wsp-calendar', 'wsp_calendar_page' );
	add_submenu_page( 'wsp-accounts', 'Şablonlar', 'Templates', 'manage_options', 'wsp-templates', 'wsp_templates_page' );
	add_submenu_page( 'wsp-accounts', 'Kayıtlar', 'Logs', 'manage_options', 'wsp-logs', 'wsp_placeholder_page' );
}
add_action( 'admin_menu', 'wsp_admin_menu' );

function wsp_admin_assets( string $hook_suffix ): void {
	if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) {
		return;
	}
	$page = sanitize_key( wp_unslash( $_GET['page'] ) );
	if ( ! in_array( $page, array( 'wsp-templates', 'wsp-accounts' ), true ) ) {
		return;
	}
	$base_url = plugin_dir_url( dirname( __DIR__ ) . '/wp-social-publisher.php' );
	wp_enqueue_style( 'wsp-templates', $base_url . 'assets/templates.css', array(), '0.5.0' );
	if ( 'wsp-templates' === $page ) {
		wp_enqueue_script( 'wsp-templates', $base_url . 'assets/templates.js', array(), '0.3.0', true );
	}
}
add_action( 'admin_enqueue_scripts', 'wsp_admin_assets' );

function wsp_notice(): void {
	echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Deneme sürümü: Yalnızca Facebook Sayfasına elle onaylanan gönderim kullanılabilir. Otomatik paylaşım ve gönderim kuyruğu yoktur; tarihler yalnızca plan taslağıdır.', 'wp-social-publisher' ) . '</p></div>';
}

function wsp_placeholder_page(): void {
	$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$pages = array(
		'wsp-accounts'  => array( 'Accounts / Hesaplar', 'Hesap bağlantısı henüz eklenmedi. Bu sayfaya şifre veya erişim anahtarı girilmez.' ),
		'wsp-auto-post' => array( 'WP Auto Post / Otomatik paylaşım', 'Otomatik paylaşım henüz kullanılamıyor.' ),
		'wsp-share-now' => array( 'Share Now / Şimdi paylaş', 'Anında paylaşım henüz kullanılamıyor.' ),
		'wsp-logs'      => array( 'Logs / Kayıtlar', 'Kalıcı gönderim geçmişi henüz eklenmedi. Facebook gönderiminin sonucu Şimdi paylaş ekranında gösterilir.' ),
	);
	if ( ! isset( $pages[ $page ] ) ) {
		return;
	}
	echo '<div class="wrap"><h1>' . esc_html( $pages[ $page ][0] ) . '</h1>';
	wsp_notice();
	echo '<p>' . esc_html( $pages[ $page ][1] ) . '</p></div>';
}

function wsp_templates_page(): void {
	$platforms = wsp_platforms();
	$first_platform = array_key_first( $platforms );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Templates / Paylaşım şablonları', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<p><?php esc_html_e( 'Soldan platformu seçin, orta bölümdeki alanları düzenleyin ve sağdaki önizlemede sonucu görün. Yazı düzenleyicisindeki özel metin, varsayılan şablonun yerine kullanılır.', 'wp-social-publisher' ); ?></p>
		<p><?php esc_html_e( 'Başlık, özet, bağlantı ve yazı etiketleri seçtiğiniz platforma gönderim aşamasında yazıdan alınacak şekilde yer tutucularla gösterilir. Şablona emoji, ek açıklama veya kendi etiketlerinizi normal metin olarak ekleyebilirsiniz. Görsel kaynağı öne çıkan görseldir; bu deneme sürümü görsel göndermez.', 'wp-social-publisher' ); ?></p>
		<?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Şablonlar kaydedildi.', 'wp-social-publisher' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wsp-template-builder">
			<input type="hidden" name="action" value="wsp_save_templates">
			<?php wp_nonce_field( 'wsp_save_templates' ); ?>
			<div class="wsp-template-grid">
				<nav class="wsp-template-platforms" aria-label="<?php esc_attr_e( 'Platformlar', 'wp-social-publisher' ); ?>">
					<h2><?php esc_html_e( 'Platform', 'wp-social-publisher' ); ?></h2>
					<?php foreach ( $platforms as $key => $label ) : ?>
						<button type="button" class="wsp-platform-tab<?php echo $key === $first_platform ? ' is-active' : ''; ?>" data-platform="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $key === $first_platform ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</nav>
				<div class="wsp-template-fields">
					<h2><?php esc_html_e( 'Şablon içeriği', 'wp-social-publisher' ); ?></h2>
					<?php foreach ( $platforms as $key => $label ) : ?>
						<?php $fields = wsp_template_fields( $key ); ?>
						<section class="wsp-platform-fields" data-platform="<?php echo esc_attr( $key ); ?>" <?php echo $key === $first_platform ? '' : 'hidden'; ?>>
							<h3><?php echo esc_html( $label ); ?></h3>
							<p><label for="wsp-content-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'İçerik', 'wp-social-publisher' ); ?></label>
								<textarea id="wsp-content-<?php echo esc_attr( $key ); ?>" name="templates[<?php echo esc_attr( $key ); ?>][content]" rows="5" class="large-text wsp-template-input" data-field="content"><?php echo esc_textarea( $fields['content'] ); ?></textarea>
							</p>
							<p><label for="wsp-url-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'URL', 'wp-social-publisher' ); ?></label>
								<input id="wsp-url-<?php echo esc_attr( $key ); ?>" type="text" name="templates[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $fields['url'] ); ?>" class="large-text wsp-template-input" data-field="url">
							</p>
							<p><label for="wsp-tags-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Etiketler', 'wp-social-publisher' ); ?></label>
								<input id="wsp-tags-<?php echo esc_attr( $key ); ?>" type="text" name="templates[<?php echo esc_attr( $key ); ?>][tags]" value="<?php echo esc_attr( $fields['tags'] ); ?>" class="large-text wsp-template-input" data-field="tags">
							</p>
							<details class="wsp-smart-tags">
								<summary><?php esc_html_e( 'Akıllı etiketleri göster', 'wp-social-publisher' ); ?></summary>
								<p><?php esc_html_e( 'Etiketi eklemek istediğiniz alana tıklayın; ekleme imlecin olduğu yere yapılır.', 'wp-social-publisher' ); ?></p>
								<div class="wsp-smart-tag-buttons">
									<?php foreach ( array( '{title}' => 'Başlık', '{excerpt}' => 'Özet', '{url}' => 'Yazı bağlantısı', '{tags}' => 'Yazı etiketleri', '{site_title}' => 'Site adı' ) as $tag => $tag_label ) : ?>
										<button type="button" class="button button-secondary" data-smart-tag="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $tag ); ?> <span class="screen-reader-text"><?php echo esc_html( $tag_label ); ?></span></button>
									<?php endforeach; ?>
								</div>
							</details>
						</section>
					<?php endforeach; ?>
				</div>
				<aside class="wsp-template-preview" aria-live="polite">
					<h2><?php esc_html_e( 'Önizleme', 'wp-social-publisher' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Örnek yazı bilgileriyle canlı önizleme.', 'wp-social-publisher' ); ?></p>
					<div class="wsp-preview-card">
						<div class="wsp-preview-image"><?php esc_html_e( 'Öne çıkan görsel', 'wp-social-publisher' ); ?></div>
						<p class="wsp-preview-title"><?php esc_html_e( 'Örnek yazı başlığı', 'wp-social-publisher' ); ?></p>
						<pre class="wsp-preview-text" aria-label="<?php esc_attr_e( 'Paylaşım metni önizlemesi', 'wp-social-publisher' ); ?>"></pre>
					</div>
					<p class="description"><?php esc_html_e( 'Şablon önizlemesi, gerçek yazıdaki başlık, özet, bağlantı ve etiketlerin yerini örnek verilerle gösterir.', 'wp-social-publisher' ); ?></p>
				</aside>
			</div>
			<?php submit_button( __( 'Şablonları kaydet', 'wp-social-publisher' ) ); ?>
		</form>
	</div>
	<?php
}

function wsp_save_templates(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'wp-social-publisher' ) );
	}
	check_admin_referer( 'wsp_save_templates' );
	$submitted = isset( $_POST['templates'] ) && is_array( $_POST['templates'] ) ? wp_unslash( $_POST['templates'] ) : array();
	$templates = array();
	foreach ( wsp_platforms() as $key => $label ) {
		$platform_fields = isset( $submitted[ $key ] ) && is_array( $submitted[ $key ] ) ? $submitted[ $key ] : array();
		$templates[ $key ] = array();
		foreach ( array( 'content', 'url', 'tags' ) as $field ) {
			$templates[ $key ][ $field ] = isset( $platform_fields[ $field ] ) && is_string( $platform_fields[ $field ] )
				? sanitize_textarea_field( $platform_fields[ $field ] )
				: '';
		}
	}
	update_option( 'wsp_templates', $templates );
	wp_safe_redirect( admin_url( 'admin.php?page=wsp-templates&updated=1' ) );
	exit;
}
add_action( 'admin_post_wsp_save_templates', 'wsp_save_templates' );

function wsp_selected_platforms( int $post_id ): array {
	$selected = array();
	foreach ( wsp_platforms() as $key => $label ) {
		if ( '1' === get_post_meta( $post_id, '_wsp_' . $key . '_enabled', true ) ) {
			$selected[] = $label;
		}
	}
	return $selected;
}

function wsp_scheduled_posts( ?string $start = null, ?string $end = null, int $page = 1 ): WP_Query {
	$date_query = array(
		'key'     => '_wsp_scheduled_at',
		'value'   => '',
		'compare' => '>',
	);
	if ( null !== $start && null !== $end ) {
		$date_query = array(
			'relation' => 'AND',
			array( 'key' => '_wsp_scheduled_at', 'value' => $start, 'compare' => '>=' ),
			array( 'key' => '_wsp_scheduled_at', 'value' => $end, 'compare' => '<' ),
		);
	}
	return new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'posts_per_page' => null === $start ? 50 : -1,
			'paged'          => $page,
			'meta_key'       => '_wsp_scheduled_at',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( $date_query ),
		)
	);
}

function wsp_schedules_page(): void {
	$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$query = wsp_scheduled_posts( null, null, $page );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'WP Post Schedules / Yazı planları', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<p><?php esc_html_e( 'Bu liste, yazılara kaydedilen plan tarihlerini gösterir. Platform seçilmemişse plan eksiktir. Yazı durumunu ve site saat dilimini kontrol edin.', 'wp-social-publisher' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Tarih ve saat', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Yazı', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Yazı durumu', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Platformlar', 'wp-social-publisher' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $query->posts as $post ) : ?>
				<?php $platforms = wsp_selected_platforms( $post->ID ); ?>
				<tr>
					<td><?php echo esc_html( str_replace( 'T', ' ', get_post_meta( $post->ID, '_wsp_scheduled_at', true ) ) ); ?></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></td>
					<td><?php echo esc_html( get_post_status_object( $post->post_status )->label ); ?></td>
					<td><?php echo esc_html( $platforms ? implode( ', ', $platforms ) : __( 'Seçilmemiş', 'wp-social-publisher' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $query->posts ) : ?><tr><td colspan="4"><?php esc_html_e( 'Bu sayfada plan bulunmuyor.', 'wp-social-publisher' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php
		echo wp_kses_post( paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=wsp-schedules' ) ),
			'format'  => '',
			'current' => $page,
			'total'   => $query->max_num_pages,
		) ) );
		?>
	</div>
	<?php
}

function wsp_calendar_page(): void {
	$month = isset( $_GET['month'] ) && is_string( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : '';
	$first = DateTimeImmutable::createFromFormat( '!Y-m', $month, wp_timezone() );
	if ( false === $first || $first->format( 'Y-m' ) !== $month ) {
		$first = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( 'first day of this month' )->setTime( 0, 0 );
	}
	$next = $first->modify( '+1 month' );
	$query = wsp_scheduled_posts( $first->format( 'Y-m-d\TH:i' ), $next->format( 'Y-m-d\TH:i' ) );
	$days = array();
	foreach ( $query->posts as $post ) {
		$scheduled = get_post_meta( $post->ID, '_wsp_scheduled_at', true );
		$days[ substr( $scheduled, 0, 10 ) ][] = $post;
	}
	$offset = (int) $first->format( 'N' ) - 1;
	$count = (int) $first->format( 't' );
	$previous_url = add_query_arg( 'month', $first->modify( '-1 month' )->format( 'Y-m' ), admin_url( 'admin.php?page=wsp-calendar' ) );
	$next_url = add_query_arg( 'month', $next->format( 'Y-m' ), admin_url( 'admin.php?page=wsp-calendar' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Calendar / Takvim', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<p><?php esc_html_e( 'Yazılarda kaydedilen plan taslakları, seçili platform olmasa bile görüntülenir. Bu takvim paylaşım yapmaz.', 'wp-social-publisher' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( $previous_url ); ?>"><?php esc_html_e( '← Önceki ay', 'wp-social-publisher' ); ?></a>
		<strong style="margin: 0 12px;"><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp(), wp_timezone() ) ); ?></strong>
		<a class="button" href="<?php echo esc_url( $next_url ); ?>"><?php esc_html_e( 'Sonraki ay →', 'wp-social-publisher' ); ?></a></p>
		<div style="overflow-x:auto">
		<table class="widefat striped" style="table-layout:fixed;min-width:700px">
			<thead><tr><?php foreach ( array( 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz' ) as $day ) : ?><th><?php echo esc_html( $day ); ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php for ( $cell = 0; $cell < (int) ( ceil( ( $offset + $count ) / 7 ) * 7 ); $cell++ ) : ?>
				<?php if ( 0 === $cell % 7 ) : ?><tr><?php endif; ?>
				<td style="height:90px;vertical-align:top;overflow-wrap:anywhere">
					<?php $day_number = $cell - $offset + 1; ?>
					<?php if ( $day_number > 0 && $day_number <= $count ) : ?>
						<strong><?php echo esc_html( (string) $day_number ); ?></strong>
						<?php $date = $first->format( 'Y-m-' ) . sprintf( '%02d', $day_number ); ?>
						<?php foreach ( $days[ $date ] ?? array() as $post ) : ?>
							<?php $selected = wsp_selected_platforms( $post->ID ); ?>
							<div><small><?php echo esc_html( substr( get_post_meta( $post->ID, '_wsp_scheduled_at', true ), 11, 5 ) ); ?></small>
							<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
							<small>(<?php echo esc_html( $selected ? implode( ', ', $selected ) : __( 'Platform seçilmemiş', 'wp-social-publisher' ) ); ?>)</small></div>
						<?php endforeach; ?>
					<?php endif; ?>
				</td>
				<?php if ( 6 === $cell % 7 ) : ?></tr><?php endif; ?>
			<?php endfor; ?>
			</tbody>
		</table>
		</div>
	</div>
	<?php
}
