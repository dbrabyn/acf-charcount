<?php
/**
 * Core counter logic and field detection.
 *
 * Hooks into ACF field rendering to output counter markup
 * for supported field types.
 *
 * @package ACF_Charcount
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ACF_CC_Counter
 *
 * Renders character counter markup below ACF fields
 * and determines which fields should display counters.
 */
class ACF_CC_Counter {

	/**
	 * Field configuration instance.
	 *
	 * @var ACF_CC_Field_Config
	 */
	private $field_config;

	/**
	 * Constructor.
	 *
	 * @param ACF_CC_Field_Config $field_config Field configuration instance.
	 */
	public function __construct( ACF_CC_Field_Config $field_config ) {
		$this->field_config = $field_config;

		add_action( 'acf/render_field/type=text', array( $this, 'render_counter' ) );
		add_action( 'acf/render_field/type=textarea', array( $this, 'render_counter' ) );
		add_action( 'acf/render_field/type=wysiwyg', array( $this, 'render_counter' ) );
	}

	/**
	 * Render the character counter element after a field.
	 *
	 * Outputs a counter for fields that have a character limit set
	 * (via ACF maxlength or [maxchars:N] in instructions), or for
	 * all fields if the "show without limit" option is enabled.
	 *
	 * @param array $field The ACF field configuration array.
	 * @return void
	 */
	public function render_counter( $field ) {
		$max_length         = $this->field_config->get_max_length( $field );
		$show_without_limit = ( '1' === get_option( 'acf_cc_show_without_limit', '0' ) );

		// Skip if no limit and option to show without limit is off.
		if ( 0 === $max_length && ! $show_without_limit ) {
			return;
		}

		$value   = isset( $field['value'] ) ? $field['value'] : '';
		$current = $this->count_characters( $value, $field['type'] );

		$data_attrs = '';
		if ( $max_length > 0 ) {
			$data_attrs = ' data-max="' . esc_attr( $max_length ) . '"';
		}

		echo '<span class="acf-cc-counter"' . $data_attrs . '>';
		echo '<span class="acf-cc-current">' . esc_html( $current ) . '</span>';
		if ( $max_length > 0 ) {
			echo ' / <span class="acf-cc-max">' . esc_html( $max_length ) . '</span>';
		}
		echo ' ' . esc_html__( 'characters', 'acf-charcount' );
		echo '</span>';
	}

	/**
	 * Count characters in a field value.
	 *
	 * Strips HTML tags for WYSIWYG fields before counting.
	 * Uses mb_strlen when available for multibyte support.
	 *
	 * @param string $value The field value.
	 * @param string $type  The ACF field type.
	 * @return int Character count.
	 */
	private function count_characters( $value, $type ) {
		if ( 'wysiwyg' === $type ) {
			$value = wp_strip_all_tags( $value );
		}

		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
