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


function condition_operator2str($operator) {
	$operators = [
		CONDITION_OPERATOR_EQUAL  => _('equals'),
		CONDITION_OPERATOR_NOT_EQUAL  => _('does not equal'),
		CONDITION_OPERATOR_LIKE  => _('contains'),
		CONDITION_OPERATOR_NOT_LIKE  => _('does not contain'),
		CONDITION_OPERATOR_IN => _('in'),
		CONDITION_OPERATOR_MORE_EQUAL => _('is greater than or equals'),
		CONDITION_OPERATOR_LESS_EQUAL => _('is less than or equals'),
		CONDITION_OPERATOR_NOT_IN => _('not in'),
		CONDITION_OPERATOR_YES => _('Yes'),
		CONDITION_OPERATOR_NO => _('No'),
		CONDITION_OPERATOR_REGEXP => _('matches'),
		CONDITION_OPERATOR_NOT_REGEXP => _('does not match')
	];

	return $operators[$operator];
}

function condition_type2str($type) {
	$types = [
		CONDITION_TYPE_SUPPRESSED => _('Problem is suppressed'),
		CONDITION_TYPE_TRIGGER_NAME => _('Trigger name'),
		CONDITION_TYPE_TRIGGER_SEVERITY => _('Trigger severity'),
		CONDITION_TYPE_TRIGGER => _('Trigger'),
		CONDITION_TYPE_HOST_NAME => _('Host name'),
		CONDITION_TYPE_HOST_GROUP => _('Host group'),
		CONDITION_TYPE_TEMPLATE => _('Template'),
		CONDITION_TYPE_HOST => _('Host'),
		CONDITION_TYPE_TIME_PERIOD => _('Time period'),
		CONDITION_TYPE_DRULE => _('Discovery rule'),
		CONDITION_TYPE_DCHECK => _('Discovery check'),
		CONDITION_TYPE_DOBJECT => _('Discovery object'),
		CONDITION_TYPE_DHOST_IP => _('Host IP'),
		CONDITION_TYPE_DSERVICE_TYPE => _('Service type'),
		CONDITION_TYPE_DSERVICE_PORT => _('Service port'),
		CONDITION_TYPE_DSTATUS => _('Discovery status'),
		CONDITION_TYPE_DUPTIME => _('Uptime/Downtime'),
		CONDITION_TYPE_DVALUE => _('Received value'),
		CONDITION_TYPE_EVENT_ACKNOWLEDGED => _('Event acknowledged'),
		CONDITION_TYPE_APPLICATION => _('Application'),
		CONDITION_TYPE_PROXY => _('Proxy'),
		CONDITION_TYPE_EVENT_TYPE => _('Event type'),
		CONDITION_TYPE_HOST_METADATA => _('Host metadata'),
		CONDITION_TYPE_EVENT_TAG => _('Tag name'),
		CONDITION_TYPE_EVENT_TAG_VALUE => _('Tag value')
	];

	return $types[$type];
}

function discovery_object2str($object = null) {
	$objects = [
		EVENT_OBJECT_DHOST => _('Device'),
		EVENT_OBJECT_DSERVICE => _('Service')
	];

	if ($object === null) {
		return $objects;
	}

	return $objects[$object];
}

/**
 * Converts numerical action condition values to their corresponding string values according to action condition type.
 *
 * For action condition types such as: hosts, host groups, templates, proxies, triggers, discovery rules
 * and discovery checks, action condition values contain IDs. All unique IDs are first collected and then queried.
 * For other action condition types values are returned as they are or converted using simple string conversion
 * functions according to action condition type.
 *
 * @param array $actions							array of actions
 * @param array $action['filter']					array containing arrays of action conditions and other data
 * @param array $action['filter']['conditions']		array of action conditions
 * @param array $config								array containing configuration parameters for getting trigger
 *													severity names
 *
 * @return array									returns an array of actions condition string values
 */
function actionConditionValueToString(array $actions, array $config) {
	$result = [];

	$groupIds = [];
	$triggerIds = [];
	$hostIds = [];
	$templateIds = [];
	$proxyIds = [];
	$dRuleIds = [];
	$dCheckIds = [];

	foreach ($actions as $i => $action) {
		$result[$i] = [];

		foreach ($action['filter']['conditions'] as $j => $condition) {
			// unknown types and all of the default values for other types are 'Unknown'
			$result[$i][$j] = _('Unknown');

			switch ($condition['conditiontype']) {
				case CONDITION_TYPE_HOST_GROUP:
					$groupIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_TRIGGER:
					$triggerIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_HOST:
					$hostIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_TEMPLATE:
					$templateIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_PROXY:
					$proxyIds[$condition['value']] = $condition['value'];
					break;

				// return values as is for following condition types
				case CONDITION_TYPE_TRIGGER_NAME:
				case CONDITION_TYPE_HOST_METADATA:
				case CONDITION_TYPE_HOST_NAME:
				case CONDITION_TYPE_TIME_PERIOD:
				case CONDITION_TYPE_DHOST_IP:
				case CONDITION_TYPE_DSERVICE_PORT:
				case CONDITION_TYPE_DUPTIME:
				case CONDITION_TYPE_DVALUE:
				case CONDITION_TYPE_APPLICATION:
				case CONDITION_TYPE_EVENT_TAG:
				case CONDITION_TYPE_EVENT_TAG_VALUE:
					$result[$i][$j] = $condition['value'];
					break;

				case CONDITION_TYPE_EVENT_ACKNOWLEDGED:
					$result[$i][$j] = $condition['value'] ? _('Ack') : _('Not Ack');
					break;

				case CONDITION_TYPE_TRIGGER_SEVERITY:
					$result[$i][$j] = getSeverityName($condition['value'], $config);
					break;

				case CONDITION_TYPE_DRULE:
					$dRuleIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_DCHECK:
					$dCheckIds[$condition['value']] = $condition['value'];
					break;

				case CONDITION_TYPE_DOBJECT:
					$result[$i][$j] = discovery_object2str($condition['value']);
					break;

				case CONDITION_TYPE_DSERVICE_TYPE:
					$result[$i][$j] = discovery_check_type2str($condition['value']);
					break;

				case CONDITION_TYPE_DSTATUS:
					$result[$i][$j] = discovery_object_status2str($condition['value']);
					break;

				case CONDITION_TYPE_EVENT_TYPE:
					$result[$i][$j] = eventType($condition['value']);
					break;
			}
		}
	}

	$groups = [];
	$triggers = [];
	$hosts = [];
	$templates = [];
	$proxies = [];
	$dRules = [];
	$dChecks = [];

	if ($groupIds) {
		$groups = API::HostGroup()->get([
			'output' => ['name'],
			'groupids' => $groupIds,
			'preservekeys' => true
		]);
	}

	if ($triggerIds) {
		$triggers = API::Trigger()->get([
			'output' => ['description'],
			'triggerids' => $triggerIds,
			'expandDescription' => true,
			'selectHosts' => ['name'],
			'preservekeys' => true
		]);
	}

	if ($hostIds) {
		$hosts = API::Host()->get([
			'output' => ['name'],
			'hostids' => $hostIds,
			'preservekeys' => true
		]);
	}

	if ($templateIds) {
		$templates = API::Template()->get([
			'output' => ['name'],
			'templateids' => $templateIds,
			'preservekeys' => true
		]);
	}

	if ($proxyIds) {
		$proxies = API::Proxy()->get([
			'output' => ['host'],
			'proxyids' => $proxyIds,
			'preservekeys' => true
		]);
	}

	if ($dRuleIds) {
		$dRules = API::DRule()->get([
			'output' => ['name'],
			'druleids' => $dRuleIds,
			'preservekeys' => true
		]);
	}

	if ($dCheckIds) {
		$dChecks = API::DCheck()->get([
			'output' => ['type', 'key_', 'ports'],
			'dcheckids' => $dCheckIds,
			'selectDRules' => ['name'],
			'preservekeys' => true
		]);
	}

	if ($groups || $triggers || $hosts || $templates || $proxies || $dRules || $dChecks) {
		foreach ($actions as $i => $action) {
			foreach ($action['filter']['conditions'] as $j => $condition) {
				$id = $condition['value'];

				switch ($condition['conditiontype']) {
					case CONDITION_TYPE_HOST_GROUP:
						if (isset($groups[$id])) {
							$result[$i][$j] = $groups[$id]['name'];
						}
						break;

					case CONDITION_TYPE_TRIGGER:
						if (isset($triggers[$id])) {
							$host = reset($triggers[$id]['hosts']);
							$result[$i][$j] = $host['name'].NAME_DELIMITER.$triggers[$id]['description'];
						}
						break;

					case CONDITION_TYPE_HOST:
						if (isset($hosts[$id])) {
							$result[$i][$j] = $hosts[$id]['name'];
						}
						break;

					case CONDITION_TYPE_TEMPLATE:
						if (isset($templates[$id])) {
							$result[$i][$j] = $templates[$id]['name'];
						}
						break;

					case CONDITION_TYPE_PROXY:
						if (isset($proxies[$id])) {
							$result[$i][$j] = $proxies[$id]['host'];
						}
						break;

					case CONDITION_TYPE_DRULE:
						if (isset($dRules[$id])) {
							$result[$i][$j] = $dRules[$id]['name'];
						}
						break;

					case CONDITION_TYPE_DCHECK:
						if (isset($dChecks[$id])) {
							$drule = reset($dChecks[$id]['drules']);
							$type = $dChecks[$id]['type'];
							$key_ = $dChecks[$id]['key_'];
							$ports = $dChecks[$id]['ports'];

							$dCheck = discovery_check2str($type, $key_, $ports);

							$result[$i][$j] = $drule['name'].NAME_DELIMITER.$dCheck;
						}
						break;
				}
			}
		}
	}

	return $result;
}

