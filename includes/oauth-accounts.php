<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wsp_oauth_providers(): array {
	return array(
		'x' => array(
			'name'          => 'Twitter / X',
			'authorize_url' => 'https://x.com/i/oauth2/authorize',
			'token_url'     => 'https://api.x.com/2/oauth2/token',
			'profile_url'   => 'https://api.x.com/2/users/me?user.fields=name%2Cusername',
			'scopes'        => array( 'users.read', 'tweet.read', 'tweet.write', 'offline.access' ),
			'profile'       => 'x',
		),
		'linkedin' => array(
			'name'          => 'LinkedIn',
			'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
			'token_url'     => 'https://www.linkedin.com/oauth/v2/accessToken',
			'profile_url'   => 'https://api.linkedin.com/v2/userinfo',
			'scopes'        => array( 'openid', 'profile', 'w_member_social' ),
			'profile'       => 'linkedin',
		),
	);
}

function wsp_oauth_apps(): array {
	$apps = get_option( 'wsp_oauth_apps', array() );
	return is_array( $apps ) ? $apps : array();
}

function wsp_oauth_app( string $platform ): array {
	$apps = wsp_oauth_apps();
	$app = isset( $apps[ $platform ] ) && is_array( $apps[ $platform ] ) ? $apps[ $platform ] : array();
	$secret = isset( $app['client_secret'] ) && is_string( $app['client_secret'] ) && '' !== $app['client_secret']
		? wsp_decrypt_secret( $app['client_secret'] )
		: '';
	if ( is_wp_error( $secret ) ) {
		return $secret;
	}
	return array(
		'client_id'     => isset( $app['client_id'] ) && is_string( $app['client_id'] ) ? $app['client_id'] : '',
		'client_secret' => $secret,
	);
}

function wsp_oauth_callback_url( string $platform ): string {
	return isset( wsp_oauth_providers()[ $platform ] ) ? admin_url( 'admin-post.php' ) : '';
}

function wsp_oauth_account( string $platform ) {
	$accounts = get_option( 'wsp_oauth_accounts', array() );
	$account = is_array( $accounts ) && isset( $accounts[ $platform ] ) && is_array( $accounts[ $platform ] )
		? $accounts[ $platform ]
		: array();
	if ( empty( $account['access_token'] ) || ! is_string( $account['access_token'] ) ) {
		return new WP_Error( 'not_connected', 'Hesap henüz bağlanmadı.' );
	}
	$account['access_token'] = wsp_decrypt_secret( $account['access_token'] );
	if ( is_wp_error( $account['access_token'] ) ) {
		return $account['access_token'];
	}
	if ( ! empty( $account['refresh_token'] ) ) {
		$account['refresh_token'] = wsp_decrypt_secret( $account['refresh_token'] );
		if ( is_wp_error( $account['refresh_token'] ) ) {
			return $account['refresh_token'];
		}
	}
	if ( 'x' === $platform && ! empty( $account['refresh_token'] ) && ! empty( $account['expires_at'] ) && (int) $account['expires_at'] <= time() + 60 ) {
		$app = wsp_oauth_app( $platform );
		if ( is_wp_error( $app ) ) {
			return $app;
		}
		$tokens = wsp_oauth_token_request(
			$platform,
			array( 'grant_type' => 'refresh_token', 'refresh_token' => $account['refresh_token'] ),
			$app
		);
		if ( is_wp_error( $tokens ) ) {
			return $tokens;
		}
		$encrypted_access = wsp_encrypt_secret( $tokens['access_token'] );
		$refresh = isset( $tokens['refresh_token'] ) && is_string( $tokens['refresh_token'] ) ? $tokens['refresh_token'] : $account['refresh_token'];
		$encrypted_refresh = wsp_encrypt_secret( $refresh );
		if ( is_wp_error( $encrypted_access ) || is_wp_error( $encrypted_refresh ) ) {
			return new WP_Error( 'oauth_crypto', 'Yenilenen X belirteci güvenli biçimde kaydedilemedi.' );
		}
		$account['access_token'] = $encrypted_access;
		$account['refresh_token'] = $encrypted_refresh;
		$account['expires_at'] = isset( $tokens['expires_in'] ) && is_numeric( $tokens['expires_in'] ) ? time() + (int) $tokens['expires_in'] : 0;
		$accounts[ $platform ] = $account;
		update_option( 'wsp_oauth_accounts', $accounts, false );
		if ( get_option( 'wsp_oauth_accounts' ) !== $accounts ) {
			return new WP_Error( 'oauth_storage', 'Yenilenen X belirteci kaydedilemedi.' );
		}
		$account['access_token'] = $tokens['access_token'];
		$account['refresh_token'] = $refresh;
	}
	return $account;
}

