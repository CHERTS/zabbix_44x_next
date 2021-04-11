<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


class CControllerHintBox extends CController {

	protected function init() {
		$this->disableSIDvalidation();
	}

	protected function checkInput() {
		$fields = [
			'type' => 'required|in eventlist',
			'data' => 'required|array'
		];

		$ret = $this->validateInput($fields) && $this->validateInputData();

		if (!$ret) {
			$this->setResponse(new CControllerResponseData([]));
		}

		return $ret;
	}

	private function validateInputData(): bool {
		$data = $this->getInput('data');

		switch ($this->getInput('type')) {
			case 'eventlist':
				$fields = [
					'triggerid' => 'required|db triggers.triggerid',
					'eventid_till' => 'required|db events.eventid',
					'show_timeline' => 'required|in 0,1',
					'show_tags' => 'required|in '.implode(',', [PROBLEMS_SHOW_TAGS_NONE, PROBLEMS_SHOW_TAGS_1, PROBLEMS_SHOW_TAGS_2, PROBLEMS_SHOW_TAGS_3]),
					'filter_tags' => 'array',
					'tag_name_format' => 'required|in '.implode(',', [PROBLEMS_TAG_NAME_FULL, PROBLEMS_TAG_NAME_SHORTENED, PROBLEMS_TAG_NAME_NONE]),
					'tag_priority' => 'required|string'
				];
				break;
		}

		$validator = new CNewValidator($data, $fields);
		array_map('error', $validator->getAllErrors());

		$ret = !$validator->isError() && !$validator->isErrorFatal();

		if ($ret) {
			switch ($this->getInput('type')) {
				case 'eventlist':
					$ret = $this->validateInputFilterTags();
					break;
			}
		}

		return $ret;
	}

	private function validateInputFilterTags(): bool {
		$data = $this->getInput('data');
		$filter_tags = array_key_exists('filter_tags', $data) ? $data['filter_tags'] : [];

		foreach ($filter_tags as $filter_tag) {
			$fields = [
				'tag' => 'required|string',
				'operator' => 'required|in '.implode(',', [TAG_OPERATOR_EQUAL, TAG_OPERATOR_LIKE]),
				'value' => 'required|string'
			];

			$validator = new CNewValidator($filter_tag, $fields);
			array_map('error', $validator->getAllErrors());

			if ($validator->isError() || $validator->isErrorFatal()) {
				return false;
			}
		}

		return true;
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		switch ($this->getInput('type')) {
			case 'eventlist':
				$hint_data = self::getHintDataEventList($this->getInput('data'));
				break;

			default:
				$hint_data = null;
		}

		$output = [];

		if ($hint_data !== null) {
			$output['data'] = $hint_data;
		}

		$this->setResponse(new CControllerResponseData($output));
	}

	/**
	 * Get data for a hint with trigger events.
	 *
	 * @param array  $data
	 * @param string $data['triggerid']
	 * @param string $data['eventid_till']
	 * @param int    $data['show_timeline']              Show time line flag.
	 * @param int    $data['show_tags']                  Show tags flag. Possible values:
	 *                                                     - PROBLEMS_SHOW_TAGS_NONE;
	 *                                                     - PROBLEMS_SHOW_TAGS_1;
	 *                                                     - PROBLEMS_SHOW_TAGS_2;
	 *                                                     - PROBLEMS_SHOW_TAGS_3 (default).
	 * @param array  $data['filter_tags']                (optional) An array of tag filtering data.
	 * @param string $data['filter_tags'][]['tag']       Tag name.
	 * @param int    $data['filter_tags'][]['operator']  Tag operator.
	 * @param string $data['filter_tags'][]['value']     Tag value.
	 * @param int    $data['tag_name_format']            Tag name format. Possible values:
	 *                                                     - PROBLEMS_TAG_NAME_FULL (default);
	 *                                                     - PROBLEMS_TAG_NAME_SHORTENED;
	 *                                                     - PROBLEMS_TAG_NAME_NONE.
	 * @param string $data['tag_priority']               A list of comma-separated tag names.
	 *
	 * @return array|null
	 */
	private static function getHintDataEventList(array $data): ?array {
		$triggers = API::Trigger()->get([
			'output' => ['triggerid', 'expression', 'comments', 'url'],
			'triggerids' => $data['triggerid'],
			'preservekeys' => true
		]);

		if (!$triggers) {
			error(_('No permissions to referred object or it does not exist!'));

			return null;
		}

		$trigger = reset($triggers);
		$trigger['url'] = CMacrosResolverHelper::resolveTriggerUrl($trigger + ['eventid' => $data['eventid_till']],
			$url
		) ? $url : '';

		$options = [
			'output' => ['eventid', 'r_eventid', 'clock', 'ns', 'acknowledged'],
			'select_acknowledges' => ['action'],
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'eventid_till' => $data['eventid_till'],
			'objectids' => $data['triggerid'],
			'value' => TRIGGER_VALUE_TRUE,
			'sortfield' => ['eventid'],
			'sortorder' => ZBX_SORT_DOWN,
			'limit' => ZBX_WIDGET_ROWS
		];

		if ($data['show_tags'] != PROBLEMS_SHOW_TAGS_NONE) {
			$options['selectTags'] = ['tag', 'value'];
		}

		$problems = API::Event()->get($options);

		CArrayHelper::sort($problems, [
			['field' => 'clock', 'order' => ZBX_SORT_DOWN],
			['field' => 'ns', 'order' => ZBX_SORT_DOWN]
		]);

		$r_eventids = [];

		foreach ($problems as $problem) {
			$r_eventids[$problem['r_eventid']] = true;
		}
		unset($r_eventids[0]);

		$r_events = $r_eventids
			? API::Event()->get([
				'output' => ['clock', 'correlationid', 'userid'],
				'source' => EVENT_SOURCE_TRIGGERS,
				'object' => EVENT_OBJECT_TRIGGER,
				'eventids' => array_keys($r_eventids),
				'preservekeys' => true
			])
			: [];

		foreach ($problems as &$problem) {
			if (array_key_exists($problem['r_eventid'], $r_events)) {
				$problem['r_clock'] = $r_events[$problem['r_eventid']]['clock'];
				$problem['correlationid'] = $r_events[$problem['r_eventid']]['correlationid'];
				$problem['userid'] = $r_events[$problem['r_eventid']]['userid'];
			}
			else {
				$problem['r_clock'] = 0;
				$problem['correlationid'] = 0;
				$problem['userid'] = 0;
			}

			if (bccomp($problem['eventid'], $data['eventid_till']) == 0) {
				$trigger['comments'] = CMacrosResolverHelper::resolveTriggerDescription($trigger + [
					'clock' => $problem['clock'],
					'ns' => $problem['ns']
				], ['events' => true]);
			}
		}
		unset($problem);

		return [
			'trigger' => array_intersect_key($trigger, array_flip(['triggerid', 'comments', 'url'])),
			'problems' => $problems,
			'show_timeline' => (bool) $data['show_timeline'],
			'show_tags' => $data['show_tags'],
			'filter_tags' => array_key_exists('filter_tags', $data) ? $data['filter_tags'] : [],
			'tag_name_format' => $data['tag_name_format'],
			'tag_priority' => $data['tag_priority']
		];
	}
}
