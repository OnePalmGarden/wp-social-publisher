<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WSP_FACEBOOK_API_VERSION = 'v26.0';

function wsp_secret_key(): string {
	return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
}

function wsp_encrypt_secret( string $token ) {
	if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
		return new WP_Error( 'crypto_unavailable', 'Sunucuda OpenSSL veya güvenli rastgele sayı desteği yok.' );
	}
	try {
		$iv = random_bytes( 12 );
	} catch ( Exception $error ) {
		return new WP_Error( 'crypto_unavailable', 'Güvenli rastgele sayı üretilemedi.' );
	}
	$tag = '';
	$ciphertext = openssl_encrypt( $token, 'aes-256-gcm', wsp_secret_key(), OPENSSL_RAW_DATA, $iv, $tag );
	if ( false === $ciphertext || 16 !== strlen( $tag ) ) {
		return new WP_Error( 'crypto_failed', 'Erişim belirteci şifrelenemedi.' );
	}
	return base64_encode( $iv . $tag . $ciphertext );
}

function wsp_decrypt_secret( string $encrypted ) {
	if ( ! function_exists( 'openssl_decrypt' ) ) {
		return new WP_Error( 'crypto_unavailable', 'Sunucuda OpenSSL desteği yok.' );
	}
	$data = base64_decode( $encrypted, true );
	if ( false === $data || strlen( $data ) <= 28 ) {
		return new WP_Error( 'crypto_failed', 'Kaydedilen belirteç okunamadı; hesabı yeniden bağlayın.' );
	}
	$token = openssl_decrypt( substr( $data, 28 ), 'aes-256-gcm', wsp_secret_key(), OPENSSL_RAW_DATA, substr( $data, 0, 12 ), substr( $data, 12, 16 ) );
	return false === $token ? new WP_Error( 'crypto_failed', 'Kaydedilen belirteç çözülemedi; hesabı yeniden bağlayın.' ) : $token;
}

function wsp_facebook_key(): string {
	return wsp_secret_key();
}

function wsp_facebook_encrypt( string $token ) {
	return wsp_encrypt_secret( $token );
}

function wsp_facebook_decrypt( string $encrypted ) {
	return wsp_decrypt_secret( $encrypted );
}

function wsp_facebook_request( string $path, string $token, string $method = 'GET', array $body = array() ) {
	$response = wp_remote_request(
		'https://graph.facebook.com/' . WSP_FACEBOOK_API_VERSION . '/' . $path,
		array(
			'method'      => $method,
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $token ),
			'body'        => $body,
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'network', 'Meta bağlantısında hata oluştu. Gönderim denemesiyse sonuç belirsiz olabilir; tekrar denemeden önce Facebook Sayfasını kontrol edin.' );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 > wp_remote_retrieve_response_code( $response ) || 300 <= wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) {
		$code = isset( $data['error']['code'] ) && is_numeric( $data['error']['code'] ) ? (string) (int) $data['error']['code'] : '';
		return new WP_Error( 'meta_error', 'Meta işlemi reddetti veya geçersiz yanıt verdi.' . ( '' !== $code ? ' Hata kodu: ' . $code : '' ) . ' İzinleri, belirteci ve Sayfa kimliğini kontrol edin. Gönderim denemesiyse tekrar denemeden önce Facebook Sayfasını kontrol edin.' );
	}
	return $data;
}

function wsp_facebook_account() {
	$account = get_option( 'wsp_facebook_account', array() );
	if ( ! is_array( $account ) || empty( $account['page_id'] ) || empty( $account['token'] ) || ! is_string( $account['token'] ) ) {
		return new WP_Error( 'not_connected', 'Önce Accounts sayfasından Facebook Sayfasını bağlayın.' );
	}
	$token = wsp_facebook_decrypt( $account['token'] );
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$account['token'] = $token;
	return $account;
}

function wsp_facebook_admin_check( string $action ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'wp-social-publisher' ) );
	}
	check_admin_referer( $action );
}

