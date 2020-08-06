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
require_once dirname(__FILE__).'/include/graphs.inc.php';

$page['title'] = _('Custom graphs');
$page['file'] = 'charts.php';
$page['scripts'] = ['class.calendar.js', 'gtlc.js', 'flickerfreescreen.js', 'layout.mode.js'];
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

CView::$has_web_layout_mode = true;
$page['web_layout_mode'] = CView::getLayoutMode();

define('ZBX_PAGE_DO_JS_REFRESH', 1);

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'groupid' =>	[T_ZBX_INT,			O_OPT, P_SYS, DB_ID,		null],
	'hostid' =>		[T_ZBX_INT,			O_OPT, P_SYS, DB_ID,		null],
	'graphid' =>	[T_ZBX_INT,			O_OPT, P_SYS, DB_ID,		null],
	'from' =>		[T_ZBX_RANGE_TIME,	O_OPT, P_SYS, null,			null],
	'to' =>			[T_ZBX_RANGE_TIME,	O_OPT, P_SYS, null,			null],
	'action' =>		[T_ZBX_STR,			O_OPT, P_SYS, IN('"'.HISTORY_GRAPH.'","'.HISTORY_VALUES.'"'), null]
];
check_fields($fields);
validateTimeSelectorPeriod(getRequest('from'), getRequest('to'));

/*
 * Permissions
 */
if (getRequest('groupid') && !isReadableHostGroups([getRequest('groupid')])) {
	access_deny();
}
if (getRequest('hostid') && !isReadableHosts([getRequest('hostid')])) {
	access_deny();
}
if (getRequest('graphid')) {
	$graphs = API::Graph()->get([
		'graphids' => [$_REQUEST['graphid']],
		'output' => ['graphid']
	]);
	if (!$graphs) {
		access_deny();
	}
}

if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

$pageFilter = new CPageFilter([
	'groups' => ['real_hosts' => true, 'with_graphs' => true],
	'hosts' => ['with_graphs' => true],
	'groupid' => getRequest('groupid'),
	'hostid' => getRequest('hostid'),
	'graphs' => ['templated' => 0],
	'graphid' => getRequest('graphid')
]);

/*
 * Display
 */
$timeselector_options = [
	'profileIdx' => 'web.graphs.filter',
	'profileIdx2' => $pageFilter->graphid,
	'from' => getRequest('from'),
	'to' => getRequest('to')
];
updateTimeSelectorPeriod($timeselector_options);

$data = [
	'pageFilter' => $pageFilter,
	'graphid' => $pageFilter->graphid,
	'action' => getRequest('action', HISTORY_GRAPH),
	'actions' => [
		HISTORY_GRAPH => _('Graph'),
		HISTORY_VALUES => _('Values')
	],
	'timeline' => getTimeSelectorPeriod($timeselector_options),
	'active_tab' => CProfile::get('web.graphs.filter.active', 1)
];

// render view
$chartsView = new CView('monitoring.charts', $data);
$chartsView->render();
$chartsView->show();

require_once dirname(__FILE__).'/include/page_footer.php';
