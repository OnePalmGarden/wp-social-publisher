<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wsp_admin_menu(): void {
	add_menu_page( 'Social Publisher', 'Social Publisher', 'manage_options', 'wsp-accounts', 'wsp_accounts_page', 'dashicons-share', 26 );
	add_submenu_page( 'wsp-accounts', 'Hesaplar', 'Accounts', 'manage_options', 'wsp-accounts', 'wsp_accounts_page' );
	add_submenu_page( 'wsp-accounts', 'Otomatik paylaşım', 'WP Auto Post', 'manage_options', 'wsp-auto-post', 'wsp_auto_post_page' );
	add_submenu_page( 'wsp-accounts', 'Yazı planları', 'WP Post Schedules', 'manage_options', 'wsp-schedules', 'wsp_schedules_page' );
	add_submenu_page( 'wsp-accounts', 'Şimdi paylaş', 'Share Now', 'manage_options', 'wsp-share-now', 'wsp_share_now_page' );
	add_submenu_page( 'wsp-accounts', 'Takvim', 'Calendar', 'manage_options', 'wsp-calendar', 'wsp_calendar_page' );
	add_submenu_page( 'wsp-accounts', 'Şablonlar', 'Templates', 'manage_options', 'wsp-templates', 'wsp_templates_page' );
	add_submenu_page( 'wsp-accounts', 'Kayıtlar', 'Logs', 'manage_options', 'wsp-logs', 'wsp_logs_page' );
}
add_action( 'admin_menu', 'wsp_admin_menu' );

function wsp_admin_bar_menu( $bar ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$bar->add_node( array(
		'id'    => 'wsp-publisher',
		'title' => 'Social Publisher',
		'href'  => admin_url( 'admin.php?page=wsp-accounts' ),
	) );
}
add_action( 'admin_bar_menu', 'wsp_admin_bar_menu', 100 );

function wsp_admin_assets( string $hook_suffix ): void {
	if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) {
		return;
	}
	$page = sanitize_key( wp_unslash( $_GET['page'] ) );
	if ( ! in_array( $page, array( 'wsp-templates', 'wsp-accounts', 'wsp-auto-post', 'wsp-schedules', 'wsp-share-now', 'wsp-calendar', 'wsp-logs' ), true ) ) {
		return;
	}
	$base_url = plugin_dir_url( dirname( __DIR__ ) . '/wp-social-publisher.php' );
	wp_enqueue_style( 'wsp-templates', $base_url . 'assets/templates.css', array(), '0.9.4' );
	if ( 'wsp-templates' === $page ) {
		wp_enqueue_script( 'wsp-templates', $base_url . 'assets/templates.js', array(), '0.9.1', true );
	}
}
add_action( 'admin_enqueue_scripts', 'wsp_admin_assets' );

function wsp_notice(): void {
	return;
}

function wsp_post_category_names( int $post_id ): string {
	$categories = get_the_category( $post_id );
	if ( ! is_array( $categories ) || ! $categories ) {
		return __( 'Kategorisiz', 'wp-social-publisher' );
	}
	$names = array();
	foreach ( $categories as $category ) {
		if ( isset( $category->name ) && is_string( $category->name ) ) {
			$names[] = $category->name;
		}
	}
	return $names ? implode( ', ', $names ) : __( 'Kategorisiz', 'wp-social-publisher' );
}

function wsp_category_options(): array {
	$categories = get_categories( array( 'hide_empty' => false ) );
	return is_array( $categories ) ? $categories : array();
}

function wsp_placeholder_page(): void {
	$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$pages = array(
		'wsp-accounts'  => array( 'Accounts / Hesaplar', 'Hesap bağlantısı henüz eklenmedi. Bu sayfaya şifre veya erişim anahtarı girilmez.' ),
		'wsp-share-now' => array( 'Share Now / Şimdi paylaş', 'Şimdi paylaş sayfası yüklenemedi.' ),
	);
	if ( ! isset( $pages[ $page ] ) ) {
		return;
	}
	echo '<div class="wrap"><h1>' . esc_html( $pages[ $page ][0] ) . '</h1>';
	wsp_notice();
	echo '<p>' . esc_html( $pages[ $page ][1] ) . '</p></div>';
}

