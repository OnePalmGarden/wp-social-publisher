<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wsp_social_api_error( $response, string $access_token ): WP_Error {
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'social_network', 'Platform bağlantısında hata oluştu; gönderi sonucu belirsiz olabilir. Tekrar denemeden önce hesabı kontrol edin.' );
	}
	$status = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$message = '';
	if ( is_array( $data ) ) {
		$message = isset( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : ( isset( $data['error_description'] ) && is_string( $data['error_description'] ) ? $data['error_description'] : '' );
		if ( '' === $message && isset( $data['error'] ) && is_string( $data['error'] ) ) {
			$message = $data['error'];
		}
		if ( '' === $message && isset( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
			$message = $data['error']['message'];
		}
		if ( '' === $message && isset( $data['errors'][0]['message'] ) && is_string( $data['errors'][0]['message'] ) ) {
			$message = $data['errors'][0]['message'];
		}
	}
	$message = sanitize_text_field( $message );
	if ( '' !== $access_token ) {
		$message = str_replace( $access_token, '[gizlendi]', $message );
	}
	return new WP_Error( 'social_api_error', 'Platform isteği başarısız oldu (HTTP ' . (int) $status . ')' . ( '' !== $message ? ': ' . $message : '' ) . '. İzinleri ve platform hesabını kontrol edin.' );
}

function wsp_publish_x_post( string $access_token, string $message ) {
	$body = wp_json_encode( array( 'text' => $message ) );
	if ( ! is_string( $body ) ) {
		return new WP_Error( 'x_invalid_post', 'X gönderi metni kodlanamadı.' );
	}
	$response = wp_remote_request(
		'https://api.x.com/2/tweets',
		array(
			'method'      => 'POST',
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
			'body'        => $body,
		)
	);
	if ( is_wp_error( $response ) || 201 !== wp_remote_retrieve_response_code( $response ) ) {
		return wsp_social_api_error( $response, $access_token );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	return isset( $data['data']['id'] ) && is_string( $data['data']['id'] )
		? array( 'id' => $data['data']['id'] )
		: new WP_Error( 'x_missing_id', 'X gönderi kimliği döndürmedi; tekrar denemeden önce hesabı kontrol edin.' );
}

function wsp_publish_instagram_post( array $account, string $message, string $link, string $image_url ) {
	if ( '' === $image_url || ! wp_http_validate_url( $image_url ) || 'https' !== wp_parse_url( $image_url, PHP_URL_SCHEME ) ) {
		return new WP_Error( 'instagram_image_required', 'Instagram gönderisi için herkese açık HTTPS öne çıkan görsel gereklidir.' );
	}
	$caption = trim( $message . "\n\n" . $link );
	$container = wp_remote_post(
		'https://graph.instagram.com/v24.0/' . rawurlencode( $account['profile']['id'] ) . '/media',
		array(
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $account['access_token'], 'Accept' => 'application/json' ),
			'body'        => array( 'image_url' => $image_url, 'caption' => $caption ),
		)
	);
	if ( is_wp_error( $container ) || 200 !== wp_remote_retrieve_response_code( $container ) ) {
		return wsp_social_api_error( $container, $account['access_token'] );
	}
	$container_data = json_decode( wp_remote_retrieve_body( $container ), true );
	$creation_id = isset( $container_data['id'] ) && is_string( $container_data['id'] ) ? $container_data['id'] : '';
	if ( '' === $creation_id ) {
		return new WP_Error( 'instagram_container_missing', 'Instagram medya konteyneri oluşturulamadı.' );
	}
	$published = wp_remote_post(
		'https://graph.instagram.com/v24.0/' . rawurlencode( $account['profile']['id'] ) . '/media_publish',
		array(
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $account['access_token'], 'Accept' => 'application/json' ),
			'body'        => array( 'creation_id' => $creation_id ),
		)
	);
	if ( is_wp_error( $published ) || 200 !== wp_remote_retrieve_response_code( $published ) ) {
		return wsp_social_api_error( $published, $account['access_token'] );
	}
	$data = json_decode( wp_remote_retrieve_body( $published ), true );
	return isset( $data['id'] ) && is_string( $data['id'] )
		? array( 'id' => $data['id'] )
		: new WP_Error( 'instagram_missing_id', 'Instagram gönderi kimliği döndürmedi; tekrar denemeden önce hesabı kontrol edin.' );
}

