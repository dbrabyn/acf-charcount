<?php
/**
 * Settings page registration and rendering.
 *
 * Adds an options page under the ACF menu to configure
 * counter display preferences.
 *
 * @package ACF_Charcount
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ACF_CC_Settings
 *
 * Handles the plugin settings page, option registration,
 * and settings rendering.
 */
class ACF_CC_Settings {

	/**
	 * The settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'acf-charcount-settings';

	/**
	 * The option group name for the Settings API.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'acf_cc_settings';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'ACF Character Count', 'acf-charcount' ),
			__( 'ACF Char Count', 'acf-charcount' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings with the WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'acf_cc_counter_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_counter_position' ),
				'default'           => 'below',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'acf_cc_show_without_limit',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => '0',
			)
		);

		add_settings_section(
			'acf_cc_display_section',
			__( 'Display Settings', 'acf-charcount' ),
			array( $this, 'render_display_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'acf_cc_counter_position',
			__( 'Counter Position', 'acf-charcount' ),
			array( $this, 'render_counter_position_field' ),
			self::PAGE_SLUG,
			'acf_cc_display_section'
		);

		add_settings_field(
			'acf_cc_show_without_limit',
			__( 'Fields Without Limit', 'acf-charcount' ),
			array( $this, 'render_show_without_limit_field' ),
			self::PAGE_SLUG,
			'acf_cc_display_section'
		);
	}

	/**
	 * Sanitize the counter position option.
	 *
	 * @param string $value The submitted value.
	 * @return string Sanitized value.
	 */
	public function sanitize_counter_position( $value ) {
		$allowed = array( 'label', 'below' );
		return in_array( $value, $allowed, true ) ? $value : 'below';
	}

	/**
	 * Sanitize a checkbox value to '0' or '1'.
	 *
	 * @param string $value The submitted value.
	 * @return string '0' or '1'.
	 */
	public function sanitize_checkbox( $value ) {
		return ( '1' === $value ) ? '1' : '0';
	}

	/**
	 * Render the display settings section description.
	 *
	 * @return void
	 */
	public function render_display_section() {
		echo '<p>' . esc_html__( 'Configure how character counters are displayed on ACF fields.', 'acf-charcount' ) . '</p>';
	}

	/**
	 * Render the counter position radio buttons.
	 *
	 * @return void
	 */
	public function render_counter_position_field() {
		$value = get_option( 'acf_cc_counter_position', 'below' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="acf_cc_counter_position" value="label" <?php checked( $value, 'label' ); ?> />
				<?php esc_html_e( 'Alongside the field label', 'acf-charcount' ); ?>
			</label>
			<br />
			<label>
				<input type="radio" name="acf_cc_counter_position" value="below" <?php checked( $value, 'below' ); ?> />
				<?php esc_html_e( 'Below the field value', 'acf-charcount' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render the "show without limit" checkbox.
	 *
	 * @return void
	 */
	public function render_show_without_limit_field() {
		$value = get_option( 'acf_cc_show_without_limit', '0' );
		?>
		<label>
			<input type="checkbox" name="acf_cc_show_without_limit" value="1" <?php checked( $value, '1' ); ?> />
			<?php esc_html_e( 'Display character count for fields that have no character limit set', 'acf-charcount' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the settings page HTML.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ACF Character Count Settings', 'acf-charcount' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
