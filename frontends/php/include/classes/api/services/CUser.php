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


/**
 * Class containing methods for operations with users.
 */
class CUser extends CApiService {

	protected $tableName = 'users';
	protected $tableAlias = 'u';
	protected $sortColumns = ['userid', 'alias'];

	/**
	 * Get users data.
	 *
	 * @param array  $options
	 * @param array  $options['usrgrpids']		filter by UserGroup IDs
	 * @param array  $options['userids']		filter by User IDs
	 * @param bool   $options['type']			filter by User type [USER_TYPE_ZABBIX_USER: 1, USER_TYPE_ZABBIX_ADMIN: 2, USER_TYPE_SUPER_ADMIN: 3]
	 * @param bool   $options['selectUsrgrps']	extend with UserGroups data for each User
	 * @param bool   $options['getAccess']		extend with access data for each User
	 * @param bool   $options['count']			output only count of objects in result. (result returned in property 'rowscount')
	 * @param string $options['pattern']		filter by Host name containing only give pattern
	 * @param int    $options['limit']			output will be limited to given number
	 * @param string $options['sortfield']		output will be sorted by given property ['userid', 'alias']
	 * @param string $options['sortorder']		output will be sorted in given order ['ASC', 'DESC']
	 *
	 * @return array
	 */
	public function get($options = []) {
		$result = [];

		$sqlParts = [
			'select'	=> ['users' => 'u.userid'],
			'from'		=> ['users' => 'users u'],
			'where'		=> [],
			'order'		=> [],
			'limit'		=> null
		];

		$defOptions = [
			'usrgrpids'					=> null,
			'userids'					=> null,
			'mediaids'					=> null,
			'mediatypeids'				=> null,
			// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'				=> null,
			'startSearch'				=> false,
			'excludeSearch'				=> false,
			'searchWildcardsEnabled'	=> null,
			// output
			'output'					=> API_OUTPUT_EXTEND,
			'editable'					=> false,
			'selectUsrgrps'				=> null,
			'selectMedias'				=> null,
			'selectMediatypes'			=> null,
			'getAccess'					=> null,
			'countOutput'				=> false,
			'preservekeys'				=> false,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		];
		$options = zbx_array_merge($defOptions, $options);

		// permission check
		if (self::$userData['type'] != USER_TYPE_SUPER_ADMIN) {
			if (!$options['editable']) {
				$sqlParts['from']['users_groups'] = 'users_groups ug';
				$sqlParts['where']['uug'] = 'u.userid=ug.userid';
				$sqlParts['where'][] = 'ug.usrgrpid IN ('.
					' SELECT uug.usrgrpid'.
					' FROM users_groups uug'.
					' WHERE uug.userid='.self::$userData['userid'].
				')';
			}
			else {
				$sqlParts['where'][] = 'u.userid='.self::$userData['userid'];
			}
		}

		// userids
		if ($options['userids'] !== null) {
			zbx_value2array($options['userids']);

			$sqlParts['where'][] = dbConditionInt('u.userid', $options['userids']);
		}

		// usrgrpids
		if ($options['usrgrpids'] !== null) {
			zbx_value2array($options['usrgrpids']);

			$sqlParts['from']['users_groups'] = 'users_groups ug';
			$sqlParts['where'][] = dbConditionInt('ug.usrgrpid', $options['usrgrpids']);
			$sqlParts['where']['uug'] = 'u.userid=ug.userid';
		}

		// mediaids
		if ($options['mediaids'] !== null) {
			zbx_value2array($options['mediaids']);

			$sqlParts['from']['media'] = 'media m';
			$sqlParts['where'][] = dbConditionInt('m.mediaid', $options['mediaids']);
			$sqlParts['where']['mu'] = 'm.userid=u.userid';
		}

		// mediatypeids
		if ($options['mediatypeids'] !== null) {
			zbx_value2array($options['mediatypeids']);

			$sqlParts['from']['media'] = 'media m';
			$sqlParts['where'][] = dbConditionInt('m.mediatypeid', $options['mediatypeids']);
			$sqlParts['where']['mu'] = 'm.userid=u.userid';
		}

		// filter
		if (is_array($options['filter'])) {
			if (array_key_exists('autologout', $options['filter']) && $options['filter']['autologout'] !== null) {
				$options['filter']['autologout'] = getTimeUnitFilters($options['filter']['autologout']);
			}

			if (array_key_exists('refresh', $options['filter']) && $options['filter']['refresh'] !== null) {
				$options['filter']['refresh'] = getTimeUnitFilters($options['filter']['refresh']);
			}

			if (isset($options['filter']['passwd'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('It is not possible to filter by user password.'));
			}

			$this->dbFilter('users u', $options, $sqlParts);
		}

		// search
		if (is_array($options['search'])) {
			if (isset($options['search']['passwd'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('It is not possible to search by user password.'));
			}

			zbx_db_search('users u', $options, $sqlParts);
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		$userIds = [];

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$res = DBselect($this->createSelectQueryFromParts($sqlParts), $sqlParts['limit']);

		while ($user = DBfetch($res)) {
			unset($user['passwd']);

			if ($options['countOutput']) {
				$result = $user['rowscount'];
			}
			else {
				$userIds[$user['userid']] = $user['userid'];

				$result[$user['userid']] = $user;
			}
		}

		if ($options['countOutput']) {
			return $result;
		}

		/*
		 * Adding objects
		 */
		if ($options['getAccess'] !== null) {
			foreach ($result as $userid => $user) {
				$result[$userid] += ['gui_access' => 0, 'debug_mode' => 0, 'users_status' => 0];
			}

			$access = DBselect(
				'SELECT ug.userid,MAX(g.gui_access) AS gui_access,'.
					' MAX(g.debug_mode) AS debug_mode,MAX(g.users_status) AS users_status'.
					' FROM usrgrp g,users_groups ug'.
					' WHERE '.dbConditionInt('ug.userid', $userIds).
						' AND g.usrgrpid=ug.usrgrpid'.
					' GROUP BY ug.userid'
			);

			while ($userAccess = DBfetch($access)) {
				$result[$userAccess['userid']] = zbx_array_merge($result[$userAccess['userid']], $userAccess);
			}
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
		}

		// removing keys
		if (!$options['preservekeys']) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * @param array $users
	 *
	 * @return array
	 */
	public function create(array $users) {
		$this->validateCreate($users);

		$ins_users = [];

		foreach ($users as $user) {
			unset($user['usrgrps'], $user['user_medias']);
			$ins_users[] = $user;
		}
		$userids = DB::insert('users', $ins_users);

		foreach ($users as $index => &$user) {
			$user['userid'] = $userids[$index];
		}
		unset($user);

		$this->updateUsersGroups($users, __FUNCTION__);
		$this->updateMedias($users, __FUNCTION__);

		$this->addAuditBulk(AUDIT_ACTION_ADD, AUDIT_RESOURCE_USER, $users);

		return ['userids' => $userids];
	}

	/**
	 * @param array $users
	 *
	 * @throws APIException if the input is invalid.
	 */
	private function validateCreate(array &$users) {
		if (self::$userData['type'] != USER_TYPE_SUPER_ADMIN) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('You do not have permissions to create users.'));
		}

		$locales = array_keys(getLocales());
		$themes = THEME_DEFAULT.','.implode(',', array_keys(Z::getThemes()));

		$api_input_rules = ['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY | API_NORMALIZE, 'uniq' => [['alias']], 'fields' => [
			'alias' =>			['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('users', 'alias')],
			'name' =>			['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('users', 'name')],
			'surname' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('users', 'surname')],
			'passwd' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => 255],
			'url' =>			['type' => API_URL, 'length' => DB::getFieldLength('users', 'url')],
			'autologin' =>		['type' => API_INT32, 'in' => '0,1'],
			'autologout' =>		['type' => API_TIME_UNIT, 'flags' => API_NOT_EMPTY, 'in' => '0,90:'.SEC_PER_DAY],
			'lang' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'in' => implode(',', $locales)],
			'theme' =>			['type' => API_STRING_UTF8, 'in' => $themes, 'length' => DB::getFieldLength('users', 'theme')],
			'type' =>			['type' => API_INT32, 'in' => implode(',', [USER_TYPE_ZABBIX_USER, USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])],
			'refresh' =>		['type' => API_TIME_UNIT, 'flags' => API_NOT_EMPTY, 'in' => '0:'.SEC_PER_HOUR],
			'rows_per_page' =>	['type' => API_INT32, 'in' => '1:999999'],
			'usrgrps' =>		['type' => API_OBJECTS, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'uniq' => [['usrgrpid']], 'fields' => [
				'usrgrpid' =>		['type' => API_ID, 'flags' => API_REQUIRED]
			]],
			'user_medias' =>	['type' => API_OBJECTS, 'fields' => [
				'mediatypeid' =>	['type' => API_ID, 'flags' => API_REQUIRED],
				'sendto' =>			['type' => API_STRINGS_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY | API_NORMALIZE],
				'active' =>			['type' => API_INT32, 'in' => implode(',', [MEDIA_STATUS_ACTIVE, MEDIA_STATUS_DISABLED])],
				'severity' =>		['type' => API_INT32, 'in' => '0:63'],
				'period' =>			['type' => API_TIME_PERIOD, 'flags' => API_ALLOW_USER_MACRO, 'length' => DB::getFieldLength('media', 'period')]
			]]
		]];
		if (!CApiInputValidator::validate($api_input_rules, $users, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		foreach ($users as &$user) {
			$user = $this->checkLoginOptions($user);

			/*
			 * If user is created without a password (e.g. for GROUP_GUI_ACCESS_LDAP), store an empty string
			 * as his password in database.
			 */
			$user['passwd'] = (array_key_exists('passwd', $user)) ? md5($user['passwd']) : '';
		}
		unset($user);

		$this->checkDuplicates(zbx_objectValues($users, 'alias'));
		$this->checkLanguages(zbx_objectValues($users, 'lang'));
		$this->checkUserGroups($users, []);
		$db_mediatypes = $this->checkMediaTypes($users);
		$this->validateMediaRecipients($users, $db_mediatypes);
	}

	/**
	 * @param array $users
	 *
	 * @return array
	 */
	public function update(array $users) {
		$this->validateUpdate($users, $db_users);

		$upd_users = [];

		foreach ($users as $user) {
			$db_user = $db_users[$user['userid']];

			$upd_user = [];

			// strings
			$field_names = ['alias', 'name', 'surname', 'autologout', 'passwd', 'refresh', 'url', 'lang', 'theme'];
			foreach ($field_names as $field_name) {
				if (array_key_exists($field_name, $user) && $user[$field_name] !== $db_user[$field_name]) {
					$upd_user[$field_name] = $user[$field_name];
				}
			}

			// integers
			foreach (['autologin', 'type', 'rows_per_page'] as $field_name) {
				if (array_key_exists($field_name, $user) && $user[$field_name] != $db_user[$field_name]) {
					$upd_user[$field_name] = $user[$field_name];
				}
			}

			if ($upd_user) {
				$upd_users[] = [
					'values' => $upd_user,
					'where' => ['userid' => $user['userid']]
				];
			}
		}

		if ($upd_users) {
			DB::update('users', $upd_users);
		}

		$this->updateUsersGroups($users, __FUNCTION__);
		$this->updateMedias($users, __FUNCTION__);

		$this->addAuditBulk(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, $users, $db_users);

		return ['userids' => zbx_objectValues($users, 'userid')];
	}

	/**
	 * @param array $users
	 * @param array $db_users
	 *
	 * @throws APIException if the input is invalid.
	 */
	private function validateUpdate(array &$users, array &$db_users = null) {
		$locales = array_keys(getLocales());
		$themes = THEME_DEFAULT.','.implode(',', array_keys(Z::getThemes()));

		$api_input_rules = ['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY | API_NORMALIZE, 'uniq' => [['userid'], ['alias']], 'fields' => [
			'userid' =>			['type' => API_ID, 'flags' => API_REQUIRED],
			'alias' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('users', 'alias')],
			'name' =>			['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('users', 'name')],
			'surname' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('users', 'surname')],
			'passwd' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => 255],
			'url' =>			['type' => API_URL, 'length' => DB::getFieldLength('users', 'url')],
			'autologin' =>		['type' => API_INT32, 'in' => '0,1'],
			'autologout' =>		['type' => API_TIME_UNIT, 'flags' => API_NOT_EMPTY, 'in' => '0,90:'.SEC_PER_DAY],
			'lang' =>			['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'in' => implode(',', $locales)],
			'theme' =>			['type' => API_STRING_UTF8, 'in' => $themes, 'length' => DB::getFieldLength('users', 'theme')],
			'type' =>			['type' => API_INT32, 'in' => implode(',', [USER_TYPE_ZABBIX_USER, USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])],
			'refresh' =>		['type' => API_TIME_UNIT, 'flags' => API_NOT_EMPTY, 'in' => '0:'.SEC_PER_HOUR],
			'rows_per_page' =>	['type' => API_INT32, 'in' => '1:999999'],
			'usrgrps' =>		['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY, 'uniq' => [['usrgrpid']], 'fields' => [
				'usrgrpid' =>		['type' => API_ID, 'flags' => API_REQUIRED]
			]],
			'user_medias' =>	['type' => API_OBJECTS, 'fields' => [
				'mediatypeid' =>	['type' => API_ID, 'flags' => API_REQUIRED],
				'sendto' =>			['type' => API_STRINGS_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY | API_NORMALIZE],
				'active' =>			['type' => API_INT32, 'in' => implode(',', [MEDIA_STATUS_ACTIVE, MEDIA_STATUS_DISABLED])],
				'severity' =>		['type' => API_INT32, 'in' => '0:63'],
				'period' =>			['type' => API_TIME_PERIOD, 'flags' => API_ALLOW_USER_MACRO, 'length' => DB::getFieldLength('media', 'period')]
			]]
		]];
		if (!CApiInputValidator::validate($api_input_rules, $users, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$db_users = $this->get([
			'output' => [],
			'userids' => zbx_objectValues($users, 'userid'),
			'editable' => true,
			'preservekeys' => true
		]);

		// 'passwd' can't be received by the user.get method
		$db_users = DB::select('users', [
			'output' => ['userid', 'alias', 'name', 'surname', 'passwd', 'url', 'autologin', 'autologout', 'lang',
				'theme', 'type', 'refresh', 'rows_per_page'
			],
			'userids' => array_keys($db_users),
			'preservekeys' => true
		]);

		$aliases = [];

		foreach ($users as &$user) {
			if (!array_key_exists($user['userid'], $db_users)) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_('No permissions to referred object or it does not exist!')
				);
			}

			$db_user = $db_users[$user['userid']];

			if (array_key_exists('alias', $user) && $user['alias'] !== $db_user['alias']) {
				if ($db_user['alias'] === ZBX_GUEST_USER) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot rename guest user.'));
				}

				$aliases[] = $user['alias'];
			}