function wsp_publish_reddit_post( array $account, array $app, WP_Post $post, string $message, string $link ) {
	$subreddit = isset( $app['subreddit'] ) && is_string( $app['subreddit'] ) ? trim( $app['subreddit'] ) : '';
	if ( ! preg_match( '/^[A-Za-z0-9_]{2,21}$/', $subreddit ) ) {
		return new WP_Error( 'reddit_subreddit_required', 'Accounts sayfasında geçerli bir hedef subreddit kaydedin.' );
	}
	$response = wp_remote_post(
		'https://oauth.reddit.com/api/submit',
		array(
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $account['access_token'], 'User-Agent' => 'WP Social Publisher/0.9 by u/' . $account['profile']['username'], 'Accept' => 'application/json' ),
			'body'        => array(
				'api_type' => 'json',
				'kind'     => 'link',
				'sr'       => $subreddit,
				'title'    => sanitize_text_field( get_the_title( $post ) ),
				'url'      => $link,
				'resubmit' => 'false',
			),
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return wsp_social_api_error( $response, $account['access_token'] );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$errors = $data['json']['errors'] ?? array();
	if ( ! empty( $errors ) ) {
		return new WP_Error( 'reddit_submit_failed', 'Reddit gönderiyi reddetti. Subreddit kurallarını ve hesabın gönderi izinlerini kontrol edin.' );
	}
	$id = $data['json']['data']['id'] ?? '';
	return is_string( $id ) && '' !== $id
		? array( 'id' => $id )
		: new WP_Error( 'reddit_missing_id', 'Reddit gönderi kimliği döndürmedi; tekrar denemeden önce subreddit’i kontrol edin.' );
}

function wsp_publish_pinterest_post( array $account, array $app, WP_Post $post, string $message, string $link, string $image_url ) {
	$board_id = isset( $app['board_id'] ) && is_string( $app['board_id'] ) ? trim( $app['board_id'] ) : '';
	if ( '' === $board_id || '' === $image_url || ! wp_http_validate_url( $image_url ) || 'https' !== wp_parse_url( $image_url, PHP_URL_SCHEME ) ) {
		return new WP_Error( 'pinterest_pin_requirements', 'Pinterest için pano kimliği ve herkese açık HTTPS öne çıkan görsel gereklidir.' );
	}
	$body = wp_json_encode( array(
		'board_id'     => $board_id,
		'title'        => sanitize_text_field( get_the_title( $post ) ),
		'description'  => $message,
		'link'         => $link,
		'alt_text'     => sanitize_text_field( get_the_title( $post ) ),
		'media_source' => array( 'source_type' => 'image_url', 'url' => $image_url ),
	) );
	if ( ! is_string( $body ) ) {
		return new WP_Error( 'pinterest_invalid_pin', 'Pinterest Pin içeriği kodlanamadı.' );
	}
	$response = wp_remote_request(
		'https://api.pinterest.com/v5/pins',
		array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Bearer ' . $account['access_token'], 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
			'body'        => $body,
		)
	);
	if ( is_wp_error( $response ) || 201 !== wp_remote_retrieve_response_code( $response ) ) {
		return wsp_social_api_error( $response, $account['access_token'] );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	return isset( $data['id'] ) && is_string( $data['id'] )
		? array( 'id' => $data['id'] )
		: new WP_Error( 'pinterest_missing_id', 'Pinterest Pin kimliği döndürmedi; tekrar denemeden önce panoyu kontrol edin.' );
}

function wsp_publish_platform_post( string $platform, WP_Post $post, string $message, string $link ) {
	$account = wsp_share_platform_account( $platform );
	if ( is_wp_error( $account ) ) {
		return $account;
	}
	if ( 'facebook' === $platform ) {
		return wsp_facebook_request( $account['page_id'] . '/feed', $account['token'], 'POST', array( 'message' => $message, 'link' => $link ) );
	}
	if ( 'linkedin' === $platform ) {
		return wsp_linkedin_publish_post( $account['access_token'], (string) $account['profile']['id'], $message, (int) get_post_thumbnail_id( $post ), get_the_title( $post ) );
	}
	if ( 'x' === $platform ) {
		$x_message = wsp_x_complete_message( $post, $message, $link );
		if ( wsp_x_character_count( $x_message ) > wsp_x_character_limit() ) {
			return new WP_Error( 'x_character_limit', 'X gönderisi 200 karakterlik hedefi aşıyor; metni kısaltın.' );
		}
		return wsp_publish_x_post( $account['access_token'], $x_message );
	}
	$image_url = get_the_post_thumbnail_url( $post, 'large' );
	$image_url = is_string( $image_url ) ? $image_url : '';
	if ( 'instagram' === $platform ) {
		return wsp_publish_instagram_post( $account, $message, $link, $image_url );
	}
	$app = wsp_oauth_apps();
	$app = isset( $app[ $platform ] ) && is_array( $app[ $platform ] ) ? $app[ $platform ] : array();
	if ( 'reddit' === $platform ) {
		return wsp_publish_reddit_post( $account, $app, $post, $message, $link );
	}
	if ( 'pinterest' === $platform ) {
		return wsp_publish_pinterest_post( $account, $app, $post, $message, $link, $image_url );
	}
	return new WP_Error( 'unsupported_platform', 'Bu platformda yayınlama desteklenmiyor.' );
}

function wsp_sync_scheduled_post( int $post_id ): bool {
	wp_clear_scheduled_hook( 'wsp_publish_scheduled_post', array( $post_id ) );
	$scheduled = get_post_meta( $post_id, '_wsp_scheduled_at', true );
	$date = is_string( $scheduled ) ? DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled, wp_timezone() ) : false;
	$selected = wsp_selected_platforms( $post_id );
	if ( '' === $scheduled ) {
		update_post_meta( $post_id, '_wsp_schedule_state', 'unscheduled' );
		delete_post_meta( $post_id, '_wsp_schedule_processed_at' );
		delete_option( 'wsp_schedule_lock_' . $post_id );
		return true;
	}
	if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $scheduled || ! $selected || $date->getTimestamp() <= time() ) {
		update_post_meta( $post_id, '_wsp_schedule_state', 'invalid' );
		return false;
	}
	if ( get_post_meta( $post_id, '_wsp_schedule_processed_at', true ) !== $scheduled ) {
		delete_post_meta( $post_id, '_wsp_schedule_processed_at' );
		delete_option( 'wsp_schedule_lock_' . $post_id );
		update_post_meta( $post_id, '_wsp_schedule_state', 'scheduled' );
	}
	$result = wp_schedule_single_event( $date->getTimestamp(), 'wsp_publish_scheduled_post', array( $post_id ), true );
	if ( is_wp_error( $result ) || false === $result ) {
		update_post_meta( $post_id, '_wsp_schedule_state', 'schedule_error' );
		return false;
	}
	return true;
}

