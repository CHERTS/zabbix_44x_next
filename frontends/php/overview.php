<?php
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


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';
require_once dirname(__FILE__).'/include/triggers.inc.php';
require_once dirname(__FILE__).'/include/items.inc.php';

$page['title'] = _('Overview');
$page['file'] = 'overview.php';
$page['type'] = detect_page_type(PAGE_TYPE_HTML);
$page['scripts'] = ['layout.mode.js'];

CView::$has_web_layout_mode = true;
$page['web_layout_mode'] = CView::getLayoutMode();

define('ZBX_PAGE_DO_REFRESH', 1);
define('SHOW_TRIGGERS', 0);
define('SHOW_DATA', 1);

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'groupid'     => [T_ZBX_INT, O_OPT, P_SYS, DB_ID,							null],
	'view_style'  => [T_ZBX_INT, O_OPT, P_SYS, IN(STYLE_LEFT.','.STYLE_TOP),	null],
	'type'        => [T_ZBX_INT, O_OPT, P_SYS, IN(SHOW_TRIGGERS.','.SHOW_DATA), null],
	// filter
	'filter_rst' =>			[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'filter_set' =>			[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'show_triggers' =>		[T_ZBX_INT, O_OPT, null,	null,		null],
	'ack_status' =>			[T_ZBX_INT, O_OPT, P_SYS,	null,		null],
	'show_severity' =>		[T_ZBX_INT, O_OPT, P_SYS,	null,		null],
	'show_suppressed' =>	[T_ZBX_INT, O_OPT, null,	null,		null],
	'status_change_days' =>	[T_ZBX_INT, O_OPT, null,	BETWEEN(1, DAY_IN_YEAR * 2), null],
	'status_change' =>		[T_ZBX_INT, O_OPT, null,	null,		null],
	'txt_select' =>			[T_ZBX_STR, O_OPT, null,	null,		null],
	'application' =>		[T_ZBX_STR, O_OPT, null,	null,		null],
	'inventory' =>			[T_ZBX_STR, O_OPT, null,	null,		null]
];
check_fields($fields);

/*
 * Permissions
 */
if (getRequest('groupid') && !isReadableHostGroups([getRequest('groupid')])) {
	access_deny();
}

$config = select_config();
if (hasRequest('filter_set')) {
	CProfile::update('web.overview.filter.show_triggers', getRequest('show_triggers', TRIGGERS_OPTION_RECENT_PROBLEM),
		PROFILE_TYPE_INT
	);
	CProfile::update('web.overview.filter.show_suppressed', getRequest('show_suppressed', 0), PROFILE_TYPE_INT);
	CProfile::update('web.overview.filter.show_severity', getRequest('show_severity', TRIGGER_SEVERITY_NOT_CLASSIFIED),
		PROFILE_TYPE_INT
	);
	CProfile::update('web.overview.filter.txt_select', getRequest('txt_select'), PROFILE_TYPE_STR);
	CProfile::update('web.overview.filter.status_change', getRequest('status_change', 0), PROFILE_TYPE_INT);
	CProfile::update('web.overview.filter.status_change_days', getRequest('status_change_days', 14),
		PROFILE_TYPE_INT
	);
	CProfile::update('web.overview.filter.application', getRequest('application'), PROFILE_TYPE_STR);

	// ack status
	CProfile::update('web.overview.filter.ack_status', getRequest('ack_status', 0), PROFILE_TYPE_INT);

	// update host inventory filter
	$inventoryFields = [];
	$inventoryValues = [];
	foreach (getRequest('inventory', []) as $field) {
		if ($field['value'] === '') {
			continue;
		}

		$inventoryFields[] = $field['field'];
		$inventoryValues[] = $field['value'];
	}
	CProfile::updateArray('web.overview.filter.inventory.field', $inventoryFields, PROFILE_TYPE_STR);
	CProfile::updateArray('web.overview.filter.inventory.value', $inventoryValues, PROFILE_TYPE_STR);
}
elseif (hasRequest('filter_rst')) {
	DBStart();
	CProfile::delete('web.overview.filter.show_triggers');
	CProfile::delete('web.overview.filter.show_suppressed');
	CProfile::delete('web.overview.filter.ack_status');
	CProfile::delete('web.overview.filter.show_severity');
	CProfile::delete('web.overview.filter.txt_select');
	CProfile::delete('web.overview.filter.status_change');
	CProfile::delete('web.overview.filter.status_change_days');
	CProfile::delete('web.overview.filter.application');
	CProfile::deleteIdx('web.overview.filter.inventory.field');
	CProfile::deleteIdx('web.overview.filter.inventory.value');
	DBend();
}

// overview type
if (hasRequest('type')) {
	CProfile::update('web.overview.type', getRequest('type'), PROFILE_TYPE_INT);
}
$type = CProfile::get('web.overview.type', SHOW_TRIGGERS);

// overview style
if (hasRequest('view_style')) {
	CProfile::update('web.overview.view_style', getRequest('view_style'), PROFILE_TYPE_INT);
}

$showTriggers = CProfile::get('web.overview.filter.show_triggers', TRIGGERS_OPTION_RECENT_PROBLEM);

/*
 * Display
 */
$page_filter = new CPageFilter([
	'groups' => [
		($type == SHOW_TRIGGERS ? 'with_monitored_triggers' : 'with_monitored_items') => true
	],
	'hosts' => [
		'monitored_hosts' => true,
		($type == SHOW_TRIGGERS ? 'with_monitored_triggers' : 'with_monitored_items') => true
	],
	'hostid' => getRequest('hostid'),
	'groupid' => getRequest('groupid')
]);

$data = [
	'type' => $type,
	'view_style' => CProfile::get('web.overview.view_style', STYLE_TOP),
	'config' => $config,
	'pageFilter' => $page_filter,
	'groupid' => $page_filter->groupid,
	'hostid' => $page_filter->hostid,
	'profileIdx' => 'web.overview.filter',
	'active_tab' => CProfile::get('web.overview.filter.active', 1)
];

// fetch trigger data
if ($type == SHOW_TRIGGERS) {
	// filter data
	$filter = [
		'showTriggers' => $showTriggers,
		'ackStatus' => CProfile::get('web.overview.filter.ack_status', 0),
		'showSeverity' => CProfile::get('web.overview.filter.show_severity', TRIGGER_SEVERITY_NOT_CLASSIFIED),
		'statusChange' => CProfile::get('web.overview.filter.status_change', 0),
		'statusChangeDays' => CProfile::get('web.overview.filter.status_change_days', 14),
		'txtSelect' => CProfile::get('web.overview.filter.txt_select', ''),
		'application' => CProfile::get('web.overview.filter.application', ''),
		'show_suppressed' => CProfile::get('web.overview.filter.show_suppressed', 0),
		'inventory' => []
	];
	foreach (CProfile::getArray('web.overview.filter.inventory.field', []) as $i => $field) {
		$filter['inventory'][] = [
			'field' => $field,
			'value' => CProfile::get('web.overview.filter.inventory.value', null, $i)
		];
	}

	if ($data['pageFilter']->groupsSelected) {
		$host_options = [];

		if ($filter['inventory']) {
			$host_options['searchInventory'] = [];

			foreach ($filter['inventory'] as $field) {
				$host_options['searchInventory'][$field['field']][] = $field['value'];
			}
		}

		$trigger_options = [
			'search' => ($filter['txtSelect'] !== '') ? ['description' => $filter['txtSelect']] : null,
			'only_true' => ($filter['showTriggers'] == TRIGGERS_OPTION_RECENT_PROBLEM) ? true : null,
			'filter' => [
				'value' => ($filter['showTriggers'] == TRIGGERS_OPTION_IN_PROBLEM) ? TRIGGER_VALUE_TRUE : null
			],
			'skipDependent' => ($filter['showTriggers'] == TRIGGERS_OPTION_ALL) ? null : true
		];

		$problem_options = [
			'show_recent' => ($filter['showTriggers'] == TRIGGERS_OPTION_RECENT_PROBLEM) ? true : null,
			'show_suppressed' => $filter['show_suppressed'],
			'acknowledged' => ($filter['ackStatus'] == EXTACK_OPTION_UNACK) ? false : null,
			'min_severity' => $filter['showSeverity'],
			'time_from' => $filter['statusChange'] ? (time() - $filter['statusChangeDays'] * SEC_PER_DAY) : null
		];

		$groupids = ($data['pageFilter']->groupids !== null) ? $data['pageFilter']->groupids : [];

		list($hosts, $triggers) = getTriggersOverviewData($groupids, $filter['application'], $host_options,
			$trigger_options, $problem_options
		);
	}
	else {
		$hosts = [];
		$triggers = [];
	}

	$data['filter'] = $filter;
	$data['hosts'] = $hosts;
	$data['triggers'] = $triggers;

	$overviewView = new CView('monitoring.overview.triggers', $data);
}
// fetch item data
else {
	$data['filter'] = [
		'application' => CProfile::get('web.overview.filter.application', ''),
		'show_suppressed' => CProfile::get('web.overview.filter.show_suppressed', 0)
	];

	$overviewView = new CView('monitoring.overview.items', $data);
}

// render view
$overviewView->render();
$overviewView->show();

require_once dirname(__FILE__).'/include/page_footer.php';