/**
 * Returns the HTML representation of an action condition and action operation condition.
 *
 * @param string $condition_type
 * @param string $operator
 * @param string $value
 * @param string $value2
 *
 * @return array
 */
function getConditionDescription($condition_type, $operator, $value, $value2) {
	if ($condition_type == CONDITION_TYPE_EVENT_TAG_VALUE) {
		$description = [_('Value of tag')];
		$description[] = ' ';
		$description[] = italic(CHtml::encode($value2));
		$description[] = ' ';
	}
	elseif ($condition_type == CONDITION_TYPE_SUPPRESSED) {
		return ($operator == CONDITION_OPERATOR_YES)
			? [_('Problem is suppressed')]
			: [_('Problem is not suppressed')];
	}
	else {
		$description = [condition_type2str($condition_type)];
		$description[] = ' ';
	}

	$description[] = condition_operator2str($operator);
	$description[] = ' ';
	$description[] = italic(CHtml::encode($value));

	return $description;
}

/**
 * Gathers media types, user groups, users, host groups, hosts and templates for actions and their operations, and
 * returns the HTML representation of action operation values according to action operation type.
 *
 * @param array $actions				Array of actions
 * @param int $type						Operations recovery type (ACTION_OPERATION or ACTION_RECOVERY_OPERATION)
 *
 * @return array						Returns an array of actions operation descriptions.
 */
function getActionOperationDescriptions(array $actions, $type) {
	$result = [];

	$media_typeids = [];
	$userids = [];
	$usr_grpids = [];
	$hostids = [];
	$groupids = [];
	$templateids = [];

	foreach ($actions as $i => $action) {
		$result[$i] = [];

		if ($type == ACTION_OPERATION) {
			foreach ($action['operations'] as $j => $operation) {
				$result[$i][$j] = [];

				switch ($operation['operationtype']) {
					case OPERATION_TYPE_MESSAGE:
						$media_typeid = $operation['opmessage']['mediatypeid'];

						if ($media_typeid != 0) {
							$media_typeids[$media_typeid] = $media_typeid;
						}

						if (array_key_exists('opmessage_usr', $operation) && $operation['opmessage_usr']) {
							foreach ($operation['opmessage_usr'] as $users) {
								$userids[$users['userid']] = $users['userid'];
							}
						}

						if (array_key_exists('opmessage_grp', $operation) && $operation['opmessage_grp']) {
							foreach ($operation['opmessage_grp'] as $user_groups) {
								$usr_grpids[$user_groups['usrgrpid']] = $user_groups['usrgrpid'];
							}
						}
						break;

					case OPERATION_TYPE_COMMAND:
						if (array_key_exists('opcommand_hst', $operation) && $operation['opcommand_hst']) {
							foreach ($operation['opcommand_hst'] as $host) {
								if ($host['hostid'] != 0) {
									$hostids[$host['hostid']] = $host['hostid'];
								}
							}
						}

						if (array_key_exists('opcommand_grp', $operation) && $operation['opcommand_grp']) {
							foreach ($operation['opcommand_grp'] as $host_group) {
								$groupids[$host_group['groupid']] = true;
							}
						}
						break;

					case OPERATION_TYPE_GROUP_ADD:
					case OPERATION_TYPE_GROUP_REMOVE:
						foreach ($operation['groupids'] as $groupid) {
							$groupids[$groupid] = true;
						}
						break;

					case OPERATION_TYPE_TEMPLATE_ADD:
					case OPERATION_TYPE_TEMPLATE_REMOVE:
						foreach ($operation['templateids'] as $templateid) {
							$templateids[$templateid] = true;
						}
						break;
				}
			}
		}
		else {
			$operations_key = ($type == ACTION_RECOVERY_OPERATION)
				? 'recovery_operations'
				: 'ack_operations';

			foreach ($action[$operations_key] as $j => $operation) {
				$result[$i][$j] = [];

				switch ($operation['operationtype']) {
					case OPERATION_TYPE_MESSAGE:
						$media_typeid = $operation['opmessage']['mediatypeid'];

						if ($media_typeid != 0) {
							$media_typeids[$media_typeid] = $media_typeid;
						}

						if (array_key_exists('opmessage_usr', $operation) && $operation['opmessage_usr']) {
							foreach ($operation['opmessage_usr'] as $users) {
								$userids[$users['userid']] = $users['userid'];
							}
						}

						if (array_key_exists('opmessage_grp', $operation) && $operation['opmessage_grp']) {
							foreach ($operation['opmessage_grp'] as $user_groups) {
								$usr_grpids[$user_groups['usrgrpid']] = $user_groups['usrgrpid'];
							}
						}
						break;

					case OPERATION_TYPE_COMMAND:
						if (array_key_exists('opcommand_hst', $operation) && $operation['opcommand_hst']) {
							foreach ($operation['opcommand_hst'] as $host) {
								if ($host['hostid'] != 0) {
									$hostids[$host['hostid']] = $host['hostid'];
								}
							}
						}

						if (array_key_exists('opcommand_grp', $operation) && $operation['opcommand_grp']) {
							foreach ($operation['opcommand_grp'] as $host_group) {
								$groupids[$host_group['groupid']] = true;
							}
						}
						break;
				}
			}
		}
	}

	$media_types = [];
	$users = [];
	$user_groups = [];
	$hosts = [];
	$host_groups = [];
	$templates = [];

	if ($media_typeids) {
		$media_types = API::Mediatype()->get([
			'output' => ['name'],
			'mediatypeids' => $media_typeids,
			'preservekeys' => true
		]);
	}

	if ($userids) {
		$fullnames = [];

		$users = API::User()->get([
			'output' => ['userid', 'alias', 'name', 'surname'],
			'userids' => $userids
		]);

		foreach ($users as $user) {
			$fullnames[$user['userid']] = getUserFullname($user);
		}
	}

	if ($usr_grpids) {
		$user_groups = API::UserGroup()->get([
			'output' => ['name'],
			'usrgrpids' => $usr_grpids,
			'preservekeys' => true
		]);
	}

	if ($hostids) {
		$hosts = API::Host()->get([
			'output' => ['name'],
			'hostids' => $hostids,
			'preservekeys' => true
		]);
	}

	if ($groupids) {
		$host_groups = API::HostGroup()->get([
			'output' => ['name'],
			'groupids' => array_keys($groupids),
			'preservekeys' => true
		]);
	}

	if ($templateids) {
		$templates = API::Template()->get([
			'output' => ['name'],
			'templateids' => array_keys($templateids),
			'preservekeys' => true
		]);
	}

	// Format the HTML output.
	foreach ($actions as $i => $action) {
		if ($type == ACTION_OPERATION) {
			foreach ($action['operations'] as $j => $operation) {
				switch ($operation['operationtype']) {
					case OPERATION_TYPE_MESSAGE:
						$media_type = _('all media');
						$media_typeid = $operation['opmessage']['mediatypeid'];

						if ($media_typeid != 0 && isset($media_types[$media_typeid])) {
							$media_type = $media_types[$media_typeid]['name'];
						}

						if (array_key_exists('opmessage_usr', $operation) && $operation['opmessage_usr']) {
							$user_names_list = [];

							foreach ($operation['opmessage_usr'] as $user) {
								if (isset($fullnames[$user['userid']])) {
									$user_names_list[] = $fullnames[$user['userid']];
								}
							}

							order_result($user_names_list);

							$result[$i][$j][] = bold(_('Send message to users').': ');
							$result[$i][$j][] = [implode(', ', $user_names_list), SPACE, _('via'), SPACE,
								$media_type
							];
							$result[$i][$j][] = BR();
						}

						if (array_key_exists('opmessage_grp', $operation) && $operation['opmessage_grp']) {
							$user_groups_list = [];

							foreach ($operation['opmessage_grp'] as $userGroup) {
								if (isset($user_groups[$userGroup['usrgrpid']])) {
									$user_groups_list[] = $user_groups[$userGroup['usrgrpid']]['name'];
								}
							}

							order_result($user_groups_list);

							$result[$i][$j][] = bold(_('Send message to user groups').': ');
							$result[$i][$j][] = [implode(', ', $user_groups_list), SPACE, _('via'), SPACE,
								$media_type
							];
							$result[$i][$j][] = BR();
						}
						break;

					case OPERATION_TYPE_COMMAND:
						if (array_key_exists('opcommand_hst', $operation) && $operation['opcommand_hst']) {
							$host_list = [];

							foreach ($operation['opcommand_hst'] as $host) {
								if ($host['hostid'] == 0) {
									$result[$i][$j][] = [
										bold(_('Run remote commands on current host')),
										BR()
									];
								}
								elseif (isset($hosts[$host['hostid']])) {
									$host_list[] = $hosts[$host['hostid']]['name'];
								}
							}

							if ($host_list) {
								order_result($host_list);

								$result[$i][$j][] = bold(_('Run remote commands on hosts').': ');
								$result[$i][$j][] = [implode(', ', $host_list), BR()];
							}
						}

						if (array_key_exists('opcommand_grp', $operation) && $operation['opcommand_grp']) {
							$host_group_list = [];

							foreach ($operation['opcommand_grp'] as $host_group) {
								if (isset($host_groups[$host_group['groupid']])) {
									$host_group_list[] = $host_groups[$host_group['groupid']]['name'];
								}
							}

							order_result($host_group_list);

							$result[$i][$j][] = bold(_('Run remote commands on host groups').': ');
							$result[$i][$j][] = [implode(', ', $host_group_list), BR()];
						}
						break;

					case OPERATION_TYPE_HOST_ADD:
						$result[$i][$j][] = [bold(_('Add host')), BR()];
						break;

					case OPERATION_TYPE_HOST_REMOVE:
						$result[$i][$j][] = [bold(_('Remove host')), BR()];
						break;

					case OPERATION_TYPE_HOST_ENABLE:
						$result[$i][$j][] = [bold(_('Enable host')), BR()];
						break;

					case OPERATION_TYPE_HOST_DISABLE:
						$result[$i][$j][] = [bold(_('Disable host')), BR()];
						break;

					case OPERATION_TYPE_GROUP_ADD:
					case OPERATION_TYPE_GROUP_REMOVE:
						$host_group_list = [];

						foreach ($operation['groupids'] as $groupid) {
							if (array_key_exists($groupid, $host_groups)) {
								$host_group_list[] = $host_groups[$groupid]['name'];
							}
						}

						order_result($host_group_list);

						if ($operation['operationtype'] == OPERATION_TYPE_GROUP_ADD) {
							$result[$i][$j][] = bold(_('Add to host groups').': ');
						}
						else {
							$result[$i][$j][] = bold(_('Remove from host groups').': ');
						}

						$result[$i][$j][] = [implode(', ', $host_group_list), BR()];
						break;

					case OPERATION_TYPE_TEMPLATE_ADD:
					case OPERATION_TYPE_TEMPLATE_REMOVE:
						$template_list = [];

						foreach ($operation['templateids'] as $templateid) {
							if (array_key_exists($templateid, $templates)) {
								$template_list[] = $templates[$templateid]['name'];
							}
						}

						order_result($template_list);

						if ($operation['operationtype'] == OPERATION_TYPE_TEMPLATE_ADD) {
							$result[$i][$j][] = bold(_('Link to templates').': ');
						}
						else {
							$result[$i][$j][] = bold(_('Unlink from templates').': ');
						}

						$result[$i][$j][] = [implode(', ', $template_list), BR()];
						break;

					case OPERATION_TYPE_HOST_INVENTORY:
						$host_inventory_modes = getHostInventoryModes();
						$result[$i][$j][] = bold(operation_type2str(OPERATION_TYPE_HOST_INVENTORY).': ');
						$result[$i][$j][] = [
							$host_inventory_modes[$operation['opinventory']['inventory_mode']],
							BR()
						];
						break;
				}
			}
		}
		else {
			$operations_key = ($type == ACTION_RECOVERY_OPERATION)
				? 'recovery_operations'
				: 'ack_operations';

			foreach ($action[$operations_key] as $j => $operation) {
				switch ($operation['operationtype']) {
					case OPERATION_TYPE_MESSAGE:
						$media_type = _('all media');
						$media_typeid = $operation['opmessage']['mediatypeid'];

						if ($media_typeid != 0 && isset($media_types[$media_typeid])) {
							$media_type = $media_types[$media_typeid]['name'];
						}

						if (array_key_exists('opmessage_usr', $operation) && $operation['opmessage_usr']) {
							$user_names_list = [];

							foreach ($operation['opmessage_usr'] as $user) {
								if (isset($fullnames[$user['userid']])) {
									$user_names_list[] = $fullnames[$user['userid']];
								}
							}

							order_result($user_names_list);

							$result[$i][$j][] = bold(_('Send message to users').': ');
							$result[$i][$j][] = [implode(', ', $user_names_list), SPACE, _('via'), SPACE,
								$media_type
							];
							$result[$i][$j][] = BR();
						}

						if (array_key_exists('opmessage_grp', $operation) && $operation['opmessage_grp']) {
							$user_groups_list = [];

							foreach ($operation['opmessage_grp'] as $userGroup) {
								if (isset($user_groups[$userGroup['usrgrpid']])) {
									$user_groups_list[] = $user_groups[$userGroup['usrgrpid']]['name'];
								}
							}

							order_result($user_groups_list);

							$result[$i][$j][] = bold(_('Send message to user groups').': ');
							$result[$i][$j][] = [implode(', ', $user_groups_list), SPACE, _('via'), SPACE,
								$media_type
							];
							$result[$i][$j][] = BR();
						}
						break;

					case OPERATION_TYPE_COMMAND:
						if (array_key_exists('opcommand_hst', $operation) && $operation['opcommand_hst']) {
							$host_list = [];

							foreach ($operation['opcommand_hst'] as $host) {
								if ($host['hostid'] == 0) {
									$result[$i][$j][] = [
										bold(_('Run remote commands on current host')),
										BR()
									];
								}
								elseif (isset($hosts[$host['hostid']])) {
									$host_list[] = $hosts[$host['hostid']]['name'];
								}
							}

							if ($host_list) {
								order_result($host_list);

								$result[$i][$j][] = bold(_('Run remote commands on hosts').': ');
								$result[$i][$j][] = [implode(', ', $host_list), BR()];
							}
						}

						if (array_key_exists('opcommand_grp', $operation) && $operation['opcommand_grp']) {
							$host_group_list = [];

							foreach ($operation['opcommand_grp'] as $host_group) {
								if (isset($host_groups[$host_group['groupid']])) {
									$host_group_list[] = $host_groups[$host_group['groupid']]['name'];
								}
							}

							order_result($host_group_list);

							$result[$i][$j][] = bold(_('Run remote commands on host groups').': ');
							$result[$i][$j][] = [implode(', ', $host_group_list), BR()];
						}
						break;

					case OPERATION_TYPE_RECOVERY_MESSAGE:
					case OPERATION_TYPE_ACK_MESSAGE:
						$result[$i][$j][] = bold(_('Notify all involved'));
						break;
				}
			}
		}
	}

	return $result;
}