			$user = $this->checkLoginOptions($user);

			if (array_key_exists('passwd', $user)) {
				if ($db_user['alias'] == ZBX_GUEST_USER) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Not allowed to set password for user "guest".'));
				}

				$user['passwd'] = md5($user['passwd']);
			}
		}
		unset($user);

		if ($aliases) {
			$this->checkDuplicates($aliases);
		}
		$this->checkLanguages(zbx_objectValues($users, 'lang'));
		$this->checkUserGroups($users, $db_users);
		$db_mediatypes = $this->checkMediaTypes($users);
		$this->validateMediaRecipients($users, $db_mediatypes);
		$this->checkHimself($users);
	}

	/**
	 * Check for duplicated users.
	 *
	 * @param array $aliases
	 *
	 * @throws APIException  if user already exists.
	 */
	private function checkDuplicates(array $aliases) {
		$db_users = DB::select('users', [
			'output' => ['alias'],
			'filter' => ['alias' => $aliases],
			'limit' => 1
		]);

		if ($db_users) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('User with alias "%s" already exists.', $db_users[0]['alias'])
			);
		}
	}

	/**
	 * Check for valid user groups.
	 *
	 * @param array $users
	 * @param array $users[]['passwd']  (optional)
	 * @param array $users[]['usrgrps']  (optional)
	 * @param array $db_users
	 * @param array $db_users[]['passwd']
	 *
	 * @throws APIException  if user groups is not exists.
	 */
	private function checkUserGroups(array $users, array $db_users) {
		$usrgrpids = [];

		foreach ($users as $user) {
			if (array_key_exists('usrgrps', $user)) {
				foreach ($user['usrgrps'] as $usrgrp) {
					$usrgrpids[$usrgrp['usrgrpid']] = true;
				}
			}
		}

		if (!$usrgrpids) {
			return;
		}

		$usrgrpids = array_keys($usrgrpids);

		$db_usrgrps = DB::select('usrgrp', [
			'output' => ['gui_access'],
			'usrgrpids' => $usrgrpids,
			'preservekeys' => true
		]);

		foreach ($usrgrpids as $usrgrpid) {
			if (!array_key_exists($usrgrpid, $db_usrgrps)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('User group with ID "%1$s" is not available.', $usrgrpid));
			}
		}

		foreach ($users as $user) {
			if (array_key_exists('passwd', $user)) {
				$passwd = $user['passwd'];
			}
			elseif (array_key_exists('userid', $user) && array_key_exists($user['userid'], $db_users)) {
				$passwd = $db_users[$user['userid']]['passwd'];
			}
			else {
				$passwd = '';
			}

			// Do not allow empty password for users with GROUP_GUI_ACCESS_INTERNAL.
			if ($passwd === '' && self::hasInternalAuth($user, $db_usrgrps)) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Incorrect value for field "%1$s": %2$s.', 'passwd', _('cannot be empty'))
				);
			}
		}
	}

	/**
	 * Check if specified language has dependent locale installed.
	 *
	 * @param array $languages
	 *
	 * @throws APIException if language locale is not installed.
	 */
	private function checkLanguages(array $languages) {
		foreach ($languages as $lang) {
			if ($lang !== 'en_GB' && !setlocale(LC_MONETARY, zbx_locale_variants($lang))) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Language "%1$s" is not supported.', $lang));
			}
		}
	}

	/**
	 * Returns true if user has internal authentication type.
	 *
	 * @param array  $user
	 * @param string $user['usrgrps'][]['usrgrpid']
	 * @param array  $db_usrgrps
	 * @param int    $db_usrgrps[usrgrpid]['gui_access']
	 *
	 * @return bool
	 */
	private static function hasInternalAuth($user, $db_usrgrps) {
		$config = select_config();
		$system_gui_access = ($config['authentication_type'] == ZBX_AUTH_INTERNAL)
			? GROUP_GUI_ACCESS_INTERNAL
			: GROUP_GUI_ACCESS_LDAP;

		foreach($user['usrgrps'] as $usrgrp) {
			$gui_access = (int) $db_usrgrps[$usrgrp['usrgrpid']]['gui_access'];
			$gui_access = ($gui_access == GROUP_GUI_ACCESS_SYSTEM) ? $system_gui_access : $gui_access;

			if ($gui_access == GROUP_GUI_ACCESS_INTERNAL) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for valid media types.
	 *
	 * @param array $users                               Array of users.
	 * @param array $users[]['user_medias']  (optional)  Array of user medias.
	 *
	 * @throws APIException if user media type does not exist.
	 *
	 * @return array                                     Returns valid media types.
	 */
	private function checkMediaTypes(array $users) {
		$mediatypeids = [];

		foreach ($users as $user) {
			if (array_key_exists('user_medias', $user)) {
				foreach ($user['user_medias'] as $media) {
					$mediatypeids[$media['mediatypeid']] = true;
				}
			}
		}

		if (!$mediatypeids) {
			return [];
		}

		$mediatypeids = array_keys($mediatypeids);

		$db_mediatypes = DB::select('media_type', [
			'output' => ['mediatypeid', 'type'],
			'mediatypeids' => $mediatypeids,
			'preservekeys' => true
		]);

		foreach ($mediatypeids as $mediatypeid) {
			if (!array_key_exists($mediatypeid, $db_mediatypes)) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Media type with ID "%1$s" is not available.', $mediatypeid)
				);
			}
		}

		return $db_mediatypes;
	}

	/**
	 * Check if the passed 'sendto' value is a valid input according to the mediatype. Currently validates
	 * only e-mail media types.
	 *
	 * @param array         $users                                    Array of users.
	 * @param string        $users[]['user_medias'][]['mediatypeid']  Media type ID.
	 * @param array|string  $users[]['user_medias'][]['sendto']       Address where to send the alert.
	 * @param array         $db_mediatypes                            List of available media types.
	 *
	 * @throws APIException if e-mail is not valid or exceeds maximum DB field length.
	 */
	private function validateMediaRecipients(array $users, array $db_mediatypes) {
		if ($db_mediatypes) {
			$email_mediatypes = [];

			foreach ($db_mediatypes as $db_mediatype) {
				if ($db_mediatype['type'] == MEDIA_TYPE_EMAIL) {
					$email_mediatypes[$db_mediatype['mediatypeid']] = true;
				}
			}

			$max_length = DB::getFieldLength('media', 'sendto');
			$email_validator = new CEmailValidator();

			foreach ($users as $user) {
				if (array_key_exists('user_medias', $user)) {
					foreach ($user['user_medias'] as $media) {
						/*
						 * For non-email media types only one value allowed. Since value is normalized, need to validate
						 * if array contains only one item. If there are more than one string, error message is
						 * displayed, indicating that passed value is not a string.
						 */
						if (!array_key_exists($media['mediatypeid'], $email_mediatypes)
								&& count($media['sendto']) > 1) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Invalid parameter "%1$s": %2$s.', 'sendto', _('a character string is expected'))
							);
						}

						/*
						 * If input value is an array with empty string, ApiInputValidator identifies it as valid since
						 * values are normalized. That's why value must be revalidated.
						 */
						foreach ($media['sendto'] as $sendto) {
							if ($sendto === '') {
								self::exception(ZBX_API_ERROR_PARAMETERS,
									_s('Invalid parameter "%1$s": %2$s.', 'sendto', _('cannot be empty'))
								);
							}
						}

						/*
						 * If media type is email, validate each given string against email pattern.
						 * Additionally, total length of emails must be checked, because all media type emails are
						 * separated by newline and stored as a string in single database field. Newline characters
						 * consumes extra space, so additional validation must be made.
						 */
						if (array_key_exists($media['mediatypeid'], $email_mediatypes)) {
							foreach ($media['sendto'] as $sendto) {
								if (!$email_validator->validate($sendto)) {
									self::exception(ZBX_API_ERROR_PARAMETERS,
										_s('Invalid email address for media type with ID "%1$s".',
											$media['mediatypeid']
										)
									);
								}
								elseif (strlen(implode("\n", $media['sendto'])) > $max_length) {
									self::exception(ZBX_API_ERROR_PARAMETERS,
										_s('Maximum total length of email address exceeded for media type with ID "%1$s".',
											$media['mediatypeid']
										)
									);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Additional check to exclude an opportunity to deactivate himself.
	 *
	 * @param array  $users
	 * @param array  $users[]['usrgrps']  (optional)
	 *
	 * @throws APIException
	 */
	private function checkHimself(array $users) {
		foreach ($users as $user) {
			if (bccomp($user['userid'], self::$userData['userid']) == 0) {
				if (array_key_exists('type', $user)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('User cannot change their user type.'));
				}

				if (array_key_exists('usrgrps', $user)) {
					$db_usrgrps = DB::select('usrgrp', [
						'output' => ['gui_access', 'users_status'],
						'usrgrpids' => zbx_objectValues($user['usrgrps'], 'usrgrpid')
					]);

					foreach ($db_usrgrps as $db_usrgrp) {
						if ($db_usrgrp['gui_access'] == GROUP_GUI_ACCESS_DISABLED
								|| $db_usrgrp['users_status'] == GROUP_STATUS_DISABLED) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_('User cannot add himself to a disabled group or a group with disabled GUI access.')
							);
						}
					}
				}

				break;
			}
		}
	}

	/**
	 * Additional check to exclude an opportunity to enable auto-login and auto-logout options together..
	 *
	 * @param array  $user
	 * @param int    $user[]['autologin']   (optional)
	 * @param string $user[]['autologout']  (optional)
	 *
	 * @throws APIException
	 */
	private function checkLoginOptions(array $user) {
		if (!array_key_exists('autologout', $user) && array_key_exists('autologin', $user) && $user['autologin'] != 0) {
			$user['autologout'] = '0';
		}

		if (!array_key_exists('autologin', $user) && array_key_exists('autologout', $user)
				&& timeUnitToSeconds($user['autologout']) != 0) {
			$user['autologin'] = 0;
		}

		if (array_key_exists('autologin', $user) && array_key_exists('autologout', $user)
				&& $user['autologin'] != 0 && timeUnitToSeconds($user['autologout']) != 0) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_('Auto-login and auto-logout options cannot be enabled together.')
			);
		}

		return $user;
	}

	/**
	 * Update table "users_groups".
	 *
	 * @param array  $users
	 * @param string $method
	 */
	private function updateUsersGroups(array $users, $method) {
		$users_groups = [];

		foreach ($users as $user) {
			if (array_key_exists('usrgrps', $user)) {
				$users_groups[$user['userid']] = [];

				foreach ($user['usrgrps'] as $usrgrp) {
					$users_groups[$user['userid']][$usrgrp['usrgrpid']] = true;
				}
			}
		}

		if (!$users_groups) {
			return;
		}

		$db_users_groups = ($method === 'update')
			? DB::select('users_groups', [
				'output' => ['id', 'usrgrpid', 'userid'],
				'filter' => ['userid' => array_keys($users_groups)]
			])
			: [];

		$ins_users_groups = [];
		$del_ids = [];

		foreach ($db_users_groups as $db_user_group) {
			if (array_key_exists($db_user_group['usrgrpid'], $users_groups[$db_user_group['userid']])) {
				unset($users_groups[$db_user_group['userid']][$db_user_group['usrgrpid']]);
			}
			else {
				$del_ids[] = $db_user_group['id'];
			}
		}

		foreach ($users_groups as $userid => $usrgrpids) {
			foreach (array_keys($usrgrpids) as $usrgrpid) {
				$ins_users_groups[] = [
					'userid' => $userid,
					'usrgrpid' => $usrgrpid
				];
			}
		}

		if ($ins_users_groups) {
			DB::insertBatch('users_groups', $ins_users_groups);
		}

		if ($del_ids) {
			DB::delete('users_groups', ['id' => $del_ids]);
		}
	}

	/**
	 * Auxiliary function for updateMedias().
	 *
	 * @param array  $medias
	 * @param string $mediatypeid
	 * @param string $sendto
	 *
	 * @return int
	 */
	private function getSimilarMedia(array $medias, $mediatypeid, $sendto) {
		foreach ($medias as $index => $media) {
			if (bccomp($media['mediatypeid'], $mediatypeid) == 0 && $media['sendto'] === $sendto) {
				return $index;
			}
		}

		return -1;
	}

	/**
	 * Update table "media".
	 *
	 * @param array  $users
	 * @param string $method
	 */
	private function updateMedias(array $users, $method) {
		$medias = [];

		foreach ($users as $user) {
			if (array_key_exists('user_medias', $user)) {
				$medias[$user['userid']] = [];

				foreach ($user['user_medias'] as $media) {
					$media['sendto'] = implode("\n", $media['sendto']);
					$medias[$user['userid']][] = $media;
				}
			}
		}

		if (!$medias) {
			return;
		}

		$db_medias = ($method === 'update')
			? DB::select('media', [
				'output' => ['mediaid', 'userid', 'mediatypeid', 'sendto', 'active', 'severity', 'period'],
				'filter' => ['userid' => array_keys($medias)]
			])
			: [];

		$ins_medias = [];
		$upd_medias = [];
		$del_mediaids = [];

		foreach ($db_medias as $db_media) {
			$index = $this->getSimilarMedia($medias[$db_media['userid']], $db_media['mediatypeid'],
				$db_media['sendto']
			);

			if ($index != -1) {
				$media = $medias[$db_media['userid']][$index];

				$upd_media = [];

				if (array_key_exists('active', $media) && $media['active'] != $db_media['active']) {
					$upd_media['active'] = $media['active'];
				}
				if (array_key_exists('severity', $media) && $media['severity'] != $db_media['severity']) {
					$upd_media['severity'] = $media['severity'];
				}
				if (array_key_exists('period', $media) && $media['period'] !== $db_media['period']) {
					$upd_media['period'] = $media['period'];
				}

				if ($upd_media) {
					$upd_medias[] = [
						'values' => $upd_media,
						'where' => ['mediaid' => $db_media['mediaid']]
					];
				}

				unset($medias[$db_media['userid']][$index]);
			}
			else {
				$del_mediaids[] = $db_media['mediaid'];
			}
		}

		foreach ($medias as $userid => $user_medias) {
			foreach ($user_medias as $media) {
				$ins_medias[] = ['userid' => $userid] + $media;
			}
		}

		if ($ins_medias) {
			DB::insert('media', $ins_medias);
		}

		if ($upd_medias) {
			DB::update('media', $upd_medias);
		}

		if ($del_mediaids) {
			DB::delete('media', ['mediaid' => $del_mediaids]);
		}
	}

	/**
	 * @param array $userids
	 *
	 * @return array
	 */
	public function delete(array $userids) {
		$this->validateDelete($userids, $db_users);

		DB::delete('media', ['userid' => $userids]);
		DB::delete('profiles', ['userid' => $userids]);
		DB::delete('users_groups', ['userid' => $userids]);
		DB::delete('users', ['userid' => $userids]);

		$this->addAuditBulk(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, $db_users);

		return ['userids' => $userids];
	}

	/**
	 * @param array $userids
	 * @param array $db_users
	 *
	 * @throws APIException if the input is invalid.
	 */
	private function validateDelete(array &$userids, array &$db_users = null) {
		$api_input_rules = ['type' => API_IDS, 'flags' => API_NOT_EMPTY, 'uniq' => true];
		if (!CApiInputValidator::validate($api_input_rules, $userids, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$db_users = $this->get([
			'output' => ['userid', 'alias'],
			'userids' => $userids,
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($userids as $userid) {
			if (!array_key_exists($userid, $db_users)) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_('No permissions to referred object or it does not exist!')
				);
			}

			$db_user = $db_users[$userid];

			if (bccomp($userid, self::$userData['userid']) == 0) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('User is not allowed to delete himself.'));
			}

			if ($db_user['alias'] == ZBX_GUEST_USER) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Cannot delete Zabbix internal user "%1$s", try disabling that user.', ZBX_GUEST_USER)
				);
			}
		}

		// Check if deleted users used in actions.
		$db_actions = DBselect(
			'SELECT a.name,om.userid'.
			' FROM opmessage_usr om,operations o,actions a'.
			' WHERE om.operationid=o.operationid'.
				' AND o.actionid=a.actionid'.
				' AND '.dbConditionInt('om.userid', $userids),
			1
		);

		if ($db_action = DBfetch($db_actions)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('User "%1$s" is used in "%2$s" action.',
				$db_users[$db_action['userid']]['alias'], $db_action['name']
			));
		}

		// Check if deleted users have a map.
		$db_maps = API::Map()->get([
			'output' => ['name', 'userid'],
			'userids' => $userids,
			'limit' => 1
		]);

		if ($db_maps) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('User "%1$s" is map "%2$s" owner.', $db_users[$db_maps[0]['userid']]['alias'], $db_maps[0]['name'])
			);
		}

		// Check if deleted users have a screen.
		$db_screens = API::Screen()->get([
			'output' => ['name', 'userid'],
			'userids' => $userids,
			'limit' => 1
		]);

		if ($db_screens) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('User "%1$s" is screen "%2$s" owner.', $db_users[$db_screens[0]['userid']]['alias'],
					$db_screens[0]['name']
				)
			);
		}

		// Check if deleted users have a slide show.
		$db_slideshows = DB::select('slideshows', [
			'output' => ['name', 'userid'],
			'filter' => ['userid' => $userids],
			'limit' => 1
		]);

		if ($db_slideshows) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('User "%1$s" is slide show "%2$s" owner.', $db_users[$db_slideshows[0]['userid']]['alias'],
					$db_slideshows[0]['name']
				)
			);
		}

		// Check if deleted users have dashboards.
		$db_dashboards = API::Dashboard()->get([
			'output' => ['name', 'userid'],
			'filter' => ['userid' => $userids],
			'limit' => 1
		]);

		if ($db_dashboards) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('User "%1$s" is dashboard "%2$s" owner.', $db_users[$db_dashboards[0]['userid']]['alias'],
					$db_dashboards[0]['name']
				)
			);
		}
	}

	/**
	 * Authenticate a user using LDAP.
	 *
	 * The $user array must have the following attributes:
	 * - user       - user name
	 * - password   - user password
	 *
	 * @param array $user
	 *
	 * @return bool
	 */
	protected function ldapLogin(array $user) {
		$config = select_config();
		$cnf = [];

		foreach ($config as $id => $value) {
			if (strpos($id, 'ldap_') !== false) {
				$cnf[str_replace('ldap_', '', $id)] = $config[$id];
			}
		}

		$ldap_status = (new CFrontendSetup())->checkPhpLdapModule();

		if ($ldap_status['result'] != CFrontendSetup::CHECK_OK) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $ldap_status['error']);
		}

		$ldapValidator = new CLdapAuthValidator(['conf' => $cnf]);

		if ($ldapValidator->validate($user)) {
			return true;
		}
		else {
			self::exception($ldapValidator->isConnectionError()
					? ZBX_API_ERROR_PARAMETERS
					: ZBX_API_ERROR_PERMISSIONS,
				$ldapValidator->getError()
			);
		}
	}

	public function logout($user) {
		$api_input_rules = ['type' => API_OBJECT, 'fields' => []];
		if (!CApiInputValidator::validate($api_input_rules, $user, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$sessionid = self::$userData['sessionid'];

		$db_sessions = DB::select('sessions', [
			'output' => ['userid'],
			'filter' => [
				'sessionid' => $sessionid,
				'status' => ZBX_SESSION_ACTIVE
			],
			'limit' => 1
		]);

		if (!$db_sessions) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot logout.'));
		}

		DB::delete('sessions', [
			'status' => ZBX_SESSION_PASSIVE,
			'userid' => $db_sessions[0]['userid']
		]);
		DB::update('sessions', [
			'values' => ['status' => ZBX_SESSION_PASSIVE],
			'where' => ['sessionid' => $sessionid]
		]);

		$this->addAuditDetails(AUDIT_ACTION_LOGOUT, AUDIT_RESOURCE_USER);

		self::$userData = null;

		return true;
	}

	/**
	 * @param array $user
	 *
	 * @return string|array
	 */
	public function login(array $user) {
		$api_input_rules = ['type' => API_OBJECT, 'fields' => [
			'user' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED, 'length' => 255],
			'password' =>	['type' => API_STRING_UTF8, 'flags' => API_REQUIRED, 'length' => 255],
			'userData' =>	['type' => API_FLAG]
		]];
		if (!CApiInputValidator::validate($api_input_rules, $user, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$config = select_config();
		$group_to_auth_map = [
			GROUP_GUI_ACCESS_SYSTEM => $config['authentication_type'],
			GROUP_GUI_ACCESS_INTERNAL => ZBX_AUTH_INTERNAL,
			GROUP_GUI_ACCESS_LDAP => ZBX_AUTH_LDAP,
			GROUP_GUI_ACCESS_DISABLED => $config['authentication_type']
		];

		$db_user = $this->findByAlias($user['user'], ($config['ldap_case_sensitive'] == ZBX_AUTH_CASE_SENSITIVE),
			$config['authentication_type'], true
		);

		if ($db_user['attempt_failed'] >= ZBX_LOGIN_ATTEMPTS) {
			$sec_left = ZBX_LOGIN_BLOCK - (time() - $db_user['attempt_clock']);

			if ($sec_left > 0) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_n('Account is blocked for %1$s second.', 'Account is blocked for %1$s seconds.', $sec_left)
				);
			}
		}

		try {
			switch ($group_to_auth_map[$db_user['gui_access']]) {
				case ZBX_AUTH_LDAP:
					$this->ldapLogin($user);
					break;

				case ZBX_AUTH_INTERNAL:
					if (md5($user['password']) !== $db_user['passwd']) {
						self::exception(ZBX_API_ERROR_PERMISSIONS, _('Login name or password is incorrect.'));
					}
					break;

				default:
					self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions for system access.'));
					break;
			}
		}
		catch (APIException $e) {
			if ($e->getCode() == ZBX_API_ERROR_PERMISSIONS) {
				++$db_user['attempt_failed'];
			}

			DB::update('users', [
				'values' => [
					'attempt_failed' => $db_user['attempt_failed'],
					'attempt_clock' => time(),
					'attempt_ip' => substr($db_user['userip'], 0, 39)
				],
				'where' => ['userid' => $db_user['userid']]
			]);

			$this->addAuditDetails(AUDIT_ACTION_LOGIN, AUDIT_RESOURCE_USER, _('Login failed.'), $db_user['userid'],
				$db_user['userip']
			);

			if ($e->getCode() == ZBX_API_ERROR_PERMISSIONS && $db_user['attempt_failed'] >= ZBX_LOGIN_ATTEMPTS) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_n('Account is blocked for %1$s second.', 'Account is blocked for %1$s seconds.', ZBX_LOGIN_BLOCK)
				);
			}

			self::exception(ZBX_API_ERROR_PERMISSIONS, $e->getMessage());
		}

		// Start session.
		unset($db_user['passwd']);
		$db_user = $this->createSession($user, $db_user);
		self::$userData = $db_user;

		$this->addAuditDetails(AUDIT_ACTION_LOGIN, AUDIT_RESOURCE_USER);

		return array_key_exists('userData', $user) && $user['userData'] ? $db_user : $db_user['sessionid'];
	}

	/**
	 * Method is ONLY for internal use!
	 * Login user by alias. Return array with user data.
	 *
	 * @param string $alias      Authenticated user alias value.
	 * @param bool   $api_call   Check is method called via API call or from local php file.
	 *
	 * @return array
	 */
	public function loginHttp($alias, $api_call = true) {
		if ($api_call) {
			return self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect method "%1$s.%2$s".', 'user', 'loginHttp'));
		}

		$config = select_config();
		$db_user = $this->findByAlias($alias, ($config['http_case_sensitive'] == ZBX_AUTH_CASE_SENSITIVE),
			$config['authentication_type'], false
		);

		unset($db_user['passwd']);
		$db_user = $this->createSession([
			'user' => $alias,
			'password' => mt_rand()
		], $db_user);
		self::$userData = $db_user;

		$this->addAuditDetails(AUDIT_ACTION_LOGIN, AUDIT_RESOURCE_USER);
		return $db_user;
	}

	/**
	 * Check if session id is authenticated.
	 *
	 * @param array  $session
	 * @param string $session[]['sessionid']  (required) session id to be checked
	 * @param bool   $session[]['extend']     (optional) extend session (update lastaccess time)
	 *
	 * @return array
	 */
	public function checkAuthentication(array $session) {
		$api_input_rules = ['type' => API_OBJECT, 'fields' => [
			'sessionid' =>	['type' => API_STRING_UTF8, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('sessions', 'sessionid')],
			'extend' =>	['type' => API_BOOLEAN, 'default' => true]
		]];
		if (!CApiInputValidator::validate($api_input_rules, $session, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$sessionid = $session['sessionid'];

		// access DB only once per page load
		if (self::$userData !== null && self::$userData['sessionid'] === $sessionid) {
			return self::$userData;
		}

		$time = time();

		$db_sessions = DB::select('sessions', [
			'output' => ['userid', 'lastaccess'],
			'sessionids' => $sessionid,
			'filter' => ['status' => ZBX_SESSION_ACTIVE]
		]);

		if (!$db_sessions) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Session terminated, re-login, please.'));
		}

		$db_session = $db_sessions[0];

		$db_users = DB::select('users', [
			'output' => ['userid', 'alias', 'name', 'surname', 'url', 'autologin', 'autologout', 'lang', 'refresh',
				'type', 'theme', 'attempt_failed', 'attempt_ip', 'attempt_clock', 'rows_per_page'
			],
			'userids' => $db_session['userid']
		]);

		if (!$db_users) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Session terminated, re-login, please.'));
		}

		$db_user = $db_users[0];

		$usrgrps = $this->getUserGroupsData($db_user['userid']);

		$db_user['sessionid'] = $sessionid;
		$db_user['debug_mode'] = $usrgrps['debug_mode'];
		$db_user['userip'] = $usrgrps['userip'];
		$db_user['gui_access'] = $usrgrps['gui_access'];

		$autologout = timeUnitToSeconds($db_user['autologout']);

		// Check system permissions.
		if (($autologout != 0 && $db_session['lastaccess'] + $autologout <= $time)
				|| $usrgrps['users_status'] == GROUP_STATUS_DISABLED) {
			DB::delete('sessions', [
				'status' => ZBX_SESSION_PASSIVE,
				'userid' => $db_user['userid']
			]);
			DB::update('sessions', [
				'values' => ['status' => ZBX_SESSION_PASSIVE],
				'where' => ['sessionid' => $sessionid]
			]);

			self::exception(ZBX_API_ERROR_PARAMETERS, _('Session terminated, re-login, please.'));
		}

		if ($session['extend'] && $time != $db_session['lastaccess']) {
			DB::update('sessions', [
				'values' => ['lastaccess' => $time],
				'where' => ['sessionid' => $sessionid]
			]);
		}

		self::$userData = $db_user;

		return $db_user;
	}

	private function getUserGroupsData($userid) {
		$usrgrps = [
			'debug_mode' => GROUP_DEBUG_MODE_DISABLED,
			'userip' => (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '')
				? $_SERVER['HTTP_X_FORWARDED_FOR']
				: $_SERVER['REMOTE_ADDR'],
			'users_status' => GROUP_STATUS_ENABLED,
			'gui_access' => GROUP_GUI_ACCESS_SYSTEM
		];

		$db_usrgrps = DBselect(
			'SELECT g.debug_mode,g.users_status,g.gui_access'.
			' FROM usrgrp g,users_groups ug'.
			' WHERE g.usrgrpid=ug.usrgrpid'.
				' AND ug.userid='.$userid
		);

		while ($db_usrgrp = DBfetch($db_usrgrps)) {
			if ($db_usrgrp['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
				$usrgrps['debug_mode'] = GROUP_DEBUG_MODE_ENABLED;
			}
			if ($db_usrgrp['users_status'] == GROUP_STATUS_DISABLED) {
				$usrgrps['users_status'] = GROUP_STATUS_DISABLED;
			}
			if ($db_usrgrp['gui_access'] > $usrgrps['gui_access']) {
				$usrgrps['gui_access'] = $db_usrgrp['gui_access'];
			}
		}

		return $usrgrps;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$userIds = zbx_objectValues($result, 'userid');

		// adding usergroups
		if ($options['selectUsrgrps'] !== null && $options['selectUsrgrps'] != API_OUTPUT_COUNT) {
			$relationMap = $this->createRelationMap($result, 'userid', 'usrgrpid', 'users_groups');

			$dbUserGroups = API::UserGroup()->get([
				'output' => $options['selectUsrgrps'],
				'usrgrpids' => $relationMap->getRelatedIds(),
				'preservekeys' => true
			]);

			$result = $relationMap->mapMany($result, $dbUserGroups, 'usrgrps');
		}

		// adding medias
		if ($options['selectMedias'] !== null && $options['selectMedias'] != API_OUTPUT_COUNT) {
			$db_medias = API::getApiService()->select('media', [
				'output' => $this->outputExtend($options['selectMedias'], ['userid', 'mediaid', 'mediatypeid']),
				'filter' => ['userid' => $userIds],
				'preservekeys' => true
			]);

			// 'sendto' parameter in media types with 'type' == MEDIA_TYPE_EMAIL are returned as array.
			if (($options['selectMedias'] === API_OUTPUT_EXTEND || in_array('sendto', $options['selectMedias']))
					&& $db_medias) {
				$db_email_medias = DB::select('media_type', [
					'output' => [],
					'filter' => [
						'mediatypeid' => zbx_objectValues($db_medias, 'mediatypeid'),
						'type' => MEDIA_TYPE_EMAIL
					],
					'preservekeys' => true
				]);

				foreach ($db_medias as &$db_media) {
					if (array_key_exists($db_media['mediatypeid'], $db_email_medias)) {
						$db_media['sendto'] = explode("\n", $db_media['sendto']);
					}
				}
				unset($db_media);
			}

			$relationMap = $this->createRelationMap($db_medias, 'userid', 'mediaid');

			$db_medias = $this->unsetExtraFields($db_medias, ['userid', 'mediaid', 'mediatypeid'],
				$options['selectMedias']
			);
			$result = $relationMap->mapMany($result, $db_medias, 'medias');
		}

		// adding media types
		if ($options['selectMediatypes'] !== null && $options['selectMediatypes'] != API_OUTPUT_COUNT) {
			$relationMap = $this->createRelationMap($result, 'userid', 'mediatypeid', 'media');
			$mediaTypes = API::Mediatype()->get([
				'output' => $options['selectMediatypes'],
				'mediatypeids' => $relationMap->getRelatedIds(),
				'preservekeys' => true
			]);
			$result = $relationMap->mapMany($result, $mediaTypes, 'mediatypes');
		}

		return $result;
	}

	/**
	 * Initialize session for user. Returns user data array with valid sessionid.
	 *
	 * @param array  $user              Authentication credentials.
	 * @param string $user['user']      User alias value.
	 * @param string $user['password']  User password, is used in sessionid generation.
	 * @param array  $db_user           User data from database.
	 *
	 * @return array
	 */
	private function createSession($user, $db_user) {
		$db_user['sessionid'] = md5(microtime().md5($user['password']).$user['user'].mt_rand());

		DB::insert('sessions', [[
			'sessionid' => $db_user['sessionid'],
			'userid' => $db_user['userid'],
			'lastaccess' => time(),
			'status' => ZBX_SESSION_ACTIVE
		]], false);

		if ($db_user['attempt_failed'] != 0) {
			DB::update('users', [
				'values' => ['attempt_failed' => 0],
				'where' => ['userid' => $db_user['userid']]
			]);
		}

		return $db_user;
	}

	/**
	 * Find user by alias. Return user data from database.
	 *
	 * @param string $alias             User alias to search for.
	 * @param bool   $case_sensitive    Perform case sensitive search.
	 * @param int    $default_auth      System default authentication type.
	 * @param bool   $do_group_check    Is actual only when $case_sensitive equals false. In HTTP authentication case
	 *                                  user alias string is case insensitive string even for groups with frontend
	 *                                  access GROUP_GUI_ACCESS_INTERNAL.
	 *
	 * @return array
	 */
	private function findByAlias($alias, $case_sensitive, $default_auth, $do_group_check) {
		$db_users = [];
		$group_to_auth_map = [
			GROUP_GUI_ACCESS_SYSTEM => $default_auth,
			GROUP_GUI_ACCESS_INTERNAL => ZBX_AUTH_INTERNAL,
			GROUP_GUI_ACCESS_LDAP => ZBX_AUTH_LDAP,
			GROUP_GUI_ACCESS_DISABLED => $default_auth
		];
		$fields = ['userid', 'alias', 'name', 'surname', 'url', 'autologin', 'autologout', 'lang', 'refresh',
			'type', 'theme', 'attempt_failed', 'attempt_ip', 'attempt_clock', 'rows_per_page', 'passwd'
		];

		if ($case_sensitive) {
			$db_users = DB::select('users', [
				'output' => $fields,
				'filter' => ['alias' => $alias]
			]);
		}
		else {
			$db_users_rows = DBfetchArray(DBselect(
				'SELECT '.implode(',', $fields).
				' FROM users'.
					' WHERE LOWER(alias)='.zbx_dbstr(strtolower($alias))
			));

			if ($do_group_check) {
				// Users with ZBX_AUTH_INTERNAL access attribute 'alias' is always case sensitive.
				foreach($db_users_rows as $db_user_row) {
					$permissions = $this->getUserGroupsData($db_user_row['userid']);

					if ($group_to_auth_map[$permissions['gui_access']] != ZBX_AUTH_INTERNAL
							|| $db_user_row['alias'] === $alias) {
						$db_users[] = $db_user_row;
					}
				}
			}
			else {
				$db_users = $db_users_rows;
			}
		}

		if (!$db_users) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Login name or password is incorrect.'));
		}
		elseif (count($db_users) > 1) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Authentication failed: %1$s.', _('supplied credentials are not unique'))
			);
		}

		$db_user = reset($db_users);
		$usrgrps = $this->getUserGroupsData($db_user['userid']);

		if ($usrgrps['users_status'] == GROUP_STATUS_DISABLED) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('No permissions for system access.'));
		}

		$db_user['debug_mode'] = $usrgrps['debug_mode'];
		$db_user['userip'] = $usrgrps['userip'];
		$db_user['gui_access'] = $usrgrps['gui_access'];

		return $db_user;
	}
}
