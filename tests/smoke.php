<?php

define( 'ABSPATH', __DIR__ );
$GLOBALS['meta'] = array();
$GLOBALS['options'] = array();
$GLOBALS['permitted'] = true;
$GLOBALS['posts'] = array();
$GLOBALS['enqueued'] = array();
$GLOBALS['transients'] = array();
$GLOBALS['cron_events'] = array();
define( 'MINUTE_IN_SECONDS', 60 );
define( 'MB_IN_BYTES', 1024 * 1024 );

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
		$GLOBALS['query_history'][] = $args;
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
function wp_remote_request( $url, $args ) {
	$GLOBALS['request'] = array( $url, $args );
	$GLOBALS['request_history'][] = $GLOBALS['request'];
	return ! empty( $GLOBALS['response_queue'] ) ? array_shift( $GLOBALS['response_queue'] ) : $GLOBALS['response'];
}
function wp_remote_get( $url, $args ) { $GLOBALS['profile_request'] = array( $url, $args ); return $GLOBALS['profile_response']; }
function wp_remote_post( $url, $args ) { $GLOBALS['token_request'] = array( $url, $args ); return $GLOBALS['token_response']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][strtolower( $header )] ?? ''; }
function get_post( $id ) { return $GLOBALS['test_post'] ?? null; }
function get_current_user_id() { return 7; }
function check_admin_referer( $action ) { if ( ! $GLOBALS['nonce_valid'] ) { throw new RuntimeException( 'invalid nonce' ); } }
function set_transient( $key, $value, $expiry ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function wp_schedule_single_event( $timestamp, $hook, $args = array(), $wp_error = false ) {
	$GLOBALS['cron_events'][ $hook . ':' . implode( ',', $args ) ] = array( $timestamp, $hook, $args );
	return true;
}
function wp_clear_scheduled_hook( $hook, $args = array() ) {
	unset( $GLOBALS['cron_events'][ $hook . ':' . implode( ',', $args ) ] );
	return 0;
}
function wp_next_scheduled( $hook, $args = array() ) {
	$event = $GLOBALS['cron_events'][ $hook . ':' . implode( ',', $args ) ] ?? false;
	return $event ? $event[0] : false;
}
function wp_safe_redirect( $url ) { throw new RuntimeException( 'redirect: ' . $url ); }
function wp_redirect( $url, $status, $x_redirect_by ) { $GLOBALS['external_redirect'] = $url; throw new RuntimeException( 'external redirect' ); }
function wp_die( $message ) { throw new RuntimeException( 'die: ' . $message ); }
function wp_generate_password( $length, $special, $extra ) { return $GLOBALS['password_override'] ?? str_repeat( 'a', $length ); }
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
function wp_timezone_string() { return 'Europe/Istanbul'; }
function get_the_title( $post ) { return 'Deneme başlığı'; }
function get_the_post_thumbnail_url( $post, $size ) { return 'https://example.test/featured.jpg'; }
function get_post_thumbnail_id( $post ) { return 77; }
function get_attached_file( $attachment_id ) { return $GLOBALS['attached_file'] ?? false; }
function wp_check_filetype_and_ext( $file, $filename ) { return array( 'type' => str_ends_with( $filename, '.jpg' ) ? 'image/jpeg' : '' ); }
function get_the_excerpt( $post ) { return '<p>Özet metni</p>'; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function get_permalink( $post ) { return 'https://example.test/deneme/'; }
function get_the_tags( $id ) { return array_key_exists( 'test_tags', $GLOBALS ) ? $GLOBALS['test_tags'] : array( (object) array( 'name' => 'WordPress' ), (object) array( 'name' => 'Sosyal medya' ) ); }
function get_the_category( $id ) { return array( (object) array( 'term_id' => 3, 'name' => 'Teknoloji' ) ); }
function get_categories( $args = array() ) { return array( (object) array( 'term_id' => 3, 'name' => 'Teknoloji' ) ); }
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
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
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
function selected( $selected, $current = true, $echo = true ) {
	$result = (string) $selected === (string) $current ? ' selected="selected"' : '';
	if ( $echo ) { echo $result; }
	return $result;
}
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
expect( array( 'x', 'linkedin', 'facebook', 'instagram', 'reddit', 'pinterest' ) === array_keys( wsp_platforms() ), 'Platform listesi yeni servisleri içermeli; Medium/Substack kaldırılmalı' );
expect( 140 === wsp_x_character_count( str_repeat( 'a', 139 ) . '😀' ), 'Emoji bir Unicode karakteri sayılmalı' );
expect( 141 === wsp_x_character_count( str_repeat( 'ğ', 140 ) . "\n" ), '140 üstü Unicode ve satır sonu sayılmalı' );
expect( 200 === wsp_x_character_limit(), 'X özel yayın hedefi 200 karakter olmalı' );
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
expect( '#WordPress #Sosyalmedya' === wsp_render_template( '{hashtags}', $post ), 'Hashtag yer tutucusu her etiketi ayrı biçimlendirmeli' );
$GLOBALS['test_tags'] = array( (object) array( 'name' => '#İstanbul 2026' ), (object) array( 'name' => 'çağrı-ş/ö_2' ), (object) array( 'name' => '!!!' ) );
expect( '#İstanbul2026 #çağrışö_2' === wsp_render_template( '{hashtags}', $post ), 'Hashtag Türkçe harfleri ve rakamları korumalı, noktalama ve boş etiketleri çıkarmalı' );
$GLOBALS['test_tags'] = false;
expect( '' === wsp_render_template( '{hashtags}', $post ), 'Etiketsiz yazıda hashtag alanı boş olmalı' );
unset( $GLOBALS['test_tags'] );

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
expect( str_contains( $html, 'Twitter / X' ) && str_contains( $html, 'Instagram' ) && str_contains( $html, 'Reddit' ) && str_contains( $html, 'Pinterest' ) && ! str_contains( $html, '>Medium<' ) && ! str_contains( $html, '>Substack<' ), 'Yazı kutusu yeni platform listesini kullanmalı' );
expect( str_contains( $html, 'Tahmini karakter sayısı:' ) && str_contains( $html, 'wsp-x-preview' ), 'X karakter sayacı yazıda görünmeli' );
expect( str_contains( $html, 'wsp_x_fields[title]' ) && str_contains( $html, '🔥 {title}' ) && str_contains( $html, '👉 {url}' ) && str_contains( $html, '🔍 {hashtags}' ), 'X dört düzenlenebilir alan ve varsayılan emojileri göstermeli' );
$_POST = array(
	'wsp_share_nonce' => 'valid',
	'wsp_platforms' => array( 'x' => array( 'text' => 'Eski X metni' ) ),
	'wsp_x_fields_enabled' => '1',
	'wsp_x_fields' => array( 'title' => '🔥 {title}', 'content' => 'İçerik', 'link' => '👉 {url}', 'tags' => '🔍 {hashtags}' ),
	'wsp_scheduled_at' => '',
);
wsp_save_share_metabox( 12 );
$x_message = wsp_post_share_message( 'x', $post );
expect( "🔥 Deneme başlığı\n\nİçerik\n\n👉 https://example.test/deneme/\n\n🔍 #WordPress #Sosyalmedya" === $x_message, 'X özel alanları gerçek yazı verileriyle birleştirilmeli' );
expect( $x_message === wsp_x_complete_message( $post, $x_message, get_permalink( $post ) ), 'X özel bağlantısı tekrar eklenmemeli' );
unset( $_POST['wsp_x_fields_enabled'] );
wsp_save_share_metabox( 12 );
expect( 'Eski X metni' === wsp_post_share_message( 'x', $post ), 'Özel alanlar kapatılınca eski metin korunmalı' );
expect( "Eski X metni\n\nhttps://example.test/deneme/" === wsp_x_complete_message( $post, 'Eski X metni', get_permalink( $post ) ), 'Eski X metnine eksik bağlantı eklenmeli' );
$_POST = array(
	'wsp_share_nonce' => 'valid',
	'wsp_platforms' => array( 'facebook' => array( 'enabled' => '1', 'text' => 'Eski özel metin' ), 'linkedin' => array( 'enabled' => '1', 'text' => 'LinkedIn: {title}' ) ),
	'wsp_scheduled_at' => '2026-11-18T09:30',
);
wsp_save_share_metabox( 12 );

$bar = new class {
	public array $nodes = array();
	public function add_node( $node ) { $this->nodes[] = $node; }
};
wsp_admin_bar_menu( $bar );
expect( 'wsp-publisher' === $bar->nodes[0]['id'] && str_contains( $bar->nodes[0]['href'], 'page=wsp-accounts' ), 'Üst menü eklentiye bağlantı içermeli' );
$GLOBALS['permitted'] = false;
wsp_admin_bar_menu( $bar );
expect( 1 === count( $bar->nodes ), 'Yetkisiz kullanıcılara üst menü bağlantısı gösterilmemeli' );
$GLOBALS['permitted'] = true;

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
$_POST = array( 'platform' => 'facebook', 'post_id' => '12' );
try {
	wsp_facebook_preview();
} catch ( RuntimeException $error ) {
	expect( str_contains( $error->getMessage(), 'preview=' ), 'Önizleme yönetim sayfasına yönlendirmeli' );
}
$draft_id = str_repeat( 'a', 32 );
expect( isset( $GLOBALS['transients']['wsp_fb_draft_' . $draft_id] ), 'Onay için sunucuda taslak saklanmalı' );
$_GET = array( 'preview' => $draft_id );
ob_start();
wsp_share_now_page();
$confirmation = ob_get_clean();
expect( str_contains( $confirmation, 'Eski özel metin' ) && str_contains( $confirmation, 'Onayla ve Facebook' ) && str_contains( $confirmation, 'wsp-social-card' ), 'Önizleme WordPress yönetim sayfasında görsel kart olarak gösterilmeli' );
$_GET['page'] = 'wsp-share-now';
$GLOBALS['enqueued'] = array();
wsp_admin_assets( 'unused-hook-name' );
expect( 1 === count( $GLOBALS['enqueued'] ) && str_contains( $GLOBALS['enqueued'][0], 'templates.css' ), 'Share Now önizlemesi yönetim paneli stilini yüklemeli' );
$GLOBALS['enqueued'] = array();
$_POST = array( 'draft' => $draft_id, 'platform' => 'facebook' );
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
expect( 'Başarılı' === get_option( 'wsp_delivery_logs' )[0]['status'] && '123_456' === get_option( 'wsp_delivery_logs' )[0]['remote_id'], 'Başarılı Facebook gönderimi Logs kaydına yazılmalı' );

$_GET = array( 'page' => 'wsp-templates' );
$GLOBALS['posts'] = array( $post );
wsp_admin_assets( 'unused-hook-name' );
expect( 2 === count( $GLOBALS['enqueued'] ), 'Şablon sayfası CSS ve JS eklemeli' );
ob_start();
wsp_templates_page();
$template_page = ob_get_clean();
expect( str_contains( $template_page, 'Akıllı etiketleri göster' ) && str_contains( $template_page, 'data-smart-tag="{title}"' ), 'Akıllı etiketleri gösterip ekleyebilmeli' );
expect( str_contains( $template_page, 'data-smart-tag="{hashtags}"' ), 'Hashtag yer tutucusu şablon düzenleyicisinden seçilebilmeli' );
expect( str_contains( $template_page, 'wsp-template-grid' ) && str_contains( $template_page, 'wsp-preview-text' ), 'Üç bölümlü düzen ve önizleme bulunmalı' );
expect( str_contains( $template_page, 'wsp-preview-image' ) && str_contains( $template_page, 'featured.jpg' ), 'Şablon önizlemesi öne çıkan görseli göstermeli' );

$_GET = array( 'page' => 'wsp-accounts' );
$GLOBALS['enqueued'] = array();
wsp_admin_assets( 'unused-hook-name' );
expect( 1 === count( $GLOBALS['enqueued'] ) && str_contains( $GLOBALS['enqueued'][0], 'templates.css' ), 'Hesaplar sayfası ortak stili yüklemeli' );
ob_start();
wsp_accounts_page();
$accounts_page = ob_get_clean();
expect( str_contains( $accounts_page, 'wsp-accounts-grid' ) && str_contains( $accounts_page, 'wsp_facebook_connect' ) && str_contains( $accounts_page, 'wsp_facebook_disconnect' ), 'Facebook bağlantı ve kaldırma formları korunmalı' );
expect( str_contains( $accounts_page, 'Kayıtlı Sayfa: Sayfa' ) && ! str_contains( $accounts_page, 'secret-token' ), 'Hesap bilgisi belirteç gösterilmeden sunulmalı' );
expect( substr_count( $accounts_page, 'class="regular-text wsp-facebook-field"' ) === 2, 'Facebook kimlik ve erişim alanı ortak stil kullanmalı' );
expect( str_contains( $accounts_page, 'güncel durumu burada kontrol edilmez' ), 'Bağlantı durumu güncel olarak sunulmamalı' );
foreach ( array( 'x', 'linkedin', 'instagram', 'reddit', 'pinterest' ) as $platform ) {
	$_GET['platform'] = $platform;
	ob_start();
	wsp_accounts_page();
	$accounts_page = ob_get_clean();
	expect( str_contains( $accounts_page, wsp_platforms()[ $platform ] ) && str_contains( $accounts_page, 'OAuth Redirect URI' ), 'OAuth uygulaması ve dönüş adresi gösterilmeli: ' . $platform );
	expect( str_contains( $accounts_page, 'wsp_oauth_save_app' ) && ! str_contains( $accounts_page, 'wsp_facebook_connect' ), 'Platforma özel OAuth formu olmalı: ' . $platform );
	expect( ! str_contains( $accounts_page, 'wsp_oauth_start' ), 'Uygulama bilgileri olmadan giriş başlamamalı: ' . $platform );
}
$_GET['platform'] = 'reddit';
ob_start();
wsp_accounts_page();
$reddit_page = ob_get_clean();
expect( str_contains( $reddit_page, 'name="subreddit"' ) && array( 'identity', 'submit' ) === wsp_oauth_providers()['reddit']['scopes'], 'Reddit bağlantısı hedef subreddit ve OAuth izinlerini sunmalı' );
$_GET['platform'] = 'pinterest';
ob_start();
wsp_accounts_page();
$pinterest_page = ob_get_clean();
expect( str_contains( $pinterest_page, 'name="board_id"' ) && in_array( 'pins:write', wsp_oauth_providers()['pinterest']['scopes'], true ), 'Pinterest bağlantısı pano ve Pin yazma izinlerini sunmalı' );
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

$GLOBALS['options']['wsp_oauth_apps']['reddit'] = array(
	'client_id'     => 'reddit-client',
	'client_secret' => wsp_encrypt_secret( 'reddit-secret' ),
);
$_POST = array( 'platform' => 'reddit' );
try {
	wsp_oauth_start();
	expect( false, 'Reddit OAuth yönlendirmesi başlamalı' );
} catch ( RuntimeException $error ) {
	expect( 'external redirect' === $error->getMessage(), 'Kullanıcı Reddit OAuth sayfasına yönlendirilmeli' );
}
$reddit_authorize = array();
parse_str( (string) parse_url( $GLOBALS['external_redirect'], PHP_URL_QUERY ), $reddit_authorize );
expect( 'permanent' === $reddit_authorize['duration'], 'Reddit OAuth kalıcı izin istemeli' );

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
update_post_meta( 12, '_wsp_x_fields_enabled', '1' );
$GLOBALS['response'] = array( 'code' => 201, 'body' => '{"data":{"id":"tweet-123"}}' );
$x_result = wsp_publish_platform_post( 'x', $post, str_repeat( 'a', 200 ), get_permalink( $post ) );
expect( ! is_wp_error( $x_result ) && str_repeat( 'a', 200 ) === json_decode( $GLOBALS['request'][1]['body'], true )['text'], '200 karakterlik özel X metni değiştirilmeden gönderilmeli' );
$before_request = count( $GLOBALS['request_history'] );
expect( is_wp_error( wsp_publish_platform_post( 'x', $post, str_repeat( 'a', 201 ), get_permalink( $post ) ) ) && $before_request === count( $GLOBALS['request_history'] ), '201 karakterli X metni API isteği yapılmadan reddedilmeli' );
update_post_meta( 12, '_wsp_x_fields_enabled', '0' );

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
expect( array( 'instagram_business_basic', 'instagram_business_content_publish' ) === wsp_oauth_providers()['instagram']['scopes'], 'Instagram OAuth yalnızca temel hesap ve yayınlama izinlerini istemeli' );
expect( array( 'identity', 'submit' ) === wsp_oauth_providers()['reddit']['scopes'] && array( 'user_accounts:read', 'boards:read', 'pins:write' ) === wsp_oauth_providers()['pinterest']['scopes'], 'Reddit ve Pinterest OAuth yayınlama izinleri tanımlı olmalı' );
foreach ( array( 'instagram' => array( 'id' => 'ig-123', 'name' => 'blogger', 'username' => 'blogger' ), 'reddit' => array( 'id' => 'rd-123', 'name' => 'reddit-user', 'username' => 'reddit-user' ), 'pinterest' => array( 'id' => 'pin-user', 'name' => 'pin-user', 'username' => 'pin-user' ) ) as $platform => $expected_profile ) {
	$GLOBALS['profile_response'] = array( 'code' => 200, 'body' => json_encode( 'instagram' === $platform ? array( 'user_id' => 'ig-123', 'username' => 'blogger' ) : ( 'reddit' === $platform ? array( 'id' => 'rd-123', 'name' => 'reddit-user' ) : array( 'username' => 'pin-user' ) ) ) );
	expect( $expected_profile === wsp_oauth_profile( $platform, 'test-access-token' ), $platform . ' hesabı profili doğrulanmalı' );
}
$oauth_accounts = get_option( 'wsp_oauth_accounts' );
foreach ( array( 'instagram', 'reddit', 'pinterest' ) as $platform ) {
	$oauth_accounts[ $platform ] = array( 'profile' => array( 'id' => $platform . '-123', 'name' => $platform . '-test', 'username' => $platform . '-test' ), 'access_token' => wsp_encrypt_secret( $platform . '-token' ), 'refresh_token' => '', 'expires_at' => 0 );
}
update_option( 'wsp_oauth_accounts', $oauth_accounts );
$connected_share_platforms = wsp_shareable_platforms();
expect( array( 'x', 'linkedin', 'facebook', 'instagram', 'reddit', 'pinterest' ) === array_keys( $connected_share_platforms ), 'Share Now bağlı hesapları platform sırasıyla otomatik göstermeli' );
$_GET = array( 'cat' => '3' );
ob_start();
wsp_share_now_page();
$share_picker = ob_get_clean();
expect( str_contains( $share_picker, 'name="cat"' ) && str_contains( $share_picker, 'Deneme başlığı — Teknoloji' ) && 3 === $GLOBALS['last_query']['cat'], 'Share Now yazı listesi kategorilerle süzülebilmeli' );
expect( str_contains( $share_picker, 'wsp-filter-bar wsp-share-filter' ), 'Share Now filtresi paylaşım sütunlarıyla aynı genişlikte sınırlandırılmalı' );
$temporary_image = tempnam( sys_get_temp_dir(), 'wsp-image-' );
$GLOBALS['attached_file'] = $temporary_image . '.jpg';
rename( $temporary_image, $GLOBALS['attached_file'] );
file_put_contents( $GLOBALS['attached_file'], 'test image bytes' );
$GLOBALS['password_override'] = str_repeat( 'b', 32 );
$_POST = array( 'platform' => 'linkedin', 'post_id' => '12' );
$GLOBALS['response_queue'] = array(
	array( 'code' => 200, 'body' => '{"value":{"uploadUrl":"https://www.linkedin.com/dms-uploads/test-upload","image":"urn:li:image:test-image"}}' ),
	array( 'code' => 201, 'body' => '' ),
	array( 'code' => 201, 'body' => '', 'headers' => array( 'x-restli-id' => 'urn:li:share:789' ) ),
);
try {
	wsp_facebook_preview();
} catch ( RuntimeException $error ) {}
$GLOBALS['password_override'] = str_repeat( 'b', 32 );
$_GET = array( 'preview' => str_repeat( 'b', 32 ) );
ob_start();
wsp_share_now_page();
$linkedin_confirmation = ob_get_clean();
expect( str_contains( $linkedin_confirmation, 'LinkedIn: Deneme başlığı' ) && str_contains( $linkedin_confirmation, 'Onayla ve LinkedIn' ), 'LinkedIn önizlemesi onay adımını göstermeli' );
$linkedin_draft_id = str_repeat( 'b', 32 );
$linkedin_draft = get_transient( 'wsp_fb_draft_' . $linkedin_draft_id );
expect( is_array( $linkedin_draft ) && str_contains( $linkedin_draft['message'], 'https://example.test/deneme/' ), 'LinkedIn gönderi metnine yazı bağlantısı eklenmeli' );
expect( 'https://example.test/featured.jpg' === $linkedin_draft['image_url'] && str_contains( $linkedin_confirmation, 'wsp-social-card' ) && str_contains( $linkedin_confirmation, 'featured.jpg' ), 'LinkedIn onay ekranı metin, görsel ve yazı kartıyla görsel önizleme sunmalı' );
$_POST = array( 'draft' => $linkedin_draft_id, 'platform' => 'linkedin' );
try {
	wsp_facebook_publish();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı LinkedIn gönderimi yönlendirmeli' );
}
expect( 'https://api.linkedin.com/rest/posts' === $GLOBALS['request'][0], 'LinkedIn Posts API kullanılmalı' );
expect( 'Bearer li-access' === $GLOBALS['request'][1]['headers']['Authorization'] && '202511' === $GLOBALS['request'][1]['headers']['LinkedIn-Version'], 'LinkedIn istek belirteci ve API sürümü başlıklarını kullanmalı' );
$linkedin_payload = json_decode( $GLOBALS['request'][1]['body'], true );
expect( 'urn:li:person:li-123' === $linkedin_payload['author'] && 'PUBLISHED' === $linkedin_payload['lifecycleState'], 'LinkedIn API yükü doğru yazarla yayınlanmalı' );
expect( isset( $linkedin_payload['content']['media']['id'] ) && 'urn:li:image:test-image' === $linkedin_payload['content']['media']['id'], 'LinkedIn postu yüklenen öne çıkan görseli kullanmalı' );
$linkedin_requests = array_slice( $GLOBALS['request_history'], -3 );
$image_init = json_decode( $linkedin_requests[0][1]['body'], true );
expect( 'https://api.linkedin.com/rest/images?action=initializeUpload' === $linkedin_requests[0][0] && 'urn:li:person:li-123' === $image_init['initializeUploadRequest']['owner'], 'LinkedIn görseli doğru yazarla resmi Images API üzerinden başlatılmalı' );
expect( 'PUT' === $linkedin_requests[1][1]['method'] && 'image/jpeg' === $linkedin_requests[1][1]['headers']['Content-Type'] && 'test image bytes' === $linkedin_requests[1][1]['body'], 'LinkedIn görsel yüklemesi doğru HTTP metodu ve MIME ile yapılmalı' );
expect( '202511' === $linkedin_requests[0][1]['headers']['LinkedIn-Version'] && '202511' === $linkedin_requests[2][1]['headers']['LinkedIn-Version'], 'LinkedIn Images ve Posts API istekleri aynı sürümü kullanmalı' );
expect( 'Başarılı' === get_option( 'wsp_delivery_logs' )[0]['status'] && 'urn:li:share:789' === get_option( 'wsp_delivery_logs' )[0]['remote_id'], 'Başarılı LinkedIn gönderimi Logs kaydına yazılmalı' );
expect( ! str_contains( json_encode( get_option( 'wsp_delivery_logs' ) ), 'li-access' ), 'Logs kayıtları erişim belirteci içermemeli' );
$_GET = array(
	'page'     => 'wsp-logs',
	'platform' => 'linkedin',
	'status'   => 'Başarılı',
	'day'      => wp_date( 'Y-m-d', strtotime( get_option( 'wsp_delivery_logs' )[0]['created_at'] . ' UTC' ), wp_timezone() ),
);
ob_start();
wsp_logs_page();
$logs_page = ob_get_clean();
expect( str_contains( $logs_page, 'urn:li:share:789' ) && str_contains( $logs_page, 'Deneme başlığı' ) && str_contains( $logs_page, 'name="day"' ), 'Logs ekranı sonuçları platform/durum/gün filtresiyle göstermeli' );
$_GET = array();
$GLOBALS['posts'] = array( $post );
ob_start();
wsp_auto_post_page();
$auto_post_page = ob_get_clean();
expect( str_contains( $auto_post_page, 'name="platforms[]"' ) && str_contains( $auto_post_page, 'datetime-local' ) && str_contains( $auto_post_page, 'Takvime plan olarak ekle' ), 'WP Auto Post plan oluşturma alanlarını göstermeli' );
$_GET = array( 'day' => '2026-11-25' );
ob_start();
wsp_auto_post_page();
$preselected_auto_post_page = ob_get_clean();
expect( str_contains( $preselected_auto_post_page, 'value="2026-11-25T09:00"' ), 'Takvimden seçilen gün otomatik plan formuna taşınmalı' );
expect( str_contains( $preselected_auto_post_page, 'Teknoloji' ) && str_contains( $preselected_auto_post_page, 'Son planlanan 10 gönderi' ), 'Autopost kategori bilgisi ve son 10 planı göstermeli' );
expect( (bool) preg_match( '/<section class="wsp-panel wsp-scheduler-calendar">.*?<\/section>\s*<section class="wsp-panel wsp-scheduler-recent">/s', $preselected_auto_post_page ), 'Takvim bağlantısı ve son planlar ayrı panellerde olmalı' );
$GLOBALS['password_override'] = str_repeat( 'c', 32 );
$_POST = array( 'platform' => 'linkedin', 'post_id' => '12' );
try {
	wsp_facebook_preview();
} catch ( RuntimeException $error ) {}
$_POST = array( 'draft' => str_repeat( 'c', 32 ), 'platform' => 'linkedin' );
$GLOBALS['response'] = array( 'code' => 403, 'body' => '{"message":"li-access"}' );
try {
	wsp_facebook_publish();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'LinkedIn API hatası kullanıcıya bildirilmeli' );
}
expect( 'Başarısız' === get_option( 'wsp_delivery_logs' )[0]['status'] && ! str_contains( json_encode( get_option( 'wsp_delivery_logs' )[0] ), 'li-access' ), 'LinkedIn hatası belirteç sızdırmadan Logs içine yazılmalı' );
expect( str_contains( get_option( 'wsp_delivery_logs' )[0]['message'], 'HTTP 403' ), 'LinkedIn API hata yanıtının HTTP durumu Logs kaydına eklenmeli' );
unlink( $GLOBALS['attached_file'] );
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
$GLOBALS['options']['wsp_delivery_logs'] = array(
	array( 'id' => 'cal-1', 'created_at' => '2026-11-18 07:15:00', 'platform' => 'linkedin', 'platform_name' => 'LinkedIn', 'post_id' => 12, 'post_title' => 'Deneme başlığı', 'status' => 'Başarılı', 'message' => 'Gönderildi.', 'remote_id' => 'li-cal' ),
	array( 'id' => 'cal-2', 'created_at' => '2026-11-19 08:15:00', 'platform' => 'facebook', 'platform_name' => 'Facebook', 'post_id' => 12, 'post_title' => 'Deneme başlığı', 'status' => 'Başarısız', 'message' => 'İzin yok.', 'remote_id' => '' ),
);
$_GET = array( 'month' => '2026-11' );
ob_start();
wsp_calendar_page();
$calendar = ob_get_clean();
expect( str_contains( $calendar, '09:30' ) && str_contains( $calendar, 'LinkedIn' ) && str_contains( $calendar, 'Facebook' ) && str_contains( $calendar, 'Başarılı' ) && str_contains( $calendar, 'Başarısız' ), 'Takvim planlanan ve gerçekleşen platform/durum kayıtlarını göstermeli' );
expect( str_contains( $calendar, 'Bu güne planla' ) && str_contains( $calendar, 'wsp-event-success' ), 'Takvim plan oluşturma bağlantısı ve durum göstergesi sunmalı' );
expect( '2026-11-01T00:00' === $GLOBALS['last_query']['meta_query'][0][0]['value'], 'Takvim ay başından sorgulamalı' );
expect( '2026-12-01T00:00' === $GLOBALS['last_query']['meta_query'][0][1]['value'], 'Takvim sonraki ayı dışlamalı' );
ob_start();
wsp_schedules_page();
$schedule = ob_get_clean();
expect( str_contains( $schedule, '2026-11-18 09:30' ) && str_contains( $schedule, 'Deneme başlığı' ) && str_contains( $schedule, 'Teknoloji' ), 'Plan listesi tarih, yazı ve kategoriyi göstermeli' );
expect( str_contains( $schedule, 'date_from' ) && str_contains( $schedule, 'date_to' ) && str_contains( $schedule, 'name="cat"' ), 'Plan listesi kategori ve tarih filtreleri sunmalı' );
expect( str_contains( $schedule, 'wsp-filter-bar wsp-schedules-filter' ), 'Plan filtrelerine özel boşluk sınıfı eklenmeli' );
$GLOBALS['options']['wsp_oauth_accounts']['linkedin'] = array( 'access_token' => 'encrypted-token', 'profile' => array( 'id' => 'li-123', 'name' => 'LinkedIn Tester' ) );
$_POST = array( 'post_id' => '12', 'platforms' => array( 'linkedin', 'facebook' ), 'scheduled_at' => '2026-11-20T14:45' );
try {
	wsp_save_auto_post();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Başarılı plan kaydı otomatik plan sayfasına yönlendirmeli' );
}
expect( '2026-11-20T14:45' === get_post_meta( 12, '_wsp_scheduled_at', true ) && '1' === get_post_meta( 12, '_wsp_linkedin_enabled', true ) && '1' === get_post_meta( 12, '_wsp_facebook_enabled', true ), 'WP Auto Post seçilen yazı/platform/tarihi WordPress meta alanlarına kaydetmeli' );
expect( false !== wp_next_scheduled( 'wsp_publish_scheduled_post', array( 12 ) ) && 'scheduled' === get_post_meta( 12, '_wsp_schedule_state', true ), 'Kaydedilen paylaşım WP-Cron kuyruğuna eklenmeli' );
$GLOBALS['test_post'] = $post;
$_GET = array( 'month' => '2026-11' );
ob_start();
wsp_calendar_page();
$plan_calendar = ob_get_clean();
expect( str_contains( $plan_calendar, 'Düzenle' ) && str_contains( $plan_calendar, 'wsp_remove_scheduled_platform' ) && str_contains( $plan_calendar, '>Sil</button>' ), 'Gelecek planlarda düzenleme ve platform silme düğmeleri görünmeli' );
expect( str_contains( $plan_calendar, 'class="wsp-event-actions"' ), 'Takvim planı düğmeleri aynı hizalama satırında olmalı' );
$_POST = array( 'post_id' => '12', 'platform' => 'linkedin', 'scheduled_at' => '2026-11-20T14:45' );
$GLOBALS['nonce_valid'] = false;
try { wsp_remove_scheduled_platform(); } catch ( RuntimeException $error ) {}
expect( '1' === get_post_meta( 12, '_wsp_linkedin_enabled', true ), 'Geçersiz nonce ile plan silinmemeli' );
$GLOBALS['nonce_valid'] = true;
try {
	wsp_remove_scheduled_platform();
} catch ( RuntimeException $error ) {
	expect( str_starts_with( $error->getMessage(), 'redirect:' ), 'Platform plandan çıkarılınca takvime dönülmeli' );
}
expect( '0' === get_post_meta( 12, '_wsp_linkedin_enabled', true ) && '1' === get_post_meta( 12, '_wsp_facebook_enabled', true ) && false !== wp_next_scheduled( 'wsp_publish_scheduled_post', array( 12 ) ), 'Yalnız seçilen platform çıkarılmalı, diğerinin cron görevi korunmalı' );
$_POST['scheduled_at'] = '2026-11-21T14:45';
try { wsp_remove_scheduled_platform(); } catch ( RuntimeException $error ) {}
expect( '1' === get_post_meta( 12, '_wsp_facebook_enabled', true ), 'Eski plan bilgisi diğer platformu silmemeli' );
$_POST = array( 'post_id' => '12', 'platform' => 'facebook', 'scheduled_at' => '2026-11-20T14:45' );
try { wsp_remove_scheduled_platform(); } catch ( RuntimeException $error ) {}
expect( '' === get_post_meta( 12, '_wsp_scheduled_at', true ) && false === wp_next_scheduled( 'wsp_publish_scheduled_post', array( 12 ) ), 'Son platform çıkarılınca plan ve cron görevi kaldırılmalı' );
$GLOBALS['meta'][12]['_wsp_scheduled_at'] = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '-5 minutes' )->format( 'Y-m-d\TH:i' );
$GLOBALS['meta'][12]['_wsp_facebook_enabled'] = '1';
$GLOBALS['meta'][12]['_wsp_linkedin_enabled'] = '0';
$GLOBALS['response_queue'] = array( array( 'code' => 200, 'body' => '{"id":"fb-scheduled"}' ) );
$GLOBALS['test_post'] = $post;
wsp_dispatch_scheduled_post( 12 );
$scheduled_log_count = count( get_option( 'wsp_delivery_logs' ) );
expect( 'completed' === get_post_meta( 12, '_wsp_schedule_state', true ) && 'Başarılı' === get_option( 'wsp_delivery_logs' )[0]['status'], 'WP-Cron planlanan saatte bağlı hesaba gerçek gönderim yapıp sonucu kaydetmeli' );
wsp_dispatch_scheduled_post( 12 );
expect( $scheduled_log_count === count( get_option( 'wsp_delivery_logs' ) ), 'Yinelenen WP-Cron tetiklemesi ikinci kez paylaşım yapmamalı' );

echo "Smoke checks passed.\n";
