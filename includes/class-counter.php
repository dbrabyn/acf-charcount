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

		foreach ( ACF_CC_SUPPORTED_FIELD_TYPES as $type ) {
			add_action( 'acf/render_field/type=' . $type, array( $this, 'render_counter' ) );
		}
	}

	/**
	 * Render the character counter element after a field.
	 *
	 * Outputs a counter for fields that have a character limit set
	 * (via ACF maxlength, [maxchars:N] in instructions, or plugin defaults),
	 * or for all fields if the display style is "always".
	 *
	 * @param array $field The ACF field configuration array.
	 * @return void
	 */
	public function render_counter( $field ) {
		$settings      = ACF_CC_Settings::get_all();
		$max_length    = $this->field_config->get_max_length( $field );
		$display_style = $settings['display_style'];

		// Apply plugin default max length if no per-field limit is set.
		if ( 0 === $max_length ) {
			$default_key = 'max_' . $field['type'];
			if ( isset( $settings[ $default_key ] ) && $settings[ $default_key ] > 0 ) {
				$max_length = (int) $settings[ $default_key ];
			}
		}

		// In "configured" mode, skip fields without any limit.
		if ( 0 === $max_length && 'configured' === $display_style ) {
			return;
		}

		$value   = isset( $field['value'] ) ? $field['value'] : '';
		$current = $this->count_characters( $value, $field['type'] );

		$data_attrs = '';
		if ( $max_length > 0 ) {
			$data_attrs = ' data-max="' . esc_attr( $max_length ) . '"';
		}

		// Position class is applied client-side by acf-charcount.js based on the
		// localized counterPosition setting — kept out of PHP to avoid duplication.
		echo '<span class="acf-cc-counter"' . $data_attrs . '>';
		if ( $max_length > 0 ) {
			printf(
				/* translators: 1: current character count, 2: maximum character count */
				esc_html__( '%1$s / %2$s characters', 'acf-charcount' ),
				'<span class="acf-cc-current">' . esc_html( $current ) . '</span>',
				'<span class="acf-cc-max">' . esc_html( $max_length ) . '</span>'
			);
		} else {
			printf(
				/* translators: %s: current character count */
				esc_html__( '%s characters', 'acf-charcount' ),
				'<span class="acf-cc-current">' . esc_html( $current ) . '</span>'
			);
		}
		echo '</span>';
	}

	/**
	 * Count characters in a field value.
	 *
	 * Strips HTML tags for WYSIWYG fields and decodes HTML entities so
	 * the count reflects what the user actually sees, matching the JS
	 * counter behavior. Uses mb_strlen for multibyte/Unicode-correct
	 * counting (so emoji and accented characters count as one).
	 *
	 * @param string $value The field value.
	 * @param string $type  The ACF field type.
	 * @return int Character count.
	 */
	private function count_characters( $value, $type ) {
		if ( 'wysiwyg' === $type ) {
			$value = wp_strip_all_tags( (string) $value );
		}

		$value = html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
