<?php
/**
 * Settings page registration and rendering.
 *
 * Adds an options page under the Settings menu to configure
 * counter display preferences and default max character lengths.
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
 * and settings rendering. All settings are stored as a
 * single serialized array under the 'acf_cc_settings' option.
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
	 * The option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'acf_cc_settings';

	/**
	 * Default settings values.
	 *
	 * @var array
	 */
	const DEFAULTS = array(
		'max_text'       => 0,
		'max_textarea'   => 0,
		'max_wysiwyg'    => 0,
		'display_style'  => 'always',
		'counter_position' => 'below-right',
	);

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get a single setting value, falling back to the default.
	 *
	 * @param string $key Setting key.
	 * @return mixed The setting value.
	 */
	public static function get( $key ) {
		$settings = get_option( self::OPTION_NAME, self::DEFAULTS );
		$settings = wp_parse_args( $settings, self::DEFAULTS );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array Complete settings array.
	 */
	public static function get_all() {
		$settings = get_option( self::OPTION_NAME, self::DEFAULTS );
		return wp_parse_args( $settings, self::DEFAULTS );
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
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::DEFAULTS,
			)
		);

		// Default character limits section.
		add_settings_section(
			'acf_cc_defaults_section',
			__( 'Default Character Limits', 'acf-charcount' ),
			array( $this, 'render_defaults_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'acf_cc_max_text',
			__( 'Text Fields', 'acf-charcount' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'acf_cc_defaults_section',
			array(
				'key'         => 'max_text',
				'description' => __( 'Default max characters for text fields. 0 = no limit.', 'acf-charcount' ),
			)
		);

		add_settings_field(
			'acf_cc_max_textarea',
			__( 'Textarea Fields', 'acf-charcount' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'acf_cc_defaults_section',
			array(
				'key'         => 'max_textarea',
				'description' => __( 'Default max characters for textarea fields. 0 = no limit.', 'acf-charcount' ),
			)
		);

		add_settings_field(
			'acf_cc_max_wysiwyg',
			__( 'WYSIWYG Fields', 'acf-charcount' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'acf_cc_defaults_section',
			array(
				'key'         => 'max_wysiwyg',
				'description' => __( 'Default max characters for WYSIWYG fields. 0 = no limit.', 'acf-charcount' ),
			)
		);

		// Display settings section.
		add_settings_section(
			'acf_cc_display_section',
			__( 'Display Settings', 'acf-charcount' ),
			array( $this, 'render_display_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'acf_cc_display_style',
			__( 'Counter Display', 'acf-charcount' ),
			array( $this, 'render_display_style_field' ),
			self::PAGE_SLUG,
			'acf_cc_display_section'
		);

		add_settings_field(
			'acf_cc_counter_position',
			__( 'Counter Position', 'acf-charcount' ),
			array( $this, 'render_counter_position_field' ),
			self::PAGE_SLUG,
			'acf_cc_display_section'
		);
	}

	/**
	 * Sanitize the entire settings array on save.
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['max_text']     = isset( $input['max_text'] ) ? absint( $input['max_text'] ) : 0;
		$sanitized['max_textarea'] = isset( $input['max_textarea'] ) ? absint( $input['max_textarea'] ) : 0;
		$sanitized['max_wysiwyg'] = isset( $input['max_wysiwyg'] ) ? absint( $input['max_wysiwyg'] ) : 0;

		$allowed_styles = array( 'always', 'configured' );
		$sanitized['display_style'] = isset( $input['display_style'] ) && in_array( $input['display_style'], $allowed_styles, true )
			? $input['display_style']
			: 'always';

		$allowed_positions = array( 'below-right', 'below-left' );
		$sanitized['counter_position'] = isset( $input['counter_position'] ) && in_array( $input['counter_position'], $allowed_positions, true )
			? $input['counter_position']
			: 'below-right';

		return $sanitized;
	}

	/**
	 * Render the default character limits section description.
	 *
	 * @return void
	 */
	public function render_defaults_section() {
		echo '<p>' . esc_html__( 'Set default maximum character lengths per field type. These apply when no per-field limit is configured via ACF maxlength or [maxchars:N]. Set to 0 for no limit.', 'acf-charcount' ) . '</p>';
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
	 * Render a number input field.
	 *
	 * @param array $args Field arguments including 'key' and 'description'.
	 * @return void
	 */
	public function render_number_field( $args ) {
		$settings = self::get_all();
		$key      = $args['key'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : 0;
		?>
		<input
			type="number"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="0"
			step="1"
			class="small-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the counter display style radio buttons.
	 *
	 * @return void
	 */
	public function render_display_style_field() {
		$value = self::get( 'display_style' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_NAME . '[display_style]' ); ?>" value="always" <?php checked( $value, 'always' ); ?> />
				<?php esc_html_e( 'Always show counter on all supported fields', 'acf-charcount' ); ?>
			</label>
			<br />
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_NAME . '[display_style]' ); ?>" value="configured" <?php checked( $value, 'configured' ); ?> />
				<?php esc_html_e( 'Only show on fields with a character limit set (via ACF maxlength, [maxchars:N], or defaults above)', 'acf-charcount' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render the counter position radio buttons.
	 *
	 * @return void
	 */
	public function render_counter_position_field() {
		$value = self::get( 'counter_position' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_NAME . '[counter_position]' ); ?>" value="below-right" <?php checked( $value, 'below-right' ); ?> />
				<?php esc_html_e( 'Below the field, right-aligned', 'acf-charcount' ); ?>
			</label>
			<br />
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_NAME . '[counter_position]' ); ?>" value="below-left" <?php checked( $value, 'below-left' ); ?> />
				<?php esc_html_e( 'Below the field, left-aligned', 'acf-charcount' ); ?>
			</label>
		</fieldset>
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
