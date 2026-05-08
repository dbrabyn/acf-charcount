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
 *
 * Hooks `acf/prepare_field` to parse the [maxchars:N] tag once per
 * render, stash the parsed value on the field array, and strip the
 * tag from the visible instructions text so editors don't see it.
 */
class ACF_CC_Field_Config {

	/**
	 * Regex matching the [maxchars:N] tag in instructions text.
	 *
	 * @var string
	 */
	const MAXCHARS_TAG_REGEX = '/\s*\[maxchars:(\d+)\]\s*/';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		foreach ( ACF_CC_SUPPORTED_FIELD_TYPES as $type ) {
			add_filter( 'acf/prepare_field/type=' . $type, array( $this, 'prepare_field' ) );
		}
	}

	/**
	 * Parse the [maxchars:N] tag from instructions and strip it for display.
	 *
	 * Runs before `acf/render_field`, so the tag is removed from the
	 * description rendered to editors. The parsed value is stashed
	 * on the field array under `_acf_cc_max` for later retrieval.
	 *
	 * @param array $field The ACF field configuration array.
	 * @return array The modified field array.
	 */
	public function prepare_field( $field ) {
		if ( empty( $field['instructions'] ) ) {
			return $field;
		}

		if ( preg_match( self::MAXCHARS_TAG_REGEX, $field['instructions'], $matches ) ) {
			$field['_acf_cc_max']   = absint( $matches[1] );
			$field['instructions']  = trim( preg_replace( self::MAXCHARS_TAG_REGEX, ' ', $field['instructions'] ) );
		}

		return $field;
	}

	/**
	 * Get the max character length for a field.
	 *
	 * Priority:
	 * 1. [maxchars:N] tag parsed by prepare_field (`_acf_cc_max`)
	 * 2. ACF's native maxlength setting (text and textarea fields)
	 * 3. 0 (no limit)
	 *
	 * @param array $field The ACF field configuration array.
	 * @return int The max character length, or 0 if none is set.
	 */
	public function get_max_length( $field ) {
		if ( ! empty( $field['_acf_cc_max'] ) ) {
			return absint( $field['_acf_cc_max'] );
		}

		if ( ! empty( $field['maxlength'] ) ) {
			return absint( $field['maxlength'] );
		}

		return 0;
	}
}
