<?php
// functions


add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );
add_theme_support( 'html5' );

// clean wp head
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );

remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

add_filter( 'emoji_svg_url', '__return_false' );

add_action( 'wp_enqueue_scripts', 'wpj_wp_enqueue_scripts' );

function wpj_wp_enqueue_scripts() {
	wp_dequeue_script( 'wp-embed' );
	wp_deregister_script( 'wp-embed' );
}


add_filter( 'embed_oembed_html', 'wpj_embed_oembed_html', 10, 4 );

function wpj_embed_oembed_html( $html, $url, $attr, $post_id ) {
	// vrať původní obsah pokud se nejedná se o příspěvek nebo stránku
	if ( ! ( get_post_type( $post_id ) == 'post' || get_post_type( $post_id ) == 'page' ) ) {
		return $html;
	}
	// vrať původní obsah pokud se nejedná se o video z YouTube
	if ( ! ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) ) {
		return $html;
	}
	$video_id = '';
	if ( strpos( $url, 'youtube.com' ) !== false ) {
		$url_parse = parse_url( $url );
		$query     = $url_parse['query'];
		parse_str( $query, $params );
		if ( isset( $params['v'] ) && $params['v'] != '' ) {
			$video_id = $params['v'];
		}
	} else {
		$url_parse = parse_url( $url );
		if ( isset( $url_parse['path'] ) && $url_parse['path'] != '' ) {
			$video_id = trim( $url_parse['path'], '/' );
		}
	}
	// vrať původní obsah pokud se nepodaří vyparsovat id videa
	if ( $video_id == '' ) {
		return $html;
	}


	// zkontroluj, zda máme video už uloženo lokálně
	$local_data = get_post_meta( $post_id, 'youtube_data', true );

	if ( is_array( $local_data ) && array_key_exists( $video_id, $local_data ) ) {
		$video = $local_data[ $video_id ];
	} else {
	    // pokud ne vytvoř request na api
		$request_youtube = 'https://www.googleapis.com/youtube/v3/videos?id=' . $video_id . '&part=snippet&key=' . 'AIzaSyCdg_qdMJPaszXe_taKHJFsrk87IJAcu9M';
		$videos          = wpj_youtube_get_data_from_api( $request_youtube, $post_id );

		if ( ! is_array( $local_data ) ) {
			$local_data = array();
		}
		$local_data[ $video_id ] = $videos[0];
		update_post_meta( $post_id, 'youtube_data', $local_data );
		$video = $videos[0];
	}
	ob_start();
	?>
    <div class="video__wrap">
        <div class="video__iframe" data-id="<?php echo $video['id']; ?>"></div>
        <h3 class="video__title"><?php echo $video['title']; ?></h3>
        <?php $img_arr = wp_get_attachment_image_src($video['img'], 'large'); ?>
        <img src="<?php echo $img_arr[0]; ?>" alt="<?php echo $video['title']; ?>">
        <button class="video__play"><?php _e( 'Přehrát', 'domain' ); ?></button>
    </div>
	<?php
	return ob_get_clean();


}


function wpj_youtube_get_data_from_api( $request_youtube, $post_id = '' ) {
	$response_youtube = wp_remote_request( $request_youtube, array(
		'timeout' => 15,
	) );
	$result_youtube   = json_decode( wp_remote_retrieve_body( $response_youtube ) );
	$items  = $result_youtube->items;
	$videos = array();
	if ( $items ) {
		foreach ( $items as $item ) {
			if ( is_object( $item->id ) && $item->id->kind == 'youtube#video' || $item->kind == 'youtube#video' ) {
		    		// tato podmínka je zde protože, item->ID může být objekt nebo string
				if ( is_object( $item->id ) ) {
					$video_id = (string) $item->id->videoId;
				} else {
					$video_id = (string) $item->id;
				}
				$video_title   = $item->snippet->title;
				$video_desc    = $item->snippet->description;
				$video_publish = $item->snippet->publishedAt;
				$url           = $item->snippet->thumbnails->high->url;
				$url_max       = str_replace( 'hqdefault', 'maxresdefault', $item->snippet->thumbnails->high->url );
                // někdy existuje obrázek ve větším roulišení
				if ( wpj_url_exists( $url_max ) ) {
					$url = $url_max;
				}
				$new_img_id = wpj_upload_image_form_url( $url, $video_title, $post_id );

				$videos[] = array(
					'id'      => $video_id,
					'title'   => $video_title,
					'desc'    => $video_desc,
					'publish' => $video_publish,
					'img'     => $new_img_id,
				);

			}
		}
	}


	return $videos;

}

