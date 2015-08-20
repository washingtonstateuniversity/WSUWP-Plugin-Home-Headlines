<?php
/*
Plugin Name: WSUWP Home Headlines
Version: 0.1.5
Plugin URI: https://web.wsu.edu/
Description: Really, to be determined. But–pull headlines and information via shortcode.
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu/
*/

class WSU_Home_Headlines {

	/**
	 * @var string Meta key for storing headline.
	 */
	public $headline_meta_key = '_wsu_home_headline';

	/**
	 * @var string Meta key for storing subtitle.
	 */
	public $subtitle_meta_key = '_wsu_home_subtitle';

	/**
	 * @var string Meta key for storing the call to action.
	 */
	public $call_to_action_meta_key = '_wsu_home_call_to_action';

	/**
	 * @var string Meta key for storing the call to action's URL.
	 */
	public $call_to_action_url_meta_key = '_wsu_home_call_to_action_url';

	/**
	 * Setup the hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_shortcode( 'home_headline', array( $this, 'display_home_headline' ) );
	}

	/**
	 * Add metaboxes for subtitle and call to action to page and post edit screens.
	 *
	 * @param string $post_type Current post type screen being displayed.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( ! in_array( $post_type, array( 'page', 'post' ) ) ) {
			return;
		}

		add_meta_box( 'wsu_home_headlines', 'Page Headlines', array( $this, 'display_headlines_metabox' ), null, 'normal', 'high' );
	}

	/**
	 * Display the metabox used to capture additional headlines for a post or page.
	 *
	 * @param WP_Post $post
	 */
	public function display_headlines_metabox( $post ) {
		$headline = get_post_meta( $post->ID, $this->headline_meta_key, true );
		$subtitle = get_post_meta( $post->ID, $this->subtitle_meta_key, true );
		$call_to_action = get_post_meta( $post->ID, $this->call_to_action_meta_key, true );
		$call_to_action_url = get_post_meta( $post->ID, $this->call_to_action_url_meta_key, true );

		wp_nonce_field( 'wsu-home-headlines-nonce', '_wsu_home_headlines_nonce' );
		?>
		<label for="wsu-home-page-headline">Headline:</label>
		<input type="text" class="widefat" id="wsu-home-page-headline" name="wsu_home_page_headline" value="<?php echo esc_attr( $headline ); ?>" />
		<p class="description">Primary headline to be used for the page.</p>

		<label for="wsu-home-subtitle">Subtitle:</label>
		<input type="text" class="widefat" id="wsu-home-subtitle" name="wsu_home_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" />
		<p class="description">Subtitle to be used on various views throughout the theme.</p>

		<label for="wsu-home-cta">Call to Action:</label>
		<input type="text" class="widefat" id="wsu-home-cta" name="wsu_home_call_to_action" value="<?php echo esc_attr( $call_to_action ); ?>" />
		<p class="description">Call to action text for use as a guide to this page.</p>

		<label for="wsu-home-cta-url">Call to Action URL:</label>
		<input type="text" class="widefat" id="wsu-home-cta-url" name="wsu_home_call_to_action_url" value="<?php echo esc_attr( $call_to_action_url ); ?>" />
	<?php
	}

