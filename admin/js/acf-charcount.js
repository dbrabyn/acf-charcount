/**
 * ACF Character Count — Admin JavaScript
 *
 * Attaches live character counters to ACF text, textarea, and WYSIWYG fields.
 * Handles dynamically added fields inside repeaters, groups, and flexible content.
 *
 * @package ACF_Charcount
 */
( function() {
	'use strict';

	// Bail if ACF JS API is not available.
	if ( typeof acf === 'undefined' ) {
		return;
	}

	var config = window.acfCharcount || {};

	/**
	 * Parse [maxchars:N] from a field's instruction text in the DOM.
	 *
	 * Falls back to checking the instruction element rendered by ACF
	 * below the field label, allowing per-field max length without
	 * needing server-side field group data in JS.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @return {number} Parsed max length, or 0 if not found.
	 */
	function parseMaxcharsFromInstructions( $field ) {
		var $instructions = $field.find( '.description' ).first();
		if ( ! $instructions.length ) {
			return 0;
		}

		var text  = $instructions.text() || '';
		var match = text.match( /\[maxchars:(\d+)\]/ );
		return match ? parseInt( match[1], 10 ) : 0;
	}

	/**
	 * Get the current character count from a field's input element.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @param {string} type   The field type (text, textarea, wysiwyg).
	 * @return {number} Character count.
	 */
	function getCharCount( $field, type ) {
		var value = '';

		if ( 'wysiwyg' === type ) {
			var editorId = $field.find( '.wp-editor-area' ).attr( 'id' );
			if ( editorId && typeof tinymce !== 'undefined' && tinymce.get( editorId ) ) {
				var editor = tinymce.get( editorId );
				value = editor.getContent( { format: 'text' } ) || '';
			} else {
				value = $field.find( '.wp-editor-area' ).val() || '';
				// Strip HTML when reading raw textarea in text mode.
				var tmp = document.createElement( 'div' );
				tmp.innerHTML = value;
				value = tmp.textContent || tmp.innerText || '';
			}
		} else {
			var $input = $field.find( 'input[type="text"], textarea' ).first();
			value = $input.val() || '';
		}

		return value.length;
	}

	/**
	 * Update the counter display for a field.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @param {string} type   The field type.
	 */
	function updateCounter( $field, type ) {
		var $counter = $field.find( '.acf-cc-counter' ).first();
		if ( ! $counter.length ) {
			return;
		}

		var count = getCharCount( $field, type );
		var max   = parseInt( $counter.attr( 'data-max' ), 10 ) || 0;

		$counter.find( '.acf-cc-current' ).text( count );

		// Toggle over-limit class.
		$counter.removeClass( 'acf-cc-over' );
		if ( max > 0 && count > max ) {
			$counter.addClass( 'acf-cc-over' );
		}
	}

	/**
	 * Build and insert the counter element for a field.
	 *
	 * If the PHP-rendered counter is already present (server-side render),
	 * this is a no-op. Otherwise it creates the counter from JS — useful
	 * for dynamically added repeater/flex rows where PHP won't re-render.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @return {jQuery|null} The counter element, or null if skipped.
	 */
	function ensureCounter( $field ) {
		// Already rendered by PHP.
		var $existing = $field.find( '.acf-cc-counter' ).first();
		if ( $existing.length ) {
			return $existing;
		}

		// Determine max length: check data-max on input (ACF native maxlength)
		// or parse [maxchars:N] from instructions.
		var $input = $field.find( 'input[type="text"], textarea' ).first();
		var max    = 0;

		if ( $input.length && $input.attr( 'maxlength' ) ) {
			max = parseInt( $input.attr( 'maxlength' ), 10 ) || 0;
		}

		if ( ! max ) {
			max = parseMaxcharsFromInstructions( $field );
		}

		// If no max and setting says don't show without limit, skip.
		if ( ! max && ! config.showWithoutLimit ) {
			return null;
		}

		// Build the counter markup.
		var html = '<span class="acf-cc-counter"';
		if ( max > 0 ) {
			html += ' data-max="' + max + '"';
		}
		html += '>';
		html += '<span class="acf-cc-current">0</span>';
		if ( max > 0 ) {
			html += ' / <span class="acf-cc-max">' + max + '</span>';
		}
		html += ' ' + ( config.i18n && config.i18n.characters ? config.i18n.characters : 'characters' );
		html += '</span>';

		var $counter = acf.$( html );
		$field.find( '.acf-input' ).first().append( $counter );

		return $counter;
	}

	/**
	 * Position the counter element based on plugin settings.
	 *
	 * @param {jQuery} $field   The ACF field jQuery element.
	 * @param {jQuery} $counter The counter element.
	 */
	function positionCounter( $field, $counter ) {
		if ( 'label' === config.counterPosition ) {
			var $label = $field.find( '.acf-label label' ).first();
			if ( $label.length ) {
				$counter.appendTo( $label );
				return;
			}
		}
		// Default: counter stays in .acf-input (rendered by PHP after the field).
	}

	/**
	 * Initialize the counter for a single ACF field.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 */
	function initField( $field ) {
		var type     = $field.data( 'type' );
		var $counter = ensureCounter( $field );

		if ( ! $counter ) {
			return;
		}

		// Position counter according to settings.
		positionCounter( $field, $counter );

		// Set initial count.
		updateCounter( $field, type );

		if ( 'wysiwyg' === type ) {
			initWysiwyg( $field, type );
		} else {
			// Bind to input events for text and textarea.
			$field.find( 'input[type="text"], textarea' ).first().on( 'input keyup', function() {
				updateCounter( $field, type );
			} );
		}
	}

	/**
	 * Initialize WYSIWYG counter bindings.
	 *
	 * TinyMCE editors may not be initialized when this runs, so we
	 * watch for the editor init event as well.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @param {string} type   The field type.
	 */
	function initWysiwyg( $field, type ) {
		var $textarea = $field.find( '.wp-editor-area' );
		if ( ! $textarea.length ) {
			return;
		}

		var editorId = $textarea.attr( 'id' );

		// Bind to the raw textarea for text-mode editing.
		$textarea.on( 'input keyup', function() {
			updateCounter( $field, type );
		} );

		// Bind to TinyMCE if already initialized.
		if ( editorId && typeof tinymce !== 'undefined' && tinymce.get( editorId ) ) {
			bindTinymce( tinymce.get( editorId ), $field, type );
		}

		// Also watch for TinyMCE init (handles lazy-loaded editors).
		if ( typeof tinymce !== 'undefined' ) {
			tinymce.on( 'AddEditor', function( e ) {
				if ( e.editor && e.editor.id === editorId ) {
					e.editor.on( 'init', function() {
						bindTinymce( e.editor, $field, type );
					} );
				}
			} );
		}
	}

	/**
	 * Bind TinyMCE editor events for live counting.
	 *
	 * @param {tinymce.Editor} editor The TinyMCE editor instance.
	 * @param {jQuery}         $field The ACF field jQuery element.
	 * @param {string}         type   The field type.
	 */
	function bindTinymce( editor, $field, type ) {
		editor.on( 'keyup change SetContent', function() {
			updateCounter( $field, type );
		} );
		// Trigger initial update once the editor content is loaded.
		updateCounter( $field, type );
	}

	/*
	 * Register ACF actions for each supported field type.
	 * `ready_field/type=X` fires once for existing fields on page load.
	 * `append_field/type=X` fires when new rows are added in repeaters/flex content.
	 */
	var fieldTypes = config.fieldTypes || [ 'text', 'textarea', 'wysiwyg' ];

	fieldTypes.forEach( function( type ) {
		acf.addAction( 'ready_field/type=' + type, function( field ) {
			initField( field.$el );
		} );

		acf.addAction( 'append_field/type=' + type, function( field ) {
			initField( field.$el );
		} );
	} );

} )();
