<?php
/**
 * Plugin Name: ACF Character Count
 * Plugin URI:  https://github.com/9wdigital/acf-charcount
 * Description: Adds live character counters to ACF text-based fields in the WordPress admin UI.
 * Version:     1.1.0
 * Author:      David Brabyn 9W
 * Author URI:  https://9wdigital.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-charcount
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package ACF_Charcount
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'ACF_CC_VERSION', '1.1.0' );
define( 'ACF_CC_PLUGIN_FILE', __FILE__ );
define( 'ACF_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACF_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACF_CC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ACF_CC_SUPPORTED_FIELD_TYPES', array( 'text', 'textarea', 'wysiwyg' ) );

/**
 * Plugin Update Checker — automatic updates from GitHub Releases.
 *
 * Compares the Version header above against the latest GitHub Release tag.
 * When a newer release exists, WordPress shows "update available" in the
 * Plugins screen and handles the update like any other plugin.
 * Only loaded in admin — no reason to check for updates on the frontend.
 */
if ( is_admin() ) {
	require_once ACF_CC_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
	YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/dbrabyn/acf-charcount/',
		__FILE__,
		'acf-charcount'
	);
}

/**
 * Activation hook — check that ACF is available.
 *
 * @return void
 */
function acf_cc_activate() {
	// Nothing to do on activation for now. Dependency check runs at runtime
	// because ACF may be activated after this plugin.
}
register_activation_hook( __FILE__, 'acf_cc_activate' );

/**
 * Deactivation hook — clean up if needed.
 *
 * @return void
 */
function acf_cc_deactivate() {
	// Nothing to clean up for now. Options are intentionally preserved
	// so settings survive a deactivate/reactivate cycle.
}
register_deactivation_hook( __FILE__, 'acf_cc_deactivate' );

/**
 * Display an admin notice when ACF is not active.
 *
 * @return void
 */
function acf_cc_missing_acf_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: Advanced Custom Fields plugin name */
				esc_html__( '%s requires Advanced Custom Fields (ACF) to be installed and activated.', 'acf-charcount' ),
				'<strong>ACF Character Count</strong>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Bootstrap the plugin after all plugins have loaded.
 *
 * Checks for the ACF dependency before loading any plugin classes.
 *
 * @return void
 */
function acf_cc_init() {
	// Check for ACF dependency (supports both Pro and Free).
	if ( ! class_exists( 'ACF' ) ) {
		add_action( 'admin_notices', 'acf_cc_missing_acf_notice' );
		return;
	}

	// Load plugin classes (settings first — other classes reference it).
	require_once ACF_CC_PLUGIN_DIR . 'includes/class-settings.php';
	require_once ACF_CC_PLUGIN_DIR . 'includes/class-field-config.php';
	require_once ACF_CC_PLUGIN_DIR . 'includes/class-counter.php';

	// Initialize classes and store for potential debugging/extensibility.
	$GLOBALS['acf_cc'] = array(
		'field_config' => new ACF_CC_Field_Config(),
		'settings'     => new ACF_CC_Settings(),
	);
	$GLOBALS['acf_cc']['counter'] = new ACF_CC_Counter( $GLOBALS['acf_cc']['field_config'] );

	// Enqueue admin assets.
	add_action( 'acf/input/admin_enqueue_scripts', 'acf_cc_enqueue_admin_assets' );

	// Load translations.
	load_plugin_textdomain( 'acf-charcount', false, dirname( ACF_CC_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'acf_cc_init' );

/**
 * Enqueue admin JavaScript and CSS on screens where ACF fields are present.
 *
 * Hooked to `acf/input/admin_enqueue_scripts` which only fires on admin
 * pages that render ACF input fields.
 *
 * @return void
 */
function acf_cc_enqueue_admin_assets() {
	wp_enqueue_style(
		'acf-charcount',
		ACF_CC_PLUGIN_URL . 'admin/css/acf-charcount.css',
		array(),
		ACF_CC_VERSION
	);

	wp_enqueue_script(
		'acf-charcount',
		ACF_CC_PLUGIN_URL . 'admin/js/acf-charcount.js',
		array( 'acf-input' ),
		ACF_CC_VERSION,
		true
	);

	// Pass plugin settings to JavaScript.
	$settings = ACF_CC_Settings::get_all();

	wp_localize_script(
		'acf-charcount',
		'acfCharcount',
		array(
			'fieldTypes'      => ACF_CC_SUPPORTED_FIELD_TYPES,
			'displayStyle'    => $settings['display_style'],
			'counterPosition' => $settings['counter_position'],
			'defaults'        => array(
				'text'     => $settings['max_text'],
				'textarea' => $settings['max_textarea'],
				'wysiwyg'  => $settings['max_wysiwyg'],
			),
			'i18n'            => array(
				'characters' => __( 'characters', 'acf-charcount' ),
			),
		)
	);
}