function wpj_upload_image_form_url( $url, $title = '', $post_parent = '' ) {

	if (  wpj_url_exists( $url ) ) {

		$uploads      = wp_upload_dir();
		$uploads_path = $uploads['path'];

		$url_arr         = explode( '/', $url );
		$file_title_full = $url_arr[ count( $url_arr ) - 1 ];
		$file_ext        = pathinfo( $file_title_full, PATHINFO_EXTENSION );
		if ( $title != '' ) {
			$file_title_full = $title . '.' . $file_ext;
		}
		if ( $title == '' ) {
			$title = pathinfo( $file_title_full, PATHINFO_FILENAME );
		}
		$file_title_without_extension = pathinfo( $file_title_full, PATHINFO_FILENAME );

		$filename = $uploads_path . '/' . wpj_sanitize_file_name( $file_title_full );
		$c        = 1;

		while ( file_exists( $filename ) ) {
			$filename = $uploads_path . '/' . wpj_sanitize_file_name( $file_title_without_extension . '-' . $c . '.' . $file_ext );
			$c ++;
		}
		copy( $url, $filename );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );


		$filetype = wp_check_filetype( basename( $filename ), null );

		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',

		);
		if ( $post_parent != '' ) {
			$attachment['post_parent'] = $post_parent;
		}
		$attach_id = wp_insert_attachment( $attachment, $filename );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;

	}

}

function wpj_url_exists( $url, $log_error = false ) {
	$ch = curl_init();

	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER         => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING       => "",
		CURLOPT_AUTOREFERER    => true,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT        => 5,
		CURLOPT_MAXREDIRS      => 10,
	);
	curl_setopt_array( $ch, $options );
	$response = curl_exec( $ch );
	$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );


	if ( $httpCode == 200 ) {
		return true;
	} else {
		if ( $log_error ) {
			error_log( 'Chyba při nahrávání obrázku u url: ' . $url . ' chyba: ' . $httpCode, 0 );
		}

		return false;
	}
}

add_filter( 'sanitize_file_name', 'wpj_sanitize_file_name' );

function wpj_sanitize_file_name( $filename ) {
	// white space to underscores
	$filename = preg_replace( '/[\s,]+/', '_', $filename );
	// all dashes to underscores
	$filename = str_replace( '-', '_', $filename );
	// more underscores to one underscore
	$filename = preg_replace( '/_+/', '_', rtrim( $filename, '_' ) );
	// underscore to dash
	$filename = str_replace( '_', '-', $filename );
	// file name
	$file = substr( $filename, 0, strrpos( $filename, '.' ) );
	$file = remove_accents( $file );
	$file = preg_replace( '/[^a-zA-Z0-9-]/', '', $file );
	$file = trim( $file, '-' );
	// file type
	$exp = substr( $filename, strrpos( $filename, '.' ) + 1 );
	$exp = remove_accents( $exp );
	$exp = preg_replace( '/[^a-zA-Z0-9-]/', '', $exp );
	$exp = trim( $exp, '-' );

	return strtolower( $file . '.' . $exp );
}



add_filter( 'wp_insert_post_data', 'wpj_wp_insert_post_data', 5, 2 );
function wpj_wp_insert_post_data( $data, $postarr ) {
	$post_id    = $postarr['ID'];
	$local_data = get_post_meta( $post_id, 'youtube_data', true );
	if ( is_array( $local_data ) ) {
		// smazání starých obrázků od youtube videí
		foreach ( $local_data as $video ) {
			wp_delete_attachment( $video['img'], true );
		}
	}
	// smazání post meta
	delete_post_meta( $post_id, 'youtube_data' );
	return $data;
}
