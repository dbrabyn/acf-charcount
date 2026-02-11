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
	var i18n   = config.i18n || {};

	/**
	 * Strip HTML tags from a string.
	 *
	 * @param {string} html The HTML string.
	 * @return {string} Plain text.
	 */
	function stripTags( html ) {
		var tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || '';
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
				// Use text content from TinyMCE, stripping HTML.
				value = editor.getContent( { format: 'text' } ) || '';
			} else {
				// Fallback: read from the textarea directly (text mode).
				value = $field.find( '.wp-editor-area' ).val() || '';
				value = stripTags( value );
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
	 * @param {jQuery} $field  The ACF field jQuery element.
	 * @param {string} type    The field type.
	 */
	function updateCounter( $field, type ) {
		var $counter = $field.find( '.acf-cc-counter' ).first();
		if ( ! $counter.length ) {
			return;
		}

		var count = getCharCount( $field, type );
		var max   = parseInt( $counter.attr( 'data-max' ), 10 ) || 0;

		$counter.find( '.acf-cc-count' ).text( count );

		// Update warning states.
		$counter.removeClass( 'acf-cc-warning acf-cc-over' );
		if ( max > 0 ) {
			if ( count > max ) {
				$counter.addClass( 'acf-cc-over' );
			} else if ( count >= Math.floor( max * 0.75 ) ) {
				$counter.addClass( 'acf-cc-warning' );
			}
		}
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
		var $counter = $field.find( '.acf-cc-counter' ).first();

		if ( ! $counter.length ) {
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