function wsp_logs_page(): void {
	$logs = get_option( 'wsp_delivery_logs', array() );
	$logs = is_array( $logs ) ? $logs : array();
	$platform_filter = isset( $_GET['platform'] ) && is_string( $_GET['platform'] ) ? sanitize_key( wp_unslash( $_GET['platform'] ) ) : '';
	$status_filter = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
	$date_filter = isset( $_GET['day'] ) && is_string( $_GET['day'] ) ? sanitize_text_field( wp_unslash( $_GET['day'] ) ) : '';
	$valid_statuses = array( 'Gönderiliyor', 'Başarılı', 'Başarısız', 'Belirsiz' );
	if ( ! in_array( $platform_filter, array_merge( array( '' ), array_keys( wsp_platforms() ) ), true ) ) {
		$platform_filter = '';
	}
	if ( ! in_array( $status_filter, array_merge( array( '' ), $valid_statuses ), true ) ) {
		$status_filter = '';
	}
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $date_filter, wp_timezone() );
	if ( false === $date || $date->format( 'Y-m-d' ) !== $date_filter ) {
		$date_filter = '';
	}
	$logs = array_values( array_filter( $logs, static function ( $item ) use ( $platform_filter, $status_filter, $date_filter ): bool {
		if ( ! is_array( $item ) ) {
			return false;
		}
		if ( '' !== $platform_filter && ( $item['platform'] ?? '' ) !== $platform_filter ) {
			return false;
		}
		if ( '' !== $status_filter && ( $item['status'] ?? '' ) !== $status_filter ) {
			return false;
		}
		if ( '' !== $date_filter ) {
			$timestamp = isset( $item['created_at'] ) && is_string( $item['created_at'] ) ? strtotime( $item['created_at'] . ' UTC' ) : false;
			if ( false === $timestamp || wp_date( 'Y-m-d', $timestamp, wp_timezone() ) !== $date_filter ) {
				return false;
			}
		}
		return true;
	} ) );
	$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$page_size = 25;
	$total = count( $logs );
	$items = array_slice( $logs, ( $page - 1 ) * $page_size, $page_size );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Logs / Gönderim kayıtları', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<p><?php esc_html_e( 'Son 200 gönderim denemesi burada saklanır. Gün filtresi site saat dilimini kullanır.', 'wp-social-publisher' ); ?></p>
		<form method="get" class="wsp-filter-bar">
			<input type="hidden" name="page" value="wsp-logs">
			<label><?php esc_html_e( 'Platform', 'wp-social-publisher' ); ?>
				<select name="platform"><option value=""><?php esc_html_e( 'Tüm platformlar', 'wp-social-publisher' ); ?></option>
					<?php foreach ( wsp_platforms() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $platform_filter, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Durum', 'wp-social-publisher' ); ?>
				<select name="status"><option value=""><?php esc_html_e( 'Tüm durumlar', 'wp-social-publisher' ); ?></option>
					<?php foreach ( $valid_statuses as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>><?php echo esc_html( $status ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Gün', 'wp-social-publisher' ); ?> <input type="date" name="day" value="<?php echo esc_attr( $date_filter ); ?>"></label>
			<?php submit_button( __( 'Filtrele', 'wp-social-publisher' ), 'secondary', 'filter_action', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-logs' ) ); ?>"><?php esc_html_e( 'Temizle', 'wp-social-publisher' ); ?></a>
		</form>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Tarih ve saat', 'wp-social-publisher' ); ?></th>
				<th><?php esc_html_e( 'Platform', 'wp-social-publisher' ); ?></th>
				<th><?php esc_html_e( 'Yazı', 'wp-social-publisher' ); ?></th>
				<th><?php esc_html_e( 'Durum', 'wp-social-publisher' ); ?></th>
				<th><?php esc_html_e( 'Açıklama', 'wp-social-publisher' ); ?></th>
				<th><?php esc_html_e( 'Platform gönderi kimliği', 'wp-social-publisher' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $items as $item ) : ?>
				<tr>
					<td><?php echo esc_html( isset( $item['created_at'] ) && is_string( $item['created_at'] ) && false !== ( $log_time = strtotime( $item['created_at'] . ' UTC' ) ) ? wp_date( 'Y-m-d H:i', $log_time, wp_timezone() ) : '' ); ?></td>
					<td><?php echo esc_html( isset( $item['platform_name'] ) && is_string( $item['platform_name'] ) ? $item['platform_name'] : '' ); ?></td>
					<td><?php if ( ! empty( $item['post_id'] ) && is_scalar( $item['post_id'] ) ) : ?><a href="<?php echo esc_url( get_edit_post_link( (int) $item['post_id'] ) ); ?>"><?php echo esc_html( isset( $item['post_title'] ) && is_string( $item['post_title'] ) ? $item['post_title'] : '' ); ?></a><?php endif; ?></td>
					<td><?php echo esc_html( isset( $item['status'] ) && is_string( $item['status'] ) ? $item['status'] : '' ); ?></td>
					<td><?php echo esc_html( isset( $item['message'] ) && is_string( $item['message'] ) ? $item['message'] : '' ); ?></td>
					<td><?php echo esc_html( isset( $item['remote_id'] ) && is_string( $item['remote_id'] ) ? $item['remote_id'] : '' ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $items ) : ?><tr><td colspan="6"><?php esc_html_e( $total ? 'Filtrelere uygun gönderim kaydı bulunamadı.' : 'Henüz gönderim denemesi yok.', 'wp-social-publisher' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php
		echo wp_kses_post( paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=wsp-logs' ) ),
			'format'  => '',
			'current' => $page,
			'total'   => (int) ceil( $total / $page_size ),
			'add_args' => array_filter( array( 'platform' => $platform_filter, 'status' => $status_filter, 'day' => $date_filter ) ),
		) ) );
		?>
	</div>
	<?php
}

function wsp_templates_page(): void {
	$platforms = wsp_platforms();
	$first_platform = array_key_first( $platforms );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Templates / Paylaşım şablonları', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<p><?php esc_html_e( 'Soldan platformu seçin, orta bölümdeki alanları düzenleyin ve sağdaki önizlemede sonucu görün. Yazı düzenleyicisindeki özel metin, varsayılan şablonun yerine kullanılır.', 'wp-social-publisher' ); ?></p>
		<p><?php esc_html_e( 'Başlık, özet, bağlantı ve yazı etiketleri şablon metnine yer tutucularla eklenir. Öne çıkan görsel şablon metnine değil, gönderinin medya kartına eklenir. LinkedIn gönderisinde görsel yüklemesi desteklenir; diğer platformlar için hesap bağlantısı ve medya gönderimi ayrıca gereklidir.', 'wp-social-publisher' ); ?></p>
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
								<span class="description"><?php esc_html_e( '{tags} düz etiketleri, {hashtags} ise # işaretli etiketleri ekler.', 'wp-social-publisher' ); ?></span>
							</p>
							<details class="wsp-smart-tags">
								<summary><?php esc_html_e( 'Akıllı etiketleri göster', 'wp-social-publisher' ); ?></summary>
								<p><?php esc_html_e( 'Etiketi eklemek istediğiniz alana tıklayın; ekleme imlecin olduğu yere yapılır.', 'wp-social-publisher' ); ?></p>
								<div class="wsp-smart-tag-buttons">
									<?php foreach ( array( '{title}' => 'Başlık', '{excerpt}' => 'Özet', '{url}' => 'Yazı bağlantısı', '{tags}' => 'Yazı etiketleri', '{hashtags}' => 'Hashtag olarak yazı etiketleri', '{site_title}' => 'Site adı' ) as $tag => $tag_label ) : ?>
										<button type="button" class="button button-secondary" data-smart-tag="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $tag ); ?> <span class="screen-reader-text"><?php echo esc_html( $tag_label ); ?></span></button>
									<?php endforeach; ?>
								</div>
							</details>
						</section>
					<?php endforeach; ?>
				</div>
				<?php
				$preview_query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_key' => '_thumbnail_id' ) );
				$preview_post = $preview_query->posts ? $preview_query->posts[0] : null;
				$preview_image = $preview_post ? get_the_post_thumbnail_url( $preview_post, 'large' ) : false;
				?>
				<aside class="wsp-template-preview" aria-live="polite">
					<h2><?php esc_html_e( 'Önizleme', 'wp-social-publisher' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Öne çıkan görsel ve örnek yazı bilgileriyle şablon önizlemesi.', 'wp-social-publisher' ); ?></p>
					<div class="wsp-preview-card">
						<?php if ( is_string( $preview_image ) && '' !== $preview_image ) : ?>
							<img class="wsp-preview-image" src="<?php echo esc_url( $preview_image ); ?>" alt="<?php echo esc_attr( get_the_title( $preview_post ) ); ?>">
						<?php else : ?>
							<div class="wsp-preview-image"><?php esc_html_e( 'Öne çıkan görsel eklenmemiş', 'wp-social-publisher' ); ?></div>
						<?php endif; ?>
						<p class="wsp-preview-title"><?php echo esc_html( $preview_post ? get_the_title( $preview_post ) : __( 'Örnek yazı başlığı', 'wp-social-publisher' ) ); ?></p>
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

function wsp_auto_post_page(): void {
	$day = isset( $_GET['day'] ) && is_string( $_GET['day'] ) ? sanitize_text_field( wp_unslash( $_GET['day'] ) ) : '';
	$day_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, wp_timezone() );
	$scheduled_default = false !== $day_date && $day_date->format( 'Y-m-d' ) === $day ? $day . 'T09:00' : '';
	$category_filter = isset( $_GET['cat'] ) && is_scalar( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
	$available_platforms = wsp_shareable_platforms();
	$query = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending' ),
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'has_password'   => false,
		'cat'            => $category_filter,
	) );
	$recent_plans = wsp_scheduled_posts( null, null, 1 );
	?>
	<div class="wrap wsp-admin-page">
		<h1><?php esc_html_e( 'WP Auto Post / Paylaşım planla', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); wsp_admin_notice(); ?>
		<div class="wsp-scheduler-layout">
			<section class="wsp-panel wsp-scheduler-form">
				<h2><?php esc_html_e( 'Yeni plan', 'wp-social-publisher' ); ?></h2>
				<p><?php esc_html_e( 'Yazıyı, paylaşım platformlarını ve plan tarihini seçin. Seçilen saatte bağlı hesaplara otomatik olarak gönderilir.', 'wp-social-publisher' ); ?></p>
				<form method="get" class="wsp-category-filter">
					<input type="hidden" name="page" value="wsp-auto-post">
					<label class="wsp-field-label" for="wsp-auto-category"><?php esc_html_e( 'Yazı listesini kategoriye göre süz', 'wp-social-publisher' ); ?></label>
					<select id="wsp-auto-category" name="cat" class="wsp-field-control">
						<option value="0"><?php esc_html_e( 'Tüm kategoriler', 'wp-social-publisher' ); ?></option>
						<?php foreach ( wsp_category_options() as $category ) : ?>
							<option value="<?php echo esc_attr( (string) $category->term_id ); ?>" <?php selected( $category_filter, (int) $category->term_id ); ?>><?php echo esc_html( $category->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php submit_button( __( 'Yazıları süz', 'wp-social-publisher' ), 'secondary', 'filter_action', false ); ?>
				</form>
				<?php if ( ! $query->posts ) : ?>
					<p><?php esc_html_e( 'Planlanabilecek yazı bulunamadı.', 'wp-social-publisher' ); ?></p>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wsp_save_auto_post">
						<?php wp_nonce_field( 'wsp_save_auto_post' ); ?>
						<label class="wsp-field-label" for="wsp-schedule-post"><?php esc_html_e( 'WordPress yazısı', 'wp-social-publisher' ); ?></label>
						<select id="wsp-schedule-post" name="post_id" required class="wsp-field-control">
							<?php foreach ( $query->posts as $post ) : if ( '' !== $post->post_password ) { continue; } ?>
								<option value="<?php echo esc_attr( (string) $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) . ' — ' . wsp_post_category_names( $post->ID ) . ' — ' . get_post_status_object( $post->post_status )->label ); ?></option>
							<?php endforeach; ?>
						</select>
						<fieldset class="wsp-platform-picker">
							<legend><?php esc_html_e( 'Bağlı platform hesapları', 'wp-social-publisher' ); ?></legend>
							<?php if ( $available_platforms ) : ?>
								<?php foreach ( $available_platforms as $key => $label ) : ?>
									<label class="wsp-platform-choice"><input type="checkbox" name="platforms[]" value="<?php echo esc_attr( $key ); ?>"> <span><?php echo esc_html( $label ); ?></span></label>
								<?php endforeach; ?>
							<?php else : ?>
								<p><?php esc_html_e( 'Önce Accounts sayfasından desteklenen bir platformu bağlayın.', 'wp-social-publisher' ); ?></p>
							<?php endif; ?>
						</fieldset>
						<label class="wsp-field-label" for="wsp-schedule-time"><?php esc_html_e( 'Tarih ve saat', 'wp-social-publisher' ); ?></label>
						<input id="wsp-schedule-time" class="wsp-field-control" type="datetime-local" name="scheduled_at" value="<?php echo esc_attr( $scheduled_default ); ?>" required>
						<p class="description"><?php echo esc_html( sprintf( __( 'Saat dilimi: %s', 'wp-social-publisher' ), wp_timezone_string() ) ); ?></p>
						<?php submit_button( __( 'Takvime plan olarak ekle', 'wp-social-publisher' ), 'primary', 'submit', true, array( 'class' => 'button button-primary button-large' ) ); ?>
					</form>
				<?php endif; ?>
			</section>
			<div class="wsp-scheduler-sidebar">
				<section class="wsp-panel wsp-scheduler-calendar">
					<h2><?php esc_html_e( 'Takvimi görüntüle', 'wp-social-publisher' ); ?></h2>
					<p><?php esc_html_e( 'Kayıtlı planları ay görünümünde inceleyin; gerçek gönderim sonuçları ayrıca durum etiketiyle gösterilir.', 'wp-social-publisher' ); ?></p>
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-calendar' ) ); ?>"><?php esc_html_e( 'Paylaşım takvimini aç', 'wp-social-publisher' ); ?></a>
				</section>
				<section class="wsp-panel wsp-scheduler-recent">
					<h2><?php esc_html_e( 'Son planlanan 10 gönderi', 'wp-social-publisher' ); ?></h2>
					<?php if ( $recent_plans->posts ) : ?>
						<ul>
							<?php foreach ( array_slice( $recent_plans->posts, 0, 10 ) as $planned_post ) : ?>
								<li><?php echo esc_html( get_post_meta( $planned_post->ID, '_wsp_scheduled_at', true ) . ' — ' . get_the_title( $planned_post ) . ' — ' . wsp_post_category_names( $planned_post->ID ) . ' — ' . wsp_schedule_state_label( (string) get_post_meta( $planned_post->ID, '_wsp_schedule_state', true ) ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php esc_html_e( 'Henüz planlanan gönderi yok.', 'wp-social-publisher' ); ?></p>
					<?php endif; ?>
				</section>
			</div>
		</div>
	</div>
	<?php
}

function wsp_save_auto_post(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'wp-social-publisher' ) );
	}
	check_admin_referer( 'wsp_save_auto_post' );
	$post_id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post = get_post( $post_id );
	$scheduled = isset( $_POST['scheduled_at'] ) && is_string( $_POST['scheduled_at'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ) ) : '';
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled, wp_timezone() );
	$platforms = isset( $_POST['platforms'] ) && is_array( $_POST['platforms'] ) ? wp_unslash( $_POST['platforms'] ) : array();
	$platforms = array_map( 'sanitize_key', array_filter( $platforms, 'is_string' ) );
	$platforms = array_values( array_unique( array_intersect( array_keys( wsp_platforms() ), $platforms ) ) );
	if ( ! $post || 'post' !== $post->post_type || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) || '' !== $post->post_password || ! current_user_can( 'edit_post', $post_id ) ) {
		wsp_admin_redirect( 'wsp-auto-post', 'Seçilen yazı planlanamıyor. Yazı durumunu ve yetkinizi kontrol edin.', true );
	}
	$connected_platforms = wsp_shareable_platforms();
	$platforms = array_values( array_intersect( $platforms, array_keys( $connected_platforms ) ) );
	if ( false === $date || $date->format( 'Y-m-d\TH:i' ) !== $scheduled || $date->getTimestamp() <= time() || ! $platforms ) {
		wsp_admin_redirect( 'wsp-auto-post', 'Gelecekte geçerli bir tarih/saat ve en az bir bağlı platform hesabı seçin.', true );
	}
	update_post_meta( $post_id, '_wsp_scheduled_at', $scheduled );
	foreach ( wsp_platforms() as $key => $label ) {
		update_post_meta( $post_id, '_wsp_' . $key . '_enabled', in_array( $key, $platforms, true ) ? '1' : '0' );
	}
	if ( ! wsp_sync_scheduled_post( $post_id ) ) {
		wsp_admin_redirect( 'wsp-auto-post', 'Plan kaydedildi ancak WP-Cron gönderim görevi oluşturulamadı. WordPress cron ayarlarını kontrol edin.', true );
	}
	wsp_admin_redirect( 'wsp-auto-post', 'Paylaşım planı kaydedildi ve takvime eklendi.' );
}
add_action( 'admin_post_wsp_save_auto_post', 'wsp_save_auto_post' );

