<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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


class CJsonRpc {

	const VERSION = '2.0';

	public $json;

	/**
	 * API client to use for making requests.
	 *
	 * @var CApiClient
	 */
	protected $apiClient;

	private $_error;
	private $_response;
	private $_error_list;
	private $_zbx2jsonErrors;
	private $_jsonDecoded;

	/**
	 * Constructor.
	 *
	 * @param CApiClient $apiClient
	 * @param string $data
	 */
	public function __construct(CApiClient $apiClient, $data) {
		$this->apiClient = $apiClient;

		$this->json = new CJson();
		$this->initErrors();

		$this->_error = false;
		$this->_response = [];
		$this->_jsonDecoded = $this->json->decode($data, true);
	}

	/**
	 * Executes API requests.
	 *
	 * @return string JSON encoded value
	 */
	public function execute() {
		if ($this->json->hasError()) {
			$this->jsonError(null, '-32700', null, null, true);
			return $this->json->encode($this->_response[0], [], false, false);
		}

		if (!is_array($this->_jsonDecoded) || $this->_jsonDecoded == []) {
			$this->jsonError(null, '-32600', null, null, true);
			return $this->json->encode($this->_response[0], [], false, false);
		}

		foreach (zbx_toArray($this->_jsonDecoded) as $call) {
			$call = is_array($call) ? $call : [$call];

			// notification
			if (!array_key_exists('id', $call)) {
				$call['id'] = null;
			}

			if (!$this->validate($call)) {
				continue;
			}

			list($api, $method) = array_merge(explode('.', $call['method']), [null, null]);
			$result = $this->apiClient->callMethod($api, $method, $call['params'],
				array_key_exists('auth', $call) ? $call['auth'] : null
			);

			$this->processResult($call, $result);
		}

		if (is_array($this->_jsonDecoded)
				&& array_keys($this->_jsonDecoded) === range(0, count($this->_jsonDecoded) - 1)) {
			// Return response as encoded batch if $this->_jsonDecoded is associative array.
			return $this->json->encode($this->_response, [], false, false);
		}

		return $this->json->encode($this->_response[0], [], false, false);
	}

	public function validate($call) {
		if (!isset($call['jsonrpc'])) {
			$this->jsonError($call['id'], '-32600', _('JSON-rpc version is not specified.'), null, true);

			return false;
		}

		if ($call['jsonrpc'] != self::VERSION) {
			$this->jsonError($call['id'], '-32600',
				_s('Expecting JSON-rpc version 2.0, "%1$s" is given.', $call['jsonrpc']), null, true
			);

			return false;
		}

		if (!isset($call['method'])) {
			$this->jsonError($call['id'], '-32600', _('JSON-rpc method is not defined.'));

			return false;
		}

		if (isset($call['params']) && !is_array($call['params'])) {
			$this->jsonError($call['id'], '-32602', _('JSON-rpc params is not an Array.'));

			return false;
		}

		return true;
	}

	public function processResult($call, CApiClientResponse $response) {
		if ($response->errorCode) {
			$errno = $this->_zbx2jsonErrors[$response->errorCode];

			$this->jsonError($call['id'], $errno, $response->errorMessage, $response->debug);
		}
		else {
			// Notifications MUST NOT be answered
			if ($call['id'] === null) {
				return;
			}

			$formedResp = [
				'jsonrpc' => self::VERSION,
				'result' => $response->data,
				'id' => $call['id']
			];

			$this->_response[] = $formedResp;
		}
	}

	private function jsonError($id, $errno, $data = null, $debug = null, $force_err = false) {
		// Notifications MUST NOT be answered, but error MUST be generated on JSON parse error
		if (is_null($id) && !$force_err) {
			return;
		}

		$this->_error = true;

		if (!isset($this->_error_list[$errno])) {
			$data = _s('JSON-rpc error generation failed. No such error "%1$s".', $errno);
			$errno = '-32400';
		}

		$error = $this->_error_list[$errno];

		if (!is_null($data)) {
			$error['data'] = $data;
		}
		if (!is_null($debug)) {
			$error['debug'] = $debug;
		}


		$formed_error = [
			'jsonrpc' => self::VERSION,
			'error' => $error,
			'id' => $id
		];

		$this->_response[] = $formed_error;
	}

	private function initErrors() {
		$this->_error_list = [
			'-32700' => [
				'code' => -32700,
				'message' => _('Parse error'),
				'data' => _('Invalid JSON. An error occurred on the server while parsing the JSON text.')
			],
			'-32600' => [
				'code' => -32600,
				'message' => _('Invalid Request.'),
				'data' => _('The received JSON is not a valid JSON-RPC Request.')
			],
			'-32601' => [
				'code' => -32601,
				'message' => _('Method not found.'),
				'data' => _('The requested remote-procedure does not exist / is not available')
			],
			'-32602' => [
				'code' => -32602,
				'message' => _('Invalid params.'),
				'data' => _('Invalid method parameters.')
			],
			'-32603' => [
				'code' => -32603,
				'message' => _('Internal error.'),
				'data' => _('Internal JSON-RPC error.')
			],
			'-32500' => [
				'code' => -32500,
				'message' => _('Application error.'),
				'data' => _('No details')
			],
			'-32400' => [
				'code' => -32400,
				'message' => _('System error.'),
				'data' => _('No details')
			],
			'-32300' => [
				'code' => -32300,
				'message' => _('Transport error.'),
				'data' => _('No details')
			]
		];

		$this->_zbx2jsonErrors = [
			ZBX_API_ERROR_NO_METHOD => '-32601',
			ZBX_API_ERROR_PARAMETERS => '-32602',
			ZBX_API_ERROR_NO_AUTH => '-32602',
			ZBX_API_ERROR_PERMISSIONS => '-32500',
			ZBX_API_ERROR_INTERNAL => '-32500'
		];
	}
}
