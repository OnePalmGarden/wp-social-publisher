<?php
/**
 * Plugin Name: WP Social Publisher
 * Description: Yazılar için platform bazlı sosyal paylaşım metinleri ve planlama taslağı.
 * Version: 0.9.4
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: OnePalmGarden
 * License: GPL-2.0-or-later
 * Text Domain: wp-social-publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/facebook.php';
require_once __DIR__ . '/includes/oauth-accounts.php';
require_once __DIR__ . '/includes/publishing.php';

function wsp_platforms(): array {
	return array(
		'x'         => 'Twitter / X',
		'linkedin'  => 'LinkedIn',
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'reddit'    => 'Reddit',
		'pinterest' => 'Pinterest',
	);
}

function wsp_default_template_fields(): array {
	return array(
		'content' => "{title}\n\n{excerpt}",
		'url'     => '{url}',
		'tags'    => '{tags}',
	);
}

function wsp_template_fields( string $platform ): array {
	$templates = get_option( 'wsp_templates', array() );
	$fields = wsp_default_template_fields();
	if ( ! is_array( $templates ) || ! isset( $templates[ $platform ] ) ) {
		return $fields;
	}

	$saved = $templates[ $platform ];
	if ( is_string( $saved ) ) {
		$legacy_default = "{title}\n\n{excerpt}\n\n{url}\n\n{tags}";
		if ( $legacy_default === $saved ) {
			return $fields;
		}
		$fields['content'] = $saved;
		$fields['url'] = '';
		$fields['tags'] = '';
		return $fields;
	}

	if ( is_array( $saved ) ) {
		foreach ( array_keys( $fields ) as $field ) {
			if ( isset( $saved[ $field ] ) && is_string( $saved[ $field ] ) ) {
				$fields[ $field ] = $saved[ $field ];
			}
		}
	}
	return $fields;
}

function wsp_template( string $platform ): string {
	return implode( "\n\n", array_filter( wsp_template_fields( $platform ), static fn( $field ) => '' !== trim( $field ) ) );
}

function wsp_template_values( WP_Post $post ): array {
	$tags = get_the_tags( $post->ID );
	$tag_names = array();
	$hashtags = array();
	if ( is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$tag_names[] = $tag->name;
			$hashtag = preg_replace( '/[^\p{L}\p{M}\p{N}_]+/u', '', $tag->name );
			if ( is_string( $hashtag ) && '' !== $hashtag ) {
				$hashtags[] = '#' . $hashtag;
			}
		}
	}
	return array(
			'{title}'   => get_the_title( $post ),
			'{excerpt}' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'{url}'     => get_permalink( $post ),
			'{tags}'    => implode( ', ', $tag_names ),
			'{hashtags}' => implode( ' ', $hashtags ),
			'{site_title}' => get_bloginfo( 'name' ),
	);
}

function wsp_render_template( string $template, WP_Post $post ): string {
	return strtr( $template, wsp_template_values( $post ) );
}

function wsp_post_share_message( string $platform, WP_Post $post ): string {
	if ( 'x' === $platform && '1' === get_post_meta( $post->ID, '_wsp_x_fields_enabled', true ) ) {
		$fields = get_post_meta( $post->ID, '_wsp_x_fields', true );
		$fields = is_array( $fields ) ? $fields : array();
		$parts = array();
		foreach ( array( 'title', 'content', 'link', 'tags' ) as $key ) {
			$value = isset( $fields[ $key ] ) && is_string( $fields[ $key ] ) ? trim( $fields[ $key ] ) : '';
			if ( '' !== $value ) {
				$parts[] = wsp_render_template( $value, $post );
			}
		}
		return implode( "\n\n", array_filter( $parts, static fn( $part ) => '' !== trim( $part ) ) );
	}
	$custom = (string) get_post_meta( $post->ID, '_wsp_' . $platform . '_text', true );
	return wsp_render_template( '' !== $custom ? $custom : wsp_template( $platform ), $post );
}

function wsp_x_complete_message( WP_Post $post, string $message, string $link ): string {
	if ( '1' === get_post_meta( $post->ID, '_wsp_x_fields_enabled', true ) || str_contains( $message, $link ) ) {
		return $message;
	}
	return $message . "\n\n" . $link;
}

function wsp_x_character_count( string $text ): int {
	$count = preg_match_all( '/./us', $text );
	return false === $count ? 0 : $count;
}

function wsp_x_character_limit(): int {
	return 200;
}

function wsp_editor_assets( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_script( 'wsp-post-preview', plugin_dir_url( __FILE__ ) . 'assets/post-preview.js', array(), '0.9.4', true );
}
add_action( 'admin_enqueue_scripts', 'wsp_editor_assets' );

/**
 * Register the sharing editor box for blog posts.
 */
