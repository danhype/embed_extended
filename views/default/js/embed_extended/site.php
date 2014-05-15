<?php
/**
 * Add to global site JS
 */
?>
//<script>
elgg.provide("elgg.embed_extended");

/**
 * Check if the embed js is loaded, if the textarea was loaded in a lightbox this isn't the case.
 */
elgg.embed_extended.lightbox_initialize = function() {

	try {
		elgg.require("elgg.embed");
	} catch (err) {
		// JS was not loaded so do that
		$.getScript(elgg.get_simplecache_url("js", "embed/embed"));
	}
}

/**
 * Helper function to close embedded windows opened in a lightbox
 */
elgg.embed_extended.lightbox_close = function() {
	$("#cboxLoadedContent").remove();
	$("#cboxOriginalContent").attr("id", "cboxLoadedContent").show();

	$("#cboxClose").bind("click", elgg.ui.lightbox.close); 

	return false;
}

/**
 * Inserts data attached to an embed list item in textarea
 *
 * @param {Object} event
 * @return void
 */
elgg.embed_extended.insert = function(event) {
	var textAreaId = elgg.embed_extended.textAreaId;
	var textArea = $('#' + textAreaId);

	// generalize this based on a css class attached to what should be inserted
	var content = ' ' + $(this).find(".embed-insert").parent().html() + ' ';
	var value = textArea.val();
	var result = textArea.val();

	// this is a temporary work-around for #3971
	if (content.indexOf('thumbnail.php') != -1) {
		content = content.replace('size=small', 'size=medium');
	}

	textArea.focus();

	if (!elgg.isNullOrUndefined(textArea.prop('selectionStart'))) {
		var cursorPos  = textArea.prop('selectionStart');
		var textBefore = value.substring(0, cursorPos);
		var textAfter  = value.substring(cursorPos, value.length);
		result = textBefore + content + textAfter;

	} else if (document.selection) {
		// IE compatibility
		var sel = document.selection.createRange();
		sel.text = content;
		result = textArea.val();
	}

	// See the ckeditor plugin for an example of this hook
	result = elgg.trigger_hook('embed', 'editor', {
		textAreaId: textAreaId,
		content: content,
		value: value,
		event: event
	}, result);

	if (result || result === '') {
		textArea.val(result);
	}
	
	event.preventDefault();
	event.stopPropagation();
	event.stopImmediatePropagation();

	elgg.embed_extended.lightbox_close();
};

elgg.embed_extended.move_lighbox_content = function() {
	$("#cboxLoadedContent").attr("id", "cboxOriginalContent").hide();
	$("#cboxOriginalContent").after("<div id='cboxLoadedContent'></div>");

	$("#cboxLoadingOverlay").show();
	
	$("#cboxClose").unbind(); 
	$("#cboxClose").bind("click", elgg.embed_extended.lightbox_close);
};

elgg.embed_extended.detect_lightbox = function(event) {
	if ($("#cboxLoadedContent").length) {
		elgg.embed_extended.move_lighbox_content();
		
		var href = $(this).attr("href");
		elgg.get(href, {
			success: function(data) {
				
				$("#cboxLoadedContent").html(data);

				$("#cboxLoadingOverlay").hide();

				$(".embed-item").live("click", elgg.embed_extended.insert);
				 
			}
		});	
		
	} else {
		opts = {};
		// merge opts into defaults
		opts = $.extend({}, elgg.ui.lightbox.getSettings(), opts);

		var $this = $(this),
			href = $this.prop('href') || $this.prop('src'),
			dataOpts = $this.data('colorboxOpts');
		// Q: why not use "colorbox"? A: https://github.com/jackmoore/colorbox/issues/435

		if (!$.isPlainObject(dataOpts)) {
			dataOpts = {};
		}

		// merge data- options into opts
		$.colorbox($.extend({href: href}, opts, dataOpts));
	}
	event.preventDefault();
};

/**
 * custom lightbox initialization to fix issue with embed loaded in a lightbox
 */
elgg.embed_extended.init = function() {
	$(document).on("click", ".elgg-embed-lightbox", elgg.embed_extended.detect_lightbox);

	// need to reregister
	// caches the current textarea id
	$(".embed-control").live('click', function() {
		var textAreaId = /embed-control-(\S)+/.exec($(this).attr('class'))[0];
		elgg.embed_extended.textAreaId = textAreaId.substr("embed-control-".length);
	});
}

elgg.register_hook_handler('init', 'system', elgg.embed_extended.init);