	/**
	 * Save the subtitle and call to action assigned to the post.
	 *
	 * @param int     $post_id ID of the post being saved.
	 * @param WP_Post $post    Post object of the post being saved.
	 */
	public function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'page', 'post' ) ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( ! isset( $_POST['_wsu_home_headlines_nonce'] ) || false === wp_verify_nonce( $_POST['_wsu_home_headlines_nonce'], 'wsu-home-headlines-nonce' ) ) {
			return;
		}

		if ( isset( $_POST['wsu_home_call_to_action'] ) ) {
			update_post_meta( $post_id, $this->call_to_action_meta_key, wp_kses_post( $_POST['wsu_home_call_to_action'] ) );
		}

		if ( isset( $_POST['wsu_home_call_to_action_url'] ) && ! empty( trim( $_POST['wsu_home_call_to_action_url'] ) ) ) {
			update_post_meta( $post_id, $this->call_to_action_url_meta_key, esc_url_raw( $_POST['wsu_home_call_to_action_url'] ) );
		} elseif ( ! isset( $_POST['wsu_home_call_to_action_url'] ) || empty( trim( $_POST['wsu_home_call_to_action_url'] ) ) ) {
			delete_post_meta( $post_id, $this->call_to_action_url_meta_key );
		}

		if ( isset( $_POST['wsu_home_subtitle'] ) ) {
			update_post_meta( $post_id, $this->subtitle_meta_key, wp_kses_post( $_POST['wsu_home_subtitle'] ) );
		}

		if ( isset( $_POST['wsu_home_page_headline'] ) ) {
			update_post_meta( $post_id, $this->headline_meta_key, strip_tags( $_POST['wsu_home_page_headline'], '<br><span><em><strong>' ) );
		}
	}

	/**
	 * Display a content block, intended for the home page, that links through to the
	 * page it represents.
	 *
	 * @param array $atts List of attributes to apply to the shortcode.
	 *
	 * @return string HTML content to display.
	 */
	public function display_home_headline( $atts, $pre_content = '' ) {
		$default_atts = array(
			'id' => 0,
			'site_id' => 0,
			'headline' => '',
			'subtitle' => '',
			'date' => '',
			'background' => '',
			'palette' => '',
			'link' => 'page',
			'cta' => '',
			'wrapper' => 'div',
			'wrapper_class' => '',
		);
		$atts = shortcode_atts( $default_atts, $atts );

		if ( isset( $atts['site_id'] ) && 0 !== absint( $atts['site_id'] ) ) {
			switch_to_blog( $atts['site_id'] );
		}

		if ( ! isset( $atts['id'] ) || empty( absint( $atts['id'] ) ) ) {
			$post = false;
		} else {
			$post = get_post( absint( $atts['id'] ) );
		}

		if ( ! $post && empty( $atts['headline'] ) ) {
			$headline = '';
		} elseif ( '' !== $atts['headline'] ) {
			$headline = $atts['headline'];
		} else {
			$headline = $this->get_headline( $post->ID );
		}

		if ( ! $post && empty( $atts['subtitle'] ) ) {
			$subtitle = '';
		} elseif ( '' !== $atts['subtitle'] ) {
			$subtitle = $atts['subtitle'];
		} else {
			$subtitle = $this->get_subtitle( $post->ID );
		}

		if ( ! empty( $atts['background'] ) ) {
			$background_image = $atts['background'];
		} elseif ( $post && class_exists( 'MultiPostThumbnails' ) ) {
			$background_image = MultiPostThumbnails::get_post_thumbnail_url( $post->post_type, 'background-image', $post->ID, 'spine-xlarge_size' );
		} else {
			$background_image = false;
		}

		if ( ! $post && empty( $atts['palette'] ) ) {
			$palette = 'default';
		} elseif ( '' !== $atts['palette'] ) {
			$palette = $atts['palette'];
		} else {
			$palette = 'default';
		}

		if ( $background_image ) {
			$class = 'headline-has-background';
			$style = 'style="background-image: url(' . esc_url( $background_image ) .');"';
		} else {
			$class = 'wsu-home-palette-block-' . $palette;
			$style = '';
		}

		if ( $post && 'page' === $atts['link'] ) {
			$page_url = get_the_permalink( $post->ID );
		} elseif ( 'none' === $atts['link'] || ( ! $post && 'page' === $atts['link'] ) ) {
			$page_url = false;
		} else {
			$page_url = $atts['link'];
		}

		if ( ! $post && empty( $atts['date'] ) ) {
			$meta_date = '';
		} elseif ( '' !== $atts['date'] ) {
			$meta_date = esc_html( $atts['date'] );
		} else {
			$meta_date = get_the_date( 'M d', $post->ID );
		}

		if ( $page_url ) {
			$page_url = esc_url( $page_url );
			$anchor = '<a href="' . esc_url( $page_url ) . '">';
			$close_anchor = '</a>';
		} else {
			$page_url = '';
			$anchor = '';
			$close_anchor = '';
		}

		if ( ! empty( $atts['cta'] ) ) {
			$call_to_action = '<div class="home-cta">' . $anchor . sanitize_text_field( $atts['cta'] ) . $close_anchor . '</div>';
		} else {
			$call_to_action = '';
		}

		if ( ms_is_switched() ) {
			restore_current_blog();
		}

		$content = '';
		$container_id = uniqid();

		// Handle wrapper container and class.
		if ( 'a' === $atts['wrapper'] && $page_url ) {
			$content = '<a id="' . $container_id . '" class="home-link-wrap wsu-home-palette-text-' . $palette . ' ' . $atts['wrapper_class'] . '" href="' . $page_url . '">';
			$close_wrapper = '</a>';
		} elseif ( ! empty( $atts['wrapper'] ) && in_array( $atts['wrapper'], array( 'div', 'span' ) ) ) {
			$content = '<' . $atts['wrapper'] . ' id="' . $container_id . '" class="wsu-home-headline-wrapper ' .  $atts['wrapper_class'] . '">';
			$close_wrapper = '</' . $atts['wrapper'] . '>';
		} else {
			$close_wrapper = '';
		}

		$content .= '
			<div ' . $style . ' class="home-headline ' . $class . '" data-id="' . $container_id . '" data-headline="'. esc_attr( strip_tags( $headline ) ) .'" data-anchor="'. $page_url .'" data-date="'. $meta_date .'">
				<div>
					<div class="home-headline-head-wrapper">';

		$content .= apply_filters( 'wsu_home_headlines_title', '<h2>' . strip_tags( $headline, '<br><span><em><strong>' ) . '</h2>', $atts, $pre_content );
		$content .= apply_filters( 'wsu_home_headlines_after_title', '', $atts, $pre_content );
		$content .= apply_filters( 'wsu_home_headlines_sub_title', '<div class="home-subtitle">' . strip_tags( $subtitle, '<br><span><em><strong>' ) .  '</div>', $atts, $pre_content );
		$content .= apply_filters( 'wsu_home_headlines_after_sub_title', '', $atts, $pre_content );

		$content .= '</div>'; // close .home-headline-head-wrapper

		$content .= apply_filters( 'wsu_home_headlines_cta', $call_to_action, $atts, $pre_content );

		$content .= '</div></div>'; // close inner div and .home-headline

		$content .= $close_wrapper;

		return $content;
	}

	/**
	 * Retrieve the assigned headline of a page.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function get_headline( $post_id ) {
		return get_post_meta( $post_id, $this->headline_meta_key, true );
	}

	/**
	 * Retrieve the assigned subtitle of a page.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function get_subtitle( $post_id ) {
		return get_post_meta( $post_id, $this->subtitle_meta_key, true );
	}

	/**
	 * Retrieve the assigned call to action of a page.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function get_call_to_action( $post_id ) {
		return get_post_meta( $post_id, $this->call_to_action_meta_key, true );
	}

	/**
	 * Retrieve the assigned URL for the call to action of a page.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function get_call_to_action_url( $post_id ) {
		return get_post_meta( $post_id, $this->call_to_action_url_meta_key, true );
	}
}
$wsu_home_headlines = new WSU_Home_Headlines();

/**
 * Wrapper to retrieve an assigned page headline. Will fallback to the current page if
 * a post ID is not specified.
 *
 * @param int $post_id
 *
 * @return mixed
 */