/**
 * Gathers action operation script details and returns the HTML items representing action operation with hint.
 *
 * @param array  $operations								Array of action operations or recovery operations.
 * @param string $operation['operationtype']				Action operation type.
 *															Possible values: OPERATION_TYPE_MESSAGE, OPERATION_TYPE_COMMAND,
 *															OPERATION_TYPE_ACK_MESSAGE and OPERATION_TYPE_RECOVERY_MESSAGE
 * @param string $operation['opcommand']['type']			Action operation command type.
 *															Possible values: ZBX_SCRIPT_TYPE_IPMI, ZBX_SCRIPT_TYPE_SSH,
 *															ZBX_SCRIPT_TYPE_TELNET, ZBX_SCRIPT_TYPE_CUSTOM_SCRIPT
 *															and ZBX_SCRIPT_TYPE_GLOBAL_SCRIPT
 * @param string $operation['opmessage']['default_msg']		'1' to show default message (optional)
 * @param array  $defaultMessage							Array containing default subject and message set via action.
 * @param string $defaultMessage['subject']					Default subject.
 * @param string $defaultMessage['message']					Default message text.
 *
 * @return array											Returns an array of action operation hints.
 */
function getActionOperationHints(array $operations, array $defaultMessage) {
	$result = [];
	$scriptids = [];
	$scripts = [];

	foreach ($operations as $operation) {
		if ($operation['operationtype'] == OPERATION_TYPE_COMMAND
				&& $operation['opcommand']['type'] == ZBX_SCRIPT_TYPE_GLOBAL_SCRIPT) {
			$scriptids[$operation['opcommand']['scriptid']] = true;
		}
	}

	if ($scriptids) {
		$scripts = API::Script()->get([
			'output' => ['name'],
			'scriptids' => array_keys($scriptids),
			'preservekeys' => true
		]);
	}

	foreach ($operations as $key => $operation) {
		$result[$key] = [];

		switch ($operation['operationtype']) {
			case OPERATION_TYPE_MESSAGE:
				// show default entered action subject and message or show custom subject and message for each operation
				$subject = (isset($operation['opmessage']['default_msg']) && $operation['opmessage']['default_msg'])
					? $defaultMessage['subject']
					: $operation['opmessage']['subject'];

				$message = (isset($operation['opmessage']['default_msg']) && $operation['opmessage']['default_msg'])
					? $defaultMessage['message']
					: $operation['opmessage']['message'];

				$result_hint = [];

				if (trim($subject)) {
					$result_hint = [bold($subject), BR(), BR()];
				}

				if (trim($message)) {
					$result_hint[] = zbx_nl2br($message);
				}

				if ($result_hint) {
					$result[$key][] = $result_hint;
				}
				break;

			case OPERATION_TYPE_COMMAND:
				switch ($operation['opcommand']['type']) {
					case ZBX_SCRIPT_TYPE_IPMI:
						$result[$key][] = [bold(_('Run IPMI command').': '), BR(),
							italic(zbx_nl2br($operation['opcommand']['command']))
						];
						break;

					case ZBX_SCRIPT_TYPE_SSH:
						$result[$key][] = [bold(_('Run SSH commands').': '), BR(),
							italic(zbx_nl2br($operation['opcommand']['command']))
						];
						break;

					case ZBX_SCRIPT_TYPE_TELNET:
						$result[$key][] = [bold(_('Run TELNET commands').': '), BR(),
							italic(zbx_nl2br($operation['opcommand']['command']))
						];
						break;

					case ZBX_SCRIPT_TYPE_CUSTOM_SCRIPT:
						if ($operation['opcommand']['execute_on'] == ZBX_SCRIPT_EXECUTE_ON_AGENT) {
							$result[$key][] = [bold(_s('Run custom commands on %1$s', _('Zabbix agent')).': '),
								BR(), italic(zbx_nl2br($operation['opcommand']['command']))
							];
						}
						elseif ($operation['opcommand']['execute_on'] == ZBX_SCRIPT_EXECUTE_ON_PROXY) {
							$result[$key][] = [bold(_s('Run custom commands on %1$s', _('Zabbix server (proxy)')).': '),
								BR(), italic(zbx_nl2br($operation['opcommand']['command']))
							];
						}
						else {
							$result[$key][] = [bold(_s('Run custom commands on %1$s', _('Zabbix server')).': '),
								BR(), italic(zbx_nl2br($operation['opcommand']['command']))
							];
						}
						break;

					case ZBX_SCRIPT_TYPE_GLOBAL_SCRIPT:
						$scriptId = $operation['opcommand']['scriptid'];

						if (isset($scripts[$scriptId])) {
							$result[$key][] = [bold(_('Run global script').': '),
								italic($scripts[$scriptId]['name'])
							];
						}
						break;

					default:
						$result[$key][] = [bold(_('Run commands').': '), BR(),
							italic(zbx_nl2br($operation['opcommand']['command']))
						];
				}
				break;

			case OPERATION_TYPE_ACK_MESSAGE:
			case OPERATION_TYPE_RECOVERY_MESSAGE:
				$result_hint = [];
				$message = (array_key_exists('default_msg', $operation['opmessage'])
					&& $operation['opmessage']['default_msg'])
					? $defaultMessage
					: $operation['opmessage'];

				if (trim($message['subject'])) {
					$result_hint = [bold($message['subject']), BR(), BR()];
				}

				if (trim($message['message'])) {
					$result_hint[] = zbx_nl2br($message['message']);
				}

				if ($result_hint) {
					$result[$key][] = $result_hint;
				}
				break;
		}
	}

	return $result;
}

