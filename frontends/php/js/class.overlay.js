/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Overlay object DOM node to be mounted before document body closing tag.
 *
 * @param {string} type
 * @param {string} (optional) dialogueid
 *
 * @prop {jQuery} $dialogue
 * @prop {jQuery} $backdrop
 * @prop {string} type
 * @prop {string} headerid
 */
function Overlay(type, dialogueid) {
	this.type = type;
	this.dialogueid = dialogueid || overlays_stack.getNextId();
	this.headerid = 'dashbrd-widget-head-title-' + this.dialogueid;
	this.$backdrop = jQuery('<div>', {
		'class': 'overlay-bg',
		'data-dialogueid': this.dialogueid
	});

	this.$dialogue = jQuery('<div>', {
		'class': 'overlay-dialogue modal',
		'data-dialogueid': this.dialogueid,
		'role': 'dialog',
		'aria-modal': 'true',
		'aria-labeledby': this.headerid
	});

	var $close_btn = jQuery('<button>', {
		class: 'overlay-close-btn',
		title: t('S_CLOSE')
	})
	.click(function(e) {
		overlayDialogueDestroy(this.dialogueid);
		e.preventDefault();
	}.bind(this));

	this.$dialogue.append($close_btn);

	this.$dialogue.$header = jQuery('<h4>', {id: this.headerid});
	this.$dialogue.$script = jQuery('<script>');
	this.$dialogue.$debug = jQuery('<pre>', {class: 'debug-output', name: 'zbx_debug_info'});
	this.$dialogue.$controls = jQuery('<div>', {class: 'overlay-dialogue-controls'});
	this.$dialogue.$body = jQuery('<div>', {class: 'overlay-dialogue-body'});
	this.$dialogue.$footer = jQuery('<div>', {class: 'overlay-dialogue-footer'});

	this.$dialogue.append(jQuery('<div>', {class: 'dashbrd-widget-head'}).append(this.$dialogue.$header));
	this.$dialogue.append(this.$dialogue.$body);
	this.$dialogue.append(this.$dialogue.$footer);

	var body_mutation_observer = window.MutationObserver || window.WebKitMutationObserver;
	this.body_mutation_observer = new body_mutation_observer(this.centerDialog.bind(this));

	jQuery(window).resize(function() {
		this.$dialogue.is(':visible') && this.centerDialog();
	}.bind(this));

	this.setProperties({
		content: jQuery('<div>', {'height': '68px', class: 'is-loading'})
	});
}

/**
 * Centers the $dialog.
 */
Overlay.prototype.centerDialog = function() {
	var body_scroll_height = this.$dialogue.$body[0].scrollHeight,
		body_height = this.$dialogue.$body.height();

	if (body_height != Math.floor(body_height)) {
		// The body height is often about a half pixel less than the height.
		body_height = Math.floor(body_height) + 1;
	}

	// A fix for IE and Edge to stop popup width flickering when having vertical scrollbar.
	this.$dialogue.$body.css('overflow-y', body_scroll_height > body_height ? 'scroll' : 'hidden');

	this.$dialogue.css({
		'left': Math.round((jQuery(window).width() - this.$dialogue.outerWidth()) / 2) + 'px',
		'top': this.$dialogue.hasClass('sticked-to-top')
			? ''
			: Math.round((jQuery(window).height() - this.$dialogue.outerHeight()) / 2) + 'px'
	});
};

/**
 * Determines element to place focus on and focuses it if found.
 */
Overlay.prototype.recoverFocus = function() {
	if (this.$btn_focus) {
		this.$btn_focus.focus();
		return;
	}

	if (jQuery('[autofocus=autofocus]:focusable', this.$dialogue).length) {
		jQuery('[autofocus=autofocus]:focusable', this.$dialogue).first().focus();
	}
	else if (jQuery('.overlay-dialogue-body form :focusable', this.$dialogue).length) {
		jQuery('.overlay-dialogue-body form :focusable', this.$dialogue).first().focus();
	}
	else {
		jQuery(':focusable:first', this.$dialogue).focus();
	}
};

/**
 * Binds keyboard events to contain focus within dialogue window.
 */
Overlay.prototype.containFocus = function() {
	var focusable = jQuery(':focusable', this.$dialogue);

	if (focusable.length > 1) {
		var first_focusable = focusable.filter(':first'),
			last_focusable = focusable.filter(':last');

		first_focusable
			.off('keydown')
			.on('keydown', function(e) {
				// TAB and SHIFT
				if (e.which == 9 && e.shiftKey) {
					last_focusable.focus();
					return false;
				}
			});

		last_focusable
			.off('keydown')
			.on('keydown', function(e) {
				// TAB and not SHIFT
				if (e.which == 9 && !e.shiftKey) {
					first_focusable.focus();
					return false;
				}
			});
	}
	else {
		focusable
			.off('keydown')
			.on('keydown', function(e) {
				if (e.which == 9) {
					return false;
				}
			});
	}
};

/**
 * Sets dialogue in loading sate.
 */
Overlay.prototype.setLoading = function() {
	this.$dialogue.$body.addClass('is-loading');
	this.$dialogue.$controls.find('select, button').prop('disabled', true);
	this.$btn_submit && this.$btn_submit.prop('disabled', true);
};