function wsp_remove_scheduled_platform(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'wp-social-publisher' ) );
	}
	check_admin_referer( 'wsp_remove_scheduled_platform' );
	$post_id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$platform = isset( $_POST['platform'] ) && is_string( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
	$scheduled = isset( $_POST['scheduled_at'] ) && is_string( $_POST['scheduled_at'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ) ) : '';
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled, wp_timezone() );
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) || ! isset( wsp_platforms()[ $platform ] ) || '1' !== get_post_meta( $post_id, '_wsp_' . $platform . '_enabled', true ) || get_post_meta( $post_id, '_wsp_scheduled_at', true ) !== $scheduled || ! $date || $date->format( 'Y-m-d\TH:i' ) !== $scheduled || $date->getTimestamp() <= time() || get_post_meta( $post_id, '_wsp_schedule_processed_at', true ) === $scheduled ) {
		wsp_admin_redirect( 'wsp-calendar', 'Plan değişmiş, başlamış veya silinemiyor. Takvimi yenileyin.', true );
	}
	update_post_meta( $post_id, '_wsp_' . $platform . '_enabled', '0' );
	if ( ! wsp_selected_platforms( $post_id ) ) {
		$cleared = wp_clear_scheduled_hook( 'wsp_publish_scheduled_post', array( $post_id ), true );
		if ( is_wp_error( $cleared ) || false === $cleared ) {
			update_post_meta( $post_id, '_wsp_' . $platform . '_enabled', '1' );
			wsp_admin_redirect( 'wsp-calendar', 'Cron görevi kaldırılamadı; plan korunuyor.', true );
		}
		delete_post_meta( $post_id, '_wsp_scheduled_at' );
		update_post_meta( $post_id, '_wsp_schedule_state', 'unscheduled' );
	}
	wsp_admin_redirect( 'wsp-calendar&month=' . $date->format( 'Y-m' ), 'Seçilen platform plandan çıkarıldı.' );
}
add_action( 'admin_post_wsp_remove_scheduled_platform', 'wsp_remove_scheduled_platform' );