/**
 * Return an array of action conditions supported by the given event source.
 *
 * @param int $eventsource
 *
 * @return mixed
 */
function get_conditions_by_eventsource($eventsource) {
	$conditions[EVENT_SOURCE_TRIGGERS] = [
		CONDITION_TYPE_TRIGGER_NAME,
		CONDITION_TYPE_TRIGGER,
		CONDITION_TYPE_TRIGGER_SEVERITY,
		CONDITION_TYPE_APPLICATION,
		CONDITION_TYPE_HOST,
		CONDITION_TYPE_HOST_GROUP,
		CONDITION_TYPE_SUPPRESSED,
		CONDITION_TYPE_EVENT_TAG,
		CONDITION_TYPE_EVENT_TAG_VALUE,
		CONDITION_TYPE_TEMPLATE,
		CONDITION_TYPE_TIME_PERIOD
	];
	$conditions[EVENT_SOURCE_DISCOVERY] = [
		CONDITION_TYPE_DHOST_IP,
		CONDITION_TYPE_DCHECK,
		CONDITION_TYPE_DOBJECT,
		CONDITION_TYPE_DRULE,
		CONDITION_TYPE_DSTATUS,
		CONDITION_TYPE_PROXY,
		CONDITION_TYPE_DVALUE,
		CONDITION_TYPE_DSERVICE_PORT,
		CONDITION_TYPE_DSERVICE_TYPE,
		CONDITION_TYPE_DUPTIME
	];
	$conditions[EVENT_SOURCE_AUTO_REGISTRATION] = [
		CONDITION_TYPE_HOST_NAME,
		CONDITION_TYPE_HOST_METADATA,
		CONDITION_TYPE_PROXY
	];
	$conditions[EVENT_SOURCE_INTERNAL] = [
		CONDITION_TYPE_APPLICATION,
		CONDITION_TYPE_EVENT_TYPE,
		CONDITION_TYPE_HOST,
		CONDITION_TYPE_HOST_GROUP,
		CONDITION_TYPE_TEMPLATE
	];

	if (isset($conditions[$eventsource])) {
		return $conditions[$eventsource];
	}

	return $conditions[EVENT_SOURCE_TRIGGERS];
}

function get_opconditions_by_eventsource($eventsource) {
	$conditions = [
		EVENT_SOURCE_TRIGGERS => [CONDITION_TYPE_EVENT_ACKNOWLEDGED],
		EVENT_SOURCE_DISCOVERY => [],
	];

	if (isset($conditions[$eventsource])) {
		return $conditions[$eventsource];
	}
}

/**
 * Return allowed operations types.
 *
 * @param int $eventsource
 *
 * @return array
 */
function getAllowedOperations($eventsource) {
	if ($eventsource == EVENT_SOURCE_TRIGGERS) {
		$operations = [
			ACTION_OPERATION => [
				OPERATION_TYPE_MESSAGE,
				OPERATION_TYPE_COMMAND
			],
			ACTION_RECOVERY_OPERATION => [
				OPERATION_TYPE_MESSAGE,
				OPERATION_TYPE_COMMAND,
				OPERATION_TYPE_RECOVERY_MESSAGE
			],
			ACTION_ACKNOWLEDGE_OPERATION => [
				OPERATION_TYPE_MESSAGE,
				OPERATION_TYPE_COMMAND,
				OPERATION_TYPE_ACK_MESSAGE
			]
		];
	}

	if ($eventsource == EVENT_SOURCE_DISCOVERY) {
		$operations[ACTION_OPERATION] = [
			OPERATION_TYPE_MESSAGE,
			OPERATION_TYPE_COMMAND,
			OPERATION_TYPE_HOST_ADD,
			OPERATION_TYPE_HOST_REMOVE,
			OPERATION_TYPE_GROUP_ADD,
			OPERATION_TYPE_GROUP_REMOVE,
			OPERATION_TYPE_TEMPLATE_ADD,
			OPERATION_TYPE_TEMPLATE_REMOVE,
			OPERATION_TYPE_HOST_ENABLE,
			OPERATION_TYPE_HOST_DISABLE,
			OPERATION_TYPE_HOST_INVENTORY
		];
	}

	if ($eventsource == EVENT_SOURCE_AUTO_REGISTRATION) {
		$operations[ACTION_OPERATION] = [
			OPERATION_TYPE_MESSAGE,
			OPERATION_TYPE_COMMAND,
			OPERATION_TYPE_HOST_ADD,
			OPERATION_TYPE_HOST_REMOVE,
			OPERATION_TYPE_GROUP_ADD,
			OPERATION_TYPE_GROUP_REMOVE,
			OPERATION_TYPE_TEMPLATE_ADD,
			OPERATION_TYPE_TEMPLATE_REMOVE,
			OPERATION_TYPE_HOST_ENABLE,
			OPERATION_TYPE_HOST_DISABLE,
			OPERATION_TYPE_HOST_INVENTORY
		];
	}

	if ($eventsource == EVENT_SOURCE_INTERNAL) {
		$operations = [
			ACTION_OPERATION => [OPERATION_TYPE_MESSAGE],
			ACTION_RECOVERY_OPERATION => [
				OPERATION_TYPE_MESSAGE,
				OPERATION_TYPE_RECOVERY_MESSAGE
			]
		];
	}

	return $operations;
}

/**
 * Get operation type text label according $type value. If $type is equal null array of all available operation types
 * will be returned.
 *
 * @param int|null $type  Operation type, one of OPERATION_TYPE_* constant or null.
 *
 * @return string|array
 */
function operation_type2str($type) {
	$types = [
		OPERATION_TYPE_MESSAGE => _('Send message'),
		OPERATION_TYPE_COMMAND => _('Remote command'),
		OPERATION_TYPE_HOST_ADD => _('Add host'),
		OPERATION_TYPE_HOST_REMOVE => _('Remove host'),
		OPERATION_TYPE_HOST_ENABLE => _('Enable host'),
		OPERATION_TYPE_HOST_DISABLE => _('Disable host'),
		OPERATION_TYPE_GROUP_ADD => _('Add to host group'),
		OPERATION_TYPE_GROUP_REMOVE => _('Remove from host group'),
		OPERATION_TYPE_TEMPLATE_ADD => _('Link to template'),
		OPERATION_TYPE_TEMPLATE_REMOVE => _('Unlink from template'),
		OPERATION_TYPE_HOST_INVENTORY => _('Set host inventory mode'),
		OPERATION_TYPE_RECOVERY_MESSAGE => _('Notify all involved'),
		OPERATION_TYPE_ACK_MESSAGE => _('Notify all involved')
	];

	if (is_null($type)) {
		return order_result($types);
	}
	elseif (isset($types[$type])) {
		return $types[$type];
	}
	else {
		return _('Unknown');
	}
}

function sortOperations($eventsource, &$operations) {
	if ($eventsource == EVENT_SOURCE_TRIGGERS || $eventsource == EVENT_SOURCE_INTERNAL) {
		$esc_step_from = [];
		$esc_step_to = [];
		$esc_period = [];
		$operationTypes = [];

		$simple_interval_parser = new CSimpleIntervalParser();

		foreach ($operations as $key => $operation) {
			$esc_step_from[$key] = $operation['esc_step_from'];
			$esc_step_to[$key] = $operation['esc_step_to'];
			// Try to sort by "esc_period" in seconds, otherwise sort as string in case it's a macro or something invalid.
			$esc_period[$key] = ($simple_interval_parser->parse($operation['esc_period']) == CParser::PARSE_SUCCESS)
				? timeUnitToSeconds($operation['esc_period'])
				: $operation['esc_period'];

			$operationTypes[$key] = $operation['operationtype'];
		}
		array_multisort($esc_step_from, SORT_ASC, $esc_step_to, SORT_ASC, $esc_period, SORT_ASC, $operationTypes, SORT_ASC, $operations);
	}
	else {
		CArrayHelper::sort($operations, ['operationtype']);
	}
}

/**
 * Return an array of operators supported by the given action condition.
 *
 * @param int $conditiontype
 *
 * @return array
 */
