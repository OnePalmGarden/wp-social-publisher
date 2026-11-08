<?php

define( 'ABSPATH', __DIR__ );
$GLOBALS['meta'] = array();
$GLOBALS['options'] = array();
$GLOBALS['permitted'] = true;
$GLOBALS['posts'] = array();
$GLOBALS['enqueued'] = array();

class WP_Post {
	public int $ID;
	public string $post_status = 'publish';
	public function __construct( int $id ) { $this->ID = $id; }
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
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][ $id ][ $key ] = $value; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['meta'][ $id ][ $key ] ); }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) { return 'valid' === $nonce; }
function wp_is_post_revision( $id ) { return false; }
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
function add_query_arg( $key, $value, $url ) { return $url . '&' . $key . '=' . $value; }
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

$_GET = array( 'page' => 'wsp-templates' );
wsp_admin_assets( 'unused-hook-name' );
expect( 2 === count( $GLOBALS['enqueued'] ), 'Şablon sayfası CSS ve JS eklemeli' );
ob_start();
wsp_templates_page();
$template_page = ob_get_clean();
expect( str_contains( $template_page, 'Akıllı etiketleri göster' ) && str_contains( $template_page, 'data-smart-tag="{title}"' ), 'Akıllı etiketleri gösterip ekleyebilmeli' );
expect( str_contains( $template_page, 'wsp-template-grid' ) && str_contains( $template_page, 'wsp-preview-text' ), 'Üç bölümlü düzen ve önizleme bulunmalı' );

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
