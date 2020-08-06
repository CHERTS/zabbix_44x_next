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
require_once dirname(__FILE__).'/include/maintenances.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

$page['title'] = _('Configuration of maintenance periods');
$page['file'] = 'maintenance.php';
$page['scripts'] = ['class.calendar.js', 'multiselect.js'];

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'hostids' =>							[T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null],
	'groupids' =>							[T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null],
	'groupid' =>							[T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null],
	// maintenance
	'maintenanceid' =>						[T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		'isset({form}) && {form} == "update"'],
	'maintenanceids' =>						[T_ZBX_INT, O_OPT, P_SYS,	DB_ID, 		null],
	'mname' =>								[T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'isset({add}) || isset({update})', _('Name')],
	'maintenance_type' =>					[T_ZBX_INT, O_OPT, null,	null,		'isset({add}) || isset({update})'],
	'description' =>						[T_ZBX_STR, O_OPT, null,	null,		'isset({add}) || isset({update})'],
	'active_since' =>						[T_ZBX_ABS_TIME, O_OPT, null, NOT_EMPTY,
												'isset({add}) || isset({update})', _('Active since')
											],
	'active_till' =>						[T_ZBX_ABS_TIME, O_OPT, null, NOT_EMPTY,
												'isset({add}) || isset({update})', _('Active till')
											],
	'new_timeperiod_start_date' =>			[T_ZBX_ABS_TIME, O_OPT, null, 	NOT_EMPTY,	null, _('Date')],
	'new_timeperiod' =>						[T_ZBX_STR, O_OPT, null,	null,		'isset({add_timeperiod})'],
	'timeperiods' =>						[T_ZBX_STR, O_OPT, null,	null,		null],
	'del_timeperiodid' =>					[T_ZBX_STR, O_OPT, P_ACT,	null,		null],
	'edit_timeperiodid' =>					[T_ZBX_STR, O_OPT, P_ACT,	null,		null],
	'tags_evaltype' =>						[T_ZBX_INT, O_OPT, null,	null,		null],
	'tags' =>								[T_ZBX_STR, O_OPT, null,	null,		null],
	// actions
	'action' =>								[T_ZBX_STR, O_OPT, P_SYS|P_ACT, IN('"maintenance.massdelete"'), null],
	'add_timeperiod' =>						[T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null],
	'cancel_new_timeperiod' =>				[T_ZBX_STR, O_OPT, P_SYS, null,	null],
	'add' =>								[T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null],
	'update' =>								[T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null],
	'clone' =>								[T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null],
	'delete' =>								[T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null],
	'cancel' =>								[T_ZBX_STR, O_OPT, P_SYS,		 null,	null],
	// form
	'form' =>								[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'form_refresh' =>						[T_ZBX_INT, O_OPT, null,	null,		null],
	// filter
	'filter_set' =>							[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'filter_rst' =>							[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'filter_name' =>						[T_ZBX_STR, O_OPT, null,	null,		null],
	'filter_status' =>						[T_ZBX_INT, O_OPT, null,	IN([-1, MAINTENANCE_STATUS_ACTIVE, MAINTENANCE_STATUS_APPROACH, MAINTENANCE_STATUS_EXPIRED]), null],
	// sort and sortorder
	'sort' =>								[T_ZBX_STR, O_OPT, P_SYS,
												IN('"active_since","active_till","maintenance_type","name"'),
												null
											],
	'sortorder' =>							[T_ZBX_STR, O_OPT, P_SYS, IN('"'.ZBX_SORT_DOWN.'","'.ZBX_SORT_UP.'"'),
												null
											]
];

check_fields($fields);

/*
 * Permissions
 */
if (getRequest('groupid') && !isWritableHostGroups([getRequest('groupid')])) {
	access_deny();
}
if (isset($_REQUEST['maintenanceid'])) {
	$dbMaintenance = API::Maintenance()->get([
		'output' => API_OUTPUT_EXTEND,
		'selectTimeperiods' => API_OUTPUT_EXTEND,
		'selectTags' => API_OUTPUT_EXTEND,
		'editable' => true,
		'maintenanceids' => getRequest('maintenanceid'),
	]);
	if (empty($dbMaintenance)) {
		access_deny();
	}
}
if (hasRequest('action') && (!hasRequest('maintenanceids') || !is_array(getRequest('maintenanceids')))) {
	access_deny();
}

/*
 * Actions
 */
if (isset($_REQUEST['clone']) && isset($_REQUEST['maintenanceid'])) {
	unset($_REQUEST['maintenanceid']);
	$_REQUEST['form'] = 'clone';
}
elseif (isset($_REQUEST['cancel_new_timeperiod'])) {
	unset($_REQUEST['new_timeperiod']);
}
elseif (hasRequest('add') || hasRequest('update')) {
	if (hasRequest('update')) {
		$messageSuccess = _('Maintenance updated');
		$messageFailed = _('Cannot update maintenance');
	}
	else {
		$messageSuccess = _('Maintenance added');
		$messageFailed = _('Cannot add maintenance');
	}

	$result = true;
	$absolute_time_parser = new CAbsoluteTimeParser();

	$absolute_time_parser->parse(getRequest('active_since'));
	$active_since_date = $absolute_time_parser->getDateTime(true);

	$absolute_time_parser->parse(getRequest('active_till'));
	$active_till_date = $absolute_time_parser->getDateTime(true);

	if (!validateDateInterval($active_since_date->format('Y'), $active_since_date->format('m'),
			$active_since_date->format('d'))) {
		info(_s('"%s" must be between 1970.01.01 and 2038.01.18.', _('Active since')));
		$result = false;
	}

	if (!validateDateInterval($active_till_date->format('Y'), $active_till_date->format('m'),
			$active_till_date->format('d'))) {
		info(_s('"%s" must be between 1970.01.01 and 2038.01.18.', _('Active till')));
		$result = false;
	}

	if ($result) {
		$timeperiods = getRequest('timeperiods', []);

		foreach ($timeperiods as &$timeperiod) {
			if ($timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_ONETIME) {
				$absolute_time_parser->parse($timeperiod['start_date']);
				$timeperiod['start_date'] = $absolute_time_parser
					->getDateTime(true)
					->getTimestamp();
			}
		}
		unset($timeperiod);

		$maintenance = [
			'name' => $_REQUEST['mname'],
			'maintenance_type' => getRequest('maintenance_type'),
			'description' => getRequest('description'),
			'active_since' => $active_since_date->getTimestamp(),
			'active_till' => $active_till_date->getTimestamp(),
			'timeperiods' => $timeperiods,
			'hostids' => getRequest('hostids', []),
			'groupids' => getRequest('groupids', [])
		];

		if ($maintenance['maintenance_type'] != MAINTENANCE_TYPE_NODATA) {
			$maintenance += [
				'tags_evaltype' => getRequest('tags_evaltype', MAINTENANCE_TAG_EVAL_TYPE_AND_OR),
				'tags' => getRequest('tags', [])
			];

			foreach ($maintenance['tags'] as $tnum => $tag) {
				if ($tag['tag'] === '' && $tag['value'] === '') {
					unset($maintenance['tags'][$tnum]);
				}
			}
		}

		if (isset($_REQUEST['maintenanceid'])) {
			$maintenance['maintenanceid'] = $_REQUEST['maintenanceid'];
			$result = API::Maintenance()->update($maintenance);
		}
		else {
			$result = API::Maintenance()->create($maintenance);
		}
	}

	if ($result) {
		unset($_REQUEST['form']);
		uncheckTableRows();
	}

	show_messages($result, $messageSuccess, $messageFailed);
}
elseif (hasRequest('delete') || (hasRequest('action') && getRequest('action') == 'maintenance.massdelete')) {
	$maintenanceids = getRequest('maintenanceid', []);
	if (hasRequest('maintenanceids')) {
		$maintenanceids = getRequest('maintenanceids');
	}

	zbx_value2array($maintenanceids);

	$result = API::Maintenance()->delete($maintenanceids);
	if ($result) {
		unset($_REQUEST['form'], $_REQUEST['maintenanceid']);
		uncheckTableRows();
	}
	else {
		$maintenances = API::Maintenance()->get([
			'maintenanceids' => getRequest('maintenanceids'),
			'output' => [],
			'editable' => true
		]);
		uncheckTableRows(null, zbx_objectValues($maintenances, 'maintenanceid'));
	}

	show_messages($result, _('Maintenance deleted'), _('Cannot delete maintenance'));
}
elseif (hasRequest('add_timeperiod') && hasRequest('new_timeperiod')) {
	$new_timeperiod = getRequest('new_timeperiod');

	if ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_ONETIME) {
		$new_timeperiod['start_date'] = getRequest('new_timeperiod_start_date');
	}

	// start time
	$new_timeperiod['start_time'] = ($new_timeperiod['hour'] * SEC_PER_HOUR) + ($new_timeperiod['minute'] * SEC_PER_MIN);

	// period
	$new_timeperiod['period'] = ($new_timeperiod['period_days'] * SEC_PER_DAY) + ($new_timeperiod['period_hours'] * SEC_PER_HOUR) + ($new_timeperiod['period_minutes'] * SEC_PER_MIN);

	// days of week
	if (!isset($new_timeperiod['dayofweek'])) {
		$dayofweek =  (!isset($new_timeperiod['dayofweek_su'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_sa'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_fr'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_th'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_we'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_tu'])) ? '0' : '1';
		$dayofweek .= (!isset($new_timeperiod['dayofweek_mo'])) ? '0' : '1';
		$new_timeperiod['dayofweek'] = bindec($dayofweek);
	}

	// months
	if (!isset($new_timeperiod['month'])) {
		$month =  (!isset($new_timeperiod['month_dec'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_nov'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_oct'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_sep'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_aug'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_jul'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_jun'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_may'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_apr'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_mar'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_feb'])) ? '0' : '1';
		$month .= (!isset($new_timeperiod['month_jan'])) ? '0' : '1';
		$new_timeperiod['month'] = bindec($month);
	}

	if ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_MONTHLY) {
		if ($new_timeperiod['month_date_type'] > 0) {
			$new_timeperiod['day'] = 0;
		}
		else {
			$new_timeperiod['every'] = 1;
			$new_timeperiod['dayofweek'] = 0;
		}
	}

	$_REQUEST['timeperiods'] = getRequest('timeperiods', []);

	$result = true;

	if ($new_timeperiod['period'] < 300) {
		info(_('Incorrect maintenance period (minimum 5 minutes)'));
		$result = false;
	}
	elseif ($new_timeperiod['hour'] > 23 || $new_timeperiod['minute'] > 59) {
		info(_('Incorrect maintenance period'));
		$result = false;
	}
	elseif ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_ONETIME) {
		$absolute_time_parser = new CAbsoluteTimeParser();
		$absolute_time_parser->parse($new_timeperiod['start_date']);
		$start_date = $absolute_time_parser->getDateTime(true);
		$new_timeperiod['start_date'] = $start_date->format(ZBX_DATE_TIME);

		if (!validateDateInterval($start_date->format('Y'), $start_date->format('m'), $start_date->format('d'))) {
			error(_('Incorrect maintenance - date must be between 1970.01.01 and 2038.01.18'));
			$result = false;
		}
	}
	elseif ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_DAILY && $new_timeperiod['every'] < 1) {
		info(_('Incorrect maintenance day period'));
		$result = false;
	}
	elseif ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_WEEKLY) {
		if ($new_timeperiod['every'] < 1) {
			info(_('Incorrect maintenance week period'));
			$result = false;
		}
		elseif ($new_timeperiod['dayofweek'] < 1) {
			info(_('Incorrect maintenance days of week'));
			$result = false;
		}
	}
	elseif ($new_timeperiod['timeperiod_type'] == TIMEPERIOD_TYPE_MONTHLY) {
		if ($new_timeperiod['month'] < 1) {
			info(_('Incorrect maintenance month period'));
			$result = false;
		}
		elseif ($new_timeperiod['day'] == 0 && $new_timeperiod['dayofweek'] < 1) {
			info(_('Incorrect maintenance days of week'));
			$result = false;
		}
		elseif (($new_timeperiod['day'] < 1 || $new_timeperiod['day'] > 31) && $new_timeperiod['dayofweek'] == 0) {
			info(_('Incorrect maintenance date'));
			$result = false;
		}
	}

	show_messages();

	if ($result) {
		if (!isset($new_timeperiod['id'])) {
			if (!str_in_array($new_timeperiod, $_REQUEST['timeperiods'])) {
				array_push($_REQUEST['timeperiods'], $new_timeperiod);
			}
		}
		else {
			$id = $new_timeperiod['id'];
			unset($new_timeperiod['id']);
			$_REQUEST['timeperiods'][$id] = $new_timeperiod;
		}
		unset($_REQUEST['new_timeperiod']);
	}
}
elseif (isset($_REQUEST['del_timeperiodid'])) {
	$_REQUEST['timeperiods'] = getRequest('timeperiods', []);
	$delTimeperiodId = array_keys($_REQUEST['del_timeperiodid']);
	$delTimeperiodId = reset($delTimeperiodId);
	unset($_REQUEST['timeperiods'][$delTimeperiodId]);
}
elseif (isset($_REQUEST['edit_timeperiodid'])) {
	$_REQUEST['edit_timeperiodid'] = array_keys($_REQUEST['edit_timeperiodid']);
	$edit_timeperiodid = $_REQUEST['edit_timeperiodid'] = array_pop($_REQUEST['edit_timeperiodid']);
	$_REQUEST['timeperiods'] = getRequest('timeperiods', []);

	if (isset($_REQUEST['timeperiods'][$edit_timeperiodid])) {
		$_REQUEST['new_timeperiod'] = $_REQUEST['timeperiods'][$edit_timeperiodid];
		$_REQUEST['new_timeperiod']['id'] = $edit_timeperiodid;
		$_REQUEST['new_timeperiod_start_date'] = $_REQUEST['new_timeperiod']['start_date'];
	}
}

