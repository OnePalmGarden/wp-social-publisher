<?php
/**
 * Plugin Name: WP Social Publisher
 * Description: Yazılar için platform bazlı sosyal paylaşım metinleri ve planlama taslağı.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: OnePalmGarden
 * License: GPL-2.0-or-later
 * Text Domain: wp-social-publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	$platforms = array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'medium'    => 'Medium',
		'substack'  => 'Substack',
	);
	wp_nonce_field( 'wsp_save_share_settings', 'wsp_share_nonce' );
	?>
	<p><?php esc_html_e( 'Her platform için paylaşım hedefini ve metnini ayrı ayrı belirleyin.', 'wp-social-publisher' ); ?></p>
	<div class="wsp-platform-list">
		<?php foreach ( $platforms as $key => $label ) : ?>
			<?php
			$enabled = (bool) get_post_meta( $post->ID, '_wsp_' . $key . '_enabled', true );
			$text    = (string) get_post_meta( $post->ID, '_wsp_' . $key . '_text', true );
			?>
			<fieldset style="margin: 0 0 16px; padding: 12px; border: 1px solid #dcdcde;">
				<legend style="padding: 0 6px; font-weight: 600;"><?php echo esc_html( $label ); ?></legend>
				<label>
					<input type="checkbox" name="wsp_platforms[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?>>
					<?php esc_html_e( 'Bu yazıyı bu platform için hazırla', 'wp-social-publisher' ); ?>
				</label>
				<p style="margin-bottom: 0;">
					<label for="wsp-<?php echo esc_attr( $key ); ?>-text"><?php esc_html_e( 'Paylaşım metni', 'wp-social-publisher' ); ?></label>
					<textarea id="wsp-<?php echo esc_attr( $key ); ?>-text" name="wsp_platforms[<?php echo esc_attr( $key ); ?>][text]" rows="3" class="widefat"><?php echo esc_textarea( $text ); ?></textarea>
				</p>
			</fieldset>
		<?php endforeach; ?>
	</div>
	<p>
		<label for="wsp-scheduled-at"><strong><?php esc_html_e( 'Planlanan tarih ve saat', 'wp-social-publisher' ); ?></strong></label><br>
		<input type="datetime-local" id="wsp-scheduled-at" name="wsp_scheduled_at" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_wsp_scheduled_at', true ) ); ?>">
		<span class="description"><?php esc_html_e( 'WordPress sitenizin saat dilimi kullanılır.', 'wp-social-publisher' ); ?></span>
	</p>
	<p><strong><?php esc_html_e( 'Deneme sürümü:', 'wp-social-publisher' ); ?></strong> <?php esc_html_e( 'Bu sürüm seçimleri kaydeder; sosyal platformlara gönderim yapmaz.', 'wp-social-publisher' ); ?></p>
	<?php
}

/**
 * Save validated sharing settings from the post editor.
 */
function wsp_save_share_metabox( int $post_id ): void {
	if ( ! isset( $_POST['wsp_share_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsp_share_nonce'] ) ), 'wsp_save_share_settings' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	$platforms = array( 'facebook', 'instagram', 'medium', 'substack' );
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
		return;
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled_at, wp_timezone() );
	$errors = DateTimeImmutable::getLastErrors();
	if ( false !== $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $date->format( 'Y-m-d\TH:i' ) === $scheduled_at ) {
		update_post_meta( $post_id, '_wsp_scheduled_at', $scheduled_at );
	}
}
add_action( 'save_post', 'wsp_save_share_metabox' );
