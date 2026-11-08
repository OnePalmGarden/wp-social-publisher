<?php

define( 'ABSPATH', __DIR__ );
$GLOBALS['meta'] = array();
$GLOBALS['options'] = array();
$GLOBALS['permitted'] = true;
$GLOBALS['posts'] = array();
$GLOBALS['enqueued'] = array();
$GLOBALS['transients'] = array();
define( 'MINUTE_IN_SECONDS', 60 );

class WP_Post {
	public int $ID;
	public string $post_status = 'publish';
	public string $post_type = 'post';
	public string $post_password = '';
	public function __construct( int $id ) { $this->ID = $id; }
}
class WP_Error {
	private string $message;
	public function __construct( string $code, string $message ) { $this->message = $message; }
	public function get_error_message(): string { return $this->message; }
}
class WP_Query {
	public array $posts = array();
	public int $max_num_pages = 1;
	public function __construct( array $args ) {
		$GLOBALS['last_query'] = $args;
		$this->posts = $GLOBALS['posts'];
	}
}
function add_action( ...$args ) {}
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function is_ssl() { return $GLOBALS['ssl'] ?? true; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][ $id ][ $key ] = $value; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['meta'][ $id ][ $key ] ); }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) { return 'valid' === $nonce; }
function wp_is_post_revision( $id ) { return false; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt( $scheme ) { return 'test-only-salt-' . $scheme; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_request( $url, $args ) { $GLOBALS['request'] = array( $url, $args ); return $GLOBALS['response']; }
function wp_remote_get( $url, $args ) { $GLOBALS['profile_request'] = array( $url, $args ); return $GLOBALS['profile_response']; }
function wp_remote_post( $url, $args ) { $GLOBALS['token_request'] = array( $url, $args ); return $GLOBALS['token_response']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function get_post( $id ) { return $GLOBALS['test_post'] ?? null; }
function get_current_user_id() { return 7; }
function check_admin_referer( $action ) { if ( ! $GLOBALS['nonce_valid'] ) { throw new RuntimeException( 'invalid nonce' ); } }
function set_transient( $key, $value, $expiry ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function wp_safe_redirect( $url ) { throw new RuntimeException( 'redirect: ' . $url ); }
function wp_redirect( $url, $status, $x_redirect_by ) { $GLOBALS['external_redirect'] = $url; throw new RuntimeException( 'external redirect' ); }
function wp_die( $message ) { throw new RuntimeException( 'die: ' . $message ); }
function wp_generate_password( $length, $special, $extra ) { return str_repeat( 'a', $length ); }
function wp_basename( $path ) { return basename( $path ); }
function wp_http_validate_url( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ); }
function add_option( $key, $value, $deprecated, $autoload ) {
	if ( array_key_exists( $key, $GLOBALS['options'] ) ) { return false; }
	$GLOBALS['options'][ $key ] = $value;
	return true;
}
function current_user_can( ...$args ) { return $GLOBALS['permitted']; }
function get_post_type( $id ) { return 'post'; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $value ) ); }
function wp_timezone() { return new DateTimeZone( 'Europe/Istanbul' ); }
function get_the_title( $post ) { return 'Deneme başlığı'; }
function get_the_excerpt( $post ) { return '<p>Özet metni</p>'; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function get_permalink( $post ) { return 'https://example.test/deneme/'; }
function get_the_tags( $id ) { return array( (object) array( 'name' => 'WordPress' ), (object) array( 'name' => 'Sosyal medya' ) ); }
function get_bloginfo( $show ) { return 'Deneme sitesi'; }
function __( $value, ...$args ) { return $value; }
function esc_html__( $value, ...$args ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_html_e( $value, ...$args ) { echo esc_html__( $value ); }
function esc_attr_e( $value, ...$args ) { echo esc_attr( $value ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_textarea( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function wp_nonce_field( ...$args ) {}
function checked( $value ) { if ( $value ) { echo 'checked'; } }
function wp_date( $format, $time, $timezone ) { return ( new DateTimeImmutable( '@' . $time ) )->setTimezone( $timezone )->format( $format ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function add_query_arg( $key, $value = null, $url = null ) {
	if ( is_array( $key ) ) {
		$url = $value;
		$arguments = $key;
	} else {
		$arguments = array( $key => $value );
	}
	if ( null === $url ) {
		$url = '';
	}
	return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $arguments );
}
function get_edit_post_link( $id ) { return admin_url( 'post.php?post=' . $id ); }
function get_post_status_object( $status ) { return (object) array( 'label' => $status ); }
function paginate_links( $args ) { return ''; }
function wp_kses_post( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function plugin_dir_url( $file ) { return 'https://example.test/wp-content/plugins/wp-social-publisher/'; }
function wp_enqueue_style( $handle, $src, $deps, $version ) { $GLOBALS['enqueued'][] = $src; }
function wp_enqueue_script( $handle, $src, $deps, $version, $in_footer ) { $GLOBALS['enqueued'][] = $src; }
function submit_button( $text ) { echo '<button>' . esc_html( $text ) . '</button>'; }

require dirname( __DIR__ ) . '/wp-social-publisher.php';

function expect( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

$post = new WP_Post( 12 );
expect( array( 'x', 'linkedin', 'facebook', 'medium', 'substack' ) === array_keys( wsp_platforms() ), 'Platformlar istenen sırada olmalı; Instagram kaldırılmalı' );
expect( 140 === wsp_x_character_count( str_repeat( 'a', 139 ) . '😀' ), 'Emoji bir Unicode karakteri sayılmalı' );
expect( 141 === wsp_x_character_count( str_repeat( 'ğ', 140 ) . "\n" ), '140 üstü Unicode ve satır sonu sayılmalı' );
expect( "{title}\n\n{excerpt}\n\n{url}\n\n{tags}" === wsp_template( 'facebook' ), 'Varsayılan şablon' );
$GLOBALS['options']['wsp_templates'] = array( 'facebook' => 'Özel: {title}' );
expect( 'Özel: {title}' === wsp_template( 'facebook' ), 'Kaydedilmiş şablon' );
expect( array( 'content' => 'Özel: {title}', 'url' => '', 'tags' => '' ) === wsp_template_fields( 'facebook' ), 'Önceki tek alan şablonu korunmalı' );
$GLOBALS['options']['wsp_templates'] = array(
	'facebook' => array( 'content' => '✨ {title}', 'url' => '{url}', 'tags' => '#{tags}' ),
);
expect( "✨ {title}\n\n{url}\n\n#{tags}" === wsp_template( 'facebook' ), 'Üç alanlı şablon birleşmeli' );
expect(
	"Deneme başlığı|Özet metni|https://example.test/deneme/|WordPress, Sosyal medya|Deneme sitesi" === wsp_render_template( '{title}|{excerpt}|{url}|{tags}|{site_title}', $post ),
	'Yer tutucular yazı verilerine dönüşmeli'
);

$_POST = array(
	'wsp_share_nonce' => 'valid',
	'wsp_platforms' => array(
		'facebook' => array( 'enabled' => '1', 'text' => 'Eski özel metin' ),
		'linkedin' => array( 'enabled' => '1', 'text' => 'LinkedIn: {title}' ),
	),
	'wsp_scheduled_at' => '2026-11-18T09:30',
);
wsp_save_share_metabox( 12 );
expect( '1' === get_post_meta( 12, '_wsp_linkedin_enabled', true ), 'LinkedIn seçimi kaydedilmeli' );
expect( 'Eski özel metin' === get_post_meta( 12, '_wsp_facebook_text', true ), 'Önceki platform metni korunmalı' );
expect( '2026-11-18T09:30' === get_post_meta( 12, '_wsp_scheduled_at', true ), 'Tarih kaydedilmeli' );
expect( array( 'LinkedIn', 'Facebook' ) === wsp_selected_platforms( 12 ), 'Seçimler istenen platform sırasıyla listelenmeli' );

$_POST['wsp_scheduled_at'] = '2026-02-31T12:00';
wsp_save_share_metabox( 12 );
expect( '2026-11-18T09:30' === get_post_meta( 12, '_wsp_scheduled_at', true ), 'Geçersiz tarih eski tarihi değiştirmemeli' );
$GLOBALS['permitted'] = false;
$_POST['wsp_platforms'] = array();
wsp_save_share_metabox( 12 );
expect( '1' === get_post_meta( 12, '_wsp_facebook_enabled', true ), 'Yetkisiz kullanıcı kayıt yapamamalı' );
$GLOBALS['permitted'] = true;
$_POST['wsp_share_nonce'] = 'invalid';
wsp_save_share_metabox( 12 );
expect( '1' === get_post_meta( 12, '_wsp_facebook_enabled', true ), 'Geçersiz nonce kayıt yapmamalı' );

ob_start();
wsp_render_share_metabox( $post );
$html = ob_get_clean();
expect( str_contains( $html, 'LinkedIn' ) && str_contains( $html, 'LinkedIn: Deneme başlığı' ), 'Yazı metninin önizlemesi' );
expect( str_contains( $html, 'Eski özel metin' ), 'Önceki özel metin korunmalı' );
expect( str_contains( $html, 'Twitter / X' ) && ! str_contains( $html, '>Instagram<' ), 'Yazı kutusu yeni platform listesini kullanmalı' );
expect( str_contains( $html, 'Tahmini karakter sayısı:' ) && str_contains( $html, 'wsp-x-preview' ), 'X karakter sayacı yazıda görünmeli' );

$cipher = wsp_facebook_encrypt( 'secret-token' );
expect( ! is_wp_error( $cipher ) && ! str_contains( $cipher, 'secret-token' ), 'Belirteç şifrelenmeli' );
expect( 'secret-token' === wsp_facebook_decrypt( $cipher ), 'Belirteç doğru anahtarla çözülmeli' );
expect( is_wp_error( wsp_facebook_decrypt( base64_encode( str_repeat( 'a', 40 ) ) ) ), 'Bozulmuş belirteç reddedilmeli' );
$GLOBALS['options']['wsp_facebook_account'] = array( 'page_id' => '123', 'name' => 'Sayfa', 'token' => $cipher );
expect( 'secret-token' === wsp_facebook_account()['token'], 'Kaydedilen hesap okunmalı' );
$GLOBALS['nonce_valid'] = true;
$GLOBALS['response'] = array( 'code' => 200, 'body' => '{"id":"123","name":"Sayfa"}' );
$_POST = array( 'page_id' => '123', 'page_token' => 'secret-token' );
try {
	wsp_facebook_connect();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı bağlantı yönlendirmeli' );
}
expect( str_contains( $GLOBALS['request'][0], '/me?' ), 'Belirtecin sahibi doğrulanmalı' );
expect( 'secret-token' === wsp_facebook_account()['token'], 'Doğrulanan belirteç kaydedilmeli' );
$saved_account = $GLOBALS['options']['wsp_facebook_account'];
$GLOBALS['response'] = array( 'code' => 200, 'body' => '{"id":"999","name":"Başka hesap"}' );
try {
	wsp_facebook_connect();
} catch ( RuntimeException $error ) {}
expect( $saved_account === $GLOBALS['options']['wsp_facebook_account'], 'Sayfayla eşleşmeyen belirteç mevcut hesabı değiştirmemeli' );
$GLOBALS['response'] = array( 'code' => 200, 'body' => '{"id":"123"}' );
$response = wsp_facebook_request( '123/feed', 'secret-token', 'POST', array( 'message' => 'Merhaba', 'link' => 'https://example.test/deneme/' ) );
expect( '123' === $response['id'], 'Başarılı Facebook yanıtı' );
expect( ! str_contains( $GLOBALS['request'][0], 'secret-token' ) && 'Bearer secret-token' === $GLOBALS['request'][1]['headers']['Authorization'], 'Belirteç URL yerine yetkilendirme başlığında olmalı' );
expect( 0 === $GLOBALS['request'][1]['redirection'] && 'POST' === $GLOBALS['request'][1]['method'], 'Facebook isteği yönlendirmeleri izlememeli' );
$GLOBALS['response'] = array( 'code' => 403, 'body' => '{"error":{"code":200,"message":"secret-token"}}' );
$response = wsp_facebook_request( '123/feed', 'secret-token', 'POST' );
expect( is_wp_error( $response ) && ! str_contains( $response->get_error_message(), 'secret-token' ), 'Meta hatasında belirteç gösterilmemeli' );
$GLOBALS['response'] = new WP_Error( 'network', 'secret-token' );
$response = wsp_facebook_request( '123/feed', 'secret-token', 'POST' );
expect( is_wp_error( $response ) && str_contains( $response->get_error_message(), 'belirsiz' ) && ! str_contains( $response->get_error_message(), 'secret-token' ), 'Ağ hatasında belirsizlik ve güvenli hata gösterilmeli' );

$GLOBALS['test_post'] = $post;
$GLOBALS['nonce_valid'] = true;
$GLOBALS['response'] = array( 'code' => 200, 'body' => '{"id":"123_456"}' );
$_POST = array( 'post_id' => '12' );
ob_start();
wsp_facebook_preview();
$confirmation = ob_get_clean();
expect( str_contains( $confirmation, 'Eski özel metin' ) && str_contains( $confirmation, 'Onayla ve Facebook' ), 'Paylaşım öncesi doğru mesaj ve onay gösterilmeli' );
$draft_id = str_repeat( 'a', 32 );
expect( isset( $GLOBALS['transients']['wsp_fb_draft_' . $draft_id] ), 'Onay için sunucuda taslak saklanmalı' );
$_POST = array( 'draft' => $draft_id );
$GLOBALS['nonce_valid'] = false;
try {
	wsp_facebook_publish();
	expect( false, 'Geçersiz nonce reddedilmeli' );
} catch ( RuntimeException $error ) {
	expect( 'invalid nonce' === $error->getMessage(), 'Geçersiz nonce gönderimi durdurmalı' );
}
expect( isset( $GLOBALS['transients']['wsp_fb_draft_' . $draft_id] ), 'Reddedilen istek taslağı tüketmemeli' );
$GLOBALS['nonce_valid'] = true;
try {
	wsp_facebook_publish();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı gönderim yönlendirmeli' );
}
expect( 'Eski özel metin' === $GLOBALS['request'][1]['body']['message'], 'Onaylanan mesaj Meta isteğine gitmeli' );
expect( 'https://example.test/deneme/' === $GLOBALS['request'][1]['body']['link'], 'Onaylanan bağlantı Meta isteğine gitmeli' );
expect( false === get_transient( 'wsp_fb_draft_' . $draft_id ), 'Gönderim sonrasında onay tek kullanımlık olmalı' );
$GLOBALS['request'] = null;
try {
	wsp_facebook_publish();
} catch ( RuntimeException $error ) {}
expect( null === $GLOBALS['request'], 'Tekrarlanan onay Meta isteği göndermemeli' );

$_GET = array( 'page' => 'wsp-templates' );
wsp_admin_assets( 'unused-hook-name' );
expect( 2 === count( $GLOBALS['enqueued'] ), 'Şablon sayfası CSS ve JS eklemeli' );
ob_start();
wsp_templates_page();
$template_page = ob_get_clean();
expect( str_contains( $template_page, 'Akıllı etiketleri göster' ) && str_contains( $template_page, 'data-smart-tag="{title}"' ), 'Akıllı etiketleri gösterip ekleyebilmeli' );
expect( str_contains( $template_page, 'wsp-template-grid' ) && str_contains( $template_page, 'wsp-preview-text' ), 'Üç bölümlü düzen ve önizleme bulunmalı' );

$_GET = array( 'page' => 'wsp-accounts' );
$GLOBALS['enqueued'] = array();
wsp_admin_assets( 'unused-hook-name' );
expect( 1 === count( $GLOBALS['enqueued'] ) && str_contains( $GLOBALS['enqueued'][0], 'templates.css' ), 'Hesaplar sayfası ortak stili yüklemeli' );
ob_start();
wsp_accounts_page();
$accounts_page = ob_get_clean();
expect( str_contains( $accounts_page, 'wsp-accounts-grid' ) && str_contains( $accounts_page, 'wsp_facebook_connect' ) && str_contains( $accounts_page, 'wsp_facebook_disconnect' ), 'Facebook bağlantı ve kaldırma formları korunmalı' );
expect( str_contains( $accounts_page, 'Kayıtlı Sayfa: Sayfa' ) && ! str_contains( $accounts_page, 'secret-token' ), 'Hesap bilgisi belirteç gösterilmeden sunulmalı' );
expect( str_contains( $accounts_page, 'güncel durumu burada kontrol edilmez' ), 'Bağlantı durumu güncel olarak sunulmamalı' );
foreach ( array( 'x', 'linkedin' ) as $platform ) {
	$_GET['platform'] = $platform;
	ob_start();
	wsp_accounts_page();
	$accounts_page = ob_get_clean();
	expect( str_contains( $accounts_page, wsp_platforms()[ $platform ] ) && str_contains( $accounts_page, 'OAuth Redirect URI' ), 'OAuth uygulaması ve dönüş adresi gösterilmeli: ' . $platform );
	expect( str_contains( $accounts_page, 'wsp_oauth_save_app' ) && ! str_contains( $accounts_page, 'wsp_facebook_connect' ), 'Platforma özel OAuth formu olmalı: ' . $platform );
	expect( ! str_contains( $accounts_page, 'wsp_oauth_start' ), 'Uygulama bilgileri olmadan giriş başlamamalı: ' . $platform );
}
foreach ( array( 'medium', 'substack' ) as $platform ) {
	$_GET['platform'] = $platform;
	ob_start();
	wsp_accounts_page();
	$accounts_page = ob_get_clean();
	expect( str_contains( $accounts_page, wsp_platforms()[ $platform ] ) && str_contains( $accounts_page, 'Resmi hesap bağlantısı desteklenmiyor' ), 'Desteklenmeyen platform durumu gösterilmeli: ' . $platform );
	expect( ! str_contains( $accounts_page, 'page_token' ) && ! str_contains( $accounts_page, 'client_secret' ), 'Desteklenmeyen platform için kimlik bilgisi alınmamalı' );
}
$_GET['platform'] = 'gecersiz';
$GLOBALS['ssl'] = false;
ob_start();
wsp_accounts_page();
$accounts_page = ob_get_clean();
expect( str_contains( $accounts_page, 'HTTPS gereklidir' ) && ! str_contains( $accounts_page, 'name="page_token"' ), 'Geçersiz platform varsayılan Facebook ekranına dönmeli; HTTPS yoksa form gösterilmemeli' );
$GLOBALS['ssl'] = true;
$GLOBALS['nonce_valid'] = true;

$_POST = array( 'platform' => 'linkedin', 'client_id' => 'linkedin-client', 'client_secret' => 'linkedin-secret' );
try {
	wsp_oauth_save_app();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Uygulama bilgileri kaydedilmeli' );
}
$linkedin_app = wsp_oauth_apps()['linkedin'];
expect( 'linkedin-client' === $linkedin_app['client_id'] && 'linkedin-secret' === wsp_decrypt_secret( $linkedin_app['client_secret'] ), 'Client secret şifrelenmiş olarak saklanmalı' );
$_POST['client_secret'] = '';
try {
	wsp_oauth_save_app();
} catch ( RuntimeException $error ) {}
expect( $linkedin_app['client_secret'] === wsp_oauth_apps()['linkedin']['client_secret'], 'Boş gizli alan mevcut anahtarı korumalı' );
expect( 'https://example.test/wp-admin/admin-post.php' === wsp_oauth_callback_url( 'linkedin' ), 'Callback yönetici oturumunu koruyan sabit admin-post URL kullanmalı' );

$GLOBALS['options']['wsp_oauth_apps']['x'] = array(
	'client_id' => 'x-client',
	'client_secret' => wsp_encrypt_secret( 'x-secret' ),
);
$_POST = array( 'platform' => 'x' );
try {
	wsp_oauth_start();
	expect( false, 'OAuth yönlendirmesi başlamalı' );
} catch ( RuntimeException $error ) {
	expect( 'external redirect' === $error->getMessage(), 'Kullanıcı X OAuth sayfasına yönlendirilmeli' );
}
$authorize = array();
parse_str( (string) parse_url( $GLOBALS['external_redirect'], PHP_URL_QUERY ), $authorize );
expect( 'https://x.com/i/oauth2/authorize' === parse_url( $GLOBALS['external_redirect'], PHP_URL_SCHEME ) . '://' . parse_url( $GLOBALS['external_redirect'], PHP_URL_HOST ) . parse_url( $GLOBALS['external_redirect'], PHP_URL_PATH ), 'X resmi HTTPS OAuth uç noktasına gidilmeli' );
expect( 'S256' === $authorize['code_challenge_method'] && isset( $authorize['code_challenge'], $authorize['state'] ), 'X PKCE ve state göndermeli' );
$oauth_state_key = 'wsp_oauth_state_' . hash( 'sha256', $authorize['state'] );
$oauth_state = $GLOBALS['transients'][ $oauth_state_key ];
expect( 'x' === $oauth_state['platform'] && 7 === $oauth_state['user_id'], 'OAuth state kullanıcıya ve platforma bağlanmalı' );
$verifier = wsp_decrypt_secret( $oauth_state['verifier'] );
expect( ! is_wp_error( $verifier ) && $verifier !== $oauth_state['verifier'], 'PKCE verifier transient içinde şifreli saklanmalı' );
expect( hash_equals( $authorize['code_challenge'], rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ) ), 'X PKCE challenge verifier ile eşleşmeli' );

$_SERVER['PHP_SELF'] = '/wp-admin/admin-post.php';
$_GET = array( 'state' => $authorize['state'], 'code' => 'test-authorization-code' );
$GLOBALS['token_response'] = array( 'code' => 200, 'body' => '{"access_token":"x-access","refresh_token":"x-refresh","expires_in":7200}' );
$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => '{"data":{"id":"x-123","name":"X Tester","username":"xuser"}}' );
try {
	wsp_oauth_maybe_handle_callback();
	expect( false, 'OAuth callback hesaba bağlanmalı' );
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı OAuth callback hesaba yönlendirmeli' );
}
expect( false === get_transient( $oauth_state_key ), 'OAuth state callback sonrasında tek kullanımlık olmalı' );
expect( 'authorization_code' === $GLOBALS['token_request'][1]['body']['grant_type'] && 'test-authorization-code' === $GLOBALS['token_request'][1]['body']['code'], 'Authorization code token karşılığında değiştirilmeli' );
expect( 'Basic ' . base64_encode( 'x-client:x-secret' ) === $GLOBALS['token_request'][1]['headers']['Authorization'], 'X istemci doğrulaması token isteğinde kullanılmalı' );
expect( 'x-client' === $GLOBALS['token_request'][1]['body']['client_id'] && isset( $GLOBALS['token_request'][1]['body']['code_verifier'] ), 'X token isteği client ID ve PKCE verifier içermeli' );
$x_account = get_option( 'wsp_oauth_accounts' )['x'];
expect( 'X Tester' === $x_account['profile']['name'] && '@xuser' === '@' . $x_account['profile']['username'], 'X hesap profili doğrulanıp kaydedilmeli' );
expect( 'x-access' === wsp_oauth_account( 'x' )['access_token'] && 'x-refresh' === wsp_oauth_account( 'x' )['refresh_token'], 'X belirteçleri şifreli saklanıp okunmalı' );
expect( ! str_contains( $x_account['access_token'], 'x-access' ) && ! str_contains( $x_account['refresh_token'], 'x-refresh' ), 'OAuth belirteçleri veritabanında şifreli olmalı' );
$_POST = array( 'platform' => 'x' );
$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => '{"data":{"id":"x-123","name":"X Tester","username":"xuser"}}' );
try {
	wsp_oauth_test();
	expect( false, 'X bağlantı testi tamamlanmalı' );
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'X bağlantı testi sonuç ekranına dönmeli' );
}
expect( str_contains( get_transient( 'wsp_oauth_notice_7' )['message'], '@xuser' ), 'X bağlantı testi kullanıcı profilini bildirmeli' );
$GLOBALS['options']['wsp_oauth_accounts']['x']['expires_at'] = 1;
$GLOBALS['token_response'] = array( 'code' => 200, 'body' => '{"access_token":"x-refreshed","refresh_token":"x-refresh-2","expires_in":3600}' );
$refreshed_account = wsp_oauth_account( 'x' );
expect( 'refresh_token' === $GLOBALS['token_request'][1]['body']['grant_type'] && 'x-refresh' === $GLOBALS['token_request'][1]['body']['refresh_token'], 'Süresi dolan X hesabı refresh token ile yenilenmeli' );
expect( 'x-refreshed' === $refreshed_account['access_token'] && 'x-refresh-2' === $refreshed_account['refresh_token'], 'Yenilenen X tokenları döndürülmeli ve saklanmalı' );

$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => '{"sub":"li-123","name":"LinkedIn Tester"}' );
$profile = wsp_oauth_profile( 'linkedin', 'li-access' );
expect( 'li-123' === $profile['id'] && 'LinkedIn Tester' === $profile['name'], 'LinkedIn OpenID Connect profili doğrulanmalı' );
$GLOBALS['options']['wsp_oauth_apps']['linkedin']['client_secret'] = wsp_encrypt_secret( 'linkedin-secret' );
$_POST = array( 'platform' => 'linkedin' );
try {
	wsp_oauth_start();
	expect( false, 'LinkedIn OAuth yönlendirmesi başlamalı' );
} catch ( RuntimeException $error ) {
	expect( 'external redirect' === $error->getMessage(), 'Kullanıcı LinkedIn OAuth sayfasına yönlendirilmeli' );
}
$linkedin_authorize = array();
parse_str( (string) parse_url( $GLOBALS['external_redirect'], PHP_URL_QUERY ), $linkedin_authorize );
expect( 'https://www.linkedin.com/oauth/v2/authorization' === parse_url( $GLOBALS['external_redirect'], PHP_URL_SCHEME ) . '://' . parse_url( $GLOBALS['external_redirect'], PHP_URL_HOST ) . parse_url( $GLOBALS['external_redirect'], PHP_URL_PATH ), 'LinkedIn resmi HTTPS OAuth uç noktasına gidilmeli' );
expect( ! isset( $linkedin_authorize['code_challenge'] ) && str_contains( $linkedin_authorize['scope'], 'w_member_social' ), 'LinkedIn yetki isteği gerekli paylaşım kapsamını istemeli' );
$_GET = array( 'state' => $linkedin_authorize['state'], 'code' => 'linkedin-code' );
$GLOBALS['token_response'] = array( 'code' => 200, 'body' => '{"access_token":"li-access","expires_in":3600}' );
$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => '{"sub":"li-123","name":"LinkedIn Tester"}' );
try {
	wsp_oauth_callback();
	expect( false, 'LinkedIn OAuth callback hesaba bağlanmalı' );
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı LinkedIn callback hesaba yönlendirmeli' );
}
expect( 'linkedin-client' === $GLOBALS['token_request'][1]['body']['client_id'] && 'linkedin-secret' === $GLOBALS['token_request'][1]['body']['client_secret'], 'LinkedIn token isteği uygulama bilgilerini kullanmalı' );
expect( 'li-123' === get_option( 'wsp_oauth_accounts' )['linkedin']['profile']['id'], 'LinkedIn profili doğrulanıp kaydedilmeli' );
$_POST = array( 'platform' => 'linkedin' );
$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => '{"sub":"li-123","name":"LinkedIn Tester"}' );
try {
	wsp_oauth_test();
	expect( false, 'LinkedIn bağlantı testi tamamlanmalı' );
} catch ( RuntimeException $error ) {}
expect( str_contains( get_transient( 'wsp_oauth_notice_7' )['message'], 'LinkedIn Tester' ), 'LinkedIn bağlantı testi profilini bildirmeli' );
$_GET = array( 'page' => 'wsp-accounts', 'platform' => 'linkedin' );
ob_start();
wsp_accounts_page();
$linkedin_page = ob_get_clean();
expect( str_contains( $linkedin_page, 'Doğrulanan hesap: LinkedIn Tester (li-123)' ), 'LinkedIn profil ekranı kullanıcı kimliğini doğru göstermeli' );
$_POST = array( 'platform' => 'linkedin' );
try {
	wsp_oauth_disconnect();
	expect( false, 'LinkedIn bağlantısı kaldırılmalı' );
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Bağlantı kaldırma sonrası yönlendirmeli' );
}
expect( ! isset( get_option( 'wsp_oauth_accounts' )['linkedin'] ), 'LinkedIn tokenları yerelden silinmeli' );
expect( 'linkedin-client' === wsp_oauth_apps()['linkedin']['client_id'], 'Bağlantıyı kaldırmak geliştirici uygulamasını silmemeli' );

$GLOBALS['posts'] = array( $post );
$_GET = array( 'month' => '2026-11' );
ob_start();
wsp_calendar_page();
$calendar = ob_get_clean();
expect( str_contains( $calendar, '09:30' ) && str_contains( $calendar, 'LinkedIn, Facebook' ), 'Takvim planı göstermeli' );
expect( '2026-11-01T00:00' === $GLOBALS['last_query']['meta_query'][0][0]['value'], 'Takvim ay başından sorgulamalı' );
expect( '2026-12-01T00:00' === $GLOBALS['last_query']['meta_query'][0][1]['value'], 'Takvim sonraki ayı dışlamalı' );
ob_start();
wsp_schedules_page();
$schedule = ob_get_clean();
expect( str_contains( $schedule, '2026-11-18 09:30' ) && str_contains( $schedule, 'Deneme başlığı' ), 'Plan listesi tarih ve yazıyı göstermeli' );

echo "Smoke checks passed.\n";
