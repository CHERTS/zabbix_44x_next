/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


(function($) {
	"use strict";

	var ZBX_WIDGET_VIEW_MODE_NORMAL = 0,
		ZBX_WIDGET_VIEW_MODE_HIDDEN_HEADER = 1;

	function makeWidgetDiv($obj, data, widget) {
		var iterator_classes = {
				'root': 'dashbrd-grid-iterator',
				'container': 'dashbrd-grid-iterator-container',
				'head': 'dashbrd-grid-iterator-head',
				'content': 'dashbrd-grid-iterator-content',
				'focus': 'dashbrd-grid-iterator-focus',
				'actions': 'dashbrd-grid-iterator-actions',
				'mask': 'dashbrd-grid-iterator-mask',
				'hidden_header': 'dashbrd-grid-iterator-hidden-header'
			},
			widget_classes = {
				'root': 'dashbrd-grid-widget',
				'container': 'dashbrd-grid-widget-container',
				'head': 'dashbrd-grid-widget-head',
				'content': 'dashbrd-grid-widget-content',
				'focus': 'dashbrd-grid-widget-focus',
				'actions': 'dashbrd-grid-widget-actions',
				'mask': 'dashbrd-grid-widget-mask',
				'hidden_header': 'dashbrd-grid-widget-hidden-header'
			},
			classes = widget['iterator'] ? iterator_classes : widget_classes;

		widget['content_header'] = $('<div>', {'class': classes['head']})
			.append($('<h4>').text((widget['header'] !== '')
				? widget['header']
				: data['widget_defaults'][widget['type']]['header']
			));

		if (!widget['parent']) {
			// Do not add action buttons for child widgets of iterators.
			widget['content_header']
				.append(widget['iterator']
					? $('<div>', {'class': 'dashbrd-grid-iterator-pager'}).append(
						$('<button>', {
							'type': 'button',
							'class': 'btn-iterator-page-previous',
							'title': t('Previous page')
						}).on('click', function() {
							if (widget['page'] > 1) {
								widget['page']--;
								updateWidgetContent($obj, data, widget);
							}
						}),
						$('<span>', {'class': 'dashbrd-grid-iterator-pager-info'}),
						$('<button>', {
							'type': 'button',
							'class': 'btn-iterator-page-next',
							'title': t('Next page')
						}).on('click', function() {
							if (widget['page'] < widget['page_count']) {
								widget['page']++;
								updateWidgetContent($obj, data, widget);
							}
						})
					)
					: ''
				)
				.append($('<ul>', {'class': classes['actions']})
					.append((data['options']['editable'] && !data['options']['kioskmode'])
						? $('<li>').append(
							$('<button>', {
								'type': 'button',
								'class': 'btn-widget-edit',
								'title': t('Edit')
							}).on('click', function() {
								if (!methods.isEditMode.call($obj)) {
									showEditMode();
								}
								doAction('beforeConfigLoad', $obj, data, widget);
								methods.editWidget.call($obj, widget, this);
							})
						)
						: ''
					)
					.append(
						$('<li>').append(
							$('<button>', {
								'type': 'button',
								'class': 'btn-widget-action',
								'title': t('Adjust widget refresh interval'),
								'data-menu-popup': JSON.stringify({
									'type': 'refresh',
									'data': {
										'widgetName': widget['widgetid'],
										'currentRate': widget['rf_rate'],
										'multiplier': '0'
									}
								}),
								'attr': {
									'aria-haspopup': true
								}
							})
						)
					)
					.append((data['options']['editable'] && !data['options']['kioskmode'])
						? $('<li>').hide().append(
							$('<button>', {
								'type': 'button',
								'class': 'btn-widget-delete',
								'title': t('Delete')
							}).on('click', function() {
								methods.deleteWidget.call($obj, widget);
							})
						)
						: ''
					)
				);
		}

		widget['content_body'] = $('<div>', {'class': classes['content']})
			.toggleClass('no-padding', !widget['iterator'] && !widget['configuration']['padding']);

		widget['container'] = $('<div>', {'class': classes['container']})
			.append(widget['content_header'])
			.append(widget['content_body']);

		if (widget['iterator']) {
			widget['container']
				.append($('<div>', {'class': 'dashbrd-grid-iterator-too-small'})
					.append($('<div>').html(t('Widget is too small for the specified number of columns and rows.')))
				);
		}
		else {
			widget['content_script'] = $('<div>');
			widget['container'].append(widget['content_script']);
		}

		var $div = $('<div>', {'class': classes['root']})
				.toggleClass(classes['hidden_header'], widget['view_mode'] == ZBX_WIDGET_VIEW_MODE_HIDDEN_HEADER)
				.toggleClass('new-widget', widget['new_widget']);

		if (!widget['parent']) {
			$div.css({
				'min-height': data['options']['widget-height'] + 'px',
				'min-width': data['options']['widget-width'] + '%'
			});
		}

		// Used for disabling widget interactivity in edit mode while resizing.
		widget['mask'] = $('<div>', {'class': classes['mask']});

		$div.append(widget['container'], widget['mask']);

		widget['content_header']
			.on('focusin', function() {
				enterWidget($obj, data, widget);
			})
			.on('focusout', function(event) {
				if (!widget['content_header'].has(event.relatedTarget).length) {
					leaveWidget($obj, data, widget);
				}
			})
			.on('focusin focusout', function() {
				// Skip mouse events caused by animations which were caused by focus change.
				data['options']['mousemove_waiting'] = true;
			});

		$div
			// "Mouseenter" is required, since "mousemove" may not always bubble.
			.on('mouseenter mousemove', function() {
				enterWidget($obj, data, widget);

				delete data['options']['mousemove_waiting'];
			})
			.on('mouseleave', function() {
				if (!data['options']['mousemove_waiting']) {
					leaveWidget($obj, data, widget);
				}
			});

		return $div;
	}

	/**
	 * Find out if widgets should react on mouse and focus events.
	 *
	 * @param {object} $obj  Dashboard container jQuery object.
	 * @param {object} data  Dashboard data and options object.
	 *
	 * @returns {boolean}
	 */
	function isDashboardFrozen($obj, data) {
		// Edit widget dialogue active?
		if (data['options']['config_dialogue_active']) {
			return true;
		}

		var result = false;
		data['widgets'].forEach(function(widget) {
			// Widget popup open (refresh rate)?
			if (widget['content_header'].find('[data-expanded="true"]').length > 0
					// Widget being dragged or resized in dashboard edit mode?
					|| widget['div'].hasClass('ui-draggable-dragging')
					|| widget['div'].hasClass('ui-resizable-resizing')) {
				result = true;
			}
		});

		return result;
	}

	/**
	 * Focus specified widget or iterator and blur all other widgets.
	 * If child widget of iterator is specified, blur all other child widgets of iterator.
	 * This top-level function should be called by mouse and focus event handlers.
	 *
	 * @param {object} $obj    Dashboard container jQuery object.
	 * @param {object} data    Dashboard data and options object.
	 * @param {object} widget  Dashboard widget object.
	 */
	function enterWidget($obj, data, widget) {
		var focus_class = widget['iterator'] ? 'dashbrd-grid-iterator-focus' : 'dashbrd-grid-widget-focus';

		if (widget['div'].hasClass(focus_class)) {
			return;
		}

		if (isDashboardFrozen($obj, data)) {
			return;
		}

		if (widget['parent']) {
			doLeaveWidgetsOfIteratorExcept(widget['parent'], widget);
			doEnterWidgetOfIterator(widget);
		}
		else {
			doLeaveWidgetsExcept($obj, data, widget);
			doEnterWidget($obj, data, widget);
		}

		slideKiosk($obj, data);
	}

	/**
	 * Blur specified widget or iterator. If iterator is specified, blur it's focused child widget as well.
	 * This top-level function should be called by mouse and focus event handlers.
	 *
	 * @param {object} $obj    Dashboard container jQuery object.
	 * @param {object} data    Dashboard data and options object.
	 * @param {object} widget  Dashboard widget object.
	 */
	function leaveWidget($obj, data, widget) {
		var focus_class = widget['iterator'] ? 'dashbrd-grid-iterator-focus' : 'dashbrd-grid-widget-focus';

		if (!widget['div'].hasClass(focus_class)) {
			return;
		}

		if (isDashboardFrozen($obj, data)) {
			return;
		}

		doLeaveWidget($obj, data, widget);

		slideKiosk($obj, data);
	}

	/**
	 * Focus specified top-level widget or iterator. If iterator is specified, focus it's hovered child widget as well.
	 *
	 * @param {object} $obj    Dashboard container jQuery object.
	 * @param {object} data    Dashboard data and options object.
	 * @param {object} widget  Dashboard widget object.
	 */
	function doEnterWidget($obj, data, widget) {
		widget['div'].addClass(widget['iterator'] ? 'dashbrd-grid-iterator-focus' : 'dashbrd-grid-widget-focus');

		if (widget['iterator']) {
			var child_hovered = null;
			widget['children'].forEach(function(child) {
				if (child['div'].is(':hover')) {
					child_hovered = child;
				}
			});

			if (child_hovered !== null) {
				doEnterWidgetOfIterator(child_hovered);
			}
		}
	}

	/**
	 * Focus specified child widget of iterator.
	 *
	 * @param {object} widget  Dashboard widget object.
	 */
	function doEnterWidgetOfIterator(widget) {
		widget['div'].addClass('dashbrd-grid-widget-focus');

		if (widget['parent']['div'].hasClass('dashbrd-grid-iterator-hidden-header')) {
			widget['parent']['div'].toggleClass('iterator-double-header', widget['div'].position().top == 0);
		}
	}

	/**
	 * Blur all top-level widgets and iterators, except the specified one.
	 *
	 * @param {object} $obj           Dashboard container jQuery object.
	 * @param {object} data           Dashboard data and options object.
	 * @param {object} except_widget  Dashboard widget object.
	 */
	function doLeaveWidgetsExcept($obj, data, except_widget) {
		data['widgets'].forEach(function(widget) {
			if (except_widget !== undefined && widget.uniqueid === except_widget.uniqueid) {
				return;
			}

			doLeaveWidget($obj, data, widget);
		});
	}

	/**
	 * Blur specified top-level widget or iterator. If iterator is specified, blur it's focused child widget as well.
	 *
	 * @param {object} $obj           Dashboard container jQuery object.
	 * @param {object} data           Dashboard data and options object.
	 * @param {object} except_widget  Dashboard widget object.
	 */
	function doLeaveWidget($obj, data, widget) {
		if (widget['content_header'].has(document.activeElement).length) {
			document.activeElement.blur();
		}

		if (widget['iterator']) {
			doLeaveWidgetsOfIteratorExcept(widget);
			widget['div'].removeClass('iterator-double-header');
		}

		widget['div'].removeClass(widget['iterator'] ? 'dashbrd-grid-iterator-focus' : 'dashbrd-grid-widget-focus');
	}

	/**
	 * Blur all child widgets of iterator, except the specified one.
	 *
	 * @param {object} $obj           Dashboard container jQuery object.
	 * @param {object} data           Dashboard data and options object.
	 * @param {object} except_widget  Dashboard widget object.
	 */
	function doLeaveWidgetsOfIteratorExcept(iterator, except_child) {
		iterator['children'].forEach(function(child) {
			if (except_child !== undefined && child.uniqueid === except_child.uniqueid) {
				return;
			}

			child['div'].removeClass('dashbrd-grid-widget-focus');
		});
	}

	/**
	 * Update dashboard sliding effect if in kiosk mode.
	 */
	function slideKiosk($obj, data) {
		var iterator_classes = {
				'focus': 'dashbrd-grid-iterator-focus',
				'hidden_header': 'dashbrd-grid-iterator-hidden-header'
			},
			widget_classes = {
				'focus': 'dashbrd-grid-widget-focus',
				'hidden_header': 'dashbrd-grid-widget-hidden-header'
			};

		// Calculate the dashboard offset (0, 1 or 2 lines) based on focused widget.

		var slide_lines = 0;

		for (var index = 0; index < data['widgets'].length; index++) {
			var widget = data['widgets'][index],
				classes = widget['iterator'] ? iterator_classes : widget_classes;

			if (!widget['div'].hasClass(classes['focus'])) {
				continue;
			}

			// Focused widget not on the first row of dashboard?
			if (widget['div'].position().top != 0) {
				break;
			}

			if (widget['iterator']) {
				slide_lines = widget['div'].hasClass('iterator-double-header') ? 2 : 1;
			}
			else if (widget['div'].hasClass(classes['hidden_header'])) {
				slide_lines = 1;
			}

			break;
		}

		// Apply the calculated dashboard offset (0, 1 or 2 lines) slowly.

		var $main = $obj.closest('main.layout-kioskmode');
		if (!$main.length) {
			return;
		}

		if (typeof data['options']['kiosk_slide_timeout'] !== 'undefined') {
			clearTimeout(data['options']['kiosk_slide_timeout'])
			delete data['options']['kiosk_slide_timeout'];
		}

		var slide_lines_current = 0;
		for (var i = 2; i > 0; i--) {
			if ($main.hasClass('kiosk-slide-lines-' + i)) {
				slide_lines_current = i;
				break;
			}
		}

		if (slide_lines > slide_lines_current) {
			if (slide_lines_current > 0) {
				$main.removeClass('kiosk-slide-lines-' + slide_lines_current);
			}
			$main.addClass('kiosk-slide-lines-' + slide_lines);
		}
		else if (slide_lines < slide_lines_current) {
			data['options']['kiosk_slide_timeout'] = setTimeout(function() {
				$main.removeClass('kiosk-slide-lines-' + slide_lines_current);
				if (slide_lines > 0) {
					$main.addClass('kiosk-slide-lines-' + slide_lines);
				}
				delete data['options']['kiosk_slide_timeout'];
			}, 2000);
		}
	}

	function setWidgetViewMode(widget, view_mode) {
		if (widget['view_mode'] == view_mode) {
			return;
		}

		widget['view_mode'] = view_mode;

		var hidden_header_class = widget['iterator']
				? 'dashbrd-grid-iterator-hidden-header'
				: 'dashbrd-grid-widget-hidden-header';

		if (widget['iterator']) {
			if (view_mode == ZBX_WIDGET_VIEW_MODE_NORMAL) {
				widget['div'].removeClass('iterator-double-header');
			}

			widget['children'].forEach(function(child) {
				setWidgetViewMode(child, view_mode);
			});
		}

		widget['div'].toggleClass(hidden_header_class, view_mode == ZBX_WIDGET_VIEW_MODE_HIDDEN_HEADER);
	}

	function updateIteratorPager(iterator) {
		$('.dashbrd-grid-iterator-pager-info', iterator['content_header'])
			.text(iterator['page'] + ' / ' + iterator['page_count']);

		iterator['content_header'].addClass('pager-visible');

		var too_narrow = iterator['content_header'].width() <
				$('.dashbrd-grid-iterator-pager', iterator['content_header']).outerWidth(true)
					+ $('.dashbrd-grid-iterator-actions', iterator['content_header']).outerWidth(true),
			pager_visible = iterator['page_count'] > 1 && !too_narrow && !getIteratorTooSmallState(iterator);

		iterator['content_header'].toggleClass('pager-visible', pager_visible);
	}

	function addWidgetInfoButtons($content_header, buttons) {
		// Note: this function is used only for widgets and not iterators.

		var $widget_actions = $('.dashbrd-grid-widget-actions', $content_header);

		buttons.each(function(button) {
			$widget_actions.prepend(
				$('<li>', {'class': 'widget-info-button'})
					.append(
						$('<button>', {
							'type': 'button',
							'class': button.icon,
							'data-hintbox': 1,
							'data-hintbox-static': 1
						})
					)
					.append(
						$('<div>', {
							'class': 'hint-box',
							'html': button.hint
						}).hide()
					)
				);
		});
	}

	function removeWidgetInfoButtons($content_header) {
		// Note: this function is used only for widgets and not iterators.

		$('.dashbrd-grid-widget-actions', $content_header).find('.widget-info-button').remove();
	}

	function setWidgetPadding($obj, data, widget, padding) {
		// Note: this function is used only for widgets and not iterators.

		if (!widget['iterator'] && widget['configuration']['padding'] !== padding) {
			widget['configuration']['padding'] = padding;
			widget['content_body'].toggleClass('no-padding', !padding);
			resizeWidget($obj, data, widget);
		}
	}

	function applyWidgetConfiguration($obj, data, widget, configuration) {
		if ('padding' in configuration) {
			setWidgetPadding($obj, data, widget, configuration['padding']);
		}
	}

	/**
	 * Set height of dashboard container DOM element.
	 *
	 * @param {object} $obj       Dashboard container jQuery object.
	 * @param {object} data       Dashboard data and options object.
	 * @param {integer} min_rows  Minimal desired rows count.
	 */
	function resizeDashboardGrid($obj, data, min_rows) {
		data['options']['rows'] = 0;

		$.each(data['widgets'], function() {
			if (this['pos']['y'] + this['pos']['height'] > data['options']['rows']) {
				data['options']['rows'] = this['pos']['y'] + this['pos']['height'];
			}
		});

		if (data['options']['rows'] == 0) {
			data.new_widget_placeholder.container.show();
		}

		if (typeof min_rows !== 'undefined' && data['options']['rows'] < min_rows) {
			data['options']['rows'] = min_rows;
		}

		var height = data['options']['widget-height'] * data['options']['rows'];

		if (data['options']['edit_mode']) {
			// Occupy whole screen only if in edit mode, not to cause scrollbar in kiosk mode.
			height = Math.max(height, data.minimalHeight);
		}

		$obj.css({
			height: height + 'px'
		});
	}

	/**
	 * Calculate minimal required height of dashboard container.
	 *
	 * @param {object} $obj  Dashboard container jQuery object.
	 *
	 * @returns {integer}
	 */
	function calculateGridMinHeight($obj) {
		return $(window).height() - $obj.offset().top - parseInt($(document.body).css('margin-bottom'), 10);
	}

	function getWidgetByTarget(widgets, $div) {
		return widgets[$div.data('widget-index')];
	}

	function generateRandomString(length) {
		var space = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			ret = '';

		for (var i = 0; length > i; i++) {
			ret += space.charAt(Math.floor(Math.random() * space.length));
		}
		return ret;
	}

	function calcDivPosition($obj, data, $div) {
		var	pos = $div.position(),
			cell_w = data['cell-width'],
			cell_h = data['options']['widget-height'];

		if (data['pos-action'] === 'resize') {
			// 0.49 refers to pixels in the following calculations.
			var place_w = Math.round($div.width() / cell_w - 0.49),
				place_h = Math.round($div.height() / cell_h - 0.49),
				place_x = $div.hasClass('resizing-left')
					? (Math.round((pos.left + $div.width()) / cell_w) - place_w)
					: Math.round(pos.left / cell_w),
				place_y = $div.hasClass('resizing-top')
					? (Math.round((pos.top + $div.height()) / cell_h) - place_h)
					: Math.round(pos.top / cell_h);
		}
		else {
			var place_x = Math.round(pos.left / cell_w),
				place_y = Math.round(pos.top / cell_h),
				place_w = Math.round(($div.width() + pos.left - place_x * cell_w) / cell_w),
				place_h = Math.round(($div.height() + pos.top - place_y * cell_h) / cell_h);
		}

		if (data['pos-action'] === 'resize') {
			place_w = Math.min(place_w, place_w + place_x, data['options']['max-columns'] - place_x);
			place_h = Math.min(place_h, place_h + place_y, data['options']['max-rows'] - place_y);
		}

		place_x = Math.min(place_x, data['options']['max-columns'] - place_w);
		place_y = Math.min(place_y, data['options']['max-rows'] - place_h);

		return {
			x: Math.max(place_x, 0),
			y: Math.max(place_y, 0),
			width: Math.max(place_w, 1),
			height: Math.max(place_h, data['options']['widget-min-rows'])
		}
	}

	function getCurrentCellWidth(data) {
		return $('.dashbrd-grid-container').width() / data['options']['max-columns'];
	}

	function setDivPosition($div, data, pos) {
		$div.css({
			left: (data['options']['widget-width'] * pos['x']) + '%',
			top: (data['options']['widget-height'] * pos['y']) + 'px',
			width: (data['options']['widget-width'] * pos['width']) + '%',
			height: (data['options']['widget-height'] * pos['height']) + 'px'
		});
	}

	function resetCurrentPositions(widgets) {
		for (var i = 0; i < widgets.length; i++) {
			widgets[i]['current_pos'] = $.extend({}, widgets[i]['pos']);
		}
	}

	function startWidgetPositioning($obj, data, widget, action) {
		data['pos-action'] = action;
		data['cell-width'] = getCurrentCellWidth(data);
		data['placeholder'].css('visibility', (action === 'resize') ? 'hidden' : 'visible').show();
		data.new_widget_placeholder.container.hide();
		resetCurrentPositions(data['widgets']);
	}

	function posEquals(pos1, pos2) {
		var ret = true;

		$.each(['x', 'y', 'width', 'height'], function(index, key) {
			if (pos1[key] !== pos2[key]) {
				ret = false;
				return false;
			}
		});

		return ret;
	}

	/**
	 * Check is there collision between two position objects.
	 *
	 * @param {object} pos1  Object with position and dimension.
	 * @param {object} pos2  Object with position and dimension.
	 *
	 * @returns {boolean}
	 */
	function rectOverlap(pos1, pos2) {
		return (pos1.x < (pos2.x + pos2.width)
			&& (pos1.x + pos1.width) > pos2.x
			&& pos1.y < (pos2.y + pos2.height)
			&& (pos1.y + pos1.height) > pos2.y);
	}

	/**
	 * Rearrange widgets on drag operation.
	 *
	 * @param {array}  widgets   Array of widget objects.
	 * @param {object} widget    Moved widget object.
	 * @param {number} max_rows
	 *
	 * @returns {boolean}
	 */
	function realignWidget(widgets, widget, max_rows) {
		var overflow = false,
			realign = function(widgets, widget, allow_reorder) {
				var next = [];

				widgets.forEach(function(w) {
					if (widget.uniqueid !== w.uniqueid && !overflow) {
						if (rectOverlap(widget.current_pos, w.current_pos)
								|| (!allow_reorder && 'affected_by_id' in w && w.affected_by_id === widget.uniqueid)) {
							w.current_pos.y = Math.max(w.current_pos.y,
								widget.current_pos.y + widget.current_pos.height
							);
							next.push(w);
							overflow = (overflow || (w.current_pos.y + w.current_pos.height) > max_rows);
						}
					}
				});

				next.forEach(function(widget) {
					if (!overflow) {
						realign(widgets, widget, false);
					}
				});
			};

		widgets.each(function(w) {
			if (widget.uniqueid !== w.uniqueid && !overflow) {
				w.current_pos = $.extend({}, w.pos);
			}
		});

		realign(sortWidgets(widgets), widget, true);

		return overflow;
	}

	function sortWidgets(widgets, by_current) {
		var by_current = by_current || false;

		widgets
			.sort(function(box1, box2) {
				return by_current ? box1.current_pos.y - box2.current_pos.y : box1.pos.y - box2.pos.y;
			})
			.each(function(box, index) {
				box.div.data('widget-index', index);
			});

		return widgets;
	}

	/**
	 * Collapse dragged widget position moving widgets below to it position.
	 *
	 * @param {array}  widgets   Array of widget objects.
	 * @param {object} widget    Dragged widget object.
	 * @param {number} max_rows  Dashboard rows count.
	 */
	function dragPrepare(widgets, widget, max_rows) {
		var markAffected = function(widgets, affected_by, affected_by_draggable) {
				var w_pos = $.extend({}, affected_by.pos);
				w_pos.height++;

				$.map(widgets, function(w) {
					return (!('affected' in w) && rectOverlap(w_pos, w.pos)) ? w : null;
				}).each(function(w) {
					if (w.uniqueid !== widget.uniqueid) {
						w.affected = true;
						w.affected_by_id = affected_by.uniqueid;
						if (affected_by_draggable) {
							w.affected_by_draggable = affected_by.uniqueid;
						}
						markAffected(widgets, w, affected_by_draggable);
					}
				});
			};

		markAffected(widgets, widget, true);

		widgets.forEach(function(w) {
			delete w.affected;
		});

		widgets.forEach(function(w) {
			markAffected(widgets, w, false);
		});

		$.each(widgets, function(_, w) {
			if ('affected_by_draggable' in w) {
				var pos = $.extend({}, w.pos),
					overlaps = false;

				pos.y -= widget.pos.height;
				pos.height += widget.pos.height;

				$.each(widgets, function(_, b) {
					overlaps = (b.uniqueid !== w.uniqueid && b.uniqueid !== widget.uniqueid && rectOverlap(b.pos, pos));

					if (overlaps) {
						pos.y = b.pos.y + b.pos.height;
						pos.height -= w.pos.y - pos.y;
						overlaps = (pos.height < w.pos.height || pos.y >= w.pos.y);
					}

					return !overlaps;
				});

				if (overlaps) {
					return false;
				}

				w.pos.y = pos.y;
			}
		});
	}

	/**
	 * Resize widgets.
	 *
	 * @param {array}  widgets        Array of widget objects.
	 * @param {object} widget         Resized widget object.
	 * @param {object} axis           Resized axis options.
	 * @param {string} axis.axis_key  Axis key as string: 'x', 'y'.
	 * @param {string} axis.size_key  Size key as string: 'width', 'height'.
	 * @param {number} axis.size_min  Minimum size allowed for one item.
	 * @param {number} axis.size_max  Maximum size allowed for one item, also is used as maximum size of dashboard.
	 */
	function fitWigetsIntoBox(widgets, widget, axis) {
		var axis_key = axis.axis_key,
			size_key = axis.size_key,
			size_min = axis.size_min,
			size_max = axis.size_max,
			opposite_axis_key = (axis_key === 'x') ? 'y' : 'x',
			opposite_size_key = (size_key === 'width') ? 'height' : 'width',
			new_max = 0,
			affected,
			getAffectedInBounds = function(bounds) {
				return $.map(affected, function(box) {
					return rectOverlap(bounds, box.current_pos) ? box : null;
				});
			},
			markAffectedWidgets = function(pos, uid) {
				$.map(widgets, function(box) {
					return (!('affected_axis' in box) && box.uniqueid !== uid && rectOverlap(pos, box.current_pos))
						? box
						: null;
				})
				.each(function(box) {
					var boundary = $.extend({}, box.current_pos);

					if (box.uniqueid !== widget.uniqueid) {
						boundary[size_key] += pos[axis_key] + pos[size_key] - boundary[axis_key];
					}
					box.affected_axis = axis_key;

					markAffectedWidgets(boundary);
				});
			},
			axis_pos = $.extend({}, widget.current_pos),
			margins = {},
			overlap = 0;

		// Resize action for left/up is mirrored right/down action.
		if ('mirrored' in axis) {
			widgets.each(function(box) {
				box.current_pos[axis_key] = size_max - box.current_pos[axis_key] - box.current_pos[size_key];
				box.pos[axis_key] = size_max - box.pos[axis_key] - box.pos[size_key];
			});
			axis_pos[axis_key] = size_max - axis_pos[axis_key] - axis_pos[size_key];
		}

		// Get array containing only widgets affected by resize operation.
		markAffectedWidgets(widget.current_pos, widget.uniqueid);
		affected = $.map(widgets, function(box) {
			return ('affected_axis' in box && box.affected_axis === axis_key && box.uniqueid !== widget.uniqueid)
				? box
				: null;
		});

		affected = affected.sort(function(box1, box2) {
			return box1.current_pos[axis_key] - box2.current_pos[axis_key];
		});

		/**
		 * Compact affected widgets removing empty space between them when possible. Additionally build overlap array
		 * which will contain maximal coordinate occupied by widgets on every opposite axis line.
		 */
		affected.each(function(box) {
			var new_pos = axis_pos[axis_key] + axis_pos[size_key],
				last = box.current_pos[opposite_axis_key] + box.current_pos[opposite_size_key],
				i;

			for (i = box.current_pos[opposite_axis_key]; i < last; i++) {
				if (i in margins) {
					new_pos = Math.max(new_pos, margins[i]);
				}
			}

			if (box.current_pos[axis_key] > new_pos) {
				// Should keep widget original position if compacted value is less than original.
				for (i = box.current_pos[opposite_axis_key]; i < last; i++) {
					margins[i] = box.current_pos[axis_key] + box.current_pos[size_key];
				}

				return;
			}

			for (i = box.current_pos[opposite_axis_key]; i < last; i++) {
				margins[i] = new_pos + box.current_pos[size_key];
			}

			box.current_pos[axis_key] = new_pos;
			new_max = Math.max(new_max, new_pos + box.current_pos[size_key]);
		});

		overlap = new_max - size_max;

		/*
		 * When previous step could not fit affected widgets into visible area resize should be done.
		 * Resize scan affected widgets line by line collapsing only widgets having size greater than minimal
		 * allowed 'size_min' and position overlapped by dashboard visible area.
		 */
		if (overlap > 0) {
			// Scanline is virtual box that utilizes whole width/height depending on its direction defined by size_key.
			var scanline = $.extend({
					x: 0,
					y: 0
				}, axis.scanline),
				slot = axis_pos[axis_key] + axis_pos[size_key],
				next_col,
				col,
				collapsed,
				collapsed_pos,
				margins_backup,
				axis_boundaries = {};

			scanline[size_key] = 1;

			/*
			 * Build affected boundaries object with minimum and maximum value on opposite axis for every widget.
			 * Key in axis_boundaries object will be widget uniqueid and value boundaries object described above.
			 */
			affected.each(function(box) {
				var min = box.current_pos[opposite_axis_key],
					max = min + box.current_pos[opposite_size_key],
					size = box.current_pos[size_key],
					affected_box = $.extend({}, box.current_pos),
					boxes = [],
					bounds_changes = true;

				affected_box[size_key] = new_max - affected_box[axis_key] - affected_box[size_key];

				while (bounds_changes) {
					bounds_changes = false;
					affected_box[axis_key] += size;
					affected_box[opposite_axis_key] = min;
					affected_box[opposite_size_key] = max - min;
					size = new_max;
					boxes = getAffectedInBounds(affected_box);

					boxes.each(function(box) {
						if (min > box.current_pos[opposite_axis_key]) {
							min = box.current_pos[opposite_axis_key];
							bounds_changes = true;
						}

						if (max < box.current_pos[opposite_axis_key] + box.current_pos[opposite_size_key]) {
							max = box.current_pos[opposite_axis_key] + box.current_pos[opposite_size_key];
							bounds_changes = true;
						}

						size = Math.min(size, box.current_pos[size_key]);
					});
				}

				axis_boundaries[box.uniqueid] = {debug: box.header, min: min, max: max};
			});

			// Scan affected line by line.
			while (slot < new_max && overlap > 0) {
				margins_backup = $.extend({}, margins);
				collapsed_pos = {};
				scanline[axis_key] = slot;
				col = getAffectedInBounds(scanline);
				scanline[axis_key] += scanline[size_key];
				next_col = getAffectedInBounds(scanline);
				collapsed = next_col.length > 0;

				$.each(next_col, function(_, box) {
					if ('pos' in box && box.pos[axis_key] > slot) {
						return;
					}

					box.new_pos = $.extend({}, box.current_pos);
					box.new_pos[axis_key] = slot;

					$.each(col, function(_, col_box) {
						if (col_box.uniqueid === box.uniqueid || rectOverlap(col_box.current_pos, box.new_pos)) {
							if (col_box.current_pos[size_key] > size_min) {
								var start_pos = axis_boundaries[col_box.uniqueid].min,
									stop_pos = axis_boundaries[col_box.uniqueid].max,
									margin = 0,
									i;

								// Find max overlap position value for checked widget.
								for (i = start_pos; i < stop_pos; i++) {
									margin = Math.max(margin, margins[i]);
								}

								if (margin && margin < size_max) {
									box.new_pos[axis_key] = box.current_pos[axis_key];
									return true;
								}
								else {
									for (i = start_pos; i < stop_pos; i++) {
										margins[i] = margins_backup[i] - scanline[size_key];
									}
								}

								col_box.new_pos = $.extend({}, col_box.current_pos);
								col_box.new_pos[size_key] -= scanline[size_key];

								// Mark opposite axis coordinates as moveable.
								for (i = start_pos; i < stop_pos; i++) {
									collapsed_pos[i] = 1;
								}
							}
							else {
								collapsed = false;
							}
						}

						return collapsed;
					});

					return collapsed;
				});

				if (collapsed) {
					affected.each(function(box) {
						if (box.current_pos[axis_key] > slot && box.current_pos[opposite_axis_key] in collapsed_pos) {
							box.current_pos[axis_key] = Math.max(box.current_pos[axis_key] - scanline[size_key],
								box.pos[axis_key]
							);
						}
					});

					// Update margin values for collapsed lines on opposite axis.
					$.each(collapsed_pos, function(index) {
						margins[index] = margins_backup[index] - scanline[size_key];
					});

					overlap -= 1;
					new_max -= 1;
				}
				else {
					margins = margins_backup;
					slot += scanline[size_key];
				}

				next_col.concat(col).each(function(box) {
					if (collapsed && 'new_pos' in box) {
						box.current_pos = box.new_pos;
					}

					delete box.new_pos;
				});
			}
		}

		/*
		 * When resize failed to fit affected widgets move them into visible area and decrease size of widget
		 * which started resize operation, additionally setting 'overflow' property to widget.
		 */
		if (overlap > 0) {
			widget.current_pos[size_key] -= overlap;
			widget.current_pos.overflow = true;

			affected.each(function(box) {
				box.current_pos[axis_key] = Math.max(box.current_pos[axis_key] - overlap, box.pos[axis_key]);
			});
		}

		/*
		 * Perform additional check on validity of collapsed size. Collapsing is done if there is collision between
		 * box on axis_key and box on {axis_key+scanline[size_key]} therefore box can be collapsed on collision with
		 * itself, such situation can lead to missdetection of ability to be collapsed.
		 */
		affected.sort(function(box1, box2) {
			return box2.current_pos[axis_key] - box1.current_pos[axis_key];
		}).each(function(box) {
			if (box.pos[size_key] > box.current_pos[size_key]) {
				var new_pos = $.extend({}, box.current_pos),
					size = Math.min(box.pos[size_key], size_max - box.current_pos[axis_key]);

				new_pos[size_key] = box.pos[size_key];
				$.map(affected, function(col_box) {
					return col_box.uniqueid !== box.uniqueid && rectOverlap(col_box.current_pos, new_pos)
						? col_box
						: null;
				}).each(function(col_box) {
					size = Math.min(size,
						col_box.current_pos[axis_key] - box.current_pos[axis_key]
					);
				});

				box.current_pos[size_key] = Math.max(size, size_min);
			}
		});

		// Resize action for left/up is mirrored right/down action, mirror coordinates back.
		if ('mirrored' in axis) {
			widgets.each(function(box) {
				box.current_pos[axis_key] = size_max - box.current_pos[axis_key] - box.current_pos[size_key];
				box.pos[axis_key] = size_max - box.pos[axis_key] - box.pos[size_key];
			});
		}
	}

	/**
	 * Rearrange widgets. Modifies widget.current_pos if desired size is greater than allowed by resize.
	 *
	 * @param {array}  data
	 * @param {array}  data.widgets  Array of widgets objects.
	 * @param {object} widget        Moved widget object.
	 */
	function realignResize(data, widget) {
		var axis,
			opposite_axis_key,
			opposite_size_key,
			process_order = (widget.prev_pos.x != widget.current_pos.x
				|| widget.prev_pos.width != widget.current_pos.width)
					? ['x', 'y']
					: ['y', 'x'];

		data.widgets.each(function(box) {
			if (box.uniqueid !== widget.uniqueid) {
				box.current_pos = $.extend({}, box.pos);
			}
		});

		if (widget.prev_pos.x > widget.current_pos.x) {
			widget.prev_pos.mirrored.x = true;
		}

		if (widget.prev_pos.y > widget.current_pos.y) {
			widget.prev_pos.mirrored.y = true;
		}

		// Situation when there are changes on both axes should be handled as special case.
		if (process_order[0] === 'x' && (widget.prev_pos.y != widget.current_pos.y
				|| widget.prev_pos.height != widget.current_pos.height)) {
			// Mark affected_axis as y if affected box is affected by only changing y position or height.
			var pos = {
					x: widget.prev_pos.x,
					y: widget.current_pos.y,
					width: widget.prev_pos.width,
					height: widget.current_pos.height
				};

			if ('width' in widget.prev_pos.axis_correction) {
				// Use 'corrected' size if it is less than current size.
				pos.width = Math.min(widget.prev_pos.axis_correction.width, pos.width);

				if ('x' in widget.prev_pos.mirrored && 'x' in widget.prev_pos.axis_correction) {
					pos.x = Math.max(widget.prev_pos.axis_correction.x, pos.x);
				}
			}

			$.map(data.widgets, function(box) {
				return (!('affected_axis' in box) && widget.uniqueid !== box.uniqueid
					&& rectOverlap(widget.current_pos, box.current_pos))
					? box
					: null;
			}).each(function(box) {
				if (rectOverlap(pos, box.current_pos)) {
					box.affected_axis = 'y';
				}
			});
		}

		// Store current position as previous position for next steps.
		widget.prev_pos = $.extend(widget.prev_pos, widget.current_pos);

		// Process changes for every axis.
		process_order.each(function(axis_key) {
			data.widgets.each(function(box) {
				if ('affected_axis' in box && box.affected_axis === axis_key) {
					delete box.affected_axis;
				}
			});

			axis = {
				axis_key: axis_key,
				size_key: 'width',
				size_min: 1,
				size_max: data.options['max-columns'],
				scanline: {
					width: data.options['max-columns'],
					height: data.options['max-rows']
				}
			};

			if (axis_key === 'y') {
				axis.size_key = 'height';
				axis.size_min = data.options['widget-min-rows'];
				axis.size_max = data.options['max-rows'];
			}

			if (axis_key in widget.prev_pos.mirrored) {
				axis.mirrored = true;
			}

			opposite_axis_key = (axis_key === 'y') ? 'x' : 'y',
			opposite_size_key = (opposite_axis_key === 'x') ? 'width' : 'height';

			if (opposite_size_key in widget.prev_pos.axis_correction) {
				// Use 'corrected' size if it is less than current size.
				widget.current_pos[opposite_size_key] = Math.min(widget.prev_pos.axis_correction[opposite_size_key],
					widget.current_pos[opposite_size_key]);

				if (opposite_axis_key in widget.prev_pos.mirrored && opposite_axis_key in widget.prev_pos.axis_correction) {
					widget.current_pos[opposite_axis_key] = Math.max(widget.prev_pos.axis_correction[opposite_axis_key],
						widget.current_pos[opposite_axis_key]);
				}
			}

			fitWigetsIntoBox(data.widgets, widget, axis);

			if ('overflow' in widget.current_pos) {
				// Store 'corrected' size.
				widget.prev_pos.axis_correction[axis.size_key] = widget.current_pos[axis.size_key];

				if (axis.mirrored) {
					widget.prev_pos.axis_correction[axis_key] = widget.current_pos[axis_key];
				}

				delete widget.current_pos.overflow;
			}
		});
	}

	function checkWidgetOverlap(data) {
		resetCurrentPositions(data['widgets']);

		$.each(data['widgets'], function() {
			if (!posEquals(this['pos'], this['current_pos'])) {
				this['pos'] = this['current_pos'];
				setDivPosition(this['div'], data, this['pos']);
			}

			delete this['current_pos'];
		});
	}

	/**
	 * User action handler for resize of widget.
	 *
	 * @param {object} $obj    Dashboard container jQuery object.
	 * @param {object} data    Dashboard data and options object.
	 * @param {object} widget  Dashboard widget object.
	 */
	function doWidgetResize($obj, data, widget) {
		var	pos = calcDivPosition($obj, data, widget['div']),
			rows = 0;

		if (!posEquals(pos, widget['current_pos'])) {
			widget['current_pos'] = pos;
			realignResize(data, widget);

			if (widget['iterator']) {
				alignIteratorContents($obj, data, widget, widget['current_pos']);
			}

			data.widgets.each(function(box) {
				if (widget.uniqueid !== box.uniqueid) {
					if (box['iterator']) {
						var box_pos = calcDivPosition($obj, data, box['div']);
						if (box_pos['width'] !== box['current_pos']['width']
								|| box_pos['height'] !== box['current_pos']['height']) {
							alignIteratorContents($obj, data, box, box['current_pos']);
						}
					}

					setDivPosition(box['div'], data, box['current_pos']);
				}

				rows = Math.max(rows, box.current_pos.y + box.current_pos.height);
			});

			if (rows != data['options']['rows']) {
				resizeDashboardGrid($obj, data, rows);
			}
		}

		setDivPosition(data['placeholder'], data, pos);
	}

	/**
	 * User action handler for drag of widget.
	 *
	 * @param {object} $obj    Dashboard container jQuery object.
	 * @param {object} data    Dashboard data and options object.
	 * @param {object} widget  Dashboard widget object.
	 */
	function doWidgetPositioning($obj, data, widget) {
		var	pos = calcDivPosition($obj, data, widget['div']),
			rows = 0,
			overflow = false;

		if (!posEquals(pos, widget.current_pos)) {
			widget.current_pos = pos;
			overflow = realignWidget(data['widgets'], widget, data.options['max-rows']);

			if (overflow) {
				// restore last non-overflow position
				data.widgets.each(function(w) {
					w.current_pos = $.extend({}, data.undo_pos[w.uniqueid]);
				});
				pos = widget.current_pos;
			}
			else {
				// store all widget current_pos objects
				data.undo_pos = {};
				data.widgets.each(function(w) {
					data.undo_pos[w.uniqueid] = $.extend({}, w.current_pos);
				});

				data['widgets'].forEach(function(w) {
					if (widget.uniqueid !== w.uniqueid) {
						setDivPosition(w['div'], data, w.current_pos);
					}

					rows = Math.max(rows, w.current_pos.y + w.current_pos.height);
				});

				if (rows > data.options['rows']) {
					data.options['rows_actual'] = rows;
					resizeDashboardGrid($obj, data, rows);
				}
			}
		}

		setDivPosition(data['placeholder'], data, pos);
	}

	function stopWidgetPositioning($obj, data, widget) {
		data['placeholder'].hide();
		data['pos-action'] = '';

		$.each(data['widgets'], function() {
			// Check if position of widget changed
			var new_pos = this['current_pos'],
				old_pos = this['pos'],
				changed = false;

			$.each(['x', 'y', 'width', 'height'], function(index, value) {
				if (new_pos[value] !== old_pos[value]) {
					changed = true;
				}
			});

			if (changed === true) {
				// mark dashboard as updated
				data['options']['updated'] = true;
				this['pos'] = this['current_pos'];
			}

			// should be present only while dragging
			delete this['current_pos'];
		});

		setDivPosition(widget['div'], data, widget['pos']);
		resizeDashboardGrid($obj, data);
	}

	function makeDraggable($obj, data, widget) {
		widget['div'].draggable({
			cursor: IE ? 'move' : 'grabbing',
			handle: widget['content_header'],
			scroll: true,
			scrollSensitivity: data.options['widget-height'],
			start: function() {
				$obj.addClass('dashbrd-positioning');

				data.calculated = {
					'left-max': $obj.width() - widget['div'].width(),
					'top-max': data.options['max-rows'] * data.options['widget-height'] - widget['div'].height()
				};

				setResizableState('disable', data.widgets, '');
				dragPrepare(data.widgets, widget, data['options']['max-rows']);
				startWidgetPositioning($obj, data, widget, 'drag');
				realignWidget(data.widgets, widget, data.options['max-rows']);

				widget.current_pos = $.extend({}, widget.pos);
				data.undo_pos = {};
				data.widgets.each(function(w) {
					data.undo_pos[w.uniqueid] = $.extend({}, w.current_pos);
				});
			},
			drag: function(event, ui) {
				// Limit element draggable area for X and Y axis.
				ui.position = {
					left: Math.max(0, Math.min(ui.position.left, data.calculated['left-max'])),
					top: Math.max(0, Math.min(ui.position.top, data.calculated['top-max']))
				};

				doWidgetPositioning($obj, data, widget);
			},
			stop: function() {
				delete data.calculated;
				delete data.undo_pos;

				data.widgets = sortWidgets(data.widgets).each(function(widget) {
					delete widget.affected_by_draggable;
					delete widget.affected_by_id;
					delete widget.affected;
				});

				setResizableState('enable', data.widgets, '');
				stopWidgetPositioning($obj, data, widget);

				if (widget['iterator'] && !widget['div'].is(':hover')) {
					widget['div'].removeClass('iterator-double-header');
				}

				data['options']['rows'] = data['options']['rows_actual'];
				resizeDashboardGrid($obj, data, data['options']['rows_actual']);

				$obj.removeClass('dashbrd-positioning');
			}
		});
	}

	function makeResizable($obj, data, widget) {
		var	handles = {};

		$.each(['n', 'e', 's', 'w', 'ne', 'se', 'sw', 'nw'], function(index, key) {
			var	$handle = $('<div>').addClass('ui-resizable-handle').addClass('ui-resizable-' + key);

			if ($.inArray(key, ['n', 'e', 's', 'w']) >= 0) {
				$handle
					.append($('<div>', {'class': 'ui-resize-dot'}))
					.append($('<div>', {'class': 'ui-resizable-border-' + key}));
			}

			widget['div'].append($handle);
			handles[key] = $handle;
		});

		widget['div'].resizable({
			handles: handles,
			scroll: false,
			minWidth: getCurrentCellWidth(data),
			minHeight: data['options']['widget-min-rows'] * data['options']['widget-height'],
			start: function(event) {
				doLeaveWidgetsExcept($obj, data, widget);
				doEnterWidget($obj, data, widget);

				$obj.addClass('dashbrd-positioning');

				var handle_class = event.currentTarget.className;
				data['resizing_top'] = handle_class.match(/(^|\s)ui-resizable-(n|ne|nw)($|\s)/) !== null;
				data['resizing_left'] = handle_class.match(/(^|\s)ui-resizable-(w|sw|nw)($|\s)/) !== null;

				data.widgets.each(function(box) {
					delete box.affected_axis;
				});

				setResizableState('disable', data.widgets, widget.uniqueid);
				startWidgetPositioning($obj, data, widget, 'resize');
				widget.prev_pos = $.extend({mirrored: {}}, widget.pos);
				widget.prev_pos.axis_correction = {};
			},
			resize: function(event, ui) {
				// Will break fast-resizing widget-top past minimum height, if moved to start section (jQuery UI bug?)
				widget['div']
					.toggleClass('resizing-top', data['resizing_top'])
					.toggleClass('resizing-left', data['resizing_left']);

				/*
				 * 1. Prevent physically resizing widgets beyond the allowed limits.
				 * 2. Prevent browser's vertical scrollbar from appearing when resizing right size of the widgets.
				 */

				if (ui.position.left < 0) {
					ui.size.width += ui.position.left;
					ui.position.left = 0;
				}

				if (ui.position.top < 0) {
					ui.size.height += ui.position.top;
					ui.position.top = 0;
				}

				if (data['resizing_top']) {
					ui.position.top += Math.max(0,
						ui.size.height - data['options']['widget-max-rows'] * data['options']['widget-height']
					);
				}

				widget['div'].css({
					'left': ui.position.left,
					'top': ui.position.top,
					'max-width': Math.min(ui.size.width,
						data['cell-width'] * data['options']['max-columns'] - ui.position.left
					),
					'max-height': Math.min(ui.size.height,
						data['options']['widget-max-rows'] * data['options']['widget-height'],
						data['options']['max-rows'] * data['options']['widget-height'] - ui.position.top
					)
				});

				doWidgetResize($obj, data, widget);

				widget['container'].css({
					'width': data['placeholder'].width(),
					'height': data['placeholder'].height()
				});
			},
			stop: function() {
				doLeaveWidget($obj, data, widget);

				delete widget.prev_pos;

				setResizableState('enable', data.widgets, widget.uniqueid);
				stopWidgetPositioning($obj, data, widget);

				widget['container'].removeAttr('style');

				if (widget['iterator']) {
					alignIteratorContents($obj, data, widget, widget['pos']);
				}

				delete data['resizing_top'];
				delete data['resizing_left'];

				widget['div']
					.removeClass('resizing-top')
					.removeClass('resizing-left')
					.css({
						'max-width': '',
						'max-height': ''
					});

				// Invoke onResizeEnd on every affected widget.
				data.widgets.each(function(box) {
					if ('affected_axis' in box || box.uniqueid === widget.uniqueid) {
						resizeWidget($obj, data, box);
					}
				});

				$obj.removeClass('dashbrd-positioning');
			}
		});
	}

	/**
	 * Set resizable state for dashboard widgets.
	 *
	 * @param {string} state     Enable or disable resizable for widgets. Available values: 'enable', 'disable'.
	 * @param {array}  widgets   Array of all widgets.
	 * @param {string} ignoreid  All widgets except widget with such uniqueid will be affected.
	 */
	function setResizableState(state, widgets, ignoreid) {
		widgets.each(function(widget) {
			if (widget.uniqueid !== ignoreid) {
				widget.div.resizable(state);
			}
		});
	}

	function showPreloader(widget) {
		if (typeof widget['preloader_div'] === 'undefined') {
			if (widget['iterator']) {
				widget['div'].addClass('iterator-loading');
			}

			widget['preloader_div'] = $('<div>')
				.addClass('preloader-container')
				.append($('<div>').addClass('preloader'));

			widget['div'].append(widget['preloader_div']);
		}
	}

	function hidePreloader(widget) {
		if (typeof widget['preloader_div'] !== 'undefined') {
			if (widget['iterator']) {
				widget['div'].removeClass('iterator-loading');
			}

			widget['preloader_div'].remove();
			delete widget['preloader_div'];
		}
	}

	function startPreloader(widget, timeout) {
		timeout = timeout || widget['preloader_timeout'];

		if (typeof widget['preloader_timeoutid'] !== 'undefined' || typeof widget['preloader_div'] !== 'undefined') {
			return;
		}

		widget['preloader_timeoutid'] = setTimeout(function () {
			delete widget['preloader_timeoutid'];

			showPreloader(widget);
			widget['content_body'].stop(true, true).fadeTo(widget['preloader_fadespeed'], 0.4);
		}, timeout);
	}

	function stopPreloader(widget) {
		if (typeof widget['preloader_timeoutid'] !== 'undefined') {
			clearTimeout(widget['preloader_timeoutid']);
			delete widget['preloader_timeoutid'];
		}

		hidePreloader(widget);

		// Stop animations and set to visible state.
		// Do not use .show(), nor .fadeTo(0, 1) here, since these set display: block, which will break css rules.
		widget['content_body'].stop(true, true).css('opacity', 1);
	}

	function setUpdateWidgetContentTimer($obj, data, widget, rf_rate) {
		clearUpdateWidgetContentTimer(widget);

		if (widget['updating_content']) {
			// Waiting for another AJAX request to either complete of fail.
			return;
		}

		if (typeof rf_rate === 'undefined') {
			rf_rate = widget['rf_rate'];
		}

		if (rf_rate) {
			widget['rf_timeoutid'] = setTimeout(function() {
				// Do not update widget content if there are active popup or hintbox.
				var active = widget['content_body'].find('[data-expanded="true"]');

				if (!active.length && !doAction('timer_refresh', $obj, data, widget)) {
					// No active popup or hintbox AND no triggers executed => update now.
					updateWidgetContent($obj, data, widget);
				}
				else {
					// Active popup or hintbox OR triggers executed => just setup the next cycle.
					setUpdateWidgetContentTimer($obj, data, widget);
				}
			}, rf_rate * 1000);
		}
	}

	function clearUpdateWidgetContentTimer(widget) {
		if (typeof widget['rf_timeoutid'] !== 'undefined') {
			clearTimeout(widget['rf_timeoutid']);
			delete widget['rf_timeoutid'];
		}
	}

	function setIteratorTooSmallState(iterator, enabled) {
		iterator['div'].toggleClass('iterator-too-small', enabled);
	}

	function getIteratorTooSmallState(iterator) {
		return iterator['div'].hasClass('iterator-too-small');
	}

	function numIteratorColumns(iterator) {
		return iterator['fields']['columns'] ? iterator['fields']['columns'] : 2;
	}

	function numIteratorRows(iterator) {
		return iterator['fields']['rows'] ? iterator['fields']['rows'] : 1;
	}

	function isIteratorTooSmall($obj, data, iterator, pos) {
		return pos['width'] < numIteratorColumns(iterator)
			|| pos['height'] < numIteratorRows(iterator) * data['options']['widget-min-rows'];
	}

	function addIteratorPlaceholders($obj, data, iterator, count) {
		$('.dashbrd-grid-iterator-placeholder', iterator['content_body']).remove();

		for (var index = 0; index < count; index++) {
			iterator['content_body'].append($('<div>', {'class': 'dashbrd-grid-iterator-placeholder'})
				.append('<div>')
				.on('mouseenter', function() {
					// Set single-line header for the iterator.
					iterator['div'].removeClass('iterator-double-header');

					if (data['options']['kioskmode'] && iterator['div'].position().top == 0) {
						slideKiosk($obj, data);
					}
				})
			);
		}
	}

	function alignIteratorContents($obj, data, iterator, pos) {
		if (isIteratorTooSmall($obj, data, iterator, pos)) {
			setIteratorTooSmallState(iterator, true);

			return;
		}

		if (getIteratorTooSmallState(iterator) && iterator['update_pending']) {
			setIteratorTooSmallState(iterator, false);
			showPreloader(iterator);
			updateWidgetContent($obj, data, iterator);

			return;
		}

		setIteratorTooSmallState(iterator, false);

		var $placeholders = iterator['content_body'].find('.dashbrd-grid-iterator-placeholder'),
			num_columns = numIteratorColumns(iterator),
			num_rows = numIteratorRows(iterator);

		for (var index = 0, count = num_columns * num_rows; index < count; index++) {
			var cell_column = index % num_columns,
				cell_row = Math.floor(index / num_columns),
				cell_width_min = Math.floor(pos['width'] / num_columns),
				cell_height_min = Math.floor(pos['height'] / num_rows),
				num_enlarged_columns = pos['width'] - cell_width_min * num_columns,
				num_enlarged_rows = pos['height'] - cell_height_min * num_rows,
				x = cell_column * cell_width_min + Math.min(cell_column, num_enlarged_columns),
				y = cell_row * cell_height_min + Math.min(cell_row, num_enlarged_rows),
				width = cell_width_min + (cell_column < num_enlarged_columns ? 1 : 0),
				height = cell_height_min + (cell_row < num_enlarged_rows ? 1 : 0),
				css = {
					left: (x / pos['width'] * 100) + '%',
					top: (y * data['options']['widget-height']) + 'px',
					width: (width / pos['width'] * 100) + '%',
					height: (height * data['options']['widget-height']) + 'px'
				};

			if (cell_column == num_columns - 1) {
				// Setting right side for last column of widgets (fixes IE11 and Opera issues).
				$.extend(css, {
					width: 'auto',
					right: '0px'
				});
			}
			else {
				$.extend(css, {
					width: Math.round(width / pos['width'] * 100 * 100) / 100 + '%',
					right: 'auto'
				});
			}

			if (index < iterator['children'].length) {
				iterator['children'][index]['div'].css(css);
			}
			else {
				$placeholders.eq(index - iterator['children'].length).css(css);
			}
		}
	}

	function addWidgetOfIterator($obj, data, iterator, child) {
		// Replace empty arrays (or anything non-object) with empty objects.
		if (typeof child['fields'] !== 'object') {
			child['fields'] = {};
		}
		if (typeof child['configuration'] !== 'object') {
			child['configuration'] = {};
		}

		child = $.extend({
			'widgetid': '',
			'type': '',
			'header': '',
			'view_mode': iterator['view_mode'],
			'preloader_timeout': 10000,	// in milliseconds
			'preloader_fadespeed': 500,
			'update_paused': false,
			'initial_load': true,
			'ready': false,
			'storage': {}
		}, child, {
			'iterator': false,
			'parent': iterator,
			'new_widget': false
		});

		child['uniqueid'] = generateUniqueId($obj, data);
		child['div'] = makeWidgetDiv($obj, data, child);

		updateWidgetDynamic($obj, data, child);

		iterator['content_body'].append(child['div']);
		iterator['children'].push(child);

		showPreloader(child);
	}

	function hasEqualProperties(object_1, object_2) {
		if (Object.keys(object_1).length !== Object.keys(object_2).length) {
			return false;
		}

		for (var key in object_1) {
			if (object_1[key] !== object_2[key]) {
				return false;
			}
		}

		return true;
	}

	/**
	* Clear and reset the state of the iterator.
	*/
	function clearIterator($obj, data, iterator) {
		iterator['children'].forEach(function(child) {
			removeWidget($obj, data, child);
		});

		iterator['content_body'].empty();
		iterator['children'] = [];

		iterator['div'].removeClass('iterator-alt-content');
	}

	function updateIteratorCallback($obj, data, iterator, response, options) {
		var has_alt_content = typeof response.messages !== 'undefined' || typeof response.body !== 'undefined';

		if (has_alt_content || getIteratorTooSmallState(iterator)) {
			clearIterator($obj, data, iterator);

			if (has_alt_content) {
				var $alt_content = $('<div>');
				if (typeof response.messages !== 'undefined') {
					$alt_content.append(response.messages);
				}
				if (typeof response.body !== 'undefined') {
					$alt_content.append(response.body);
				}
				iterator['content_body'].append($alt_content);
				iterator['div'].addClass('iterator-alt-content');
			}
			else {
				iterator['update_pending'] = true;
			}

			return;
		}

		if (iterator['div'].hasClass('iterator-alt-content')) {
			// Returning from alt-content to normal mode.
			clearIterator($obj, data, iterator);
		}

		iterator['page'] = response.page;
		iterator['page_count'] = response.page_count;
		updateIteratorPager(iterator);

		var current_children = iterator['children'],
			current_children_by_widgetid = {};

		iterator['children'] = [];

		current_children.forEach(function(child) {
			if (child['widgetid'] !== '') {
				current_children_by_widgetid[child['widgetid']] = child;
			}
			else {
				// Child widgets without 'uniqueid' are never persisted.
				removeWidget($obj, data, child);
			}
		});

		var reused_widgetids = [];
		response.children.slice(0, numIteratorColumns(iterator) * numIteratorRows(iterator))
			.forEach(function(child) {
				if (typeof child['widgetid'] !== 'undefined' && current_children_by_widgetid[child['widgetid']]
						&& hasEqualProperties(child['fields'], current_children_by_widgetid[child['widgetid']]['fields'])) {

					// Reuse widget, if it has 'widgetid' supplied, has exactly the same fields and fields data.
					// Please note, that the order of widgets inside of iterator['content_body'] is not important,
					// since the absolute positioning is done based on widget order in the iterator['children'].

					iterator['children'].push(current_children_by_widgetid[child['widgetid']]);
					reused_widgetids.push(child['widgetid']);
				}
				else {
					addWidgetOfIterator($obj, data, iterator, child);
				}
			});

		$.each(current_children_by_widgetid, function(index, child) {
			if ($.inArray(child['widgetid'], reused_widgetids) === -1) {
				removeWidget($obj, data, child);
			}
		});

		addIteratorPlaceholders($obj, data, iterator,
			numIteratorColumns(iterator) * numIteratorRows(iterator) - iterator['children'].length
		);

		alignIteratorContents($obj, data, iterator,
			(typeof iterator['current_pos'] === 'object') ? iterator['current_pos'] : iterator['pos']
		);

		iterator['children'].forEach(function(child) {
			/* Possible update policies for the child widgets:
				resize: execute 'onResizeEnd' action (widget won't update if there's no trigger or size hasn't changed).
					- Is used to propagate iterator's resize event.

				refresh: either execute 'timer_refresh' action (if trigger exists) or updateWidgetContent.
					- Is used when widget surely hasn't been resized, but needs to be refreshed.

				resize_or_refresh: either execute 'onResizeEnd' or 'timer_refresh' action, or updateWidgetContent.
					- Is used when widget might have been resized, and needs to be refreshed anyway.
			*/

			var update_policy = 'refresh';

			if ($.inArray(child['widgetid'], reused_widgetids) !== -1 && 'update_policy' in options) {
				// Allow to override update_policy only for existing (not new) widgets.
				update_policy = options['update_policy'];
			}

			var success = false;
			switch (update_policy) {
				case 'resize':
				case 'resize_or_refresh':
					success = resizeWidget($obj, data, child);
					if (update_policy === 'resize') {
						success = true;
					}
					if (success) {
						break;
					}
					// No break here.

				case 'refresh':
					success = doAction('timer_refresh', $obj, data, child);
					break;
			}

			if (!success) {
				// No triggers executed for the widget, therefore update the conventional way.
				updateWidgetContent($obj, data, child);
			}
		});
	}

	function updateWidgetCallback($obj, data, widget, response, options) {
		widget['content_body'].empty();
		if (typeof response.messages !== 'undefined') {
			widget['content_body'].append(response.messages);
		}
		widget['content_body'].append(response.body);

		if (typeof response.debug !== 'undefined') {
			var debug_visible = $('[name="zbx_debug_info"]', widget['content_body']).is(':visible');

			$(response.debug).appendTo(widget['content_body'])[debug_visible ? 'show' : 'hide']();
		}

		removeWidgetInfoButtons(widget['content_header']);
		if (typeof response.info !== 'undefined' && !data['options']['edit_mode']) {
			addWidgetInfoButtons(widget['content_header'], response.info);
		}

		// Creates new script elements and removes previous ones to force their re-execution.
		widget['content_script'].empty();
		if (typeof response.script_inline !== 'undefined') {
			// NOTE: to execute script with current widget context, add unique ID for required div, and use it in script.
			widget['content_script'].append($('<script>').text(response.script_inline));
		}
	}

	function isDeletedWidget($obj, data, widget) {
		if (widget['parent']) {
			if (isDeletedWidget($obj, data, widget['parent'])) {
				return true;
			}

			var search_widgets = widget['parent']['children'];
		}
		else {
			var search_widgets = data['widgets'];
		}

		var widgets_found = search_widgets.filter(function(w) {
				return (w['uniqueid'] === widget['uniqueid']);
			});

		return !widgets_found.length;
	}

	function setWidgetReady($obj, data, widget) {
		if (widget['ready']) {
			return;
		}

		var ready_updated = false,
			dashboard_was_ready = !data['widgets'].filter(function(widget) {
				return !widget['ready'];
			}).length;

		if (widget['iterator']) {
			if (!widget['children'].length) {
				// Set empty iterator to ready state.

				ready_updated = !widget['ready'];
				widget['ready'] = true;
			}
		}
		else if (widget['parent']) {
			widget['ready'] = true;

			var children = widget['parent']['children'],
				children_not_ready = children.filter(function(widget) {
					return !widget['ready'];
				});

			if (!children_not_ready.length) {
				// Set parent iterator to ready state.

				ready_updated = !widget['parent']['ready'];
				widget['parent']['ready'] = true;
			}
		}
		else {
			ready_updated = !widget['ready'];
			widget['ready'] = true;
		}

		if (ready_updated) {
			/*
			 * The conception:
			 *   - Hold 'registerDataExchangeCommit' until all widgets are loaded.
			 *   - Call 'registerDataExchangeCommit' and 'onDashboardReady' once, as soon as all widgets are loaded.
			 *   - Call 'registerDataExchangeCommit' and 'onDashboardReady' for each new widget added in edit mode.
			 */

			if (dashboard_was_ready) {
				methods.registerDataExchangeCommit.call($obj);
			}
			else {
				var dashboard_is_ready = !data['widgets'].filter(function(widget) {
						return !widget['ready'];
					}).length;

				if (dashboard_is_ready) {
					methods.registerDataExchangeCommit.call($obj);
					doAction('onDashboardReady', $obj, data, null);
				}
			}
		}
	}

	function getWidgetContentSize(widget) {
		return {
			'content_width': Math.floor(widget['content_body'].width()),
			'content_height': Math.floor(widget['content_body'].height())
		};
	}

	function isEqualContentSize(size_1, size_2) {
		if (typeof size_1 === 'undefined' || typeof size_2 === 'undefined') {
			return false;
		}

		return size_1['content_width'] === size_2['content_width']
			&& size_1['content_height'] === size_2['content_height'];
	}

	function updateWidgetContent($obj, data, widget, options) {
		clearUpdateWidgetContentTimer(widget);

		if (widget['updating_content']) {
			// Waiting for another AJAX request to either complete of fail.
			return;
		}

		if (widget['update_paused']) {
			setUpdateWidgetContentTimer($obj, data, widget);
			return;
		}

		if (widget['iterator']) {
			var pos = (typeof widget['current_pos'] === 'object') ? widget['current_pos'] : widget['pos'];

			if (isIteratorTooSmall($obj, data, widget, pos)) {
				clearIterator($obj, data, widget);

				stopPreloader(widget);
				setIteratorTooSmallState(widget, true);
				widget['update_pending'] = true;

				return;
			}
			else {
				setIteratorTooSmallState(widget, false);
				widget['update_pending'] = false;
			}
		}

		var url = new Curl('zabbix.php');
		url.setArgument('action', 'widget.' + widget['type'] + '.view');

		var ajax_data = {
				'dashboardid': data['dashboard']['id'],
				'uniqueid': widget['uniqueid'],
				'initial_load': widget['initial_load'] ? 1 : 0,
				'edit_mode': data['options']['edit_mode'] ? 1 : 0,
				'storage': widget['storage'],
				'view_mode': widget['view_mode']
			};

		widget['content_size'] = getWidgetContentSize(widget);

		if (widget['iterator']) {
			ajax_data['page'] = widget['page'];
		}
		else {
			$.extend(ajax_data, widget['content_size']);
		};

		if (widget['widgetid'] !== '') {
			ajax_data['widgetid'] = widget['widgetid'];
		}
		if (widget['header'] !== '') {
			ajax_data['name'] = widget['header'];
		}
		if (typeof widget['fields'] !== 'undefined' && Object.keys(widget['fields']).length != 0) {
			ajax_data['fields'] = JSON.stringify(widget['fields']);
		}
		if (typeof widget['dynamic'] !== 'undefined') {
			ajax_data['dynamic_hostid'] = widget['dynamic']['hostid'];
			ajax_data['dynamic_groupid'] = widget['dynamic']['groupid'];
		}

		startPreloader(widget);
		$('#dashbrd-save').prop('disabled', true);

		widget['updating_content'] = true;

		return jQuery.ajax({
			url: url.getUrl(),
			method: 'POST',
			data: ajax_data,
			dataType: 'json'
		})
			.then(function(response) {
				delete widget['updating_content'];

				stopPreloader(widget);

				if (isDeletedWidget($obj, data, widget)) {
					return;
				}

				var $content_header = $('h4', widget['content_header']);
				$content_header.text(response.header);
				if (typeof response.aria_label !== 'undefined') {
					$content_header.attr('aria-label', (response.aria_label !== '') ? response.aria_label : null);
				}

				if (typeof options === 'undefined') {
					options = {};
				}

				if (widget['iterator']) {
					updateIteratorCallback($obj, data, widget, response, options);
				}
				else {
					updateWidgetCallback($obj, data, widget, response, options);
				}

				doAction('onContentUpdated', $obj, data, null);
				$('#dashbrd-save').prop('disabled', false);
			})
			.then(function() {
				// Separate 'then' section allows to execute scripts added by widgets in previous section first.

				setWidgetReady($obj, data, widget);

				if (!widget['parent']) {
					// Iterator child widgets are excluded here.
					setUpdateWidgetContentTimer($obj, data, widget);
				}

				// The widget is loaded now, although possibly already resized.
				widget['initial_load'] = false;

				if (!widget['iterator']) {
					// Update the widget, if it was resized before it was fully loaded.
					resizeWidget($obj, data, widget);
				}
			})
			.fail(function() {
				// TODO: gentle message about failed update of widget content

				delete widget['updating_content'];
				setUpdateWidgetContentTimer($obj, data, widget, 3);
			});
	}

	/**
	 * @param {object} $obj
	 * @param {object} data
	 * @param {object} widget
	 */
	function updateWidgetConfig($obj, data, widget) {
		if (data['options']['updating_config']) {
			// Waiting for another AJAX request to either complete of fail.
			return;
		}

		var	fields = $('form', data.dialogue['body']).serializeJSON(),
			type = fields['type'],
			name = fields['name'],
			view_mode = (fields['show_header'] == 1) ? ZBX_WIDGET_VIEW_MODE_NORMAL : ZBX_WIDGET_VIEW_MODE_HIDDEN_HEADER,
			pos;

		delete fields['type'];
		delete fields['name'];
		delete fields['show_header'];

		if (widget === null || !('type' in widget) && !('pos' in widget)) {
			pos = findEmptyPosition($obj, data, type);
			if (!pos) {
				showMessageExhausted(data);
				return;
			}
		}

		data['options']['updating_config'] = true;

		// Prepare to call dashboard.widget.check.

		var url = new Curl('zabbix.php');
		url.setArgument('action', 'dashboard.widget.check');

		var ajax_data = {
				type: type,
				name: name,
				view_mode: view_mode
			};

		if (Object.keys(fields).length != 0) {
			ajax_data['fields'] = JSON.stringify(fields);
		}

		var $save_btn = data.dialogue.div.find('.dialogue-widget-save'),
			overlay = overlays_stack.getById('widgetConfg');

		$save_btn.prop('disabled', true);
		overlay.xhr = $.ajax({
			url: url.getUrl(),
			method: 'POST',
			dataType: 'json',
			data: ajax_data
		});

		overlay.xhr
			.then(function(response) {
				if (typeof(response.errors) !== 'undefined') {
					// Error returned. Remove previous errors.

					$('.msg-bad', data.dialogue['body']).remove();
					data.dialogue['body'].prepend(response.errors);
					$save_btn.prop('disabled', false);

					return $.Deferred().reject();
				}
				else {
					// Set view mode of a reusable widget early to escape focus flickering.
					if (widget !== null && widget['type'] === type) {
						setWidgetViewMode(widget, view_mode);

						doLeaveWidgetsExcept($obj, data, widget);
						doEnterWidget($obj, data, widget);
					}
				}
			})
			.then(function() {
				// Prepare to call dashboard.widget.configure.

				var url = new Curl('zabbix.php');
				url.setArgument('action', 'dashboard.widget.configure');

				var ajax_data = {
						type: type,
						view_mode: view_mode
					};

				if (Object.keys(fields).length != 0) {
					ajax_data['fields'] = JSON.stringify(fields);
				}

				return $.ajax({
					url: url.getUrl(),
					method: 'POST',
					dataType: 'json',
					data: ajax_data
				});
			})
			.then(function(response) {
				overlayDialogueDestroy('widgetConfg');

				var configuration = {};
				if ('configuration' in response) {
					configuration = response['configuration'];
				}

				if (widget === null || !('type' in widget)) {
					// In case of ADD widget, create and add widget to the dashboard.

					if (widget && 'pos' in widget) {
						pos = $.extend({}, data.widget_defaults[type].size, widget.pos);

						$.map(data.widgets, function(box) {
							return rectOverlap(box.pos, pos) ? box : null;
						}).each(function(box) {
							if (!rectOverlap(box.pos, pos)) {
								return;
							}

							if (pos.x + pos.width > box.pos.x && pos.x < box.pos.x) {
								pos.width = box.pos.x - pos.x;
							}
							else if (pos.y + pos.height > box.pos.y && pos.y < box.pos.y) {
								pos.height = box.pos.y - pos.y;
							}
						});

						pos.width = Math.min(data.options['max-columns'] - pos.x, pos.width);
						pos.height = Math.min(data.options['max-rows'] - pos.y, pos.height);
					}

					var widget_data = {
							'type': type,
							'header': name,
							'view_mode': view_mode,
							'pos': pos,
							'fields': fields,
							'configuration': configuration
						};

					if (pos['y'] + pos['height'] > data['options']['rows']) {
						resizeDashboardGrid($obj, data, pos['y'] + pos['height']);

						// Body height should be adjusted to animate scrollTop work.
						$('body').css('height', Math.max(
							$('body').height(), (pos['y'] + pos['height']) * data['options']['widget-height']
						));
					}

					// 5px shift is widget padding.
					$('html, body')
						.animate({scrollTop: pos['y'] * data['options']['widget-height']
							+ $('.dashbrd-grid-container').position().top - 5})
						.promise()
						.then(function() {
							methods.addWidget.call($obj, widget_data);

							// New widget is last element in data['widgets'] array.
							widget = data['widgets'].slice(-1)[0];
							setWidgetModeEdit($obj, data, widget);
							updateWidgetContent($obj, data, widget);

							// Remove height attribute set for scroll animation.
							$('body').css('height', '');
						});
				}
				else if (widget['type'] === type) {
					// In case of EDIT widget, if type has not changed, update the widget.

					widget['header'] = name;
					widget['fields'] = fields;

					// Set preloader to widget content after overlayDialogueDestroy as fast as we can.
					startPreloader(widget, 100);

					// View mode was just set after the overlayDialogueDestroy was called in first 'then' section.

					applyWidgetConfiguration($obj, data, widget, configuration);
					doAction('afterUpdateWidgetConfig', $obj, data, null);
					updateWidgetDynamic($obj, data, widget);

					if (widget['iterator']) {
						updateWidgetContent($obj, data, widget, {
							'update_policy': 'resize_or_refresh'
						});
					}
					else {
						updateWidgetContent($obj, data, widget);
					}
				} else {
					// In case of EDIT widget, if type has changed, replace the widget.

					removeWidget($obj, data, widget);

					var widget_data = {
							'type': type,
							'header': name,
							'view_mode': view_mode,
							'pos': widget['pos'],
							'fields': fields,
							'configuration': configuration,
							'new_widget': false
						};

					// Disable position/size checking during addWidget call.
					data['pos-action'] = 'updateWidgetConfig';
					methods.addWidget.call($obj, widget_data);
					data['pos-action'] = '';

					// New widget is last element in data['widgets'] array.
					widget = data['widgets'].slice(-1)[0];
					setWidgetModeEdit($obj, data, widget);
					updateWidgetContent($obj, data, widget);
				}

				// Mark dashboard as updated.
				data['options']['updated'] = true;
			})
			.always(function() {
				$save_btn.prop('disabled', false);
				delete data['options']['updating_config'];
			});
	}

	function findEmptyPosition($obj, data, type) {
		var pos = {
				'x': 0,
				'y': 0,
				'width': data.widget_defaults[type].size.width,
				'height': data.widget_defaults[type].size.height
			};

		// Go y by row and try to position widget in each space.
		var	max_col = data.options['max-columns'] - pos.width,
			max_row = data.options['max-rows'] - pos.height,
			found = false,
			x, y;

		for (y = 0; !found; y++) {
			if (y > max_row) {
				return false;
			}
			for (x = 0; x <= max_col && !found; x++) {
				pos['x'] = x;
				pos['y'] = y;
				found = isPosFree($obj, data, pos);
			}
		}

		return pos;
	}

	function isPosFree($obj, data, pos) {
		var free = true;

		$.each(data['widgets'], function() {
			if (rectOverlap(pos, this['pos'])) {
				free = false;
			}
		});

		return free;
	}

	function openConfigDialogue($obj, data, widget, trigger_elmnt) {
		data['options']['config_dialogue_active'] = true;

		var config_dialogue_close = function() {
			delete data['options']['config_dialogue_active'];
			$.unsubscribe('overlay.close', config_dialogue_close);
		};
		$.subscribe('overlay.close', config_dialogue_close);

		var edit_mode = (widget !== null && 'type' in widget);

		data.dialogue = {};
		data.dialogue.widget = widget;

		overlayDialogue({
			'title': (edit_mode ? t('Edit widget') : t('Add widget')),
			'content': '',
			'buttons': [
				{
					'title': (edit_mode ? t('Apply') : t('Add')),
					'class': 'dialogue-widget-save',
					'keepOpen': true,
					'action': function() {
						updateWidgetConfig($obj, data, widget);
					}
				},
				{
					'title': t('Cancel'),
					'class': 'btn-alt',
					'action': function() {}
				}
			],
			'dialogueid': 'widgetConfg'
		}, trigger_elmnt);

		var overlay_dialogue = $('#overlay_dialogue');
		data.dialogue.div = overlay_dialogue;
		data.dialogue.body = $('.overlay-dialogue-body', overlay_dialogue);

		updateWidgetConfigDialogue();
	}

	/**
	 * Creates placeholder object for 'Add a new Widget'.
	 *
	 * @returns {object}    Placeholder object with DOM elements and additional methods to set visual style.
	 */
	function createNewWidgetPlaceholder() {
		var $label = $('<div>', {'class': 'dashbrd-grid-new-widget-label'}),
			$inner_box = $('<div>', {'class': 'dashbrd-grid-widget-new-box'}).append($label),
			$placeholder = $('<div>', {'class': 'dashbrd-grid-new-widget-placeholder'}).append($inner_box),
			updateLabelVisibility = function() {
				if (!$inner_box.is('.dashbrd-grid-widget-set-size,.dashbrd-grid-widget-set-position')) {
					return;
				}

				var message = $inner_box.is('.dashbrd-grid-widget-set-size')
						? t('Release to create a new widget.')
						: t('Click and drag to desired size.'),
					frame_callback,
					size_detection,
					callback = function() {
						if (size_detection.height()) {
							$label.text($label.height() >= size_detection.height() ? message : '');
							window.cancelAnimationFrame(frame_callback);
						}
						else if (size_detection.is(':visible')) {
							frame_callback = window.requestAnimationFrame(callback);
						}
					};

				// Create container to detect text overflow on y axis. Message div container will be removed on repaint.
				size_detection = $('<div>').text(message).appendTo($label);

				callback();
			};

		return {
			container: $placeholder,
			inner_box: $inner_box,
			label: $label,
			setDefault: function(callback) {
				$inner_box.removeClass('dashbrd-grid-widget-set-size dashbrd-grid-widget-set-position');
				$label.empty().append($('<a>', {
					href: '#',
					text: t('Add a new widget')
				}));

				$placeholder.click(callback);
			},
			setPositioning: function() {
				$placeholder.off('click');
				$inner_box
					.removeClass('dashbrd-grid-widget-set-size')
					.addClass('dashbrd-grid-widget-set-position');
				updateLabelVisibility();
			},
			setResizing: function() {
				$placeholder.off('click');
				$inner_box
					.removeClass('dashbrd-grid-widget-set-position')
					.addClass('dashbrd-grid-widget-set-size');
				updateLabelVisibility();
			},
			updateLabelVisibility: updateLabelVisibility
		};
	}

	function setModeEditDashboard($obj, data) {
		$obj.addClass('dashbrd-mode-edit');

		// Recaltulate minimal height and expand dashboard to the whole screen.
		data.minimalHeight = calculateGridMinHeight($obj);
		resizeDashboardGrid($obj, data);

		data['widgets'].forEach(function(widget) {
			widget['rf_rate'] = 0;
			setWidgetModeEdit($obj, data, widget);
		});

		data['pos-action'] = '';
		data['cell-width'] = getCurrentCellWidth(data);
		data['add_widget_dimension'] = {};

		// Add new widget user interaction handlers.
		$.subscribe('overlay.close', function(e, dialogue) {
			if (data['pos-action'] === 'addmodal' && dialogue.dialogueid === 'widgetConfg') {
				data['pos-action'] = '';
				data.add_widget_dimension = {};
				data.new_widget_placeholder.setDefault(function(e) {
					methods.addNewWidget.call($obj, this);
					return cancelEvent(e);
				});

				if (data.widgets.length) {
					data.new_widget_placeholder.container.hide();
					data.new_widget_placeholder.setPositioning();
				}

				resizeDashboardGrid($obj, data);

				$obj.trigger('mouseenter');
			}
		});

		$(document).on('click mouseup dragend', function() {
			if (data['pos-action'] !== 'add') {
				return;
			}

			var dimension = $.extend({}, data.add_widget_dimension);

			/*
			 * Unset if dimension width/height is equal to size of placeholder.
			 * Widget default size will be used.
			 */
			if (dimension.width == 2 && dimension.height == 2) {
				delete dimension.width;
				delete dimension.height;
			}

			data['pos-action'] = 'addmodal';
			setResizableState('enable', data.widgets, '');
			$obj.dashboardGrid('addNewWidget', null, dimension);
		});

		$obj
			.on('mousedown', function(event) {
				if (event.which != 1 || data['pos-action'] !== ''
						|| (!$(event.target).is(data.new_widget_placeholder.container)
						&& data.new_widget_placeholder.container.has(event.target).length == 0)) {
					return;
				}

				setResizableState('disable', data.widgets, '');
				data['pos-action'] = 'add';
				data.new_widget_placeholder.setResizing();

				return cancelEvent(event);
			})
			.on('mouseleave', function(event) {
				if (data['pos-action']) {
					return;
				}

				data.add_widget_dimension = {};
				data.new_widget_placeholder.setDefault(function(e) {
					methods.addNewWidget.call($obj, this);
					return cancelEvent(e);
				});

				if (data.widgets.length) {
					data.new_widget_placeholder.container.hide();
					data.new_widget_placeholder.setPositioning();
				}
				else {
					data.new_widget_placeholder.container.removeAttr('style');
				}
			})
			.on('mouseenter mousemove', function(event) {
				var drag = (data['pos-action'] === 'add'),
					$target = $(event.target);

				if (!drag && data['pos-action'] !== '') {
					return;
				}

				if (event.type === 'mouseenter' && data['pos-action'] === '') {
					data.new_widget_placeholder.container.show();
					data.new_widget_placeholder.setPositioning();
				}
				else if (!drag && !$target.is($obj) && !$target.is(data.new_widget_placeholder.container)
						&& data.new_widget_placeholder.container.has($target).length == 0) {
					resizeDashboardGrid($obj, data);
					data.add_widget_dimension = {};
					data.new_widget_placeholder.container.hide();
					return;
				}

				var offset = $obj.offset(),
					y = Math.min(data.options['max-rows'] - 1,
							Math.max(0, Math.floor((event.pageY - offset.top) / data.options['widget-height']))
						),
					x = Math.min(data.options['max-columns'] - 1,
							Math.max(0, Math.floor((event.pageX - offset.left) / data['cell-width']))
						),
					overlap = false;

				if (isNaN(x) || isNaN(y)) {
					return;
				}

				var	pos = {
						x: x,
						y: y,
						width: (x < data.options['max-columns'] - 1) ? 1 : 2,
						height: data.options['widget-min-rows']
					};

				if (drag) {
					if (('top' in data.add_widget_dimension) === false) {
						data.add_widget_dimension.left = x;
						data.add_widget_dimension.top = Math.min(y, data.add_widget_dimension.y);
					}

					pos = {
						x: Math.min(x, (data.add_widget_dimension.left < x)
							? data.add_widget_dimension.x
							: data.add_widget_dimension.left
						),
						y: Math.min(y, (data.add_widget_dimension.top < y)
							? data.add_widget_dimension.y
							: data.add_widget_dimension.top
						),
						width: Math.max(1, (data.add_widget_dimension.left < x)
							? x - data.add_widget_dimension.left + 1
							: data.add_widget_dimension.left - x + 1
						),
						height: Math.max(2, (data.add_widget_dimension.top < y)
							? y - data.add_widget_dimension.top + 1
							: data.add_widget_dimension.top - y + 2
						)
					};

					$.each(data.widgets, function(_, box) {
						overlap |= rectOverlap(box.pos, pos);

						return !overlap;
					});

					if (overlap) {
						pos = data.add_widget_dimension;
					}
				}
				else {

					if ((pos.x + pos.width) > data.options['max-columns']) {
						pos.x = data.options['max-columns'] - pos.width;
					}
					else if (data.add_widget_dimension.x < pos.x) {
						--pos.x;
					}

					if ((pos.y + pos.height) > data.options['max-rows']) {
						pos.y = data.options['max-rows'] - pos.height;
					}
					else if (data.add_widget_dimension.y < pos.y) {
						--pos.y;
					}

					/*
					 * If there is collision make additional check to ensure that mouse is not at the bottom of 1x2 free
					 * slot.
					 */
					var delta_check = [
							[0, 0, 2],
							[-1, 0, 2],
							[0, 0, 1],
							[0, -1, 2],
							[0, -1, 1]
						];

					$.each(delta_check, function(i, val) {
						var c_pos = $.extend({}, {
								x: Math.max(0, (val[2] < 2 ? x : pos.x) + val[0]),
								y: Math.max(0, pos.y + val[1]),
								width: val[2],
								height: pos.height
							});

						if (x > c_pos.x + 1) {
							++c_pos.x;
						}

						overlap = false;
						if (rectOverlap({
							x: 0,
							y: 0,
							width: data.options['max-columns'],
							height: data.options['max-rows']
						}, c_pos)) {
							$.each(data.widgets, function(_, box) {
								overlap |= rectOverlap(box.pos, c_pos);
								return !overlap;
							});
						}

						if (!overlap) {
							pos = c_pos;
							return false;
						}
					});

					if (overlap) {
						data.add_widget_dimension = {};
						data.new_widget_placeholder.container.hide();
						return;
					}
				}

				if ((pos.y + pos.height) > data.options['rows']) {
					resizeDashboardGrid($obj, data, pos.y + pos.height);
				}

				data.add_widget_dimension = $.extend(data.add_widget_dimension, pos);

				// Hide widget headers, not to interfere with the new widget placeholder.
				doLeaveWidgetsExcept($obj, data);

				data.new_widget_placeholder.container
					.css({
						position: 'absolute',
						top: (data.add_widget_dimension.y * data.options['widget-height']) + 'px',
						left: (data.add_widget_dimension.x * data.options['widget-width']) + '%',
						height: (data.add_widget_dimension.height * data.options['widget-height']) + 'px',
						width: (data.add_widget_dimension.width * data.options['widget-width']) + '%'
					})
					.show();

				data.new_widget_placeholder.updateLabelVisibility();
			});
	}

	function setWidgetModeEdit($obj, data, widget) {
		clearUpdateWidgetContentTimer(widget);

		$('.btn-widget-action', widget['content_header']).parent('li').hide();
		$('.btn-widget-delete', widget['content_header']).parent('li').show();

		if (!widget['iterator']) {
			removeWidgetInfoButtons(widget['content_header']);
		}

		makeDraggable($obj, data, widget);
		makeResizable($obj, data, widget);
		resizeWidget($obj, data, widget);
	}

	/**
	 * Remove widget actions added by addAction.
	 */
	function removeWidgetActions($obj, data, widget) {
		for (var hook_name in data['triggers']) {
			for (var index = 0; index < data['triggers'][hook_name].length; index++) {
				if (widget['uniqueid'] === data['triggers'][hook_name][index]['uniqueid']) {
					data['triggers'][hook_name].splice(index, 1);
				}
			}
		}
	}

	/**
	 * Remove the widget without updating the dashboard.
	 */
	function removeWidget($obj, data, widget) {
		if (widget['iterator']) {
			widget['children'].forEach(function(child) {
				doAction('onWidgetDelete', $obj, data, child);
				removeWidgetActions($obj, data, child);
				child['div'].remove();
			});
		}

		if (widget['parent']) {
			doAction('onWidgetDelete', $obj, data, widget);
			removeWidgetActions($obj, data, widget);
			widget['div'].remove();
		}
		else {
			var index = widget['div'].data('widget-index');

			doAction('onWidgetDelete', $obj, data, widget);
			removeWidgetActions($obj, data, widget);
			widget['div'].remove();

			data['widgets'].splice(index, 1);

			for (var i = index; i < data['widgets'].length; i++) {
				data['widgets'][i]['div'].data('widget-index', i);
			}
		}
	}

	/**
	 * Delete the widget and update the dashboard.
	 */
	function deleteWidget($obj, data, widget) {
		removeWidget($obj, data, widget);

		if (!widget['parent']) {
			data['options']['updated'] = true;

			resizeDashboardGrid($obj, data);
		}
	}

	function saveChanges($obj, data) {
		var	url = new Curl('zabbix.php'),
			ajax_widgets = [];

		// Remove previous messages.
		dashboardRemoveMessages();

		url.setArgument('action', 'dashboard.update');

		$.each(data['widgets'], function(index, widget) {
			var	ajax_widget = {};

			if (widget['widgetid'] !== '') {
				ajax_widget['widgetid'] = widget['widgetid'];
			}
			ajax_widget['pos'] = widget['pos'];
			ajax_widget['type'] = widget['type'];
			ajax_widget['name'] = widget['header'];
			ajax_widget['view_mode'] = widget['view_mode'];
			if (Object.keys(widget['fields']).length != 0) {
				ajax_widget['fields'] = JSON.stringify(widget['fields']);
			}

			ajax_widgets.push(ajax_widget);
		});

		var ajax_data = {
				// Can be undefined if dashboard is new.
				dashboardid: data['dashboard']['id'],
				name: data['dashboard']['name'],
				userid: data['dashboard']['userid'],
				widgets: ajax_widgets
			};

		if (isset('sharing', data['dashboard'])) {
			ajax_data['sharing'] = data['dashboard']['sharing'];
		}

		$.ajax({
			url: url.getUrl(),
			method: 'POST',
			dataType: 'json',
			data: ajax_data
		})
			.done(function(response) {
				// We can have redirect with errors.
				if ('redirect' in response) {
					// There are no more unsaved changes.
					data['options']['updated'] = false;
					/*
					 * Replace add possibility to remove previous url (as ..&new=1) from the document history.
					 * It allows to use back browser button more user-friendly.
					 */
					window.location.replace(response.redirect);
				}
				else if ('errors' in response) {
					// Error returned.
					dashboardAddMessages(response.errors);
				}
			})
			.always(function() {
				var ul = $('#dashbrd-config').closest('ul');
				$('#dashbrd-save', ul).prop('disabled', false);
			});
	}

	function confirmExit($obj, data) {
		if (data['options']['updated'] === true) {
			return t('You have unsaved changes.') + "\n" + t('Are you sure, you want to leave this page?');
		}
	}

	function updateWidgetDynamic($obj, data, widget) {
		// This function may be called for widget that is not in data['widgets'] array yet.
		if (typeof widget['fields']['dynamic'] !== 'undefined') {
			if (widget['fields']['dynamic'] == 1 && data['dashboard']['dynamic']['has_dynamic_widgets'] === true) {
				widget['dynamic'] = {
					'hostid': data['dashboard']['dynamic']['hostid'],
					'groupid': data['dashboard']['dynamic']['groupid']
				};
			}
			else {
				delete widget['dynamic'];
			}
		}
	}

	function generateUniqueId($obj, data) {
		var ref = false;

		while (!ref) {
			ref = generateRandomString(5);

			$.each(data['widgets'], function(index, widget) {
				if (widget['uniqueid'] === ref) {
					ref = false;
					return false;
				}
			});
		}

		return ref;
	}

	function onIteratorResizeEnd($obj, data, iterator) {
		updateIteratorPager(iterator);

		if (getIteratorTooSmallState(iterator)) {
			return;
		}

		updateWidgetContent($obj, data, iterator, {
			'update_policy': 'resize'
		});
	}

	function resizeWidget($obj, data, widget) {
		var success = false;

		if (widget['iterator']) {
			// Iterators will sync first, then selectively propagate the resize event to the child widgets.
			success = doAction('onResizeEnd', $obj, data, widget);
		}
		else {
			var size_old = widget['content_size'],
				size_new = getWidgetContentSize(widget);

			if (!isEqualContentSize(size_old, size_new)) {
				success = doAction('onResizeEnd', $obj, data, widget);
				if (success) {
					widget['content_size'] = size_new;
				}
			}
		}

		return success;
	}

	/**
	 * Show message if dashboard free space exhausted.
	 *
	 * @param {object} data  Dashboard data and options object.
	 */
	function showMessageExhausted(data) {
		data.dialogue.body.children('.msg-warning').remove();
		data.dialogue.body.prepend(makeMessageBox(
			'warning', t('Cannot add widget: not enough free space on the dashboard.'), null, false
		));
	}

	/**
	 * Performs action added by addAction function.
	 *
	 * @param {string} hook_name  Name of trigger that is currently being called.
	 * @param {object} $obj       Dashboard container jQuery object.
	 * @param {object} data       Dashboard data and options object.
	 * @param {object} widget     Current widget object (can be null for generic actions).
	 *
	 * @returns {int}  Number of triggers, that were called.
	 */
	function doAction(hook_name, $obj, data, widget) {
		if (typeof data['triggers'][hook_name] === 'undefined') {
			return 0;
		}
		var triggers = [];

		if (widget === null) {
			triggers = data['triggers'][hook_name];
		}
		else {
			$.each(data['triggers'][hook_name], function(index, trigger) {
				if (widget['uniqueid'] === trigger['uniqueid']) {
					triggers.push(trigger);
				}
			});
		}

		triggers.sort(function(a,b) {
			var priority_a = (typeof a['options']['priority'] !== 'undefined') ? a['options']['priority'] : 10,
				priority_b = (typeof b['options']['priority'] !== 'undefined') ? b['options']['priority'] : 10;

			if (priority_a < priority_b) {
				return -1;
			}
			if (priority_a > priority_b) {
				return 1;
			}
			return 0;
		});

		$.each(triggers, function(index, trigger) {
			var trigger_function = null;
			if (typeof trigger['function'] === typeof Function) {
				// A function given?
				trigger_function = trigger['function'];
			}
			else if (typeof window[trigger['function']] === typeof Function) {
				// A name of function given?
				trigger_function = window[trigger['function']];
			}

			if (trigger_function === null) {
				return true;
			}

			var params = [];
			if (typeof trigger['options']['parameters'] !== 'undefined') {
				params = trigger['options']['parameters'];
			}

			if (typeof trigger['options']['grid'] !== 'undefined') {
				var grid = {};
				if (typeof trigger['options']['grid']['widget'] !== 'undefined'
						&& trigger['options']['grid']['widget']
				) {
					if (widget === null) {
						var widgets = methods.getWidgetsBy.call($obj, 'uniqueid', trigger['uniqueid']);
						// Will return only first element.
						if (widgets.length > 0) {
							grid['widget'] = widgets[0];
						}
					}
					else {
						grid['widget'] = widget;
					}
				}
				if (typeof trigger['options']['grid']['data'] !== 'undefined' && trigger['options']['grid']['data']) {
					grid['data'] = data;
				}
				if (typeof trigger['options']['grid']['obj'] !== 'undefined' && trigger['options']['grid']['obj']) {
					grid['obj'] = $obj;
				}
				params.push(grid);
			}

			try {
				trigger_function.apply(null, params);
			}
			catch(e) {}
		});

		return triggers.length;
	}

	var	methods = {
		init: function(options) {
			options = $.extend({
				'widget-height': 70,
				'rows': 0,
				'updated': false
			}, options);

			options['widget-width'] = 100 / options['max-columns'];

			return this.each(function() {
				var	$this = $(this),
					new_widget_placeholder = createNewWidgetPlaceholder(),
					placeholder = $('<div>', {'class': 'dashbrd-grid-widget-placeholder'}).append($('<div>')).hide();

				if (options['editable']) {
					if (options['kioskmode']) {
						new_widget_placeholder.label.text(t('Cannot add widgets in kiosk mode'));
						new_widget_placeholder.container.addClass('disabled');
					}
					else {
						new_widget_placeholder.setDefault(function(e) {
							if (!methods.isEditMode.call($this)) {
								showEditMode();
							}

							methods.addNewWidget.call($this, this);
							return cancelEvent(e);
						});
					}
				}
				else {
					new_widget_placeholder.label.text(t('You do not have permissions to edit dashboard'));
					new_widget_placeholder.container.addClass('disabled');
				}

				$this.append(new_widget_placeholder.container, placeholder);

				$this.data('dashboardGrid', {
					dashboard: {},
					options: options,
					widget_defaults: {},
					widgets: [],
					triggers: {},
					placeholder: placeholder,
					new_widget_placeholder: new_widget_placeholder,
					widget_relation_submissions: [],
					widget_relations: {
						relations: [],
						tasks: {}
					},
					data_buffer: [],
					minimalHeight: calculateGridMinHeight($this)
				});

				var	data = $this.data('dashboardGrid'),
					resize_timeout;

				$(window)
					.on('beforeunload', function() {
						var	res = confirmExit($this, data);

						// Return value only if we need confirmation window, return nothing otherwise.
						if (typeof res !== 'undefined') {
							return res;
						}
					})
					.on('resize', function() {
						clearTimeout(resize_timeout);
						resize_timeout = setTimeout(function() {
							data.widgets.each(function(widget) {
								resizeWidget($this, data, widget);
							});
						}, 200);

						// Recalculate dashboard container minimal required height.
						data.minimalHeight = calculateGridMinHeight($this);
						data['cell-width'] = getCurrentCellWidth(data);
						data.new_widget_placeholder.updateLabelVisibility();
					});
			});
		},

		setDashboardData: function(dashboard) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				if (!$.isEmptyObject(data['dashboard']) && (data['dashboard']['name'] !== dashboard['name']
						|| data['dashboard']['userid'] !== dashboard['userid'])) {
					data['options']['updated'] = true;
				}

				dashboard = $.extend({}, data['dashboard'], dashboard);
				data['dashboard'] = dashboard;
			});
		},

		setWidgetDefaults: function(defaults) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				defaults = $.extend({}, data['widget_defaults'], defaults);
				data['widget_defaults'] = defaults;
			});
		},

		addWidget: function(widget) {
			// Replace empty arrays (or anything non-object) with empty objects.
			if (typeof widget['fields'] !== 'object') {
				widget['fields'] = {};
			}
			if (typeof widget['configuration'] !== 'object') {
				widget['configuration'] = {};
			}

			widget = $.extend({
				'widgetid': '',
				'type': '',
				'header': '',
				'view_mode': ZBX_WIDGET_VIEW_MODE_NORMAL,
				'pos': {
					'x': 0,
					'y': 0,
					'width': 1,
					'height': 1
				},
				'rf_rate': 0,
				'preloader_timeout': 10000,	// in milliseconds
				'preloader_fadespeed': 500,
				'update_paused': false,
				'initial_load': true,
				'ready': false,
				'storage': {}
			}, widget, {
				'parent': false
			});

			if (typeof widget['new_widget'] === 'undefined') {
				widget['new_widget'] = !widget['widgetid'].length;
			}

			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid'),
					widget_local = JSON.parse(JSON.stringify(widget)),
					widget_type_defaults = data['widget_defaults'][widget_local['type']];

				widget_local['iterator'] = widget_type_defaults['iterator'];

				if (widget_local['iterator']) {
					$.extend(widget_local, {
						'page': 1,
						'page_count': 1,
						'children': [],
						'update_pending': false
					});
				}

				widget_local['uniqueid'] = generateUniqueId($this, data);
				widget_local['div'] = makeWidgetDiv($this, data, widget_local);
				widget_local['div'].data('widget-index', data['widgets'].length);

				updateWidgetDynamic($this, data, widget_local);

				data['widgets'].push(widget_local);
				$this.append(widget_local['div']);

				setDivPosition(widget_local['div'], data, widget_local['pos']);

				if (data['pos-action'] !== 'updateWidgetConfig') {
					checkWidgetOverlap(data);
					resizeDashboardGrid($this, data);
				}

				showPreloader(widget_local);
				data.new_widget_placeholder.container.hide();

				if (widget_local['iterator']) {
					// Placeholders will be shown while the iterator will be loading.
					addIteratorPlaceholders($this, data, widget_local,
						numIteratorColumns(widget_local) * numIteratorRows(widget_local)
					);
					alignIteratorContents($this, data, widget_local, widget_local['pos']);

					$this.dashboardGrid('addAction', 'onResizeEnd', onIteratorResizeEnd, widget_local['uniqueid'], {
						parameters: [$this, data, widget_local],
						trigger_name: 'onIteratorResizeEnd_' + widget_local['uniqueid']
					});
				}
			});
		},

		setWidgetRefreshRate: function(widgetid, rf_rate) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (widget['widgetid'] == widgetid) {
						widget['rf_rate'] = rf_rate;
						setUpdateWidgetContentTimer($this, data, widget);
					}
				});
			});
		},

		refreshWidget: function(widgetid) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (widget['widgetid'] == widgetid || widget['uniqueid'] === widgetid) {
						updateWidgetContent($this, data, widget);
					}
				});
			});
		},

		// Pause specific widget refresh.
		pauseWidgetRefresh: function(widgetid) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (widget['widgetid'] == widgetid || widget['uniqueid'] === widgetid) {
						widget['update_paused'] = true;
						return false;
					}
				});
			});
		},

		// Unpause specific widget refresh.
		unpauseWidgetRefresh: function(widgetid) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (widget['widgetid'] == widgetid || widget['uniqueid'] === widgetid) {
						widget['update_paused'] = false;
						return false;
					}
				});
			});
		},

		setWidgetStorageValue: function(uniqueid, field, value) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (widget['uniqueid'] === uniqueid) {
						widget['storage'][field] = value;
					}
				});
			});
		},

		addWidgets: function(widgets) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				$.each(widgets, function() {
					methods.addWidget.apply($this, Array.prototype.slice.call(arguments, 1));
				});

				$.each(data['widgets'], function(index, value) {
					updateWidgetContent($this, data, value);
				});
			});
		},

		// Make widgets editable - Header icons, Resizeable, Draggable.
		setModeEditDashboard: function() {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				data['options']['edit_mode'] = true;
				doAction('onEditStart', $this, data, null);
				dashboardRemoveMessages();
				setModeEditDashboard($this, data);
			});
		},

		// Save changes and remove editable elements from widget - Header icons, Resizeable, Draggable.
		saveDashboardChanges: function() {
			return this.each(function() {
				var	$this = $(this),
					ul = $('#dashbrd-config').closest('ul'),
					data = $this.data('dashboardGrid');

				$('#dashbrd-save', ul).prop('disabled', true);
				doAction('beforeDashboardSave', $this, data, null);
				saveChanges($this, data);
			});
		},

		// Discard changes and remove editable elements from widget - Header icons, Resizeable, Draggable.
		cancelEditDashboard: function() {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid'),
					current_url = new Curl(),
					url = new Curl('zabbix.php', false);

				// Don't show warning about existing updates.
				data['options']['updated'] = false;

				url.setArgument('action', 'dashboard.view');
				if (current_url.getArgument('dashboardid')) {
					url.setArgument('dashboardid', current_url.getArgument('dashboardid'));
				}

				// Redirect to last active dashboard.
				// (1) In case of New Dashboard from list, it will open list
				// (2) In case of New Dashboard or Clone Dashboard from other dashboard, it will open that dashboard
				// (3) In case of simple editing of current dashboard, it will reload same dashboard
				location.replace(url.getUrl());
			});
		},

		// After pressing "Edit" button on widget.
		editWidget: function(widget, trigger_elmnt) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				openConfigDialogue($this, data, widget, trigger_elmnt);
			});
		},

		// After pressing "delete" button on widget.
		deleteWidget: function(widget) {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				deleteWidget($this, data, widget);
			});
		},

		/*
		 * Add or update form on widget configuration dialogue (when opened, as well as when requested by 'onchange'
		 * attributes in form itself).
		 */
		updateWidgetConfigDialogue: function() {
			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid'),
					body = data.dialogue['body'],
					footer = $('.overlay-dialogue-footer', data.dialogue['div']),
					header = $('.dashbrd-widget-head', data.dialogue['div']),
					form = $('form', body),
					widget = data.dialogue['widget'], // Widget currently being edited.
					url = new Curl('zabbix.php'),
					ajax_data = {},
					fields;

				// Disable saving, while form is being updated.
				$('.dialogue-widget-save', footer).prop('disabled', true);

				url.setArgument('action', 'dashboard.widget.edit');

				if (form.length) {
					// Take values from form.
					fields = form.serializeJSON();
					ajax_data['type'] = fields['type'];
					ajax_data['prev_type'] = data.dialogue['widget_type'];
					delete fields['type'];

					if (ajax_data['prev_type'] === ajax_data['type']) {
						ajax_data['name'] = fields['name'];
						ajax_data['view_mode'] = (fields['show_header'] == 1)
							? ZBX_WIDGET_VIEW_MODE_NORMAL
							: ZBX_WIDGET_VIEW_MODE_HIDDEN_HEADER;

						delete fields['name'];
						delete fields['show_header'];
					}
					else {
						// Get default config if widget type changed.
						fields = {};
					}
				}
				else if (widget !== null) {
					// Open form with current config.
					ajax_data['type'] = widget['type'];
					ajax_data['name'] = widget['header'];
					ajax_data['view_mode'] = widget['view_mode'];
					fields = widget['fields'];
				}
				else {
					// Get default config for new widget.
					fields = {};
				}

				if (Object.keys(fields).length != 0) {
					ajax_data['fields'] = JSON.stringify(fields);
				}

				jQuery.ajax({
					url: url.getUrl(),
					method: 'POST',
					data: ajax_data,
					dataType: 'json',
					beforeSend: function() {
						/*
						 * Clear the 'sticked-to-top' class before updating the body for it's mutation handler
						 * to center the popup while the widget form is being loaded.
						 */
						jQuery('[data-dialogueid="widgetConfg"]').removeClass('sticked-to-top');

						body.empty()
							.append($('<div>')
								// The smallest possible size of configuration dialog.
								.css({
									'width': '544px',
									'height': '68px',
									'max-width': '100%'
								})
								.append($('<div>')
									.addClass('preloader-container')
									.append($('<div>').addClass('preloader'))
								));
					}
				})
					.done(function(response) {
						data.dialogue['widget_type'] = response.type;

						/*
						 * Set the 'sticked-to-top' class before updating the body for it's mutation handler
						 * to have actual data for the popup positioning.
						 */
						if (response.options.stick_to_top) {
							jQuery('[data-dialogueid="widgetConfg"]').addClass('sticked-to-top');
						}

						body.empty();
						body.append(response.body);
						if (typeof response.debug !== 'undefined') {
							body.append(response.debug);
						}
						if (typeof response.messages !== 'undefined') {
							body.append(response.messages);
						}

						body.find('form').attr('aria-labeledby', header.find('h4').attr('id'));

						// Change submit function for returned form.
						$('#widget_dialogue_form', body).on('submit', function(e) {
							e.preventDefault();
							updateWidgetConfig($this, data, widget);
						});

						if (widget === null && !findEmptyPosition($this, data, data.dialogue['widget_type'])) {
							showMessageExhausted(data);
						}
						else {
							// Enable save button after successful form update.
							$('.dialogue-widget-save', footer).prop('disabled', false);
						}

						overlayDialogueOnLoad(true, jQuery('[data-dialogueid="widgetConfg"]'));
					});
			});
		},

		// Returns list of widgets filterd by key=>value pair
		getWidgetsBy: function(key, value) {
			var widgets_found = [];
			this.each(function() {
				var	$this = $(this),
						data = $this.data('dashboardGrid');

				$.each(data['widgets'], function(index, widget) {
					if (typeof widget[key] !== 'undefined' && widget[key] === value) {
						widgets_found.push(widget);
					}
				});
			});

			return widgets_found;
		},

		// Register widget as data receiver shared by other widget.
		registerDataExchange: function(obj) {
			return this.each(function() {
				var $this = $(this),
					data = $this.data('dashboardGrid');

				data['widget_relation_submissions'].push(obj);
			});
		},

		registerDataExchangeCommit: function() {
			return this.each(function() {
				var $this = $(this),
					used_indexes = [],
					data = $this.data('dashboardGrid'),
					erase;

				$.each(data['widget_relation_submissions'], function(rel_index, rel) {
					erase = false;

					// No linked widget reference given. Just register as data receiver.
					if (typeof rel.linkedto === 'undefined') {
						if (typeof data['widget_relations']['tasks'][rel.uniqueid] === 'undefined') {
							data['widget_relations']['tasks'][rel.uniqueid] = [];
						}

						data['widget_relations']['tasks'][rel.uniqueid].push({
							data_name: rel.data_name,
							callback: rel.callback
						});
						erase = true;
					}
					/*
					 * Linked widget reference is given. Register two direction relationship as well as
					 * register data receiver.
					 */
					else {
						$.each(data['widgets'], function(index, widget) {
							if (typeof widget['fields']['reference'] !== 'undefined'
									&& widget['fields']['reference'] === rel.linkedto) {
								if (typeof data['widget_relations']['relations'][widget.uniqueid] === 'undefined') {
									data['widget_relations']['relations'][widget.uniqueid] = [];
								}
								if (typeof data['widget_relations']['relations'][rel.uniqueid] === 'undefined') {
									data['widget_relations']['relations'][rel.uniqueid] = [];
								}
								if (typeof data['widget_relations']['tasks'][rel.uniqueid] === 'undefined') {
									data['widget_relations']['tasks'][rel.uniqueid] = [];
								}

								data['widget_relations']['relations'][widget.uniqueid].push(rel.uniqueid);
								data['widget_relations']['relations'][rel.uniqueid].push(widget.uniqueid);
								data['widget_relations']['tasks'][rel.uniqueid].push({
									data_name: rel.data_name,
									callback: rel.callback
								});
								erase = true;
							}
						});
					}

					if (erase) {
						used_indexes.push(rel_index);
					}
				});

				for (var i = used_indexes.length - 1; i >= 0; i--) {
					data['widget_relation_submissions'].splice(used_indexes[i], 1);
				}

				methods.callWidgetDataShare.call($this);
			});
		},

		/**
		 * Pushes received data in data buffer and calls sharing method.
		 *
		 * @param {object} widget     Data origin widget
		 * @param {string} data_name  String to identify data shared
		 *
		 * @returns {boolean}  Indicates either there was linked widget that was related to data origin widget
		 */
		widgetDataShare: function(widget, data_name) {
			var args = Array.prototype.slice.call(arguments, 2),
				uniqueid = widget['uniqueid'],
				ret = true;

			if (!args.length) {
				return false;
			}

			this.each(function() {
				var $this = $(this),
					data = $this.data('dashboardGrid'),
					indx = -1;

				if (typeof data['widget_relations']['relations'][widget['uniqueid']] === 'undefined'
						|| data['widget_relations']['relations'][widget['uniqueid']].length == 0) {
					ret = false;
				}

				if (typeof data['data_buffer'][uniqueid] === 'undefined') {
					data['data_buffer'][uniqueid] = [];
				}
				else if (typeof data['data_buffer'][uniqueid] !== 'undefined') {
					$.each(data['data_buffer'][uniqueid], function(i, arr) {
						if (arr['data_name'] === data_name) {
							indx = i;
						}
					});
				}

				if (indx === -1) {
					data['data_buffer'][uniqueid].push({
						data_name: data_name,
						args: args,
						old: []
					});
				}
				else {
					if (data['data_buffer'][uniqueid][indx]['args'] !== args) {
						data['data_buffer'][uniqueid][indx]['args'] = args;
						data['data_buffer'][uniqueid][indx]['old'] = [];
					}
				}

				methods.callWidgetDataShare.call($this);
			});

			return ret;
		},

		callWidgetDataShare: function($obj) {
			return this.each(function() {
				var $this = $(this),
					data = $this.data('dashboardGrid');

				for (var src_uniqueid in data['data_buffer']) {
					if (typeof data['data_buffer'][src_uniqueid] === 'object') {
						$.each(data['data_buffer'][src_uniqueid], function(index, buffer_data) {
							if (typeof data['widget_relations']['relations'][src_uniqueid] !== 'undefined') {
								$.each(data['widget_relations']['relations'][src_uniqueid], function(index,
										dest_uid) {
									if (buffer_data['old'].indexOf(dest_uid) == -1) {
										if (typeof data['widget_relations']['tasks'][dest_uid] !== 'undefined') {
											var widget = methods.getWidgetsBy.call($this, 'uniqueid', dest_uid);
											if (widget.length) {
												$.each(data['widget_relations']['tasks'][dest_uid], function(i, task) {
													if (task['data_name'] === buffer_data['data_name']) {
														task.callback.apply($obj, [widget[0], buffer_data['args']]);
													}
												});

												buffer_data['old'].push(dest_uid);
											}
										}
									}
								});
							}
						});
					}
				}
			});
		},

		makeReference: function() {
			var ref = false;

			this.each(function() {
				var data = $(this).data('dashboardGrid');

				while (!ref) {
					ref = generateRandomString(5);

					for (var i = 0, l = data['widgets'].length; l > i; i++) {
						if (typeof data['widgets'][i]['fields']['reference'] !== 'undefined') {
							if (data['widgets'][i]['fields']['reference'] === ref) {
								ref = false;
								break;
							}
						}
					}
				}
			});

			return ref;
		},

		addNewWidget: function(trigger_elmnt, pos) {
			var widget = (pos && 'x' in pos && 'y' in pos) ? {pos: pos} : null;

			return this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid');

				openConfigDialogue($this, data, widget, trigger_elmnt);
			});
		},

		isEditMode: function() {
			var response = false;

			this.each(function() {
				response = $(this).data('dashboardGrid')['options']['edit_mode'];
			});

			return response;
		},

		/**
		 * Add action, that will be performed on $hook_name trigger.
		 *
		 * @param {string} hook_name                  Name of trigger, when $function_to_call should be called.
		 * @param {string} function_to_call           Name of function in global scope that will be called.
		 * @param {string} uniqueid                   Identifier of widget, that added this action.
		 * @param {array}  options                    Any key in options is optional.
		 * @param {array}  options['parameters']      Array of parameters with which the function will be called.
		 * @param {array}  options['grid']            Mark, what data from grid should be passed to $function_to_call.
		 *                                            If is empty, parameter 'grid' will not be added to function_to_call params.
		 * @param {string} options['grid']['widget']  Should contain 1. Will add widget object.
		 * @param {string} options['grid']['data']    Should contain '1'. Will add dashboard grid data object.
		 * @param {string} options['grid']['obj']     Should contain '1'. Will add dashboard grid object ($this).
		 * @param {int}    options['priority']        Order, when it should be called, compared to others. Default = 10.
		 * @param {int}    options['trigger_name']    Unique name. There can be only one trigger with this name for each hook.
		 */
		addAction: function(hook_name, function_to_call, uniqueid, options) {
			this.each(function() {
				var	$this = $(this),
					data = $this.data('dashboardGrid'),
					found = false,
					trigger_name = null;

				if (typeof data['triggers'][hook_name] === 'undefined') {
					data['triggers'][hook_name] = [];
				}

				// Add trigger with each name only once.
				if (typeof options['trigger_name'] !== 'undefined') {
					trigger_name = options['trigger_name'];
					$.each(data['triggers'][hook_name], function(index, trigger) {
						if (typeof trigger['options']['trigger_name'] !== 'undefined'
							&& trigger['options']['trigger_name'] === trigger_name)
						{
							found = true;
						}
					});
				}

				if (!found) {
					data['triggers'][hook_name].push({
						'function': function_to_call,
						'uniqueid': uniqueid,
						'options': options
					});
				}
			});
		}
	};

	$.fn.dashboardGrid = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		}
		else {
			$.error('Invalid method "' +  method + '".');
		}
	}
}(jQuery));
