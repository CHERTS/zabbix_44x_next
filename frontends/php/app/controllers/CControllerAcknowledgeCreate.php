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


class CControllerAcknowledgeCreate extends CController {

	protected function checkInput() {
		$fields = [
			'eventids' =>				'required|array_db acknowledges.eventid',
			'message' =>				'db acknowledges.message |flags '.P_CRLF,
			'scope' =>					'in '.ZBX_ACKNOWLEDGE_SELECTED.','.ZBX_ACKNOWLEDGE_PROBLEM,
			'change_severity' =>		'db acknowledges.action|in '.
											ZBX_PROBLEM_UPDATE_NONE.','.ZBX_PROBLEM_UPDATE_SEVERITY,
			'severity' =>				'ge '.TRIGGER_SEVERITY_NOT_CLASSIFIED.'|le '.TRIGGER_SEVERITY_COUNT,
			'acknowledge_problem' =>	'db acknowledges.action|in '.
												ZBX_PROBLEM_UPDATE_NONE.','.ZBX_PROBLEM_UPDATE_ACKNOWLEDGE,
			'close_problem' =>			'db acknowledges.action|in '.
											ZBX_PROBLEM_UPDATE_NONE.','.ZBX_PROBLEM_UPDATE_CLOSE,
			'backurl' =>				'string'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->GetValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect('zabbix.php?action=acknowledge.edit');
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot update event'));
					$this->setResponse($response);
					break;

				case self::VALIDATION_FATAL_ERROR:
					$this->setResponse(new CControllerResponseFatal());
					break;
			}
		}

