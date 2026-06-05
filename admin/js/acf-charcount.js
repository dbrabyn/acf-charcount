/**
 * ACF Character Count — Admin JavaScript
 *
 * Attaches live character counters to ACF text, textarea, and WYSIWYG fields.
 * Handles dynamically added fields inside repeaters, groups, and flexible content.
 *
 * @package ACF_Charcount
 */
( function( $ ) {
	'use strict';

	// Bail if ACF JS API is not available.
	if ( typeof acf === 'undefined' ) {
		return;
	}

	var config        = window.acfCharcount || {};
	var fieldTypes    = config.fieldTypes;
	if ( ! fieldTypes || ! fieldTypes.length ) {
		// No supported types localized — nothing to do.
		return;
	}
	var fieldSelector = fieldTypes.map( function( type ) {
		return '.acf-field[data-type="' + type + '"]';
	} ).join( ', ' );

	/*
	 * Map of TinyMCE editor IDs → { $field, type } for WYSIWYG fields on the
	 * page. A single global `AddEditor` listener (registered once below)
	 * looks up entries here when WordPress recreates an editor (e.g., when
	 * the editor swaps from Text mode back to Visual mode). This avoids the
	 * O(n) listener accumulation pattern of binding `AddEditor` per field.
	 */
	var wysiwygFields = {};

	if ( typeof tinymce !== 'undefined' ) {
		tinymce.on( 'AddEditor', function( e ) {
			if ( ! e.editor || ! wysiwygFields[ e.editor.id ] ) {
				return;
			}
			var entry = wysiwygFields[ e.editor.id ];
			e.editor.on( 'init', function() {
				bindTinymce( e.editor, entry.$field, entry.type );
			} );
		} );
	}

	/**
	 * Minimal sprintf-style format helper.
	 *
	 * Supports %s (sequential) and %N$s (positional) so localized format
	 * strings can reorder arguments — important for languages where the
	 * counter reads more naturally with the noun first.
	 *
	 * @param {string} format Format string with %s or %N$s placeholders.
	 * @param {Array}  args   Replacement values.
	 * @return {string} Formatted string.
	 */
	function tplFormat( format, args ) {
		var i = 0;
		return ( format || '' ).replace( /%(?:(\d+)\$)?s/g, function( _match, idx ) {
			var index = idx ? parseInt( idx, 10 ) - 1 : i++;
			return args[ index ] != null ? args[ index ] : '';
		} );
	}

	/**
	 * Count Unicode codepoints in a string.
	 *
	 * Native `String.prototype.length` returns UTF-16 code units, so emoji
	 * and astral-plane characters count as 2. Spreading into an array
	 * iterates by codepoint, matching PHP's `mb_strlen` behavior so the
	 * server-rendered count agrees with the client-side count.
	 *
	 * @param {string} str Source string.
	 * @return {number} Codepoint count.
	 */
	function codepointLength( str ) {
		if ( ! str ) {
			return 0;
		}
		// Array.from is supported everywhere ACF supports.
		return Array.from( str ).length;
	}

	/**
	 * Decode HTML entities and strip tags to get plain text.
	 *
	 * Uses a temporary DOM element to let the browser handle entity
	 * decoding (e.g. &amp; → &, &nbsp; → space) so the character
	 * count reflects what the user actually sees.
	 *
	 * @param {string} html Raw HTML string.
	 * @return {string} Plain text with entities decoded and tags stripped.
	 */
	function stripHtmlAndDecode( html ) {
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
				// Visual mode: TinyMCE's format:'text' strips HTML and decodes entities.
				var editor = tinymce.get( editorId );
				value = editor.getContent( { format: 'text' } ) || '';
			} else {
				// Text mode: read raw textarea and strip HTML + decode entities.
				value = $field.find( '.wp-editor-area' ).val() || '';
				value = stripHtmlAndDecode( value );
			}
		} else {
			var $input = $field.find( 'input[type="text"], textarea' ).first();
			value = $input.val() || '';
		}

		return codepointLength( value );
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
	 * Resolve the max length for a field from native maxlength or defaults.
	 *
	 * The server-side `acf/prepare_field` filter already strips the
	 * [maxchars:N] tag from instructions, so this function only handles
	 * the client-discoverable sources (input maxlength attribute and
	 * plugin defaults). PHP renders counters for all known fields,
	 * including dynamic repeater rows, so this branch is only hit
	 * for edge cases where PHP didn't render a counter.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @param {string} type   The field type.
	 * @return {number} Max length, or 0 if none.
	 */
	function resolveMax( $field, type ) {
		if ( 'wysiwyg' !== type ) {
			var $input = $field.find( 'input[type="text"], textarea' ).first();
			if ( $input.length && $input.attr( 'maxlength' ) ) {
				return parseInt( $input.attr( 'maxlength' ), 10 ) || 0;
			}
		}

		if ( config.defaults && config.defaults[ type ] ) {
			return parseInt( config.defaults[ type ], 10 ) || 0;
		}

		return 0;
	}

	/**
	 * Resolve the element the counter should be inserted into.
	 *
	 * Preference, so the counter sits on one line above the input and the
	 * area below the input stays clear:
	 *   1. Right of the field instruction (`.acf-label .description`).
	 *   2. Right of the field label, when there is no instruction.
	 *   3. Top of `.acf-input`, as a fallback for fields with no label block.
	 *
	 * The label/instruction targets float the counter right via CSS.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @return {jQuery} The element to insert the counter into.
	 */
	function counterTarget( $field ) {
		var $label = $field.find( '.acf-label' ).first();
		if ( ! $label.length ) {
			return $field.find( '.acf-input' ).first();
		}

		var $desc = $label.find( '.description' ).first();
		if ( $desc.length ) {
			return $desc;
		}

		var $labelEl = $label.find( 'label' ).first();
		return $labelEl.length ? $labelEl : $label;
	}

	/**
	 * Insert (or move) the counter into its resolved target.
	 *
	 * Label/instruction targets get the counter appended so it floats to the
	 * right of that line. The `.acf-input` fallback gets it prepended so it
	 * sits above the input.
	 *
	 * @param {jQuery} $target  The resolved target element.
	 * @param {jQuery} $counter The counter element.
	 * @return {void}
	 */
	function placeCounter( $target, $counter ) {
		if ( $target.hasClass( 'acf-input' ) ) {
			$target.prepend( $counter );
		} else {
			$target.append( $counter );
		}
	}

	/**
	 * Build, insert, and position the counter element for a field.
	 *
	 * The counter is placed beside the field's instruction (or label) so it
	 * reads label → counter → input and leaves the area below the input clear
	 * for other plugins. ACF fires `acf/render_field` after the input, so the
	 * PHP-rendered counter starts out inside `.acf-input`; here we move it to
	 * the resolved target. If no counter exists (edge case where ACF didn't
	 * fire `acf/render_field` for a dynamically added field), it's created
	 * from JS and inserted in the same position.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 * @return {jQuery|null} The counter element, or null if skipped.
	 */
	function ensureCounter( $field ) {
		var $target = counterTarget( $field );

		// PHP-rendered counter: move it into the resolved target.
		var $existing = $field.find( '.acf-cc-counter' ).first();
		if ( $existing.length ) {
			placeCounter( $target, $existing );
			return $existing;
		}

		var type = $field.data( 'type' );
		var max  = resolveMax( $field, type );

		// In "configured" mode, skip fields without any limit. Use a positive
		// check on 'configured' to mirror the PHP-side logic exactly.
		if ( ! max && 'configured' === config.displayStyle ) {
			return null;
		}

		var i18n        = config.i18n || {};
		var currentSpan = '<span class="acf-cc-current">0</span>';
		var content;

		if ( max > 0 ) {
			var maxSpan = '<span class="acf-cc-max">' + max + '</span>';
			content = tplFormat( i18n.formatWithMax || '%1$s / %2$s chars', [ currentSpan, maxSpan ] );
		} else {
			content = tplFormat( i18n.formatNoMax || '%s chars', [ currentSpan ] );
		}

		var html = '<span class="acf-cc-counter" aria-live="polite"';
		if ( max > 0 ) {
			html += ' data-max="' + max + '"';
		}
		html += '>' + content + '</span>';

		var $counter = $( html );
		placeCounter( $target, $counter );

		return $counter;
	}

	/**
	 * Initialize the counter for a single ACF field.
	 *
	 * Uses a data attribute guard to prevent double-initialization,
	 * which can occur when fields exist inside nested repeaters or
	 * flexible content layouts that trigger multiple append events.
	 *
	 * @param {jQuery} $field The ACF field jQuery element.
	 */
	function initField( $field ) {
		// Prevent double-initialization on dynamically added rows.
		if ( $field.attr( 'data-acf-cc-initialized' ) === 'true' ) {
			return;
		}
		$field.attr( 'data-acf-cc-initialized', 'true' );

		var type     = $field.data( 'type' );
		var $counter = ensureCounter( $field );

		if ( ! $counter ) {
			return;
		}

		// Set initial count.
		updateCounter( $field, type );

		if ( 'wysiwyg' === type ) {
			initWysiwyg( $field, type );
		} else {
			$field.find( 'input[type="text"], textarea' ).first().on( 'input keyup', function() {
				updateCounter( $field, type );
			} );
		}
	}

	/**
	 * Initialize WYSIWYG counter bindings.
	 *
	 * Registers the field with the shared `wysiwygFields` map so the
	 * single global `AddEditor` listener can wire up TinyMCE events
	 * when (or if) the editor is created — including after a Visual/Text
	 * tab swap, which destroys and recreates the TinyMCE instance.
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

		// Register for the global AddEditor handler.
		if ( editorId ) {
			wysiwygFields[ editorId ] = { $field: $field, type: type };
		}

		// Bind to TinyMCE if already initialized.
		if ( editorId && typeof tinymce !== 'undefined' && tinymce.get( editorId ) ) {
			bindTinymce( tinymce.get( editorId ), $field, type );
		}

		// Handle Visual/Text tab clicks — update the count after WP swaps editors.
		$field.find( '.wp-editor-tabs' ).on( 'click', '.wp-switch-editor', function() {
			setTimeout( function() {
				updateCounter( $field, type );
			}, 100 );
		} );
	}

	/**
	 * Bind TinyMCE editor events for live counting.
	 *
	 * Idempotent — guarded by `_acfCcBound` so we don't double-bind if
	 * `bindTinymce` is called multiple times for the same editor.
	 *
	 * @param {tinymce.Editor} editor The TinyMCE editor instance.
	 * @param {jQuery}         $field The ACF field jQuery element.
	 * @param {string}         type   The field type.
	 */
	function bindTinymce( editor, $field, type ) {
		if ( editor._acfCcBound ) {
			updateCounter( $field, type );
			return;
		}
		editor._acfCcBound = true;
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
	fieldTypes.forEach( function( type ) {
		acf.addAction( 'ready_field/type=' + type, function( field ) {
			initField( field.$el );
		} );

		acf.addAction( 'append_field/type=' + type, function( field ) {
			initField( field.$el );
		} );
	} );

	/*
	 * Generic `append` handler — catch-all for deeply nested structures.
	 *
	 * When a repeater row or flexible content layout is added, ACF fires
	 * `append` with the new container element. This handler walks the
	 * container to find all supported fields (at any nesting depth) and
	 * initializes counters. The data-acf-cc-initialized guard in
	 * initField() ensures fields already handled by the type-specific
	 * `append_field/type=X` actions above are not double-initialized.
	 */
	acf.addAction( 'append', function( $el ) {
		$el.find( fieldSelector ).each( function() {
			initField( $( this ) );
		} );
	} );

	/*
	 * Generic `remove` handler — release WYSIWYG editor registrations.
	 *
	 * Fired with the row/layout element just before ACF detaches it. Any
	 * WYSIWYG editors inside are about to be destroyed, so drop their
	 * entries from the `wysiwygFields` map to keep it from growing as rows
	 * are repeatedly added and removed.
	 */
	acf.addAction( 'remove', function( $el ) {
		$el.find( '.wp-editor-area' ).each( function() {
			delete wysiwygFields[ this.id ];
		} );
	} );

} )( jQuery );