function wsp_oauth_profile( string $platform, string $access_token ) {
	$providers = wsp_oauth_providers();
	if ( ! isset( $providers[ $platform ] ) ) {
		return new WP_Error( 'unsupported_platform', 'Bu platform için OAuth bağlantısı desteklenmiyor.' );
	}
	$response = wp_remote_get(
		$providers[ $platform ]['profile_url'],
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Accept'        => 'application/json',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'profile_network', 'Platform hesabı doğrulanamadı. Bağlantıyı yeniden deneyin.' );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) {
		return new WP_Error( 'profile_failed', 'Platform hesabı doğrulanamadı. İzinleri veya uygulama ayarlarını kontrol edin.' );
	}
	if ( 'x' === $platform ) {
		$profile = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
		$id = isset( $profile['id'] ) && is_string( $profile['id'] ) ? $profile['id'] : '';
		$name = isset( $profile['name'] ) && is_string( $profile['name'] ) ? $profile['name'] : '';
		$username = isset( $profile['username'] ) && is_string( $profile['username'] ) ? $profile['username'] : '';
		if ( '' === $id || '' === $username ) {
			return new WP_Error( 'profile_invalid', 'X hesabı yanıtında gerekli profil bilgileri yok.' );
		}
		return array( 'id' => $id, 'name' => $name, 'username' => $username );
	}
	if ( empty( $data['sub'] ) || ! is_string( $data['sub'] ) ) {
		return new WP_Error( 'profile_invalid', 'LinkedIn hesabı yanıtında gerekli profil bilgileri yok.' );
	}
	$name = isset( $data['name'] ) && is_string( $data['name'] ) ? $data['name'] : '';
	return array( 'id' => $data['sub'], 'name' => $name, 'username' => '' );
}

function wsp_oauth_notice( string $platform ): void {
	$key = 'wsp_oauth_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	if ( ! is_array( $notice ) || empty( $notice['message'] ) || ( isset( $notice['platform'] ) && $platform !== $notice['platform'] ) ) {
		return;
	}
	delete_transient( $key );
	echo '<div class="notice ' . ( ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
}

function wsp_oauth_redirect( string $platform, string $message, bool $error = false ): void {
	set_transient(
		'wsp_oauth_notice_' . get_current_user_id(),
		array( 'platform' => $platform, 'message' => $message, 'error' => $error ),
		60
	);
	wp_safe_redirect( admin_url( 'admin.php?page=wsp-accounts&platform=' . rawurlencode( $platform ) ) );
	exit;
}