function wsu_home_get_page_headline( $post_id = 0 ) {
	global $wsu_home_headlines;

	if ( is_404() ) {
		return "We're sorry. We can't find the page you're looking for.";
	}

	$post_id = absint( $post_id );

	if ( 0 === $post_id ) {
		$post_id = get_the_ID();
	}

	return $wsu_home_headlines->get_headline( $post_id );
}

/**
 * Wrapper to retrieve an assigned page subtitle. Will fallback to the current page if
 * a post ID is not specified.
 *
 * @param int $post_id
 *
 * @return mixed
 */
function wsu_home_get_page_subtitle( $post_id = 0 ) {
	global $wsu_home_headlines;

	if ( is_404() ) {
		return false;
	}

	$post_id = absint( $post_id );

	if ( 0 === $post_id ) {
		$post_id = get_the_ID();
	}

	return $wsu_home_headlines->get_subtitle( $post_id );
}

/**
 * Wrapper to retrieve an assigned page call to action. Will fallback to the current page
 * if a post ID is not specified.
 *
 * @param int $post_id
 *
 * @return mixed
 */
function wsu_home_get_page_call_to_action( $post_id = 0 ) {
	global $wsu_home_headlines;

	if ( is_404() ) {
		return false;
	}

	$post_id = absint( $post_id );

	if ( 0 === $post_id ) {
		$post_id = get_the_ID();
	}

	return $wsu_home_headlines->get_call_to_action( $post_id );
}

/**
 * Wrapper to retrieve an assigned URL for a page's call to action. Will fallback to the
 * current page if a post ID is not specified.
 *
 * @param int $post_id
 *
 * @return mixed
 */
function wsu_home_get_page_call_to_action_url( $post_id = 0 ) {
	global $wsu_home_headlines;

	if ( is_404() ) {
		return false;
	}

	$post_id = absint( $post_id );

	if ( 0 === $post_id ) {
		$post_id = get_the_ID();
	}

	return $wsu_home_headlines->get_call_to_action_url( $post_id );
}