function wsp_schedule_delivery_log( string $platform, WP_Post $post, string $scheduled ) {
	$message = wsp_post_share_message( $platform, $post );
	$link = get_permalink( $post );
	if ( 'linkedin' === $platform && is_string( $link ) && ! str_contains( $message, $link ) ) {
		$message = rtrim( $message ) . "\n\n" . $link;
	}
	if ( '' === trim( $message ) || ! is_string( $link ) || ! wp_http_validate_url( $link ) ) {
		return new WP_Error( 'scheduled_invalid_post', 'Paylaşım metni veya yazı bağlantısı geçersiz.' );
	}
	$id = bin2hex( random_bytes( 16 ) );
	$log = array(
		'id'            => $id,
		'created_at'    => gmdate( 'Y-m-d H:i:s' ),
		'platform'      => $platform,
		'platform_name' => wsp_platforms()[ $platform ],
		'post_id'       => $post->ID,
		'post_title'    => sanitize_text_field( get_the_title( $post ) ),
		'status'        => 'Gönderiliyor',
		'message'       => 'Planlanan saat geldi; otomatik gönderim başlatıldı.',
		'remote_id'     => '',
	);
	if ( ! wsp_delivery_log_add( $log ) ) {
		return new WP_Error( 'scheduled_log_failed', 'Gönderim kaydı oluşturulamadı; platforma istek gönderilmedi.' );
	}
	$result = wsp_publish_platform_post( $platform, $post, $message, $link );
	if ( is_wp_error( $result ) ) {
		$error = $result->get_error_message();
		$status = str_contains( strtolower( $error ), 'belirsiz' ) ? 'Belirsiz' : 'Başarısız';
		if ( ! wsp_delivery_log_update( $id, array( 'status' => $status, 'message' => $error ) ) ) {
			return new WP_Error( 'scheduled_log_update_failed', $error . ' Logs kaydı güncellenemedi; hedef hesabı kontrol edin.' );
		}
		return $result;
	}
	$remote_id = isset( $result['id'] ) && is_string( $result['id'] ) ? $result['id'] : '';
	if ( '' === $remote_id ) {
		wsp_delivery_log_update( $id, array( 'status' => 'Belirsiz', 'message' => 'Platform gönderi kimliği döndürmedi; tekrar denemeden önce hesabı kontrol edin.' ) );
		return new WP_Error( 'scheduled_missing_id', 'Platform gönderi kimliği döndürmedi; gönderim sonucu belirsiz.' );
	}
	if ( ! wsp_delivery_log_update( $id, array( 'status' => 'Başarılı', 'message' => 'Zamanlanmış gönderi platformda oluşturuldu.', 'remote_id' => sanitize_text_field( $remote_id ) ) ) ) {
		return new WP_Error( 'scheduled_log_update_failed', 'Gönderi oluşturuldu ancak Logs kaydı güncellenemedi; hedef hesabı kontrol edin.' );
	}
	return $result;
}