function wsp_register_share_metabox(): void {
	add_meta_box(
		'wsp-share-settings',
		__( 'Sosyal paylaşım taslağı', 'wp-social-publisher' ),
		'wsp_render_share_metabox',
		'post',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'wsp_register_share_metabox' );

/**
 * @param WP_Post $post Current post.
 */
function wsp_render_share_metabox( WP_Post $post ): void {
	$platforms = wsp_platforms();
	wp_nonce_field( 'wsp_save_share_settings', 'wsp_share_nonce' );
	?>
	<p><?php esc_html_e( 'Platformları seçin. Yazıya özel metni boş bırakırsanız o platformun varsayılan şablonu kullanılır. Alanlarda {title}, {excerpt}, {url}, {tags} ve {hashtags} kullanabilirsiniz.', 'wp-social-publisher' ); ?></p>
	<p><?php esc_html_e( 'Önizleme en son kaydedilen yazı bilgilerine dayanır. Öne çıkan görsel gönderi metninden ayrı bir medya öğesidir; görsel desteği platform bağlantısına göre değişir.', 'wp-social-publisher' ); ?></p>
	<div class="wsp-platform-list">
		<?php foreach ( $platforms as $key => $label ) : ?>
			<?php
			$enabled = (bool) get_post_meta( $post->ID, '_wsp_' . $key . '_enabled', true );
			$text    = (string) get_post_meta( $post->ID, '_wsp_' . $key . '_text', true );
			$preview = wsp_post_share_message( $key, $post );
			if ( 'x' === $key ) {
				$preview = wsp_x_complete_message( $post, $preview, get_permalink( $post ) );
			}
			?>
			<fieldset <?php if ( 'x' === $key ) : ?>class="wsp-x-preview" data-limit="<?php echo esc_attr( (string) wsp_x_character_limit() ); ?>" data-template="<?php echo esc_attr( wsp_template( $key ) ); ?>" data-values="<?php echo esc_attr( wp_json_encode( wsp_template_values( $post ) ) ); ?>"<?php endif; ?> style="margin: 0 0 16px; padding: 12px; border: 1px solid #dcdcde;">
				<legend style="padding: 0 6px; font-weight: 600;"><?php echo esc_html( $label ); ?></legend>
				<label>
					<input type="checkbox" name="wsp_platforms[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?>>
					<?php esc_html_e( 'Bu yazıyı bu platform için hazırla', 'wp-social-publisher' ); ?>
				</label>
				<p style="margin-bottom: 0;">
					<label for="wsp-<?php echo esc_attr( $key ); ?>-text"><?php esc_html_e( 'Yazıya özel metin (isteğe bağlı)', 'wp-social-publisher' ); ?></label>
					<textarea id="wsp-<?php echo esc_attr( $key ); ?>-text" name="wsp_platforms[<?php echo esc_attr( $key ); ?>][text]" rows="3" class="widefat"><?php echo esc_textarea( $text ); ?></textarea>
				</p>
				<?php if ( 'x' === $key ) : ?>
					<?php
					$x_fields = get_post_meta( $post->ID, '_wsp_x_fields', true );
					$x_fields = is_array( $x_fields ) ? $x_fields : array( 'title' => '🔥 {title}', 'content' => '{excerpt}', 'link' => '👉 {url}', 'tags' => '🔍 {hashtags}' );
					$x_fields_enabled = '1' === get_post_meta( $post->ID, '_wsp_x_fields_enabled', true );
					?>
					<p><label><input type="checkbox" class="wsp-x-fields-enabled" name="wsp_x_fields_enabled" value="1" <?php checked( $x_fields_enabled ); ?>> <?php esc_html_e( 'Dört alanlı özel X taslağını kullan', 'wp-social-publisher' ); ?></label></p>
					<p class="description"><?php esc_html_e( 'Etkinleştirildiğinde yukarıdaki özel metin ve Templates yerine bu alanlar kullanılır. Alanlarda akıllı etiketler kullanılabilir.', 'wp-social-publisher' ); ?></p>
					<div class="wsp-x-fields">
						<?php foreach ( array( 'title' => 'Başlık', 'content' => 'İçerik', 'link' => 'Link', 'tags' => 'Etiketler' ) as $field => $label ) : ?>
							<p><label for="wsp-x-field-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label><br>
								<textarea id="wsp-x-field-<?php echo esc_attr( $field ); ?>" class="widefat wsp-x-field" name="wsp_x_fields[<?php echo esc_attr( $field ); ?>]" rows="<?php echo 'content' === $field ? '3' : '1'; ?>"><?php echo esc_textarea( isset( $x_fields[ $field ] ) && is_string( $x_fields[ $field ] ) ? $x_fields[ $field ] : '' ); ?></textarea>
								<span class="wsp-x-field-count" aria-live="polite"></span>
							</p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<details style="margin-top: 10px;">
					<summary><?php esc_html_e( 'Metnin önizlemesi', 'wp-social-publisher' ); ?></summary>
					<p class="wsp-post-preview-text" style="white-space: pre-wrap;"><?php echo esc_html( $preview ); ?></p>
				</details>
				<?php if ( 'x' === $key ) : ?>
					<?php $count = wsp_x_character_count( $preview ); ?>
					<p class="wsp-x-count" aria-live="polite">
						<?php echo esc_html( sprintf( __( 'Tahmini karakter sayısı: %1$d / %2$d', 'wp-social-publisher' ), $count, wsp_x_character_limit() ) ); ?>
						<?php if ( $count > wsp_x_character_limit() ) : ?> <strong><?php esc_html_e( '200 karakterlik hedef aşıldı.', 'wp-social-publisher' ); ?></strong><?php endif; ?>
					</p>
					<p class="description"><?php esc_html_e( 'Toplam 200 karakteri aşarsa X gönderimi engellenir. Taslak kaydedilebilir; X bağlantı ve emojileri farklı hesaplayabilir. Yazı bilgileri son kaydedilen sürümdendir.', 'wp-social-publisher' ); ?></p>
				<?php endif; ?>
			</fieldset>
		<?php endforeach; ?>
	</div>
	<p>
		<label for="wsp-scheduled-at"><strong><?php esc_html_e( 'Planlanan tarih ve saat', 'wp-social-publisher' ); ?></strong></label><br>
		<input type="datetime-local" id="wsp-scheduled-at" name="wsp_scheduled_at" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_wsp_scheduled_at', true ) ); ?>">
		<span class="description"><?php esc_html_e( 'Seçili tüm platformlar için ortak tarih; WordPress sitenizin saat dilimi kullanılır.', 'wp-social-publisher' ); ?></span>
	</p>
	<?php
}

/**
 * Save validated sharing settings from the post editor.
 */
function wsp_save_share_metabox( int $post_id ): void {
	if ( ! isset( $_POST['wsp_share_nonce'] ) || ! is_string( $_POST['wsp_share_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsp_share_nonce'] ) ), 'wsp_save_share_settings' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	$platforms = array_keys( wsp_platforms() );
	$submitted = isset( $_POST['wsp_platforms'] ) && is_array( $_POST['wsp_platforms'] )
		? wp_unslash( $_POST['wsp_platforms'] )
		: array();

	foreach ( $platforms as $platform ) {
		$settings = isset( $submitted[ $platform ] ) && is_array( $submitted[ $platform ] )
			? $submitted[ $platform ]
			: array();

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {
			update_post_meta( $post_id, '_wsp_' . $platform . '_enabled', '1' );
		} else {
			delete_post_meta( $post_id, '_wsp_' . $platform . '_enabled' );
		}
		if ( isset( $_POST['wsp_x_fields'] ) && is_array( $_POST['wsp_x_fields'] ) ) {
			$submitted_fields = wp_unslash( $_POST['wsp_x_fields'] );
			$x_fields = array();
			foreach ( array( 'title', 'content', 'link', 'tags' ) as $field ) {
				$x_fields[ $field ] = isset( $submitted_fields[ $field ] ) && is_string( $submitted_fields[ $field ] ) ? sanitize_textarea_field( $submitted_fields[ $field ] ) : '';
			}
			update_post_meta( $post_id, '_wsp_x_fields', $x_fields );
			update_post_meta( $post_id, '_wsp_x_fields_enabled', isset( $_POST['wsp_x_fields_enabled'] ) && '1' === $_POST['wsp_x_fields_enabled'] ? '1' : '0' );
		}

		$text = isset( $settings['text'] ) && is_string( $settings['text'] )
			? sanitize_textarea_field( $settings['text'] )
			: '';
		update_post_meta( $post_id, '_wsp_' . $platform . '_text', $text );
	}

	$scheduled_at = isset( $_POST['wsp_scheduled_at'] ) && is_string( $_POST['wsp_scheduled_at'] )
		? sanitize_text_field( wp_unslash( $_POST['wsp_scheduled_at'] ) )
		: '';

	if ( '' === $scheduled_at ) {
		delete_post_meta( $post_id, '_wsp_scheduled_at' );
		wsp_sync_scheduled_post( $post_id );
		return;
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled_at, wp_timezone() );
	$errors = DateTimeImmutable::getLastErrors();
	if ( false !== $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $date->format( 'Y-m-d\TH:i' ) === $scheduled_at ) {
		update_post_meta( $post_id, '_wsp_scheduled_at', $scheduled_at );
		wsp_sync_scheduled_post( $post_id );
	}
}
add_action( 'save_post', 'wsp_save_share_metabox' );
