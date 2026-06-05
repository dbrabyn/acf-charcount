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
 * Renders character counter markup for ACF fields and determines which
 * fields should display counters. ACF fires `acf/render_field` after the
 * input, so the counter is emitted at the end of `.acf-input`; the admin
 * JS then repositions it to the top of `.acf-input`, above the input.
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
	 * Render the character counter element for a field.
	 *
	 * Emitted at the end of `.acf-input` (ACF fires this hook after the
	 * input); the admin JS repositions it above the input on init.
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

		// Emitted inside .acf-input; acf-charcount.js moves it beside the field
		// label/instruction on init (ACF has no hook that fires within .acf-label).
		echo '<span class="acf-cc-counter" aria-live="polite"' . $data_attrs . '>';
		if ( $max_length > 0 ) {
			printf(
				/* translators: 1: current character count, 2: maximum character count */
				esc_html__( '%1$s / %2$s chars', 'acf-charcount' ),
				'<span class="acf-cc-current">' . esc_html( $current ) . '</span>',
				'<span class="acf-cc-max">' . esc_html( $max_length ) . '</span>'
			);
		} else {
			printf(
				/* translators: %s: current character count */
				esc_html__( '%s chars', 'acf-charcount' ),
				'<span class="acf-cc-current">' . esc_html( $current ) . '</span>'
			);
		}
		echo '</span>';
	}

	/**
	 * Count characters in a field value.
	 *
	 * For WYSIWYG fields, strips HTML tags and decodes the resulting HTML
	 * entities so the count reflects the visible text — matching the JS
	 * counter, which reads TinyMCE's decoded plain text. For text and
	 * textarea fields the stored value already IS the visible text, so it
	 * is counted as-is (no entity decoding) to stay consistent with the JS
	 * counter, which counts the raw input value. Uses mb_strlen for
	 * multibyte/Unicode-correct counting (so emoji and accented characters
	 * count as one).
	 *
	 * @param string $value The field value.
	 * @param string $type  The ACF field type.
	 * @return int Character count.
	 */
	private function count_characters( $value, $type ) {
		$value = (string) $value;

		if ( 'wysiwyg' === $type ) {
			$value = wp_strip_all_tags( $value );
			$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