function wsp_dispatch_scheduled_post( int $post_id ): void {
	$scheduled = get_post_meta( $post_id, '_wsp_scheduled_at', true );
	$date = is_string( $scheduled ) ? DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled, wp_timezone() ) : false;
	if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $scheduled || $date->getTimestamp() > time() || get_post_meta( $post_id, '_wsp_schedule_processed_at', true ) === $scheduled ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		update_post_meta( $post_id, '_wsp_schedule_state', 'failed' );
		return;
	}
	if ( ! add_option( 'wsp_schedule_lock_' . $post_id, $scheduled, '', false ) ) {
		return;
	}
	update_post_meta( $post_id, '_wsp_schedule_processed_at', $scheduled );
	update_post_meta( $post_id, '_wsp_schedule_state', 'sending' );
	$failed = false;
	foreach ( wsp_platforms() as $platform => $label ) {
		if ( '1' !== get_post_meta( $post_id, '_wsp_' . $platform . '_enabled', true ) ) {
			continue;
		}
		$result = wsp_schedule_delivery_log( $platform, $post, $scheduled );
		if ( is_wp_error( $result ) ) {
			$failed = true;
		}
	}
	update_post_meta( $post_id, '_wsp_schedule_state', $failed ? 'completed_with_errors' : 'completed' );
}
add_action( 'wsp_publish_scheduled_post', 'wsp_dispatch_scheduled_post' );

function wsp_reconcile_scheduled_posts(): void {
	if ( ! function_exists( 'wp_next_scheduled' ) ) {
		return;
	}
	$start = ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d\TH:i' );
	$first_page = wsp_scheduled_posts( $start, null, 1 );
	for ( $page = 1; $page <= max( 1, $first_page->max_num_pages ); $page++ ) {
		$query = 1 === $page ? $first_page : wsp_scheduled_posts( $start, null, $page );
		foreach ( $query->posts as $post ) {
			$scheduled = get_post_meta( $post->ID, '_wsp_scheduled_at', true );
			$date = is_string( $scheduled ) ? DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $scheduled, wp_timezone() ) : false;
			if ( $date && $date->getTimestamp() > time() && get_post_meta( $post->ID, '_wsp_schedule_processed_at', true ) !== $scheduled && ! wp_next_scheduled( 'wsp_publish_scheduled_post', array( $post->ID ) ) ) {
				wsp_sync_scheduled_post( $post->ID );
			}
		}
	}
}
add_action( 'admin_init', 'wsp_reconcile_scheduled_posts' );
