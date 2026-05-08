<?php
/**
 * Uninstall handler for ACF Character Count.
 *
 * Runs only when the plugin is deleted from the WordPress Plugins screen
 * (not on deactivate). Removes the single options row this plugin owns
 * so a clean uninstall leaves no orphan data behind.
 *
 * @package ACF_Charcount
 */

// Bail if WordPress did not call this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'acf_cc_settings' );

// On multisite, also clean up the per-site option on every site.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'acf_cc_settings' );
		restore_current_blog();
	}
}