function wsp_oauth_test_form( string $platform ): void {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
		<input type="hidden" name="action" value="wsp_oauth_test">
		<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
		<?php wp_nonce_field( 'wsp_oauth_test_' . $platform ); ?>
		<?php submit_button( __( 'Bağlantıyı test et', 'wp-social-publisher' ), 'secondary', 'submit', false ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
		<input type="hidden" name="action" value="wsp_oauth_disconnect">
		<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
		<?php wp_nonce_field( 'wsp_oauth_disconnect_' . $platform ); ?>
		<?php submit_button( __( 'Hesap bağlantısını kaldır', 'wp-social-publisher' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}

function wsp_render_accounts_page(): void {
	$platforms = wsp_platforms();
	$platform = isset( $_GET['platform'] ) && is_string( $_GET['platform'] ) ? sanitize_key( wp_unslash( $_GET['platform'] ) ) : 'facebook';
	if ( ! isset( $platforms[ $platform ] ) ) {
		$platform = 'facebook';
	}
	$facebook = get_option( 'wsp_facebook_account', array() );
	$apps = wsp_oauth_apps();
	$oauth_accounts = get_option( 'wsp_oauth_accounts', array() );
	$oauth_accounts = is_array( $oauth_accounts ) ? $oauth_accounts : array();
	$oauth_platforms = wsp_oauth_providers();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Accounts / Hesaplar', 'wp-social-publisher' ); ?></h1>
		<?php wsp_facebook_notice(); wsp_oauth_notice( $platform ); wsp_notice(); ?>
		<p><?php esc_html_e( 'Bir platform seçerek hesap bağlantısı ayrıntılarını görüntüleyin.', 'wp-social-publisher' ); ?></p>
		<div class="wsp-accounts-grid">
			<nav class="wsp-template-platforms" aria-label="<?php esc_attr_e( 'Hesap platformları', 'wp-social-publisher' ); ?>">
				<h2><?php esc_html_e( 'Platform', 'wp-social-publisher' ); ?></h2>
				<?php foreach ( $platforms as $key => $label ) : ?>
					<a class="wsp-platform-tab<?php echo $platform === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'platform', $key, admin_url( 'admin.php?page=wsp-accounts' ) ) ); ?>" <?php echo $platform === $key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<section class="wsp-template-fields" aria-labelledby="wsp-account-heading">
				<?php if ( 'facebook' === $platform ) : ?>
					<h2 id="wsp-account-heading"><?php esc_html_e( 'Facebook Sayfası', 'wp-social-publisher' ); ?></h2>
					<?php if ( is_array( $facebook ) && ! empty( $facebook['page_id'] ) ) : ?>
						<p><?php echo esc_html( sprintf( __( 'Kayıtlı Sayfa: %1$s (ID: %2$s)', 'wp-social-publisher' ), $facebook['name'], $facebook['page_id'] ) ); ?></p>
						<p class="description"><?php esc_html_e( 'Sayfa kaydedildi; erişim belirtecinin ve paylaşım yetkisinin güncel durumu burada kontrol edilmez.', 'wp-social-publisher' ); ?></p>
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
				<?php elseif ( isset( $oauth_platforms[ $platform ] ) ) : ?>
					<?php wsp_render_oauth_account( $platform, $oauth_platforms[ $platform ], $apps, $oauth_accounts ); ?>
				<?php elseif ( in_array( $platform, array( 'medium', 'substack' ), true ) ) : ?>
					<h2 id="wsp-account-heading"><?php echo esc_html( $platforms[ $platform ] ); ?></h2>
					<p><strong><?php esc_html_e( 'Resmi hesap bağlantısı desteklenmiyor', 'wp-social-publisher' ); ?></strong></p>
					<p><?php esc_html_e( 'Bu eklenti parola toplamaz ve resmi olmayan tarayıcı otomasyonuyla giriş yapmaz. Platform tarafından desteklenen resmi bir OAuth/API yayınlama yöntemi sunulursa bu alan güncellenebilir.', 'wp-social-publisher' ); ?></p>
				<?php endif; ?>
			</section>
		</div>
	</div>
	<?php
}

function wsp_render_oauth_account( string $platform, array $provider, array $apps, array $accounts ): void {
	$app = isset( $apps[ $platform ] ) && is_array( $apps[ $platform ] ) ? $apps[ $platform ] : array();
	$account = isset( $accounts[ $platform ] ) && is_array( $accounts[ $platform ] ) ? $accounts[ $platform ] : array();
	$has_account = ! empty( $account['access_token'] ) && ! empty( $account['profile'] ) && is_array( $account['profile'] );
	$secret = isset( $app['client_secret'] ) && is_string( $app['client_secret'] ) ? $app['client_secret'] : '';
	$profile_identifier = ! empty( $account['profile']['username'] )
		? '@' . $account['profile']['username']
		: ( $account['profile']['id'] ?? '' );
	?>
	<h2 id="wsp-account-heading"><?php echo esc_html( $provider['name'] ); ?></h2>
	<p><?php esc_html_e( 'Bu platform için ayrı bir geliştirici uygulaması gerekir. Uygulama bilgileri bu sitede saklanır; kullanıcı girişi platformun resmi OAuth sayfasında yapılır.', 'wp-social-publisher' ); ?></p>
	<?php if ( $has_account ) : ?>
		<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( __( 'Doğrulanan hesap: %1$s (%2$s)', 'wp-social-publisher' ), $account['profile']['name'], $profile_identifier ) ); ?></p></div>
		<?php wsp_oauth_test_form( $platform ); ?>
	<?php endif; ?>
	<?php if ( ! is_ssl() ) : ?>
		<div class="notice notice-error inline"><p><?php esc_html_e( 'OAuth bağlantısı için WordPress yönetimi HTTPS üzerinden açılmalıdır.', 'wp-social-publisher' ); ?></p></div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
			<input type="hidden" name="action" value="wsp_oauth_save_app">
			<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
			<?php wp_nonce_field( 'wsp_oauth_save_app_' . $platform ); ?>
			<p><label for="wsp-client-id"><?php esc_html_e( 'Client ID', 'wp-social-publisher' ); ?></label><br>
				<input id="wsp-client-id" name="client_id" type="text" class="regular-text" value="<?php echo esc_attr( isset( $app['client_id'] ) && is_string( $app['client_id'] ) ? $app['client_id'] : '' ); ?>" autocomplete="off" required></p>
			<p><label for="wsp-client-secret"><?php esc_html_e( 'Client Secret', 'wp-social-publisher' ); ?></label><br>
				<input id="wsp-client-secret" name="client_secret" type="password" class="regular-text" autocomplete="new-password" <?php echo '' === $secret ? 'required' : ''; ?>>
				<span class="description"><?php echo '' !== $secret ? esc_html__( 'Gizli anahtar kayıtlı. Değiştirmek istemiyorsanız boş bırakın.', 'wp-social-publisher' ) : esc_html__( 'Geliştirici uygulamanızdan alınır; sohbet veya herkese açık kodda paylaşmayın.', 'wp-social-publisher' ); ?></span></p>
			<p><strong><?php esc_html_e( 'OAuth Redirect URI', 'wp-social-publisher' ); ?></strong><br>
				<code><?php echo esc_html( wsp_oauth_callback_url( $platform ) ); ?></code><br>
				<span class="description"><?php esc_html_e( 'Bu adresi geliştirici uygulamanızdaki tam eşleşen Redirect / Callback URL listesine ekleyin.', 'wp-social-publisher' ); ?></span></p>
			<?php submit_button( __( 'Uygulama bilgilerini kaydet', 'wp-social-publisher' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php if ( ! empty( $app['client_id'] ) && '' !== $secret ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="wsp_oauth_start">
				<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
				<?php wp_nonce_field( 'wsp_oauth_start_' . $platform ); ?>
				<?php submit_button( $has_account ? __( 'Başka hesapla bağlan', 'wp-social-publisher' ) : sprintf( __( '%s ile güvenli giriş', 'wp-social-publisher' ), $provider['name'] ), 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( $has_account ) : ?>
		<hr>
		<h3><?php esc_html_e( 'Bağlantıyı kaldır', 'wp-social-publisher' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Bu işlem belirteçleri bu WordPress sitesinden siler. Sağlayıcıdaki uygulama yetkisini ayrıca geri almanız gerekebilir.', 'wp-social-publisher' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wsp_oauth_disconnect">
			<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
			<?php wp_nonce_field( 'wsp_oauth_disconnect_' . $platform ); ?>
			<?php submit_button( __( 'Hesap bağlantısını kaldır', 'wp-social-publisher' ), 'secondary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
	<?php if ( 'x' === $platform ) : ?>
		<p class="description"><?php esc_html_e( 'X Developer Portal’da Web App/Confidential Client türünde OAuth 2.0 Authorization Code Flow with PKCE kurun. Callback adresini aynen kopyalayın; users.read, tweet.read, tweet.write ve offline.access izinlerini etkinleştirin. API erişimi ve yazma izni uygulamanızın planına bağlı olabilir.', 'wp-social-publisher' ); ?></p>
		<p><a href="https://developer.x.com/en/portal/dashboard" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'X Developer Portal’ı aç', 'wp-social-publisher' ); ?></a></p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'LinkedIn Developer Portal’da uygulama oluşturup burada gösterilen callback adresini Authorized redirect URLs listesine ekleyin. Giriş için Sign In with LinkedIn using OpenID Connect; gönderi izni için ayrıca Share on LinkedIn ürününü etkinleştirin. Ürün erişimi LinkedIn onayı gerektirebilir.', 'wp-social-publisher' ); ?></p>
		<p><a href="https://www.linkedin.com/developers/apps" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LinkedIn Developer Portal’ı aç', 'wp-social-publisher' ); ?></a></p>
	<?php endif; ?>
<?php
}

function wsp_oauth_platform_from_post(): string {
	$platform = isset( $_POST['platform'] ) && is_string( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
	return isset( wsp_oauth_providers()[ $platform ] ) ? $platform : '';
}

function wsp_oauth_require_admin( string $platform, string $action ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'wp-social-publisher' ) );
	}
	if ( '' === $platform ) {
		wp_die( esc_html__( 'Desteklenmeyen platform.', 'wp-social-publisher' ) );
	}
	check_admin_referer( $action . '_' . $platform );
}

function wsp_oauth_save_app(): void {
	$platform = wsp_oauth_platform_from_post();
	wsp_oauth_require_admin( $platform, 'wsp_oauth_save_app' );
	if ( ! is_ssl() ) {
		wsp_oauth_redirect( $platform, 'Uygulama bilgilerini kaydetmek için HTTPS gereklidir.', true );
	}
	$client_id = isset( $_POST['client_id'] ) && is_string( $_POST['client_id'] ) ? trim( wp_unslash( $_POST['client_id'] ) ) : '';
	$client_secret = isset( $_POST['client_secret'] ) && is_string( $_POST['client_secret'] ) ? trim( wp_unslash( $_POST['client_secret'] ) ) : '';
	if ( '' === $client_id || strlen( $client_id ) > 512 || preg_match( '/[\r\n]/', $client_id ) || strlen( $client_secret ) > 4096 || preg_match( '/\s/', $client_secret ) ) {
		wsp_oauth_redirect( $platform, 'Client ID veya gizli anahtar geçersiz.', true );
	}
	$apps = wsp_oauth_apps();
	$existing = isset( $apps[ $platform ] ) && is_array( $apps[ $platform ] ) ? $apps[ $platform ] : array();
	$encrypted = isset( $existing['client_secret'] ) && is_string( $existing['client_secret'] ) ? $existing['client_secret'] : '';
	if ( '' !== $client_secret ) {
		$encrypted = wsp_encrypt_secret( $client_secret );
		if ( is_wp_error( $encrypted ) ) {
			wsp_oauth_redirect( $platform, $encrypted->get_error_message(), true );
		}
	}
	if ( '' === $encrypted ) {
		wsp_oauth_redirect( $platform, 'Client Secret gereklidir.', true );
	}
	$client_id = sanitize_text_field( $client_id );
	$client_id_changed = isset( $existing['client_id'] ) && $existing['client_id'] !== $client_id;
	$apps[ $platform ] = array( 'client_id' => $client_id, 'client_secret' => $encrypted );
	update_option( 'wsp_oauth_apps', $apps, false );
	if ( get_option( 'wsp_oauth_apps' ) !== $apps ) {
		wsp_oauth_redirect( $platform, 'Uygulama bilgileri kaydedilemedi.', true );
	}
	if ( $client_id_changed ) {
		$accounts = get_option( 'wsp_oauth_accounts', array() );
		if ( is_array( $accounts ) && isset( $accounts[ $platform ] ) ) {
			unset( $accounts[ $platform ] );
			update_option( 'wsp_oauth_accounts', $accounts, false );
		}
		wsp_oauth_redirect( $platform, 'Uygulama Client ID değişti. Hesap bağlantısı kaldırıldı; yeni uygulamayla tekrar giriş yapın.' );
	}
	wsp_oauth_redirect( $platform, 'Uygulama bilgileri güvenli biçimde kaydedildi.' );
}
add_action( 'admin_post_wsp_oauth_save_app', 'wsp_oauth_save_app' );

function wsp_oauth_start(): void {
	$platform = wsp_oauth_platform_from_post();
	wsp_oauth_require_admin( $platform, 'wsp_oauth_start' );
	if ( ! is_ssl() ) {
		wsp_oauth_redirect( $platform, 'OAuth bağlantısı için HTTPS gereklidir.', true );
	}
	$providers = wsp_oauth_providers();
	$app = wsp_oauth_app( $platform );
	if ( is_wp_error( $app ) || '' === $app['client_id'] || '' === $app['client_secret'] ) {
		wsp_oauth_redirect( $platform, is_wp_error( $app ) ? $app->get_error_message() : 'Önce geçerli geliştirici uygulaması bilgilerini kaydedin.', true );
	}
	try {
		$state = bin2hex( random_bytes( 32 ) );
		$verifier = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	} catch ( Exception $error ) {
		wsp_oauth_redirect( $platform, 'Güvenli OAuth doğrulama kodu oluşturulamadı.', true );
	}
	$encrypted_verifier = wsp_encrypt_secret( $verifier );
	if ( is_wp_error( $encrypted_verifier ) ) {
		wsp_oauth_redirect( $platform, $encrypted_verifier->get_error_message(), true );
	}
	$state_key = 'wsp_oauth_state_' . hash( 'sha256', $state );
	$stored = array( 'platform' => $platform, 'user_id' => get_current_user_id(), 'verifier' => $encrypted_verifier );
	set_transient( $state_key, $stored, 10 * MINUTE_IN_SECONDS );
	if ( get_transient( $state_key ) !== $stored ) {
		wsp_oauth_redirect( $platform, 'OAuth doğrulama durumu kaydedilemedi. WordPress geçici önbellek ayarlarını kontrol edin.', true );
	}
	$arguments = array(
		'response_type' => 'code',
		'client_id'     => $app['client_id'],
		'redirect_uri'  => wsp_oauth_callback_url( $platform ),
		'scope'         => implode( ' ', $providers[ $platform ]['scopes'] ),
		'state'         => $state,
	);
	if ( 'x' === $platform ) {
		$arguments['code_challenge'] = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
		$arguments['code_challenge_method'] = 'S256';
	}
	$url = $providers[ $platform ]['authorize_url'] . '?' . http_build_query( $arguments, '', '&', PHP_QUERY_RFC3986 );
	wp_redirect( $url, 302, 'WP Social Publisher' );
	exit;
}
add_action( 'admin_post_wsp_oauth_start', 'wsp_oauth_start' );

function wsp_oauth_token_request( string $platform, array $body, array $app ) {
	$providers = wsp_oauth_providers();
	$headers = array( 'Accept' => 'application/json' );
	if ( 'x' === $platform ) {
		$headers['Authorization'] = 'Basic ' . base64_encode( $app['client_id'] . ':' . $app['client_secret'] );
		$body['client_id'] = $app['client_id'];
	} else {
		$body['client_id'] = $app['client_id'];
		$body['client_secret'] = $app['client_secret'];
	}
	$response = wp_remote_post(
		$providers[ $platform ]['token_url'],
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'headers'     => $headers,
			'body'        => $body,
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'oauth_network', 'Platform token servisine bağlanılamadı.' );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) || empty( $data['access_token'] ) || ! is_string( $data['access_token'] ) ) {
		return new WP_Error( 'oauth_token', 'Platform girişini tamamlayamadı. Redirect URI, uygulama bilgileri, ürün erişimi ve izinleri kontrol edin.' );
	}
	return $data;
}

function wsp_oauth_callback() {
	if ( ! is_ssl() ) {
		wp_die( esc_html__( 'Bu OAuth callback geçersiz veya HTTPS kullanmıyor.', 'wp-social-publisher' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'OAuth dönüşünü tamamlamak için aynı WordPress yönetici hesabında oturum açık olmalıdır.', 'wp-social-publisher' ) );
	}
	$state = isset( $_GET['state'] ) && is_string( $_GET['state'] ) ? wp_unslash( $_GET['state'] ) : '';
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $state ) ) {
		wp_die( esc_html__( 'OAuth state geçersiz veya süresi dolmuş. Yeniden bağlanmayı deneyin.', 'wp-social-publisher' ) );
	}
	$state_key = 'wsp_oauth_state_' . hash( 'sha256', $state );
	$stored = get_transient( $state_key );
	delete_transient( $state_key );
	$platform = is_array( $stored ) && isset( $stored['platform'] ) && is_string( $stored['platform'] ) ? $stored['platform'] : '';
	if ( ! isset( wsp_oauth_providers()[ $platform ] ) || ! is_array( $stored ) || get_current_user_id() !== ( $stored['user_id'] ?? 0 ) ) {
		wp_die( esc_html__( 'OAuth isteği bu yönetici oturumuyla eşleşmiyor veya süresi dolmuş. WordPress’e tekrar giriş yapıp yeniden deneyin.', 'wp-social-publisher' ) );
	}
	$error = isset( $_GET['error'] ) && is_string( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
	if ( '' !== $error ) {
		wsp_oauth_redirect( $platform, 'Platform giriş isteğini tamamlamadı. Hesap, uygulama rolü ve gerekli izinleri kontrol edin.', true );
	}
	$code = isset( $_GET['code'] ) && is_string( $_GET['code'] ) ? wp_unslash( $_GET['code'] ) : '';
	if ( '' === $code || strlen( $code ) > 4096 ) {
		wp_die( esc_html__( 'Platform geçerli bir authorization code döndürmedi.', 'wp-social-publisher' ) );
	}
	$app = wsp_oauth_app( $platform );
	if ( is_wp_error( $app ) || '' === $app['client_id'] || '' === $app['client_secret'] ) {
		wsp_oauth_redirect( $platform, 'Uygulama bilgileri okunamadı. Yeniden kaydedip tekrar deneyin.', true );
	}
	$body = array(
		'grant_type'   => 'authorization_code',
		'code'         => $code,
		'redirect_uri' => wsp_oauth_callback_url( $platform ),
	);
	if ( 'x' === $platform ) {
		$verifier = isset( $stored['verifier'] ) && is_string( $stored['verifier'] ) ? wsp_decrypt_secret( $stored['verifier'] ) : new WP_Error( 'pkce_verifier', 'PKCE doğrulama kodu bulunamadı.' );
		if ( is_wp_error( $verifier ) ) {
			wsp_oauth_redirect( $platform, 'OAuth doğrulama kodu çözülemedi. Yeniden bağlanın.', true );
		}
		$body['code_verifier'] = $verifier;
	}
	$tokens = wsp_oauth_token_request( $platform, $body, $app );
	if ( is_wp_error( $tokens ) ) {
		wsp_oauth_redirect( $platform, $tokens->get_error_message(), true );
	}
	$profile = wsp_oauth_profile( $platform, $tokens['access_token'] );
	if ( is_wp_error( $profile ) ) {
		wsp_oauth_redirect( $platform, $profile->get_error_message(), true );
	}
	$access_token = wsp_encrypt_secret( $tokens['access_token'] );
	$refresh_token = ! empty( $tokens['refresh_token'] ) && is_string( $tokens['refresh_token'] ) ? wsp_encrypt_secret( $tokens['refresh_token'] ) : '';
	if ( is_wp_error( $access_token ) || is_wp_error( $refresh_token ) ) {
		wsp_oauth_redirect( $platform, 'OAuth belirteci güvenli biçimde saklanamadı.', true );
	}
	$accounts = get_option( 'wsp_oauth_accounts', array() );
	$accounts = is_array( $accounts ) ? $accounts : array();
	$accounts[ $platform ] = array(
		'profile'       => $profile,
		'access_token'  => $access_token,
		'refresh_token' => $refresh_token,
		'expires_at'    => isset( $tokens['expires_in'] ) && is_numeric( $tokens['expires_in'] ) ? time() + (int) $tokens['expires_in'] : 0,
	);
	update_option( 'wsp_oauth_accounts', $accounts, false );
	if ( get_option( 'wsp_oauth_accounts' ) !== $accounts ) {
		wsp_oauth_redirect( $platform, 'Hesap doğrulandı ancak kaydedilemedi.', true );
	}
	wsp_oauth_redirect( $platform, sprintf( 'Giriş doğrulandı: %s (%s). Bu test, daha sonra gönderi paylaşma iznini garanti etmez.', $profile['name'], 'x' === $platform ? '@' . $profile['username'] : $profile['id'] ) );
}

function wsp_oauth_maybe_handle_callback(): void {
	$script = isset( $_SERVER['PHP_SELF'] ) && is_string( $_SERVER['PHP_SELF'] ) ? wp_basename( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';
	$has_response = isset( $_GET['state'] ) && ( isset( $_GET['code'] ) || isset( $_GET['error'] ) );
	if ( 'admin-post.php' === $script && $has_response ) {
		wsp_oauth_callback();
		exit;
	}
}
add_action( 'admin_init', 'wsp_oauth_maybe_handle_callback', 0 );

function wsp_oauth_test(): void {
	$platform = wsp_oauth_platform_from_post();
	wsp_oauth_require_admin( $platform, 'wsp_oauth_test' );
	$account = wsp_oauth_account( $platform );
	if ( is_wp_error( $account ) ) {
		wsp_oauth_redirect( $platform, $account->get_error_message(), true );
	}
	$profile = wsp_oauth_profile( $platform, $account['access_token'] );
	if ( is_wp_error( $profile ) ) {
		wsp_oauth_redirect( $platform, $profile->get_error_message(), true );
	}
	wsp_oauth_redirect( $platform, 'Bağlantı testi başarılı: ' . $profile['name'] . ( 'x' === $platform ? ' (@' . $profile['username'] . ')' : '' ) );
}
add_action( 'admin_post_wsp_oauth_test', 'wsp_oauth_test' );

function wsp_oauth_disconnect(): void {
	$platform = wsp_oauth_platform_from_post();
	wsp_oauth_require_admin( $platform, 'wsp_oauth_disconnect' );
	$accounts = get_option( 'wsp_oauth_accounts', array() );
	if ( is_array( $accounts ) ) {
		unset( $accounts[ $platform ] );
		update_option( 'wsp_oauth_accounts', $accounts, false );
		if ( get_option( 'wsp_oauth_accounts' ) !== $accounts ) {
			wsp_oauth_redirect( $platform, 'Hesap bağlantısı kaldırılamadı.', true );
		}
	}
	wsp_oauth_redirect( $platform, 'Hesap belirteçleri bu WordPress sitesinden kaldırıldı. Sağlayıcıdaki uygulama yetkisini ayrıca iptal edin.' );
}
add_action( 'admin_post_wsp_oauth_disconnect', 'wsp_oauth_disconnect' );