/**
 * Sets dialogue in idle sate.
 */
Overlay.prototype.unsetLoading = function() {
	this.$dialogue.$body.removeClass('is-loading');
	this.$btn_submit && this.$btn_submit.removeClass('is-loading').prop('disabled', false);
};

/**
 * @param {string} action
 * @param {array|object} options (optional)
 *
 * @return {jQuery.XHR}
 */
Overlay.prototype.load = function(action, options) {
	var url = new Curl('zabbix.php');
	url.setArgument('action', action);

	if (this.xhr) {
		this.xhr.abort();
	}

	this.setLoading();
	this.xhr = jQuery.ajax({
		url: url.getUrl(),
		type: 'post',
		dataType: 'json',
		data: options
	});

	this.xhr.always(function() {
		this.unsetLoading();
	}.bind(this));

	return this.xhr;
};

/**
 * Removes associated nodes from DOM.
 */
Overlay.prototype.unmount = function() {
	this.cancel_action && this.cancel_action();

	this.$backdrop.remove();
	this.$dialogue.remove();

	this.body_mutation_observer.disconnect();

	if (!jQuery('[data-dialogueid]').length) {
		jQuery('body').css('overflow', jQuery('body').data('overflow'));
		jQuery('body').removeData('overflow');
	}
};

/**
 * Appends associated nodes to document body.
 */
Overlay.prototype.mount = function() {
	if (!jQuery('[data-dialogueid]').length) {
		jQuery('body').data('overflow', jQuery('body').css('overflow'));
		jQuery('body').css('overflow', 'hidden');
	}

	this.$backdrop.appendTo(document.body);
	this.$dialogue.appendTo(document.body);

	this.body_mutation_observer.observe(this.$dialogue[0], {childList: true, subtree: true});
	this.centerDialog();
};

/**
 * @param {object} obj
 *
 * @return {jQuery}
 */
Overlay.prototype.makeButton = function(obj) {
	var $button = jQuery('<button>', {
		type: 'button',
		text: obj.title
	});

	$button.on('click', function(e) {
		if (obj.isSubmit && this.$btn_submit) {
			this.$btn_submit.blur().addClass('is-loading');
		}

		if (obj.action && obj.action(this) !== false) {
			this.cancel_action = null;

			if (!obj.keepOpen) {
				overlayDialogueDestroy(this.dialogueid);
			}
		}

		e.preventDefault();
	}.bind(this));

	if (obj.class) {
		$button.addClass(obj.class);
	}

	if (obj.enabled === false) {
		$button.prop('disabled', true);
	}

	return $button;
};

/**
 * @param {array} arr
 *
 * @return {array}
 */
Overlay.prototype.makeButtons = function(arr) {
	var buttons = [];

	this.$btn_submit = null;
	this.$btn_focus = null;

	arr.forEach(function(obj) {
		if (typeof obj.action === 'string') {
			obj.action = new Function('overlay', obj.action);
		}

		var $button = this.makeButton(obj);

		if (obj.cancel) {
			this.cancel_action = obj.action;
		}

		if (obj.isSubmit) {
			this.$btn_submit = $button;
		}

		if (obj.focused) {
			this.$btn_focus = $button;
		}

		buttons.push($button);
	}.bind(this));

	return buttons;
};

/**
 * @param {string} key
 */
Overlay.prototype.unsetProperty = function(key) {
	switch (key) {
		case 'title':
			this.$dialogue.$header.text('');
			break;

		case 'buttons':
			this.$dialogue.$footer.find('button').remove();
			break;

		case 'content':
			this.$dialogue.$body.html('');
			break;

		case 'controls':
			this.$dialogue.$controls.remove();
			break;

		case 'debug':
			this.$dialogue.$debug.remove();
			break;

		case 'script_inline':
			this.$dialogue.$script.remove();
			break;
	}
};

/**
 * Evaluates and applies properties.
 *
 * @param {object} obj
 */
Overlay.prototype.setProperties = function(obj) {
	for (var key in obj) {
		if (!obj[key]) {
			this.unsetProperty(key);
			continue;
		}

		switch (key) {
			case 'class':
				this.$dialogue.addClass(obj[key]);
				break;

			case 'title':
				this.$dialogue.$header.text(obj[key]);
				break;

			case 'buttons':
				this.unsetProperty(key);
				this.$dialogue.$footer.append(this.makeButtons(obj[key]));
				break;

			case 'content':
				this.$dialogue.$body.html(obj[key]);
				break;

			case 'controls':
				this.$dialogue.$controls.html(obj[key]);
				this.$dialogue.$body.before(this.$dialogue.$controls);
				break;

			case 'debug':
				this.$dialogue.$debug.html(jQuery(obj[key]).html());
				this.$dialogue.$footer.after(this.$dialogue.$debug);
				break;

			case 'script_inline':
				this.unsetProperty(key);
				this.$dialogue.$script.html(obj[key]);
				this.$dialogue.$footer.prepend(this.$dialogue.$script);
				break;

			case 'element':
				this.element = obj[key];
				break;
		}
	}

	// Hijack form.
	this.$btn_submit && this.$dialogue.$body.find('form').on('submit', function(e) {
		e.preventDefault();
		this.$btn_submit.trigger('click');
	}.bind(this));
};