		return $ret;
	}

	protected function checkPermissions() {
		$events = API::Event()->get([
			'countOutput' => true,
			'eventids' => $this->getInput('eventids'),
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER
		]);

		return ($events == count($this->getInput('eventids')));
	}

	protected function doAction() {
		$updated_events_count = 0;
		$result = false;
		$data = null;

		$this->close_problems = ($this->getInput('close_problem', ZBX_PROBLEM_UPDATE_NONE) == ZBX_PROBLEM_UPDATE_CLOSE);
		$this->change_severity = ($this->getInput('change_severity', ZBX_PROBLEM_UPDATE_NONE) == ZBX_PROBLEM_UPDATE_SEVERITY);
		$this->acknowledge = ($this->getInput('acknowledge_problem', ZBX_PROBLEM_UPDATE_NONE) == ZBX_PROBLEM_UPDATE_ACKNOWLEDGE);
		$this->new_severity = $this->getInput('severity', '');
		$this->message = $this->getInput('message', '');

		$eventids = array_flip($this->getInput('eventids'));

		// Select events that are created from the same trigger if ZBX_ACKNOWLEDGE_PROBLEM is selected.
		if ($this->getInput('scope', ZBX_ACKNOWLEDGE_SELECTED) == ZBX_ACKNOWLEDGE_PROBLEM) {
			$eventids += array_flip($this->getRelatedProblemids($eventids));
		}

		// Select data about all affected events and triggers involved.
		list($events, $editable_triggers) = $this->getEventDetails(array_keys($eventids));
		unset($eventids);

		// Group events by actions user is allowed to perform.
		$eventid_groups = $this->groupEventsByActionsAllowed($events, $editable_triggers);

		// Update selected events.
		while ($eventid_groups['readable']) {
			$data = $this->getAcknowledgeOptions($eventid_groups);
			/*
			 * No actions to perform. This can happen only if user has selected action he have no permissions to do
			 * for any of selected events. This can happen, when you will perform one action on multiple problems,
			 * where only some of these problems can perform this action (ex. close problem).
			 */
			if ($data['action'] === ZBX_PROBLEM_UPDATE_NONE) {
				break;
			}

			if ($data['eventids']) {
				$eventid_chunks = array_chunk($data['eventids'], ZBX_DB_MAX_INSERTS);
				foreach ($eventid_chunks as $eventid_chunk) {
					$data['eventids'] = $eventid_chunk;
					$result = API::Event()->acknowledge($data);

					// Do not continue if event.acknowledge validation fails.
					if (!$result) {
						break 2;
					}

					$updated_events_count += count($data['eventids']);
				}
			}
		}

		if ($result) {
			$response = new CControllerResponseRedirect($this->getInput('backurl', 'zabbix.php?action=problem.view'));
			$response->setMessageOk(_n('Event updated', 'Events updated', $updated_events_count));
		}
		else {
			$response = new CControllerResponseRedirect('zabbix.php?action=acknowledge.edit');
			$response->setFormData($this->getInputAll());
			$response->setMessageError(($data && $data['action'] == ZBX_PROBLEM_UPDATE_NONE)
				? _('At least one update operation is mandatory')
				: _n('Cannot update event', 'Cannot update events', $updated_events_count)
			);
		}
		$this->setResponse($response);
	}

	/**
	 * Function returns array containing problem IDs generated by same trigger as event IDs passed as $eventids.
	 *
	 * @param array $eventids  Event IDs for which related problems must be selected.
	 *
	 * @return array
	 */
	protected function getRelatedProblemids(array $eventids) {
		$events = API::Event()->get([
			'output' => ['objectid'],
			'eventids' => array_keys($eventids),
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER
		]);

		if ($events) {
			$related_problems = API::Problem()->get([
				'output' => [],
				'objectids' => array_keys(array_flip(zbx_objectValues($events, 'objectid'))),
				'preservekeys' => true
			]);

			return array_keys($related_problems);
		}

		return [];
	}

	/**
	 * Function returns array containing 2 sub-arrays:
	 *  - First sub-array contains details for all requested events based on user actions.
	 *  - Second sub-array contains all editable trigger IDs that has caused requested events.
	 *
	 * @param array $eventids
	 *
	 * @return array
	 */
	protected function getEventDetails(array $eventids) {
		// Select details for all affected events.
		$events = API::Event()->get([
			'output' => ['eventid', 'objectid', 'acknowledged', 'r_eventid'],
			'select_acknowledges' => $this->close_problems ? ['action'] : null,
			'eventids' => $eventids,
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'preservekeys' => true
		]);

		// Select editable triggers.
		$editable_triggers = ($events && ($this->change_severity || $this->close_problems))
			? API::Trigger()->get([
				'output' => ['manual_close'],
				'triggerids' => zbx_objectValues($events, 'objectid'),
				'editable' => true,
				'preservekeys' => true
			])
			: [];

		return [$events, $editable_triggers];
	}

	/**
	 * Function groups eventids according the actions user can perform for each of event.
	 * Following groups of eventids are made:
	 *  - closable events (events are writable + configured to be closed manually + not closed before);
	 *  - editable events (events are writable);
	 *  - acknowledgeable (events are not yet acknowledged);
	 *  - readable events (events that user has at least read permissions).
	 *
	 * @param array $events
	 * @param int   $events[]['eventid']                             Event id.
	 * @param int   $events[]['objectid']                            Trigger ID that has generated particular event.
	 * @param int   $events[]['r_eventid']                           Recovery event ID.
	 * @param array $events[]['acknowledged']                        Array containing previously performed actions.
	 * @param array $editable_triggers[<triggerid>]                  Arrays containing editable trigger IDs as keys.
	 * @param array $editable_triggers[<triggerid>]['manual_close']  Arrays containing editable trigger IDs as keys.
	 *
	 * @param array
	 */
	protected function groupEventsByActionsAllowed(array $events, array $editable_triggers) {
		$eventid_groups = [
			'closable' => [],
			'editable' => [],
			'acknowledgeable' => [],
			'readable' => []
		];

		foreach ($events as $event) {
			if ($this->close_problems && $this->isEventClosable($event, $editable_triggers)) {
				$eventid_groups['closable'][] = $event['eventid'];
			}

			if ($this->change_severity && array_key_exists($event['objectid'], $editable_triggers)) {
				$eventid_groups['editable'][] = $event['eventid'];
			}

			if ($this->acknowledge && $event['acknowledged'] == EVENT_NOT_ACKNOWLEDGED) {
				$eventid_groups['acknowledgeable'][] = $event['eventid'];
			}

			$eventid_groups['readable'][] = $event['eventid'];
		}

		return $eventid_groups;
	}

	/**
	 * Checks if events can be closed manually.
	 *
	 * @param array $event                                           Event object.
	 * @param array $event['r_eventid']                              OK event id. 0 if not resolved.
	 * @param array $event['acknowledges']                           List of problem updates.
	 * @param array $event['acknowledges'][]['action']               Action performed in update.
	 * @param array $editable_triggers[<triggerid>]                  List of editable triggers.
	 * @param array $editable_triggers[<triggerid>]['manual_close']  Trigger's manual_close configuration.
	 *
	 * @return bool
	 */
	protected function isEventClosable(array $event, array $editable_triggers) {
		if (!array_key_exists($event['objectid'], $editable_triggers)
				|| $editable_triggers[$event['objectid']]['manual_close'] == ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED
				|| bccomp($event['r_eventid'], '0') > 0) {
			return false;
		}
		else {
			foreach ($event['acknowledges'] as $acknowledge) {
				if (($acknowledge['action'] & ZBX_PROBLEM_UPDATE_CLOSE) == ZBX_PROBLEM_UPDATE_CLOSE) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Function returns an array for event.acknowledge API method, containing a list of eventids and specific 'action'
	 * flag to perform for list of eventids returned. Function will also clean utilized eventids from $eventids array.
	 *
	 * @param array $eventids
	 * @param array $eventids['closable']            Event ids that user is allowed to close manually.
	 * @param array $eventids['editable']            Event ids that user is allowed to make changes.
	 * @param array $eventids['acknowledgeable']     Event ids that user is allowed to make acknowledgement.
	 * @param array $eventids['readable']            Event ids that user is allowed to read.
	 *
	 * @return array
	 */
	protected function getAcknowledgeOptions(array &$eventid_groups) {
		$data = [
			'action' => ZBX_PROBLEM_UPDATE_NONE,
			'eventids' => []
		];

		if ($this->close_problems && $eventid_groups['closable']) {
			$data['action'] |= ZBX_PROBLEM_UPDATE_CLOSE;
			$data['eventids'] = $eventid_groups['closable'];
			$eventid_groups['closable'] = [];
		}

		if ($this->change_severity && $eventid_groups['editable']) {

			if (!$data['eventids']) {
				$data['eventids'] = $eventid_groups['editable'];
			}

			$data['action'] |= ZBX_PROBLEM_UPDATE_SEVERITY;
			$data['severity'] = $this->new_severity;
			$eventid_groups['editable'] = array_diff($eventid_groups['editable'], $data['eventids']);
		}

		if ($this->acknowledge && $eventid_groups['acknowledgeable']) {

			if (!$data['eventids']) {
				$data['eventids'] = $eventid_groups['acknowledgeable'];
			}

			$data['action'] |= ZBX_PROBLEM_UPDATE_ACKNOWLEDGE;
			$eventid_groups['acknowledgeable'] = array_diff($eventid_groups['acknowledgeable'], $data['eventids']);
		}

		if ($this->message !== '' && $eventid_groups['readable']) {

			if (!$data['eventids']) {
				$data['eventids'] = $eventid_groups['readable'];
			}

			$data['action'] |= ZBX_PROBLEM_UPDATE_MESSAGE;
			$data['message'] = $this->message;
		}

		$eventid_groups['readable'] = array_diff($eventid_groups['readable'], $data['eventids']);
		$data['eventids'] = array_keys(array_flip($data['eventids']));

		return $data;
	}
}