function wsp_admin_redirect( string $page, string $message, bool $error = false ): void {
	set_transient( 'wsp_admin_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => $error ), 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
	exit;
}

function wsp_admin_notice(): void {
	$key = 'wsp_admin_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	delete_transient( $key );
	if ( is_array( $notice ) && isset( $notice['message'] ) ) {
		echo '<div class="notice ' . ( ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
	}
}

function wsp_schedule_state_label( string $state ): string {
	return array(
		'scheduled'            => __( 'Planlandı', 'wp-social-publisher' ),
		'sending'              => __( 'Gönderiliyor', 'wp-social-publisher' ),
		'completed'            => __( 'Gönderildi', 'wp-social-publisher' ),
		'completed_with_errors' => __( 'Kısmen başarısız', 'wp-social-publisher' ),
		'failed'               => __( 'Başarısız', 'wp-social-publisher' ),
		'schedule_error'       => __( 'Kuyruk hatası', 'wp-social-publisher' ),
		'invalid'              => __( 'Plan geçersiz', 'wp-social-publisher' ),
		'unscheduled'          => __( 'Plan yok', 'wp-social-publisher' ),
	)[ $state ] ?? __( 'Durum bilinmiyor', 'wp-social-publisher' );
}

function wsp_scheduled_posts( ?string $start = null, ?string $end = null, int $page = 1, int $category = 0 ): WP_Query {
	$date_query = array(
		'key'     => '_wsp_scheduled_at',
		'value'   => '',
		'compare' => '>',
	);
	if ( null !== $start || null !== $end ) {
		$date_query = array( 'relation' => 'AND' );
		if ( null !== $start ) {
			$date_query[] = array( 'key' => '_wsp_scheduled_at', 'value' => $start, 'compare' => '>=' );
		}
		if ( null !== $end ) {
			$date_query[] = array( 'key' => '_wsp_scheduled_at', 'value' => $end, 'compare' => '<' );
		}
	}
	$args = array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'posts_per_page' => null === $start ? 50 : -1,
			'paged'          => $page,
			'meta_key'       => '_wsp_scheduled_at',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( $date_query ),
	);
	if ( 0 < $category ) {
		$args['cat'] = $category;
	}
	return new WP_Query( $args );
}

function wsp_schedules_page(): void {
	$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$category = isset( $_GET['cat'] ) && is_scalar( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
	$date_from = isset( $_GET['date_from'] ) && is_string( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
	$date_to = isset( $_GET['date_to'] ) && is_string( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
	$from_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $date_from, wp_timezone() );
	$to_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $date_to, wp_timezone() );
	$date_from = false !== $from_date && $from_date->format( 'Y-m-d' ) === $date_from ? $date_from->format( 'Y-m-d\T00:00' ) : '';
	$date_to = false !== $to_date && $to_date->format( 'Y-m-d' ) === $date_to ? $to_date->modify( '+1 day' )->format( 'Y-m-d\T00:00' ) : '';
	if ( '' !== $date_from && '' !== $date_to && $date_to <= $date_from ) {
		$date_to = '';
	}
	$query = wsp_scheduled_posts( '' !== $date_from ? $date_from : null, '' !== $date_to ? $date_to : null, $page, $category );
	?>
	<div class="wrap wsp-schedules-page">
		<h1><?php esc_html_e( 'WP Post Schedules / Yazı planları', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); ?>
		<form method="get" class="wsp-filter-bar wsp-schedules-filter">
			<input type="hidden" name="page" value="wsp-schedules">
			<label><?php esc_html_e( 'Kategori', 'wp-social-publisher' ); ?>
				<select name="cat"><option value="0"><?php esc_html_e( 'Tüm kategoriler', 'wp-social-publisher' ); ?></option>
					<?php foreach ( wsp_category_options() as $item ) : ?><option value="<?php echo esc_attr( (string) $item->term_id ); ?>" <?php selected( $category, (int) $item->term_id ); ?>><?php echo esc_html( $item->name ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Tarih başlangıcı', 'wp-social-publisher' ); ?> <input type="date" name="date_from" value="<?php echo esc_attr( substr( $date_from, 0, 10 ) ); ?>"></label>
			<label><?php esc_html_e( 'Tarih bitişi', 'wp-social-publisher' ); ?> <input type="date" name="date_to" value="<?php echo esc_attr( '' !== $date_to ? ( new DateTimeImmutable( $date_to, wp_timezone() ) )->modify( '-1 day' )->format( 'Y-m-d' ) : '' ); ?>"></label>
			<?php submit_button( __( 'Filtrele', 'wp-social-publisher' ), 'secondary', 'filter_action', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-schedules' ) ); ?>"><?php esc_html_e( 'Temizle', 'wp-social-publisher' ); ?></a>
		</form>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Tarih ve saat', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Yazı', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Kategori', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Yazı durumu', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Platformlar', 'wp-social-publisher' ); ?></th><th><?php esc_html_e( 'Gönderim durumu', 'wp-social-publisher' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $query->posts as $post ) : ?>
				<?php $platforms = wsp_selected_platforms( $post->ID ); ?>
				<tr>
					<td><?php echo esc_html( str_replace( 'T', ' ', get_post_meta( $post->ID, '_wsp_scheduled_at', true ) ) ); ?></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></td>
					<td><?php echo esc_html( wsp_post_category_names( $post->ID ) ); ?></td>
					<td><?php echo esc_html( get_post_status_object( $post->post_status )->label ); ?></td>
					<td><?php echo esc_html( $platforms ? implode( ', ', $platforms ) : __( 'Seçilmemiş', 'wp-social-publisher' ) ); ?></td>
					<td><?php echo esc_html( wsp_schedule_state_label( (string) get_post_meta( $post->ID, '_wsp_schedule_state', true ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $query->posts ) : ?><tr><td colspan="6"><?php esc_html_e( 'Bu sayfada plan bulunmuyor.', 'wp-social-publisher' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php
		echo wp_kses_post( paginate_links( array(
			'base'    => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=wsp-schedules' ) ),
			'format'  => '',
			'current' => $page,
			'total'   => $query->max_num_pages,
			'add_args' => array_filter( array( 'cat' => $category, 'date_from' => substr( $date_from, 0, 10 ), 'date_to' => '' !== $date_to ? ( new DateTimeImmutable( $date_to, wp_timezone() ) )->modify( '-1 day' )->format( 'Y-m-d' ) : '' ) ),
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
		$platforms = array();
		foreach ( wsp_platforms() as $key => $label ) {
			if ( '1' === get_post_meta( $post->ID, '_wsp_' . $key . '_enabled', true ) ) {
				$platforms[ $key ] = $label;
			}
		}
		if ( ! $platforms ) {
			$platforms[''] = __( 'Platform seçilmemiş', 'wp-social-publisher' );
		}
		$schedule_state = (string) get_post_meta( $post->ID, '_wsp_schedule_state', true );
		$plan_status = '' === $platforms ? 'Eksik plan' : ( ( new DateTimeImmutable( $scheduled, wp_timezone() ) )->getTimestamp() < time() ? 'Gecikti' : 'Planlandı' );
		$plan_status = array(
			'sending'               => 'Gönderiliyor',
			'completed'             => 'Gönderildi',
			'completed_with_errors' => 'Kısmen başarısız',
			'failed'                => 'Başarısız',
			'schedule_error'        => 'Kuyruk hatası',
		)[ $schedule_state ] ?? $plan_status;
		foreach ( $platforms as $platform => $label ) {
			$days[ substr( $scheduled, 0, 10 ) ][] = array(
				'type'      => 'plan',
				'time'      => substr( $scheduled, 11, 5 ),
				'post_id'   => $post->ID,
				'title'     => get_the_title( $post ),
				'platform'  => $platform,
				'label'     => $label,
				'status'    => '' === $platform ? 'Eksik plan' : $plan_status,
				'scheduled' => $scheduled,
				'editable'  => '' !== $platform && ( new DateTimeImmutable( $scheduled, wp_timezone() ) )->getTimestamp() > time() && get_post_meta( $post->ID, '_wsp_schedule_processed_at', true ) !== $scheduled,
			);
		}
	}
	$logs = get_option( 'wsp_delivery_logs', array() );
	foreach ( is_array( $logs ) ? $logs : array() as $log ) {
		if ( ! is_array( $log ) || empty( $log['created_at'] ) || ! is_string( $log['created_at'] ) || empty( $log['platform'] ) || ! is_string( $log['platform'] ) ) {
			continue;
		}
		$timestamp = strtotime( $log['created_at'] . ' UTC' );
		if ( false === $timestamp ) {
			continue;
		}
		$local_date = wp_date( 'Y-m-d', $timestamp, wp_timezone() );
		if ( $local_date < $first->format( 'Y-m-d' ) || $local_date >= $next->format( 'Y-m-d' ) ) {
			continue;
		}
		$days[ $local_date ][] = array(
			'type'      => 'delivery',
			'time'      => wp_date( 'H:i', $timestamp, wp_timezone() ),
			'post_id'   => isset( $log['post_id'] ) ? absint( $log['post_id'] ) : 0,
			'title'     => isset( $log['post_title'] ) && is_string( $log['post_title'] ) ? $log['post_title'] : __( 'Yazı', 'wp-social-publisher' ),
			'platform'  => $log['platform'],
			'label'     => isset( $log['platform_name'] ) ? $log['platform_name'] : ( wsp_platforms()[ $log['platform'] ] ?? $log['platform'] ),
			'status'    => isset( $log['status'] ) && is_string( $log['status'] ) ? $log['status'] : 'Bilinmiyor',
		);
	}
	$offset = (int) $first->format( 'N' ) - 1;
	$count = (int) $first->format( 't' );
	$previous_url = add_query_arg( 'month', $first->modify( '-1 month' )->format( 'Y-m' ), admin_url( 'admin.php?page=wsp-calendar' ) );
	$next_url = add_query_arg( 'month', $next->format( 'Y-m' ), admin_url( 'admin.php?page=wsp-calendar' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Calendar / Paylaşım takvimi', 'wp-social-publisher' ); ?></h1>
		<?php wsp_notice(); wsp_admin_notice(); ?>
		<p><?php esc_html_e( 'Planlar ve gönderim sonuçları platform ve durumlarıyla gösterilir. Düzenleme yazının tüm platformları için ortak plan tarihini değiştirir; silme yalnızca seçilen platformu plandan çıkarır.', 'wp-social-publisher' ); ?></p>
		<div class="wsp-calendar-legend" aria-label="<?php esc_attr_e( 'Takvim durum açıklamaları', 'wp-social-publisher' ); ?>">
			<span class="wsp-event-planned"><?php esc_html_e( 'Planlandı', 'wp-social-publisher' ); ?></span>
			<span class="wsp-event-success"><?php esc_html_e( 'Paylaşıldı', 'wp-social-publisher' ); ?></span>
			<span class="wsp-event-failed"><?php esc_html_e( 'Başarısız / gecikti', 'wp-social-publisher' ); ?></span>
			<span class="wsp-event-uncertain"><?php esc_html_e( 'Belirsiz', 'wp-social-publisher' ); ?></span>
		</div>
		<p class="wsp-calendar-actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-auto-post' ) ); ?>"><?php esc_html_e( 'Yeni paylaşım planla', 'wp-social-publisher' ); ?></a>
		<a class="button" href="<?php echo esc_url( $previous_url ); ?>"><?php esc_html_e( '← Önceki ay', 'wp-social-publisher' ); ?></a>
		<strong style="margin: 0 12px;"><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp(), wp_timezone() ) ); ?></strong>
		<a class="button" href="<?php echo esc_url( $next_url ); ?>"><?php esc_html_e( 'Sonraki ay →', 'wp-social-publisher' ); ?></a></p>
		<div style="overflow-x:auto">
		<table class="widefat wsp-calendar-table" style="table-layout:fixed;min-width:900px">
			<thead><tr><?php foreach ( array( 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz' ) as $day ) : ?><th><?php echo esc_html( $day ); ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php for ( $cell = 0; $cell < (int) ( ceil( ( $offset + $count ) / 7 ) * 7 ); $cell++ ) : ?>
				<?php if ( 0 === $cell % 7 ) : ?><tr><?php endif; ?>
				<td class="wsp-calendar-day">
					<?php $day_number = $cell - $offset + 1; ?>
					<?php if ( $day_number > 0 && $day_number <= $count ) : ?>
						<strong><?php echo esc_html( (string) $day_number ); ?></strong>
						<?php $date = $first->format( 'Y-m-' ) . sprintf( '%02d', $day_number ); ?>
						<a class="wsp-calendar-plan-link" href="<?php echo esc_url( add_query_arg( array( 'page' => 'wsp-auto-post', 'day' => $date ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Bu güne planla', 'wp-social-publisher' ); ?></a>
						<?php foreach ( $days[ $date ] ?? array() as $event ) : ?>
							<?php
							$event_class = 'wsp-event-planned';
							if ( 'delivery' === $event['type'] ) {
								$event_class = array(
									'Başarılı'   => 'wsp-event-success',
									'Gönderildi' => 'wsp-event-success',
									'Kısmen başarısız' => 'wsp-event-failed',
									'Başarısız'  => 'wsp-event-failed',
									'Belirsiz'   => 'wsp-event-uncertain',
									'Gönderiliyor' => 'wsp-event-working',
								)[ $event['status'] ] ?? 'wsp-event-uncertain';
							} elseif ( 'Gecikti' === $event['status'] ) {
								$event_class = 'wsp-event-overdue';
							} elseif ( 'Eksik plan' === $event['status'] ) {
								$event_class = 'wsp-event-incomplete';
							}
							?>
							<div class="wsp-calendar-event <?php echo esc_attr( $event_class ); ?>">
								<span class="wsp-event-time"><?php echo esc_html( $event['time'] ); ?></span>
								<span class="wsp-event-status"><?php echo esc_html( $event['status'] ); ?></span>
								<small class="wsp-event-platform"><?php echo esc_html( $event['label'] ); ?></small>
								<?php if ( ! empty( $event['post_id'] ) ) : ?>
									<a class="wsp-event-title" href="<?php echo esc_url( get_edit_post_link( $event['post_id'] ) ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
								<?php else : ?><span class="wsp-event-title"><?php echo esc_html( $event['title'] ); ?></span><?php endif; ?>
								<?php if ( 'plan' === $event['type'] && $event['editable'] && current_user_can( 'edit_post', $event['post_id'] ) ) : ?>
									<div class="wsp-event-actions">
										<a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $event['post_id'] ) ); ?>" title="<?php esc_attr_e( 'Planı düzenle', 'wp-social-publisher' ); ?>"><?php esc_html_e( 'Düzenle', 'wp-social-publisher' ); ?></a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Bu platformu plandan çıkarmak istiyor musunuz?');">
											<input type="hidden" name="action" value="wsp_remove_scheduled_platform">
											<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $event['post_id'] ); ?>">
											<input type="hidden" name="platform" value="<?php echo esc_attr( $event['platform'] ); ?>">
											<input type="hidden" name="scheduled_at" value="<?php echo esc_attr( $event['scheduled'] ); ?>">
											<?php wp_nonce_field( 'wsp_remove_scheduled_platform' ); ?>
											<button type="submit" class="button button-small" title="<?php esc_attr_e( 'Bu platformu plandan sil', 'wp-social-publisher' ); ?>"><?php esc_html_e( 'Sil', 'wp-social-publisher' ); ?></button>
										</form>
									</div>
								<?php endif; ?>
							</div>
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
