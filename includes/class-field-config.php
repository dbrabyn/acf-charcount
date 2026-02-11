<?php
/**
 * Per-field max length configuration.
 *
 * Determines the character limit for a given ACF field by checking
 * the field's native maxlength setting and the [maxchars:N] shortcode
 * in field instructions.
 *
 * @package ACF_Charcount
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ACF_CC_Field_Config
 *
 * Resolves the effective max character length for an ACF field.
 */
class ACF_CC_Field_Config {

	/**
	 * Get the max character length for a field.
	 *
	 * Priority:
	 * 1. [maxchars:N] tag in the field's instructions
	 * 2. ACF's native maxlength setting (text/textarea only)
	 * 3. 0 (no limit)
	 *
	 * @param array $field The ACF field configuration array.
	 * @return int The max character length, or 0 if none is set.
	 */
	public function get_max_length( $field ) {
		// Check for [maxchars:N] in field instructions.
		$instructions_max = $this->parse_maxchars_tag( $field );
		if ( $instructions_max > 0 ) {
			return $instructions_max;
		}

		// Fall back to ACF's native maxlength (text and textarea fields).
		if ( ! empty( $field['maxlength'] ) ) {
			return absint( $field['maxlength'] );
		}

		return 0;
	}

	/**
	 * Parse a [maxchars:N] tag from field instructions.
	 *
	 * Allows editors to set a character limit on any field type
	 * (including WYSIWYG) by adding [maxchars:280] to the
	 * field's instructions text.
	 *
	 * @param array $field The ACF field configuration array.
	 * @return int The parsed max length, or 0 if not found.
	 */
	private function parse_maxchars_tag( $field ) {
		if ( empty( $field['instructions'] ) ) {
			return 0;
		}

		if ( preg_match( '/\[maxchars:(\d+)\]/', $field['instructions'], $matches ) ) {
			return absint( $matches[1] );
		}

		return 0;
	}
}