function get_operators_by_conditiontype($conditiontype) {
	$operators[CONDITION_TYPE_HOST_GROUP] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_TEMPLATE] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_HOST] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_TRIGGER] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_TRIGGER_NAME] = [
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE
	];
	$operators[CONDITION_TYPE_TRIGGER_SEVERITY] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL,
		CONDITION_OPERATOR_MORE_EQUAL,
		CONDITION_OPERATOR_LESS_EQUAL
	];
	$operators[CONDITION_TYPE_TIME_PERIOD] = [
		CONDITION_OPERATOR_IN,
		CONDITION_OPERATOR_NOT_IN
	];
	$operators[CONDITION_TYPE_SUPPRESSED] = [
		CONDITION_OPERATOR_NO,
		CONDITION_OPERATOR_YES
	];
	$operators[CONDITION_TYPE_DRULE] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DCHECK] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DOBJECT] = [
		CONDITION_OPERATOR_EQUAL,
	];
	$operators[CONDITION_TYPE_PROXY] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DHOST_IP] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DSERVICE_TYPE] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DSERVICE_PORT] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL
	];
	$operators[CONDITION_TYPE_DSTATUS] = [
		CONDITION_OPERATOR_EQUAL,
	];
	$operators[CONDITION_TYPE_DUPTIME] = [
		CONDITION_OPERATOR_MORE_EQUAL,
		CONDITION_OPERATOR_LESS_EQUAL
	];
	$operators[CONDITION_TYPE_DVALUE] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL,
		CONDITION_OPERATOR_MORE_EQUAL,
		CONDITION_OPERATOR_LESS_EQUAL,
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE
	];
	$operators[CONDITION_TYPE_EVENT_ACKNOWLEDGED] = [
		CONDITION_OPERATOR_EQUAL
	];
	$operators[CONDITION_TYPE_APPLICATION] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE
	];
	$operators[CONDITION_TYPE_HOST_NAME] = [
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE,
		CONDITION_OPERATOR_REGEXP,
		CONDITION_OPERATOR_NOT_REGEXP
	];
	$operators[CONDITION_TYPE_EVENT_TYPE] = [
		CONDITION_OPERATOR_EQUAL
	];
	$operators[CONDITION_TYPE_HOST_METADATA] = [
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE,
		CONDITION_OPERATOR_REGEXP,
		CONDITION_OPERATOR_NOT_REGEXP
	];
	$operators[CONDITION_TYPE_EVENT_TAG] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL,
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE
	];
	$operators[CONDITION_TYPE_EVENT_TAG_VALUE] = [
		CONDITION_OPERATOR_EQUAL,
		CONDITION_OPERATOR_NOT_EQUAL,
		CONDITION_OPERATOR_LIKE,
		CONDITION_OPERATOR_NOT_LIKE
	];

	if (isset($operators[$conditiontype])) {
		return $operators[$conditiontype];
	}

	return [];
}

function count_operations_delay($operations, $def_period) {
	$delays = [1 => 0];
	$periods = [];
	$max_step = 0;

	$simple_interval_parser = new CSimpleIntervalParser();

	$def_period = CMacrosResolverHelper::resolveTimeUnitMacros(
		[['def_period' => $def_period]], ['def_period']
	)[0]['def_period'];

	$def_period = ($simple_interval_parser->parse($def_period) == CParser::PARSE_SUCCESS)
		? timeUnitToSeconds($def_period)
		: null;

	$operations = CMacrosResolverHelper::resolveTimeUnitMacros($operations, ['esc_period']);

	foreach ($operations as $operation) {
		$esc_period = ($simple_interval_parser->parse($operation['esc_period']) == CParser::PARSE_SUCCESS)
			? timeUnitToSeconds($operation['esc_period'])
			: null;

		$esc_period = ($esc_period === null || $esc_period != 0) ? $esc_period : $def_period;
		$step_to = ($operation['esc_step_to'] != 0) ? $operation['esc_step_to'] : 9999;

		if ($max_step < $operation['esc_step_from']) {
			$max_step = $operation['esc_step_from'];
		}

		for ($i = $operation['esc_step_from']; $i <= $step_to; $i++) {
			if (!array_key_exists($i, $periods) || $esc_period === null || $periods[$i] > $esc_period) {
				$periods[$i] = $esc_period;
			}
		}
	}

	for ($i = 1; $i <= $max_step; $i++) {
		$esc_period = array_key_exists($i, $periods) ? $periods[$i] : $def_period;
		$delays[$i + 1] = ($esc_period !== null && $delays[$i] !== null) ? $delays[$i] + $esc_period : null;
	}

	return $delays;
}

/**
 * Returns the names of the "Event type" action condition values.
 *
 * If the $type parameter is passed, returns the name of the specific value, otherwise - returns an array of all
 * supported values.
 *
 * @param string $type
 *
 * @return array|string
 */
function eventType($type = null) {
	$types = [
		EVENT_TYPE_ITEM_NOTSUPPORTED => _('Item in "not supported" state'),
		EVENT_TYPE_LLDRULE_NOTSUPPORTED => _('Low-level discovery rule in "not supported" state'),
		EVENT_TYPE_TRIGGER_UNKNOWN => _('Trigger in "unknown" state')
	];

	if (is_null($type)) {
		return $types;
	}

	return $types[$type];
}

/**
 * Get data required to create messages, severity changes, actions icon with popup with event actions.
 *
 * @param array $events    Array with event objects with acknowledges.
 * @param array $triggers  Array of triggers.
 * @param array $r_events  Array with related recovery event data (optional).
 *
 * @return array
 */
function getEventsActionsIconsData(array $events, array $triggers, array $r_events = []) {
	$messages = getEventsMessages($events);
	$severities = getEventsSeverityChanges($events, $triggers);
	$actions = getEventsActions($events, $r_events);

	return [
		'data' => [
			'messages' => $messages['data'],
			'severities' => $severities['data'],
			'actions' => $actions['data']
		],
		'mediatypeids' => $actions['mediatypeids'],
		'userids' => $messages['userids'] + $severities['userids'] + $actions['userids']
	];
}

/**
 * Get data, required to create messages icon with popup with event messages.
 *
 * @param array  $events                                 Array with event objects with acknowledges.
 * @param string $events[]['eventid']                    Problem event ID.
 * @param array  $events[]['acknowledges']               Array with manual updates to problem.
 * @param string $events[]['acknowledges'][]['action']   Action that was performed by problem update.
 * @param string $events[]['acknowledges'][]['message']  Message text.
 * @param string $events[]['acknowledges'][]['clock']    Time when message was added.
 * @param string $events[]['acknowledges'][]['userid']   Author's userid.
 *
 * @return array
 */
function getEventsMessages(array $events) {
	$messages = [];
	$userids = [];

	// Create array of messages for each event
	foreach ($events as $event) {
		$event_messages = [];

		foreach ($event['acknowledges'] as $ack) {
			if (($ack['action'] & ZBX_PROBLEM_UPDATE_MESSAGE) == ZBX_PROBLEM_UPDATE_MESSAGE) {
				// Alias is mandatory for each user, so if alias is not returned, we don't have rights for this user.
				$event_messages[] = [
					'message' => $ack['message'],
					'userid' => $ack['userid'],
					'clock' => $ack['clock']
				];

				$userids[$ack['userid']] = true;
			}
		}

		CArrayHelper::sort($event_messages, [['field' => 'clock', 'order' => ZBX_SORT_DOWN]]);

		$messages[$event['eventid']] = [
			'messages' => array_values($event_messages),
			'count' => count($event_messages)
		];
	}

	return [
		'data' => $messages,
		'userids' => $userids
	];
}

/**
 * Get data, required to create severity changes icon with popup with event severity changes.
 *
 * @param array  $events                                      Array with event objects with acknowledges.
 * @param string $events[]['eventid']                         Problem event ID.
 * @param string $events[]['severity']                        Current event severity.
 * @param string $events[]['objectid']                        Related trigger ID.
 * @param array  $events[]['acknowledges']                    Array with manual updates to problem.
 * @param string $events[]['acknowledges'][]['action']        Action that was performed by problem update.
 * @param string $events[]['acknowledges'][]['clock']         Time when severity was changed.
 * @param string $events[]['acknowledges'][]['old_severity']  Severity before the change.
 * @param string $events[]['acknowledges'][]['new_severity']  Severity after the change.
 * @param string $events[]['acknowledges'][]['userid']        Responsible user's userid.
 * @param array  $triggers                                    Related trigger data.
 * @param string $triggers[]['priority']                      Severity of trigger.
 *
 * @return array
 */
function getEventsSeverityChanges(array $events, array $triggers) {
	$severities = [];
	$userids = [];

	// Create array of messages for each event.
	foreach ($events as $event) {
		$event_severities = [];

		foreach ($event['acknowledges'] as $ack) {
			if (($ack['action'] & ZBX_PROBLEM_UPDATE_SEVERITY) == ZBX_PROBLEM_UPDATE_SEVERITY) {
				$event_severities[] = [
					'old_severity' => $ack['old_severity'],
					'new_severity' => $ack['new_severity'],
					'userid' => $ack['userid'],
					'clock' => $ack['clock']
				];

				$userids[$ack['userid']] = true;
			}
		}

		CArrayHelper::sort($event_severities, [['field' => 'clock', 'order' => ZBX_SORT_DOWN]]);

		$severities[$event['eventid']] = [
			'severities' => array_values($event_severities),
			'count' => count($event_severities),
			'original_severity' => $triggers[$event['objectid']]['priority'],
			'current_severity' => $event['severity']
		];
	}

	return [
		'data' => $severities,
		'userids' => $userids
	];
}