function wsp_facebook_redirect( string $page, string $message, bool $error = false ): void {
	set_transient( 'wsp_fb_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => $error ), 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
	exit;
}

function wsp_facebook_notice(): void {
	$key = 'wsp_fb_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	delete_transient( $key );
	if ( is_array( $notice ) && isset( $notice['message'] ) ) {
		echo '<div class="notice ' . ( ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
	}
}

function wsp_accounts_page(): void {
	wsp_render_accounts_page();
}

function wsp_facebook_connect(): void {
	wsp_facebook_admin_check( 'wsp_facebook_connect' );
	if ( ! is_ssl() ) {
		wp_die( esc_html__( 'HTTPS gereklidir.', 'wp-social-publisher' ) );
	}
	$page_id = isset( $_POST['page_id'] ) && is_string( $_POST['page_id'] ) ? trim( wp_unslash( $_POST['page_id'] ) ) : '';
	$token = isset( $_POST['page_token'] ) && is_string( $_POST['page_token'] ) ? trim( wp_unslash( $_POST['page_token'] ) ) : '';
	if ( ! preg_match( '/^[0-9]{1,30}$/', $page_id ) || '' === $token || strlen( $token ) > 4096 || preg_match( '/\s/', $token ) ) {
		wsp_facebook_redirect( 'wsp-accounts', 'Geçerli bir Sayfa kimliği ve erişim belirteci girin.', true );
	}
	$result = wsp_facebook_request( 'me?fields=id%2Cname', $token );
	if ( is_wp_error( $result ) ) {
		wsp_facebook_redirect( 'wsp-accounts', $result->get_error_message(), true );
	}
	if ( ! isset( $result['id'], $result['name'] ) || (string) $result['id'] !== $page_id || ! is_string( $result['name'] ) ) {
		wsp_facebook_redirect( 'wsp-accounts', 'Belirtecin sahibi girilen Sayfa ile eşleşmiyor. Kullanıcı belirteci değil, Sayfa erişim belirteci kullanın.', true );
	}
	$encrypted = wsp_facebook_encrypt( $token );
	if ( is_wp_error( $encrypted ) ) {
		wsp_facebook_redirect( 'wsp-accounts', $encrypted->get_error_message(), true );
	}
	$account = array( 'page_id' => $page_id, 'name' => sanitize_text_field( $result['name'] ), 'token' => $encrypted );
	update_option( 'wsp_facebook_account', $account, false );
	if ( get_option( 'wsp_facebook_account' ) !== $account ) {
		wsp_facebook_redirect( 'wsp-accounts', 'Sayfa doğrulandı ancak kaydedilemedi. Site yöneticinizle görüşün.', true );
	}
	wsp_facebook_redirect( 'wsp-accounts', 'Sayfa doğrulandı ve kaydedildi. Paylaşım yetkisi ilk gönderim sırasında ayrıca doğrulanır.' );
}
add_action( 'admin_post_wsp_facebook_connect', 'wsp_facebook_connect' );

function wsp_facebook_disconnect(): void {
	wsp_facebook_admin_check( 'wsp_facebook_disconnect' );
	delete_option( 'wsp_facebook_account' );
	wsp_facebook_redirect( 'wsp-accounts', 'Facebook bağlantısı bu siteden kaldırıldı.' );
}
add_action( 'admin_post_wsp_facebook_disconnect', 'wsp_facebook_disconnect' );

function wsp_shareable_platforms(): array {
	$available = array();
	$facebook = get_option( 'wsp_facebook_account', array() );
	if ( is_array( $facebook ) && ! empty( $facebook['page_id'] ) && ! empty( $facebook['token'] ) ) {
		$available['facebook'] = sprintf( 'Facebook — %s', ! empty( $facebook['name'] ) ? $facebook['name'] : $facebook['page_id'] );
	}
	$accounts = get_option( 'wsp_oauth_accounts', array() );
	if ( is_array( $accounts ) ) {
		foreach ( wsp_oauth_providers() as $platform => $provider ) {
			if ( empty( $accounts[ $platform ]['access_token'] ) || empty( $accounts[ $platform ]['profile']['id'] ) ) {
				continue;
			}
			$name = ! empty( $accounts[ $platform ]['profile']['name'] ) ? $accounts[ $platform ]['profile']['name'] : $accounts[ $platform ]['profile']['id'];
			$available[ $platform ] = sprintf( '%s — %s', wsp_platforms()[ $platform ], $name );
		}
	}
	$ordered = array();
	foreach ( wsp_platforms() as $platform => $label ) {
		if ( isset( $available[ $platform ] ) ) {
			$ordered[ $platform ] = $available[ $platform ];
		}
	}
	return $ordered;
}

function wsp_delivery_log_add( array $entry ): bool {
	$logs = get_option( 'wsp_delivery_logs', array() );
	$logs = is_array( $logs ) ? $logs : array();
	array_unshift( $logs, $entry );
	$logs = array_slice( $logs, 0, 200 );
	update_option( 'wsp_delivery_logs', $logs, false );
	return get_option( 'wsp_delivery_logs' ) === $logs;
}

function wsp_delivery_log_update( string $id, array $changes ): bool {
	$logs = get_option( 'wsp_delivery_logs', array() );
	if ( ! is_array( $logs ) ) {
		return false;
	}
	foreach ( $logs as &$entry ) {
		if ( isset( $entry['id'] ) && $id === $entry['id'] ) {
			$entry = array_merge( $entry, $changes );
			unset( $entry );
			update_option( 'wsp_delivery_logs', $logs, false );
			return get_option( 'wsp_delivery_logs' ) === $logs;
		}
	}
	unset( $entry );
	return false;
}

function wsp_share_platform_account( string $platform ) {
	if ( 'facebook' === $platform ) {
		return wsp_facebook_account();
	}
	if ( isset( wsp_oauth_providers()[ $platform ] ) ) {
		$account = wsp_oauth_account( $platform );
		if ( is_wp_error( $account ) ) {
			return $account;
		}
		if ( empty( $account['profile']['id'] ) || ! is_string( $account['profile']['id'] ) ) {
			return new WP_Error( 'social_profile_missing', 'Platform hesabı profili eksik. Hesabı yeniden bağlayın.' );
		}
		return $account;
	}
	return new WP_Error( 'unsupported_platform', 'Bu platformda anında paylaşım desteklenmiyor.' );
}

function wsp_share_platform_name( string $platform, array $account ): string {
	return 'facebook' === $platform
		? sprintf( 'Facebook — %s', ! empty( $account['name'] ) ? $account['name'] : $account['page_id'] )
		: sprintf( '%s — %s', wsp_platforms()[ $platform ], ! empty( $account['profile']['name'] ) ? $account['profile']['name'] : $account['profile']['id'] );
}

function wsp_render_share_confirmation( string $id, array $draft ): void {
	$platforms = wsp_platforms();
	$platform = $draft['platform'];
	$platform_label = $platforms[ $platform ];
	?>
	<div class="wsp-confirm-page">
		<h2><?php esc_html_e( 'Paylaşım önizlemesi', 'wp-social-publisher' ); ?></h2>
		<p><?php esc_html_e( 'Gönderimden önce hedef hesabı ve içeriği dikkatlice kontrol edin.', 'wp-social-publisher' ); ?></p>
		<div class="wsp-confirm-layout">
			<section class="wsp-social-card">
				<header class="wsp-social-card-header">
					<span class="wsp-social-avatar"><?php echo esc_html( strtoupper( substr( $platform, 0, 1 ) ) ); ?></span>
					<span><strong><?php echo esc_html( $draft['target'] ); ?></strong><small><?php echo esc_html( $platform_label . ' · ' . __( 'Şimdi paylaşım', 'wp-social-publisher' ) ); ?></small></span>
				</header>
				<div class="wsp-social-card-copy"><?php echo esc_html( $draft['message'] ); ?></div>
				<?php if ( ! empty( $draft['image_url'] ) ) : ?>
					<img class="wsp-social-card-image" src="<?php echo esc_url( $draft['image_url'] ); ?>" alt="<?php echo esc_attr( $draft['post_title'] ); ?>">
				<?php endif; ?>
				<div class="wsp-social-link-card">
					<strong><?php echo esc_html( $draft['post_title'] ); ?></strong>
					<span><?php echo esc_html( wp_parse_url( $draft['link'], PHP_URL_HOST ) ); ?></span>
					<a href="<?php echo esc_url( $draft['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $draft['link'] ); ?></a>
				</div>
			</section>
			<aside class="wsp-panel wsp-confirm-actions">
				<h2><?php esc_html_e( 'Gönderiyi onayla', 'wp-social-publisher' ); ?></h2>
				<p><strong><?php esc_html_e( 'Hedef:', 'wp-social-publisher' ); ?></strong> <?php echo esc_html( $draft['target'] ); ?></p>
				<p><?php echo esc_html( sprintf( __( 'Onayladığınızda gönderi hemen %s hesabında yayımlanır.', 'wp-social-publisher' ), $platform_label ) ); ?></p>
				<p class="description">
					<?php
					if ( 'instagram' === $platform ) {
						esc_html_e( 'Instagram tek görselli gönderi oluşturur; herkese açık HTTPS öne çıkan görsel zorunludur. Yazı bağlantısı açıklama metnine eklenir ve Instagram bunu tıklanabilir yapmaz.', 'wp-social-publisher' );
					} elseif ( 'pinterest' === $platform ) {
						esc_html_e( 'Pinterest Pin için öne çıkan görsel ve Accounts sayfasında seçilmiş pano gerekir.', 'wp-social-publisher' );
					} elseif ( 'reddit' === $platform ) {
						esc_html_e( 'Reddit gönderisi yazı başlığını ve yazı bağlantısını seçilmiş subreddit’e yollar.', 'wp-social-publisher' );
					} elseif ( 'linkedin' === $platform ) {
						esc_html_e( 'LinkedIn paylaşımında öne çıkan JPEG/PNG görsel varsa onaydan sonra gönderiye yüklenir; metinde ayrıca yazı bağlantısı bulunur.', 'wp-social-publisher' );
					} elseif ( 'x' === $platform ) {
						esc_html_e( 'X gönderisine bağlantı eklenir; toplam 200 karakterlik hedef aşılırsa gönderim yapılmaz.', 'wp-social-publisher' );
					} else {
						esc_html_e( 'Facebook bağlantı görselini kendi önizleme kurallarıyla oluşturabilir.', 'wp-social-publisher' );
					}
					?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wsp_facebook_publish">
					<input type="hidden" name="draft" value="<?php echo esc_attr( $id ); ?>">
					<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
					<?php wp_nonce_field( 'wsp_facebook_publish_' . $id ); ?>
					<?php submit_button( sprintf( __( 'Onayla ve %s’ta paylaş', 'wp-social-publisher' ), $platform_label ), 'primary', 'submit', true, array( 'class' => 'button button-primary button-large' ) ); ?>
				</form>
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-share-now' ) ); ?>"><?php esc_html_e( 'Geri dön', 'wp-social-publisher' ); ?></a>
			</aside>
		</div>
	</div>
	<?php
}

function wsp_share_now_page(): void {
	$platforms = wsp_shareable_platforms();
	$category = isset( $_GET['cat'] ) && is_scalar( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
	$query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC', 'has_password' => false, 'cat' => $category ) );
	?>
	<div class="wrap wsp-admin-page">
		<h1><?php esc_html_e( 'Share Now / Şimdi paylaş', 'wp-social-publisher' ); ?></h1>
		<?php wsp_facebook_notice(); ?>
		<?php
		$preview_id = isset( $_GET['preview'] ) && is_string( $_GET['preview'] ) ? sanitize_text_field( wp_unslash( $_GET['preview'] ) ) : '';
		$preview = preg_match( '/^[a-zA-Z0-9]{32}$/', $preview_id ) ? get_transient( 'wsp_fb_draft_' . $preview_id ) : false;
		if ( is_array( $preview ) && isset( $preview['user_id'], $preview['platform'], $preview['target'], $preview['post_title'], $preview['message'], $preview['link'] ) && get_current_user_id() === $preview['user_id'] && isset( $platforms[ $preview['platform'] ] ) ) {
			wsp_render_share_confirmation( $preview_id, $preview );
			echo '</div>';
			return;
		}
		if ( '' !== $preview_id ) :
			?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'Önizleme geçersiz veya süresi dolmuş. Yeniden bir önizleme oluşturun.', 'wp-social-publisher' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Hesabınızı ve yazınızı seçin. Önizlemede görsel/metin düzenini kontrol edebilir, son onayla gönderiyi hemen yayımlayabilirsiniz.', 'wp-social-publisher' ); ?></p>
		<?php if ( ! $platforms ) : ?>
			<p><?php esc_html_e( 'Önce Accounts sayfasından en az bir desteklenen platform hesabı bağlayın.', 'wp-social-publisher' ); ?></p>
		<?php elseif ( ! $query->posts ) : ?>
			<p><?php esc_html_e( 'Paylaşılabilecek yayımlanmış ve şifresiz yazı bulunamadı.', 'wp-social-publisher' ); ?></p>
		<?php else : ?>
			<form method="get" class="wsp-filter-bar wsp-share-filter">
				<input type="hidden" name="page" value="wsp-share-now">
				<label><?php esc_html_e( 'Yazı kategorisi', 'wp-social-publisher' ); ?>
					<select name="cat"><option value="0"><?php esc_html_e( 'Tüm kategoriler', 'wp-social-publisher' ); ?></option>
						<?php foreach ( wsp_category_options() as $item ) : ?><option value="<?php echo esc_attr( (string) $item->term_id ); ?>" <?php selected( $category, (int) $item->term_id ); ?>><?php echo esc_html( $item->name ); ?></option><?php endforeach; ?>
					</select>
				</label>
				<?php submit_button( __( 'Yazıları süz', 'wp-social-publisher' ), 'secondary', 'filter_action', false ); ?>
			</form>
			<div class="wsp-share-layout">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wsp-panel wsp-share-form">
					<h2><?php esc_html_e( 'Paylaşım bilgileri', 'wp-social-publisher' ); ?></h2>
					<input type="hidden" name="action" value="wsp_facebook_preview">
					<?php wp_nonce_field( 'wsp_facebook_preview' ); ?>
					<label class="wsp-field-label" for="wsp-platform"><?php esc_html_e( 'Platform ve hesap', 'wp-social-publisher' ); ?></label>
					<select id="wsp-platform" name="platform" required class="wsp-field-control">
						<?php foreach ( $platforms as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select>
					<label class="wsp-field-label" for="wsp-post-id"><?php esc_html_e( 'Yayımlanmış WordPress yazısı', 'wp-social-publisher' ); ?></label>
					<select id="wsp-post-id" name="post_id" required class="wsp-field-control">
						<?php foreach ( $query->posts as $post ) : ?>
							<option value="<?php echo esc_attr( (string) $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) . ' — ' . wsp_post_category_names( $post->ID ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Yazıdaki platforma özel metin varsa o kullanılır; yoksa Templates şablonu uygulanır.', 'wp-social-publisher' ); ?></p>
					<?php submit_button( __( 'Önizlemeyi oluştur', 'wp-social-publisher' ), 'primary', 'submit', true, array( 'class' => 'button button-primary button-large' ) ); ?>
				</form>
				<aside class="wsp-panel wsp-share-help">
					<h2><?php esc_html_e( 'Önizleme ve yayınlama', 'wp-social-publisher' ); ?></h2>
					<p><?php esc_html_e( 'Bir sonraki ekranda hesap adı, paylaşım metni, yazı başlığı ve öne çıkan görselin önizlemesi gösterilir. Onay vermeden hiçbir gönderi yapılmaz.', 'wp-social-publisher' ); ?></p>
					<p class="description"><?php esc_html_e( 'Bağlı hesapların desteklediği medya, gönderi onayından sonra ilgili platformun API’si üzerinden yüklenir.', 'wp-social-publisher' ); ?></p>
				</aside>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

function wsp_facebook_preview(): void {
	wsp_facebook_admin_check( 'wsp_facebook_preview' );
	$platform = isset( $_POST['platform'] ) && is_string( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
	$platforms = wsp_shareable_platforms();
	if ( ! isset( $platforms[ $platform ] ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önce desteklenen bir platformu bağlayın.', true );
	}
	$account = wsp_share_platform_account( $platform );
	$post_id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post = get_post( $post_id );
	if ( is_wp_error( $account ) || ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password || ! current_user_can( 'edit_post', $post_id ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Hesap veya yayımlanmış şifresiz yazı bulunamadı.', true );
	}
	$message = wsp_post_share_message( $platform, $post );
	$link = get_permalink( $post );
	if ( '' === trim( $message ) || ! is_string( $link ) || ! wp_http_validate_url( $link ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Mesaj veya yazı bağlantısı geçersiz.', true );
	}
	if ( 'linkedin' === $platform && ! str_contains( $message, $link ) ) {
		$message = rtrim( $message ) . "\n\n" . $link;
	}
	if ( 'x' === $platform ) {
		$message = wsp_x_complete_message( $post, $message, $link );
	}
	$image_url = get_the_post_thumbnail_url( $post, 'large' );
	$id = wp_generate_password( 32, false, false );
	$draft = array(
		'user_id'    => get_current_user_id(),
		'platform'   => $platform,
		'target_id'  => 'facebook' === $platform ? $account['page_id'] : $account['profile']['id'],
		'target'     => wsp_share_platform_name( $platform, $account ),
		'post_id'    => $post_id,
		'post_title' => sanitize_text_field( get_the_title( $post ) ),
		'message'    => $message,
		'link'       => $link,
		'image_url'  => is_string( $image_url ) ? $image_url : '',
	);
	if ( ! set_transient( 'wsp_fb_draft_' . $id, $draft, 10 * MINUTE_IN_SECONDS ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önizleme kaydedilemedi. Lütfen tekrar deneyin.', true );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=wsp-share-now&preview=' . rawurlencode( $id ) ) );
	exit;
}
add_action( 'admin_post_wsp_facebook_preview', 'wsp_facebook_preview' );

function wsp_facebook_publish(): void {
	$id = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) ? wp_unslash( $_POST['draft'] ) : '';
	if ( ! preg_match( '/^[a-zA-Z0-9]{32}$/', $id ) ) {
		wp_die( esc_html__( 'Geçersiz önizleme.', 'wp-social-publisher' ) );
	}
	wsp_facebook_admin_check( 'wsp_facebook_publish_' . $id );
	$draft = get_transient( 'wsp_fb_draft_' . $id );
	$platform = isset( $_POST['platform'] ) && is_string( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
	$account = wsp_share_platform_account( $platform );
	if ( ! is_array( $draft ) || ! isset( $draft['user_id'], $draft['platform'], $draft['target_id'], $draft['post_id'], $draft['post_title'], $draft['message'], $draft['link'] ) || is_wp_error( $account ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önizleme geçersiz, süresi dolmuş veya hesap değişmiş. Yeniden önizleyin.', true );
	}
	$current_target_id = 'facebook' === $platform ? $account['page_id'] : $account['profile']['id'];
	if ( get_current_user_id() !== $draft['user_id'] || $platform !== $draft['platform'] || $current_target_id !== $draft['target_id'] || ! current_user_can( 'edit_post', $draft['post_id'] ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önizleme geçersiz, süresi dolmuş veya hesap değişmiş. Yeniden önizleyin.', true );
	}
	$post = get_post( $draft['post_id'] );
	if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password || get_permalink( $post ) !== $draft['link'] ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Yazı durumu veya bağlantısı değişti. Gönderi yapılmadı; yeniden önizleyin.', true );
	}
	if ( ! add_option( 'wsp_fb_attempt_' . $id, time(), '', false ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Bu onay zaten kullanılmış veya gönderim kaydı oluşturulamamış. Yinelenmeyi önlemek için hedef hesabı kontrol edin.', true );
	}
	delete_transient( 'wsp_fb_draft_' . $id );
	$log = array(
		'id'          => $id,
		'created_at'  => gmdate( 'Y-m-d H:i:s' ),
		'platform'    => $platform,
		'platform_name' => wsp_platforms()[ $platform ],
		'post_id'     => $draft['post_id'],
		'post_title'  => $draft['post_title'],
		'status'      => 'Gönderiliyor',
		'message'     => 'Kullanıcı onayı alındı.',
		'remote_id'   => '',
	);
	if ( ! wsp_delivery_log_add( $log ) ) {
		delete_option( 'wsp_fb_attempt_' . $id );
		wsp_facebook_redirect( 'wsp-share-now', 'Gönderim kaydı oluşturulamadı. Güvenlik için gönderi yapılmadı.', true );
	}
	$result = wsp_publish_platform_post( $platform, $post, $draft['message'], $draft['link'] );
	if ( is_wp_error( $result ) ) {
		$error_message = $result->get_error_message();
		$status = str_contains( strtolower( $error_message ), 'belirsiz' ) ? 'Belirsiz' : 'Başarısız';
		$logged = wsp_delivery_log_update( $id, array( 'status' => $status, 'message' => $error_message ) );
		if ( ! $logged ) {
			$error_message .= ' Logs kaydı güncellenemedi; hedef hesabı kontrol edin.';
		}
		wsp_facebook_redirect( 'wsp-share-now', $error_message, true );
	}
	$remote_id = isset( $result['id'] ) && is_string( $result['id'] ) ? $result['id'] : '';
	if ( '' === $remote_id ) {
		wsp_delivery_log_update( $id, array( 'status' => 'Belirsiz', 'message' => 'Platform gönderi kimliği döndürmedi; tekrar denemeden önce hedef hesabı kontrol edin.' ) );
		wsp_facebook_redirect( 'wsp-share-now', 'Platform gönderi kimliği döndürmedi. Sonuç belirsiz; tekrar denemeden önce hedef hesabı kontrol edin.', true );
	}
	$success = wsp_delivery_log_update( $id, array( 'status' => 'Başarılı', 'message' => 'Gönderi platform tarafından oluşturuldu.', 'remote_id' => sanitize_text_field( $remote_id ) ) );
	if ( ! $success ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Gönderi oluşturuldu ancak Logs kaydı güncellenemedi. Hedef hesabı kontrol edin.', true );
	}
	wsp_facebook_redirect( 'wsp-share-now', $log['platform_name'] . ' gönderisi oluşturuldu. Logs sayfasında kaydı görebilirsiniz.' );
}
add_action( 'admin_post_wsp_facebook_publish', 'wsp_facebook_publish' );
