<script type="text/javascript">

	jQuery(function($) {
		var $form                        = $('form[name="user_group_form"]'),
			$new_group_right_table       = $form.find('table#new-group-right-table'),
			$group_right_table_container = $form.find('table#group-right-table').parent(),
			$new_tag_filter_table        = $form.find('table#new-tag-filter-table'),
			$tag_filter_table_container  = $form.find('table#tag-filter-table').parent(),
			$ms_tag_filter_groups        = $new_tag_filter_table.find('.multiselect'),
			$ms_group_right_groups       = $new_group_right_table.find('.multiselect'),
			timeoutid_new_group_right,
			timeoutid_new_tag_filter,
			xhr_new_group_right,
			xhr_new_tag_filter;

		$form.submit(function() {
			$form.trimValues(['#name']);
		});

		/**
		 * Collects data.
		 *
		 * @return {object}
		 */
		function collectTagFilterFormData() {
			var data = {
				new_tag_filter: {groupids: []},
				tag_filters: []
			};

			$ms_tag_filter_groups.multiSelect('getData').forEach(function(ms_item) {
				data.new_tag_filter.groupids.push(ms_item.id);
			});

			data.new_tag_filter.include_subgroups = $new_tag_filter_table
				.find('[name="new_tag_filter[include_subgroups]"]').prop('checked') ? '1' : '0';

			data.new_tag_filter.tag = $new_tag_filter_table.find('[name="new_tag_filter[tag]"]').val();
			data.new_tag_filter.value = $new_tag_filter_table.find('[name="new_tag_filter[value]"]').val();

			$tag_filter_table_container.find('[name="tag_filter"]').each(function(i, node) {
				data.tag_filters.push(JSON.parse(node.value));
			});

			return data;
		}

		/**
		 * Collects data.
		 *
		 * @return {object}
		 */
		function collectGroupRightFormData() {
			var data = {
				new_group_right: {groupids: []},
				group_rights: {}
			};

			$ms_group_right_groups.multiSelect('getData').forEach(function(ms_item) {
				data.new_group_right.groupids.push(ms_item.id);
			});

			data.new_group_right.include_subgroups = $new_group_right_table
				.find('[name="new_group_right[include_subgroups]"]').prop('checked') ? '1' : '0';

			data.new_group_right.permission = $new_group_right_table
				.find('[name="new_group_right[permission]"]').filter(':checked').val();

			data.group_rights = $.extend.apply({},
				$group_right_table_container.find('[name="group_right"]').map(function(i, node) {
					var obj = JSON.parse(node.value),
						permission = jQuery(node).parent().find('input[type="radio"]').filter(':checked').val();

					if (typeof permission !== 'undefined') {
						obj[Object.keys(obj)[0]].permission = permission;
					}
					return obj;
				})
			);

			return data;
		}

		/**
		 * During long request, shows indicator and disables form elements.
		 */
		function disableNewGroupRightForm() {
			timeoutid_new_group_right = setTimeout(function() {
				$ms_group_right_groups.multiSelect('disable');
				$new_group_right_table.find('button, [name^="new_group_right"]').prop('disabled', true);
				$group_right_table_container.find('input[type="radio"]').prop('disabled', true);
			}, 150);
		}

		/**
		 * During long request, shows indicator and disables form elements.
		 */
		function disableNewTagFilterForm() {
			timeoutid_new_tag_filter = setTimeout(function() {
				$ms_tag_filter_groups.multiSelect('disable');
				$new_tag_filter_table.find('button, [name^="new_tag_filter"]').prop('disabled', true);
				$tag_filter_table_container.find('button').prop('disabled', true);
			}, 150);
		}

		/**
		 * Removes loading indicator and enables form elements.
		 */
		function enableNewGroupRightForm() {
			clearTimeout(timeoutid_new_group_right);
			$ms_group_right_groups.multiSelect('enable');
			$new_group_right_table.find('button, [name^="new_group_right"]').prop('disabled', false);
			$group_right_table_container.find('input[type="radio"]').prop('disabled', false);
		}

		/**
		 * Removes loading indicator and enables form elements.
		 */
		function enableNewTagFilterForm() {
			clearTimeout(timeoutid_new_tag_filter);
			$ms_tag_filter_groups.multiSelect('enable');
			$new_tag_filter_table.find('button, [name^="new_tag_filter"]').prop('disabled', false);
			$tag_filter_table_container.find('button').prop('disabled', false);
		}

		/**
		 * Successful response handler.
		 *
		 * @param {string} html
		 */
		function respNewGroupRight(html) {
			$ms_group_right_groups.multiSelect('clean');
			$new_group_right_table.find('[name="new_group_right[tag]"]').val('');
			$new_group_right_table.find('[name="new_group_right[value]"]').val('');
			$group_right_table_container.html(html);
		}

		/**
		 * Successful response handler.
		 *
		 * @param {string} html
		 */
		function respNewTagFilter(html) {
			$ms_tag_filter_groups.multiSelect('clean');
			$new_tag_filter_table.find('[name="new_tag_filter[tag]"]').val('');
			$new_tag_filter_table.find('[name="new_tag_filter[value]"]').val('');
			$tag_filter_table_container.html(html);
		}

		/**
		 * Response handler factory, dries up logic on how error response is recognized and handled.
		 *
		 * @param {callable} handle_data  Successful response handler.
		 *
		 * @return {callable}  Response handler.
		 */
		function respHandler(handle_data) {
			return function(resp) {
				removeMessages();
				if (resp.messages) {
					addMessage(resp.messages);
				}

				if (resp.body) {
					var	html = resp.body;

					if (resp.debug) {
						html += resp.debug;
					}

					handle_data(html);
				}
			}
		}

		/**
		 * Collects data, sends to controller for processing. On success, tag filter table is updated and form objects
		 * are removed from DOM. On failure error message is displayed. During request, loader is displayed.
		 *
		 * @param {string} action
		 */
		function submitNewTagFilter(action) {
			var url = new Curl('zabbix.php'),
				data = collectTagFilterFormData();

			url.setArgument('action', action);

			disableNewTagFilterForm();

			xhr_new_tag_filter && xhr_new_tag_filter.abort();
			xhr_new_tag_filter = $.post(url.getUrl(), data)
				.always(enableNewTagFilterForm)
				.done(respHandler(respNewTagFilter))
				.fail(function() {});
		}

		/**
		 * Collects data, sends to controller for processing. On success, permissions table is updated and form objects
		 * are removed from DOM. On failure error message is displayed. During request, loader is displayed.
		 *
		 * @param {string} action
		 */
		function submitNewGroupRight(action) {
			var url = new Curl('zabbix.php'),
				data = collectGroupRightFormData();

			url.setArgument('action', action);

			disableNewGroupRightForm();

			xhr_new_group_right && xhr_new_group_right.abort();
			xhr_new_group_right = $.post(url.getUrl(), data)
				.always(enableNewGroupRightForm)
				.done(respHandler(respNewGroupRight))
				.fail(function() {});
		}

		/**
		 * Public API.
		 */
		window.usergroups = {
			submitNewGroupRight: submitNewGroupRight,
			submitNewTagFilter: submitNewTagFilter
		};
	});
</script>