/**
 * Get data, required to create actions icon with popup with event actions.
 *
 * @param array  $events                 Array with event objects with acknowledges.
 * @param string $events[]['eventid']    Problem event ID.
 * @param string $events[]['r_eventid']  OK event ID.
 * @param array  $r_events               Array with related recovery event data (optional).
 *
 * @return array
 */
function getEventsActions(array $events, array $r_events = []) {
	$alert_eventids = [];
	$r_eventids = [];
	$userids = [];
	$mediatypeids = [];
	$actions = [];

	foreach ($events as $event) {
		// Get alerts for event.
		$alert_eventids[$event['eventid']] = true;

		// Get alerts for related recovery events.
		if ($event['r_eventid'] != 0) {
			$alert_eventids[$event['r_eventid']] = true;
			$r_eventids[$event['r_eventid']] = true;
		}
	}

	if ($r_eventids && !$r_events) {
		$r_events = API::Event()->get([
			'output' => ['clock'],
			'eventids' => array_keys($r_eventids),
			'preservekeys' => true
		]);
	}

	$alerts = $alert_eventids
		? API::Alert()->get([
			'output' => ['alerttype', 'clock', 'error', 'eventid', 'mediatypeid', 'retries', 'status', 'userid'],
			'eventids' => array_keys($alert_eventids)
		])
		: [];

	// Create array of actions for each event.
	foreach ($events as $event) {
		$event_actions = getSingleEventActions($event, $r_events, $alerts);
		$actions[$event['eventid']] = [
			'actions' => $event_actions['actions'],
			'count' => $event_actions['count'],
			'has_uncomplete_action' => $event_actions['has_uncomplete_action'],
			'has_failed_action' => $event_actions['has_failed_action']
		];
		$mediatypeids += $event_actions['mediatypeids'];
		$userids += $event_actions['userids'];
	}

	return [
		'data' => $actions,
		'mediatypeids' => $mediatypeids,
		'userids' => $userids
	];
}

/**
 * Get data, required to create table with all (automatic and manual) actions for Event details page.
 *
 * @param array  $event               Event object with acknowledges.
 * @param string $event['eventid']    Problem event ID.
 * @param string $event['r_eventid']  OK event ID.
 *
 * @return array
 */
function getEventDetailsActions(array $event) {
	$r_events = [];

	// Select eventids for alert retrieval.
	$alert_eventids = [$event['eventid']];

	if ($event['r_eventid'] != 0) {
		$alert_eventids[] = $event['r_eventid'];

		$r_events = API::Event()->get([
			'output' => ['clock'],
			'eventids' => $event['r_eventid'],
			'preservekeys' => true
		]);
	}

	// Get automatic actions (alerts).
	$alerts = API::Alert()->get([
		'output' => ['alerttype', 'clock', 'error', 'eventid', 'esc_step', 'mediatypeid', 'message', 'retries',
			'sendto', 'status', 'subject', 'userid', 'p_eventid', 'acknowledgeid'
		],
		'eventids' => $alert_eventids
	]);

	$actions = getSingleEventActions($event, $r_events, $alerts);

	return [
		'actions' => $actions['actions'],
		'mediatypeids' => $actions['mediatypeids'],
		'userids' => $actions['userids']
	];
}

/**
 * Get array with all actions for single event.
 *
 * @param array   $event                              Event objects with acknowledges.
 * @param string  $event['eventid']                   Problem event ID.
 * @param string  $event['r_eventid']                 OK event ID.
 * @param string  $event['clock']                     Time when event occurred.
 * @param array   $event['acknowledges']              Array with manual updates to problem.
 * @param string  $event['acknowledges'][]['userid']  User ID.
 * @param array   $r_events                           Recovery event data for all requested events.
 * @param string  $r_events[]['clock']                Recovery event creation time.
 * @param array   $alerts                             Alert data for all requested alerts.
 * @param string  $alerts[]['eventid']                If of problem event for which this alert was generated.
 * @param string  $alerts[]['mediatypeid']            ID for mediatype used for alert.
 * @param string  $alerts[]['alerttype']              Type of alert.
 * @param string  $alerts[]['status']                 Alert status.
 * @param string  $alerts[]['userid']                 ID of alert recipient.
 *
 * @return array
 */
function getSingleEventActions(array $event, array $r_events, array $alerts) {
	$action_count = 0;
	$has_uncomplete_action = false;
	$has_failed_action = false;
	$mediatypeids = [];
	$userids = [];

	// Create array of automatically and manually performed actions combined.
	$actions = [];

	// Add row for problem generation event.
	$actions[] = [
		'action_type' => ZBX_EVENT_HISTORY_PROBLEM_EVENT,
		'clock' => $event['clock']
	];

	// Add row for problem recovery event.
	if (array_key_exists($event['r_eventid'], $r_events)) {
		$actions[] = [
			'action_type' => ZBX_EVENT_HISTORY_RECOVERY_EVENT,
			'clock' => $r_events[$event['r_eventid']]['clock']
		];
	}

	// Add manual operations.
	foreach ($event['acknowledges'] as $ack) {
		$ack['action_type'] = ZBX_EVENT_HISTORY_MANUAL_UPDATE;
		$actions[] = $ack;

		$action_count++;
		$userids[$ack['userid']] = true;
	}

	// Add alerts.
	foreach ($alerts as $alert) {
		// Add only alerts, related to current event.
		if (bccomp($alert['eventid'], $event['eventid']) == 0
				|| bccomp($alert['eventid'], $event['r_eventid']) == 0) {
			$alert['action_type'] = ZBX_EVENT_HISTORY_ALERT;
			$actions[] = $alert;

			$action_count++;

			if ($alert['alerttype'] == ALERT_TYPE_MESSAGE) {
				if ($alert['mediatypeid'] != 0) {
					$mediatypeids[$alert['mediatypeid']] = true;
				}

				if ($alert['userid'] != 0) {
					$userids[$alert['userid']] = true;
				}
			}

			if ($alert['status'] == ALERT_STATUS_NEW || $alert['status'] == ALERT_STATUS_NOT_SENT) {
				$has_uncomplete_action = true;
			}
			elseif ($alert['status'] == ALERT_STATUS_FAILED) {
				$has_failed_action = true;
			}
		}
	}

	// Sort by action_type is done to put Recovery event before actions, resulted from it. Same for other action_type.
	CArrayHelper::sort($actions, [
		['field' => 'clock', 'order' => ZBX_SORT_DOWN],
		['field' => 'action_type', 'order' => ZBX_SORT_DOWN]
	]);

	return [
		'actions' => array_values($actions),
		'count' => $action_count,
		'has_uncomplete_action' => $has_uncomplete_action,
		'has_failed_action' => $has_failed_action,
		'mediatypeids' => $mediatypeids,
		'userids' => $userids
	];
}

/**
 * Get data required to create history list in problem update page.
 *
 * @param array  $event                               Array with event objects with acknowledges.
 * @param array  $event['acknowledges']               Array with manual updates to problem.
 * @param string $event['acknowledges'][]['clock']    Time when severity was changed.
 * @param string $event['acknowledges'][]['userid']   Responsible user's userid.
 *
 * @return array
 */
function getEventUpdates(array $event) {
	$userids = [];

	foreach ($event['acknowledges'] as $ack) {
		$userids[$ack['userid']] = true;
	}

	CArrayHelper::sort($event['acknowledges'], [['field' => 'clock', 'order' => ZBX_SORT_DOWN]]);

	return [
		'data' => array_values($event['acknowledges']),
		'userids' => $userids
	];
}

/**
 * Make icons (messages, severity changes, actions) for actions column.
 *
 * @param string $eventid                Id for event, for which icons are created.
 * @param array  $actions                Array of actions data.
 * @param array  $actions['messages']    Messages icon data.
 * @param array  $actions['severities']  Severity change icon data.
 * @param array  $actions['actions']     Actions icon data.
 * @param array  $mediatypes             Mediatypes with maxattempts value and name.
 * @param array  $users                  User name, surname and alias.
 * @param array  $config                 Zabbix config.
 *
 * @return CCol|string
 */
function makeEventActionsIcons($eventid, $actions, $mediatypes, $users, $config) {
	$messages_icon = makeEventMessagesIcon($actions['messages'][$eventid], $users);
	$severities_icon = makeEventSeverityChangesIcon($actions['severities'][$eventid], $users, $config);
	$actions_icon = makeEventActionsIcon($actions['actions'][$eventid], $users, $mediatypes, $config);

	$action_icons = [];
	if ($messages_icon !== null) {
		$action_icons[] = $messages_icon;
	}
	if ($severities_icon !== null) {
		$action_icons[] = $severities_icon;
	}
	if ($actions_icon !== null) {
		$action_icons[] = $actions_icon;
	}

	return $action_icons ? (new CCol($action_icons))->addClass(ZBX_STYLE_NOWRAP) : '';
}

/**
 * Create icon with hintbox for event messages.
 *
 * @param array  $data
 * @param array  $data['messages']               Array of messages.
 * @param string $data['messages'][]['message']  Message text.
 * @param string $data['messages'][]['clock']    Message creation time.
 * @param array  $users                          User name, surname and alias.
 *
 * @return CSpan|null
 */
