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

$page['title'] = _('Notification report');
$page['file'] = 'report4.php';

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'year' =>		[T_ZBX_INT, O_OPT, P_SYS|P_NZERO,	null,	null],
	'period' =>		[T_ZBX_STR, O_OPT, P_SYS|P_NZERO,	IN('"daily","weekly","monthly","yearly"'), null],
	'media_type' =>	[T_ZBX_INT, O_OPT, P_SYS,			DB_ID,	null]
];
check_fields($fields);

$media_type = getRequest('media_type', 0);

if ($media_type != 0) {
	$db_media_type = API::MediaType()->get([
		'mediatypeids' => $media_type,
		'countOutput' => true
	]);

	if (!$db_media_type) {
		access_deny ();
	}
}

$year = getRequest('year', intval(date('Y')));
$period = getRequest('period', 'weekly');
$current_year = date('Y');
$media_types = [];

$db_media_types = API::MediaType()->get([
	'output' => ['name'],
	'preservekeys' => true
]);
CArrayHelper::sort($db_media_types, ['name']);

$media_types = array_column($db_media_types, 'name', 'mediatypeid');

$widget = (new CWidget())->setTitle(_('Notifications'));

if ($media_types) {
	$table = (new CTableInfo())->makeVerticalRotation();

	// Fetch the year of the first alert.
	if (($first_alert = DBfetch(DBselect('SELECT MIN(a.clock) AS clock FROM alerts a'))) && $first_alert['clock']) {
		$min_year = date('Y', $first_alert['clock']);
	}
	// If no alerts exist, use the current year.
	else {
		$min_year = date('Y');
	}

	$controls = new CList();

	$cmbMedia = new CComboBox('media_type', $media_type, 'submit()');
	$cmbMedia->addItem(0, _('all'));

	foreach ($media_types as $media_type_id => $name) {
		$cmbMedia->addItem($media_type_id, $name);

		// we won't need other media types in the future, if only one was selected
		if ($media_type > 0 && $media_type != $media_type_id) {
			unset($media_types[$media_type_id]);
		}
	}
	$controls
		->addItem([
			new CLabel(_('Media type'), 'media_type'),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			$cmbMedia
		])
		->addItem([
			new CLabel(_('Period'), 'period'),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			new CComboBox('period', $period, 'submit()', [
				'daily' => _('Daily'),
				'weekly' => _('Weekly'),
				'monthly' => _('Monthly'),
				'yearly' => _('Yearly')
			])
		]);

	if ($period !== 'yearly') {
		$cmbYear = new CComboBox('year', $year, 'submit();');
		for ($y = $min_year; $y <= date('Y'); $y++) {
			$cmbYear->addItem($y, $y);
		}
		$controls->addItem([
			new CLabel(_('Year'), 'year'),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			$cmbYear
		]);
	}

	$widget->setControls((new CForm('get'))
		->cleanItems()
		->setAttribute('aria-label', _('Main filter'))
		->addItem($controls)
	);

	$header = [];
	$users = [];

	$db_users = API::User()->get([
		'output' => ['userid', 'alias', 'name', 'surname'],
		'sortfield' => 'alias'
	]);

	foreach ($db_users as $user_data) {
		$full_name = getUserFullname($user_data);
		$header[] = (new CColHeader($full_name))
			->addClass('vertical_rotation')
			->setTitle($full_name);
		$users[] = $user_data['userid'];
	}

	$intervals = [];
	switch ($period) {
		case 'yearly':
			$min_time = mktime(0, 0, 0, 1, 1, $min_year);

			$date_format = _x('Y', DATE_FORMAT_CONTEXT);
			array_unshift($header, _('Year'));

			for ($i = $min_year; $i <= date('Y'); $i++) {
				$intervals[mktime(0, 0, 0, 1, 1, $i)] = mktime(0, 0, 0, 1, 1, $i + 1);
			}
			break;

		case 'monthly':
			$min_time = mktime(0, 0, 0, 1, 1, $year);

			$date_format = _x('F', DATE_FORMAT_CONTEXT);
			array_unshift($header, _('Month'));

			$max = ($year == $current_year) ? date('n') : 12;
			for ($i = 1; $i <= $max; $i++) {
				$intervals[mktime(0, 0, 0, $i, 1, $year)] = mktime(0, 0, 0, $i + 1, 1, $year);
			}
			break;

		case 'daily':
			$min_time = mktime(0, 0, 0, 1, 1, $year);

			$date_format = DATE_FORMAT;
			array_unshift($header, _('Day'));

			$max = ($year == $current_year) ? date('z') + 1 : date('z', mktime(0, 0, 0, 12, 31, $year)) + 1;
			for ($i = 1; $i <= $max; $i++) {
				$intervals[mktime(0, 0, 0, 1, $i, $year)] = mktime(0, 0, 0, 1, $i + 1, $year);
			}
			break;

		case 'weekly':
			$time = mktime(0, 0, 0, 1, 1, $year);
			$wd = date('w', $time);
			$wd = ($wd == 0) ? 6 : $wd - 1;
			$min_time = $time - $wd * SEC_PER_DAY;

			$date_format = DATE_TIME_FORMAT;
			array_unshift($header, _('From'), _('Till'));

			$max = ($year == $current_year) ? date('W') - 1 : 52;
			for ($i = 0; $i <= $max; $i++) {
				$intervals[strtotime('+'.$i.' week', $min_time)] = strtotime('+'.($i + 1).' week', $min_time);
			}
			break;
	}

	// Time till.
	$max_time = ($year == $current_year || $period === 'yearly') ? time() : mktime(0, 0, 0, 1, 1, $year + 1);

	// Fetch alerts.
	$alerts = [];
	foreach (eventSourceObjects() as $source_object) {
		$alerts = array_merge($alerts, API::Alert()->get([
			'output' => ['mediatypeid', 'userid', 'clock'],
			'eventsource' => $source_object['source'],
			'eventobject' => $source_object['object'],
			'mediatypeids' => ($media_type != 0) ? $media_type : null,
			'time_from' => $min_time,
			'time_till' => $max_time
		]));
	}
	// Sort alerts in chronological order so we could easily iterate through them later.
	CArrayHelper::sort($alerts, ['clock']);

	$table->setHeader($header);
	foreach ($intervals as $from => $till) {
		// Interval start.
		$row = [zbx_date2str($date_format, $from)];

		// Interval end, displayed only for week intervals.
		if ($period === 'weekly') {
			$row[] = zbx_date2str($date_format, min($till, time()));
		}

		$notifications = [];
		foreach ($users as $userid) {
			$notifications[$userid] = 0;
		}

		// Loop through alerts until we reach an alert from the next interval.
		while ($alert = current($alerts)) {
			if ($alert['clock'] >= $till) {
				break;
			}

			if (array_key_exists($alert['userid'], $notifications)) {
				$notifications[$alert['userid']]++;
			}

			next($alerts);
		}

		foreach ($notifications as $notification_count) {
			$row[] = ($notification_count == 0) ? '' : [$notification_count];
		}

		$table->addRow($row);
	}
}
else {
	// If no media types were defined, there is nothing to show.
	$table = new CTableInfo();
}

$widget
	->addItem($table)
	->show();

require_once dirname(__FILE__).'/include/page_footer.php';
