<?php
/*
Plugin Name: Custom Emoji Replacer
Plugin URI: https://david.garden/plugins
Description: Replace emojis with custom images in your site content.
Version: 1.0
Author: wolfpaw
Author URI: https://david.garden/
License: GPLv3 or later
Text Domain: custom-emoji-replacer
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CER_VERSION', '1.0' );

/**
 * Class CustomEmojiReplacer
 * Handles the functionality for the Custom Emoji Replacer plugin.
 */
class CustomEmojiReplacer {
	/**
	 * CustomEmojiReplacer constructor.
	 * Initializes the plugin by setting up the necessary hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'create_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'the_content', array( $this, 'replace_emojis' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'custom-emoji-replacer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Adds the settings page to the WordPress admin menu.
	 */
	public function create_settings_page() {
		add_options_page(
			__( 'Emoji Replacer Settings', 'custom-emoji-replacer' ),
			__( 'Emoji Replacer', 'custom-emoji-replacer' ),
			'manage_options',
			'custom-emoji-replacer',
			array( $this, 'settings_page_html' )
		);
	}

	/**
	 * Outputs the HTML for the settings page.
	 */
	public function settings_page_html() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Emoji Replacer Settings', 'custom-emoji-replacer' ); ?></h1>
			<p><?php esc_html_e( 'Choose an emoji that you will use on your site. Then choose an image that will replace that emoji.', 'custom-emoji-replacer' ); ?></p>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'emoji_replacer_settings' );
				do_settings_sections( 'custom-emoji-replacer' );
				wp_nonce_field( 'emoji_replacer_nonce_action', 'emoji_replacer_nonce' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the plugin settings.
	 */
	public function register_settings() {
		register_setting( 'emoji_replacer_settings', 'emoji_replacer_data', array( $this, 'sanitize_options' ) );

		add_settings_section(
			'emoji_replacer_section',
			__( 'Emoji Replacement Settings', 'custom-emoji-replacer' ),
			null,
			'custom-emoji-replacer'
		);

		add_settings_field(
			'emoji_replacer_field',
			__( 'Emoji Replacements', 'custom-emoji-replacer' ),
			array( $this, 'emoji_replacer_field_html' ),
			'custom-emoji-replacer',
			'emoji_replacer_section'
		);
	}

	/**
	 * Sanitizes the options before saving them to the database.
	 *
	 * @param array $options The options to sanitize.
	 * @return array The sanitized options.
	 */
	public function sanitize_options( $options ) {
		if ( ! isset( $_POST['emoji_replacer_nonce'] ) || ! wp_verify_nonce( $_POST['emoji_replacer_nonce'], 'emoji_replacer_nonce_action' ) ) {
			return $options;
		}

		$sanitized_options = array();

		foreach ( $options as $index => $item ) {
			if ( ! empty( $item['emoji'] ) ) {
				$sanitized_options[] = array(
					'emoji'     => sanitize_text_field( $item['emoji'] ),
					'image_url' => esc_url_raw( $item['image_url'] ),
				);
			}
		}

		return $sanitized_options;
	}


	/**
	 * Outputs the HTML for the emoji replacement fields.
	 */
	public function emoji_replacer_field_html() {
		$data = get_option( 'emoji_replacer_data', array() );
		?>
		<div id="emoji-replacer-container">
			<?php foreach ( $data as $index => $item ) : ?>
				<div class="emoji-replacer-item">
					<input type="text" name="emoji_replacer_data[<?php echo esc_attr( $index ); ?>][emoji]" value="<?php echo esc_attr( $item['emoji'] ); ?>" placeholder="<?php esc_attr_e( 'Emoji', 'custom-emoji-replacer' ); ?>">
					<input type="hidden" class="emoji-replacer-image-url" name="emoji_replacer_data[<?php echo esc_attr( $index ); ?>][image_url]" value="<?php echo esc_attr( $item['image_url'] ); ?>">
					<button type="button" class="button select-image"><?php esc_html_e( 'Select Image', 'custom-emoji-replacer' ); ?></button>
					<button type="button" class="button remove-emoji"><?php esc_html_e( 'Delete', 'custom-emoji-replacer' ); ?></button>
					<?php if ( ! empty( $item['image_url'] ) ) : ?>
						<img src="<?php echo esc_url( $item['image_url'] ); ?>" alt="">
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" id="add-emoji-replacer" class="button"><?php esc_html_e( 'Add Emoji Replacement', 'custom-emoji-replacer' ); ?></button>
		<?php
	}

	/**
	 * Replaces emojis with the corresponding images in the post content.
	 *
	 * @param string $content The post content.
	 * @return string The content with emojis replaced by images.
	 */
	public function replace_emojis( $content ) {
		$data = get_option( 'emoji_replacer_data', array() );
		foreach ( $data as $item ) {
			if ( ! empty( $item['emoji'] ) && ! empty( $item['image_url'] ) ) {
				$emoji     = esc_html( $item['emoji'] );
				$image_url = esc_url( $item['image_url'] );
				$content   = str_replace( $emoji, '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $emoji ) . '" style="max-width: 1.25em; vertical-align: text-bottom;">', $content );
			}
		}
		return $content;
	}

	/**
	 * Enqueues the necessary admin scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_custom-emoji-replacer' !== $hook ) {
			return;
		}

		// Allows the media library to load
		wp_enqueue_media();

		wp_enqueue_script( 'emoji-replacer-admin-js', plugins_url( 'admin.js', __FILE__ ), array( 'jquery' ), CER_VERSION, true );
		wp_enqueue_style( 'emoji-replacer-admin-css', plugins_url( 'admin.css', __FILE__ ), '', CER_VERSION );

		// Allows script to be localized
		wp_localize_script(
			'emoji-replacer-admin-js',
			'emojiReplacer',
			array(
				'emojiPlaceholder'  => __( 'Emoji', 'custom-emoji-replacer' ),
				'selectImageButton' => __( 'Select Image', 'custom-emoji-replacer' ),
				'mediaTitle'        => __( 'Select or Upload an Image', 'custom-emoji-replacer' ),
				'mediaButton'       => __( 'Use this image', 'custom-emoji-replacer' ),
				'deleteButton'      => __( 'Delete', 'custom-emoji-replacer' ),
			)
		);
	}
}

// Initialize the plugin
new CustomEmojiReplacer();