function makeEventMessagesIcon(array $data, array $users) {
	$total = $data['count'];

	$table = (new CTableInfo())->setHeader([_('Time'), _('User'), _('Message')]);

	for ($i = 0; $i < $total && $i < ZBX_WIDGET_ROWS; $i++) {
		$message = $data['messages'][$i];

		// Added in order to reuse makeActionTableUser().
		$message['action_type'] = ZBX_EVENT_HISTORY_MANUAL_UPDATE;

		$table->addRow([
			zbx_date2str(DATE_TIME_FORMAT_SECONDS, $message['clock']),
			makeActionTableUser($message, $users),
			zbx_nl2br($message['message'])
		]);
	}

	return $total
		? makeActionIcon([
			'icon' => ZBX_STYLE_ACTION_ICON_MSGS,
			'hint' => [
				$table,
				($total > ZBX_WIDGET_ROWS)
					? (new CDiv(
						(new CDiv(
							(new CDiv(_s('Displaying %1$s of %2$s found', ZBX_WIDGET_ROWS, $total)))
								->addClass(ZBX_STYLE_TABLE_STATS)
						))->addClass(ZBX_STYLE_PAGING_BTN_CONTAINER)
					))->addClass(ZBX_STYLE_TABLE_PAGING)
					: null
			],
			'num' => $total,
			'aria-label' => _xn('%1$s message', '%1$s messages', $total, 'screen reader', $total)
		])
		: null;
}

/**
 * Create icon with hintbox for event severity changes.
 *
 * @param array  $data
 * @param array  $data['severities']                    Array of severities.
 * @param string $data['severities'][]['old_severity']  Event severity before change.
 * @param string $data['severities'][]['new_severity']  Event severity after change.
 * @param string $data['severities'][]['clock']         Severity change time.
 * @param string $data['original_severity']             Severity before change.
 * @param string $data['current_severity']              Current severity.
 * @param int    $data['count']                         Total number of severity changes.
 * @param array  $users                                 User name, surname and alias.
 * @param array  $config                                Zabbix config.
 *
 * @return CSpan|null
 */
function makeEventSeverityChangesIcon(array $data, array $users, array $config) {
	$total = $data['count'];

	$table = (new CTableInfo())->setHeader([_('Time'), _('User'), _('Severity changes')]);

	for ($i = 0; $i < $total && $i < ZBX_WIDGET_ROWS; $i++) {
		$severity = $data['severities'][$i];

		// Added in order to reuse makeActionTableUser().
		$severity['action_type'] = ZBX_EVENT_HISTORY_MANUAL_UPDATE;

		// severity changes
		$old_severity_name = getSeverityName($severity['old_severity'], $config);
		$new_severity_name = getSeverityName($severity['new_severity'], $config);

		$table->addRow([
			zbx_date2str(DATE_TIME_FORMAT_SECONDS, $severity['clock']),
			makeActionTableUser($severity, $users),
			$old_severity_name.'&nbsp;&rArr;&nbsp;'.$new_severity_name
		]);
	}

	// select icon
	if ($data['original_severity'] > $data['current_severity']) {
		$icon_style = ZBX_STYLE_ACTION_ICON_SEV_DOWN;
		$aria_label = _x('Severity decreased', 'screen reader');
	}
	elseif ($data['original_severity'] < $data['current_severity']) {
		$icon_style = ZBX_STYLE_ACTION_ICON_SEV_UP;
		$aria_label = _x('Severity increased', 'screen reader');
	}
	else {
		$icon_style = ZBX_STYLE_ACTION_ICON_SEV_CHANGED;
		$aria_label = _x('Severity changed', 'screen reader');
	}

	return $total
		? makeActionIcon([
			'icon' => $icon_style,
			'hint' => [
				$table,
				($total > ZBX_WIDGET_ROWS)
					? (new CDiv(
						(new CDiv(
							(new CDiv(_s('Displaying %1$s of %2$s found', ZBX_WIDGET_ROWS, $total)))
								->addClass(ZBX_STYLE_TABLE_STATS)
						))->addClass(ZBX_STYLE_PAGING_BTN_CONTAINER)
					))->addClass(ZBX_STYLE_TABLE_PAGING)
					: null
			],
			'aria-label' => $aria_label
		])
		: null;
}

/**
 * Create icon with hintbox for event actions.
 *
 * @param array  $data
 * @param array  $data['actions']                   Array with all actions sorted by clock.
 * @param int    $data['actions'][]['action_type']  Type of action table entry (ZBX_EVENT_HISTORY_*).
 * @param string $data['actions'][]['clock']        Time, when action was performed.
 * @param string $data['actions'][]['message']      Message sent by alert, or written by manual update, or remote command text.
 * @param string $data['actions'][]['action']       Flag with problem update operation performed (only for ZBX_EVENT_HISTORY_MANUAL_UPDATE).
 * @param string $data['actions'][]['alerttype']    Type of alert (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['mediatypeid']  Id for mediatype, where alert message was sent (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['error']        Error message in case of failed alert (only for ZBX_EVENT_HISTORY_ALERT).
 * @param int    $data['count']                     Number of actions.
 * @param bool   $data['has_uncomplete_action']     Does the event have at least one uncompleted alert action.
 * @param bool   $data['has_failed_action']         Does the event have at least one failed alert action.
 * @param array  $users                             User name, surname and alias.
 * @param array  $mediatypes                        Mediatypes with maxattempts value and name.
 * @param string $mediatypes[]['name']              Mediatype name.
 * @param array  $config                            Zabbix config.
 *
 * @return CSpan|null
 */
function makeEventActionsIcon(array $data, array $users, array $mediatypes, array $config) {
	// Number of meaningful actions.
	$total = $data['count'];
	// Number of all action entries.
	$action_count = count($data['actions']);

	$table = (new CTableInfo())->setHeader([
		_('Time'), _('User/Recipient'), _('Action'), _('Message/Command'), _('Status'), _('Info')
	]);

	for ($i = 0; $i < $action_count && $i < ZBX_WIDGET_ROWS; $i++) {
		$action = $data['actions'][$i];

		$message = '';
		if ($action['action_type'] == ZBX_EVENT_HISTORY_MANUAL_UPDATE
				&& ($action['action'] & ZBX_PROBLEM_UPDATE_MESSAGE) == ZBX_PROBLEM_UPDATE_MESSAGE) {
			$message = zbx_nl2br($action['message']);
		}
		elseif ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT) {
			if ($action['alerttype'] == ALERT_TYPE_COMMAND) {
				$message = _('Remote command');
			}
			elseif ($action['alerttype'] == ALERT_TYPE_MESSAGE) {
				$message = array_key_exists($action['mediatypeid'], $mediatypes)
					? $mediatypes[$action['mediatypeid']]['name']
					: '';
			}
		}

		$table->addRow([
			zbx_date2str(DATE_TIME_FORMAT_SECONDS, $action['clock']),
			makeActionTableUser($action, $users),
			makeActionTableIcon($action, $config),
			$message,
			makeActionTableStatus($action),
			makeActionTableInfo($action, $mediatypes)
		]);
	}

	// select icon
	if ($data['has_failed_action']) {
		$icon_style = ZBX_STYLE_ACTIONS_NUM_RED;
	}
	elseif ($data['has_uncomplete_action']) {
		$icon_style = ZBX_STYLE_ACTIONS_NUM_YELLOW;
	}
	else {
		$icon_style = ZBX_STYLE_ACTIONS_NUM_GRAY;
	}

	return $total
		? makeActionIcon([
			'icon' => $icon_style,
			'hint' => [
				$table,
				($total > ZBX_WIDGET_ROWS)
					? (new CDiv(
						(new CDiv(
							(new CDiv(_s('Displaying %1$s of %2$s found', ZBX_WIDGET_ROWS, $total)))
								->addClass(ZBX_STYLE_TABLE_STATS)
						))->addClass(ZBX_STYLE_PAGING_BTN_CONTAINER)
					))->addClass(ZBX_STYLE_TABLE_PAGING)
					: null
			],
			'num' => $total,
			'aria-label' => _xn('%1$s action', '%1$s actions', $total, 'screen reader', $total)
		])
		: null;
}

/**
 * Get table with list of event actions for event details page.
 *
 * @param array  $data
 * @param array  $data['actions']                     Array with all actions sorted by clock.
 * @param int    $data['actions'][]['action_type']    Type of action table entry (ZBX_EVENT_HISTORY_*).
 * @param string $data['actions'][]['clock']          Time, when action was performed.
 * @param string $data['actions'][]['message']        Message sent by alert, or written by manual update, or remote command text.
 * @param string $data['actions'][]['alerttype']      Type of alert (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['esc_step']       Alert escalation step (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['subject']        Message alert subject (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['p_eventid']      Problem eventid that was reason for alert (only for ZBX_EVENT_HISTORY_ALERT).
 * @param string $data['actions'][]['acknowledgeid']  Problem update action that was reason for alert (only for ZBX_EVENT_HISTORY_ALERT).
 * @param array  $users                               User name, surname and alias.
 * @param array  $mediatypes                          Mediatypes with maxattempts value.
 * @param array  $config                              Zabbix config.
 *
 * @return CTableInfo
 */
function makeEventDetailsActionsTable(array $data, array $users, array $mediatypes, array $config) {
	$table = (new CTableInfo())->setHeader([
		_('Step'), _('Time'), _('User/Recipient'), _('Action'), _('Message/Command'), _('Status'), _('Info')
	]);

	foreach ($data['actions'] as $action) {
		$esc_step = '';

		if ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT && $action['p_eventid'] == 0
				&& $action['acknowledgeid'] == 0) {
			/*
			 * Escalation step should be displayed, only if alert is caused by problem event.
			 * Escalation step should not be displayed, if alert is caused by resolve event, or by problem update.
			 */
			$esc_step = $action['esc_step'];
		}

		$message = '';
		if ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT && $action['alerttype'] == ALERT_TYPE_MESSAGE) {
			$message = [bold($action['subject']), BR(), BR(), zbx_nl2br($action['message'])];
		}
		elseif (($action['action_type'] == ZBX_EVENT_HISTORY_ALERT && $action['alerttype'] == ALERT_TYPE_COMMAND)
				|| $action['action_type'] == ZBX_EVENT_HISTORY_MANUAL_UPDATE) {
			$message = zbx_nl2br($action['message']);
		}

		$table->addRow([
			$esc_step,
			zbx_date2str(DATE_TIME_FORMAT_SECONDS, $action['clock']),
			makeEventDetailsTableUser($action, $users),
			makeActionTableIcon($action, $config),
			$message,
			makeActionTableStatus($action),
			makeActionTableInfo($action, $mediatypes)
		]);
	}

	return $table;
}