$options = [
	'groups' => ['editable' => 1],
	'groupid' => getRequest('groupid')
];
$pageFilter = new CPageFilter($options);
$_REQUEST['groupid'] = $pageFilter->groupid;

/*
 * Display
 */
$data = [
	'form' => getRequest('form'),
	'new_timeperiod' => getRequest('new_timeperiod', []),
	'add_timeperiod' => getRequest('add_timeperiod', 0),
	'new_timeperiod_start_date' => getRequest('new_timeperiod_start_date')
];

if (!empty($data['form'])) {
	$data['maintenanceid'] = getRequest('maintenanceid');
	$data['form_refresh'] = getRequest('form_refresh', 0);

	if (isset($data['maintenanceid']) && !hasRequest('form_refresh')) {
		$dbMaintenance = reset($dbMaintenance);
		$data['mname'] = $dbMaintenance['name'];
		$data['maintenance_type'] = $dbMaintenance['maintenance_type'];
		$data['active_since'] = date(ZBX_DATE_TIME, $dbMaintenance['active_since']);
		$data['active_till'] = date(ZBX_DATE_TIME, $dbMaintenance['active_till']);
		$data['description'] = $dbMaintenance['description'];

		// time periods
		$data['timeperiods'] = $dbMaintenance['timeperiods'];
		CArrayHelper::sort($data['timeperiods'], ['timeperiod_type', 'start_date']);

		foreach ($data['timeperiods'] as &$timeperiod) {
			$timeperiod['start_date'] = date(ZBX_DATE_TIME, $timeperiod['start_date']);
		}
		unset($timeperiod);

		// get hosts
		$db_hosts = API::Host()->get([
			'output' => ['hostid', 'name'],
			'maintenanceids' => $data['maintenanceid'],
			'editable' => true
		]);

		// get groups
		$db_groups = API::HostGroup()->get([
			'output' => ['groupid', 'name'],
			'maintenanceids' => $data['maintenanceid'],
			'editable' => true
		]);

		// tags
		$data['tags_evaltype'] = $dbMaintenance['tags_evaltype'];
		$data['tags'] = $dbMaintenance['tags'];
		CArrayHelper::sort($data['tags'], ['tag', 'value']);
	}
	else {

		if ($data['new_timeperiod'] != 1 && $data['new_timeperiod'] !== []
				&& hasRequest('new_timeperiod_start_date')) {
			$data['new_timeperiod']['start_date'] = getRequest('new_timeperiod_start_date');
		}

		$data += [
			'mname' => getRequest('mname', ''),
			'maintenance_type' => getRequest('maintenance_type', 0),
			'active_since' => getRequest('active_since', date(ZBX_DATE_TIME, strtotime('today'))),
			'active_till' => getRequest('active_till', date(ZBX_DATE_TIME, strtotime('tomorrow'))),
			'description' => getRequest('description', ''),
			'timeperiods' => getRequest('timeperiods', []),
			'tags_evaltype' => getRequest('tags_evaltype', MAINTENANCE_TAG_EVAL_TYPE_AND_OR),
			'tags' => getRequest('tags', [])
		];

		$hostids = getRequest('hostids', []);
		$groupids = getRequest('groupids', []);

		$db_hosts = $hostids
			? API::Host()->get([
				'output' => ['hostid', 'name'],
				'hostids' => $hostids,
				'editable' => true
			])
			: [];

		$db_groups = $groupids
			? API::HostGroup()->get([
				'output' => ['groupid', 'name'],
				'groupids' => $groupids,
				'editable' => true
			])
			: [];
	}

	$data['hosts_ms'] = CArrayHelper::renameObjectsKeys($db_hosts, ['hostid' => 'id']);
	CArrayHelper::sort($data['hosts_ms'], ['name']);

	$data['groups_ms'] = CArrayHelper::renameObjectsKeys($db_groups, ['groupid' => 'id']);
	CArrayHelper::sort($data['groups_ms'], ['name']);

	// render view
	$maintenanceView = new CView('configuration.maintenance.edit', $data);
	$maintenanceView->render();
	$maintenanceView->show();
}
else {
	// get maintenances
	$sortField = getRequest('sort', CProfile::get('web.'.$page['file'].'.sort', 'name'));
	$sortOrder = getRequest('sortorder', CProfile::get('web.'.$page['file'].'.sortorder', ZBX_SORT_UP));

	CProfile::update('web.'.$page['file'].'.sort', $sortField, PROFILE_TYPE_STR);
	CProfile::update('web.'.$page['file'].'.sortorder', $sortOrder, PROFILE_TYPE_STR);

	// filter
	if (hasRequest('filter_set')) {
		CProfile::update('web.maintenance.filter_name', getRequest('filter_name', ''), PROFILE_TYPE_STR);
		CProfile::update('web.maintenance.filter_status', getRequest('filter_status', -1), PROFILE_TYPE_INT);
	}
	elseif (hasRequest('filter_rst')) {
		CProfile::delete('web.maintenance.filter_name');
		CProfile::delete('web.maintenance.filter_status');
	}

	$filter = [
		'name' => CProfile::get('web.maintenance.filter_name', ''),
		'status' => CProfile::get('web.maintenance.filter_status', -1)
	];

	$config = select_config();

	$data = [
		'sort' => $sortField,
		'sortorder' => $sortOrder,
		'filter' => $filter,
		'profileIdx' => 'web.maintenance.filter',
		'active_tab' => CProfile::get('web.maintenance.filter.active', 1)
	];

	// Get list of maintenances.
	$options = [
		'output' => ['maintenanceid', 'name', 'maintenance_type', 'active_since', 'active_till', 'description'],
		'search' => [
			'name' => ($filter['name'] === '') ? null : $filter['name']
		],
		'editable' => true,
		'sortfield' => $sortField,
		'sortorder' => $sortOrder,
		'limit' => $config['search_limit'] + 1
	];

	if ($pageFilter->groupsSelected && $pageFilter->groupid > 0) {
		$options['groupids'] = $pageFilter->groupids;
	}
	else {
		$options['groupids'] = $config['dropdown_first_entry'] ? null : [];
	}

	$data['maintenances'] = API::Maintenance()->get($options);

	foreach ($data['maintenances'] as $key => $maintenance) {
		if ($maintenance['active_till'] < time()) {
			$data['maintenances'][$key]['status'] = MAINTENANCE_STATUS_EXPIRED;
		}
		elseif ($maintenance['active_since'] > time()) {
			$data['maintenances'][$key]['status'] = MAINTENANCE_STATUS_APPROACH;
		}
		else {
			$data['maintenances'][$key]['status'] = MAINTENANCE_STATUS_ACTIVE;
		}
	}

	// filter by status
	if ($filter['status'] != -1) {
		foreach ($data['maintenances'] as $key => $maintenance) {
			if ($data['maintenances'][$key]['status'] != $filter['status']) {
				unset($data['maintenances'][$key]);
			}
		}
	}

	order_result($data['maintenances'], $sortField, $sortOrder);

	$url = (new CUrl('maintenance.php'))
		->setArgument('groupid', $pageFilter->groupid);

	$data['paging'] = getPagingLine($data['maintenances'], $sortOrder, $url);

	$data['pageFilter'] = $pageFilter;

	// render view
	$maintenanceView = new CView('configuration.maintenance.list', $data);
	$maintenanceView->render();
	$maintenanceView->show();
}

require_once dirname(__FILE__).'/include/page_footer.php';
