<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WSP_FACEBOOK_API_VERSION = 'v26.0';

function wsp_facebook_key(): string {
	return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
}

function wsp_facebook_encrypt( string $token ) {
	if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
		return new WP_Error( 'crypto_unavailable', 'Sunucuda OpenSSL veya güvenli rastgele sayı desteği yok.' );
	}
	try {
		$iv = random_bytes( 12 );
	} catch ( Exception $error ) {
		return new WP_Error( 'crypto_unavailable', 'Güvenli rastgele sayı üretilemedi.' );
	}
	$tag = '';
	$ciphertext = openssl_encrypt( $token, 'aes-256-gcm', wsp_facebook_key(), OPENSSL_RAW_DATA, $iv, $tag );
	if ( false === $ciphertext || 16 !== strlen( $tag ) ) {
		return new WP_Error( 'crypto_failed', 'Erişim belirteci şifrelenemedi.' );
	}
	return base64_encode( $iv . $tag . $ciphertext );
}

function wsp_facebook_decrypt( string $encrypted ) {
	if ( ! function_exists( 'openssl_decrypt' ) ) {
		return new WP_Error( 'crypto_unavailable', 'Sunucuda OpenSSL desteği yok.' );
	}
	$data = base64_decode( $encrypted, true );
	if ( false === $data || strlen( $data ) <= 28 ) {
		return new WP_Error( 'crypto_failed', 'Kaydedilen belirteç okunamadı; hesabı yeniden bağlayın.' );
	}
	$token = openssl_decrypt( substr( $data, 28 ), 'aes-256-gcm', wsp_facebook_key(), OPENSSL_RAW_DATA, substr( $data, 0, 12 ), substr( $data, 12, 16 ) );
	return false === $token ? new WP_Error( 'crypto_failed', 'Kaydedilen belirteç çözülemedi; hesabı yeniden bağlayın.' ) : $token;
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
	$account = get_option( 'wsp_facebook_account', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Accounts / Hesaplar', 'wp-social-publisher' ); ?></h1>
		<?php wsp_facebook_notice(); wsp_notice(); ?>
		<h2><?php esc_html_e( 'Facebook Sayfası', 'wp-social-publisher' ); ?></h2>
		<?php if ( is_array( $account ) && ! empty( $account['page_id'] ) ) : ?>
			<p><?php echo esc_html( sprintf( __( 'Kayıtlı Sayfa: %1$s (ID: %2$s)', 'wp-social-publisher' ), $account['name'], $account['page_id'] ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wsp_facebook_disconnect">
				<?php wp_nonce_field( 'wsp_facebook_disconnect' ); ?>
				<?php submit_button( __( 'Bağlantıyı kaldır', 'wp-social-publisher' ), 'secondary' ); ?>
			</form>
		<?php endif; ?>
		<p><?php esc_html_e( 'Meta Graph API Explorer ile uygulamanız için oluşturduğunuz Sayfa erişim belirtecini ve Sayfa kimliğini girin. Kişisel Facebook şifrenizi girmeyin. Bu bilgiler için HTTPS kullanın.', 'wp-social-publisher' ); ?></p>
		<p><?php esc_html_e( 'Belirteç WordPress veritabanında şifrelenir; WordPress güvenlik anahtarlarıyla birlikte ele geçirilirse çözülebilir. Yönetici ve sunucu erişimini koruyun. Anahtarlar değişirse hesabı yeniden bağlayın.', 'wp-social-publisher' ); ?></p>
		<?php if ( ! is_ssl() ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'HTTPS gereklidir; bağlantı formu güvenli olmayan bağlantılarda kullanılamaz.', 'wp-social-publisher' ); ?></p></div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
				<input type="hidden" name="action" value="wsp_facebook_connect">
				<?php wp_nonce_field( 'wsp_facebook_connect' ); ?>
				<p><label for="wsp-page-id"><?php esc_html_e( 'Facebook Sayfa kimliği', 'wp-social-publisher' ); ?></label><br><input id="wsp-page-id" name="page_id" inputmode="numeric" pattern="[0-9]+" required></p>
				<p><label for="wsp-page-token"><?php esc_html_e( 'Sayfa erişim belirteci', 'wp-social-publisher' ); ?></label><br><input id="wsp-page-token" name="page_token" type="password" class="regular-text" autocomplete="new-password" required></p>
				<?php submit_button( __( 'Sayfayı doğrula ve bağla', 'wp-social-publisher' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
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

function wsp_share_now_page(): void {
	$account = wsp_facebook_account();
	$query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC', 'has_password' => false ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Share Now / Şimdi paylaş', 'wp-social-publisher' ); ?></h1>
		<?php wsp_facebook_notice(); wsp_notice(); ?>
		<p><?php esc_html_e( 'Facebook Sayfasına yayınlanmış bir yazıyı seçin. Önce mesaj ve bağlantı gösterilecek; yalnızca sonraki ekrandaki onayla gönderim yapılacaktır.', 'wp-social-publisher' ); ?></p>
		<?php if ( is_wp_error( $account ) ) : ?>
			<p><?php echo esc_html( $account->get_error_message() ); ?></p>
		<?php elseif ( ! $query->posts ) : ?>
			<p><?php esc_html_e( 'Paylaşılabilecek yayımlanmış ve şifresiz yazı bulunamadı.', 'wp-social-publisher' ); ?></p>
		<?php else : ?>
			<p><?php echo esc_html( sprintf( __( 'Hedef Sayfa: %s', 'wp-social-publisher' ), $account['name'] ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wsp_facebook_preview">
				<?php wp_nonce_field( 'wsp_facebook_preview' ); ?>
				<label for="wsp-post-id"><?php esc_html_e( 'Yazı seçin (en son 100 yazı)', 'wp-social-publisher' ); ?></label>
				<select id="wsp-post-id" name="post_id" required>
					<?php foreach ( $query->posts as $post ) : ?><option value="<?php echo esc_attr( (string) $post->ID ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></option><?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Gönderiyi önizle', 'wp-social-publisher' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

function wsp_facebook_preview(): void {
	wsp_facebook_admin_check( 'wsp_facebook_preview' );
	$account = wsp_facebook_account();
	$post_id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$post = get_post( $post_id );
	if ( is_wp_error( $account ) || ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password || ! current_user_can( 'edit_post', $post_id ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Hesap veya yayımlanmış şifresiz yazı bulunamadı.', true );
	}
	$custom = (string) get_post_meta( $post_id, '_wsp_facebook_text', true );
	$message = wsp_render_template( '' !== $custom ? $custom : wsp_template( 'facebook' ), $post );
	$link = get_permalink( $post );
	if ( '' === trim( $message ) || ! is_string( $link ) || ! wp_http_validate_url( $link ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Mesaj veya yazı bağlantısı geçersiz.', true );
	}
	$id = wp_generate_password( 32, false, false );
	$draft = array( 'user_id' => get_current_user_id(), 'page_id' => $account['page_id'], 'post_id' => $post_id, 'message' => $message, 'link' => $link );
	if ( ! set_transient( 'wsp_fb_draft_' . $id, $draft, 10 * MINUTE_IN_SECONDS ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önizleme kaydedilemedi. Lütfen tekrar deneyin.', true );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Facebook gönderisini onayla', 'wp-social-publisher' ); ?></h1>
		<p><strong><?php esc_html_e( 'Hedef Sayfa:', 'wp-social-publisher' ); ?></strong> <?php echo esc_html( $account['name'] ); ?></p>
		<p><strong><?php esc_html_e( 'Mesaj:', 'wp-social-publisher' ); ?></strong></p>
		<div style="white-space:pre-wrap;background:#fff;border:1px solid #dcdcde;padding:15px;max-width:700px"><?php echo esc_html( $message ); ?></div>
		<p><strong><?php esc_html_e( 'Bağlantı:', 'wp-social-publisher' ); ?></strong> <?php echo esc_html( $link ); ?></p>
		<p><?php esc_html_e( 'Onayladığınızda bu gönderi hemen Facebook Sayfasında yayımlanır. Görsel ayrıca yüklenmez. Meta bağlantı önizlemesini kendi kurallarına göre oluşturabilir.', 'wp-social-publisher' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wsp_facebook_publish">
			<input type="hidden" name="draft" value="<?php echo esc_attr( $id ); ?>">
			<?php wp_nonce_field( 'wsp_facebook_publish_' . $id ); ?>
			<?php submit_button( __( 'Onayla ve Facebook’ta paylaş', 'wp-social-publisher' ) ); ?>
		</form>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-share-now' ) ); ?>"><?php esc_html_e( 'İptal', 'wp-social-publisher' ); ?></a>
	</div>
	<?php
}
add_action( 'admin_post_wsp_facebook_preview', 'wsp_facebook_preview' );

function wsp_facebook_publish(): void {
	$id = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) ? wp_unslash( $_POST['draft'] ) : '';
	if ( ! preg_match( '/^[a-zA-Z0-9]{32}$/', $id ) ) {
		wp_die( esc_html__( 'Geçersiz önizleme.', 'wp-social-publisher' ) );
	}
	wsp_facebook_admin_check( 'wsp_facebook_publish_' . $id );
	$draft = get_transient( 'wsp_fb_draft_' . $id );
	$account = wsp_facebook_account();
	if ( ! is_array( $draft ) || is_wp_error( $account ) || get_current_user_id() !== $draft['user_id'] || $account['page_id'] !== $draft['page_id'] || ! current_user_can( 'edit_post', $draft['post_id'] ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Önizleme geçersiz, süresi dolmuş veya hesap değişmiş. Yeniden önizleyin.', true );
	}
	$post = get_post( $draft['post_id'] );
	if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password || get_permalink( $post ) !== $draft['link'] ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Yazı durumu veya bağlantısı değişti. Gönderi yapılmadı; yeniden önizleyin.', true );
	}
	if ( ! add_option( 'wsp_fb_attempt_' . $id, time(), '', false ) ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Bu onay zaten kullanılmış veya gönderim kaydı oluşturulamamış. Yinelenmeyi önlemek için Facebook Sayfasını kontrol edin.', true );
	}
	delete_transient( 'wsp_fb_draft_' . $id );
	$result = wsp_facebook_request( $account['page_id'] . '/feed', $account['token'], 'POST', array( 'message' => $draft['message'], 'link' => $draft['link'] ) );
	if ( is_wp_error( $result ) ) {
		wsp_facebook_redirect( 'wsp-share-now', $result->get_error_message(), true );
	}
	if ( ! isset( $result['id'] ) || ! is_string( $result['id'] ) || '' === $result['id'] ) {
		wsp_facebook_redirect( 'wsp-share-now', 'Meta gönderi kimliği döndürmedi. Sonuç belirsiz; tekrar denemeden önce Facebook Sayfasını kontrol edin.', true );
	}
	wsp_facebook_redirect( 'wsp-share-now', 'Facebook gönderisi oluşturuldu. Gönderi kimliği: ' . sanitize_text_field( $result['id'] ) );
}
add_action( 'admin_post_wsp_facebook_publish', 'wsp_facebook_publish' );