/**
 * Get table with list of event updates for update event page.
 *
 * @param array  $actions                   Array with all actions sorted by clock.
 * @param string $actions[]['clock']        Time, when action was performed.
 * @param string $actions[]['message']      Message sent by alert, or written by manual update, or remote command text.
 * @param array  $users                     User name, surname and alias.
 * @param array  $config                    Zabbix config.
 *
 * @return CTable
 */
function makeEventHistoryTable(array $actions, array $users, array $config) {
	$table = (new CTable())->setHeader([_('Time'), _('User'), _('User action'), _('Message')]);

	foreach ($actions as $action) {
		// Added in order to reuse makeActionTableUser() and makeActionTableIcon()
		$action['action_type'] = ZBX_EVENT_HISTORY_MANUAL_UPDATE;

		$table->addRow([
			zbx_date2str(DATE_TIME_FORMAT_SECONDS, $action['clock']),
			makeActionTableUser($action, $users),
			makeActionTableIcon($action, $config),
			zbx_nl2br($action['message'])
		]);
	}

	return $table;
}

/**
 * Creates username for message author or alert receiver.
 *
 * @param array  $action                 Array of action data.
 * @param int    $action['action_type']  Type of event table action (ZBX_EVENT_HISTORY_*).
 * @param string $action['alerttype']    Type of alert.
 * @param string $action['userid']       ID of message author, or alert receiver.
 * @param array  $users                  Array with user data - alias, name, surname.
 *
 * @return string
 */
function makeActionTableUser(array $action, array $users) {
	if (($action['action_type'] == ZBX_EVENT_HISTORY_ALERT && $action['alerttype'] == ALERT_TYPE_MESSAGE)
			|| $action['action_type'] == ZBX_EVENT_HISTORY_MANUAL_UPDATE) {
		return array_key_exists($action['userid'], $users)
			? getUserFullname($users[$action['userid']])
			: _('Inaccessible user');
	}
	else {
		return '';
	}
}

/**
 * Creates username for message author or alert receiver. Also contains 'sendto' for message actions.
 *
 * @param array  $action
 * @param int    $action['action_type']  Type of event table action (ZBX_EVENT_HISTORY_*).
 * @param string $action['alerttype']    Type of alert.
 * @param array  $action['userid']       ID of message author, or alert receiver.
 * @param array  $action['sendto']       Receiver media address for automatic action.
 * @param array  $users                  Array with user data - alias, name, surname.
 *
 * @return string
 */
function makeEventDetailsTableUser(array $action, array $users) {
	if ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT && $action['alerttype'] == ALERT_TYPE_MESSAGE) {
		return array_key_exists($action['userid'], $users)
			? [getUserFullname($users[$action['userid']]), BR(), italic(zbx_nl2br($action['sendto']))]
			: _('Inaccessible user');
	}
	elseif ($action['action_type'] == ZBX_EVENT_HISTORY_MANUAL_UPDATE) {
		return array_key_exists($action['userid'], $users)
			? getUserFullname($users[$action['userid']])
			: _('Inaccessible user');
	}
	else {
		return '';
	}
}

/**
 * Make appropriate icon for event action.
 *
 * @param array  $action                  Array with actions table data.
 * @param int    $action['action_type']   Type of action table entry (ZBX_EVENT_HISTORY_*).
 * @param int    $action['action']        Flag with problem update operation performed. (only for ZBX_EVENT_HISTORY_MANUAL_UPDATE)
 * @param int    $action['old_severity']  Severity before problem update. (only for ZBX_EVENT_HISTORY_MANUAL_UPDATE)
 * @param int    $action['new_severity']  Severity after problem update. (only for ZBX_EVENT_HISTORY_MANUAL_UPDATE)
 * @param int    $action['alerttype']     Type of alert. (only for ZBX_EVENT_HISTORY_ALERT)
 * @param array  $config                  Zabbix config.
 *
 * @return CSpan
 */
function makeActionTableIcon(array $action, array $config) {
	switch ($action['action_type']) {
		case ZBX_EVENT_HISTORY_PROBLEM_EVENT:
			return makeActionIcon(['icon' => ZBX_STYLE_PROBLEM_GENERATED, 'title' => _('Problem created')]);

		case ZBX_EVENT_HISTORY_RECOVERY_EVENT:
			return makeActionIcon(['icon' => ZBX_STYLE_PROBLEM_RECOVERY, 'title' => _('Problem resolved')]);

		case ZBX_EVENT_HISTORY_MANUAL_UPDATE:
			$action_icons = [];

			if (($action['action'] & ZBX_PROBLEM_UPDATE_CLOSE) == ZBX_PROBLEM_UPDATE_CLOSE) {
				$action_icons[] = makeActionIcon([
					'icon' => ZBX_STYLE_ACTION_ICON_CLOSE,
					'title' => _('Manually closed')
				]);
			}

			if (($action['action'] & ZBX_PROBLEM_UPDATE_ACKNOWLEDGE) == ZBX_PROBLEM_UPDATE_ACKNOWLEDGE) {
				$action_icons[] = makeActionIcon(['icon' => ZBX_STYLE_ACTION_ICON_ACK, 'title' => _('Acknowledged')]);
			}

			if (($action['action'] & ZBX_PROBLEM_UPDATE_MESSAGE) == ZBX_PROBLEM_UPDATE_MESSAGE) {
				$action_icons[] = makeActionIcon(['icon' => ZBX_STYLE_ACTION_ICON_MSG, 'title' => _('Message')]);
			}

			if (($action['action'] & ZBX_PROBLEM_UPDATE_SEVERITY) == ZBX_PROBLEM_UPDATE_SEVERITY) {
				$action_type = ($action['new_severity'] > $action['old_severity'])
					? ZBX_STYLE_ACTION_ICON_SEV_UP
					: ZBX_STYLE_ACTION_ICON_SEV_DOWN;

				$old_severity_name = getSeverityName($action['old_severity'], $config);
				$new_severity_name = getSeverityName($action['new_severity'], $config);
				$hint = $old_severity_name.'&nbsp;&rArr;&nbsp;'.$new_severity_name;

				$action_icons[] = makeActionIcon(['icon' => $action_type, 'hint' => $hint]);
			}

			return (new CCol($action_icons))->addClass(ZBX_STYLE_NOWRAP);

		case ZBX_EVENT_HISTORY_ALERT:
			$action_icon = ($action['alerttype'] == ALERT_TYPE_COMMAND)
					? ZBX_STYLE_ACTION_COMMAND
					: ZBX_STYLE_ACTION_MESSAGE;
			$title = ($action['alerttype'] == ALERT_TYPE_COMMAND)
				? _('Remote command')
				: _('Alert message');

			return makeActionIcon(['icon' => $action_icon, 'title' => $title]);
	}
}

/**
 * Creates span with alert status text.
 *
 * @param array  $action
 * @param int    $action['action_type']  Type of event table action (ZBX_EVENT_HISTORY_*).
 * @param string $action['status']       Alert status.
 * @param string $action['alerttype']    Type of alert.
 *
 * @return CSpan|string
 */
function makeActionTableStatus(array $action) {
	if ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT) {
		switch ($action['status']) {
			case ALERT_STATUS_SENT:
				$status_label = ($action['alerttype'] == ALERT_TYPE_COMMAND)
					? _('Executed')
					: _('Sent');
				$status_color = ZBX_STYLE_GREEN;
				break;

			case ALERT_STATUS_NEW:
			case ALERT_STATUS_NOT_SENT:
				$status_label = _('In progress');
				$status_color = ZBX_STYLE_YELLOW;
				break;

			default:
				$status_label = _('Failed');
				$status_color = ZBX_STYLE_RED;
				break;
		}

		return (new CSpan($status_label))->addClass($status_color);
	}
	else {
		return '';
	}
}

/**
 * Creates div with alert info icons.
 *
 * @param array  $action
 * @param int    $action['action_type']        Type of event table action (ZBX_EVENT_HISTORY_*).
 * @param string $action['status']             Alert status.
 * @param string $action['alerttype']          Type of alert.
 * @param string $action['mediatypeid']        ID for mediatype, where alert message was sent.
 * @param string $action['retries']            How many retries was done for pending alert message.
 * @param array  $mediatypes                   Array of media type data.
 * @param array  $mediatypes[]['maxattempts']  Maximum attempts for this mediatype.
 *
 * @return CDiv|string
 */
function makeActionTableInfo(array $action, array $mediatypes) {
	if ($action['action_type'] == ZBX_EVENT_HISTORY_ALERT) {
		$info_icons = [];

		if ($action['alerttype'] == ALERT_TYPE_MESSAGE
				&& ($action['status'] == ALERT_STATUS_NEW || $action['status'] == ALERT_STATUS_NOT_SENT)) {
			$info_icons[] = makeWarningIcon(array_key_exists($action['mediatypeid'], $mediatypes)
				? _n('%1$s retry left', '%1$s retries left',
						$mediatypes[$action['mediatypeid']]['maxattempts'] - $action['retries']
				)
				: ''
			);
		}
		elseif ($action['error'] !== '') {
			$info_icons[] = makeErrorIcon($action['error']);
		}

		return makeInformationList($info_icons);
	}
	else {
		return '';
	}
}
